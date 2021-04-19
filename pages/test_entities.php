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

$list = new EntityList('project_external_modules_usage', $module);
$list->setOperations(['create', 'update', 'delete'])
    ->render('project'); // Context: project.
