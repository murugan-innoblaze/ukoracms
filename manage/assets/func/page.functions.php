<?php
/************************************************************************/
/******************* GET FILE CONTENTS **********************************/
/************************************************************************/
function getFileContents($path){
	if(!is_file($path)){ return false; } $fhandle = fopen($path, 'r');
	if(false !== ($content = fread($fhandle, filesize($path)))){ fclose($fhandle); return $content; }else{ return null; }
}

/************************************************************************/
/******************* GET CONTENT AREA FROM TABLE ************************/
/************************************************************************/
function getContentsFor($page_id = null, $area = null){
	if(empty($page_id) or empty($area)){ return null; }
	$sql = "SELECT dzpro_page_content_html FROM dzpro_page_contents WHERE dzpro_page_id = '" . mysql_real_escape_string($page_id) . "' AND dzpro_page_content_name = '" . mysql_real_escape_string($area) . "'"; $result = @mysql_query($sql); if(mysql_num_rows($result) > 0){ $return = array(); while($row = mysql_fetch_assoc($result)){ $return = $row; } mysql_free_result($result); return stripslashes($return['dzpro_page_content_html']); }
	return null;
}

/************************************************************************/
/******************* SET CONTENT AREA FROM TABLE ************************/
/************************************************************************/
function setContentsFor($page_id = null, $area = null, $content = null){
	if(empty($page_id) or empty($area)){ return null; } if(!updateContentsFor($page_id, $area, $content)){ insertContentsFor($page_id, $area, $content); }
}

/************************************************************************/
/******************* UPDATE CONTENT AREA FROM TABLE *********************/
/************************************************************************/
function updateContentsFor($page_id = null, $area = null, $content = null){
	@mysql_query("UPDATE dzpro_page_contents SET dzpro_page_content_html = '" . mysql_real_escape_string($content) . "', dzpro_page_content_last_modified = NOW() WHERE dzpro_page_id = '" . mysql_real_escape_string($page_id) . "' AND dzpro_page_content_name = '" . mysql_real_escape_string($area) . "'"); if(mysql_affected_rows() > 0){ return true; }else{ return false; }
}

/************************************************************************/
/******************* INSERT CONTENT AREA FROM TABLE *********************/
/************************************************************************/
function insertContentsFor($page_id = null, $area = null, $content = null){
	@mysql_query("INSERT INTO dzpro_page_contents (dzpro_page_id, dzpro_page_content_name, dzpro_page_content_html, dzpro_page_content_date_added) VALUES ('" . mysql_real_escape_string($page_id) . "', '" . mysql_real_escape_string($area) . "', '" . mysql_real_escape_string($content) . "', NOW())"); if(mysql_insert_id() > 0){ return true; }else{ return false; }
}

/************************************************************************/
/******************* GET PAGE ELEMENTS **********************************/
/************************************************************************/
function getAllPageElements(){
	$return = array(); $result = @mysql_query("SELECT * FROM dzpro_page_elements"); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $return[$row['dzpro_page_element_id']] = $row; } mysql_free_result($result); } return $return;
}
?>