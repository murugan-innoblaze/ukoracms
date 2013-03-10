<?php
/* Template name: Cart Page Template */

//security check
if(!defined('RELATIVE_ASSETS_PATH')){ include '../../page.php'; exit(0); }

//force secure connection
forceSecureConnection();

//handle visitor, user and coupons
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);
$this->Coupon = new Coupon($this->db);

//Save template views
addToIntelligenceStack('template view', 'regular page');

//Start page cart
$Cart = new Cart($this->db);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en-us">
	<head><?=self::loadPageElements('head block')?></head>
	<body>
		<?=self::loadPageElements('body top')?>
		<div id="wrapper">
			<?=self::loadPageElements('wrapper top')?>			
			<div class="regular_content_outer" style="margin-top: 12px;">
				<div class="regular_content_inner">
					<div class="regular_content">
						<?=self::loadPageElements('content top')?>
						<div class="full-content">
							<?=self::loadPageElements('column top')?>
							<?php $Cart->printTheCart(); ?>
							<?=self::loadPageContent('main content')?>
							<?=self::loadPageElements('column bottom')?>
						</div>
						<?=self::loadPageElements('content bottom')?>
					</div><!-- end .regular_content -->
				</div><!-- end .regular_content_inner -->
			</div><!-- end .regular_content_outer -->
			<?=self::loadPageElements('wrapper bottom')?>
		</div><!-- end wrapper -->
		<?=self::loadPageElements('page bottom')?>
	</body>
</html>