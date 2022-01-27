<?php

namespace Stanford\ExternalModuleManager;
/** @var ExternalModuleManager $module */

try {
    $pid = filter_var($_GET['redcap_project_id'], FILTER_SANITIZE_NUMBER_INT);
    $data = $module->refreshProjectEMUsage($pid);
    echo json_encode(array('status' => 'success', 'data' => $data));
} catch (\Exception $e) {
    header("Content-type: application/json");
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}
