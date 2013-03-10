<?php 
/* Template name: Contact Page Template */

//security check
if(!defined('RELATIVE_ASSETS_PATH')){ include '../../page.php'; exit(0); }

//force secure connection
//forceSecureConnection();

//handle visitor
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);

//Save template views
addToIntelligenceStack('template view', 'contact form');

//Start page cache
$this->PageCache = new PageCache($this->db, '-1 hours');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en-us">
	<head><?=self::loadPageElements('head block')?></head>
	<body>
		<?=self::loadPageElements('body top')?>
		<div class="container">
			<div class="content">
        		<?=self::loadPageElements('containter top')?>
        		<div class="row">
          			<div class="span10">
						<?=self::loadPageElements('left column top')?>			
						<?=self::loadPageContent('main content')?>
						<?php $PageForm = new PageForm($this->db); $PageForm->buildForm(); ?>
						<?=self::loadPageElements('left column bottom')?>
					</div>
          			<div class="span4">
            			<?=self::loadPageElements('right column')?>
          			</div>
        		</div>
      		</div>	
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