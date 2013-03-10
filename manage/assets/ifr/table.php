<?php
//where are we
define('RELATIVE_ASSETS_PATH', '..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//authenticate session
$Auth = new Auth($db, true, false);

//get the stuff we need
$table_name = (isset($_GET['table_name']) and !empty($_GET['table_name'])) ? (string)$_GET['table_name'] : false;
$filter_key = (isset($_GET['filter_key']) and !empty($_GET['filter_key'])) ? (string)$_GET['filter_key'] : false;
$filter_value = (isset($_GET['filter_value']) and !empty($_GET['filter_value'])) ? (string)$_GET['filter_value'] : false;

//make sure we have the stuff
if($table_name === false or $filter_key === false or $filter_value === false){ die('[error] we are missing required table information'); }

//try form class
$Form = new Form($db, $table_name, array('states' => $states_list), array($filter_key => $filter_value));

//show total count
$Form->showTotalCountInHeader(true);

//set intent to iframe
$Form->prepareForIframe();

//don't show foreign tables
$Form->setShowForeignTables(false);

?>
<!DOCTYPE html> 
<html lang="en-us"> 
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
		<?php $Form->buildHeadBlock(); ?>
		<script type="text/javascript">
			function fitIframeToRightSize(){ $('#iframe_for_<?=$table_name?>', parent.document).css('height', $('body').height() + 'px'); }
			var resizeDelayIframe = 40; var resizeTimeOut = ''; $().ready(function(){ fitIframeToRightSize(); $(window).resize(function(){ clearTimeout(resizeTimeOut); resizeTimeOut = setTimeout(fitIframeToRightSize, resizeDelayIframe); }); });
		</script>	
	</head>
	<body>
		<?php $show_form = $Form->showEventBlock(); ?>
		<?php if(false === (isset($_GET['action']) and ($_GET['action'] == 'new' or $_GET['action'] == 'edit' or $_GET['action'] == 'delete') and $show_form)){ ?>
		<?php $Form->buildFromToolbar(); ?>
		<?php $Form->buildFromListing(); ?>												
		<?php }else{ ?>
		<?php $Form->buildFormBlock(); ?>
		<?php } //need the form block ?>
	</body>
</html>
<?php

//close the database connection
mysql_close($db);
?>