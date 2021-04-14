<?php

namespace Stanford\ExternalModuleManager;
/** @var \Stanford\ExternalModuleManager\ExternalModuleManager $module */


try {
    //
    if (!isset($_POST['key'])) {
        throw new \Exception("key does not exist");
    }
    if (md5($_POST['key']) != md5($module->getProjectSetting('travis-config-secret'))) {
        throw new \Exception("key does not match");
    }


    #
    $module->generateREDCapBuildConfigCSV();
} catch (\Exception $e) {
    $module->emError($e->getMessage());
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}
