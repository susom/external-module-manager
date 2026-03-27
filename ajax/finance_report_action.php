<?php

namespace Stanford\ExternalModuleManager;
/** @var ExternalModuleManager $module */

header("Content-type: application/json");

try {
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {

        // ── Generate / regenerate a monthly PTA report ────────────────
        case 'generate_report':
            $month = filter_var($_POST['month'] ?? '', FILTER_VALIDATE_INT);
            $year  = filter_var($_POST['year'] ?? '', FILTER_VALIDATE_INT);
            $regenerate = !empty($_POST['regenerate']);

            if (!$month || !$year) {
                throw new \InvalidArgumentException("Month and year are required.");
            }
            $result = $module->saveMonthlyPTAReport($month, $year, $regenerate);
            echo json_encode(['status' => 'success', 'data' => $result]);
            break;

        // ── List all saved reports (summary) ──────────────────────────
        case 'list_reports':
            $reports = $module->listMonthlyPTAReports();
            echo json_encode(['status' => 'success', 'data' => $reports]);
            break;

        // ── Get a single report with full data ────────────────────────
        case 'get_report':
            $reportId = filter_var($_GET['report_id'] ?? '', FILTER_VALIDATE_INT);
            if (!$reportId) {
                throw new \InvalidArgumentException("report_id is required.");
            }
            $report = $module->getMonthlyPTAReportById($reportId);
            if (!$report) {
                throw new \RuntimeException("Report not found.");
            }
            echo json_encode(['status' => 'success', 'data' => $report]);
            break;

        // ── PTA drill-down inside a report ────────────────────────────
        case 'pta_drilldown':
            $reportId  = filter_var($_GET['report_id'] ?? '', FILTER_VALIDATE_INT);
            $ptaNumber = $_GET['pta_number'] ?? '';
            if (!$reportId || $ptaNumber === '') {
                throw new \InvalidArgumentException("report_id and pta_number are required.");
            }
            $data = $module->getPTADrillDown($reportId, $ptaNumber);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        // ── List PTA accounts ─────────────────────────────────────────
        case 'list_pta':
            echo json_encode(['status' => 'success', 'data' => $module->getAllPTAAccounts()]);
            break;

        // ── Save (create/update) a PTA account ───────────────────────
        case 'save_pta':
            $ptaData = [
                'pta_number'      => trim($_POST['pta_number'] ?? ''),
                'pta_description' => trim($_POST['pta_description'] ?? ''),
                'contact_name'    => trim($_POST['contact_name'] ?? ''),
                'contact_email'   => trim($_POST['contact_email'] ?? ''),
                'active'          => isset($_POST['active']) ? (int)$_POST['active'] : 1,
            ];
            if (!empty($_POST['id'])) {
                $ptaData['id'] = (int)$_POST['id'];
            }
            if ($ptaData['pta_number'] === '') {
                throw new \InvalidArgumentException("PTA Number is required.");
            }
            $id = $module->savePTAAccount($ptaData);
            echo json_encode(['status' => 'success', 'id' => $id]);
            break;

        // ── Assign PTA to project ─────────────────────────────────────
        case 'assign_pta':
            $projectId   = filter_var($_POST['project_id'] ?? '', FILTER_VALIDATE_INT);
            $ptaEntityId = filter_var($_POST['pta_id'] ?? '', FILTER_VALIDATE_INT);
            $ptaNumber   = trim($_POST['pta_number'] ?? '');
            if (!$projectId || !$ptaEntityId || $ptaNumber === '') {
                throw new \InvalidArgumentException("project_id, pta_id, and pta_number are required.");
            }
            $id = $module->assignPTAToProject($projectId, $ptaEntityId, $ptaNumber);
            echo json_encode(['status' => 'success', 'id' => $id]);
            break;

        // ── Remove PTA mapping ────────────────────────────────────────
        case 'remove_pta_mapping':
            $mappingId = filter_var($_POST['mapping_id'] ?? '', FILTER_VALIDATE_INT);
            if (!$mappingId) {
                throw new \InvalidArgumentException("mapping_id is required.");
            }
            $module->removePTAFromProject($mappingId);
            echo json_encode(['status' => 'success']);
            break;

        // ── Get project-PTA mappings ──────────────────────────────────
        case 'get_mappings':
            $projectId   = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
            $ptaEntityId = !empty($_GET['pta_id']) ? (int)$_GET['pta_id'] : null;
            $mappings = $module->getProjectPTAMappings($projectId, $ptaEntityId);
            echo json_encode(['status' => 'success', 'data' => $mappings]);
            break;

        // ── Preview (generate without saving) ─────────────────────────
        case 'preview_report':
            $month = filter_var($_POST['month'] ?? '', FILTER_VALIDATE_INT);
            $year  = filter_var($_POST['year'] ?? '', FILTER_VALIDATE_INT);
            if (!$month || !$year) {
                throw new \InvalidArgumentException("Month and year are required.");
            }
            $report = $module->generateMonthlyPTAReport($month, $year);
            echo json_encode(['status' => 'success', 'data' => $report]);
            break;

        default:
            throw new \InvalidArgumentException("Unknown action: $action");
    }

} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

