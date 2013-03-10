<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//make sure we have a session
assureSession();

# First step is to initialize with your consumer key and secret. We'll use an out-of-band oauth_callback
$LinkedIn = new LinkedIn(LINKEDIN_API_KEY, LINKEDIN_SECRET_KEY, LINKEDIN_CALLBACK_URL);
//$linkedin->debug = true;

# Now we retrieve a request token. It will be set as $linkedin->request_token
$LinkedIn->getRequestToken();
$_SESSION['linkedin']['requestToken'] = serialize($LinkedIn->request_token);

//forward to authorization url
header("Location: " . $LinkedIn->generateAuthorizeUrl());
exit(0);
?>