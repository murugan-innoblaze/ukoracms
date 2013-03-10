<?php

if(!defined('RELATIVE_ASSETS_PATH')){
	
	//where are we
	define('RELATIVE_ASSETS_PATH', '..');
	
	//knock over the first domino
	require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';
	
	//assure session
	assureSession();
	
	//clean include
	$clean_include = true;

}

?>
<div id="wrapper_signup">
	<script type="text/javascript">
		<!--
			function reloadCaptcha(){ var d = new Date(); $('.catcha_image').attr('src', $('.catcha_image').attr('src') + "?" + d.getTime()); }
			$().ready(function(){
				$('.signup_btn').click(function(){
					$('.alert-message').hide();
					$('.signup').show();
					$('.lostpassword').hide();
					$('.login').hide();
				});
				$('.login_btn').click(function(){
					$('.alert-message').hide();	
					$('.login').show();
					$('.lostpassword').hide();
					$('.signup').hide();
				});
				$('.lostpw_btn').click(function(){
					$('.alert-message').hide();
					$('.login').hide();
					$('.signup').hide();
					$('.lostpassword').show();
				});
			});
		//-->
	</script>
	<?php $show_signup = (!userAtMachine() or (isset($_SESSION['connect_capture']['username']) and !$clean_include) and !(isset($_SESSION['connect_capture_to_login']) and $_SESSION['connect_capture_to_login'] === true)) ? true : false; ?>
	<div class="row login" <?php if($show_signup){ ?>style="display: none;"<?php } ?>>
		<div class="span4">
			<div class="content" style="border-right: 1px solid #dddddd;">
				<div style="position: relative;"><img src="/assets/img/or.gif" alt="or" style="right: -18px; top: 85px; position: absolute;" /></div>
				<h3>Facebook user?</h3>
				<p><a href="https://graph.facebook.com/oauth/authorize?client_id=<?=FACEBOOK_APPLICATION_ID?>&amp;redirect_uri=https://www.<?=HOST_NAME?><?=FACEBOOK_CATCH_COOKIE_URL?>&amp;type=user_agent&amp;display=page&amp;scope=<?=FACEBOOK_DATA_SCOPE?>" title="Connect with your Facebook&reg; account" class="facebook_login"><!-- block --></a></p>
				<h3>Google account?</h3>
				<p><a href="<?=GOOGLE_CONNECT_URL?>" class="google_login" title="Connect with your Google&reg; account"><!-- block --></a></p>
				<h3>On Twitter?</h3>
				<p><a href="<?=TWITTER_CONNECT_URL?>" class="twitter_login" title="Connect with your Twitter&reg; account"><!-- block --></a></p>
			</div>
		</div>
		<div class="span12">
			<div class="content">
				<form action="/assets/connect/native/login.php" method="post">
			        <fieldset>
			          	<legend>Login to <?=strtolower(HOST_NAME)?></legend>
						<div class="clearfix">
				  			<label for="email">Your email</label>
			            	<div class="input">
			              		<input name="email" size="30" type="text" />
			              		<span class="help-block">Enter your email address</span>
			            	</div>
			          	</div>
			          	<div class="clearfix">
				  			<label for="password">Your password</label>
			            	<div class="input">
			              		<input name="password" size="30" type="password" />
			              		<span class="help-block">Please enter your password</span>
			            	</div>
			          	</div>
			          	<div class="clearfix">
							<label><!-- spacer --></label>
							<div class="input">
								<ul class="inputs-list">
									<li>
									  	<label>
									  		<input type="checkbox" name="receive_notifications" checked="true" />
						    				<span>Yes, I would like to receive occasional <?=strtolower(HOST_NAME)?> insider updates.</span>
										</label>
									</li>
									<li>
										Having trouble logging in? <a href="javascript:void(0);" class="lostpw_btn" title="Recover your password">Click here to recover your password.</a>
									</li>
									<li>
										Need an account? <a href="javascript: void(0);" class="signup_btn">Click here to create an account.</a>
									</li>
			          			</ul>
			          		</div>
			          	</div>
					</fieldset>
		          	<div class="actions">
						<input value="Login" class="btn primary" type="submit" />
						- or -
						<input value="Register" class="btn signup_btn" type="submit" onclick="javascript: return false;" />
					</div>
				</form>
			</div>
		</div>
	</div>
	<div class="row signup" <?php if(!$show_signup){ ?>style="display: none;"<?php } ?>>
		<div class="span4">
			<div class="content" style="border-right: 1px solid #dddddd;">
				<div style="position: relative;"><img src="/assets/img/or.gif" alt="or" style="right: -18px; top: 85px; position: absolute;" /></div>
				<h3>Facebook user?</h3>
				<p><a href="https://graph.facebook.com/oauth/authorize?client_id=<?=FACEBOOK_APPLICATION_ID?>&amp;redirect_uri=https://www.<?=HOST_NAME?><?=FACEBOOK_CATCH_COOKIE_URL?>&amp;type=user_agent&amp;display=page&amp;scope=<?=FACEBOOK_DATA_SCOPE?>" title="Connect with your Facebook&reg; account" class="facebook_connect"><!-- block --></a></p>
				<h3>Google account?</h3>
				<p><a href="<?=GOOGLE_CONNECT_URL?>" class="google_connect" title="Connect with your Google&reg; account"><!-- block --></a></p>
				<h3>On Twitter?</h3>
				<p><a href="<?=TWITTER_CONNECT_URL?>" class="twitter_connect" title="Connect with your Twitter&reg; account"><!-- block --></a></p>
			</div>
		</div>
		<div class="span12">
			<div class="content">
				<form action="/assets/connect/native/connect.php" method="post">
					<fieldset>
			          	<legend>Register with <?=strtolower(HOST_NAME)?></legend>					
						<div class="clearfix">
				  			<label for="username">Your name</label>
			            	<div class="input">
			              		<input name="username" size="30" type="text" />
			              		<span class="help-block">Limited to 3-18 characters. Numbers and letters only</span>
			            	</div>
			          	</div>
						<div class="clearfix">
				  			<label for="email">Your email</label>
			            	<div class="input">
			              		<input name="email" size="30" type="text" />
			              		<span class="help-block">Please enter a valid email address.</span>
			            	</div>
			          	</div>			          	
						<div class="clearfix">
				  			<label for="password">Your password</label>
			            	<div class="input">
			              		<input name="password" size="30" type="password" />
			              		<span class="help-block">Make it 5-25 characters, don't use spaces.</span>
			            	</div>
			          	</div>
			          	<div class="clearfix">
				  			<label for="captcha">Type what you see</label>
			            	<div class="input">
			              		<table cellpadding="0" cellspacing="0" style="width: auto;">
			              			<tbody>
			              				<tr>
			              					<td style="border: none; padding: 0px;">
			              						<img src="/assets/captcha/generate.php" alt="captcha" class="catcha_image" style="border: 1px solid #dddddd;" />
			              					</td>
			              					<td style="border: none; padding: 0 0 0 12px;">
			              						<input name="captcha" size="30" type="text" />
			              						<span class="help-block">If you can't read this, <a href="javascript:void(0);" onclick="javascript: reloadCaptcha(); return false;" title="Try another image">try another one</a></span>
			              					</td>
			              				</tr>
			              			</tbody>
			              		</table>
			              	</div>
			          	</div>
						<div class="clearfix">
							<label><!-- spacer --></label>
							<div class="input">
								<ul class="inputs-list">
									<li>
									  	<label>
									  		<input type="checkbox" name="receive_notifications" checked="true" />
						    				<span>Yes, I would like to receive occasional <?=strtolower(HOST_NAME)?> insider updates.</span>
										</label>
									</li>
			          			</ul>
			          		</div>
			          	</div>
					</fieldset>
					<div class="actions">
						<input value="Register" class="btn primary" type="submit" />
						- or -
						<input value="Login" class="btn login_btn" type="submit" onclick="javascript: return false;" />
					</div>
				</form>
			</div>
		</div>
	</div>
	<div class="row lostpassword" style="display: none;">	
		<div class="span4">
			<div class="content">
				<h3>Don't worry! We can help.</h3>
				<p>Enter your email, and we'll send you a link where you can reset your password.</p>
			</div>
		</div>
		<div class="span12">
			<div class="content" style="border-left: 1px solid #dddddd; padding-left: 25px;">
				<form action="/assets/connect/native/resetpw.php" method="post">
					<fieldset>
			          	<legend>Recover Password</legend>		
						<script type="text/javascript">
							<!--
								$().ready(function(){
									$('.btn[name=recover]').click(function(){
										var submittedEmail = $('#recover_email_value').val();
										$('#valid_email_reset_mssg').hide();
										$('#invalid_email_reset_mssg').hide();
										$('#valid_email_reset_loading').show();
										$('.alert-message').hide();
										$.ajax({
											url : '/assets/connect/native/resetpw.php',
											data : 'email=' + submittedEmail,
											type : 'post',
											success : function(mssg){
												$('#valid_email_reset_loading').hide();
												if(mssg == 'send'){
													setTimeout(function(){
														$('#recover_email_value').val('');
														$('.submit_button[name=recover]').hide();
														$('#invalid_email_reset_mssg').hide();
														$('#valid_email_reset_mssg').show();
														$('#recover_password_close').show();
													}, 1000);
												}else{
													$('#valid_email_reset_mssg').hide();
													$('#invalid_email_reset_mssg').show();
													$('.alert-message').html('<p>Oops! We didn\'t find an account for this email</p>').show();
												}
											}
										})
									});
									$('#recover_password_close').click(function(){ $('#fancybox-close').trigger('click'); });
								});
							//-->
						</script>		
						<div class="clearfix">
				  			<label for="email">Your email</label>
			            	<div class="input">
			              		<input name="email" size="30" type="text" id="recover_email_value" value="" />
			              		<span class="help-block">Please enter your email</span>
			            	</div>
			          	</div>
						<div class="clearfix">
							<label><!-- spacer --></label>
							<div class="input">
								<ul class="inputs-list">
									<li>
										Please be patient for it might take a minute before the email gets there
									</li>
								</ul>
							</div>
						</div>	
					</fieldset>
					<div class="actions">
						<input type="submit" name="recover" class="btn primary" value="Send password reset email" onclick="javascript: return false;" />
						<span id="valid_email_reset_loading" style="font-size: 12px; display: none;">
							<img src="/assets/img/ajax-loader.gif" alt="loader" style="vertical-align: middle;" /> Just a second...
						</span>
						<span id="valid_email_reset_mssg" style="font-size: 12px; background-color: #e8f3e8; display: none;">
							<img src="/assets/img/checkmark.png" alt="checkmark" style="vertical-align: middle;" /> Please check your email for the next step!
						</span>
						<span id="invalid_email_reset_mssg" style="font-size: 12px; background-color: #f3e8e8; display: none;">
							<img src="/assets/img/errormark.png" alt="errormark" style="vertical-align: middle;" /> Oops! We didn't find an account for this email
						</span>
					</div>
				</form>
			</div>
		</div>
	</div>
</div><!-- end wrapper -->
<?php

if(isset($clean_include)){

	//close the database connection
	mysql_close($db);

}
?>