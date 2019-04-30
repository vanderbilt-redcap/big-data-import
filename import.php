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
</style>
<script>
    function fileValidation(fileInput){
        var filePath = fileInput.value;
        var allowedExtensions = /(\.csv)$/i;
        if(!allowedExtensions.exec(filePath)){
            simpleDialog('Please upload a CVS file.', '<span class="error-title">Wrong File</span>', null, 500);
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
                        $('#Msg').show();
                    }
                },
                error: function(e) {
                    simpleDialog("One or more of the files could not be saved."+JSON.stringify(e), 'Error', null, 500);
                }
            });
        }
        return false;
    }
</script>
<div id="big-data-info-wrapper">
    <?=$module->initializeJavascriptModuleObject()?>
    <script>
        /***SHOW DETAILS***/
        ExternalModules.Vanderbilt.BigDataImportExternalModule.details = {}

        ExternalModules.Vanderbilt.BigDataImportExternalModule.showDetails = function(logId){
            var width = window.innerWidth - 100;
            var height = window.innerHeight - 200;
            var content = '<pre style="max-height: ' + height + 'px">' + this.details[logId] + '</pre>'

            simpleDialog(content, 'Details', null, width)
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
    <div style="color: #800000;font-size: 16px;font-weight: bold;"><?=$module->getModuleName()?></div>
    <br>
    <?php
    //$module->cronBigDataImport();

    ?>
    <div>
        <div class="alert" id="Msg" style="display:none;max-width: 1000px;background-color: #dff0d8;border-color: #d0e9c6 !important;color: #3c763d;">Your file has been uploaded.<br/>If you have set an email, a message will be sent once the import is ready, if not, refresh this page.</div>
        <form method="post" onsubmit="return saveFilesIfTheyExist('<?=$module->getUrl('saveData.php')?>');" id="importForm">
            <label style="padding-right: 30px;">Select a CSV file to import:</label>
            <input type="file" id="importFile" onchange="return fileValidation(this)">
            <input type="submit" id="import" class="btn" style="color: #fff;background-color: #007bff;border-color: #007bff;cursor:not-allowed" disabled>
        </form>
    </div>
    <br>
    <br>
    <br>
    <h5>Recent Log Entries</h5>
    <p>(refresh the page to see the latest)</p>
    <table class="table table-striped" style="max-width: 1000px;">
        <thead>
        <tr>
            <th style="min-width: 160px;">Date/Time</th>
            <th>Message</th>
            <th style="min-width: 125px;">Details</th>
        </tr>
        </thead>
        <tbody>
        <?php

        $results = $module->queryLogs("
				select log_id, timestamp, message, details
				order by log_id desc
				limit 2000
			");

        if($results->num_rows === 0){
            ?>
            <tr>
                <td colspan="3">No logs available</td>
            </tr>
            <?php
        }
        else{
            while($row = $results->fetch_assoc()){
                $logId = $row['log_id'];
                $details = $row['details'];
                ?>
                <tr>
                    <td><?=$row['timestamp']?></td>
                    <td class="message"><?=$row['message']?></td>
                    <td>
                        <?php if(!empty($details)) { ?>
                            <button onclick="ExternalModules.Vanderbilt.BigDataImportExternalModule.showDetails(<?=$logId?>)">Show Details</button>
                            <script>
                                ExternalModules.Vanderbilt.BigDataImportExternalModule.details[<?=$logId?>] = <?=json_encode($details)?>
                            </script>
                        <?php } ?>
                    </td>
                </tr>
                <?php
            }
        }
        ?>
        </tbody>
    </table>
</div>