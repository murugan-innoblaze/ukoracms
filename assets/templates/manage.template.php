<?php
/* Template name: Manage Page Template */

//security check
if(!defined('RELATIVE_ASSETS_PATH')){ include '../../page.php'; exit(0); }

//don't break out iframe
define('DO_NOT_BREAK_OUT_IFRAME', true);

//force secure connection
forceSecureConnection();

//handle visitor
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);

//Save template views
addToIntelligenceStack('template view', 'manage page');

//get intended ui
$ui = isset($_GET['ui']) ? $_GET['ui'] : null;

//build the right form
switch($_GET['ui']){
	case 'addresses';
		$FormBuilder = new FormBuilder($this->db, 'dzpro_user_shipping_options', array(), array('dzpro_user_id' => getUserId()));
		$FormBuilder->showTotalCountInHeader(true);
	break;
	default:
		die('not allowed');
	break;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en-us">
	<head><?=self::loadPageElements('head block')?><?=$FormBuilder->buildHeadBlock()?></head>
	<body>
		<?php if(!activeUserSession()){ forceReloadAndStop(); } ?>
		<?=self::loadPageElements('above form')?>
		<?php $show_form = $FormBuilder->showEventBlock(); ?>
		<?php if(!(isset($_GET['action']) and ($_GET['action'] == 'new' or $_GET['action'] == 'edit' or $_GET['action'] == 'delete') and $show_form)){ ?>
		<?php $FormBuilder->buildFromToolbar(); ?>
		<?php $FormBuilder->buildFromListing(); ?>												
		<?php }else{ ?>
		<?php $FormBuilder->buildFormBlock(); ?>
		<?php } //need the form block ?>
		<?=self::loadPageElements('below form')?>	
	</body>
</html>
<?php

//page unload here instead of later
pageUnload(false, true);

?>