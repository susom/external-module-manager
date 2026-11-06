<?php

namespace Stanford\ExternalModuleManager;
/** @var ExternalModuleManager $module */

header("Content-type: application/json");

// NOAUTH page: only server-side callers holding the shared api-token may run it (rit_dashboard's ManagerEM,
// which go_prod uses before its RMA check).
if (!$module->isAuthorizedServiceRequest()) {
    http_response_code(403);
    echo json_encode(array('status' => 'error', 'message' => 'Forbidden'));
    return;
}

try {
    // Accept the id from the body or, as rit_dashboard sends it, the query string.
    $projectId = $_POST['redcap_project_id'] ?? $_GET['redcap_project_id'] ?? '';
    $data = $module->refreshProjectEMUsage($projectId);
    echo json_encode(array('status' => 'success', 'data' => $data));
} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}
