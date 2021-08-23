<?php
namespace Stanford\ExternalModuleManager;
/** @var ExternalModuleManager $module */

if (isset($_GET['name'])) {
    $module->processCron();
}
echo $module->getUrl('pages/services.php', true, true);
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
