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
$xml_response = $LinkedIn->getProfile("~:(id,first-name,last-name,headline,picture-url,educations,positions)");

//build array
if(false !== ($response_array = unserialize_xml($xml_response))){

	//linkedin id and name
	$linkedin_id = isset($response_array['id']) ? $response_array['id'] : null;
	$linkedin_name = (isset($response_array['first-name']) and isset($response_array['first-name'])) ? $response_array['first-name'] . ' ' . $response_array['last-name'] : null;
	$picture_url = isset($response_array['picture-url']) ? $response_array['picture-url'] : null;
	$headline = isset($response_array['headline']) ? $response_array['headline'] : null;
	
	//run a query
	if(!empty($linkedin_id) and !empty($linkedin_name)){
	
		//start user
		$User = new User($db);
	
		//introduce user
		if(false !== $User->introduceNewUser($linkedin_name, null, $social_uids = array('facebook_uid' => null, 'google_uid' => null, 'linkedin_uid' => $linkedin_id), null)){
	
			//save linkedin user info
			saveUserData('linkedin_uid', $linkedin_id);
			saveUserData('linkedin_name', $linkedin_name);
			saveUserData('linkedin_profile_pic', $picture_url);
			saveUserData('linkedin_headline', $headline);
		
			//save unsuccessfull facebook connect
			addToIntelligenceStack('linkedin connect', 'success');
			
			//save connect type
			addToIntelligenceStack('user connect', 'linkedin');	
		
		} //if successful introduction
	
	}else{ //if we have enough information

		//save unsuccessfull facebook connect
		addToIntelligenceStack('linkedin connect', 'failed');

	} //end if we don't have enough info

} //if we can unserialize

//save intelligence before forwarding
insertIntelligenceStack();

//navigate back to last page .. regardless of success
$Navigation = new Navigation($db);
$Navigation->returnToLast();
?>