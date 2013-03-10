<?php
//smtp
define('SMTP_EMAIL_CLASS', '/usr/share/php/Mail.php');
define('SMTP_HOST', 'ssl://smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_AUTH', true);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM', '');
define('SMTP_OUTBOX_SEND_MAX', 20);

//imap
define('IMAP_SERVER', '{imap.gmail.com:993/imap/ssl}INBOX');
define('IMAP_USERNAME', '');
define('IMAP_PASSWORD', '');
define('IMAP_ARTICLE_DEPOT_NAME', '');
define('IMAP_ARTICLE_DEPOT_EMAIL', '');
?>