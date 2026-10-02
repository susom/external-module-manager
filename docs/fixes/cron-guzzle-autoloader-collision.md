# Fix: `project_em_monthly_charge` cron fails with `Psr7\Utils::asciiToUpper()` (REDCap 17.5.2)

## Symptom (som-prod, after the REDCap 17.5.2 upgrade)

```
Cron job 'project_em_monthly_charge' failed for External Module 'external_module_manager'.
GuzzleHttp\Exception\ClientException: Client error: `GET https://redcap.stanford.edu/api/?type=module&prefix=external_module_manager&page=ajax%2Fem_charges_cron&NOAUTH&pid=16000`
resulted in a `400 Bad Request` response:
Fatal error: Uncaught Error: Call to undefined method GuzzleHttp\Psr7\Utils::asciiToUpper() in /var/www/html/redcap (truncated...)
  ...
#10 .../external_module_manager_v9.9.9/ExternalModuleManager.php(1094): GuzzleHttp\Client->request()
#11 .../ExternalModules.php(6626): ...->generateProjectEMCharges()
```

The cron process itself was fine. The fatal is the **body** of the response from the inner web request that
`generateProjectEMCharges()` makes to its own `ajax/em_charges_cron.php`.

## Root cause

A class-autoloader collision between REDCap core's Guzzle and copies bundled by other EMs:

1. `ajax/em_charges_cron.php` → `processEMMonthlyCharges()` → `getEntityFactory()` → `new \REDCapEntity\EntityFactory()`.
2. `EntityFactory::reset()` calls `ExternalModules::getModuleInstance()` for **every enabled module on the server**.
3. Many of those modules `require vendor/autoload.php` with their own (older) `guzzlehttp/guzzle` + `psr7` + `promises`.
   Composer **prepends** its autoloader, so from then on those copies win for any Guzzle class not yet loaded.
4. `GuzzleHttp\Client` / `CurlFactory` had already been loaded from **core** (in this module's constructor), but
   `Psr7\Utils`, `Psr7\Request`, `Psr7\Uri`, `Promise\Promise` were not yet loaded, so they came from a **bundled** copy.
5. REDCap 17.5.2 core Guzzle calls `Psr7\Utils::asciiToUpper()`. No bundled copy has it (not even psr7 2.12.5),
   so `getInstanceEMBody()`'s POST to the spokes fatals.

So no single other module is "the culprit". Any enabled EM bundling psr7 can trigger it, and it will recur whenever
core Guzzle moves ahead of the bundled copies.

## Reproduction (local, E2E against the real cron URL)

Local REDCap is 17.2.3 (core Guzzle 7.11, which doesn't call `asciiToUpper`), so the exact fatal needs 17.5.2 core.
The **mechanism** was reproduced on the actual request path:

- Temporarily configured hub pid 148 with one `instances` entry pointing to its own local `pages/services.php`.
- Added a temporary diagnostic in `processEMMonthlyCharges()` printing `ReflectionClass::getFileName()` per class.
- `GET /api/?type=module&prefix=external_module_manager&page=ajax%2Fem_charges_cron&NOAUTH&pid=148` showed:

```
GuzzleHttp\Client          => /var/www/html/redcap_v17.2.3/Libraries/vendor/guzzlehttp/guzzle/src/Client.php
GuzzleHttp\Handler\CurlFactory => /var/www/html/redcap_v17.2.3/Libraries/vendor/.../CurlFactory.php
GuzzleHttp\Psr7\Utils      => /var/www/html/modules-local/epic_authenticator_v0.0.0/vendor/guzzlehttp/psr7/src/Utils.php
GuzzleHttp\Psr7\Request    => /var/www/html/modules-local/epic_authenticator_v0.0.0/vendor/guzzlehttp/psr7/src/Request.php
GuzzleHttp\Promise\Promise => /var/www/html/modules-local/epic_authenticator_v0.0.0/vendor/guzzlehttp/promises/src/Promise.php
```

i.e. core and bundled Guzzle mixed in one request (bundled psr7 2.8.0 has no `asciiToUpper`).

## Fix

This module no longer uses Guzzle at all (`classes/Client.php`):

- `Client::get($url)` / `Client::post($url, $formParams, $headers)`: plain cURL, so this module is immune to
  whatever Guzzle copies other modules register.
- The three trigger crons (`projectEMUsageTriggerCron`, `generateProjectEMCharges`, `eMUtilizationTriggerCron`) and
  `getInstanceEMBody()` use it. Request shape is unchanged (same URLs, `Accept: application/json`, same form params).
- Behaviour kept from Guzzle defaults: TLS verification on, follows up to 5 redirects (http/https only), no overall
  timeout (the self-call waits for the whole spoke fan-out). Added a 30s connect timeout.
- Non-2xx and transport errors still **throw** (`\RuntimeException`), so the REDCap cron-failure email still fires.
  The message now includes up to 1000 chars of the tag-stripped body, instead of Guzzle's 120-char truncation that hid
  the real fatal location.
- Removed the eager `new \GuzzleHttp\Client()` from the constructor (it ran in every request that instantiates this
  module, including other modules' `EntityFactory` resets).

REDCap core's `http_get()`/`http_post()` were considered and rejected: they disable TLS peer verification (we send the
shared `api-token`) and return the body of a 400, which would make this exact failure silent.

## Verification (local)

- Inner request after the fix: HTTP 200, no Guzzle/Psr7 class loaded by this module, and an `external_modules_charges`
  row was created for the spoke's data.
- Outer path: `ExternalModules::callCronMethod(<id>, 'project_em_monthly_charge')` (same entry point as the prod trace)
  ran cron → self-call → spoke fan-out → charge row created, no exception.
- Error paths: unreachable host, HTML 404, and a wrong-token POST to `services.php` all throw `RuntimeException` with
  a readable message (e.g. `returned HTTP 404: {"status":"error","message":"wrong token provided"}`).
- `php -l` clean on both changed files. All temporary local settings/rows were reverted.

## Follow-ups (not part of this fix)

- **Other EMs bundling Guzzle** (`epic_authenticator`, `redcap_to_starr_link`, `docebointegration`,
  `redcap_onedirectory_lookup`, `calendar_mate`, `proj_ekg_review`, ...) are a hazard for *every* module that uses core
  Guzzle on the same request. They should drop the bundled copy (core provides it) or scope it (e.g. PHP-Scoper/Strauss).
- `ajax/cron` and `ajax/cron_em_util` (targets of `project_em_usage` / `em_utilization` crons) are **not** in
  `no-auth-pages` on `master`, while the crons call them with `NOAUTH`. Those crons likely never reach their processing
  code; worth checking separately.
- One failing spoke aborts the whole monthly-charge run (the first exception stops the loop). Consider per-instance
  error handling that logs and continues, then reports.
