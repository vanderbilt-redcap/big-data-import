<?php
$import_checked = $module->getProjectSetting('import-checked');
$import_cancel_check = $module->getProjectSetting('import-cancel-check');
$checked = false;
foreach ($import_checked as $index => $check){
    if($check && !$import_cancel_check[$index]){
        $checked = true;
    }
}

echo json_encode(array(
        'status' =>'success',
        'checked' =>$checked
    )
);
?>