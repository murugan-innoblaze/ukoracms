<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../../');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//assure session
assureSession();

/* If the oauth_token is old redirect to the connect page. */
if (isset($_REQUEST['oauth_token']) && $_SESSION['twitter']['oauth_token'] !== $_REQUEST['oauth_token']) {
  $_SESSION['twitter']['oauth_status'] = 'oldtoken';
  header('Location: ./clearsessions.php');
}

/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
$connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $_SESSION['twitter']['oauth_token'], $_SESSION['twitter']['oauth_token_secret']);

/* Request access tokens from twitter */
$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

/* If HTTP response is 200 continue otherwise send to connect page to retry */
if(200 == $connection->http_code) {

	//start user
	$User = new User($db);

	//introduce user
	$User->introduceNewUser($access_token['screen_name'], null, array('facebook_uid' => null, 'google_uid' => null, 'linkedin_uid' => null, 'twitter_uid' => $access_token['user_id']), null, null, null);
	
	//save facebook user info
	saveUserData('twitter_uid', $access_token['user_id']);
	saveUserData('twitter_name', $access_token['screen_name']);

	//save success facebook connect
	addToIntelligenceStack('twitter connect', 'success');
	
	//save intel
	addToIntelligenceStack('user connect', 'twitter');		

	//save intelligence before forwarding
	insertIntelligenceStack();

	/* Remove no longer needed request tokens */
	$_SESSION['twitter'] = array(); unset($_SESSION['twitter']);

	//Go to where we left from
	$Navigation = new Navigation($db);
	$Navigation->returnToLast(); 

	//stop
	exit(0);

}else{

	//save success facebook connect
	addToIntelligenceStack('twitter connect', 'success');

	//save intelligence before forwarding
	insertIntelligenceStack();

	/* Remove no longer needed request tokens */
	$_SESSION['twitter'] = array(); unset($_SESSION['twitter']);
	
	/* Save HTTP status for error dialog on connnect page.*/
	header('Location: ./clearsessions.php'); exit(0);

}
?>