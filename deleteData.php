<?php
$edoc = $_POST['edoc'];
$project_id = $_POST['pid'];
$status = "success";
if ($edoc) {
    $import = $module->getProjectSetting('import');
    $edoc_list = $module->getProjectSetting('edoc');

    $index = "";
    if (($key = array_search($edoc, $edoc_list)) !== false) {
        $index = $key;
    }
    if($import[$index]){
        $total_import = $module->getProjectSetting('total-import') - 1;
        $module->setProjectSetting('total-import', $total_import);

        $import_number =$module->getProjectSetting('import-number');

        for($i=$index; $i<=(count($edoc_list)-1); $i++){
            $import_number[$i] = $import_number[$i] - 1;
        }
        $module->setProjectSetting('import-number', $import_number);

        \REDCap::logEvent("File <b>deleted</b> via <i>Big Data Import</i> external module","user = ".USERID."\nFile = '".$module->getDocName($edoc)."'",null,null,null,$project_id);

        $module->resetValues($project_id,$edoc);
    }else{
        $status = "import";
    }
} else {
    echo json_encode(array(
        'status' => "You could not delete a file properly.",
    ));
}

echo json_encode(array(
        'status' =>$status
    )
);

?>