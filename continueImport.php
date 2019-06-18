<?php
$edoc = $_POST['edoc'];
$project_id = $_POST['pid'];
$status = "success";
if ($edoc) {
    $import = $module->getProjectSetting('import');
    $import_check_started = $module->getProjectSetting('import-checked-started');
    $edoc_list = $module->getProjectSetting('edoc');

    $index = "";
    if (($key = array_search($edoc, $edoc_list)) !== false) {
        $index = $key;
    }
    if(!$import[$index] && $import_check_started[$index]){
        $test = "3";
        $import_continue = $module->getProjectSetting('import-continue');
        $import_continue[$index] = true;
        $module->setProjectSetting('import-continue', $import_continue);

        $import_list = $module->getProjectSetting('import');
        $import_list[$index] = true;
        $module->setProjectSetting('import', $import_list);

        \REDCap::logEvent("<b>Continue file Import</b> via <i>Big Data Import</i> external module\n <b>Import #".$import_number_current."</b>","user = ".USERID."\nFile = '".$module->getDocName($edoc)."'\nImport = ".$import_number_current,null,null,null,$project_id);
    }else{
        $status = "import";
    }
} else {
    $status = "continue";
}
echo json_encode(array(
    'status' => $status
));

?>