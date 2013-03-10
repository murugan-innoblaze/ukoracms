<div class="bucket_left">
	<h2>Cheese Cloud</h2>
	<p>You might also be interested in these cheeses!</p>
	<p style="line-height: 200%; font-weight: bold; padding: 10px; margin-left: -5px; background-color: #faefdf;" class="radius_3">
		<?php $cloud = getCache('cheesecloudarray-sidebucket'); if(!have($cloud)){ $cloud = mysql_query_flat(" SELECT * FROM dzpro_suggest WHERE dzpro_suggest_weight > 1 ORDER BY dzpro_suggest_last_modified DESC LIMIT 20 "); } if(have($cloud)){ saveCache('cheesecloudarray-sidebucket', $cloud); foreach($cloud as $cloud_item){ ?>
		<a href="/s/<?=prepareStringForUrl($cloud_item['dzpro_suggest_string'])?>/" title="<?=prepareTag($cloud_item['dzpro_suggest_string'])?>" style="font-size: <?=rand(12, 20)?>px"><?=prepareStringHtml($cloud_item['dzpro_suggest_string'])?></a>&nbsp;&nbsp;&nbsp;
		<?php } } ?>
	</p>
</div>