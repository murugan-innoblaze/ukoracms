<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//assure session
assureSession();

//facebook
$Facebook = new Facebook($db);

//run a query
if(false !== ($fbuser = $Facebook->fbQuery('select uid, name from user where uid = me();'))){

	//Current employer
	$fb_uid = isset($fbuser[0]['uid']) ? $fbuser[0]['uid'] : null;
	$fb_name = isset($fbuser[0]['name']) ? $fbuser[0]['name'] : null;

	//Save The Facebook User Info
	introduceSiteData('facebook_user_id', $fb_uid);
	introduceSiteData('facebook_user_name', $fb_name);
	introduceSiteData('facebook_access_token', $_COOKIE['access_token']);

}

//Forward the user back to talk section
header('Location: ' . TALK_SECTION_SITE_PATH);

//close db connection
mysql_close($db);
?>