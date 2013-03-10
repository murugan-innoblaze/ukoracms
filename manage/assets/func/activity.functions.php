<?php
/***************************************************************************/
/****************** REGISTER ADMIN ACTIVITY ********************************/
/***************************************************************************/
function registerAdminActivity($name = null, $description = null){
	@mysql_query(" INSERT INTO dzpro_admin_activity ( dzpro_admin_id, dzpro_admin_activity_name, dzpro_admin_activity_description, dzpro_admin_activity_path, dzpro_admin_activity_ip, dzpro_admin_activity_date_added ) VALUES ( " . (int)$_SESSION['dzpro_admin_id'] . ", '" . mysql_real_escape_string($name) . "', '" . mysql_real_escape_string($description) . "', '" . mysql_real_escape_string($_SERVER['SCRIPT_NAME']) . "', '" . mysql_real_escape_string($_SERVER['REMOTE_ADDR']) . "', NOW() ) ") or handleError(1, mysql_error());
}
?>