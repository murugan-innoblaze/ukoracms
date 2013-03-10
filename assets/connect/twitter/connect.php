<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../../');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//assure session
assureSession();

/* Build TwitterOAuth object with client credentials. */
$connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
 
/* Get temporary credentials. */
$request_token = $connection->getRequestToken(TWITTER_OAUTH_CALLBACK);

/* Save temporary credentials to session. */
$_SESSION['twitter']['oauth_token'] = $token = $request_token['oauth_token'];
$_SESSION['twitter']['oauth_token_secret'] = $request_token['oauth_token_secret'];
 
/* If last connection failed don't display authorization link. */
switch ($connection->http_code) {
	case 200:
		header('Location: ' . $connection->getAuthorizeURL($token)); exit(0);
	break;
  	default:
		$Navigation = new Navigation($db);
		$Navigation->returnToLast(); 
	break;
}
?>