<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//authenticate session
$Auth = new Auth($db);

//try form class
$Submissions = new Submissions($db, 'dzpro_submissions', array('states' => $states_list));

//blacklist identities
$Submissions->blackListAssociativeTables(array('dzpro_identities', 'dzpro_users'));

//show form tools
//$Submissions->setFormTools(array('export'));

//$Submissions->showForeignTablesWithPrimaryKey();

//whitelist foreign table with primary key .. input array - get them with $Submissions->showForeignTablesWithPrimaryKey();
$Submissions->whitelistForeignTables(array('dzpro_submission_values'));
?>
<!DOCTYPE html> 
<html lang="en-us"> 
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
		<?php $Submissions->buildHeadBlock(); ?>
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
							<?php $show_form = $Submissions->showEventBlock(); ?>
							<table id="inner_content_table" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td id="inner_content_left">
											<div id="bucket">
												<?php $Submissions->buildFromToolbar(); ?>
												<?php $Submissions->buildFromListing(); ?>												
											</div><!-- .bucket -->
										</td><!-- end inner_content_left -->
										<td id="inner_content_right">
											<?php 
												if(
													isset($_GET['action']) and 
													($_GET['action'] == 'new' or $_GET['action'] == 'edit' or $_GET['action'] == 'delete') and 
													$show_form
												){ 
													$Submissions->buildFormBlock(); 
												}else{ 
													$Submissions->buildSubmissionsUI();
													$Submissions->buildFormStats('days', 30, null, ' SELECT COUNT(*) AS hits, dzpro_submission_name AS label FROM dzpro_submissions |||WHERE-STATEMENTS||| GROUP BY dzpro_submission_name ');
													$Submissions->buildFormStats('days', 30, null, null);
													$Submissions->buildFormStats('hours', 24, null, null);
												} 
											?>
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