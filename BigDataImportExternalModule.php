<?php
namespace Vanderbilt\BigDataImportExternalModule;

use Exception;
use REDCap;

class BigDataImportExternalModule extends \ExternalModules\AbstractExternalModule{

    public function __construct(){
        parent::__construct();
    }

    function cronBigDataImport(){
        $originalPid = $_GET['pid'];
        foreach($this->framework->getProjectsWithModuleEnabled() as $localProjectId){
            // This automatically associates all log statements with this project.
            $_GET['pid'] = $localProjectId;
            try{
                $import = $this->getProjectSetting('import', $localProjectId);
                if ($import) {
                    $this->importRecords($localProjectId, $this->getProjectSetting('edoc', $localProjectId));
                    $this->log("<div>Import process finished.</div>");
                }
            }
            catch(Exception $e){
                $this->log("An error occurred.  Click 'Show Details' for more info.", [
                    'details' => $e->getMessage() . "\n" . $e->getTraceAsString()
                ]);

                $this->sendErrorEmail("An exception occurred while syncing.");
            }
        }
        $_GET['pid'] = $originalPid;
    }

    function importRecords($project_id,$edoc){

        $sql = "SELECT stored_name,doc_name,doc_size,file_extension FROM redcap_edocs_metadata WHERE doc_id=" . $edoc;
        $q = db_query($sql);

        if ($error = db_error()) {
            die($sql . ': ' . $error);
        }

        $doc_name = "";
        while ($row = db_fetch_assoc($q)) {
            $doc_name = $row['doc_name'];
        }

        $this->log("
			<div>Importing records from CVS file:</div>
			<div class='remote-project-title'><ul><li>" . $doc_name. "</li></ul></div>
		");

        $uploadedfile_name = \Files::copyEdocToTemp($edoc, true, true);
        $importData = \DataImport::csvToArray($uploadedfile_name);
//        $importData = \DataImport::csvToArray(EDOC_PATH."20190430185225_pid137_6w7rno.csv");


        $fieldNames = array();
        foreach ($importData as $records){
            foreach ($records as $events) {
                foreach ($events as $field_name=>$value) {
                    array_push($fieldNames,$field_name);
                }
                break;
            }
            break;
        }

        $recodsData = array();
        foreach ($importData as $records){
            foreach ($records as $events) {
                array_push($recodsData,$events);
            }
        }


        // Use the number of fields times number of records as a metric to determine a reasonable chunk size.
        // The following calculation caused about 500MB of maximum memory usage when importing the TIN Database (pid 61715) on the Vanderbilt REDCap test server.
        $numberOfDataPoints = count($fieldNames) * count($importData);
        $numberOfBatches = $numberOfDataPoints / 100000;
        $batchSize = round(count($importData) / $numberOfBatches);
        $chunks = array_chunk($importData, $batchSize);

        $import_email = $this->getProjectSetting('import-email', $project_id);

        for($i=0; $i<count($chunks); $i++){
            $chunk = $chunks[$i];
            $batchText = "batch " . ($i+1) . " of " . count($chunks);

            $this->log("Importing $batchText");

            $results = \Records::saveData($project_id,'array', $chunk, 'normal', 'MDY', 'flat', '', true, true, true, false, true, array(), true, false, 1, false,'');
            $results = $this->adjustSaveResults($results);

            $stopEarly = false;
            if(empty($results['errors'])){
                $message = "completed ";

                if(empty($results['warnings'])){
                    $message .= 'successfully';
                }
                else{
                    $message .= 'with warnings';
                }
            }
            else{
                $message = "did NOT complete successfully";
                $stopEarly = true;
            }

            $this->log("Import $message for $batchText", [
                'details' => json_encode($results, JSON_PRETTY_PRINT)
            ]);

            if($stopEarly){
                $this->resetValues($project_id,$edoc);
                if($import_email != ""){
                    $email_text = "Your import process on project ".$project_id." has finished.<br/>REDCap was unable to import some record data.<br/><br/>For more information go to the project.";
                    $emails = preg_split("/[;,]+/", $import_email);
                    foreach ($emails as $email){
                        REDCap::email($email, 'noreply@vumc.org', 'Import process finished', $email_text);
                    }
                }
                $this->sendErrorEmail("REDCap was unable to import some record data.");
                return;
            }
        }

        $this->resetValues($project_id,$edoc);

        if($import_email != ""){
            $email_text = "Your import process on project ".$project_id." has finished.";
            REDCap::email($import_email, 'noreply@vumc.org', 'Import process finished.<br/><br/>For more information go to the project.', $email_text);
        }
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
//        $this->setProjectSetting('import', false);
//        $this->setProjectSetting('edoc', '');
//        if($edoc != "" && $project_id != ""){
//            $sql = "DELETE FROM redcap_edocs_metadata WHERE project_id=".$project_id." AND doc_id=" . $edoc;
//            $q = db_query($sql);
//
//            if ($error = db_error()) {
//                die($sql . ': ' . $error);
//            }
//        }
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
}