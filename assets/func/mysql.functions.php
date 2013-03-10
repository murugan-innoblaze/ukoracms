<?php

/****************************************************************************************/
/******************************** GET RESULTS ON KEY ************************************/
/****************************************************************************************/
function mysql_query_on_key($query = null, $key = null){
	if(!have($query)){ return false; }
	if(!have($key)){ return false; }
	$result = @mysql_query($query) or handleError(1, 'sql:' . $query . ' error:' . mysql_error()); if(is_resource($result) and mysql_num_rows($result) > 0){ $return = array(); while($row = mysql_fetch_assoc($result)){ $return[$row[$key]] = $row; } mysql_free_result($result); return $return; } return null;
}

/****************************************************************************************/
/******************************** GET RESULTS FLAT **************************************/
/****************************************************************************************/
function mysql_query_flat($query = null){
	if(!have($query)){ return false; }
	$result = @mysql_query($query) or handleError(1, 'sql:' . $query . ' error:' . mysql_error()); if(is_resource($result) and mysql_num_rows($result) > 0){ $return = array(); while($row = mysql_fetch_assoc($result)){ $return[] = $row; } mysql_free_result($result); return $return; } return null;
}

/****************************************************************************************/
/******************************** ARE THERE MYSQL ROWS **********************************/
/****************************************************************************************/
function mysql_query_got_rows($query){
	if(!have($query)){ return false; }
	$return = false; $result = @mysql_query($query) or handleError(1, 'sql:' . $query . ' error:' . mysql_error()); if(is_resource($result) and mysql_num_rows($result) > 0){ $return = true; } mysql_free_result($result); return $return; 
}

/****************************************************************************************/
/******************************** HOW MANY MYSQL ROWS ***********************************/
/****************************************************************************************/
function mysql_query_row_count($query = null){
	if(!have($query)){ return false; }
	$return = 0; $result = @mysql_query($query) or handleError(1, 'sql:' . $query . ' error:' . mysql_error()); if(false !== ($count = mysql_num_rows($result))){ $return = $count; } mysql_free_result($result); return $return; 
}

/****************************************************************************************/
/******************************** GET MYSQL ROW *****************************************/
/****************************************************************************************/
function mysql_query_get_row($query = null){
	if(!have($query)){ return false; }
	$return = false; $result = @mysql_query($query) or handleError(1, 'sql:' . $query . ' error:' . mysql_error()); if(is_resource($result) and mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $return = $row; } mysql_free_result($result); } return $return; 
}

/****************************************************************************************/
/******************************** MYSQL INSERT ROW **************************************/
/****************************************************************************************/
function mysql_insert($query = null){
	if(!have($query)){ return false; } 
	@mysql_query($query) or handleError(1, 'sql:' . $query . ' error:' . mysql_error()); $return = mysql_insert_id(); if(is_numeric($return) and $return > 0){ return $return; } 
	return false;
}

/****************************************************************************************/
/******************************** MYSQL INSERT ROW **************************************/
/****************************************************************************************/
function mysql_update($query = null){
	if(!have($query)){ return false; } 
	@mysql_query($query) or handleError(1, 'sql:' . $query . ' error:' . mysql_error()); if(mysql_affected_rows() > 0){ return true; }
	return false;
}

?>