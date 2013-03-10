<?php

//ini_set('display_errors', 1);

//where are we
define('RELATIVE_ASSETS_PATH', '../assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//authenticate session
$Auth = new Auth($db);

//keep record on page after adding new one
define('STICK_ON_RECORD_AFTER_INSERT', true);

//try form class
$Project = new Project($db, 'dzpro_projects', array('states' => $states_list));

//$Project->showForeignTablesWithPrimaryKey();
//whitelist foreign table with primary key .. input array - get them with $Project->showForeignTablesWithPrimaryKey();
//$Project->whitelistForeignTables(array('dzpro_project_todos'));
?>
<!DOCTYPE html> 
<html lang="en-us"> 
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
		<?php $Project->buildHeadBlock(); ?>
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
							<?php $show_form = $Project->showEventBlock(); ?>
							<table id="inner_content_table" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td id="inner_content_left">
											<div id="bucket">
												<?php $Project->buildFromToolbar(); ?>
												<?php $Project->buildFromListing(); ?>												
											</div><!-- .bucket -->
										</td><!-- end inner_content_left -->
										<td id="inner_content_right">
											<?php 	
												if(isset($_GET['action']) and ($_GET['action'] == 'new' or $_GET['action'] == 'edit' or $_GET['action'] == 'delete') and $show_form){ 
											 		$Project->buildFormBlock(); 
											 	}else{
													$Project->buildFormStats('days', 30, 'margin-top: 0px', ' SELECT SUM((UNIX_TIMESTAMP(dzpro_project_todo_log_end) - UNIX_TIMESTAMP(dzpro_project_todo_log_start)) / 3600) AS hits, dzpro_project_name AS label FROM dzpro_project_todo_log LEFT JOIN dzpro_project_todos USING( dzpro_project_todo_id ) LEFT JOIN dzpro_projects USING ( dzpro_project_id ) |||WHERE-STATEMENTS||| GROUP BY dzpro_project_name ', 'dzpro_project_todo_log_date_added');
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