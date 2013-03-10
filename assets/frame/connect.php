<?php
	
//where are we
define('RELATIVE_ASSETS_PATH', '..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//assure session
assureSession();

//the visitor
$Visitor = new Visitor($db);

//start navigation
$Navigation = new Navigation($db);
$Navigation->addToStack(FRAME_CONNECT_RETURN_PATH);

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
	</head>
	<body>
		<div id="wrapper_signup">
			<script type="text/javascript">
				<!--

				//-->
			</script>
			<?php if(isset($_SESSION['connect_capture']) or have($_SESSION['connect_failed_reason'])){ $message = have($_SESSION['connect_failed_reason']) ? $_SESSION['connect_failed_reason'] : 'There was a problem with your submission - please review your inputs'; ?>
			<div class="problem_mssg"><?=$message?></div>
			<script type="text/javascript"> $().ready(function(){ <?php foreach($_SESSION['connect_capture'] as $key => $value){ if(have($value)){ ?> $('input[name=<?=$key?>]', '#wrapper_signup').val('<?=$value?>'); <?php } } ?> }); </script>
			<?php } ?>
			<table class="wrapper_signup_outer signin" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td class="signup_bottom_left">
							<h2>Connect</h2>
							<div style="height: 7px;"><!-- spacer --></div>
							<a href="http://graph.facebook.com/oauth/authorize?client_id=<?=FACEBOOK_APPLICATION_ID?>&amp;redirect_uri=http://<?=HOST_NAME?><?=FACEBOOK_CATCH_COOKIE_URL?>&amp;type=user_agent&amp;display=page&amp;scope=<?=FACEBOOK_DATA_SCOPE?>" title="Connect with your Facebook&reg; account" id="facebook_login" target="_blank" onclick="window.open('http://graph.facebook.com/oauth/authorize?client_id=<?=FACEBOOK_APPLICATION_ID?>&amp;redirect_uri=http://<?=HOST_NAME?><?=FACEBOOK_CATCH_COOKIE_URL?>&amp;type=user_agent&amp;display=page&amp;scope=<?=FACEBOOK_DATA_SCOPE?>', 'Connect to <?=HOST_NAME?>', 'width=800,height=480,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,copyhistory=no,resizable=yes');return false;"><!-- block --></a>
							<div style="height: 18px;"><!-- spacer --></div>
							<a href="<?=GOOGLE_CONNECT_URL?>" id="google_login" title="Connect with your Google&reg; account" target="_blank" onclick="window.open('<?=GOOGLE_CONNECT_URL?>','Connect to <?=HOST_NAME?>', 'width=800, height=480, toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, copyhistory=no,resizable=yes');return false;"><!-- block --></a>
							<div style="height: 18px;"><!-- spacer --></div>
							<a href="<?=TWITTER_CONNECT_URL?>" id="twitter_login" title="Connect with your Twitter&reg; account" target="_blank" onclick="window.open('<?=TWITTER_CONNECT_URL?>','Connect to <?=HOST_NAME?>', 'width=800, height=480, toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, copyhistory=no, resizable=yes');return false;"><!-- block --></a>
						</td>
						<td class="signup_bottom_right">
							<h2>Login</h2>
							<form action="/assets/connect/frame/login.php" method="post">
								<table class="signup_fields_table" cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td class="label" colspan="2"> 
												Your email
											</td>
										</tr>
										<tr>
											<td class="left">
												<input type="text" name="email" value="" />
											</td>
											<td class="right">
												<p>Make sure you typed it correctly.</p>
											</td>
										</tr>
										<tr>
											<td class="label" colspan="2"> 
												Your password
											</td>
										</tr>
										<tr>
											<td class="left">
												<input type="password" name="password" value="" />
											</td>
											<td class="right">
												<p>Please enter your password</p>
											</td>
										</tr>
										<tr>
											<td class="bottom_area" colspan="2">
												<input type="submit" name="login" class="signin" value="" />
											</td>
										</tr>
									</tbody>
								</table>
							</form>
						</td>
					</tr>
				</tbody>
			</table>
		</div><!-- end wrapper -->
	</body>
</html>

<?php

//end script
saveStackAndExit();

?>