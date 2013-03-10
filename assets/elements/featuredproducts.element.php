<?php $features_products = mysql_query_on_key(" SELECT * FROM dzpro_shop_items WHERE dzpro_shop_item_featured = 1 AND dzpro_shop_item_active = 1 AND dzpro_shop_item_quantity > 0 ORDER BY dzpro_shop_item_orderfield LIMIT 12 ", 'dzpro_shop_item_id'); if(have($features_products)){ ?>
<script type="text/javascript" src="/assets/js/slider.js"></script>
<div class="content_outer" style="margin-top: 0px;">
	<div class="content_inner">
		<div class="content_area">
			<div style="position: relative; zoom: 1; height: 10px;">
				<h3 style="position: absolute; top: -18px; left: 45px; letter-spacing: 2px; background-color: #dc9619; color: #ffffff; padding: 1px 6px; font-size: 13px; font-weight: normal; border-bottom-left-radius: 10px; -moz-border-radius-bottomleft: 10px; border-bottom-right-radius: 10px; -moz-border-radius-bottomright: 10px;-moz-border-radius: 10px;
border-radius: 10px;">Featured Products</h3>
			</div>
			<div style="height: 6px;"><!-- spacer --></div>
			<div style="padding: 0 40px;">
				<div class="slider_outer">
					<div class="left_arrow"><!-- left --></div>
					<div class="right_arrow"><!-- right --></div>
					<div class="slider_outer_holder">
						<table class="slider_item_table">
							<tbody>
								<tr>
									<?php foreach($features_products as $product){ ?>
									<td>
										<div class="slider_item">
											<h3 class="item_name">
												<a href="/item/<?=prepareStringForUrl($product['dzpro_shop_item_name'])?>-<?=convertNumber($product['dzpro_shop_item_id'])?>/" title="<?=prepareTag($product['dzpro_shop_item_name'])?>" target="_blank">
													<?=prepareStringHtmlFlat($product['dzpro_shop_item_name'])?>
												</a>
											</h3>
											<div class="image_area_holder">
												<img src="<?=(is_file(DOCUMENT_ROOT . $product['dzpro_shop_item_thumb_image'])) ? $product['dzpro_shop_item_thumb_image'] : '/assets/layout/nocheeseimage.jpg'?>" alt="<?=prepareTag($product['dzpro_shop_item_name'])?>" class="product_image" />
											</div>
											<a href="/item/<?=prepareStringForUrl($product['dzpro_shop_item_name'])?>-<?=convertNumber($product['dzpro_shop_item_id'])?>/" title="<?=prepareTag($product['dzpro_shop_item_name'])?>" class="learn_more" onclick="javascript:openProductOverlay(<?=(int)$product['dzpro_shop_item_id']?>);return false;"><!-- learn more overlay --></a>
											<div class="item_price">$<?=number_format($product['dzpro_shop_item_price'], 2)?></div>
										</div>
									</td>
									<?php } ?>
								</tr>
							</tbody>
						</table>
					</div><!-- end .slider_outer_holder -->
				</div><!-- end .slider_outer -->
			</div>
			<div style="height: 6px;"><!-- spacer --></div>
		</div>
	</div>
</div>
<?php } ?>