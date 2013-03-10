<?php
define('MYSQL_SERVER', 'localhost');
define('MYSQL_USER', 'DbUser');
define('MYSQL_PASSWORD', '');
define('MYSQL_USE_DB', 'ukoratable');

//lets connect right away
$db = mysql_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, true);
mysql_select_db(MYSQL_USE_DB);
?>
