<?php
$project_id = $_POST['pid'];

$edoc_list = $module->getProjectSetting('edoc');
if(empty($edoc_list)){
    $module->removeLogs("project_id = $project_id");
    $module->setProjectSetting('edoc', array());
    $module->setProjectSetting('total-import', '');
    $module->setProjectSetting('import', array());
    $module->setProjectSetting('import-number', array());
    $module->setProjectSetting('import-cancel', array());
    $module->setProjectSetting('import-delimiter', array());
    $module->setProjectSetting('import-checked', array());

    \REDCap::logEvent("<i>Big Data Import</i> Logs <b>deleted</b>","user = ".USERID,null,null,null,$project_id);

    echo json_encode(array(
            'status' =>'success'
        )
    );
}else{
    echo json_encode(array(
            'status' =>'delete'
        )
    );
}



?>