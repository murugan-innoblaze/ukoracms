<?php
/* Template name: Account Order Template */

//security check
if(!defined('RELATIVE_ASSETS_PATH')){ include '../../page.php'; exit(0); }

//force secure connection
forceSecureConnection();

//handle visitor
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);

//Save template views
addToIntelligenceStack('template view', 'account order page');

//get the orders for this user
$user_orders = array(); 
$result = @mysql_query("
	SELECT 
		* 
	FROM 
		dzpro_orders
	LEFT JOIN
		dzpro_order_shipments ON dzpro_order_shipments.dzpro_order_id = dzpro_orders.dzpro_order_id
	LEFT JOIN 
		dzpro_order_shipment_labels ON dzpro_order_shipments.dzpro_order_shipment_id = dzpro_order_shipment_labels.dzpro_order_shipment_id AND dzpro_order_shipment_label_printed = 1
	LEFT JOIN 
		dzpro_order_items ON dzpro_order_items.dzpro_order_id = dzpro_orders.dzpro_order_id AND dzpro_order_items.dzpro_order_shipment_id = dzpro_order_shipments.dzpro_order_shipment_id 
	LEFT JOIN 
		dzpro_order_item_options USING ( dzpro_order_item_id ) 
	LEFT JOIN 
		dzpro_order_payments ON dzpro_order_payments.dzpro_order_id = dzpro_orders.dzpro_order_id 
	LEFT JOIN 
		dzpro_order_status_history ON dzpro_order_status_history.dzpro_order_id = dzpro_orders.dzpro_order_id 
	LEFT JOIN 
		dzpro_order_totals ON dzpro_order_totals.dzpro_order_id = dzpro_orders.dzpro_order_id 
	WHERE 
		dzpro_orders.dzpro_user_id = '" . getUserId() . "' 
	AND 
		dzpro_orders.dzpro_order_id = '" . mysql_real_escape_string(isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0) . "' 
	ORDER BY 	
		dzpro_order_shipments.dzpro_order_shipment_id ASC, 
		dzpro_order_shipment_labels.dzpro_order_shipment_label_date_added ASC
") or die(mysql_error());
if(mysql_num_rows($result) > 0){ 
	while($row = mysql_fetch_assoc($result)){ 
		if(have($row['dzpro_order_shipment_id'])){ $user_orders['shipments'][$row['dzpro_order_shipment_id']]['shipment'] = $row; }
		if(have($row['dzpro_order_item_id'])){ $user_orders['shipments'][$row['dzpro_order_shipment_id']]['items'][$row['dzpro_order_item_id']]['item'] = $row; }
		if(have($row['dzpro_order_item_option_id'])){ $user_orders['shipments'][$row['dzpro_order_shipment_id']]['items'][$row['dzpro_order_item_id']]['options'][$row['dzpro_order_item_option_id']] = $row; }
		if(have($row['dzpro_order_status_id'])){ $user_orders['shipments'][$row['dzpro_order_shipment_id']]['status'][$row['dzpro_order_status_id']] = $row; }
		if(have($row['dzpro_order_status_id'])){ $user_orders['status'][$row['dzpro_order_status_id']] = $row; }
		if(have($row['dzpro_order_payment_id'])){ $user_orders['payments'][$row['dzpro_order_payment_id']] = $row; }
		if(have($row['dzpro_order_shipment_label_id'])){ $user_orders['shipments'][$row['dzpro_order_shipment_id']]['label'] = $row; }
	} 
	mysql_free_result($result);
	$this->current_page['dzpro_page_name'] = 'Order #' . (int)$_GET['order_id'] . ' Details';
	$this->current_page['dzpro_page_title'] = 'Order #' . (int)$_GET['order_id'] . ' Details';
	$this->current_page['dzpro_page_description'] = 'Order #' . (int)$_GET['order_id'] . ' Details';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en-us">
	<head><?=self::loadPageElements('head block')?></head>
	<body>
		<?=self::loadPageElements('body top')?>
		<div id="wrapper">
			<?=self::loadPageElements('wrapper top')?>			
			<div class="regular_content_outer" style="margin-top: 12px;">
				<div class="regular_content_inner">
					<div class="regular_content">
						<?=self::loadPageElements('content top')?>
						<table cellpadding="0" cellspacing="0">
							<tbody>
								<tr>
									<td style="width: 240px; vertical-align: top;">
										<div class="full-content">
											<?=self::loadPageElements('left column')?>
										</div>
									</td>
									<td style="width: 790px; vertical-align: top;">
										<div class="full-content">
											<?=self::loadPageElements('right column top')?>
											<a href="/account/" title="Go back to my account">click to go back to your account</a>
											<?php if(!empty($user_orders['shipments'])){ ?>
											<?php foreach($user_orders['shipments'] as $shipment){ ?>
											<h2>Shipment to <?=$shipment['shipment']['dzpro_order_shipment_name']?></h2>
											<div style="padding: 0 0 0 5px;">Shipment reference #<?=$shipment['shipment']['dzpro_order_shipment_id']?></div>
											<div class="summary_address_holder">
												<table cellpadding="0" cellspacing="0">
													<tbody>
														<tr>
															<td style="width: 300px; vertical-align: top; padding-right: 30px;">
																<strong>Recipient:</strong><br />
																<?=prepareStringHtml($shipment['shipment']['dzpro_order_shipment_name'])?><br />
																<?=prepareStringHtml($shipment['shipment']['dzpro_order_shipment_address'])?><br />
																<?=prepareStringHtml($shipment['shipment']['dzpro_order_shipment_city'] . ', ' . $shipment['shipment']['dzpro_order_shipment_state'] . ' ' . $shipment['shipment']['dzpro_order_shipment_zip'])?>
															</td>
															<td style="width: 300px; vertical-align: top;">
																<strong>Status:</strong><br />
																<?php if(isset($shipment['label']) and have($shipment['label'])){ ?>
																shipped <a href="http://www.fedex.com/Tracking?clienttype=dotcom&initial=n&ascend_header=1&sum=n&cntry_code=us&language=english&tracknumber_list=<?=$shipment['label']['dzpro_order_shipment_label_tracking']?>" title="Track This Package" target="_blank"><?=$shipment['label']['dzpro_order_shipment_label_tracking']?></a><br />
																Expected arrival date:<br /> <?=date('l, F jS Y', strtotime($shipment['shipment']['dzpro_order_shipment_delivery_date']))?>
																<?php }else{ ?>
																Not yet shipped<br />
																Expecting to ship:<br /> <?=date('l, F jS Y', strtotime($shipment['shipment']['dzpro_order_shipment_shipping_date']))?>
																<?php } ?>
															</td>
														</tr>
													</tbody>
												</table>
											</div>
											<table cellspacing="0" cellpadding="0" class="order_summary_table">
												<thead>
													<th class="quantity">Quantity</th>
													<th class="name">Name</th>
													<th class="price">Price</th>
													<th class="total">Total</th>
												</thead>
												<tbody>
													<?php if(have($shipment['items'])){ foreach($shipment['items'] as $item){ ?>
													<tr>
														<td class="quantity"><?=(int)$item['item']['dzpro_order_item_quantity']?></td>
														<td class="name">
															<?=prepareStringHtml($item['item']['dzpro_order_item_name'])?>
															<span style="font-size: 11px;"><?php if(have($item['options'])){ foreach($item['options'] as $option){ echo '<br />+' . prepareStringHtml($option['dzpro_order_item_option_name'] . ' ($' . number_format($option['dzpro_order_item_option_amount'], 2) . ')'); } } ?></span>
														</td>
														<td class="price">
															<?php $item_price = $item['item']['dzpro_order_item_price']; if(have($item['options'])){ foreach($item['options'] as $option){ $item_price += $option['dzpro_order_item_option_amount']; } }?>
															<?=number_format($item_price, 2)?></td>
														<td class="total">
															<?=number_format($item_price * $item['item']['dzpro_order_item_quantity'], 2)?>
														</td>
													</tr>
													<?php } } ?>
												</tbody>
											</table>
											<?php if(isset($shipment['shipment']['dzpro_order_shipment_message']) and have($shipment['shipment']['dzpro_order_shipment_message'])){ ?>
											<div style="padding: 5px;">
												<strong>Message: </strong>
												<div style="padding: 15px; text-align: center; border: 3px dashed #fef9ec; font-size: 16px; width: 300px;">
													<?=$shipment['shipment']['dzpro_order_shipment_message']?>
												</div>
											</div>
											<?php } ?>
											<div style="height: 20px;"><!-- spacer --></div>
											<?php } ?>
											<a href="/account/" title="Go back to my account">click to go back to your account</a>
											<?php }elseif(activeUserSession()){ ?>
											<h2>Order Not Found</h2>
											<p>We could not find this order.</p>
											<?php }else{ ?>
											<h2>You are not logged in</h2>
											<p>You need to <a href="/connect/" title="Register/Connect or Login" class="connect">login</a> to see this order.</p>
											<?php } ?>											
											<?=self::loadPageContent('main content')?>
											<?=self::loadPageElements('right column bottom')?>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
						<?=self::loadPageElements('content bottom')?>
					</div><!-- end .regular_content -->
				</div><!-- end .regular_content_inner -->
			</div><!-- end .regular_content_outer -->
			<?=self::loadPageElements('wrapper bottom')?>
		</div><!-- end wrapper -->
		<?=self::loadPageElements('page bottom')?>
	</body>
</html>