<?php
ignore_user_abort(true);
set_time_limit(0);

$edoc_list = $module->getProjectSetting('edoc');
$import_cancel = $module->getProjectSetting('import-cancel');
$import_cancel_checked = $module->getProjectSetting('import-cancel-checked');
$import_checked = $module->getProjectSetting('import-checked');
$import_check_started = $module->getProjectSetting('import-checked-started');
$import_continue = $module->getProjectSetting('import-continue');
$import = $module->getProjectSetting('import');
$project_id = (int)$_GET['pid'];

$disabled = '';
foreach ($edoc_list as $index => $edoc) {
    if (($import_checked[$index] && !$import_continue[$index] && $import_check_started[$index] && !$import_cancel[$index]) || (!$import[$index] && !$import_checked[$index]) || ($import_check_started[$index] && $import_checked[$index])) {
        $disabled = 'disabled';
    }
}
?>
<link rel="stylesheet" type="text/css" href="<?=$module->getUrl('css/style.css')?>">
<script type="text/javascript" src="<?=$module->getUrl('js/functions.js')?>"></script>
<script>
    var deleteData_url = <?=json_encode($module->getUrl('deleteData.php'))?>;
    var cancelImport_url = <?=json_encode($module->getUrl('cancelImport.php'))?>;
    var deleteLogs_url = <?=json_encode($module->getUrl('deleteLogs.php'))?>;
    var startImport_url = <?=json_encode($module->getUrl('startImport.php'))?>;
    var continueImport_url = <?=json_encode($module->getUrl('continueImport.php'))?>;
    var anyFilesWithChecks_url = <?=json_encode($module->getUrl('anyFilesWithChecks.php'))?>;
    var pid = <?=$project_id?>;
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();

        $('.big-data-import-table').DataTable({
            ordering: false,
            bFilter: false,
            bLengthChange: true,
            pageLength: 50
        });

        if (($("#pending-files-div").children().length > 0 && $('#pending-files-div').first().text().trim() != "None") || (($("#checked-files-div").children().length > 0 && $('#checked-files-div').first().text().trim() != "None"))) {
            $('#start').prop('disabled',false);
            $('#start').css('cursor','pointer');
        }else{
            $('#start').css('cursor', 'not-allowed');
            $('#start').prop('disabled', true);
        }

        $('#deleteLogsForm').submit(function () {
            deleteLogs();
            return false;
        });

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
    } );

</script>

<div class="modal fade" id="deleteLogs" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <form class="form-horizontal" action="" method="post" id='deleteLogsForm'>
        <div class="modal-dialog" role="document" style="width: 500px">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Delete History</h4>
                </div>
                <div class="modal-body">
                    <div>Are you sure you want to delete the history?</div>
                    <div>Doing this will erase all of the information in the Recent Log Entry table and the module settings.</div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" id='btnCloseCodesModalDelete' data-dismiss="modal">Close</button>
                    <button type="submit" form="deleteLogsForm" class="btn btn-danger" id='btnModalAddFormForm'>Delete</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div id="big-data-info-wrapper">
    <?=$module->initializeJavascriptModuleObject()?>
    <script>
        /***SHOW DETAILS***/
        ExternalModules.Vanderbilt.BigDataImportExternalModule.details = {}

        ExternalModules.Vanderbilt.BigDataImportExternalModule.showDetails = function(logId, importdnumber){
            var width = window.innerWidth - 100;
            var height = window.innerHeight - 200;
            var content = '<pre style="max-height: ' + height + 'px">' + this.details[logId] + '</pre>'

            simpleDialog(content, 'Details Import #'+importdnumber, null, width)
        }

        ExternalModules.Vanderbilt.BigDataImportExternalModule.showSyncCancellationDetails = function(){
            var div = $('#big-data-import-module-cancellation-details').clone()
            div.show()

            var pre = div.find('pre');

            // Replace tabs with spaces for easy copy pasting into the mysql command line interface
            pre.html(pre.html().replace(/\t/g, '    '))

            ExternalModules.Vanderbilt.BigDataImportExternalModule.trimPreIndentation(pre[0])

            simpleDialog(div, 'Import Cancellation', null, 1000)
        }

        ExternalModules.Vanderbilt.BigDataImportExternalModule.trimPreIndentation = function(pre){
            var content = pre.innerHTML
            var firstNonWhitespaceIndex = content.search(/\S/)
            var leadingWhitespace = content.substr(0, firstNonWhitespaceIndex)
            pre.innerHTML = content.replace(new RegExp(leadingWhitespace, 'g'), '');
        }

    </script>
</div>
<div id="big-data-module-wrapper">
    <div>
            <?php
            if(array_key_exists('message', $_GET) &&  $_GET['message'] === 'S'){
                echo '<div class="alert" id="Msg" style="max-width: 1000px;background-color: #dff0d8;border-color: #d0e9c6 !important;color: #3c763d;">Your file has been uploaded.<br/>If you have set an email, a message will be sent once the import is ready, if not, refresh this page.</div>';
            }else if(array_key_exists('message', $_GET) &&  $_GET['message'] === 'D'){
                echo '<div class="alert" id="Msg" style="max-width: 1000px;background-color: #dff0d8;border-color: #d0e9c6 !important;color: #3c763d;">Your file has been deleted.</div>';
            }else if(array_key_exists('message', $_GET) &&  $_GET['message'] === 'C'){
                echo '<div class="alert" id="Msg" style="max-width: 1000px;background-color: #fff3cd;border-color: #ffeeba !important;color: #856404;">The import has been canceled and the imported file deleted.</div>';
            }else if(array_key_exists('message', $_GET) &&  $_GET['message'] === 'L'){
                echo '<div class="alert" id="Msg" style="max-width: 1000px;background-color: #dff0d8;border-color: #d0e9c6 !important;color: #3c763d;">Logs deleted successfully.</div>';
            }else if(array_key_exists('message', $_GET) &&  $_GET['message'] === 'I'){
                echo '<div class="alert" id="Msg" style="max-width: 1000px;background-color: #dff0d8;border-color: #d0e9c6 !important;color: #3c763d;">Import will start shortly.</div>';
            }
            ?>
        <div style="color: #800000;font-size: 16px;font-weight: bold;"><?=$module->getModuleName()?></div>
        <br/>
        <p>This tool helps import one or more big CSV files without the need to split them.</p>
        <p style="color: red;font-style: italic">Note: this page does not refresh itself. To see the status of the import you will need to refresh the browser window.</p>
        <br/>
        <div>
            <form method="post" onsubmit="return saveFilesIfTheyExist('<?=$module->getUrl('saveData.php')?>');" id="importForm">
                <div style="padding-bottom: 12px">
                    <label style="padding-right: 30px;">Set CSV delimiter character:</label>
                    <select name="csvDelimiter" id="csvDelimiter" class="select-csv">
                        <option value="," selected="selected">, (comma) - default</option>
                        <option value="tab">tab</option>
                        <option value=";">; (semi-colon)</option>
                        <option value="|">| (pipe)</option>
                        <option value="^">^ (caret)</option>
                    </select>
                </div>
                <div style="padding-bottom: 12px">
                    <label style="padding-right: 30px;"><span class="fas fa-info-circle" data-toggle="tooltip" data-placement="right" style="cursor: pointer;color:#ccc" title="When selected, the module will check if the file contains existing records in the project and provide a list of existing record IDs. You can only check one file at a time. if not selected and records in the file are already on the project the data on the project will be overwritten."></span> Check for existing records</label>
                    <input type="checkbox" id="checkExisting" name="checkExisting" style="width: 20px;height: 20px;vertical-align: -3px;" onclick="anyFilesWithChecks(document.getElementById('importFile'))" checked>
                </div>
                <div style="padding-bottom: 12px">
                    <label style="padding-right: 30px;"><span class="fas fa-info-circle" data-toggle="tooltip" data-placement="right" style="cursor: pointer;color:#ccc" title="Continue importing even if errors are found when importing a file. Instead of halting the import, an error report link will appear if any errors are found and errors will also be displayed in the logs."></span> Skip importing errors</label>
                    <input type="checkbox" id="checkErrors" name="checkErrors" style="width: 20px;height: 20px;vertical-align: -3px;">
                </div>
                <div style="padding-bottom: 12px">
                    <label style="padding-right: 30px;"><span class="fas fa-info-circle" data-toggle="tooltip" data-placement="right" style="cursor: pointer;color:#ccc" title="The tool will skip importing any records in your file with record IDs that already exist in the REDCap project. Only records with new IDs will be added."></span> Import new records only</label>
                    <input type="checkbox" id="checkNewRecords" name="checkNewRecords" style="width: 20px;height: 20px;vertical-align: -3px;">
                </div>
                <div style="padding-bottom: 12px">
                    <label style="padding-right: 30px;"><span class="fas fa-info-circle" data-toggle="tooltip" data-placement="right" style="cursor: pointer;color:#ccc" title="This setting allows blank values in the import file to overwrite existing values in the REDCap project."></span> Allow blank values to overwrite existing values</label>
                    <input type="checkbox" id="checkOverwrite" name="checkOverwrite" style="width: 20px;height: 20px;vertical-align: -3px;">
                </div>
                <div style="padding-bottom: 12px">
                    <label style="padding-right: 30px;">Set format for date and datetime values:</label>
                    <select name="dateFormat" id="dateFormat" class="select-csv">
                        <option value="MDY" selected="selected">MM/DD/YYYY or YYYY-MM-DD</option>
                        <option value="DMY" selected="selected">DD/MM/YYYY or YYYY-MM-DD</option>
                    </select>
                </div>
                <div style="padding-bottom: 12px">
                    <label style="padding-right: 30px;">Select a CSV file to import:</label>
                    <input type="file" id="importFile" onchange="return fileValidation(this)" <?=$disabled?>>
                    <input type="submit" id="import" class="btn" style="color: #fff;background-color: #007bff;border-color: #007bff;cursor:not-allowed" disabled>
                    <p style="color: red;font-style: italic">Note: Once you hit start you can't upload more files until the process has finished.</p>
                </div>
            </form>

        </div>
        <?php
        $count = 0;
        foreach ($edoc_list as $index => $edoc) {
            if (((((!$import_cancel[$index] && !$import_checked[$index]) || ($import_checked[$index] && $import_continue[$index])) && $import[$index]) || !($import_check_started[$index] && !$import_cancel[$index])) && $count == 0) {
                if($disabled == "") {
                    echo '<div><a onclick="startImport()" id="start" class="btn" style="font-size: 13px;color: #fff;background-color: #00b300;border-color: #00b300;cursor:not-allowed" disabled>Start Process</a></div>';
                    $count++;
                }
            }
        }
        ?>
        <div>
            <div class="pendingFile"><span class="fa fa-clock fa-search"></span> Checked files:</div>
            <?php
            $docs = "";
            $count_file = 0;
            foreach ($edoc_list as $index => $edoc){
                $q = $module->query("SELECT stored_name,doc_name,doc_size,file_extension 
                                    FROM redcap_edocs_metadata WHERE doc_id=?",[$edoc]);
                while ($row = db_fetch_assoc($q)) {
                    if($import_checked[$index] && !$import_continue[$index] && !$import_cancel_checked[$index]) {
                        $count_file++;
                        $delete = "";
                        $continue_btn = "";
                        if(!$import_check_started[$index]){
                            $delete = " <a onclick='deleteAndCancel(" . $edoc . ")'><span style='color: red;background-color: white;border-radius: 100%;cursor:pointer;' class='fa fa-times-circle' id='check_delete'></span></a>";
                            $delete .= " <span class='fa fa-fw fa-spinner fa-spin' style='display: none' id='check_spinner' spin='".$index."'></span>";
                        }else if($import_check_started[$index] && !$import_cancel[$index]){
                            $delete = " <a onclick='deleteAndCancel(" . $edoc . ")'><span style='color: red;background-color: white;border-radius: 100%;cursor:pointer;' class='fa fa-times-circle' id='check_delete'></span></a>";
                            $continue_btn = "<a onclick='continueImport(" . $edoc . ")' class='btn btn-success' style='float: right;font-size: 13px;color: #fff;' id='continue-import'>Continue Import</a></span>";
                            $delete .= " <span class='fa fa-fw fa-spinner fa-spin' style='display: none' id='check_spinner' spin='".$index."'></span>";
                        }else{
                            $delete = " <span class='fa fa-fw fa-spinner fa-spin' spin='".$index."'></span>";
                        }

                        $docs .= "<div style='padding:5px'>".$count_file.". <span class='fa fa-file'></span> " . htmlentities($row['doc_name'],ENT_QUOTES) .$delete."<span>".$continue_btn."</div>";
                    }
                }
            }

            if($docs != ""){
                echo "<div class='alert alert-warning' id='checked-files-div' style='border:1px solid #ffeeba !important;padding-bottom: 25px;'>".$docs."</div>";
            }else{
                echo "<div class='alert alert-warning' id='checked-files-div' style='border:1px solid #ffeeba !important;'><div><i>None</i></div>";
            }
            echo "";
            ?>
        </div>
        <div>
            <div class="pendingFile"><span class="fa fa-clock fa-fw"></span> Pending files:</div>

            <div class='alert alert-primary' style='border:1px solid #b8daff !important;' id="pending-files-div">
            <?php
            $importingFiles = false;
            $docs = "";
            $count_file = 0;
            foreach ($edoc_list as $index => $edoc){
                $q = $module->query("SELECT stored_name,doc_name,doc_size,file_extension 
                                    FROM redcap_edocs_metadata WHERE doc_id=?",[$edoc]);
                while ($row = db_fetch_assoc($q)) {
                    if((!$import_cancel[$index] && !$import_checked[$index]) || ($import_checked[$index] && $import_continue[$index])) {
                        $count_file++;
                        $delete = " <a onclick='deleteAndCancel(" . $edoc . ")'><span style='color: red;background-color: white;border-radius: 100%;cursor:pointer;' class='fa fa-times-circle' id='pending_delete'></span></a>";
                        if(!$import[$index]){
                            $delete = " <span class='fa fa-fw fa-spinner fa-spin' spin='".$index."'></span>";
                            $importingFiles = true;
                        }else{
                            $delete .= " <span class='fa fa-fw fa-spinner fa-spin' style='display: none' id='pending_spinner' spin='".$index."'></span>";
                            $importingFiles = true;
                        }
                        $docs .= "<div style='padding:5px'>".$count_file.". <span class='fa fa-file'></span> " . htmlentities($row['doc_name'],ENT_QUOTES)  .$delete."</div>";
                    }
                }
            }

            if($docs != ""){
                echo $docs;
            }else{
                echo "<div><i>None</i>";
            }
            echo "";
            ?>
            </div>
        </div>
        <div style="padding-top: 20px">
           <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <a data-toggle="collapse" href="#collapse1"><span class="fa fa-info-circle"></span> Import information <span class="fa fa-fw fa-angle-down"></span></a>
                        <a href="<?=$module->getUrl('importInformationAll.php')?>" style="color: #337ab7;float: right;">View More</a>
                    </h3>
                </div>

                <div id="collapse1" class="table-responsive panel-collapse collapse show" data-toggle="collapse" aria-expanded="true" aria-controls="collapse1">
                    <table class="table panel-table" data-sortable>
                    <thead>
                    <tr>
                        <th style="min-width: 160px;">Date/Time</th>
                        <th>File</th>
                        <th style="text-align: center">Uploaded By</th>
                        <th style="text-align: center">Records</th>
                        <th style="text-align: center">Status</th>
                        <th style="text-align: center">Options</th>
                        <th style="text-align: center">Import #</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php
                        $results = $module->queryLogs("
                            select log_id, timestamp, file, status, import, totalrecordsIds, edoc,
                            checked, skip, overwrite, newrecords 
                            where project_id = ? AND message='Data'
                            order by log_id desc
                            limit 5
                        ",[$project_id]);

                        if($results->num_rows === 0){
                            ?>
                            <tr>
                                <td colspan="7">No import logs available</td>
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
                                if($row['status'] == '0'){
                                    $status = "<span class='fa fa-check fa-fw' title='success'></span>";
                                }else if($row['status'] == '1'){
                                    $status = "<span class='fa fa-times fa-fw' title='error'></span>";
                                }else if($row['status'] == '2'){
                                    $status = "<span class='fa fa-ban fa-fw' title='cancelled'></span>";
                                }

                                $options = "";
                                if($row['checked'] == "1"){
                                    $options .= "<li style='padding-right: 30px !important;'>Check for existing records</li>";
                                }
                                if($row['skip'] == "1"){
                                    $options .= "<li style='padding-right: 30px !important;'>Skip importing errors</li>";
                                }
                                if($row['overwrite'] != "normal" && $row['overwrite'] != ""){
                                    $options .= "<li style='padding-right: 30px !important;'>Allow blank values to overwrite existing values</li>";
                                }
                                if($row['newrecords'] == "1"){
                                    $options .= "<li style='padding-right: 30px !important;'>Import new records only</li>";
                                }
                                if($row['checked'] == "1" || $row['skip'] == "1" || ($row['overwrite'] != "normal" && $row['overwrite'] != "") || $row['newrecords'] == "1"){
                                    $allOptions = "<ul>".$options."</ul>";
                                    $checked = '<a tabindex="0" role="button" data-container="body" data-toggle="popover" data-title="Options" data-content="'.$allOptions.'"><i class="fa fa-plus-circle fa-fw"></i>';
                                }else{
                                    $checked = "<em>None</em>";
                                }

                                if($row['totalrecordsIds'] != ""){
                                    $records = count(explode(",",$row['totalrecordsIds']));
                                    $total = '<a href="#" rel="popover" data-toggle="popover" data-target-selector="#records-activated'.$index.'" data-title="Records for Import #'.htmlentities($row['import'],ENT_QUOTES).'" style="color: #337ab7;">Total: '.$records.'</a></div><br/>';
                                    $total .= '<div id="records-activated'.$index.'" class="hidden">
                                                            <p>'.htmlentities($row['totalrecordsIds'],ENT_QUOTES).'</p>
                                                       </div>';
                                    $index++;
                                }else{
                                    $total = "<em>No records found</em>";
                                }


                                $resultsUser = $module->queryLogs("
                                    select log_id, edoc, user 
                                    where project_id = ? AND message='DataUser' AND edoc=?
                                    order by log_id desc
                                ",[$project_id,$row['edoc']]);
                                $user = "";
                                if($rowUser = $resultsUser->fetch_assoc()){
                                    $user = htmlentities($rowUser['user'],ENT_QUOTES);
                                }

                                ?>
                                <tr>
                                    <td><?= htmlentities($row['timestamp'],ENT_QUOTES) ?></td>
                                    <td><?= htmlentities($row['file'],ENT_QUOTES) ?></td>
                                    <td><?= $user ?></td>
                                    <td style="text-align: center"><?= $total ?></td>
                                    <td style="text-align: center"><?= $status ?></td>
                                    <td style="text-align: center"><?= $checked ?></td>
                                    <td style="text-align: center"><?= htmlentities($row['import'],ENT_QUOTES)?></td>
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

</div>
    <br>
    <br>
    <br>
    <?php if($importingFiles){ ?>
        <div style="padding-bottom:20px" id="importStarted">
            <div class='alert alert-secondary'><em class="fas fa-cog fa-spin"></em> Import in process. Uploads are disabled until the queue is complete.</div>
        </div>
    <?php } ?>
    <h5>Recent Log Entries <a onclick="cancelImport()" class="btn btn-cancel" style="float: right;"><span id="spinner"></span> Cancel Current Import</a></span> <span><a onclick="javascript:$('#deleteLogs').modal('show');" class="btn btn-error" style="float: right;margin-right: 10px;">Delete History</a></span></h5>
    <p>(refresh the page to see the latest)</p>
    <table class="table table-striped big-data-import-table">
        <thead>
        <tr>
            <th style="min-width: 160px;">Date/Time</th>
            <th>Message</th>
            <th>Records</th>
            <th style="min-width: 125px;">Details</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $results = $module->queryLogs("
				select log_id, timestamp, message, import, batch, delimiter, recordlist, details, chkerrors 
				where project_id = ?
				order by log_id desc
				limit 2000
			",[$project_id]);

        if($results->num_rows === 0){
            ?>
            <tr>
                <td colspan="4">No logs available</td>
                <td style="display: none;"></td>
                <td style="display: none;"></td>
                <td style="display: none;"></td>
            </tr>
            <?php
        }
        else{
            while($row = $results->fetch_assoc()){
                $logId = htmlentities($row['log_id'],ENT_QUOTES);
                $details = htmlentities($row['details'],ENT_QUOTES);
                $import = htmlentities($row['import'],ENT_QUOTES);
                $batch = htmlentities($row['batch'],ENT_QUOTES);
                $delimiter = htmlentities($row['delimiter'],ENT_QUOTES);
                $chkerrors = htmlentities($row['chkerrors'],ENT_QUOTES);
                if($row['message'] != "Data" && $row['message'] != "DataUser") {
                    $message = htmlspecialchars($row['message'],ENT_QUOTES);
                    if (!empty($chkerrors) && $row['message'] == "Errors") {
                        $message = '<a onclick="ExternalModules.Vanderbilt.BigDataImportExternalModule.showDetails('. $logId.','. $import .')" style="text-decoration: underline;color:#337ab7;cursor: pointer">
                            See error report
                        </a>
                        <script>
                            ExternalModules.Vanderbilt.BigDataImportExternalModule.details['.$logId.'] = '.json_encode($chkerrors).'
                        </script>';
                    }

                    #We parse the $message content that has HTML tag to display them as HTML
                    $htmlAllowed = [
                        "<br>",
                        "<br/>",
                        "<b>",
                        "</b>",
                        "<strong>",
                        "</strong>",
                        "<div>",
                        "</div>",
                        "<div class='remote-project-title'><ul><li>",
                        "</li></ul>",
                        "<span class='fa fa-check fa-fw'></span>",
                        "<span class='fa fa-times  fa-fw'></span>",
                        "<span class='fa fa-ban  fa-fw'></span>",
                        "<span class='fa fa-exclamation-circle fa-fw'></span>",
                        "<span class='fa fa-exclamation-circle warning fa-fw'></span>",
                        "<a href='" . $module->getUrl('import.php') . "'>this page</a>"
                    ];

                    foreach($htmlAllowed as $html){
                        $message = str_replace(
                            htmlspecialchars($html, ENT_QUOTES),
                            $html,
                            $message
                            );
                    }
                    ?>
                    <tr>
                        <td><?= htmlentities($row['timestamp'],ENT_QUOTES) ?></td>
                        <td class="message"><?= $message ?></td>
                        <td style="width:100px"><?= htmlentities($row['recordlist'],ENT_QUOTES) ?></td>
                        <td>
                            <?php if (!empty($details)) { ?>
                                <button onclick="ExternalModules.Vanderbilt.BigDataImportExternalModule.showDetails(<?= $logId ?>,<?= $import ?>)">
                                    Show Details
                                </button>
                                <script>
                                    ExternalModules.Vanderbilt.BigDataImportExternalModule.details[<?=$logId?>] = <?=json_encode($details)?>
                                </script>
                            <?php }else  if (!empty($import)) { ?>
                                <div>Import #<?= $import ?></div>
                                <?php if (!empty($batch)) {?>
                                <div><?= $batch ?></div>
                                <?php } ?>
                                <?php if (!empty($delimiter)) { ?>
                                    <div>Delimiter: <?= $delimiter ?></div>
                                <?php } ?>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php
                }
            }
        }
        ?>
        </tbody>
    </table>
</div>
