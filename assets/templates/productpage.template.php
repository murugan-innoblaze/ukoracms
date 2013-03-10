<?php
/* Template name: Product Page Template */

//security check
if(!defined('RELATIVE_ASSETS_PATH')){ include '../../page.php'; exit(0); }

//force secure connection
forceSecureConnection();

//handle visitor, user and coupons
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);
$this->Coupon = new Coupon($this->db);

//Save template views
addToIntelligenceStack('template view', 'product page');

//Start page cache
$this->PageCache = new PageCache($this->db, '-1 hours'); 

//get product
$product = mysql_query_flat(" SELECT * FROM dzpro_shop_items WHERE dzpro_shop_item_id = '" . mysql_real_escape_string((isset($_GET['item_id'])) ? convertNumber($_GET['item_id'], true) : 0) . "' LIMIT 1 "); if(have($product[0])){ $product = $product[0]; $this->current_page['dzpro_page_title'] = $product['dzpro_shop_item_name']; $this->current_page['dzpro_page_description'] = $product['dzpro_shop_item_description']; $this->current_page['dzpro_page_keywords'] = str_replace(' ', ', ', strtolower($product['dzpro_shop_item_name'])); }

//get Rating
$Rating = new Rating($db, 'product-' . $product['dzpro_shop_item_id'], 12)

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
						<div class="full-content">
							<?=self::loadPageElements('content top')?>
							<div id="product_overlay_wrapper" style="width: 900px !important;">
								<table cellpadding="0" cellspacing="0" id="layout_overlay_table" style="width: 900px;">
									<tbody>
										<tr>
											<td style="width: 500px;">
												<script type="text/javascript" src="/assets/js/shopCart.js"></script>
												<img src="<?=$product['dzpro_shop_item_image']?>" alt="<?=prepareTag($product['dzpro_shop_item_name'])?>" style="max-width:500px; max-height:375px;" />
												<div style="height: 10px;"><!-- spacer --></div>
												<div class="horizontal-line" style="background-position: center top;"><!-- line --></div>
												<div id="cart_ui_holder">
													<form action="/cart/" method="post" id="pform_<?=md5($product['dzpro_shop_item_id'])?>">
														<?php if($product['dzpro_shop_item_quantity'] - MIN_STOCK_LEVEL > 0 and $product['dzpro_shop_item_active'] == 1){ ?>
														<?php if(activeUserSession()){ ?>
														<?php $addresses = getUserAddresses(); if(!have($addresses) or sizeof($addresses) == 1){ ?>
														<a href="/my/addresses/?action=new" title="Add a shipping address" class="fancybox_iframe address_link">please add a shipping address</a>	
														<?php }else{ ?>
														<a href="/my/addresses/?action=new" title="Add/Edit Addresses" class="fancybox_iframe address_link">add/edit</a>														
														<?php foreach($addresses as $address_id => $address){ $addresses[$address_id] = ($address_id > 0) ? 'ship to ' . $address['dzpro_user_shipping_option_name'] . ' (' . $address['dzpro_user_shipping_option_address'] . ', ' . $address['dzpro_user_shipping_option_city'] . ')' : $address; } printSelectBox($addresses, 'address', 0, array('class' => 'addresses')); } ?>
														<?php }else{ ?>
														<a href="/connect/" title="Register/Connect or Login to pick a delivery address" class="connect address_link">login to pick delivery address</a>
														<?php } ?>
														<select name="quantity" class="quantity_select">
															<?php for($a = 1; $a <= ((MAX_SELECTOR_RANGE > $product['dzpro_shop_item_quantity'] - MIN_STOCK_LEVEL) ? $product['dzpro_shop_item_quantity'] - MIN_STOCK_LEVEL : MAX_SELECTOR_RANGE); $a++){ ?>
															<option value="<?=(int)$a?>"><?=(int)$a?></option>
															<?php } ?>
														</select>
														<input type="hidden" name="item_id" value="<?=(int)$product['dzpro_shop_item_id']?>" />
														<input type="submit" class="add_to_cart_button" value="&nbsp;" onclick="javascript:addToCart('pform_<?=md5($product['dzpro_shop_item_id'])?>');return false;" />
														<?php }else{ ?>
														<input type="submit" class="out_of_stock_button" value="&nbsp;" onclick="javascript:alert('This item is out of stock, please check back soon!');return false;" />
														<?php } ?>
													</form>
													<div class="item_price">$<?=$product['dzpro_shop_item_price']?></div>
													<div class="item_pid">item: <strong>#<?=$product['dzpro_shop_item_pid']?></strong></div>
													<div class="item_weight">weight: <strong><?=number_format($product['dzpro_shop_item_weight'], 2)?>lbs</strong></div>													
												</div>
											</td>
											<td style="padding-left: 20px; width: 380px;">
												<p><?php $Rating->printRatingBlock(); ?></p>
												<?php if(have($product['dzpro_shop_item_description_html']) and strlen($product['dzpro_shop_item_description_html']) > 30){ ?>
												<?=placeTagLinks($product['dzpro_shop_item_description_html'], $this->tags)?>
												<?php }else{ ?>
												<p><?=placeTagLinks($product['dzpro_shop_item_description'], $this->tags)?></p>
												<?php } ?>
												<?php if(have($product['dzpro_shop_item_creamery']) and strlen($product['dzpro_shop_item_creamery']) > 30){ ?>
												<p>
													<strong>Creamery:</strong><br />
													<?=prepareStringHtmlFlat($product['dzpro_shop_item_creamery'])?>
												</p>
												<?php } ?>
												<?php if($product['dzpro_shop_item_age_percentage'] > 0){ ?><div><strong>Age</strong><br /><?php printStatusBar($product['dzpro_shop_item_age_percentage'], 100, array('young', 'medium', 'aged', 'old')); ?></div><?php } ?>
												<?php if($product['dzpro_shop_item_texture_percentage'] > 0){ ?><div><strong>Texture</strong><br /><?php printStatusBar($product['dzpro_shop_item_texture_percentage'], 100, array('soft', 'medium', 'hard', 'brittle')); ?></div><?php } ?>
												<?php if($product['dzpro_shop_item_flavor_percentage'] > 0){ ?><div><strong>Flavor</strong><br /><?php printStatusBar($product['dzpro_shop_item_flavor_percentage'], 100, array('mild', 'medium', 'strong', 'pungeant')); ?></div><?php } ?>
												<?php if(have($product['dzpro_shop_item_pairings']) and strlen($product['dzpro_shop_item_pairings']) > 30){ ?>
												<p>
													<strong>Suggested Pairings:</strong><br />
													<?=prepareStringHtmlFlat($product['dzpro_shop_item_pairings'])?>
												</p>
												<?php } ?>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
							<?=self::loadPageElements('content bottom')?>
						</div>
					</div><!-- end .regular_content -->
				</div><!-- end .regular_content_inner -->
			</div><!-- end .regular_content_outer -->
			<?=self::loadPageElements('wrapper bottom')?>
		</div><!-- end wrapper -->
		<?=self::loadPageElements('page bottom')?>
	</body>
</html>
<?php

//Save page output
$this->PageCache->savePageCache();

?>