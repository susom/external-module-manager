<?php

namespace Stanford\ExternalModuleManager;
/** @var ExternalModuleManager $module */

// NOAUTH page: only the monthly-charge cron's self-call may run it (see generateProjectEMCharges()).
if (!$module->isAuthorizedServiceRequest()) {
    http_response_code(403);
    header("Content-type: application/json");
    echo json_encode(array('status' => 'error', 'message' => 'Forbidden'));
    return;
}

$module->processEMMonthlyCharges();
