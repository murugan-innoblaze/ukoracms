<?php

//where are we
define('RELATIVE_ASSETS_PATH', '..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//get the shipping expenation
echo '<div style="height: 450px; width: 550px; overflow-y: auto; padding: 7px; text-align: left; line-height: 150%;">' . getStaticContent('shipping_explanation') . '</div>';

?>
