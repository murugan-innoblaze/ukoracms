<?php
/* Template name: Login Page Template */

//security check
if(!defined('RELATIVE_ASSETS_PATH')){ include '../../page.php'; exit(0); }

//force secure connection
//forceSecureConnection();

//handle visitor
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);

//Save template views
addToIntelligenceStack('template view', 'login page');

//redirect if already logged in
if(activeUserSession()){ header('Location: /account/'); exit(0); }

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en-us">
	<head><?=self::loadPageElements('head block')?></head>
	<body>
		<?=self::loadPageElements('body top')?>
		<div class="container">
			<div class="content">
        		<?=self::loadPageElements('containter top')?>
				<?php 
					if(isset($_SESSION['connect_capture']) or have($_SESSION['connect_failed_reason'])){ 
						$message = have($_SESSION['connect_failed_reason']) ? $_SESSION['connect_failed_reason'] : 'There was a problem with your submission'; 
				?>
				<div class="alert-message error">				
					<a class="close" href="#">×</a>
					<p><?=$message?></p>
				</div>
				<script type="text/javascript"> 
					<!--
						$().ready(function(){
							<?php foreach($_SESSION['connect_capture'] as $key => $value){ if(have($value)){ ?> 
							$('input[name=<?=$key?>]').val('<?=prepareTag($value)?>'); 
							<?php } } ?> 
						}); 
					//-->		
				</script>
				<?php } ?>
				<?php include DOCUMENT_ROOT . '/assets/ajax/signup.php'; ?>
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