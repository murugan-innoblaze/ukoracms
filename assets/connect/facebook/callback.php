<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//assure session
assureSession();

//facebook
$Facebook = new Facebook($db);

//start user
$User = new User($db);

//run a query
if(false !== ($fbuser = $Facebook->fbQuery('select uid, email, name, pic_square from user where uid = me();'))){

	//Current employer
	$fb_uid = isset($fbuser[0]['uid']) ? $fbuser[0]['uid'] : null;
	$fb_name = isset($fbuser[0]['name']) ? $fbuser[0]['name'] : null;
	$fb_email = isset($fbuser[0]['email']) ? $fbuser[0]['email'] : null;
	$fb_pic = isset($fbuser[0]['pic_square']) ? $fbuser[0]['pic_square'] : null;
	
	//introduce user
	$User->introduceNewUser($fb_name, $fb_email, array('facebook_uid' => $fb_uid, 'google_uid' => null, 'linkedin_uid' => null), null, $fb_pic, $fb_latest_title);
	
	//save facebook user info
	saveUserData('facebook_uid', $fb_uid);
	saveUserData('facebook_name', $fb_name);
	saveUserData('facebook_email', $fb_email);
	saveUserData('facebook_profile_pic', $fb_pic);

	//save success facebook connect
	addToIntelligenceStack('facebook connect', 'success');
	
	//save intel
	addToIntelligenceStack('user connect', 'facebook');	

}else{

	//save unsuccessfull facebook connect
	addToIntelligenceStack('facebook connect', 'failed');

}

//save intelligence before forwarding
insertIntelligenceStack();

//navigate back to last page .. regardless of success
$Navigation = new Navigation($db);
$Navigation->returnToLast();

/* //multiple query example $fbfriends = $Facebook->fbMultiQuery(array('query1' => "SELECT uid1 FROM friend WHERE uid2 = me();",'query2' => "SELECT name, url, pic FROM profile WHERE id IN (SELECT uid1 FROM #query1)")); print_r($fbfriends); */

//close db connection
mysql_close($db);
?>