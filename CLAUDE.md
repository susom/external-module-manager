# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

NEVER USE SUBAGENT FOR ANY TASK UNLESS THE USER EXPLICITLY ASKS FOR IT.

For your reference, read ../REDCAP_TECHNICAL_GUIDANCE.md for general REDCap core guidance. and ../EXTERNAL_MODULES_INDEX.md for general External Module guidance.
## What this is

A REDCap External Module (EM), `Stanford\ExternalModuleManager`, that runs inside a REDCap install (loaded from `www/modules-local/`). It is not a standalone app — there is no build step, package manager, or test suite. PHP files are interpreted directly by the REDCap framework at request time, and pages only work when invoked through REDCap's plugin/link routing (each `pages/*.php` and `ajax/*.php` file is included by the framework with a `$module` variable already injected — see the `/** @var ExternalModuleManager $module */` docblock at the top of each file).

`composer.json`/`composer.lock` are effectively empty placeholders. Classes like `\GuzzleHttp\Client` used in `classes/Client.php` are **not** vendored locally (`vendor/composer/autoload_classmap.php` is empty) — they resolve through REDCap core's own global autoloader at runtime, not this module's `vendor/`.

There is no lint/test/build command to run. To verify a change, deploy the module folder into a running REDCap instance (folder name must match `prefix_version`, e.g. `external_module_manager_v9.9.9`) and exercise the relevant page/cron/ajax endpoint.

## Module manifest (`config.json`)

This is the source of truth for what pages exist, what settings are configurable, and what runs on a schedule — read it before `ExternalModuleManager.php` when orienting:
- `links.project` / `links.control-center` map URLs (`pages/*.php`) to nav entries. A `pages/*.php` file that is **not** listed here (e.g. `pages/cost_overview.php`, `pages/services.php`, `pages/badge.php`, `pages/test_*.php`) is not reachable through normal REDCap navigation — some are dead/WIP (`cost_overview.php` calls `$module->scanProject()`, which does not exist anywhere in the codebase).
- `no-auth-pages` lists endpoints REDCap will serve without a logged-in REDCap user — this is required for the cross-instance service calls (see below) and is not a bug.
- `project-settings` / `system-settings` define the `instances` sub-setting list, the shared `api-token`, and the hub project id (`em-project-id`).
- `crons` maps cron names to `ExternalModuleManager` method names and their schedule.

## Core architecture: hub-and-spoke across REDCap instances

This module is deployed identically on multiple REDCap instances (`som-dev`, `som-prod`, `lpch-dev`, `lpch-prod`, `shc-dev`, `shc-prod` — see the `instance` choice lists repeated in `redcap_entity_types()`). One project on one instance acts as the **hub** (its project id is the `em-project-id` system setting, default `16000`); the `instances` project-setting on the hub lists every spoke's `service-url` pointing at that instance's `pages/services.php`.

The flow, for each of the three data domains (EM utilization, project EM usage, monthly charges):
1. A cron fires on the hub (`projectEMUsageTriggerCron`, `generateProjectEMCharges`, `eMUtilizationTriggerCron` in `ExternalModuleManager.php`). Each one does **not** call the processing logic directly — it makes an authenticated Guzzle HTTP GET back into its own `ajax/cron.php` / `ajax/em_charges_cron.php` / `ajax/cron_em_util.php`, scoped to `pid=<em-project-id>`. This indirection makes the cron run as a real authenticated web request against the hub project rather than a bare CLI/cron context.
2. That ajax entry point calls a `process*()` method (`processProjectEMUsage`, `processEMMonthlyCharges`, `processEMUtilization`), which loops over `getInstances()` and calls `getInstanceEMBody($name, $url)` for each spoke.
3. `getInstanceEMBody()` POSTs to the spoke's `pages/services.php` with `secret_token` (the shared `api-token` system setting) and a `request` name. `services.php` calls `$module->processRequest()`, which validates the token (`verifyToken()`) and dispatches on `request` to the matching data-producing method on the **spoke** (e.g. `getEMMonthlyChargesRecords()`, which just echoes JSON of local data).
4. Back on the hub, the JSON body from each spoke is turned into REDCapEntity records (see below).

When touching cross-instance behavior, trace all four steps — the cron method, the ajax file it hits, the `process*` fan-out method, and the spoke-side `get*`/`process*` method it calls — since they live in different files but form one logical operation.

## Entity storage (REDCapEntity)

Persistent records are defined via the `redcap_entity_types()` hook in `ExternalModuleManager.php` and stored through the `REDCapEntity` module's `EntityFactory` (`$this->getEntityFactory()`), not plain custom SQL tables. Entity types:
- `external_modules_utilization` — per-EM global stats (older design; also mirrors into REDCap records in the hub project via `REDCap::saveData`, keyed by module prefix as the record id — see `createExternalModuleREDCapRecord()`).
- `project_external_modules_usage` — per-project/per-EM usage snapshot.
- `external_modules_charges` — one row per project+EM+month maintenance charge, created by `processEMMonthlyCharges()`.
- `projects_overdue_payments` — legacy overdue-payment tracking.
- `pta_accounts`, `project_pta_mapping`, `monthly_pta_reports` — the PTA/finance billing subsystem (below).

Some code paths also read raw `redcap_external_module*` core tables directly via `$this->query(...)` (e.g. `setExternalModulesDBRecords()`, `setProjectEMUsage()`) to compute stats before writing them into entities — don't assume all data originates from entities.

## Finance / PTA billing subsystem (this branch)

Added on `billing-branch` on top of the existing charge-tracking system. Entry point `pages/finance_reports.php` (tabbed UI: Reports / Generate / PTA Accounts / Project↔PTA Mapping) + `js/finance_reports.js`, backed by a single dispatcher `ajax/finance_report_action.php` keyed by an `action` param (`generate_report`, `list_reports`, `get_report`, `pta_drilldown`, `list_pta`, `save_pta`, `assign_pta`, `remove_pta_mapping`, `get_mappings`, `preview_report`) plus a separate `ajax/finance_report_download.php` for CSV export.

All report-generation/aggregation logic lives in `ExternalModuleManager.php` under the `PTA & Monthly Finance Report Methods` section near the end of the file (`generateMonthlyPTAReport()`, `saveMonthlyPTAReport()`, `exportMonthlyPTAReportCSV()`, `getPTADrillDown()`). Reports are generated from `external_modules_charges` entities grouped by the project's mapped PTA (`project_pta_mapping`), with projects lacking a mapping bucketed under `Unmapped`; a generated report snapshot is persisted as JSON in a `monthly_pta_reports` entity rather than recomputed on every view (`saveMonthlyPTAReport()` reuses an existing report for the month/year unless `regenerate` is passed).

`RMA_Billing_Workflow_Options.html`, `RMA_Migration_Plan_and_Open_Questions.html`, and `Workflow_Diagrams.html` in the repo root are design/planning documents for a further migration (new `em_enable_requests` / `finance_billing_lines` / `finance_billing_runs` entities, approval workflows). None of that is implemented yet — treat them as forward-looking design notes, not a description of current code.

## Other conventions

- `emLoggerTrait.php` (used via `use emLoggerTrait;` in the main class) provides `emLog()`/`emError()`/`emDebug()`, which no-op unless the separate `em_logger` module is installed/enabled; debug output additionally requires the `enable-system-debug-logging` or `enable-project-debug-logging` setting.
- `classes/User.php::hasDesignRights()` is the only user-permission check in the codebase (super user or REDCap project "design" right) — most endpoints instead rely on the shared-token auth described above, not REDCap user permissions.
- The module also integrates with a separate `ExternalModuleDeployment` EM: when a project sets `external-module-deployment` (an EM key), the constructor loads that module instance into `$this->deploymentEm` via `\ExternalModules\ExternalModules::getModuleInstance()`.
- `getFolderPath()` / `lookupExternalModuleGithubInformation()` / `lookupExternalModulesDefaultBranch()` inspect **sibling directories** under `modules-local` (naming convention `<prefix>_<version>`, matching this module's own folder name) for a `.git` or `.gitrepo` file to infer where a tracked EM's source lives — this only works when other EM folders are checked out as git repos (or git subrepos) alongside this one on disk.
