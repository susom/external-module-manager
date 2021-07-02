<?php

namespace Stanford\ExternalModuleManager;
/** @var ExternalModuleManager $module */

$dsn = \Authentication::buildDsnArray();

$module->emLog($dsn);

$userid = \Authentication::authenticate();

echo "Test Page";
