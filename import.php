<style>
    #big-data-module-wrapper th{
        font-weight: bold;
    }

    #big-data-module-wrapper .remote-project-title{
        margin-top: 5px;
        margin-left: 15px;
        font-weight: bold;
    }

    #big-data-module-wrapper td.message{
        max-width: 800px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .error-title{
        color: #a94442;
    }
    .pendingFile{padding:10px 0px;}
    .pendingFile,pendingFile a,.pendingFile a:active,.pendingFile a:hover,.pendingFile a:visited{
        padding-right: 30px;
        color:#007bff !important;
    }
    .fa-check{
        color: green;
    }
    .fa-times,.fa-exclamation-circle, .fa-ban{
        color: red;
    }
    .dataTables_wrapperm,#big-data-module-wrapper{
        max-width: 800px;
    }
    .odd, tr .odd{
        background-color: rgba(0,0,0,.05) !important;
    }
    .table td, .table th {
        padding: .75rem !important;
        vertical-align: top;
        border-top: 1px solid #dee2e6;
    }
    .big-data-import-table{
        width: 800px !important;
    }
    .big-data-import-table .odd{
        background-color: rgba(0,0,0,.05) !important;
    }
    .big-data-import-table .even{
        background-color: #ffffff !important;
    }
    .big-data-import-table thead th, .big-data-import-table table.dataTable thead th, table.dataTable thead td {
        border:none !important;
    }
    .btn-cancel{
        color: #333;
        background-color: #fff;
        border-color: #ccc;
        font-size: 13px;
    }
    .btn-cancel:hover{
        color: #333;
        background-color: #e6e6e6;
        border-color: #adadad;
    }
    .select-csv {
        height: 30px;
        padding: 6px 12px;
        font-size: 14px;
        line-height: 1.42857143;
        color: #555;
        background-color: #fff;
        background-image: none;
        border: 1px solid #ccc;
        border-radius: 4px;
        -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
        box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
        -webkit-transition: border-color ease-in-out .15s,-webkit-box-shadow ease-in-out .15s;
        -o-transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
        transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
    }
</style>
<script>
    var deleteData_url = <?=json_encode($module->getUrl('deleteData.php'))?>;
    var cancelImport_url = <?=json_encode($module->getUrl('cancelImport.php'))?>;
    var pid = <?=json_encode($_GET['pid'])?>;
    $(document).ready(function() {
        $('.big-data-import-table').DataTable({
            ordering: false,
            bFilter: false,
            bLengthChange: true,
            pageLength: 50
        } );
    } );
    function fileValidation(fileInput){
        var filePath = fileInput.value;
        var allowedExtensions = /(\.csv)$/i;
        if(!allowedExtensions.exec(filePath)){
            simpleDialog('Please upload a CSV file.', '<span class="error-title">Wrong File</span>', null, 500);
            fileInput.value = '';
            $('#import').css('cursor','not-allowed');
            $('#import').prop('disabled',true);
            return false;
        }else{
            $('#import').prop('disabled',false);
            $('#import').css('cursor','pointer');
        }
    }

    function saveFilesIfTheyExist(url) {
        var files = {};
        $('#importForm').find('input').each(function(index, element){
            var element = $(element);
            var name = element.attr('name');
            var type = element[0].type;

            if (type == 'file') {
                // only store one file per variable - the first file
                jQuery.each(element[0].files, function(i, file) {
                    if (typeof files[name] == "undefined") {
                        files[name] = file;
                    }
                });
            }
        });

        var lengthOfFiles = 0;
        var formData = new FormData();
        for (var name in files) {
            lengthOfFiles++;
            formData.append(name, files[name]);   // filename agnostic
            formData.append('csvDelimiter', $('#csvDelimiter option:selected').val());   // filename agnostic
        }
        if (lengthOfFiles > 0) {
            $.ajax({
                url: url,
                data: formData,
                processData: false,
                contentType: false,
                async: false,
                type: 'POST',
                success: function(returnData) {
                    var data = JSON.parse(returnData);
                    if (data.status != 'success') {
                        simpleDialog(data.status+" One or more of the files could not be saved."+JSON.stringify(data), 'Error', null, 500);
                    }else{
                        var url = window.location.href;
                        if(url.match(/(&message=)([A-Z]{1})/)){
                            url = url.replace( /(&message=)([A-Z]{1})/, "&message=S" );
                        }else{
                            url = url + "&message=S";
                        }
                        window.location = url;
                    }
                },
                error: function(e) {
                    simpleDialog("One or more of the files could not be saved."+JSON.stringify(e), 'Error', null, 500);
                }
            });
        }
        return false;
    }

    function deleteAndCancel(edoc){
        $.ajax({
            url: deleteData_url,
            data: "&edoc="+edoc+"&pid="+pid,
            type: 'POST',
            success: function(returnData) {
                var data = JSON.parse(returnData);
                console.log(data)
                if (data.status == 'success') {
                    var url = window.location.href;
                    if(url.match(/(&message=)([A-Z]{1})/)){
                        url = url.replace( /(&message=)([A-Z]{1})/, "&message=D" );
                    }else{
                        url = url + "&message=D";
                    }
                    window.location = url;
                }else if(data.status == 'import'){
                    simpleDialog("This file is already importing and can't be deleted. Please use cancel button to cancel the import process.", 'Error', null, 500);
                }else{
                    simpleDialog("status1: "+data.status+" One or more of the files could not be deleted."+JSON.stringify(data), 'Error', null, 500);
                }
            },
            error: function(e) {
                simpleDialog("One or more of the files could not be saved."+JSON.stringify(e), 'Error', null, 500);
            }
        });
    }

    function cancelImport(){
        $("#spinner").addClass('fa fa-fw fa-spinner fa-spin');
        $.ajax({
            url: cancelImport_url,
            data: "&pid="+pid,
            type: 'POST',
            success: function(returnData) {
                $("#spinner").removeClass('fa fa-fw fa-spinner fa-spin');
                var data = JSON.parse(returnData);
                if (data.status == 'success') {
                    var url = window.location.href;
                    if(url.match(/(&message=)([A-Z]{1})/)){
                        url = url.replace( /(&message=)([A-Z]{1})/, "&message=C" );
                    }else{
                        url = url + "&message=C";
                    }
                    window.location = url;
                }else{
                    simpleDialog(data.status+" One or more of the files could not be deleted."+JSON.stringify(data), 'Error', null, 500);
                }
            },
            error: function(e) {
                $("#spinner").removeClass('fa fa-fw fa-spinner fa-spin');
                simpleDialog("One or more of the files could not be saved."+JSON.stringify(e), 'Error', null, 500);
            }
        });
    }
</script>
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
//$module->setProjectSetting('import', array(0=>1));
//$module->setProjectSetting('import-number', array());

//print_array($module->getProjectSetting('total-import'));
//print_array($module->getProjectSetting('import-number'));
//print_array($module->getProjectSetting('edoc'));
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
        }
        ?>
    <div style="color: #800000;font-size: 16px;font-weight: bold;"><?=$module->getModuleName()?></div>
    <br/>
    <p>Tool to help import one or more big CSV files without the need to split them.</p>
    <p>Refresh the page to see the changes and logs.</p>
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
                        $icon = "";
                        if(!$import[$index]){
                            $icon = " <span class='fa fa-fw fa-spinner fa-spin'></span>";
                        }
                        $docs .= "<div style='padding:5px'>".$count_file.". <span class='fa fa-file'></span> " . $row['doc_name'] . " <a onclick='deleteAndCancel(" . $edoc . ")'><span style='color: red;background-color: white;border-radius: 100%;cursor:pointer;' class='fa fa-times-circle'></span>".$icon."</a></div>";
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
    <h5 style="max-width: 800px;">Recent Log Entries <span><a onclick="cancelImport()" class="btn btn-cancel" style="float: right;"><span id="spinner"></span> Cancel Current Import</a></span></h5>
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
        $sql = "SELECT * FROM `redcap_external_modules_log` where project_id=1414 order by log_id desc limit 2000";
        $query = db_query($sql);
        if (db_num_rows($query) > 0) {
            while ($row = db_fetch_assoc($q)) {
                $logId = $row['log_id'];
                $details = $row['details'];
                $import = $row['import'];
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
                        <?php }else if(!empty($import)) {  ?>
                            Import # <?=$import?>
                        <?php }  ?>
                    </td>
                </tr>
                <?php
            }
        }else{
            ?>
            <tr>
                <td colspan="4">No logs available</td>
                <td style="display: none;"></td>
                <td style="display: none;"></td>
                <td style="display: none;"></td>
            </tr>
            <?php
        }
    /*
        $results = $module->queryLogs("
				select log_id, timestamp, message, details, import, recordlist
				order by log_id desc
				limit 1500
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
                        <?php }else if(!empty($import)) {  ?>
                            Import # <?=$import?>
                        <?php }  ?>
                    </td>
                </tr>
                <?php
            }
        }*/
        ?>
        </tbody>
    </table>
</div>
