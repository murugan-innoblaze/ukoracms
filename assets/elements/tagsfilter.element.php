<?php if(false !== ($tags = mysql_query_on_key(" SELECT * FROM dzpro_tags LEFT JOIN dzpro_tag_to_page USING ( dzpro_tag_id ) LEFT JOIN dzpro_shop_item_to_tags USING ( dzpro_tag_id ) LEFT JOIN dzpro_shop_items USING ( dzpro_shop_item_id ) WHERE dzpro_shop_item_name IS NOT NULL AND dzpro_page_id = '" . mysql_real_escape_string($this->current_page['dzpro_page_id']) . "' ORDER BY dzpro_tag_orderfield ", 'dzpro_tag_id'))){ ?>
<div class="bucket_left">
	<h2>Show</h2>
	<ul style="list-style: none;">
<?php foreach($tags as $tag_id => $tag){ $selected = ((isset($this->filter_tags) and is_array($this->filter_tags) and in_array($tag_id, $this->filter_tags))) ? true : false; ?>
		<li>
			<input type="checkbox" name="tags[]" value="<?=(int)$tag_id?>" <?php if($selected or !have($this->filter_tags)){ ?>checked="checked"<?php } ?> onclick="javascript: $.blockUI(); window.location='<?=($selected) ? addToGetString(null, null, array('tags[]', 'start'), (int)$tag_id) : addToGetString('tags[]', (int)$tag_id, 'start')?>';" /> 
			<a href="<?=($selected) ? addToGetString(null, null, array('tags[]', 'start'), (int)$tag_id) : addToGetString('tags[]', (int)$tag_id, 'start')?>" title="<?=prepareTag($tag['dzpro_tag_name'])?>" onclick="javascript:$.blockUI();">		
				<?=prepareStringHtml(ucwords($tag['dzpro_tag_name']))?>
			</a>
		</li>
<?php } ?>
	</ul>
</div>
<?php } ?>