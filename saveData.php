<?php
$import_checked = empty($module->getProjectSetting('import-checked'))?array():$module->getProjectSetting('import-checked');
array_push($import_checked,filter_var($_REQUEST['checkExisting'], FILTER_VALIDATE_BOOLEAN));
$module->setProjectSetting('import-checked', $import_checked);

$start_import = true;
$checked_log = "";
if(filter_var($_REQUEST['checkExisting'], FILTER_VALIDATE_BOOLEAN)){
    $start_import = false;
    $checked_log = "\nChecked for existing records";
}

$import_list = empty($module->getProjectSetting('import'))?array():$module->getProjectSetting('import');
array_push($import_list,$start_import);
$module->setProjectSetting('import', $import_list);

$import_check_started = empty($module->getProjectSetting('import-checked-started'))?array():$module->getProjectSetting('import-checked-started');
array_push($import_check_started,$start_import);
$module->setProjectSetting('import-checked-started', $import_check_started);

$import_continue = empty($module->getProjectSetting('import-continue'))?array():$module->getProjectSetting('import-continue');
array_push($import_continue,$start_import);
$module->setProjectSetting('import-continue', $import_continue);

$import_cancel = empty($module->getProjectSetting('import-cancel'))?array():$module->getProjectSetting('import-cancel');
array_push($import_cancel,false);
$module->setProjectSetting('import-cancel', $import_cancel);

$total_import = $module->getProjectSetting('total-import') + 1;
$module->setProjectSetting('total-import', $total_import);

$import_number = empty($module->getProjectSetting('import-number'))?array():$module->getProjectSetting('import-number');
array_push($import_number,$total_import);
$module->setProjectSetting('import-number', $import_number);

$import_delimiter = empty($module->getProjectSetting('import-delimiter'))?array():$module->getProjectSetting('import-delimiter');
array_push($import_delimiter,$_REQUEST['csvDelimiter']);
$module->setProjectSetting('import-delimiter', $import_delimiter);

foreach($_FILES as $key=>$value){
    $myfiles[] = $key;
    if ($value) {
        # use REDCap's uploadFile
        $edoc = \Files::uploadFile($_FILES[$key]);
        if ($edoc) {
            $edoc_list = empty($module->getProjectSetting('edoc'))?array():$module->getProjectSetting('edoc');
            array_push($edoc_list,$edoc);
            $module->setProjectSetting('edoc', $edoc_list);

            $module->log("DataUser", [
                'edoc' => $edoc,
                'user' => USERID
            ]);

            \REDCap::logEvent("File <b>submitted</b> via <i>Big Data Import</i> external module\n <b>Import #".$total_import."</b>","user = ".USERID."\nFile = '".$module->getDocName($edoc)."\nImport = ".$total_import."\nDelimiter = ".$_REQUEST['csvDelimiter'].$checked_log,null,null,null,$pid);
        } else {
            header('Content-type: application/json');
            echo json_encode(array(
                'status' => "You could not save a file properly."
            ));
        }
    }
}
echo json_encode(array(
        'status' =>'success'
    )
);

?>