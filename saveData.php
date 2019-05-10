<?php
//$module->setProjectSetting('import', true);
$import_list = empty($module->getProjectSetting('import'))?array():$module->getProjectSetting('import');
array_push($import_list,true);
$module->setProjectSetting('import', $import_list);
foreach($_FILES as $key=>$value){
    $myfiles[] = $key;
    if ($value) {
        # use REDCap's uploadFile
        $edoc = \Files::uploadFile($_FILES[$key]);
        if ($edoc) {
            $edoc_list = empty($module->getProjectSetting('edoc'))?array():$module->getProjectSetting('edoc');
            array_push($edoc_list,$edoc);
            $module->setProjectSetting('edoc', $edoc_list);
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