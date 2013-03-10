<?php

//where are we
define('RELATIVE_ASSETS_PATH', '../assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//authenticate session
$Auth = new Auth($db);

//try form class
$Hollidays = new Calendar($db, 'dzpro_calendar', array('states' => $states_list));

//set date and time fields
$Hollidays->setDateFieldName('dzpro_calendar_date');

//whitelist foreign table with primary key .. input array - get them with $Hollidays->showForeignTablesWithPrimaryKey();
//$Hollidays->whitelistForeignTables(array('dzpro_admins'));

?>
<!DOCTYPE html> 
<html lang="en-us"> 
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
		<?php $Hollidays->buildHeadBlock(); ?>
		<?php $Hollidays->printCalendarHead(); ?>
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
							<?php $show_form = $Hollidays->showEventBlock(); ?>
							<table id="inner_content_table" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td id="inner_content_left">
											<div id="bucket">
												<?php $Hollidays->buildFromToolbar(); ?>
												<?php $Hollidays->printCalendar(); ?>
												<?php $Hollidays->printCalendarListing(); ?>												
											</div><!-- .bucket -->
										</td><!-- end inner_content_left -->
										<td id="inner_content_right">
										<?php if(isset($_GET['action']) and ($_GET['action'] == 'new' or $_GET['action'] == 'edit' or $_GET['action'] == 'delete') and $show_form){ $Hollidays->buildFormBlock(); } //need the form block ?>
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