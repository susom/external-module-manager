<?php


namespace Stanford\ExternalModuleManager;

use REDCapEntity\EntityList;

/** @var ExternalModuleManager $module */

#$module->createExternalModuleUtilizationLogs();

//$list = new EntityList('external_modules_utilization', $module);
//$list->setOperations(['create', 'update', 'delete'])
//    ->render('project'); // Context: project.
//

#$module->createProjectsExternalModuleUsageLogs();

//$list = new EntityList('project_external_modules_usage', $module);
//$list->setOperations(['create', 'update', 'delete'])
//    ->render('project'); // Context: project.

$data = array(
    'module_prefix' => '',
    'project_id' => '',
    'project_title' => '',
    'status' => 123,
    'record_count' => 'asd',
    'is_em_enabled' => 123,
    'number_of_settings_rows' => 'asd',
);

$result = $module->getEntityFactory()->create('external_modules_utilization', $data);
