<?php

namespace Stanford\ProjectPortal;

/** @var \Stanford\ExternalModuleManager\ExternalModuleManager $module */

try {
    $module->emLog("Process Request");
    $module->processRequest();
} catch (\LogicException $e) {
    $module->emError($e->getMessage());
    header("Content-type: application/json");
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
} catch (\Exception $e) {
    $module->emError($e->getMessage());
    header("Content-type: application/json");
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}

