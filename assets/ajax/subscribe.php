<?php
//where are we
define('RELATIVE_ASSETS_PATH', '..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//assure session
assureSession();
?>
<div id="wrapper_signup" style="text-align: left;">
	<script type="text/javascript">
		<!--
			$().ready(function(){
				$('#subscribe_button').click(function(){
					var subscriberName = $('#subscriber_name_text').val();
					var subscriberEmail = $('#subscriber_email_text').val();
					$('#valid_email_added_mssg').hide();
					$('#invalid_email_added_mssg').hide();
					$('#valid_email_added_loading').show();
					$.ajax({
						url: '/assets/connect/native/subscribe.php',
						type: 'post',
						data: 'name='+subscriberName+'&email='+subscriberEmail,
						success: function(mssg){
							if(mssg == 'added'){
								$('#valid_email_added_loading').hide();
								$('#subscriber_name_text').val('');
								$('#subscriber_email_text').val('');
								$('#invalid_email_added_mssg').hide();
								$('#valid_email_added_mssg').show();
								$('#recover_password_close').show();
							}else{
								$('#valid_email_added_loading').hide();
								$('#invalid_email_added_mssg').show().children('.message').text(mssg);
								$('#valid_email_added_mssg').hide();
								$('#recover_password_close').show();
							}
						}
					});
				});
			});
		//-->
	</script>
	<div class="row">
		<div class="span8">
			<div class="page-header">
    			<h1>Keep me posted!</h1>
				<p>We'll let you know right away when our beta version is ready for you to try.</p>
  			</div>
			<form method="post">
				<fieldset>
		          	<legend>Please share your details</legend>
					<div class="clearfix">
			  			<label for="name">Your name</label>
		            	<div class="input">
		              		<input type="text" name="name" id="subscriber_name_text" value="" />
		              		<span class="help-block">Please enter your name</span>
		            	</div>
		          	</div>
		          	<div class="clearfix">
			  			<label for="email">Your email</label>
		            	<div class="input">
		              		<input type="text" id="subscriber_email_text" name="email" value="" />
		              		<span class="help-block">Please enter a valid email</span>
		            	</div>
		          	</div>
		    	</fieldset>
		    	<div class="actions" style="padding-left: 130px; margin-bottom: 0px !important;">
					<table cellpadding="0" cellspacing="0" style="width: auto;">
						<tbody>
							<tr>
								<td style="width: 110px; border: none;">
									<input type="submit" name="subscribe" id="subscribe_button" class="btn primary" value="Keep me posted" onclick="javascript: return false;" />
								</td>
								<td style="vertical-align: middle; border: none;">
									<span id="valid_email_added_loading" style="font-size: 12px; display: none;">
										<img src="/assets/img/ajax-loader.gif" alt="loader" style="vertical-align: middle;" /> Just a second...
									</span>
									<span id="valid_email_added_mssg" style="font-size: 12px; background-color: #e8f3e8; display: none;">
										<img src="/assets/img/checkmark.png" alt="checkmark" style="vertical-align: middle;" /> Your email has been added!
									</span>
									<span id="invalid_email_added_mssg" style="font-size: 12px; background-color: #f3e8e8; display: none;">
										<img src="/assets/img/errormark.png" alt="errormark" style="vertical-align: middle;" /> Oops! <span class="message"></span>
									</span>									
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</form>
		</div>
	</div>
</div><!-- end wrapper_signup -->
<?php

//close the database connection
mysql_close($db);
?>