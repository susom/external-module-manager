<?php
namespace Stanford\ExternalModuleManager;
/** @var ExternalModuleManager $module */

if (isset($_GET['name'])) {
    if ($_GET['name'] == 'em_utilization') {
        $module->createExternalModuleUtilizationLogs();
    }
    if ($_GET['name'] == 'project_em_usage') {
        $module->createProjectsExternalModuleUsageLogs();
    }
}

echo $module->getUrl("pages/test_auth", true, true) . '<br>';
echo $module->getUrl("pages/test_auth", false, true) . '<br>';
echo $module->getUrl("pages/test_auth", true, false) . '<br>';
echo $module->getUrl("pages/test_auth", false, false) . '<br>';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm">
            <h1>
                Manually Trigger Crons
            </h1>
        </div>
    </div>
    <div class="row">
        <form method="post">
            <div class="float-right">
                <a href="<?php echo $module->getUrl('pages/trigger_cron.php') . '&name=em_utilization' ?>"
                   class="btn btn-primary">Trigger EM Utilization Cron</a>
                <a href="<?php echo $module->getUrl('pages/trigger_cron.php') . '&name=project_em_usage' ?>"
                   class="btn btn-primary">Trigger Project EM Usage Cron</a>
            </div>
        </form>
    </div>
</div>
