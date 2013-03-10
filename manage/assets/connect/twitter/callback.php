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

  	//Save twitter info
	introduceSiteData('twitter_oauth_token', $access_token['oauth_token']);
	introduceSiteData('twitter_oauth_token_secret', $access_token['oauth_token_secret']);

	/* Remove no longer needed request tokens */
	$_SESSION['twitter'] = array(); unset($_SESSION['twitter']);

	//Forward the user back to talk section
	header('Location: ' . TALK_SECTION_SITE_PATH); exit(0);

}else{

	/* Remove no longer needed request tokens */
	$_SESSION['twitter'] = array(); unset($_SESSION['twitter']);
	
	/* Save HTTP status for error dialog on connnect page.*/
	header('Location: ./clearsessions.php'); exit(0);

}
?>