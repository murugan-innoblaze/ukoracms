<div id="special_featured">
	<div id="left_sf_box">
		<?php $popular_products = mysql_query_on_key(" SELECT * FROM dzpro_shop_items WHERE dzpro_shop_item_active = 1 AND dzpro_shop_item_quantity > 0 ORDER BY dzpro_shop_item_hits DESC LIMIT 8 ", 'dzpro_shop_item_id'); if(have($popular_products)){ ?>
		<script type="text/javascript">
			<!--
				var featureTimeoutvar_p_scroll_pointer = ''; var id_p_scroll_pointer = 1; var max_p_scroll_pointer = <?=sizeof($popular_products)?>; var slide_timeout_p_scroll_pointer = 4000; var fadeSpeed_p_scroll_pointer = 500;
				function gotoNextFeatureScrollPointer(){ $('.pproduct_listing_holder').hide(); id_p_scroll_pointer += 1; if(id_p_scroll_pointer > max_p_scroll_pointer){ id_p_scroll_pointer = 1; } $('#popular_product_display_' + id_p_scroll_pointer).fadeIn(fadeSpeed_p_scroll_pointer); featureTimeoutvar_p_scroll_pointer = setTimeout(gotoNextFeatureScrollPointer, slide_timeout_p_scroll_pointer); } $().ready(function(){ gotoNextFeatureScrollPointer(); $('#left_sf_box').hover(function(){ clearTimeout(featureTimeoutvar_p_scroll_pointer); }, function(){ featureTimeoutvar_p_scroll_pointer = setTimeout(gotoNextFeatureScrollPointer, slide_timeout_p_scroll_pointer); }); }); 
			//-->
		</script>		
		<?php $count = 1; foreach($popular_products as $product){ ?>
		<div class="pproduct_listing_holder <?php if($count == 1){ ?>first_loaded<?php } ?>" id="popular_product_display_<?=(int)$count?>">
			<div class="popular_product_listing">
				<h4 class="ptitle"><?=prepareStringHtml(compressString($product['dzpro_shop_item_name'], 28))?> - $<?=number_format($product['dzpro_shop_item_price'] , 2)?></h4>
				<p class="pdescription"><?=placeTagLinks(limitString($product['dzpro_shop_item_description'], 160), $this->tags)?></p>
				<form action="/cart/" method="post" style="position: absolute; bottom: 0px; left: 0px;">
					<input type="hidden" name="item_id" value="<?=(int)$product['dzpro_shop_item_id']?>" />
					<input type="hidden" name="quantity" value="1" />
					<input type="submit" class="add_to_cart_button" value="&nbsp;" />
				</form>
				<img src="<?=(is_file(DOCUMENT_ROOT . $product['dzpro_shop_item_image'])) ? $product['dzpro_shop_item_image'] : '/assets/layout/nocheeseimage.jpg'?>" alt="<?=prepareTag($product['dzpro_shop_item_name'])?>" class="pimage" />
			</div><!-- end .popular_product_listing -->
		</div><!-- end .pproduct_listing_holder -->
		<?php $count++; } } ?>
	</div><!-- end left_sf_box -->
	<div id="right_top_sf_box_title">
		Pick a category
	</div><!-- end right_top_sf_box_title -->
	<div id="right_top_sf_box">
		<?php if(isset($this->tags) and have($this->tags)){ ?>
		<p>
			<strong>By Category</strong><br />
			Pick the cheese category you are looking for below
		</p>
		<div style="height: 9px;"><!-- spacer --></div>
		<div style="padding: 3px 3px; background-color: #e5a32c; float: left;" class="radius_3_ie">
			<script type="text/javascript">
				<!--
					$().ready(function(){ $('#footer_tag_selector').change(function(){ var the_path = $(this).children('option:selected').val(); $.blockUI(); window.location = the_path; }); });
				//-->
			</script>
			<select name="category" id="footer_tag_selector">
				<option value="">-- pick a category --</option>
				<?php foreach($this->tags as $rtag){ ?>
				<option value="/tag/<?=prepareStringForUrl($rtag['dzpro_tag_name'])?>/"><?=prepareStringHtml(compressString($rtag['dzpro_tag_name'], 30))?></option>								
				<?php } ?>
			</select>
		</div>
		<?php } ?>	
	</div><!-- end right_top_sf_box -->
	<div id="right_bottom_sf_box_title">
		Search for cheese
	</div><!-- end right_bottom_sf_box_title -->
	<div id="right_bottom_sf_box">
		<p>
			<strong>Simply Search</strong><br />
			Just type in what you are looking for, and we'll take a look!
		</p>
		<div style="height: 7px;"><!-- spacer --></div>
		<div style="padding: 3px 3px; background-color: #3f2000; float: left;" class="radius_3_ie">
			<form action="/search/" method="get" onsubmit="javascript:$.blockUI();return true;">
				<table cellpadding="0" cellspacing="0" style="width: auto;">
					<tbody>
						<tr>
							<td style="vertical-align: middle;">
								<input type="text" name="q" class="radius_3 shadow_5_inner" value="<?php if(isset($this->search)){ echo prepareStringHtmlFlat($this->search); }?>" style="width: 130px; padding: 2px 4px; color: #6f3d09; font-family: inherit; border: 1px solid #6f3d09; font-size: 12px; background-color: white;" />
							</td>
							<td style="padding-left: 7px; vertical-align: middle;">
								<input type="submit" name="submitted_footer_search" value="go" class="radius_3" style="padding: 2px; color: #412100; font-family: inherit; border: 1px solid #6f3d09; font-size: 10px;" />
							</td>
						</tr>
					</tbody>
				</table>
			</form>
		</div>	
	</div><!-- end right_bottom_sf_box -->
</div><!-- end special_featured -->