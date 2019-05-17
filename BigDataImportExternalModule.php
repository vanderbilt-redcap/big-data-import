<?php
namespace Vanderbilt\BigDataImportExternalModule;

use Exception;
use REDCap;


class BigDataImportExternalModule extends \ExternalModules\AbstractExternalModule{

    public function __construct(){
        parent::__construct();
    }

    function cronbigdata(){
        $originalPid = $_GET['pid'];
        foreach($this->framework->getProjectsWithModuleEnabled() as $localProjectId){
            // This automatically associates all log statements with this project.
            $_GET['pid'] = $localProjectId;
            try{
                $import_list = $this->getProjectSetting('import', $localProjectId);
                foreach ($import_list as $id=>$import){
                    $edoc = $this->getProjectSetting('edoc', $localProjectId)[$id];
                    $import_number = $this->getProjectSetting('import-number', $localProjectId)[$id];
                    if ($import && $edoc != "") {
                        $error = $this->importRecords($localProjectId, $edoc,$id,$import_number);
                        if(!$error){
                            $logtext = "<div>Import process finished <span class='fa fa-check fa-fw'></span></div>";
                        }else{
                            $logtext = "<div>Import process finished with errors <span class='fa fa-exclamation-circle fa-fw'></span></div>";
                        }
                        $this->log($logtext,['import' => $import_number]);
                    }
                }
            }
            catch(Exception $e){
                $this->log("An error occurred.  Click 'Show Details' for more info.", [
                    'details' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                    'import' => $import_number
                ]);

                $this->sendErrorEmail("An exception occurred while syncing.");
            }
        }
        $_GET['pid'] = $originalPid;
    }

    function importRecords($project_id,$edoc,$id,$import_number){
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

        $this->log("
        <div>Importing records from CVS file:</div>
        <div class='remote-project-title'><ul><li>" . $doc_name . "</li></ul></div>",['import' => $import_number]);

        $import_email = $this->getProjectSetting('import-email', $project_id);

        $path = EDOC_PATH.$stored_name;
        $fieldNamesTotal = $this->csvToArrayNFieldNames($path);
        $content = file($path);
        $fieldNames = explode(",", $content[0]);
        foreach ($fieldNames as $idName=>$name) {
            $str = preg_replace('/^[\pZ\p{Cc}\x{feff}]+|[\pZ\p{Cc}\x{feff}]+$/ux', '', $name);
            $fieldNames[$idName] = $str;
        }

        // Use the number of fields times number of records as a metric to determine a reasonable chunk size.
        // The following calculation caused about 500MB of maximum memory usage when importing the TIN Database (pid 61715) on the Vanderbilt REDCap test server.
        $numberOfDataPoints = $fieldNamesTotal * (count($content)-1);
        $numberOfBatches = $numberOfDataPoints / 1000000;
        if(round($numberOfBatches) <= 0){
            $numberOfBatches = 1;
        }
        $batchSize = round((count($content)-1) / $numberOfBatches);
        $chunks = round((count($content)-1) / $batchSize);

        $Proj = new \Project($project_id);
        $event_id = $Proj->firstEventId;

        /*$event_id = $Proj->getEventIdUsingUniqueEventName($matches[1][0]);
        $var = $matches[1][1];
        $event_id = ($longitudinal) ? $Proj->getEventIdUsingUniqueEventName($element[$eventNameKey]) : $Proj->firstEventId;
        */

        $sql = "select app_title from redcap_projects where project_id = '".db_escape($project_id)."' limit 1";
        $q = db_query($sql);
        $projectTitle = "";
        while ($row = db_fetch_assoc($q)) {
            $projectTitle = $row['app_title'];
        }

       $sql = "SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id ='$project_id'";
        $q = db_query($sql);
        $repeatable = false;
        if (db_num_rows($q)) {
            while($row = db_fetch_assoc($q)) {
                $sql2 = "SELECT * FROM redcap_events_repeat WHERE event_id='".$row['event_id']."'";
                $q2 = db_query($sql2);
                $row2 = db_fetch_assoc($q2);
                if (db_num_rows($q2)){
                    $repeatable = true;
                    break;
                }
            }
        }
        $count = 0;
        for ($i = 0; $i < $batchSize; $i++) {
            $batchText = "batch " . ($i + 1) . " of " . $batchSize;
            $data = array();
            for ($line = 1; $line <= $chunks; $line++) {
                $data_aux = str_getcsv($content[($line + $count)], ",", '"');
                $aux = array();
                $instrument = "";
                $instance = "";
                $record = $data_aux[0];
                foreach ($fieldNames as $index => $field) {
                    if($field == "redcap_repeat_instrument") {
                        $instrument = $data_aux[$index];
                    }else if($field == "redcap_repeat_instance") {
                        $instance = $data_aux[$index];
                    }else if($field == "redcap_event_name"){
                        $event_id = $Proj->getEventIdUsingUniqueEventName( $data_aux[$index]);
                    }else{
                        $aux[$field] = $data_aux[$index];
                    }
                }
                if($repeatable){
                    if($instance != ""){
                        $data[$record]['repeat_instances'][$event_id][$instrument][$instance] = $aux;
                    }else{
                        $data[$record][$event_id] = $aux;
                    }
                }else{
                    $data[$record] = array();
                    $data[$record][$event_id] = $aux;
                }
            }
            $count += $chunks;
            $results = \Records::saveData($project_id, 'array', $data, 'normal', 'MDY', 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
            $results = $this->adjustSaveResults($results);

            $stopEarly = false;
            if (empty($results['errors'])) {
                $message = "completed ";

                if (empty($results['warnings'])) {
                    $message .= 'successfully';
                } else {
                    $message .= 'with warnings';
                }
            } else {
                $message = "did NOT complete successfully";
                $stopEarly = true;
            }

            $icon = "";
            if ($stopEarly) {
                $icon = "<span class='fa fa-times  fa-fw'></span>";
            }
            $this->log("Import $message for $batchText $icon", [
                'details' => json_encode($results, JSON_PRETTY_PRINT),
                'import' => $import_number
            ]);

            if ($stopEarly) {
                $this->resetValues($project_id, $edoc);
                $email_text = "Your import process on <b>".$projectTitle." [" . $project_id . "]</b> has finished.<br/>REDCap was unable to import some record data.";
                $email_text .="<br/><br/>For more information go to <a href='" . $this->getUrl('import.php') . "'>this page</a>";
                if ($import_email != "") {
                     REDCap::email($import_email, 'noreply@vumc.org', 'Import process finished', $email_text);

                }else{
                    $this->sendErrorEmail($email_text);
                }
                return true;
            }

        }

        $this->resetValues($project_id, $edoc);
        if ($import_email != "") {
            $email_text = "Your import process on <b>".$projectTitle." [" . $project_id . "]</b> has finished.";
            $email_text .= "<br/><br/>For more information go to <a href='" . $this->getUrl('import.php') . "'>this page</a>";
            REDCap::email($import_email, 'noreply@vumc.org', 'Import process finished', $email_text);
        }
        return false;
    }

    private function adjustSaveResults($results){
        $results['warnings'] = array_filter($results['warnings'], function($warning){
            global $lang;

            if(strpos($warning[3], $lang['data_import_tool_197']) !== -1){
                return false;
            }

            return true;
        });

        return $results;
    }

    function resetValues($project_id,$edoc){
        $import_list = empty($this->getProjectSetting('import'))?array():$this->getProjectSetting('import');
        $import_number = $this->getProjectSetting('import-number',$project_id);
        $edoc_list = $this->getProjectSetting('edoc',$project_id);
        if (($key = array_search($edoc, $edoc_list)) !== false) {
            unset($edoc_list[$key]);
            unset($import_list[$key]);
            unset($import_number[$key]);
        }
        $this->setProjectSetting('edoc', $edoc_list,$project_id);
        $this->setProjectSetting('import', $import_list,$project_id);
        $this->setProjectSetting('import-number', $import_number,$project_id);
        if($edoc != "" && $project_id != ""){
            $sql = "DELETE FROM redcap_edocs_metadata WHERE project_id=".$project_id." AND doc_id=" . $edoc;
            $q = db_query($sql);

            if ($error = db_error()) {
                echo $sql . ': ' . $error;
                $this->exitAfterHook();

            }
        }
    }

    private function sendErrorEmail($message){
        if(!method_exists($this->framework, 'getProject')){
            // This REDCap version is older and doesn't have the methods needed for error reporting.
            return;
        }

        if($this->getProjectSetting('disable-error-emails') === true){
            return;
        }

        $url = $this->getUrl('import.php');
        $message .= "  See the logs on <a href='$url'>this page</a> for details.";

        $project = $this->framework->getProject();
        $users = $project->getUsers();

        $emails = [];
        foreach($users as $user){
            if($user->isSuperUser()){
                $emails[] = $user->getEmail();
            }
        }

        global $homepage_contact_email;
        if(empty($emails)){
            // There aren't any super users on the project.  Send to the system admin instead.
            $emails[] = $homepage_contact_email;
        }

        REDCap::email(
            implode(', ', $emails),
            $homepage_contact_email,
            "REDCap Big data import Module Error",
            $message
        );
    }

    function csvToArrayNFieldNames($path)
    {
        // Add CSV string to memory file so we can parse it into an array
        $h = fopen('php://memory', "x+");
        fwrite($h, file_get_contents($path));
        fseek($h, 0);
        // Now read the CSV file into an array
        $data = 0;
        $csv_headers = null;
       $row = fgetcsv($h, 0, ",");

       $data = count($row);

        fclose($h);
        unset($csv_headers, $row);
        return $data;
    }
}