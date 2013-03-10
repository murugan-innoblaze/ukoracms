<?php
//where are we
define('RELATIVE_ASSETS_PATH', 'assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//authenticate session
$Auth = new Auth($db, false);

//If we are already logged in lets go to the index page
if(isset($_SESSION['dzpro_admin_id']) and !empty($_SESSION['dzpro_admin_id'])){ header('Location: /'); exit(0); }
?>
<!DOCTYPE html> 
<html lang="en-us"> 
	<head> 
		<script type="text/javascript" src="/assets/js/jq.js"></script>
		<script type="text/javascript" src="/assets/js/jq-ui.js"></script>
		<link type="text/css" href="/assets/css/ui.css" rel="stylesheet" media="all" />
		<style>
			* { margin: 0; padding: 0; text-align: left; }
			body { font-family: Arial, Verdana, Serif; background-color: #333333; min-width: 1024px; background-image: url('/assets/img/manager/carb_bg.jpg'); background-position: top left; background-repeat: repeat; text-align: center; position: relative; }		
			a { color: #edf3ff; }
			img { border: none; }
			h1 { }
			h2 { }
			h3 { }
			h4 { }
			p { }
			ul { list-style: none; }
			table { width: 100%; }
			td { text-align: left; }
			:focus { outline-width: 0pt; outline-style: none; }
			
			.inner_shadow { -moz-box-shadow: inset -1px 1px 3px #bbbbbb; -webkit-box-shadow: inset -1px 1px 3px #bbbbbb; box-shadow: inset -1px 1px 3px #bbbbbb; }
			.shadow { -moz-box-shadow: -1px 1px 10px #222222; -webkit-box-shadow: -1px 1px 10px #222222; box-shadow: -1px 1px 10px #222222; }

			#top-half { height: 280px; position: fixed; top: 0px; left: 0px; width: 100%; background-color: #939393; z-index: 1; background-image: url('/assets/img/manager/barcode-bg.png'); background-position: center center; background-repeat: no-repeat; }
			form#login-form { margin: 120px auto 0 auto; display: block; width: 300px; position: relative; background-color: #c4cbd3; padding: 20px 40px; -moz-border-radius: 10px; border-radius: 10px; z-index: 3; }
			form#login-form #login-plane { position: absolute; top: 10px; left: 280px; }
			form#login-form #header_tag { font-size: 18px; color: #ffffff; text-align: left; line-height: 120%; width: 212px; text-shadow: -1px 1px 1px #333333; }
			form#login-form #header_tag strong { font-size: 32px; }
			form#login-form td { padding: 7px 0; vertical-align: middle; }
			form#login-form td input { border-top: 1px solid #82858b; border-right: 1px solid #82858b; border-bottom: 1px solid #686a70; border-left: 1px solid #686a70; background-color: white; height: 41px; -moz-border-radius: 10px; border-radius: 10px; color: #222222; font-size: 20px; padding: 0px 8px; width: 280px; }
			form#login-form td input#login_submit { border-top: 1px solid #2a354b; border-right: 1px solid #4062c4; border-bottom: 1px solid #1b49b4; border-left: 1px solid #3b5fc2; background-image: url('/assets/img/manager/button_ok_bg_rep_x.png'); background-repeat: repeat-x; background-position: top left; height: 41px; -moz-border-radius: 10px; border-radius: 10px; color: #ffffff; font-size: 16px; padding: 0px 16px; width: auto; }
			form#login-form td input#login_submit:active { background-position: bottom left; }
			
			#bottom_links { width: 300px; margin: 0 auto; padding: 5px; text-align: center; }
			#bottom_links a { font-size: 12px; text-shadow: -1px 1px 1px #333333; }
		</style>
		<script type="text/javascript">
			function popOutToTop(){ if(top.location != this.location){ top.location = this.location; } }
			$().ready(function(){
				$('#login_loader').hide();
				popOutToTop();
			});
			function performLogin(){
				$('#top-half').slideUp(500);
				$('#bottom_links').fadeOut(100);
				setTimeout(function(){
					$('#login-form').fadeOut(300);
				},1000);
			}
		</script>
	</head>
	<body>
		<div id="top-half" class="shadow"><!-- block --></div>
		<form id="login-form" class="shadow" action="/" method="post">
			<!--<img src="/assets/img/manager/login-plane.png" id="login-plane" alt="plane" />-->
			<table cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td colspan="2">
							<div id="header_tag">
								Sign in to <?=HOST_NAME?><br />
								<strong></strong>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<input type="text" name="username" class="inner_shadow" />
						</td>
					</tr>
					<tr>
						<td colspan="2">	
							<input type="password" name="password" class="inner_shadow" />
						</td>
					</tr>
					<tr>
						<td>
							<input type="submit" id="login_submit" name="logging_in" value="Sign In" />
						</td>
						<td>
							<img src="/assets/img/manager/loader-image.gif" id="login_loader" alt="loader image" />
						</td>
					</tr>
				</tbody>
			</table>
		</form><!-- end login-form -->
	</body>
</html>