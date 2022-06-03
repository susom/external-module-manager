<?php


namespace Stanford\ExternalModuleManager;

use REDCapEntity\EntityList;

/** @var ExternalModuleManager $module */

#$module->createExternalModuleUtilizationLogs();

//$list = new EntityList('external_modules_utilization', $module);
//$list->setOperations(['create', 'update', 'delete'])
//    ->setExposedFilters(['date'])
//    ->render('project'); // Context: project.

?>
<style>
    .loader {
        display: none;
        position: fixed;
        z-index: 1000;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        background: rgba(255, 255, 255, 0.8) url('https://redcap.stanford.edu/api/?type=module&prefix=covid_appointment_scheduler&page=src/images/progress.gif') 50% 50% no-repeat;
        z-index: 10000;
    }


    /* When the body has the loading class, we turn
       the scrollbar off with overflow:hidden */
    body.loading .loader {
        overflow: hidden;
    }

    /* Anytime the body has the loading class, our
       modal element will be visible */
    body.loading .loader {
        display: block;
    }
</style>
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.4/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.4/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.4/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.4/js/buttons.print.min.js"></script>

<div class="container-fluid">

    <table id="em-utilization" class="table table-bordered">
        <thead>

        </thead>
        <tbody>

        </tbody>
    </table>
</div>
<div class="loader"><!-- Place at bottom of page --></div>

<script>
    var records = []
    var columns = []

    $body = $("body");


    jQuery(document).ready(function () {

        // jQuery(document).on({
        //     ajaxStart: function () {
        //         console.log('start')
        //         $body.addClass("loading");
        //     },
        //     ajaxStop: function () {
        //         console.log('stop')
        //         $body.removeClass("loading");
        //     }
        // });

        jQuery.ajax({
            'url': "<?php echo $module->getUrl("ajax/get_em_utilizations_no_match.php", false, true) ?>",
            'type': 'GET',
            'beforeSend': function () {
                $body.addClass("loading");
            },
            'success': function (data) {
                var res = JSON.parse(data)
                console.log(res)
                records = res.data
                columns = res.columns
                if (records.length > 0) {
                    $('#em-utilization').dataTable({
                        dom: '<"production-filter"><lf<t>Bip>',
                        "data": records,
                        "pageLength": 100,
                        "columns": columns,
                        "aaSorting": [[0, "asc"]],
                        buttons: [
                            'copy', 'csv', 'excel', 'pdf', 'print'
                        ],
                        initComplete: function () {
                            // we only need day and location filter.
                            this.api().columns([12]).every(function (index) {
                                // below function will add filter to remove previous/completed appointments
                                var column = this;
                                $('<input type="checkbox" id="production-filter" name="old" checked/>')
                                    .appendTo($('.production-filter'))
                                    .on('change', function () {
                                        var val = $.fn.dataTable.util.escapeRegex(
                                            $(this).val()
                                        );
                                        if (document.getElementById('production-filter').checked) {
                                            column
                                                .search("^$", true, false)
                                                .draw();
                                        } else {
                                            column
                                                .search("^[1-9]\d*$", true, false)
                                                .draw();
                                        }

                                    });

                            });
                        }
                    });
                }
            },
            'error': function (request, error) {
                alert("Request: " + JSON.stringify(request));
            },
            'complete': function (request, error) {
                $body.removeClass("loading");
            }
        });

    });
</script>
