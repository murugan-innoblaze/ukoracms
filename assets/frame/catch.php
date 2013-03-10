<?php
	
//where are we
define('RELATIVE_ASSETS_PATH', '..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//assure session
assureSession();

?>
<!doctype html>
<html>
	<head>
		<title>Connect to <?=trim(HOST_NAME)?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="description" content="Connect to <?=trim(HOST_NAME)?>" />
		<meta name="keywords" content="connect, <?=trim(HOST_NAME)?>" />
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
		<link href="/assets/css/connect-frame.css" type="text/css" rel="stylesheet" media="all" />
		<script type="text/javascript">
			window.close();
		</script>
	</head>
	<body>
		returning...
	</body>
</html>