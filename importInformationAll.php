<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php'; ?>
<script type="text/javascript" src="<?=$module->getUrl('js/dataTables.buttons.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/buttons.html5.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/buttons.print.min.js')?>"></script>
<link rel="stylesheet" type="text/css" href="<?=$module->getUrl('css/style.css')?>">

<?php
$docname = "BigDataImport_".date("Y-m-d H:s");
?>
<script>
    $(document).ready(function() {
        var table = $('.import-archive').DataTable( {
            bLengthChange: true,
            pageLength: 50,
            dom: "<'row'<'col-sm-3'l><'col-sm-4'f><'col-sm-5'p>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            order: [0, "desc"]
        });

        //To change the text on select
        $(".dropdown-menu li").click(function(){
            var selText = $(this).html();
            $(this).parents('.dropdown').find('.dropdown-toggle').html(selText+" <input type='hidden' value='"+$(this).text()+"' id='publication_type'/><span class='caret' style='float: right;margin-top:8px'></span>");
            //when any of the filters is called upon change datatable data
            var table = $('.import-archive').DataTable();
            table.draw();
        });

        var table_sort = $('.import-archive').DataTable();
        table_sort.column(4).visible(false);
        table_sort.column(5).visible(false);
        table_sort.column(7).visible(false);

        //To filter the data
        $.fn.dataTable.ext.search.push(
            function( settings, data, dataIndex ) {
                var activity = $('#selectActivity').text().trim();
                var column_activity = data[7];

                if(activity != 'Select All' && column_activity == activity){
                    return true;
                }else if(activity == 'Select All'){
                    return true;
                }

                return false;
            }
        );

        $('#selectActivity').change( function() {
            var table = $('.import-archive').DataTable();
            table.draw();
        } );

        //POPOVER
        jQuery('[data-toggle="popover"]').popover({
            html : true,
            content: function() {
                return $(jQuery(this).data('target-selector')).html();
            },
            title: function(){
                return '<span style="padding-top:0px;">'+jQuery(this).data('title')+'<span class="close" style="line-height: 0.5;padding-top:0px;padding-left: 10px">&times;</span></span>';
            }
        }).on('shown.bs.popover', function(e){
            var popover = jQuery(this);
            jQuery(this).parent().find('div.popover .close').on('click', function(e){
                popover.popover('hide');
            });
            $('div.popover .close').on('click', function(e){
                popover.popover('hide');
            });

        });
        //We add this or the second time we click it won't work. It's a bug in bootstrap
        $('[data-toggle="popover"]').on("hidden.bs.popover", function() {
            if($(this).data("bs.popover").inState == undefined){
                //BOOTSTRAP 4
                $(this).data("bs.popover")._activeTrigger.click = false;
            }else{
                //BOOTSTRAP 3
                $(this).data("bs.popover").inState.click = false;
            }
        });

        //To prevent the popover from scrolling up on click
        $("a[rel=popover]")
            .popover()
            .click(function(e) {
                e.preventDefault();
            });

        //EXPORT
        var docname = <?=json_encode($docname)?>;
        new $.fn.dataTable.Buttons( table, {
            "buttons": [
                {
                    extend: 'csv',
                    text: '<i class="fa fa-table"></i> Excel',
                    exportOptions: {
                        columns: [0,1,2,4,5,7,8]
                    },
                    title: docname
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print"></i> Print',
                    exportOptions: {
                        columns: [0,1,2,4,5,6,7,8],
                        stripHtml: false
                    },
                    customize: function ( win ) {
                        $(win.document.body).css( 'font-size', '10pt' );

                        $(win.document.body).find( 'table' ).addClass( 'compact' ).css( 'font-size', 'inherit' );
                        var medias = win.document.querySelectorAll('[media="screen"]');
                        for(var i=0; i < medias.length;i++){ medias.item(i).media="all" };
                    }
                }
            ]
        });

        table.buttons().containers().appendTo( '#options_wrapper' );

        $('#sortable_table_filter').appendTo( '#options_wrapper' );
        $('#sortable_table_filter').attr( 'style','float: left;padding-left: 90px;padding-top: 5px;' );
        $('.dt-buttons').attr( 'style','float: left;' );
    } );

</script>

<div style="max-width: 950px">
    <div class="backTo">
        <a href="<?=$module->getUrl('import.php')?>">< Back to Big Data Import</a>
    </div>
    <h4 style="margin-top: 20px;margin-bottom: 10px;">Import information</h4>
    <p>View and search all current and past imports.</p>
    <br>
    <div class="optionSelect">
        <div style="float:left" id="options_wrapper"></div>
        <div style="float:right">
            <div style="float:left;padding-left:30px;margin-top: 8px;">
                Status:
            </div>
            <div style="float:left;padding-left:10px">
                <ul class="nav navbar-nav navbar-right" style="padding-right: 40px;">
                    <li class="menu-item dropdown">
                        <a href="#" data-toggle="dropdown" class="dropdown-toggle form-control output_select btn-group" id="selectActivity">Select All<span class="caret"></span></a>
                        <ul class="dropdown-menu output-dropdown-menu" >
                            <li>Select All</li>
                            <li><i class="fa fa-check fa-fw" aria-hidden="true"></i> success</li>
                            <li><i class="fa fa-times fa-fw" aria-hidden="true"></i> error</li>
                            <li><i class="fa fa-ban fa-fw" aria-hidden="true"></i> cancelled</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div style="max-width: 900px">
        <div>
            <table class="table import-archive" data-sortable id="sortable_table">
                <thead>
                <tr>
                    <th style="min-width: 160px;" data-sorted="true">Date/Time</th>
                    <th>File</th>
                    <th style="text-align: center">Uploaded By</th>
                    <th style="text-align: center">Records</th>
                    <th style="text-align: center">Records Total</th>
                    <th style="text-align: center">Records Imported</th>
                    <th style="text-align: center">Status</th>
                    <th style="text-align: center">Status text</th>
                    <th style="text-align: center">Checked</th>
                    <th style="text-align: center">Import #</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $results = $module->queryLogs("
                            select log_id, timestamp, file, status, import, checked, totalrecordsIds, edoc 
                            where project_id = '".$_GET['pid']."' AND message='Data'
                            order by log_id desc
                        ");

                if($results->num_rows === 0){
                    ?>
                    <tr>
                        <td colspan="10">No import logs available</td>
                        <td style="display: none;"></td>
                        <td style="display: none;"></td>
                        <td style="display: none;"></td>
                        <td style="display: none;"></td>
                        <td style="display: none;"></td>
                        <td style="display: none;"></td>
                        <td style="display: none;"></td>
                        <td style="display: none;"></td>
                        <td style="display: none;"></td>
                        <td style="display: none;"></td>
                    </tr>
                    <?php
                }
                else{
                    $index = 0;
                    while($row = $results->fetch_assoc()){
                        $status = "<span class='fa fa-check fa-fw' title='success'></span>";
                        $status_text = "success";
                        if($row['status'] == '0'){
                            $status = "<span class='fa fa-check fa-fw' title='success'></span>";
                            $status_text = "success";
                        }else if($row['status'] == '1'){
                            $status = "<span class='fa fa-times fa-fw' title='error'></span>";
                            $status_text = "error";
                        }else if($row['status'] == '2'){
                            $status = "<span class='fa fa-ban fa-fw' title='cancelled'></span>";
                            $status_text = "cancelled";
                        }

                        $checked = "No";
                        if($row['checked'] == "1"){
                            $checked = "Yes";
                        }

                        if($row['totalrecordsIds'] != ""){
                            $records = count(explode(",",$row['totalrecordsIds']));
                            $total = '<a href="#" rel="popover" data-toggle="popover" data-target-selector="#records-activated'.$index.'" data-title="Records for Import #'.$row['import'].'" style="color: #337ab7;">Total: '.$records.'</a></div><br/>';
                            $total .= '<div id="records-activated'.$index.'" class="hidden">
                                                            <p>'.$row['totalrecordsIds'].'</p>
                                                       </div>';
                            $index++;
                        }

                        $resultsUser = $module->queryLogs("
                                    select log_id, edoc, user 
                                    where project_id = '".$_GET['pid']."' AND message='DataUser' AND edoc='".$row['edoc']."'
                                    order by log_id desc
                                ");
                        $user = "";
                        if($rowUser = $resultsUser->fetch_assoc()){
                            $user = $rowUser['user'];
                        }

                        ?>
                        <tr>
                            <td><?= $row['timestamp'] ?></td>
                            <td><?= $row['file'] ?></td>
                            <td><?= $user ?></td>
                            <td style="text-align: center"><?= $total ?></td>
                            <td style="text-align: center"><?= $records ?></td>
                            <td style="text-align: center"><?= $row['totalrecordsIds'] ?></td>
                            <td style="text-align: center"><?= $status ?></td>
                            <td style="text-align: center"><?= $status_text ?></td>
                            <td style="text-align: center"><?= $checked ?></td>
                            <td style="text-align: center"><?= $row['import'] ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>