<?php
/** @var \Vanderbilt\BigDataImportExternalModule $module 
 * 
 */

$settings = $module->getProjectSettings();

unset($settings["enabled"]);
unset($settings["version"]);
unset($settings["import-email"]);
unset($settings["import-from"]);

foreach ($settings as $key => $value) {
    // Removes project setting with specified key from current project
    $module->removeProjectSetting($key);
}

$icon = '<span class="fa-stack fa-2x"><i class="fas fa-circle fa-stack-2x"></i><i class="fas fa-redo fa-stack-1x fa-inverse"></i></span>';


$logtext = "The module has been reset. {$icon}</div>";

$module->log($logtext);

echo json_encode(array(
    'status' => 'success',
    'message' => $logtext
));



?>