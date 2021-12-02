<?php


namespace Stanford\ExternalModuleManager;

use REDCapEntity\EntityList;

/** @var ExternalModuleManager $module */

#$module->createExternalModuleUtilizationLogs();

$list = new EntityList('projects_overdue_payments', $module);
$list->setOperations(['create', 'update', 'delete'])
    ->render('project'); // Context: project.

?>
