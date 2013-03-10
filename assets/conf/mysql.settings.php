<?php
/* enable on installer */
define('MYSQL_SERVER', 'localhost'); //set installer
define('MYSQL_USER', 'DbUser'); //set installer
define('MYSQL_PASSWORD', 'mysqlpassword'); //set installer
define('MYSQL_USE_DB', 'ukoratable'); //set installer

//lets connect right away
$db = mysql_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, true) or die(MAINTENANCE_MESSAGE);
mysql_select_db(MYSQL_USE_DB, $db) or die(MAINTENANCE_MESSAGE);
?>
