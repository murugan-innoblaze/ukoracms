<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//make sure we have a session
assureSession();

# First step is to initialize with your consumer key and secret. We'll use an out-of-band oauth_callback
$LinkedIn = new LinkedIn(LINKEDIN_API_KEY, LINKEDIN_SECRET_KEY, LINKEDIN_CALLBACK_URL);
//$LinkedIn->debug = true;
 
if(isset($_REQUEST['oauth_verifier'])){
	$_SESSION['linkedin']['oauth_verifier'] = $_REQUEST['oauth_verifier'];
	$LinkedIn->request_token = unserialize($_SESSION['linkedin']['requestToken']);
	$LinkedIn->oauth_verifier = $_SESSION['linkedin']['oauth_verifier'];
	$LinkedIn->getAccessToken($_REQUEST['oauth_verifier']);
	$_SESSION['linkedin']['oauth_access_token'] = serialize($LinkedIn->access_token);
	header("Location: " . LINKEDIN_CALLBACK_URL);
	exit;
}else{	
	$LinkedIn->request_token = unserialize($_SESSION['linkedin']['requestToken']);
	$LinkedIn->oauth_verifier = $_SESSION['linkedin']['oauth_verifier'];
	$LinkedIn->access_token = unserialize($_SESSION['linkedin']['oauth_access_token']);
}

//get profile
$xml_response = $LinkedIn->getProfile("~:(id,first-name,last-name)");

//build array
if(false !== ($response_array = unserialize_xml($xml_response))){

	//linkedin id and name
	$linkedin_id = isset($response_array['id']) ? $response_array['id'] : null;
	$linkedin_name = (isset($response_array['first-name']) and isset($response_array['first-name'])) ? $response_array['first-name'] . ' ' . $response_array['last-name'] : null;
	
	//run a query
	if(!empty($linkedin_id) and !empty($linkedin_name)){
		//Do something?
	}

} //if we can unserialize

//Forward the user back to talk section
header('Location: ' . TALK_SECTION_SITE_PATH);

//close the database connection
mysql_close($db);
?>