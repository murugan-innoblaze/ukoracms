<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//authenticate session
$Auth = new Auth($db);

//try form class
$Form = new Form($db, 'dzpro_subscribers', array('states' => $states_list));

//show form tools
$Form->setFormTools(array('export'));

//$Form->showForeignTablesWithPrimaryKey();

//whitelist foreign table with primary key .. input array - get them with $Form->showForeignTablesWithPrimaryKey();
//$Form->whitelistForeignTables(array('dzpro_comments', 'dzpro_user_meta'));
?>
<!DOCTYPE html> 
<html lang="en-us"> 
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
		<?php $Form->buildHeadBlock(); ?>
	</head>
	<body>
		<div id="wrapper">
			<table id="outer_table" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td id="left_column">
							<?php require_once RELATIVE_ASSETS_PATH . '/elements/sections.element.php'; ?>
						</td><!-- end left_column -->
						<td id="right_column">
							<?php require_once RELATIVE_ASSETS_PATH . '/elements/subsections.element.php'; ?>
							<?php $show_form = $Form->showEventBlock(); ?>
							<table id="inner_content_table" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td id="inner_content_left">
											<?php $Form->buildFromToolbar(); ?>
											<?php $Form->buildFromListing(); ?>												
										</td><!-- end inner_content_left -->
										<td id="inner_content_right">
										<?php if(isset($_GET['action']) and ($_GET['action'] == 'new' or $_GET['action'] == 'edit' or $_GET['action'] == 'delete') and $show_form){ ?>
											<?php $Form->buildFormBlock(); ?>
										<?php }else{ //need the form block ?>
											<?php $Form->buildFormStats('days', 30, 'margin-top: 0px;'); ?>
											<?php $Form->buildFormStats('hours', 24); ?>
										<?php } ?>
										</td><!-- end inner_content_right -->
									</tr>
								</tbody>
							</table><!-- end inner_content_table -->
						</td><!-- end right_column -->
					</tr>
				</tbody>
			</table><!-- end outer_table -->
		</div><!-- end wrapper -->
	</body>
</html>
<?php

//close the database connection
mysql_close($db);
?>