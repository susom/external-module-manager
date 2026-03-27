<?php

namespace Stanford\ExternalModuleManager;

/** @var ExternalModuleManager $module */

$ajaxUrl    = $module->getUrl("ajax/finance_report_action.php", false, true);
$downloadUrl = $module->getUrl("ajax/finance_report_download.php", false, true);

$currentMonth = date('n');
$currentYear  = date('Y');
?>

<!-- DataTables -->
<link  rel="stylesheet" href="//cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="//cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- custom styles -->
<link rel="stylesheet" href="<?php echo $module->getUrl('css/finance_reports.css'); ?>">

<div class="container-fluid" id="finance-app">

    <!-- ──────────── Header ──────────── -->
    <div class="row mb-3">
        <div class="col">
            <h2><i class="fas fa-file-invoice-dollar mr-2"></i>Monthly EM Charges – Finance Reports</h2>
            <p class="text-muted">
                Generate, view, and download monthly External Module charge reports grouped by PTA.
            </p>
        </div>
    </div>

    <!-- ──────────── Tabs ──────────── -->
    <ul class="nav nav-tabs" id="financeTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="reports-tab" data-toggle="tab" href="#reports-panel" role="tab">
                <i class="fas fa-list mr-1"></i>Reports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="generate-tab" data-toggle="tab" href="#generate-panel" role="tab">
                <i class="fas fa-plus-circle mr-1"></i>Generate Report
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="pta-tab" data-toggle="tab" href="#pta-panel" role="tab">
                <i class="fas fa-wallet mr-1"></i>PTA Accounts
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="mapping-tab" data-toggle="tab" href="#mapping-panel" role="tab">
                <i class="fas fa-project-diagram mr-1"></i>Project ↔ PTA Mapping
            </a>
        </li>
    </ul>

    <div class="tab-content pt-3" id="financeTabContent">

        <!-- ═══════════ REPORTS LIST ═══════════ -->
        <div class="tab-pane fade show active" id="reports-panel" role="tabpanel">
            <div id="reports-list-container">
                <p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading reports…</p>
            </div>

            <!-- Drill-down area (hidden by default) -->
            <div id="report-detail-container" style="display:none;">
                <hr>
                <div class="d-flex align-items-center mb-3">
                    <button class="btn btn-sm btn-outline-secondary mr-3" id="btn-back-to-list">
                        <i class="fas fa-arrow-left"></i> Back to reports
                    </button>
                    <h4 id="report-detail-title" class="mb-0"></h4>
                    <a id="btn-download-csv" class="btn btn-sm btn-success ml-auto" href="#" target="_blank">
                        <i class="fas fa-download mr-1"></i>Download CSV
                    </a>
                </div>
                <div id="report-summary" class="mb-3"></div>
                <div id="report-drilldown"></div>
            </div>
        </div>

        <!-- ═══════════ GENERATE ═══════════ -->
        <div class="tab-pane fade" id="generate-panel" role="tabpanel">
            <div class="card" style="max-width:500px;">
                <div class="card-body">
                    <h5 class="card-title">Generate Monthly Report</h5>
                    <div class="form-group">
                        <label for="gen-month">Month</label>
                        <select id="gen-month" class="form-control">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo ($m == $currentMonth) ? 'selected' : ''; ?>>
                                    <?php echo $module->getMonthName($m); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gen-year">Year</label>
                        <select id="gen-year" class="form-control">
                            <?php for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" class="custom-control-input" id="gen-regenerate">
                        <label class="custom-control-label" for="gen-regenerate">
                            Regenerate (overwrite) if report already exists
                        </label>
                    </div>
                    <button id="btn-generate" class="btn btn-primary">
                        <i class="fas fa-cogs mr-1"></i>Generate Report
                    </button>
                    <div id="gen-feedback" class="mt-3"></div>
                </div>
            </div>
        </div>

        <!-- ═══════════ PTA ACCOUNTS ═══════════ -->
        <div class="tab-pane fade" id="pta-panel" role="tabpanel">
            <div class="d-flex mb-3">
                <button class="btn btn-primary btn-sm" id="btn-new-pta">
                    <i class="fas fa-plus mr-1"></i>New PTA Account
                </button>
            </div>
            <div id="pta-form-container" style="display:none;">
                <div class="card mb-3" style="max-width:600px;">
                    <div class="card-body">
                        <h5 class="card-title" id="pta-form-title">New PTA Account</h5>
                        <input type="hidden" id="pta-id">
                        <div class="form-group">
                            <label>PTA Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="pta-number">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" class="form-control" id="pta-description">
                        </div>
                        <div class="form-group">
                            <label>Contact Name</label>
                            <input type="text" class="form-control" id="pta-contact-name">
                        </div>
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" class="form-control" id="pta-contact-email">
                        </div>
                        <button class="btn btn-success btn-sm" id="btn-save-pta">Save</button>
                        <button class="btn btn-secondary btn-sm" id="btn-cancel-pta">Cancel</button>
                    </div>
                </div>
            </div>
            <table id="pta-table" class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th>PTA Number</th>
                        <th>Description</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- ═══════════ PROJECT ↔ PTA MAPPING ═══════════ -->
        <div class="tab-pane fade" id="mapping-panel" role="tabpanel">
            <div class="card mb-3" style="max-width:600px;">
                <div class="card-body">
                    <h5 class="card-title">Assign PTA to Project</h5>
                    <div class="form-group">
                        <label>Project ID</label>
                        <input type="number" class="form-control" id="map-project-id" placeholder="e.g. 21">
                    </div>
                    <div class="form-group">
                        <label>PTA Account</label>
                        <select class="form-control" id="map-pta-select">
                            <option value="">-- select --</option>
                        </select>
                    </div>
                    <button class="btn btn-primary btn-sm" id="btn-assign-pta">Assign</button>
                </div>
            </div>
            <table id="mapping-table" class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>Mapping ID</th>
                        <th>Project ID</th>
                        <th>PTA Number</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div><!-- /tab-content -->
</div><!-- /container -->

<div class="loader"><!-- loading overlay --></div>

<script>
    // Pass PHP URLs to JS
    var FINANCE = {
        ajaxUrl: <?php echo json_encode($ajaxUrl); ?>,
        downloadUrl: <?php echo json_encode($downloadUrl); ?>
    };
</script>
<script src="<?php echo $module->getUrl('js/finance_reports.js'); ?>"></script>

