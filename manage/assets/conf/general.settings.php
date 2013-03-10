<?php
define('DOCUMENT_ROOT', 'xxxxx/vhosts/ukora.com/htdocs/manage');
define('FRONTEND_DOCUMENT_ROOT', 'xxxxx/vhosts/ukora.com/htdocs');
define('FRONTEND_TEMPLATES_PATH', '/assets/templates/');
define('FRONTEND_FORM_BUILDER_PATH', '/assets/class/PageForm.class.php');
define('FRONTEND_ELEMENTS_PATH', '/assets/elements/');
define('CRONJOBS_PATH_PATH', '/assets/cron/');
define('HOST_NAME', '');
define('SITE_NAME', '');
define('MANAGER_DOMAIN' , 'manage.ukora.com');
define('ASSETS_PATH', '/assets');
define('UPLOADS_PATH', '/uploads');
define('CLASSES_PATH', ASSETS_PATH . '/class/');
define('SETTINGS_PATH', ASSETS_PATH  . '/conf/');
define('FUNCTIONS_PATH', ASSETS_PATH . '/func/');
define('CLASS_APPEND', '.class.php');
define('SELFLOADER', 'selfload.functions.php');
define('SITE_SALT', '');

define('SITE_TIMEZONE', 'America/Chicago');

//required key on all tables
//define('REQUIRED_KEY', 'dropzone_id');

require_once DOCUMENT_ROOT . FUNCTIONS_PATH . SELFLOADER;
?>
