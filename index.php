<?php

/**
 * PLUGIN NAME: MOCK-REDCap Plugin for Heron Admin
 * DESCRIPTION: Developed by the University of Kansas Medical Center's Medical
 *              Informatics Department for integrating Heron users with Redcap
 * VERSION: 1.0
 * AUTHOR: Michael Prittie, Nazma Sulthana
 */

// Retrieve REDCap global MYSQLi database connection object (REDCapism)
global $conn;
//echo "inside starting index";
error_reporting(E_ALL);
define ("NOAUTH", true);

$redcap_root_path = get_redcap_root();
define('REDCAP_ROOT', $redcap_root_path.'/');
require_once(REDCAP_ROOT.'redcap_connect.php');

define('FRAMEWORK_ROOT', REDCAP_ROOT.'plugins/framework/');
require_once(FRAMEWORK_ROOT.'PluginConfig.php');


define('AUTH_SURVEY_ROOT',REDCAP_ROOT.'plugins/authenticated_surveys/');

$CONFIG = new PluginConfig(AUTH_SURVEY_ROOT.'config.ini');
require_once(AUTH_SURVEY_ROOT.'SurveyController.php');

$controller = new SurveyController(  
	$_GET,	
	$_POST,
	$conn,
	USERID,
	$CONFIG
);

$result = $controller->process_request();

var_dump($result);

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
