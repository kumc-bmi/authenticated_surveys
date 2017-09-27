<?php

/**
 * PLUGIN NAME: Authenticated Surveys Plugin 
 * DESCRIPTION: Developed by the University of Kansas Medical Center's Medical
 *              Informatics Department to generate secure survey links 
		for integrating other applications like HERON with Redcap
 * VERSION: 1.0
 * AUTHOR: Michael Prittie, Nazma Sulthana
 */

// Provides access to REDCap helper functions and database connection.
//(REDCapism)
global $conn;

// Report all PHP errors
error_reporting(E_ALL);

// Skipping the REDCap login authentication
define ("NOAUTH", true);

// Defining the REDCap root directory path
$redcap_root_path = get_redcap_root();
define('REDCAP_ROOT', $redcap_root_path.'/');

// Need redcap_connect.php to connect this plugin to REDCap framework
require_once(REDCAP_ROOT.'redcap_connect.php');

// Defining the authenticated surveys plugin root path
define('AUTH_SURVEY_ROOT',REDCAP_ROOT.'plugins/authenticated_surveys/');

// Load configuration plugin configuration
define('FRAMEWORK_ROOT', REDCAP_ROOT.'plugins/framework/');
require_once(FRAMEWORK_ROOT.'PluginConfig.php');
$CONFIG = new PluginConfig(AUTH_SURVEY_ROOT.'config.ini');

// Class SurveyController's object handles the incoming HTTP requests

echo "inside php";

if($_POST['action'] == surveyLink){

	require_once(AUTH_SURVEY_ROOT.'SurveyController.php');

	$controller = new SurveyController(  
			$_GET,	
			$_POST,
			$conn,
			USERID,
			$CONFIG
			);

	$result = $controller->process_request();

}else{

	$result = array('success'=>false,
			'result'=>"Invalid value in action");
}

// Displaying the reponse of the processed request
//var_dump($result);
echo "inside php";
echo json_encode($result);

/*
get_redcap_root() determines the REDCap ROOT path.

From the current directory, iteratively checks in the parent directory
for the files install.php, redcap_connect.php and database.php
to determine if that location is the REDCap root.
*/
function get_redcap_root() {
	$dir = dirname(__FILE__);
        while (!file_exists($dir.'/redcap_connect.php') && 
		!file_exists($dir.'/database.php') && 
		!file_exists($dir.'/install.php') && 
		strlen($dir) > 3) {

                $dir = dirname($dir);
        }
        if (file_exists($dir.'/redcap_connect.php')) {
		return $dir;
        }
	exit;
}

?>
