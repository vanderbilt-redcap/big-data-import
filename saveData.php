<?php
//$module->setProjectSetting('import', true);
$import_list = empty($module->getProjectSetting('import'))?array():$module->getProjectSetting('import');
array_push($import_list,true);
$module->setProjectSetting('import', $import_list);

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

            $sql = "SELECT stored_name,doc_name,doc_size,file_extension FROM redcap_edocs_metadata WHERE doc_id=" . $edoc;
            $q = db_query($sql);

            if ($error = db_error()) {
                echo $sql . ': ' . $error;
                $this->exitAfterHook();
            }

            $stored_name = "";
            $doc_name = "";
            while ($row = db_fetch_assoc($q)) {
                $doc_name = $row['doc_name'];
                $stored_name = $row['stored_name'];
            }

            \REDCap::logEvent("File submitted via <i>Big Data Import</i> external module","user = ".USERID."\nFile = '".$doc_name."'",null,null,null,$pid);
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