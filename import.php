
<link rel="stylesheet" type="text/css" href="<?=$module->getUrl('css/style.css')?>">
<script type="text/javascript" src="<?=$module->getUrl('js/functions.js')?>"></script>
<script>
    var deleteData_url = <?=json_encode($module->getUrl('deleteData.php'))?>;
    var cancelImport_url = <?=json_encode($module->getUrl('cancelImport.php'))?>;
    var deleteLogs_url = <?=json_encode($module->getUrl('deleteLogs.php'))?>;
    var pid = <?=json_encode($_GET['pid'])?>;
    $(document).ready(function() {
        $('.big-data-import-table').DataTable({
            ordering: false,
            bFilter: false,
            bLengthChange: true,
            pageLength: 50
        });

        $('#deleteLogsForm').submit(function () {
            deleteLogs();
            return false;
        });
    } );

</script>

<div class="modal fade" id="deleteLogs" tabindex="-1" role="dialog" aria-labelledby="Codes">
    <form class="form-horizontal" action="" method="post" id='deleteLogsForm'>
        <div class="modal-dialog" role="document" style="width: 500px">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Delete Logs</h4>
                </div>
                <div class="modal-body">
                    <div>Are you sure you want to delete the logs?</div>
                    <div>This will clean up all the information.</div>
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
<?php
//print_array($module->getProjectSetting('import'));
//print_array($module->getProjectSetting('edoc'));
//print_array($module->getProjectSetting('import-cancel'));
?>
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
                <label style="padding-right: 30px;">Select a CSV file to import:</label>
                <input type="file" id="importFile" onchange="return fileValidation(this)">
                <input type="submit" id="import" class="btn" style="color: #fff;background-color: #007bff;border-color: #007bff;cursor:not-allowed" disabled>
            </div>
        </form>

    </div>
    <div>
            <div class="pendingFile"><span class="fa fa-clock fa-fw"></span> Pending files:</div>
            <div class='alert alert-primary' style='border:1px solid #b8daff !important;max-width: 800px'>
            <?php
            $edoc_list = $module->getProjectSetting('edoc');
            $import_cancel = $module->getProjectSetting('import-cancel');
            $import = $module->getProjectSetting('import');
            $docs = "";
            $count_file = 0;
            foreach ($edoc_list as $index => $edoc){
                $sql = "SELECT stored_name,doc_name,doc_size,file_extension FROM redcap_edocs_metadata WHERE doc_id=" . $edoc;
                $q = db_query($sql);

                if ($error = db_error()) {
                    die($sql . ': ' . $error);
                }
                while ($row = db_fetch_assoc($q)) {
                    if(!$import_cancel[$index]) {
                        $count_file++;
                        $delete = " <a onclick='deleteAndCancel(" . $edoc . ")'><span style='color: red;background-color: white;border-radius: 100%;cursor:pointer;' class='fa fa-times-circle'></span></a>";
                        if(!$import[$index]){
                            $delete = " <span class='fa fa-fw fa-spinner fa-spin'></span>";
                        }
                        $docs .= "<div style='padding:5px'>".$count_file.". <span class='fa fa-file'></span> " . $row['doc_name'] .$delete."</div>";
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
    </div>
    <br>
    <br>
    <br>
    <h5 style="max-width: 800px;">Recent Log Entries <a onclick="cancelImport()" class="btn btn-cancel" style="float: right;"><span id="spinner"></span> Cancel Current Import</a></span> <span><a onclick="javascript:$('#deleteLogs').modal('show');" class="btn btn-error" style="float: right;margin-right: 10px;">Delete Logs</a></span></h5>
    <p>(refresh the page to see the latest)</p>
    <table class="table table-striped big-data-import-table" style="max-width: 1000px;">
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
				select log_id, timestamp, message, import,delimiter, recordlist, details
				where project_id = '".$_GET['pid']."'
				order by log_id desc
				limit 2000
			");

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
                $logId = $row['log_id'];
                $details = $row['details'];
                $import = $row['import'];
                $delimiter = $row['delimiter'];
                ?>
                <tr>
                    <td><?=$row['timestamp']?></td>
                    <td class="message"><?=$row['message']?></td>
                    <td ><?=$row['recordlist']?></td>
                    <td>
                        <?php if(!empty($details)) { ?>
                        <button onclick="ExternalModules.Vanderbilt.BigDataImportExternalModule.showDetails(<?=$logId?>,<?=$import?>)">Show Details</button>
                        <script>
                            ExternalModules.Vanderbilt.BigDataImportExternalModule.details[<?=$logId?>] = <?=json_encode($details)?>
                        </script>
                        <?php }else  if(!empty($import)) {  ?>
                            <div>Import #<?=$import?></div>
                            <?php if(!empty($delimiter)){ ?>
                                <div>Delimiter: <?=$delimiter?></div>
                            <?php }  ?>
                        <?php }  ?>
                    </td>
                </tr>
                <?php
            }
        }
        ?>
        </tbody>
    </table>
</div>
