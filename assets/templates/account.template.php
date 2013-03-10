<?php
/* Template name: Account Template */

//security check
if(!defined('RELATIVE_ASSETS_PATH')){ include '../../page.php'; exit(0); }

//force secure connection
forceSecureConnection();

//handle visitor
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);

//Save template views
addToIntelligenceStack('template view', 'account page');

//get the orders for this user
$user_orders = array(); $result = @mysql_query("SELECT * FROM dzpro_orders WHERE dzpro_user_id = '" . getUserId() . "' ORDER BY dzpro_order_date_added DESC"); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ if(have($row['dzpro_order_id'])){ foreach($row as $key => $value){ $user_orders[$row['dzpro_order_id']][$key] = $value; } } } mysql_free_result($result); }

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
											<?php if(!empty($user_orders)){ ?>
											<h2>Previous Orders</h2>
											<ul style="list-style: none;">
												<?php foreach($user_orders as $order_id => $order){ ?>
												<li>
													<p><strong>Order (#<?=$order['dzpro_order_id']?>)</strong><br />
													Date: <?=date('Y-m-d H:i:s', strtotime($order['dzpro_order_date_added']))?> <br /><a href="/account/order-<?=(int)$order['dzpro_order_id']?>/" title="Click for order details">click for order details</a></p>
												</li>
												<?php } ?>
											</ul>
											<?php }elseif(activeUserSession()){ ?>
											<h2>No Orders</h2>
											<p>You haven't placed any orders on our new website yet. <br /><a href="/cheese/" title="Cheese">Click here to order some cheese!</a></p>
											<?php }else{ ?>
											<h2>You are not logged in</h2>
											<p>You need to <a href="/connect/" title="Register/Connect or Login" class="connect">login</a> to see your previous orders.</p>
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