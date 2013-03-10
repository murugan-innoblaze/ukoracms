<?php
//where are we
define('RELATIVE_ASSETS_PATH', 'assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//authenticate session
$Auth = new Auth($db);

//dashboard
$Dashboard = new Dashboard($db);

//users
$Users = new Form($db, 'dzpro_users');

//payments
$Payments = new Form($db, 'dzpro_payments');

//payments
$Subscribers = new Form($db, 'dzpro_subscribers');

?>
<!DOCTYPE html> 
<html lang="en-us"> 
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
		<?php $Dashboard->buildDashboardHeadBlock(); ?>
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
							<table id="inner_content_table" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td id="inner_content_left">
											<?php $Dashboard->printAllNavItems(); ?>
										</td><!-- end inner_content_left -->
										<td id="inner_content_right">
											<?php $Dashboard->printDoMysqlBackupBlock(); ?>
											<?php $Dashboard->printRecentAdminActivity(); ?>
											<?php $Users->buildFormStats('days', 30); ?>
											<?php $Payments->buildFormStats('days', 30); ?>
											<?php $Subscribers->buildFormStats('days', 30); ?>
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