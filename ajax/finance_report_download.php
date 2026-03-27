<?php

namespace Stanford\ExternalModuleManager;
/** @var ExternalModuleManager $module */

try {
    $reportId = filter_var($_GET['report_id'] ?? '', FILTER_VALIDATE_INT);
    if (!$reportId) {
        throw new \InvalidArgumentException("report_id is required.");
    }

    $report = $module->getMonthlyPTAReportById($reportId);
    if (!$report) {
        throw new \RuntimeException("Report not found.");
    }

    $csv = $module->exportMonthlyPTAReportCSV($reportId);
    $filename = sprintf(
        'em_charges_pta_report_%04d_%02d.csv',
        $report['report_year'],
        $report['report_month']
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo $csv;
    exit;

} catch (\Exception $e) {
    header("Content-type: application/json");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

