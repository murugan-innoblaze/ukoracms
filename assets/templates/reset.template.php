<?php
/* Template name: Reset Password Page Template */

//security check
if(!defined('RELATIVE_ASSETS_PATH')){ include '../../page.php'; exit(0); }

//force secure connection
//forceSecureConnection();

//handle visitor
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);

//Save template views
addToIntelligenceStack('template view', 'login page');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en-us">
	<head><?=self::loadPageElements('head block')?></head>
	<body>
		<?=self::loadPageElements('body top')?>
		<div class="container">
    		<?=self::loadPageElements('containter top')?>
			<div class="content">
				<?php 
					if(isset($_POST['password']) and !empty($_POST['password']) and isset($_POST['password2']) and !empty($_POST['password2']) and $_POST['password'] == $_POST['password2'] and strlen($_POST['password']) > 4 and $this->User->validatePwResetRequest($_GET['pwresetkey'])){ 
						if($this->User->updateUserPassword($_GET['pwresetkey'], $_POST['password'])){
				?>
				<h1>Your password has been updated!</h1>
				<p>Please log in with your new password.</p>
				<?php
						}else{ //check insert new password
				?>
				<h1>We could not update your password</h1>
				<p>Are you sure it's not the same one you used before?</p>
				<?php	
						}
					}else{ //end check submission
						if($this->User->validatePwResetRequest($_GET['pwresetkey'])){ 
							addToIntelligenceStack('password reset', 'reset form'); 
				?>
				<form action="/reset/<?=$_GET['pwresetkey']?>/" method="post">
					<fieldset>
			          	<legend>Reset Password</legend>
						<div class="clearfix">
				  			<label for="email">Your new password</label>
			            	<div class="input">
			              		<input type="password" name="password" value="" />
			              		<span class="help-block">Enter your email address</span>
			            	</div>
			          	</div>
			          	<div class="clearfix">
				  			<label for="email">Repeat password</label>
			            	<div class="input">
			              		<input type="password" name="password2" value="" />
			              		<span class="help-block">Enter your email address</span>
			            	</div>
			          	</div>
			          	<div class="actions">
							<input value="Reset password" class="btn primary" type="submit" name="recover" />
						</div>			
					</fieldset>			
				</form>
				<?php }else{ ?>
				<h1>Reset Password - Invalid Link</h1>
				<p>The link you followed here is no longer valid.</p>
				<?php 
					}
				}
				?>
				<?=self::loadPageContent('main content')?>
				</div>	
			<?=self::loadPageElements('container bottom')?>
		</div><!-- end container -->
		<?=self::loadPageElements('page bottom')?>
	</body>
</html>
<?php

//page unload here instead of later
pageUnload(false, true);

?>