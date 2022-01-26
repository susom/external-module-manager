<?php
namespace Stanford\ExternalModuleManager;
/** @var ExternalModuleManager $module */

if (isset($_GET['name'])) {
    $module->processCron();
} elseif (isset($_GET['refresh']) && isset($_GET['project_id'])) {
    $result = $module->refreshProjectEMUsage();
    echo json_encode(array('status' => 'success', 'ids' => $result));
}
$module->setProjects();

//echo $module->getUrl('pages/services.php', true, true);
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
        <div class="col-2">
            <a href="<?php echo $module->getUrl('pages/trigger_cron.php') . '&name=em_utilization' ?>"
               class="btn btn-primary">Trigger EM Utilization Cron</a>
        </div>
        <div class="col-2">
            <a href="<?php echo $module->getUrl('pages/trigger_cron.php') . '&name=project_em_usage' ?>"
               class="btn btn-primary">Trigger Project EM Usage Cron</a>
        </div>
    </div>
    <hr>

    <div class="row">
        <div class="col-4">
            <a id="project-em-usage"
               href="<?php echo $module->getUrl('pages/trigger_cron.php') . '&refresh=project_em_usage' ?>"
               data-href="<?php echo $module->getUrl('pages/trigger_cron.php') . '&refresh=project_em_usage' ?>"
               class="btn btn-warning">Refresh Project EM usage for single Project in current instance</a>
        </div>
        <div class="col-6">
            <label for="select">If you want to refresh specific project please select.</label>
            <select class="form-control" id="select" name="select">
                <?php
                foreach ($module->getProjects() as $project) {
                    ?>
                    <option
                        value="<?php echo $project['project_id'] ?>"><?php echo '(' . $project['project_id'] . ')' . $project['app_title'] ?></option>
                    <?php
                }
                ?>
            </select>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        $('#select').select2();

        $('#select').on('select2:select', function (e) {
            var id = e.params.data.id
            var href = $("#project-em-usage").data('href') + '&project_id=' + id
            $("#project-em-usage").attr('href', href)
        });
    });
</script>
