<?php

/****************************************************************************************/
/******************************** GET RESULTS ON KEY ************************************/
/****************************************************************************************/
function mysql_query_on_key($query = null, $key = null){
	if(!have($query)){ return false; }
	if(!have($key)){ return false; }
	$result = @mysql_query($query) or handleError(1, 'sql:' . $query . ' error:' . mysql_error()); if(mysql_num_rows($result) > 0){ $return = array(); while($row = mysql_fetch_assoc($result)){ $return[$row[$key]] = $row; } mysql_free_result($result); return $return; } return null;
}

/****************************************************************************************/
/******************************** GET RESULTS FLAT **************************************/
/****************************************************************************************/
function mysql_query_flat($query = null){
	if(!have($query)){ return false; }
	$result = @mysql_query($query) or handleError(1, 'sql:' . $query . ' error:' . mysql_error()); if(mysql_num_rows($result) > 0){ $return = array(); while($row = mysql_fetch_assoc($result)){ $return[] = $row; } mysql_free_result($result); return $return; } return null;
}

/****************************************************************************************/
/******************************** ARE THERE MYSQL ROWS **********************************/
/****************************************************************************************/
function mysql_query_got_rows($query = null){
	if(!have($query)){ return false; }
	$return = false; $result = @mysql_query($query) or handleError(1, 'sql:' . $query . ' error:' . mysql_error()); if(mysql_num_rows($result)){ $return = true; } mysql_free_result($result); return $return; 
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
	$return = false; $result = @mysql_query($query) or handleError(1, 'sql:' . $query . ' error:' . mysql_error()); if(mysql_num_rows($result)){ while($row = mysql_fetch_assoc($result)){ $return = $row; } mysql_free_result($result); } return $return; 
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

/****************************************************************************************/
/******************************** GET MYSQL DATA DUMP ***********************************/
/****************************************************************************************/
function get_mysql_dump($tables = '*', $exclude_data_for = null){
	$return = null; if(have($exclude_data_for)){ $exclude_data_for_tables = is_array($exclude_data_for) ? $exclude_data_for : explode(',', $exclude_data_for); } if($tables == '*'){ $tables = array(); $result = mysql_query('SHOW TABLES'); while($row = mysql_fetch_row($result)){ $tables[] = $row[0]; } }else{ $tables = is_array($tables) ? $tables : explode(',', $tables); }
	foreach($tables as $table){ $result = mysql_query('SELECT * FROM ' . mysql_real_escape_string($table)); $num_fields = mysql_num_fields($result); $return .= 'DROP TABLE ' . mysql_real_escape_string($table) . ';';
    $row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE ' . mysql_real_escape_string($table))); $return .= "\n\n" . $row2[1] . ";\n\n";
    if(!isset($exclude_data_for_tables) or (isset($exclude_data_for_tables) and !in_array($table, $exclude_data_for_tables))){
	    for($i = 0; $i < $num_fields; $i++){ 
	    	while($row = mysql_fetch_row($result)){ 
	    		$return .= 'INSERT INTO ' . mysql_real_escape_string($table) . ' VALUES('; 
	    		for($j = 0; $j < $num_fields; $j++){ 
	    			$row[$j] = addslashes($row[$j]); $row[$j] = ereg_replace("\n","\\n",$row[$j]); 
	    			if(isset($row[$j])){ 
	    				$return .= '"' . mysql_real_escape_string($row[$j]) . '"' ; 
	    			}else{ 
	    				$return .= '""'; 
	    			} 
	    			if($j < ($num_fields-1)){ $return .= ','; } 
	    		} 
	    		$return .= ");\n"; 
	    	} 
	    } 
    	$return .= "\n\n\n";
    }
    } return $return;
}

?>