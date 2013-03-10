<?php
/* Template name: Full Width Template */

//security check
if(!defined('RELATIVE_ASSETS_PATH')){ include '../../page.php'; exit(0); }

//force secure connection
//forceSecureConnection();

//handle visitor, user and coupons
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);
$this->Coupon = new Coupon($this->db);

//Save template views
addToIntelligenceStack('template view', 'full width page');

//Start page cache
$this->PageCache = new PageCache($this->db, '-1 hours');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en-us">
	<head><?=self::loadPageElements('head block')?></head>
	<body>
		<?=self::loadPageElements('body top')?>
		<div class="container">
        	<?=self::loadPageElements('containter top')?>	
			<div class="content">
				<?=self::loadPageElements('content top')?>
				<?=self::loadPageContent('main content')?>
				<?=self::loadPageElements('content bottom')?>
      		</div>	
			<?=self::loadPageElements('container bottom')?>
		</div><!-- end container -->
		<?=self::loadPageElements('page bottom')?>
	</body>
</html>
<?php

//Save page output
$this->PageCache->savePageCache();

?>