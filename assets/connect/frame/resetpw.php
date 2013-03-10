<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

if(isset($_POST['email']) and !empty($_POST['email'])){ addToIntelligenceStack('password reset', 'send email'); $User = new User($db); echo $User->sendResetLink($_POST['email']); }

//save intel
insertIntelligenceStack();

//close db connection
mysql_close($db);
?>