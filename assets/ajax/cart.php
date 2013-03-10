<?php

//where are we
define('RELATIVE_ASSETS_PATH', '..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//start a session
assureSession();

//cart class
$Cart = new Cart($db);

//get simple stats
$stats_array = $Cart->getSimpleStats();

//json..
echo json_encode($stats_array);

?>