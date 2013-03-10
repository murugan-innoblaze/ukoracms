<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../../');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//assure session
assureSession();

//remove twitter sessions
$_SESSION = array(); unset($_SESSION['twitter']);
 
/* Redirect to page with the connect to Twitter option. */
header('Location: ./connect.php');
?>