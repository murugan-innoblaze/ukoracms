<?php
/**************************************************************/
/****************** REDIRECT USER TO NEW LOCATION *************/
/**************************************************************/
function findNewLocation(){
	$result = @mysql_query(" SELECT * FROM dzpro_redirects WHERE dzpro_redirect_catch LIKE '" . mysql_real_escape_string($_SERVER['REQUEST_URI']) . "%' LIMIT 1 "); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ header('HTTP/1.1 301 Moved Permanently'); header('Location: ' . $row['dzpro_redirect_target']); exit(0); } mysql_free_result($result); }
	return false;
}
?>