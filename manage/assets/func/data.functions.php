<?php
/************************************************************************/
/********************* INTRODUCE SITE DATA ******************************/
/************************************************************************/
function introduceSiteData($key = null, $value = null){
	if(empty($key) or empty($value)){ return false; }
	if(false === updateSiteData($key, $value)){ if(false !== insertSiteData($key, $value)){ return true; } }
	return false;
}

/************************************************************************/
/********************* UPDATE SITE DATA *********************************/
/************************************************************************/
function updateSiteData($key = null, $value = null){
	if(empty($key) or empty($value)){ return false; }
	@mysql_query("UPDATE dzpro_site_data SET dzpro_site_data_value = '" . mysql_real_escape_string($value) . "', dzpro_site_data_last_modified = NOW() WHERE dzpro_site_data_key = '" . mysql_real_escape_string($key) . "' AND dzpro_admin_id = '" . mysql_real_escape_string($_SESSION['dzpro_admin_id']) . "'"); if(mysql_affected_rows() > 0){ return true; }
	return false;
}	

/************************************************************************/
/********************* INSERT SITE DATA *********************************/
/************************************************************************/
function insertSiteData($key = null, $value = null){
	if(empty($key) or empty($value)){ return false; }
	@mysql_query("INSERT INTO dzpro_site_data (dzpro_site_data_key, dzpro_admin_id, dzpro_site_data_value, dzpro_site_data_date_added) VALUES ('" . mysql_real_escape_string($key) . "', '" . mysql_real_escape_string($_SESSION['dzpro_admin_id']) . "', '" . mysql_real_escape_string($value) . "', NOW())"); if(mysql_insert_id() > 0){ return true; }
	return false;
}

/************************************************************************/
/********************* GET SITE DATA ************************************/
/************************************************************************/
function getSiteData($key = null){
	if(empty($key)){ return false; }
	$result = @mysql_query("SELECT dzpro_site_data_value FROM dzpro_site_data WHERE dzpro_site_data_key = '" . mysql_real_escape_string($key) . "' AND dzpro_admin_id = '" . mysql_real_escape_string($_SESSION['dzpro_admin_id']) . "'"); if(mysql_num_rows($result) > 0){ $return = null; while($row = mysql_fetch_assoc($result)){ $return = $row['dzpro_site_data_value']; } mysql_free_result($result); return $return; }
	return false;
}
?>