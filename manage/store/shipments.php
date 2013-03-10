<?php

//where are we
define('RELATIVE_ASSETS_PATH', '../assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//do not allow adding records
define('DO_NOT_ALLOW_ADDING_RECORDS', true);

//authenticate session
$Auth = new Auth($db);

//try form class
$Shipments = new Shipments($db, 'dzpro_order_shipments', array('states' => $states_list));

//show form tools
$Shipments->setFormTools(array('export'));

//whitelist foreign table with primary key .. input array - get them with $Shipments->showForeignTablesWithPrimaryKey();
//$Shipments->whitelistForeignTables(array('dzpro_order_shipment_status'));

?>
<!DOCTYPE html> 
<html lang="en-us"> 
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
		<?php $Shipments->buildHeadBlock(); ?>
		<?php $Shipments->printCalendarHead(); ?>
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
							<?php $show_form = $Shipments->showEventBlock(); ?>
							<table id="inner_content_table" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td id="inner_content_left">
											<div id="bucket">
												<?php $Shipments->buildFromToolbar(); ?>
												<?php $Shipments->printCalendar(); ?>
												<?php $Shipments->printCalendarListing(); ?>											
											</div><!-- .bucket -->
										</td><!-- end inner_content_left -->
										<td id="inner_content_right">
										<?php 
											if(isset($_GET['action']) and ($_GET['action'] == 'edit' or $_GET['action'] == 'delete') and $show_form and isset($_GET['record_id'])){ 
												$Shipments->buildFormBlock(); 
										 		$Shipments->showShipmentItems();												
												$Shipments->showShippingLabel();
												$Shipments->shopShipmentStatusUI();
										 	}elseif(have($Shipments->show_records) and have($Shipments->filter_value)){
										 		$Shipments->showDailyStats();
										 		$Shipments->showPrintPackingSlips();									 		
										 	}elseif(!have($Shipments->show_records) and have($Shipments->filter_value)){
										 		$Shipments->noShipmentsBlock();
										 	}else{
												$Shipments->buildFormStats('days', 30, 'margin-top: 0px');
												$Shipments->buildFormStats('hours', 24);											 	
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