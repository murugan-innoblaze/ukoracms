<?php

//where are we
define('RELATIVE_ASSETS_PATH', '..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//start a session
assureSession();

//user session?
echo activeUserSession() ? 'true' : 'false';

?>