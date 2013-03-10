<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//alternate failed attempt
define('FRAME_CONNECT_PATH', '/assets/frame/connect.php');

//start user class
$User = new User($db);

//check values
if(isset($_POST['email']) and !empty($_POST['email']) and isset($_POST['password']) and !empty($_POST['password'])){ 
	
	//Login user
	if(false === ($User->loginUser($_POST['email'], $_POST['password']))){ 
	
		//failed login
		addToIntelligenceStack('native login', 'no match'); 

		//capture fields
		$User->captureConnectFields('No match found.'); //assume it - and remove when success		

		//save intel
		insertIntelligenceStack();
	
		//Forward
		gotoConnectPage(); 
	
	}

	//save intelligence
	addToIntelligenceStack('user connect', 'regular'); 
	
	//success login
	addToIntelligenceStack('native login', 'success'); 
	
}else{

	//failed login
	addToIntelligenceStack('native login', 'failed'); 

	//capture fields
	$User->captureConnectFields('Fields are missing.'); //assume it - and remove when success		

	//save intel
	insertIntelligenceStack();
	
	//goto connect page
	gotoConnectPage();

}

//save intel
insertIntelligenceStack();

//navigate back to last page .. regardless of success
$Navigation = new Navigation($db);
$Navigation->returnToLast();

//close db connection
mysql_close($db);
?>