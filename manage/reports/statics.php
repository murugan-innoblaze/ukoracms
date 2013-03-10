<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//authenticate session
$Auth = new Auth($db);

//try Statics class (database connection, table name, parameters, sticky fields);
$Statics = new Statics($db, 'dzpro_statics');

//dont show delete ui
$Statics->dontAllowDelete();

?>
<!DOCTYPE html> 
<html lang="en-us">
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
		<?php $Statics->buildHeadBlock(); ?>
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
							<?php $show_Statics = $Statics->showEventBlock(); ?>
							<table id="inner_content_table" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td id="inner_content_left">
											<?php if($Statics->isSuperUser()){ $Statics->buildFromToolbar(); } ?>
											<?php $Statics->buildFromListing(); ?>												
										</td><!-- end inner_content_left -->
										<?php if(isset($_GET['action']) and ($_GET['action'] == 'new' or $_GET['action'] == 'edit' or $_GET['action'] == 'delete') and $show_Statics){ ?>
										<td id="inner_content_right">
											<?php $Statics->buildStaticsBlock(); ?>
										</td><!-- end inner_content_right -->	
										<?php } //need the Statics block ?>
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