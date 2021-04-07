<?php

namespace Stanford\ExternalModuleManager;
/** @var \Stanford\ExternalModuleManager\ExternalModuleManager $module */


try {
    # test commit
    if (isset($_POST) && !empty($_POST)) {
        $payload = $_POST['data'];
    } else {
        throw new \Exception("something went wrong!");
    }
} catch (\Exception $e) {
    $module->emError($e->getMessage());
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}
