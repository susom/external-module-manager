# Anonymous endpoints: token-gate the server-to-server pages and remove the test page

2026-11-06, on `master` (based on `828b0d3`). This needs a matching one-file change in rit_dashboard:
`classes/ManagerEM.php`. **On every instance, deploy rit_dashboard before EM Manager** ([why](#deploy)).

## Problem

Our Apache config lets `/api/?…&NOAUTH` requests through OIDC, so every page in `no-auth-pages` is reachable from the
internet. On `master`, four of them did work for anyone. Each was reproduced locally on `828b0d3` with anonymous `curl`:

| Page | Anonymous request, before the fix |
| --- | --- |
| `pages/test_auth` | HTTP 200 "Test Page". A leftover test page that logs `Authentication::buildDsnArray()` |
| `ajax/em_charges_cron` | HTTP 200. Ran the monthly-charge fan-out to every configured instance |
| `ajax/refresh_project_em_util` | HTTP 200 `{"status":"success",…}`. Created a usage row (2 → 3 rows) for whatever project id was passed |
| `pages/services` | The token check was `==` after `FILTER_SANITIZE_STRING`. With no `api-token` configured, an empty or tags-only token got HTTP 200 and the data |

## Why the cron still calls itself over HTTP

The obvious fix is to run the charge work inside the cron and drop the anonymous page. That doesn't work:
- REDCap's cron runner defines `USERID='SYSTEM'` but never `SUPER_USER`.
- REDCapEntity's check on `project` properties (`redcap_entity_v9.9.9/classes/Entity.php:188`) evaluates
  `SUPER_USER` whenever `USERID` is defined. In cron context that throws `Error: Undefined constant "SUPER_USER"`
  (reproduced locally).
- Every charge row has a `project` property.

So the work has to run in a NOAUTH web request, which has no `USERID`. That's what the self-call gives it. The fix
keeps the self-call and makes it prove who it is.

## Fix

| File | Change |
| --- | --- |
| `ExternalModuleManager.php` | `isValidServiceToken()` fails closed when `api-token` is empty, compares the raw value with `hash_equals()`, and no longer uses `FILTER_SANITIZE_STRING`. `isAuthorizedServiceRequest()` means a POST with a valid `secret_token` |
| `ExternalModuleManager.php` | `processRequest()` (behind `pages/services`) uses the same check and reads the request name raw; it's only compared, never output |
| `ExternalModuleManager.php` | `generateProjectEMCharges()` POSTs the token to its own page, in the body so it never lands in a URL or access log |
| `ExternalModuleManager.php` | `refreshProjectEMUsage()` validates the project id with `FILTER_VALIDATE_INT`. A bad id used to cause an uncaught `TypeError` |
| `ajax/em_charges_cron.php` | Returns 403 JSON unless the request is authorized |
| `ajax/refresh_project_em_util.php` | Returns 403 JSON unless authorized. Takes the id from the body or the query string, and catches `\Throwable` (400 JSON) |
| `config.json`, `pages/test_auth.php` | The test page is removed |
| rit_dashboard `classes/ManagerEM.php` | `updateProjectEMUtil()` POSTs EM Manager's `api-token` in the body, int-casts the ids it puts in the URL, and logs failures with `emError()` instead of failing silently |

The `project_em_usage` and `em_utilization` crons are not changed by this fix.

## Verification

All checks ran locally on REDCap 17.5.9 with PHP 8.3, against a temporary hub: PID 166 with one instance pointing at
`http://localhost/…/pages/services`, and a throwaway project set to production.

| Check | Before | After |
| --- | --- | --- |
| Anonymous GET `test_auth` | 200 "Test Page" | 200 "The NOAUTH parameter is not allowed on this page." (framework refusal) |
| Anonymous GET, or POST with empty or wrong token, `em_charges_cron` | 200, fan-out ran | 403 `Forbidden`, no rows |
| Anonymous GET, or POST with wrong token, `refresh_project_em_util` | 200, row written | 403 `Forbidden`, no rows |
| `services` with `api-token` missing, empty or tags-only token | 200 + data | 404 "wrong token provided" |
| Authorized refresh with id `abc` | fatal `TypeError` page | 400 JSON "A valid REDCap project id is required." |
| `project_em_monthly_charge` run through `ExternalModules::callCronMethod()`, as `cron.php` runs it | 1 charge row | identical row (instance, project, module, fees, month, year) |
| rit_dashboard `ManagerEM::updateProjectEMUtil()`, current code, against fixed EM Manager | (n/a) | `false`, no refresh: the reason for the deploy order |
| Same, updated code, against fixed EM Manager | (n/a) | success, row refreshed |
| Same, updated code, against old EM Manager | (n/a) | success: rit_dashboard can go first |

- `php -l` is clean on every PHP file in both modules.
- The REDCap Module Security Scan now has no `FILTER_SANITIZE_STRING` warnings (4 before). Its other findings are
  the same as before this change.
- Test rows and the temporary settings were all restored afterwards.

<a id="deploy"></a>
## Deploy (every instance that runs EM Manager `master`)

The anonymous pages exist on every instance running this module, not just som-prod, and the commit makes the holes
public once it's pushed. So push and deploy together, and roll it out everywhere.

1. **See what each pin will ship.** On PID 16000, open the `external_module_manager` and `rit_dashboard` records and
   note each instance's pinned commit. Moving a pin ships every commit between the old pin and the new one, so check
   `git log <old>..<new>` for each.
2. **rit_dashboard first.** On every instance that runs it, deploy the `ManagerEM.php` change. It works with both the
   old and the new EM Manager.
3. **Check the token on every instance:** Control Center → External Modules → Manage → External Module Manager →
   Configure. "Token to be used to communicate between REDCap instances" must be set and identical everywhere. After
   this fix, an instance without it refuses every call instead of accepting an empty token. On the hub, a missing
   token makes the monthly-charge cron fail, and you get the cron-failure email.
4. **Then EM Manager**, on every instance. Ideally mid-month: the charge cron only creates rows for the current month,
   so a mid-month deploy leaves time to fix a failed run.
5. **After the deploy, not before**, check each instance from a browser window that isn't logged in to REDCap. Use the
   instance's hostname, and the `pid` from that instance's service URL in the hub's EM Manager settings (16000 on
   som-prod). Before the deploy, the first URL would actually run the charge fan-out.
   - Open `https://<host>/api/?type=module&prefix=external_module_manager&page=ajax%2Fem_charges_cron&NOAUTH&pid=<pid>`.
     Expect `{"status":"error","message":"Forbidden"}`.
   - Change the page to `pages%2Ftest_auth`. Expect "The NOAUTH parameter is not allowed on this page."
6. **The next day**, on the hub: Control Center → Cron Jobs → history for that date. The `external_module_manager` /
   `project_em_monthly_charge` run should have no error, and there should be no "Cron job … failed" email.

If a cron pod still runs an image older than this change, its old unauthenticated self-call now gets 403 and fails
every day with an email. That's expected. Update that cron's image; don't revert this fix.

If EM Manager goes out before rit_dashboard on an instance, the EM usage refresh that rit_dashboard and go_prod
trigger stops working until rit_dashboard is updated. The updated `ManagerEM` logs that case with `emError()`.

## Merging into other branches

`config.json` will conflict in `no-auth-pages` on branches that changed that list. Resolve it by keeping the list from
this fix, and review any extra entries the branch adds against the same rule: a page that does work must check the
token.
