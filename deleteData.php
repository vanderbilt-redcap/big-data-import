<?php
$edoc = $_POST['edoc'];
$project_id = $_POST['pid'];

if ($edoc) {
    $import =$module->getProjectSetting('import');
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

        \REDCap::logEvent("File <b>deleted</b> via <i>Big Data Import</i> external module","user = ".USERID."\nFile = '".$doc_name."'",null,null,null,$project_id);

        $module->resetValues($project_id,$edoc);
    }else{
        header('Content-type: application/json');
        echo json_encode(array(
            'status' => "import"
        ));
    }
} else {
    header('Content-type: application/json');
    echo json_encode(array(
        'status' => "You could not delete a file properly."
    ));
}

echo json_encode(array(
        'status' =>'success'
    )
);

?>