<?php
$import_checked = $module->getProjectSetting('import-checked');
$checked = false;
foreach ($import_checked as $index => $check){
    if($check){
        $checked = true;
    }
}

echo json_encode(array(
        'status' =>'success',
        'checked' =>$checked
    )
);
?>