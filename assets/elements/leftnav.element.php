<div class="bucket">
	<div class="head">
		Offerings
	</div><!-- end .head -->
	<?php $cruisesmenu = self::getMenu(5); if(isset($cruisesmenu) and !empty($cruisesmenu)){ ?>
	<ul>
	<?php $count = 1; foreach($cruisesmenu as $page_id => $page_array){ ?>
	<li><a href="<?=$page_array['path']?>" title="<?=prepareTag($page_array['page']['dzpro_page_title'])?>" <?php if($count == sizeof($cruisesmenu)){ ?> style="border-bottom: none;" <?php } ?>><?=prepareStringHtml($page_array['page']['dzpro_page_name'])?></a></li>
	<?php $count++; } ?>
	</ul>
	<?php } ?>
</div><!-- end .bucket -->