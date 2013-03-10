<?php
/******************************************************************************/
/******************* HANDLE ERROR *********************************************/
/******************************************************************************/
function handleError($level = null, $message = null){
	$backtrace = debug_backtrace();
	@mysql_query(" INSERT INTO dzpro_errors ( dzpro_error_path, dzpro_error_line, dzpro_error_parent_path, dzpro_error_parent_line, dzpro_error_level, dzpro_error_message, dzpro_error_details, dzpro_error_uri, dzpro_error_date_added ) VALUES ( '" . mysql_real_escape_string(str_ireplace(DOCUMENT_ROOT, null, isset($backtrace[1]['file']) ? $backtrace[1]['file'] : null)) . "', '" . mysql_real_escape_string(isset($backtrace[1]['line']) ? $backtrace[1]['line'] : null) . "', '" . mysql_real_escape_string(str_ireplace(DOCUMENT_ROOT, null, isset($backtrace[0]['file']) ? $backtrace[0]['file'] : null)) . "', '" . mysql_real_escape_string(isset($backtrace[0]['line']) ? $backtrace[0]['line'] : null) . "', '" . mysql_real_escape_string($level) . "', '" . mysql_real_escape_string($message) . "', '" . mysql_real_escape_string(json_encode(error_get_last())) . "', '" . mysql_real_escape_string(getFullUrl()) . "', NOW() ) ");
	if(mysql_insert_id()){ return true; }
	return false;
}

/******************************************************************************/
/******************** PRINT ERROR *********************************************/
/******************************************************************************/
function print_error($error = null){
	if(!empty($error)){ echo '<strong>error:</strong> ' . $error . '<br />'; }
	die();
}
?>