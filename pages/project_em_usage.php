<?php


namespace Stanford\ExternalModuleManager;

use REDCapEntity\EntityList;

/** @var ExternalModuleManager $module */

#$module->createProjectsExternalModuleUsageLogs();

$list = new EntityList('project_external_modules_usage', $module);
$list->setOperations(['create', 'update', 'delete'])
    ->render('project'); // Context: project.
