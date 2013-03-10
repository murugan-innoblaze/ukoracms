<?php
/* enable on installer */
define('DOCUMENT_ROOT', 'xxxxxx/vhosts/ukora.com/htdocs'); //set installer
define('COOKIE_URL_DOMAIN', 'ukora.com'); //set installer
define('SITE_NAME', 'Ukora'); //set installer
define('HOST_NAME', 'ukora.com'); //set installer
define('MANAGER_DOMAIN' , 'manage.ukora.com'); //set installer
define('ASSETS_PATH', '/assets');
define('CLASSES_PATH', '/assets/class/');
define('SETTINGS_PATH', '/assets/conf/');
define('FUNCTIONS_PATH', '/assets/func/');
define('TEMPLATES_PATH', '/assets/templates/');
define('ELEMENTS_PATH', '/assets/elements/');
define('CLASS_APPEND', '.class.php');
define('SELFLOADER', 'selfload.functions.php');
define('FILE_404', '/404.php');
define('SITE_SALT', 'adding some salt is a good idea 098324 nice and secure 1234782634'); //set installer

define('SITE_TIMEZONE', 'America/Chicago'); //set installer

define('MAINTENANCE_MESSAGE', '<div style="padding: 40px; text-align: center; font-family: Arial;"><h1>Performing site maintenance</h1><p>We are sorry but we are currently performing some site maintenance - we will be back online soon<p></div>');

require_once DOCUMENT_ROOT . FUNCTIONS_PATH . SELFLOADER;
?>
