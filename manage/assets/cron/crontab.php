<?php

//where are we
define('RELATIVE_ASSETS_PATH', 'xxxxxx/vhosts/ukora.com/htdocs/manage/assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//cron object
$Cron = new Cron($db);

//Do the cron thing
$Cron->selectCronJobs();

//close the database connection
mysql_close($db);

?>
