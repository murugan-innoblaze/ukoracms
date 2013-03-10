<?php $menu = self::getMenu(6); if(!empty($menu)){  ?>
<div class="bucket_left">
	<h2>Cheese Bar Events</h2>
	<ul>
		<?php $count = 1; foreach($menu as $page_id => $page_array){ $selected = ($page_array['active'] == 1) ? ' class="active" ' : null; ?>
		<li><a href="<?=$page_array['path']?>" title="<?=prepareTag($page_array['page']['dzpro_page_title'])?>" class="<?php if($page_array['active'] == 1){ ?>active<?php } ?>" <?php if($count == sizeof($menu)){ echo 'style="border-bottom: none;"'; } ?>><?=prepareStringHtml($page_array['page']['dzpro_page_name'])?></a></li>
		<?php $count++; } ?>			
	</ul>
</div>
<?php } ?>