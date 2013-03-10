<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//authenticate session
$Auth = new Auth($db);

//try PageElement class
$PageElement = new PageElement($db, 'dzpro_page_elements', array('states' => $states_list));

//$PageElement->showForeignTablesWithPrimaryKey();

//whitelist foreign table with primary key .. input array - get them with $PageElement->showForeignTablesWithPrimaryKey();
//$PageElement->whitelistForeignTables(array('dzpro_page_elements'));
?>
<!DOCTYPE html> 
<html lang="en-us"> 
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
		<?php $PageElement->buildHeadBlock(); ?>
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
							<?php $show_form = $PageElement->showEventBlock(); ?>
							<table id="inner_content_table" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td id="inner_content_left">
											<div id="bucket">
												<?php $PageElement->buildFromToolbar(); ?>
												<?php $PageElement->buildFromListing(); ?>												
											</div><!-- .bucket -->
										</td><!-- end inner_content_left -->
										<?php if(isset($_GET['action']) and ($_GET['action'] == 'new' or $_GET['action'] == 'edit' or $_GET['action'] == 'delete') and $show_form){ ?>
										<td id="inner_content_right">
											<?php $PageElement->buildFormBlock(); ?>
										</td><!-- end inner_content_right -->	
										<?php } //need the PageElement block ?>
									</tr>
								</tbody>
							</table><!-- end inner_content_table -->
						</td><!-- end right_column -->
					</tr>
				</tbody>
			</table><!-- end outer_table -->
			<?php require_once RELATIVE_ASSETS_PATH . '/elements/footer.element.php'; ?>
		</div><!-- end wrapper -->
	</body>
</html>
<?php

//close the database connection
mysql_close($db);
?>