<?php
/* Template name: Product Category Page Template */
/* Template constants: PRODUCTS_PER_PAGE,MAX_PAGES_DISPLAYED */

//security check
if(!defined('RELATIVE_ASSETS_PATH')){ include '../../page.php'; exit(0); }

//force secure connection
forceSecureConnection();

//handle visitor, user and coupons
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);
$this->Coupon = new Coupon($this->db);

//Save template views
addToIntelligenceStack('template view', 'category page');

//Start page cache
$this->PageCache = new PageCache($this->db, '-1 hours');

//catch undefined settings
if(!defined('PRODUCTS_PER_PAGE')){ define('PRODUCTS_PER_PAGE', 18); } if(!defined('MAX_PAGES_DISPLAYED')){ define('MAX_PAGES_DISPLAYED', 5); }

//start pagination
$Pagination = new Pagination($this->db, PRODUCTS_PER_PAGE, MAX_PAGES_DISPLAYED);

//get tags filter
$this->filter_tags = isset($_GET['tags']) ? $_GET['tags'] : null; if(have($this->filter_tags)){ foreach($this->filter_tags as $tag_key => $tag_value){ if(!is_numeric($tag_value)){ unset($this->filter_tags[$tag_key]); } } }

//build tags filter sql
$tags_sql = (isset($this->filter_tags) and have($this->filter_tags)) ? " AND dzpro_tag_id IN ( " . implode(', ', $this->filter_tags) . ") " : null;

//build price target sql
$price_sql = isset($_GET['priceTarget']) ? " AND ( dzpro_shop_item_price - " . (int)$_GET['priceTarget'] . " ) BETWEEN -30 AND 60 " : null;

//build order by
$order_sql = have($price_sql) ? " ABS( dzpro_shop_item_price - " . (int)$_GET['priceTarget'] . " ) ASC " : " dzpro_shop_item_hits DESC, dzpro_shop_item_orderfield ASC ";

//set query
$Pagination->setQuery(" SELECT * FROM dzpro_shop_items LEFT JOIN dzpro_shop_item_to_tags USING ( dzpro_shop_item_id ) LEFT JOIN dzpro_tag_to_page USING ( dzpro_tag_id ) WHERE dzpro_shop_item_active = 1 AND dzpro_tag_id > 0 AND dzpro_page_id = '" . mysql_real_escape_string($this->current_page['dzpro_page_id']) . "' " . $tags_sql . " " . $price_sql . " GROUP BY dzpro_shop_item_id ORDER BY " . $order_sql . " ");

//get the products
$products = $Pagination->getRecords(); 

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
											<?=self::loadPageContent('main content')?>																					
										</div>
										<?php $Pagination->printPaginationBlock(); ?>
										<?php if(have($products)){ ?>
										<table cellpadding="0" cellspacing="0" id="product_display_table" style="border-left: 1px solid #f5ebd4;">
											<tbody>
												<tr>
											<?php $count = 1; foreach($products as $product){ ?>
													<td style="width: 33.3%;">
														<div class="item_holder" style="border-bottom: 1px solid #f4ead3;<?php if($count%3 == 2 or $count%3 == 0){ ?>border-left: 1px solid #f4ead3;<?php } ?>">
															<div class="ctl"><!-- corner --></div>
															<div class="ctr"><!-- corner --></div>
															<div class="cbl"><!-- corner --></div>
															<div class="cbr"><!-- corner --></div>
															<h3 class="item_name">
																<a href="/item/<?=prepareStringForUrl($product['dzpro_shop_item_name'])?>-<?=convertNumber($product['dzpro_shop_item_id'])?>/" title="<?=prepareTag($product['dzpro_shop_item_name'])?>" target="_blank">
																	<?=prepareStringHtmlFlat($product['dzpro_shop_item_name'])?>
																</a>
															</h3>
															<div class="image_area_holder">
																<img src="<?=(is_file(DOCUMENT_ROOT . $product['dzpro_shop_item_thumb_image'])) ? $product['dzpro_shop_item_thumb_image'] : '/assets/layout/nocheeseimage.jpg'?>" alt="<?=prepareTag($product['dzpro_shop_item_name'])?>" class="product_image" />
															</div>
															<a href="/item/<?=prepareStringForUrl($product['dzpro_shop_item_name'])?>-<?=convertNumber($product['dzpro_shop_item_id'])?>/" title="<?=prepareTag($product['dzpro_shop_item_name'])?>" class="learn_more" onclick="javascript:openProductOverlay(<?=(int)$product['dzpro_shop_item_id']?>);return false;">
																<!-- block -->
															</a>
															<p class="item_description"><?=placeTagLinks(prepareStringHtml(limitString($product['dzpro_shop_item_description'], 94)), $this->tags)?></p>
															<div class="item_price">$<?=$product['dzpro_shop_item_price']?></div>
															<form action="/cart/" method="post" id="pform_<?=md5($product['dzpro_shop_item_id'])?>">
																<?php if($product['dzpro_shop_item_quantity'] - MIN_STOCK_LEVEL > 0){ ?>
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
														</div><!-- end item_holder -->
													</td>
												<?php if($count == sizeof($products) and $count%3 != 0){ for($t = 0; $t < (3 - $count%3); $t++){ ?><td style="width: 33.3%;"><!-- td --></td><?php } $count+=$t; } ?>
												<?php if($count%3 == 0){ ?></tr><?php } ?>
												<?php if($count < sizeof($products) and $count%3 == 0){ ?><tr><?php } ?>
											<?php $count++; } ?>
											</tbody>
										</table>
										<?php }else{ ?>
										<h1>No cheese here yet.</h1>
										<p>So, there isn't anything listed here yet. Please check back soon!</p>
										<?php } ?>
										<?php $Pagination->printPaginationBlock(); ?>
										<div class="full-content">
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
<?php

//Save page output
$this->PageCache->savePageCache();

?>