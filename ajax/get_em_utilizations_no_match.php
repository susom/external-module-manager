<?php

namespace Stanford\ExternalModuleManager;
/** @var ExternalModuleManager $module */

try {
    $data = $module->getEMUtilizationRecordDoNotMatchREDCapProjectRecords();
    $records = $columns = [];
    if (!empty($data)) {
        $columns = $module->prepareEntityColumns('external_modules_utilization', array_keys($data[0]));
        $records = array();

        # datatable does not accept associative arrays
        foreach ($data as $item) {
            $row = array_values($item);
            $prifix = $row[2];
            $row[2] = '<a target="_blank" href="https://redcap.stanford.edu' . APP_PATH_WEBROOT . 'DataEntry/record_home.php?pid=16000&arm=1&id=' . $prifix . '">' . $prifix . '</a>';
            $records[] = $row;
        }
    }

    echo json_encode(array('status' => 'success', 'data' => $records, 'columns' => $columns));
} catch (\Exception $e) {
    header("Content-type: application/json");
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}
