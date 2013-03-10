<?php
function saveSubscriber($email = null, $name = null){
	if(!have($email)){ return false; }
	if(!have($name)){ return false; }
	$result = @mysql_query("SELECT dzpro_subscriber_id FROM dzpro_subscribers WHERE dzpro_subscriber_email = '" . mysql_real_escape_string($email) . "'"); if(mysql_num_rows($result) > 0){ addToIntelligenceStack('subscriber added', 'failed'); mysql_free_result($result); }else{ @mysql_query("INSERT INTO dzpro_subscribers (dzpro_subscriber_name, dzpro_subscriber_email, dzpro_subscriber_date_added) VALUES ('" . mysql_real_escape_string($name) . "', '" . mysql_real_escape_string($email) . "', NOW())"); if(mysql_insert_id() > 0){ addToIntelligenceStack('subscriber added', 'success'); return true; }else{ addToIntelligenceStack('subscriber added', 'failed'); } }
	return false;
}
?>