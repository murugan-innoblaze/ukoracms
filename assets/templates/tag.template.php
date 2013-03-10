<?php
/* Template name: Tag Page Template */

//security check
if(!defined('RELATIVE_ASSETS_PATH')){ include '../../page.php'; exit(0); }

//force secure connection
forceSecureConnection();

//handle visitor
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);

//Save template views
addToIntelligenceStack('template view', 'tag page');

//get the tag
$this->the_tag = array(); $tag = isset($_GET['tag']) ? str_replace('-', ' ', $_GET['tag']) : null; $Tags = new Pagination($this->db, 12, 6); $Tags->setQuery(" SELECT * FROM dzpro_tags LEFT JOIN dzpro_tag_to_page USING ( dzpro_tag_id ) LEFT JOIN dzpro_pages USING ( dzpro_page_id ) WHERE dzpro_tag_name REGEXP '" . mysql_real_escape_string(str_replace(' ', '[^a-z^A-Z^0-9]+', preg_replace('/[^a-z^0-9]+/i', ' ', trim($tag)))) . "' "); $records = $Tags->getRecords(); if(have($records)){ foreach($records as $record){ $this->the_tag['tag'] = $record; if(have($record['dzpro_page_id'])){ $this->the_tag['pages'][$record['dzpro_page_id']] = $record; } $this->current_page['dzpro_page_description'] = $record['dzpro_tag_description']; $this->current_page['dzpro_page_name'] = $record['dzpro_tag_name']; $this->current_page['dzpro_page_title'] = $record['dzpro_tag_title']; } } 

//start pagination
$Pagination = new Pagination($this->db, 15, 5, 'p_start'); $Pagination->setQuery(" SELECT * FROM dzpro_shop_items LEFT JOIN dzpro_shop_item_to_tags USING ( dzpro_shop_item_id ) LEFT JOIN dzpro_tags USING ( dzpro_tag_id ) WHERE dzpro_shop_item_active = 1 AND dzpro_tag_id = '" . mysql_real_escape_string($this->the_tag['tag']['dzpro_tag_id']) . "' AND dzpro_tag_id IS NOT NULL AND dzpro_tag_id != 0 ORDER BY dzpro_shop_item_hits DESC, dzpro_shop_item_orderfield ASC "); $products = $Pagination->getRecords(); 

//Start page cache
$this->PageCache = new PageCache($this->db, '-1 hours');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:og="http://ogp.me/ns#" xmlns:fb="http://www.facebook.com/2008/fbml">
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
											<p><?=placeTagLinks(prepareStringHtml($record['dzpro_tag_description']), $this->tags)?></p>
										</div>
										<?php if(have($products)){ ?>
										<div class="full-content">
											<h2>Products</h2>
										</div>
										<?php $Pagination->printPaginationBlock(); ?>
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
										<?php $Pagination->printPaginationBlock(); ?>
										<?php }else{ ?>
										<div class="full-content">
											<h2>No cheese here yet.</h2>
											<p>So, there isn't anything listed here yet. Please check back soon!</p>
										</div>
										<?php } ?>
										<?php if(have($this->the_tag)){ ?>
										<div class="full-content">
											<h2>Pages</h2>
										</div>
										<?php $Tags->printPaginationBlock(); ?>
										<div class="full-content">
											<ul class="tag_list">
												<?php $alternate_color = 'ffffff'; foreach($this->the_tag['pages'] as $page){ ?>
												<li <?php if(floor($count / 2) != ($count / 2)){ $alternate_color = 'fffdf9';  ?>style="background-color: #<?=$alternate_color?>;"<?php } ?> onclick="javascript:document.location='<?=prepareTag(self::getPagePathFromId($page['dzpro_page_id']))?>';return false;">
													<h3><?=prepareStringHtml($page['dzpro_page_title'])?></h3>
													<p><?=prepareStringHtmlFlat($page['dzpro_page_description'])?><br />
													<a href="<?=self::getPagePathFromId($page['dzpro_page_id'])?>" title="<?=prepareTag($page['dzpro_page_name'])?>"><?=prepareStringHtmlFlat($page['dzpro_page_name'])?> &raquo;</a></p>
													<div style="clear: both;"><!-- clear --></div>
												</li>
												<?php $count++; } ?>
											</ul>
										</div>
										<?php $Tags->printPaginationBlock(); ?>											
										<?php }else{ ?>
										<div class="full-content">	
											<h2>Oops! This tag could not be found.</h2>
											<p>We're not sure how you ended up here. Sorry about that...</p>
										</div>
										<?php } ?>											
										<?=self::loadPageElements('right column bottom')?>
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