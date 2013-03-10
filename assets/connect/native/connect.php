<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//navigate back to last page .. regardless of success
$Navigation = new Navigation($db);

//start user
$User = new User($db);

//check values
if(isset($_POST['username']) and isset($_POST['email']) and isset($_POST['password']) and isset($_POST['captcha']) and strtolower($_POST['captcha']) == strtolower($_SESSION['captcha'])){

	//introduce user
	if(false === $User->introduceNewUser($_POST['username'], $_POST['email'], $social_uids = array('facebook_uid' => null, 'google_uid' => null, 'linkedin_uid' => null), $_POST['password'])){ 
	
		//capture fields
		$User->captureConnectFieldsFailover('User could not be added.'); //assume it - and remove when success		
	
		//Forward
		gotoConnectPage(); 

		//save intel
		insertIntelligenceStack();
	
	}

	//save intel
	addToIntelligenceStack('new user', 'regular');
	
}else{

	//save intel
	addToIntelligenceStack('new user', 'failed attempt');
	
	//capture fields
	$User->captureConnectFields('Missing fields.'); //assume it - and remove when success

	//forward 
	gotoConnectPage();
	
	//save intel
	insertIntelligenceStack();	

}

//save intelligence before forwarding
insertIntelligenceStack();

//return to last page
$Navigation->returnToLast();

//close db connection
mysql_close($db);
?>