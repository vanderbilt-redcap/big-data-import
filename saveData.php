<?php
$module->setProjectSetting('import', true);
foreach($_FILES as $key=>$value){
    $myfiles[] = $key;
    if ($value) {
        # use REDCap's uploadFile
        $edoc = \Files::uploadFile($_FILES[$key]);
        if ($edoc) {
            $module->setProjectSetting('edoc', $edoc);
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