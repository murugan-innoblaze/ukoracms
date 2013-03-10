<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//authenticate session
$Auth = new Auth($db);

//keep record on page after adding new one
define('STICK_ON_RECORD_AFTER_INSERT', true);

//try form class
$Invoices = new Invoices($db, 'dzpro_invoices');

//$Invoices->showForeignTablesWithPrimaryKey();
//whitelist foreign table with primary key .. input array - get them with $Invoices->showForeignTablesWithPrimaryKey();
$Invoices->whitelistForeignTables(array('dzpro_invoice_items'));
?>
<!DOCTYPE html> 
<html lang="en-us">
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
		<?php $Invoices->buildHeadBlock(); ?>
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
							<?php $show_form = $Invoices->showEventBlock(); ?>
							<table id="inner_content_table" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td id="inner_content_left">
											<div id="bucket">
												<?php $Invoices->buildFromToolbar(); ?>
												<?php $Invoices->buildFromListing(); ?>												
											</div><!-- .bucket -->
										</td><!-- end inner_content_left -->
										<?php if(isset($_GET['action']) and ($_GET['action'] == 'new' or $_GET['action'] == 'edit' or $_GET['action'] == 'delete') and $show_form){ ?>
										<td id="inner_content_right">
											<?php $Invoices->buildFormBlock(); ?>
										</td><!-- end inner_content_right -->	
										<?php } //need the form block ?>
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