<?php
/************************************************************************/
/******************* CLASS AUTOLOAD FUNCTION ****************************/
/************************************************************************/
function __autoload($class_name){ require_once DOCUMENT_ROOT . CLASSES_PATH . $class_name . CLASS_APPEND; }

/************************************************************************/
/******************* LOAD ALL SETTINGS **********************************/
/************************************************************************/
function loadAllSettings(){
	global $states_list, $months, $weekdays, $db, $db_alt;
	if($handle = opendir(DOCUMENT_ROOT . SETTINGS_PATH)){ while(false !== ($file = readdir($handle))){ if($file != '.' && $file != '..'){ require_once DOCUMENT_ROOT . SETTINGS_PATH . $file; } } closedir($handle); }
}

/************************************************************************/
/******************* LOAD ALL FUNCTIONS *********************************/
/************************************************************************/
function loadAllFunctions(){
	if($handle = opendir(DOCUMENT_ROOT . FUNCTIONS_PATH)){ while(false !== ($file = readdir($handle))){ if($file != '.' && $file != '..'){ require_once DOCUMENT_ROOT . FUNCTIONS_PATH . $file; } } closedir($handle); }
}

/************************************************************************/
/******************* INCLUDE SETTINS AND FUNCTIONS **********************/
/************************************************************************/
loadAllSettings();
loadAllFunctions();

/************************************************************************/
/******************* SET THE RIGHT TIMEZONE *****************************/
/************************************************************************/
date_default_timezone_set(SITE_TIMEZONE);
?>