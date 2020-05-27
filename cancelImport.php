<?php
$project_id = $_POST['pid'];

/** @var \Vanderbilt\BigDataImportExternalModule\BigDataImportExternalModule $module */
$import_cancel = $module->getProjectSetting('import-cancel', $project_id);
$import_cancel_check = $module->getProjectSetting('import-cancel-check', $project_id);
$import_check_started = $module->getProjectSetting('import-checked-started', $project_id);
$import_checked = $module->getProjectSetting('import-checked', $project_id);
$edoc_list = $module->getProjectSetting('edoc');

$edoc = array_pop(array_reverse($edoc_list));
if (($key = array_search($edoc, $edoc_list)) !== false) {
    $import = $module->getProjectSetting('import', $project_id);
    $import_number = $module->getProjectSetting('import-number', $project_id)[$key];
    if($import[$key] == false){
        $import_cancel[$key] = true;
        $module->setProjectSetting('import-cancel', $import_cancel,$project_id);
        \REDCap::logEvent("<i>Big Data Import</i> process <b>cancelled</b>\n <b>Import #".$import_number."</b>","user = ".USERID."\nFile = '".$module->getDocName($edoc)."'\nImport = ".$import_number,null,null,null,$project_id);
    }
    if($import_checked[$key] == true && $import_check_started[$key == true]){
        $import_cancel_check[$key] = true;
        $module->setProjectSetting('import-cancel-check', $import_cancel_check,$project_id);
        \REDCap::logEvent("<i>Big Data Import</i> checking process <b>cancelled</b>\n","user = ".USERID."\nFile = '".$module->getDocName($edoc)."'\nImport = ".$import_number,null,null,null,$project_id);
    }
    echo json_encode(array(
            'status' =>'success'
        )
    );
}else{
    echo json_encode(array(
        'status' => "cancel"
    ));
}
?>