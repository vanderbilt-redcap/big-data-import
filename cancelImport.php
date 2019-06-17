<?php
$project_id = $_POST['pid'];

$import_cancel = $module->getProjectSetting('import-cancel', $project_id);

$edoc_list = $module->getProjectSetting('edoc');

$edoc = array_pop(array_reverse($edoc_list));
if (($key = array_search($edoc, $edoc_list)) !== false) {
    $import = $module->getProjectSetting('import', $project_id);
    if($import[$key] == false){
        $import_cancel[$key] = true;

        $module->setProjectSetting('import-cancel', $import_cancel,$project_id);

        \REDCap::logEvent("<i>Big Data Import</i> process <b>cancelled</b>","user = ".USERID."\nFile = '".$module->getDocName($edoc)."'",null,null,null,$project_id);
    }
}

echo json_encode(array(
        'status' =>'success'
    )
);
?>