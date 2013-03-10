<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//make sure we have a session
assureSession();

//Get google response
$googleLogin = Google::getResponse();

//check for success
if($googleLogin->success()){
	
	//get google user id
	$user_id = urldecode($googleLogin->identity());
	
	//get user mail
	$user_email = urldecode($googleLogin->email());
	
	//start user
	$User = new User($db);
	
	//get the username
	$user_name = trim(ucwords(preg_replace('/[^a-z^0-9]/', ' ', substr($user_email, 0, strpos($user_email, '@')))));
	
	//introduce user
	$User->introduceNewUser($user_name, $user_email, $social_uids = array('facebook_uid' => null, 'google_uid' => $user_id, 'linkedin_uid' => null), null);
	
	//save google user information
	saveUserData('google_uid', $user_id);
	saveUserData('google_user_name', $user_name);
	saveUserData('google_email', $user_email);

	//save unsuccessfull facebook connect
	addToIntelligenceStack('linkedin connect', 'success');
			
	//save connect type
	addToIntelligenceStack('user connect', 'linkedin');	

}else{

	//save unsuccessfull facebook connect
	addToIntelligenceStack('linkedin connect', 'failed');

}

//save intelligence before forwarding
insertIntelligenceStack();

//navigate back to last page .. regardless of success
$Navigation = new Navigation($db);
$Navigation->returnToLast();

//close db connection
mysql_close($db);
?>