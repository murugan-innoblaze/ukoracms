<?php

//ini_set('display_errors', 1);

//where are we
define('RELATIVE_ASSETS_PATH', 'assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//secure connection is a must
//forceSecureConnection();

//assure session
assureSession();

//get this page
$Page = new Page($db);

//load page template
$Page->includePageTemplate();

//page unload
pageUnload();

?>