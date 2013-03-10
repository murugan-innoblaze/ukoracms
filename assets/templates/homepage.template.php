<?php
/* Template name: Homepage Template */

//security check
if(!defined('RELATIVE_ASSETS_PATH')){ include '../../page.php'; exit(0); }

//force secure connection
//forceSecureConnection();

//handle visitor
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);
$this->Coupon = new Coupon($this->db);

//Save template views
addToIntelligenceStack('template view', 'homepage');

//Start page cache
$this->PageCache = new PageCache($this->db, '-1 hour');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en-us">
	<head><?=self::loadPageElements('head block')?></head>
	<body>
		<?=self::loadPageElements('body top')?>
		<div class="container">
			<div class="hero-unit">
        		<div class="row">
	        		<div class="span6">
	        			<img src="/assets/layout/ukora-logo-large.png" alt="<?=SITE_NAME?>" />
	        		</div>
	        		<div class="span8">
		        		<p>Ukora is a developer friendly cms that offers you <strong>flexibility</strong> and <strong>power</strong> to develop <strong>without the constraint</strong> of a conventional cms.</p>
		        		<p><a class="btn primary large">Download</a></p>
	      			</div>
      			</div>
      		</div>
      		<p>Vestibulum id ligula porta felis euismod semper. Integer <a href="#" class="hottitle" title="Vestibulum id ligula porta felis euismod semper. Integer bla bla bla. Vestibulum id ligula porta felis euismod semper. Integer bla bla bla.">posuere</a> erat a ante venenatis dapibus posuere velit aliquet. Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit.</p>
			<?=self::loadPageElements('container top')?>			
			<?=self::loadPageContent('main content')?>
			<?=self::loadPageElements('container bottom')?>
		</div><!-- end container -->
		<?=self::loadPageElements('page bottom')?>
	</body>
</html>
<?php

//Save page output
$this->PageCache->savePageCache();

?>