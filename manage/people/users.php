<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//authenticate session
$Auth = new Auth($db);

//try form class
$Users = new Users($db, 'dzpro_users', array('states' => $states_list));

//blacklist identities
$Users->blackListAssociativeTables(array('dzpro_identities'));

//$Users->showForeignTablesWithPrimaryKey();

//whitelist foreign table with primary key .. input array - get them with $Users->showForeignTablesWithPrimaryKey();
$Users->whitelistForeignTables(array('dzpro_user_shipping_options'));
?>
<!DOCTYPE html> 
<html lang="en-us"> 
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
		<?php $Users->buildHeadBlock(); ?>
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
							<?php $show_form = $Users->showEventBlock(); ?>
							<table id="inner_content_table" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td id="inner_content_left">
											<?php $Users->buildFromToolbar(); ?>
											<?php $Users->buildFromListing(); ?>												
										</td><!-- end inner_content_left -->
										<td id="inner_content_right">
										<?php if(isset($_GET['action']) and ($_GET['action'] == 'new' or $_GET['action'] == 'edit' or $_GET['action'] == 'delete') and $show_form){ ?>
											<?php $Users->buildFormBlock(); ?>
										<?php }else{ //need the form block ?>
											<?php $Users->buildFormStats('days', 30, 'margin-top: 0px;'); ?>
											<?php $Users->buildFormStats('hours', 24); ?>
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