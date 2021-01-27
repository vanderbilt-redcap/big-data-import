<?php
/** @var \Vanderbilt\BigDataImportExternalModule $module 
 * 
 */

$settings = $module->getProjectSettings();

# Ignore settings that are not relevant
unset($settings["enabled"]);
unset($settings["version"]);
unset($settings["import-email"]);
unset($settings["import-from"]);

$num = count($settings);

if($num == 0) {
    echo json_encode(array(
        'status' => 'warning',
        'message' => 'There is no module data yet to be reset.'
    ));
    exit();
}

# Remove project setting with specified key from current project
foreach ($settings as $key => $value) {
    $module->removeProjectSetting($key);
}

# Send response and log 
$icon = '<span class="fa-stack fa-2x"><i class="fas fa-circle fa-stack-2x"></i><i class="fas fa-redo fa-stack-1x fa-inverse"></i></span>';
$logtext = "The module has been reset. {$icon}</div>";

$module->log($logtext);
$message = 'Reset was successful. '.$num.' rows have been reset.';

echo json_encode(array(
    'status' => 'success',
    'message' => $message
));

?>