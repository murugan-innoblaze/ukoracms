<?php

//where are we
define('RELATIVE_ASSETS_PATH', '..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//get the product
$product = mysql_query_flat(" SELECT * FROM dzpro_shop_items LEFT JOIN dzpro_shop_item_to_option USING ( dzpro_shop_item_id ) LEFT JOIN dzpro_shop_item_options USING ( dzpro_shop_item_option_id ) WHERE dzpro_shop_item_id = '" . mysql_real_escape_string((isset($_GET['product_id'])) ? (int)$_GET['product_id'] : 0) . "' ");

//exit if no product
if(!isset($product[0]['dzpro_shop_item_name'])){ exit(0); }else{ $product = $product[0]; }

//get the tags
$tags = mysql_query_flat(" SELECT * FROM dzpro_tags ");

//get rating
$Rating = new Rating($db, 'product-' . $product['dzpro_shop_item_id'], 12);

?>
<div id="product_overlay_wrapper">
	<table cellpadding="0" cellspacing="0" id="layout_overlay_table">
		<tbody>
			<tr>
				<td style="width: 500px;">
					<img src="<?=(is_file(DOCUMENT_ROOT . $product['dzpro_shop_item_image'])) ? $product['dzpro_shop_item_image'] : '/assets/layout/nocheeseimage.jpg'?>" alt="<?=prepareTag($product['dzpro_shop_item_name'])?>" style="max-width:500px; max-height:375px;" />
					<div style="height: 10px;"><!-- spacer --></div>
					<div class="horizontal-line" style="background-position: center top;"><!-- line --></div>
					<div id="cart_ui_holder">
						<form action="/cart/" method="post" id="pform_overlay_<?=md5($product['dzpro_shop_item_id'])?>">
							<?php if($product['dzpro_shop_item_quantity'] - MIN_STOCK_LEVEL > 0 and $product['dzpro_shop_item_active'] == 1){ ?>
							<?php if(activeUserSession()){ ?>
							<?php $addresses = getUserAddresses(); if(!have($addresses) or sizeof($addresses) == 1){ ?>
							<?php }else{ ?>						
							<?php foreach($addresses as $address_id => $address){ $addresses[$address_id] = ($address_id > 0) ? 'ship to ' . $address['dzpro_user_shipping_option_name'] . ' (' . $address['dzpro_user_shipping_option_address'] . ', ' . $address['dzpro_user_shipping_option_city'] . ')' : $address; } printSelectBox($addresses, 'address', 0, array('class' => 'addresses')); } ?>
							<?php } ?>
							<select name="quantity" class="quantity_select">
								<?php for($a = 1; $a <= ((MAX_SELECTOR_RANGE > $product['dzpro_shop_item_quantity'] - MIN_STOCK_LEVEL) ? $product['dzpro_shop_item_quantity'] - MIN_STOCK_LEVEL : MAX_SELECTOR_RANGE); $a++){ ?>
								<option value="<?=(int)$a?>"><?=(int)$a?></option>
								<?php } ?>
							</select>
							<input type="hidden" name="item_id" value="<?=(int)$product['dzpro_shop_item_id']?>" />
							<input type="submit" class="add_to_cart_button" value="&nbsp;" onclick="javascript:addToCart('pform_overlay_<?=md5($product['dzpro_shop_item_id'])?>');return false;" />
							<?php }else{ ?>
							<input type="submit" class="out_of_stock_button" value="&nbsp;" onclick="javascript:alert('This item is out of stock, please check back soon!');return false;" />
							<?php } ?>
						</form>
						<div class="item_price">$<?=$product['dzpro_shop_item_price']?></div>
						<div class="item_pid">item: <strong>#<?=$product['dzpro_shop_item_pid']?></strong></div>
						<div class="item_weight">weight: <strong><?=number_format($product['dzpro_shop_item_weight'], 2)?>lbs</strong></div>
					</div>	
				</td>
				<td style="padding-left: 20px; width: 300px;">
					<h1><?=prepareStringHtml($product['dzpro_shop_item_name'])?></h1>
					<p><?php $Rating->printRatingBlock(); ?></p>
					<p style="padding: 5px 0;"><?=placeTagLinks(limitString($product['dzpro_shop_item_description'], 400), $tags)?> <a href="/item/<?=prepareStringForUrl($product['dzpro_shop_item_name'])?>-<?=convertNumber($product['dzpro_shop_item_id'])?>/" title="<?=prepareTag($product['dzpro_shop_item_name'])?>" target="_blank">read more</a></p>			
					<?php if($product['dzpro_shop_item_age_percentage'] > 0){ ?><p><strong>Age</strong><br /><?php printStatusBar($product['dzpro_shop_item_age_percentage'], 100, array('young', 'medium', 'aged', 'old')); ?></p><?php } ?>
					<?php if($product['dzpro_shop_item_texture_percentage'] > 0){ ?><p><strong>Texture</strong><br /><?php printStatusBar($product['dzpro_shop_item_texture_percentage'], 100, array('soft', 'medium', 'hard', 'brittle')); ?></p><?php } ?>
					<?php if($product['dzpro_shop_item_flavor_percentage'] > 0){ ?><p><strong>Flavor</strong><br /><?php printStatusBar($product['dzpro_shop_item_flavor_percentage'], 100, array('mild', 'medium', 'strong', 'pungeant')); ?></p><?php } ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>