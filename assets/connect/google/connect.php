<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//fetch an association handle
$association_handle = Google::getAssociationHandle();

//create request
$Google = Google::createRequest(GOOGLE_CALLBACK_URL, $association_handle, true);

//close db connection
mysql_close($db);

//redirect user
$Google->redirect();
?>