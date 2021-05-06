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
    'version' => '',
    'date' => 'bolbol',
    'globally_enabled' => 123,
    'total_enabled_projects' => 'asd',
    'total_enabled_dev_projects' => 123,
    'total_enabled_prod_projects' => 'asd',
);

$result = $module->getEntityFactory()->create('external_modules_utilization', $data);
if (!$result) {
    echo '<pre>';
    print_r($module->getEntityFactory()->errors);
    echo '</pre>';
}
