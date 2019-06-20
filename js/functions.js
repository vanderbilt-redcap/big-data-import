function fileValidation(fileInput) {
    var filePath = fileInput.value;
    var allowedExtensions = /(\.csv)$/i;
    if (!allowedExtensions.exec(filePath)) {
        simpleDialog('Please upload a CSV file.', '<span class="error-title">Wrong File</span>', null, 500);
        fileInput.value = '';
        $('#import').css('cursor', 'not-allowed');
        $('#import').prop('disabled', true);
        return false;
    }else if($('#checkExisting').is(":checked")){
        anyFilesWithChecks(fileInput)
    } else{
        $('#import').prop('disabled',false);
        $('#import').css('cursor','pointer');
    }
}

function anyFilesWithChecks(fileInput){
    console.log("url:"+anyFilesWithChecks_url)
    $.ajax({
        url: anyFilesWithChecks_url,
        data: "&pid="+pid,
        type: 'POST',
        success: function(returnData) {
            var data = JSON.parse(returnData);
            if(data.checked){
                if(fileInput.value != ""){
                    simpleDialog('There is currently a file being uploaded and file checking can not occur during an active upload.', '<span class="error-title">Multiple existing records checked files</span>', null, 500);
                    fileInput.value = '';
                    $('#import').css('cursor', 'not-allowed');
                    $('#import').prop('disabled', true);
                }
            }else{
                $('#import').prop('disabled',false);
                $('#import').css('cursor','pointer');
            }

        }
    });
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
        formData.append('csvDelimiter', $('#csvDelimiter option:selected').val());
        formData.append('checkExisting', $('#checkExisting').is(':checked'));
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
            }else if(data.status == 'cancel') {
                simpleDialog("Currently there are no imports in progress.", 'Error', null, 500);
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

function deleteLogs(){
    $.ajax({
        url: deleteLogs_url,
        data: "&pid="+pid,
        type: 'POST',
        success: function(returnData) {
            var data = JSON.parse(returnData);
            if (data.status == 'success') {
                var url = window.location.href;
                if(url.match(/(&message=)([A-Z]{1})/)){
                    url = url.replace( /(&message=)([A-Z]{1})/, "&message=L" );
                }else{
                    url = url + "&message=L";
                }
                window.location = url;
            }else if(data.status == 'delete') {
                $('#deleteLogs').modal('hide');
                simpleDialog("There are currently files being imported.\n\nPlease Delete Logs after all processes have been finalized.", 'Error', null, 500);
            }else{
                simpleDialog(data.status+" One or more of the files could not be deleted."+JSON.stringify(data), 'Error', null, 500);
            }
        },
        error: function(e) {
            simpleDialog("One or more of the files could not be saved."+JSON.stringify(e), 'Error', null, 500);
        }
    });
}

function continueImport(edoc){
    $.ajax({
        url: continueImport_url,
        data: "&pid="+pid+"&edoc="+edoc,
        type: 'POST',
        success: function(returnData) {
            var data = JSON.parse(returnData);
            if (data.status == 'success') {
                var url = window.location.href;
                if(url.match(/(&message=)([A-Z]{1})/)){
                    url = url.replace( /(&message=)([A-Z]{1})/, "&message=I" );
                }else{
                    url = url + "&message=I";
                }
                window.location = url;
            }else{
                simpleDialog(data.status+" Could not add continue status."+JSON.stringify(data), 'Error', null, 500);
            }
        },
        error: function(e) {
            simpleDialog("One or more of the files could not be saved."+JSON.stringify(e), 'Error', null, 500);
        }
    });
}