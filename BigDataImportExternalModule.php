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
        error_log("cronbigdata - New cronbigdata() call");
        foreach($this->framework->getProjectsWithModuleEnabled() as $localProjectId){
            // This automatically associates all log statements with this project.
            $_GET['pid'] = $localProjectId;
            try{
                $import_list = $this->getProjectSetting('import', $localProjectId);
                foreach ($import_list as $id=>$import){
                    $edoc = $this->getProjectSetting('edoc', $localProjectId)[$id];
                    $import_checked = $this->getProjectSetting('import-checked', $localProjectId)[$id];
                    $import_number = $this->getProjectSetting('import-number', $localProjectId)[$id];
                    $import_continue = $this->getProjectSetting('import-continue', $localProjectId)[$id];
                    $import_check_started = $this->getProjectSetting('import-checked-started', $localProjectId)[$id];

                    error_log( "cronbigdata - Import #".$import_number);
                    error_log("cronbigdata - ".$import." && ".$edoc." != '' && ".$import_continue);
                    if ($import && $edoc != "" && $import_continue) {
                        error_log("cronbigdata - IF");
                        $import_started = $this->getProjectSetting('import', $localProjectId);
                        $import_started[$id] = false;
                        $this->setProjectSetting('import', $import_started,$localProjectId);

                        $error = $this->importRecords($localProjectId, $edoc,$id,$import_number);
                        if($error == "0"){
                            error_log( "cronbigdata - FINISHED!");
                            $logtext = "<div>Import process finished <span class='fa fa-check fa-fw'></span></div>";
                            $this->log($logtext,['import' => $import_number]);
                        }else if($error == "1"){
                            error_log( "cronbigdata - FINISHED with Errors!");
                            $logtext = "<div>Import process finished with errors <span class='fa fa-exclamation-circle fa-fw'></span></div>";
                            $this->log($logtext,['import' => $import_number]);
                        }

                    }else if($import_checked && $edoc != "" && !$import_check_started){
                        error_log("cronbigdata - ELSE");
                        $import_check_started_aux = $this->getProjectSetting('import-checked-started', $localProjectId);
                        $import_check_started_aux[$id] = true;
                        $this->setProjectSetting('import-checked-started', $import_check_started_aux,$localProjectId);

                        $import_cancel = $this->getProjectSetting('import-cancel', $localProjectId);
                        $import_cancel[$id] = true;
                        $this->setProjectSetting('import-cancel', $import_cancel,$localProjectId);

                        $error = $this->checkRecords($localProjectId, $edoc,$id,$import_number);

                        if($error == "0"){
                            $logtext = "<div>Checking process finished <span class='fa fa-check fa-fw'></span></div>";
                            $this->log($logtext,['import' => $import_number]);
                        }else if($error == "1"){
                            $logtext = "<div>Checking process finished with issues <span class='fa fa-exclamation-circle fa-fw'></span></div>";
                            $this->log($logtext,['import' => $import_number]);
                        }

                        if(!$error){
                            $import_after_check = $this->getProjectSetting('import', $localProjectId);
                            $import_after_check[$id] = true;
                            if($import_after_check){
                                error_log( "cronbigdata - cronbigdata()\n");
                                $this->cronbigdata();
                            }
                        }
                    }
                }
            }
            catch(Exception $e){
                $this->log("An error occurred.  Click 'Show Details' for more info.", [
                    'details' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                    'import' => $import_number
                ]);

                $import_email = $this->getProjectSetting('import-email', $localProjectId);
                $import_from = ($this->getProjectSetting('import-from', $localProjectId)=="")?'noreply@vumc.org':$this->getProjectSetting('import-from', $localProjectId);
                if ($import_email != "") {
                    REDCap::email($import_email, $import_from, 'Import process #'.$import_number.' has failed.', "An exception occurred while importing.");
                }
            }
        }
        $_GET['pid'] = $originalPid;
    }

    function hook_every_page_before_render($project_id = null){
        if((strpos($_SERVER['REQUEST_URI'],'delete_project.php') !== false && $_POST['action'] == 'delete') || (strpos($_SERVER['REQUEST_URI'],'erase_project_data.php') !== false && $_POST['action'] == 'erase_data')){
            #Button: Delete the project OR Button: Erase all data
            $this->removeLogs("project_id = $project_id");
            $this->setProjectSetting('edoc', array());
            $this->setProjectSetting('total-import', '');
            $this->setProjectSetting('import', array());
            $this->setProjectSetting('import-number', array());
            $this->setProjectSetting('import-cancel', array());
            $this->setProjectSetting('import-cancel-check', array());
            $this->setProjectSetting('import-delimiter', array());
            $this->setProjectSetting('import-checked', array());
            $this->setProjectSetting('import-continue', array());
            $this->setProjectSetting('import-checked-started', array());
        }
    }

    function checkRecords($project_id,$edoc,$id,$import_number){
        $sql = "SELECT stored_name,doc_name,doc_size,file_extension FROM redcap_edocs_metadata WHERE doc_id='" . db_escape($edoc)."'";
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

        $delimiter = $this->getProjectSetting('import-delimiter',$project_id)[$id];
        $delimiter_text = $delimiter;
        if($delimiter == ""){
            $delimiter = ",";
            $delimiter_text = $delimiter;
        }else if($delimiter == "tab"){
            $delimiter = "\t";
        }

        $this->log("
        <div>Checking records from CSV file:</div>
        <div class='remote-project-title'><ul><li>" . $doc_name . "</li></ul></div>",['import' => $import_number, 'delimiter' => $delimiter_text]);

        $import_email = $this->getProjectSetting('import-email', $project_id);

        $path = EDOC_PATH.$stored_name;
        $content = file($path);
        $fieldNames = explode($delimiter, $content[0]);
        $str = preg_replace('/^[\pZ\p{Cc}\x{feff}]+|[\pZ\p{Cc}\x{feff}]+$/ux', '', $fieldNames[0]);
        $record_id_name = $str;

        $checked_records = "";
        $checked_records_errors = "";
        for ($i = 1; $i < count($content); $i++) {
            $import_cancel_check = $this->getProjectSetting('import-cancel-check', $project_id)[$id];
            if($import_cancel_check){
                $this->log("Checking cancelled <span class='fa fa-ban  fa-fw'></span>");
                $this->resetValues($project_id, $edoc);
                return "2";
            }else{
                $data_aux = str_getcsv($content[$i], $delimiter, '"');
                $record = $data_aux[0];
                $data = REDCap::getData($project_id,'array',$record,$record_id_name);
                if($data && strpos($checked_records,$record) === false && $record != "") {
                    $checked_records .= $record . ", ";
                }else if($record == ""){
                    $checked_records_errors .= ($i+1). ", ";
                }
            }

        }
        $checked_records_errors = rtrim($checked_records_errors, ", ");
        $checked_records = rtrim($checked_records, ", ");
        if($checked_records != "" || $checked_records_errors != ""){
            $sql = "select app_title from redcap_projects where project_id = '".db_escape($project_id)."' limit 1";
            $q = db_query($sql);
            $projectTitle = "";
            while ($row = db_fetch_assoc($q)) {
                $projectTitle = $row['app_title'];
            }
            if($checked_records_errors != ""){
                $this->resetValues($project_id, $edoc);
                $this->log("There are blank records in the file! <span class='fa fa-times  fa-fw'></span>", [
                    'recordlist' => "Line: ".$checked_records_errors
                ]);

                $email_text = "Your checking process on <b>".$projectTitle." [" . $project_id . "]</b> has finished.<br/>There are blank records in the project. Please upload the file again after fixing the error lines.";
                $email_text .="<br/><br/>For more information go to <a href='" . $this->getUrl('import.php') . "'>this page</a>";
                $subject = 'Checking process has finished with blank records';
            }else{
                $import_cancel = $this->getProjectSetting('import-cancel', $project_id);
                $import_cancel[$id] = false;
                $this->setProjectSetting('import-cancel', $import_cancel,$project_id);


                $this->log("There are existing records in the project that match the csv file <span class='fa fa-times  fa-fw'></span>", [
                    'recordlist' => $checked_records
                ]);

                $email_text = "Your checking process on <b>".$projectTitle." [" . $project_id . "]</b> has finished.<br/>There are existing records in the project.";
                $email_text .="<br/><br/>To continue with the import go to <a href='" . $this->getUrl('import.php') . "'>this page and click on <b>Continue Import</b></a>";
                $subject = 'Checking process has finished with existing records';
            }

            if ($import_email != "") {
                $import_from = ($this->getProjectSetting('import-from', $project_id)=="")?'noreply@vumc.org':$this->getProjectSetting('import-from', $project_id);
                REDCap::email($import_email, $import_from, $subject, $email_text);
            }
            return "1";
        }else{
            $import_continue = $this->getProjectSetting('import-continue', $project_id);
            $import_continue[$id] = true;
            $this->setProjectSetting('import-continue', $import_continue,$project_id);

            $import_cancel = $this->getProjectSetting('import-cancel', $project_id);
            $import_cancel[$id] = false;
            $this->setProjectSetting('import-cancel', $import_cancel,$project_id);

            $import = $this->getProjectSetting('import', $project_id);
            $import[$id] = true;
            $this->setProjectSetting('import', $import,$project_id);
        }

        return "0";

    }

    function importRecords($project_id,$edoc,$id,$import_number){
        error_log("cronbigdata - ...importRecords");
        $sql = "SELECT stored_name,doc_name,doc_size,file_extension FROM redcap_edocs_metadata WHERE doc_id='" . db_escape($edoc)."'";
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

        $delimiter = $this->getProjectSetting('import-delimiter',$project_id)[$id];
        $delimiter_text = $delimiter;
        if($delimiter == ""){
            $delimiter = ",";
            $delimiter_text = $delimiter;
        }else if($delimiter == "tab"){
            $delimiter = "\t";
        }

        $overwrite = $this->getProjectSetting('import-overwrite',$project_id)[$id];
        if($overwrite){
            $overwrite = "overwrite";
        }else{
            $overwrite = "normal";
        }

        $datetime = $this->getProjectSetting('import-datetime',$project_id)[$id];
        if($datetime == ""){
            $overwrite = "MDY";
        }

        $chkerrors = $this->getProjectSetting('import-chkerrors',$project_id)[$id];

        $this->log("
        <div>Importing records from CSV file:</div>
        <div class='remote-project-title'><ul><li>" . $doc_name . "</li></ul></div>",['import' => $import_number, 'delimiter' => $delimiter_text]);

        $import_email = $this->getProjectSetting('import-email', $project_id);
        $import_checked = $this->getProjectSetting('import-checked', $project_id)[$id];

        $path = EDOC_PATH.$stored_name;
        $fieldNamesTotal = $this->csvToArrayNFieldNames($path,$delimiter);
        $content = file($path);
        $fieldNames = explode($delimiter, $content[0]);
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
        $chunks = ceil($numberOfBatches);
        $batchSize = ceil((count($content)-1) / $chunks);

        $Proj = new \Project($project_id);
        $event_id = $Proj->firstEventId;

        $sql = "select app_title from redcap_projects where project_id = '".db_escape($project_id)."' limit 1";
        $q = db_query($sql);
        $projectTitle = "";
        while ($row = db_fetch_assoc($q)) {
            $projectTitle = $row['app_title'];
        }

       $sql = "SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id ='".db_escape($project_id)."'";
        $q = db_query($sql);
        $repeatable = false;
        if (db_num_rows($q)) {
            while($row = db_fetch_assoc($q)) {
                $sql2 = "SELECT * FROM redcap_events_repeat WHERE event_id='".db_escape($row['event_id'])."'";
                $q2 = db_query($sql2);
                $row2 = db_fetch_assoc($q2);
                if (db_num_rows($q2)){
                    $repeatable = true;
                    break;
                }
            }
        }
        $count = 0;
        $totalrecordsIds = "";
        $warnings = "";
        $warnings_errors = "";
        $import_chkerrors_details =  "";
        $jsonresults = array();
        for ($i = 0; $i < $batchSize; $i++) {
            $import_records = "";
            $batchText = "batch " . ($i + 1) . " of " . $batchSize;
            $batchTextImport = "Batch " . ($i + 1) . " of " . $batchSize;
            $data = array();
            $numrecords = 0;
            for ($line = 1; $line <= $chunks; $line++) {
                if(($count+$line) <= (count($content)-1)){
                    $data_aux = str_getcsv($content[($line + $count)], $delimiter, '"');
                    $aux = array();
                    $instrument = "";
                    $instance = "";
                    $record = $data_aux[0];
                    foreach ($fieldNames as $index => $field) {
                        if ($field == "redcap_repeat_instrument") {
                            $instrument = $data_aux[$index];
                        } else if ($field == "redcap_repeat_instance") {
                            $instance = $data_aux[$index];
                        } else if ($field == "redcap_event_name") {
                            $event_id = $Proj->getEventIdUsingUniqueEventName($data_aux[$index]);
                        } else {
                            $aux[$field] = $data_aux[$index];
                        }
                    }
                    if ($repeatable) {
                        if ($instance != "") {
                            $data[$record]['repeat_instances'][$event_id][$instrument][$instance] = $aux;
                        } else {
                            $data[$record][$event_id] = $aux;
                        }
                    } else {
                        $data[$record] = array();
                        $data[$record][$event_id] = $aux;
                    }
                    if (strpos($import_records, $record) === false && $record != '') {
                        $import_records .= $record . ", ";
                        $numrecords++;
                    }
                    if (strpos($totalrecordsIds, $record) === false && $record != '') {
                        $totalrecordsIds .= $record . ", ";
                    }
                }
            }
            $count += $chunks;
            $results = \Records::saveData($project_id, 'array', $data, $overwrite, $datetime, 'flat', '', true, true, true, false, true, array(), true, false, 1, false, '');
            $results = $this->adjustSaveResults($results,$fieldNames);
            array_push($jsonresults,$results);
            $stopEarly = false;
            $icon = "";
            if (empty($results['errors'])) {
                $message = "completed ";

                if (empty($results['warnings'])) {
                    $message .= 'successfully for';
                } else {
                    $message .= 'with warnings for';
                    $errormsg = ltrim($results['warnings'],"Array");
                    if (strpos($warnings, $errormsg) === false) {
                        $warnings .= $errormsg ;
                    }
                    if (strpos($warnings_errors, $import_records) === false) {
                        $warnings_errors .= $import_records;
                    }
                }
            }else if(!empty($results['errors']) && $chkerrors){
                $message = 'has <strong>errors</strong> for';
                $icon = "<span class='fa fa-times  fa-fw'></span>";
                $error = "• Error on record ". json_encode($results['ids']). ": <br>".$results['errors']."<br>";
                if (strpos($import_chkerrors_details, $error) === false) {
                    $import_chkerrors_details .= $error;
                }
            } else {
                $message = "did NOT complete successfully.<br> Errors in";
                $stopEarly = true;
            }

            $details = "";
            if ($stopEarly ) {
                $icon = "<span class='fa fa-times  fa-fw'></span>";
                $details = json_encode($results, JSON_PRETTY_PRINT);
            }
            $this->log("Import #$import_number $message $batchText $icon", [
                'details' => $details,
                'recordlist' => rtrim($import_records, ", "),
                'import' => $import_number,
                'batch' => $batchTextImport
            ]);

            if ($stopEarly) {
                if(!$chkerrors){
                    $this->resetValues($project_id, $edoc);
                    $email_text = "Your import process on <b>".$projectTitle." [" . $project_id . "]</b> has finished.<br/>REDCap was unable to import some record data.";
                    $email_text .="<br/><br/>For more information go to <a href='" . $this->getUrl('import.php') . "'>this page</a>";
                    if ($import_email != "") {
                        $import_from = ($this->getProjectSetting('import-from', $project_id)=="")?'noreply@vumc.org':$this->getProjectSetting('import-from', $project_id);
                        REDCap::email($import_email, $import_from, 'Import process #'.$import_number.' has failed', $email_text);

                    }

                    $this->log("Data", [
                        'file' => $doc_name,
                        'totalrecordsIds' => rtrim($totalrecordsIds, ", "),
                        'status' => 1,
                        'edoc' => $edoc,
                        'checked' =>$import_checked,
                        'import' => $import_number,
                        'batch' => $batchTextImport
                    ]);
                    return "1";
                }
            }
            $import_cancel = $this->getProjectSetting('import-cancel', $project_id)[$id];
            if($import_cancel){
                $this->log("Import #$import_number cancelled <span class='fa fa-ban  fa-fw'></span>", ['import' => $import_number]);
                $this->resetValues($project_id, $edoc);
                $this->log("Data", [
                    'file' => $doc_name,
                    'totalrecordsIds' => rtrim($totalrecordsIds, ", "),
                    'status' => 2,
                    'edoc' => $edoc,
                    'checked' =>$import_checked,
                    'import' => $import_number,
                    'batch' => $batchTextImport
                ]);
                return "2";
            }
        }
        error_log( "cronbigdata - JSON:".json_encode($jsonresults));

        $this->log("Data", [
            'file' => $doc_name,
            'totalrecordsIds' => rtrim($totalrecordsIds, ", "),
            'status' => 0,
            'edoc' => $edoc,
            'checked' =>$import_checked,
            'import' => $import_number,
            'batch' => $batchTextImport
        ]);

        if($import_chkerrors_details != "" && $chkerrors){
            $this->log("Errors", [
                'import' => $import_number,
                'chkerrors' => $import_chkerrors_details
            ]);
        }

        $this->resetValues($project_id, $edoc);
        if ($import_email != "") {
            $email_text = "Your import process on <b>".$projectTitle." [" . $project_id . "]</b> has finished.";
            $email_text .= "<br/><br/>For more information go to <a href='" . $this->getUrl('import.php') . "'>this page</a>";
            $import_from = ($this->getProjectSetting('import-from', $project_id)=="")?'noreply@vumc.org':$this->getProjectSetting('import-from', $project_id);
            REDCap::email($import_email, $import_from, 'Import process #'.$import_number.' finished', $email_text);
        }
        if(!empty($warnings)){
            $this->log("Import #$import_number finished with warnings <span class='fa fa-exclamation-circle warning fa-fw'></span>", [
                'recordlist' => rtrim($warnings_errors,", "),
                'details' => $warnings,
                'import' => $import_number,
                'batch' => $batchTextImport
            ]);
        }
        return "0";
    }

    private function adjustSaveResults($results,$fieldNames){
        $results['warnings'] = array_filter($results['warnings'], function($warning){
            global $lang;

            if(strpos($warning[3], $lang['data_import_tool_197']) !== -1){
                return false;
            }

            return true;
        });

        if(empty($results['warnings'])){
            foreach ($results['values'] as $warnings){
                foreach ($fieldNames as $name){
                    if(array_key_exists('validation',$warnings[$name]) && $warnings[$name]['validation'] == 'warning'){
                        if(strpos($results['warnings'], $warnings[$name]['message']) === false || empty($results['warnings'])){
                            $results['warnings'] .= $warnings[$name]['message']."\n";
                        }
                    }
                }
            }

        }

        return $results;
    }

    function resetValues($project_id,$edoc){
       $import_list = empty($this->getProjectSetting('import'))?array():$this->getProjectSetting('import');
        $import_number = $this->getProjectSetting('import-number',$project_id);
        $import_cancel = $this->getProjectSetting('import-cancel',$project_id);
        $import_cancel_check = $this->getProjectSetting('import-cancel-check',$project_id);
        $import_delimiter = $this->getProjectSetting('import-delimiter');
        $import_datetime = $this->getProjectSetting('import-overwrite');
        $import_overwrite = $this->getProjectSetting('import-datetime');
        $import_checked = $this->getProjectSetting('import-checked');
        $import_continue = $this->getProjectSetting('import-continue');
        $import_chkerrors = $this->getProjectSetting('import-chkerrors');
        $import_check_started = $this->getProjectSetting('import-checked-started');
        $edoc_list = $this->getProjectSetting('edoc',$project_id);
        if (($key = array_search($edoc, $edoc_list)) !== false) {
            unset($edoc_list[$key]);
            unset($import_list[$key]);
            unset($import_number[$key]);
            unset($import_cancel[$key]);
            unset($import_cancel_check[$key]);
            unset($import_delimiter[$key]);
            unset($import_datetime[$key]);
            unset($import_overwrite[$key]);
            unset($import_checked[$key]);
            unset($import_continue[$key]);
            unset($import_chkerrors[$key]);
            unset($import_check_started[$key]);
        }
        $this->setProjectSetting('edoc', $edoc_list,$project_id);
        $this->setProjectSetting('import', $import_list,$project_id);
        $this->setProjectSetting('import-number', $import_number,$project_id);
        $this->setProjectSetting('import-cancel', $import_cancel,$project_id);
        $this->setProjectSetting('import-cancel-check', $import_cancel_check,$project_id);
        $this->setProjectSetting('import-delimiter', $import_delimiter,$project_id);
        $this->setProjectSetting('import-datetime', $import_datetime,$project_id);
        $this->setProjectSetting('import-overwrite', $import_overwrite,$project_id);
        $this->setProjectSetting('import-checked', $import_checked,$project_id);
        $this->setProjectSetting('import-continue', $import_continue,$project_id);
        $this->setProjectSetting('import-chkerrors', $import_chkerrors,$project_id);
        $this->setProjectSetting('import-checked-started', $import_check_started,$project_id);

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

    function getDocName($edoc){
        $sql = "SELECT stored_name,doc_name,doc_size,file_extension FROM redcap_edocs_metadata WHERE doc_id='" . db_escape($edoc)."'";
        $q = db_query($sql);

        if ($error = db_error()) {
            echo $sql . ': ' . $error;
            $this->exitAfterHook();
        }

        $doc_name = "";
        while ($row = db_fetch_assoc($q)) {
            $doc_name = $row['doc_name'];
        }

        return $doc_name;
    }

    function csvToArrayNFieldNames($path,$delimiter)
    {
        // Add CSV string to memory file so we can parse it into an array
        $h = fopen('php://memory', "x+");
        fwrite($h, file_get_contents($path));
        fseek($h, 0);
        // Now read the CSV file into an array
        $data = 0;
        $csv_headers = null;
        $row = fgetcsv($h, 0, $delimiter);

        $data = count($row);

        fclose($h);
        unset($csv_headers, $row);
        return $data;
    }
}