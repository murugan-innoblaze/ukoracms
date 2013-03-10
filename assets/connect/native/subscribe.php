<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

if(isset($_POST['email']) and !empty($_POST['email']) and isset($_POST['name']) and !empty($_POST['name'])){ 

	//remember success
	addToIntelligenceStack('subscriber added', 'success'); 
	
	//handle new subscriber
	$result = @mysql_query("SELECT dzpro_subscriber_id FROM dzpro_subscribers WHERE dzpro_subscriber_email = '" . mysql_real_escape_string($_POST['email']) . "'"); if(mysql_num_rows($result) > 0){ echo 'This email has already been added.'; mysql_free_result($result); }else{ @mysql_query("INSERT INTO dzpro_subscribers (dzpro_subscriber_name, dzpro_subscriber_email, dzpro_subscriber_date_added) VALUES ('" . mysql_real_escape_string($_POST['name']) . "', '" . mysql_real_escape_string($_POST['email']) . "', NOW())"); if(mysql_insert_id() > 0){ echo 'added'; }else{ echo 'Your email could not be added.'; } }
	
}else{

	//return invalid
	echo 'Please enter your name and email.';

	//remember failure
	addToIntelligenceStack('subscriber added', 'failed');

}

//save intel
insertIntelligenceStack();

//close db connection
mysql_close($db);
?>