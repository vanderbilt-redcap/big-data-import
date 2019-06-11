<?php
$import_list = empty($module->getProjectSetting('import'))?array():$module->getProjectSetting('import');
array_push($import_list,true);
$module->setProjectSetting('import', $import_list);

$import_cancel = empty($module->getProjectSetting('import-cancel'))?array():$module->getProjectSetting('import-cancel');
array_push($import_cancel,false);
$module->setProjectSetting('import-cancel', $import_cancel);

$total_import = $module->getProjectSetting('total-import') + 1;
$module->setProjectSetting('total-import', $total_import);

$import_number = empty($module->getProjectSetting('import-number'))?array():$module->getProjectSetting('import-number');
array_push($import_number,$total_import);
$module->setProjectSetting('import-number', $import_number);

foreach($_FILES as $key=>$value){
    $myfiles[] = $key;
    if ($value) {
        # use REDCap's uploadFile
        $edoc = \Files::uploadFile($_FILES[$key]);
        if ($edoc) {
            $edoc_list = empty($module->getProjectSetting('edoc'))?array():$module->getProjectSetting('edoc');
            array_push($edoc_list,$edoc);
            $module->setProjectSetting('edoc', $edoc_list);

            \REDCap::logEvent("File <b>submitted</b> via <i>Big Data Import</i> external module","user = ".USERID."\nFile = '".$module->getDocName($edoc)."'",null,null,null,$pid);
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