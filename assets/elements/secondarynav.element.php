<?php $submenu = array(); $mainmenu = self::getMenu(1); if(have($mainmenu)){ foreach($mainmenu as $main_id => $main_array){ if($main_array['active'] == 1){ $this_sub_name = $main_array['page']['dzpro_page_title']; $submenu = $main_array['subpages']; } } } if(have($submenu)){ ?>
<div class="bucket_left">
	<h2><?=prepareStringHtml($this_sub_name)?></h2>
	<ul>
		<?php $count = 1; foreach($submenu as $page_id => $page_array){ ?>
		<li><a href="<?=$page_array['path']?>" title="<?=prepareTag($page_array['page']['dzpro_page_title'])?>"><?php if($page_array['active'] == 1){ ?><strong><?=prepareStringHtml($page_array['page']['dzpro_page_name'])?></strong><?php }else{ ?><?=prepareStringHtml($page_array['page']['dzpro_page_name'])?><?php } ?></a>
		<?php if($page_array['active'] == 1 and have($page_array['subpages'])){  ?>
			<ul>
		<?php foreach($page_array['subpages'] as $sub_page_id => $sub_page_array){ ?>
				<li><a href="<?=$sub_page_array['path']?>" title="<?=prepareTag($sub_page_array['page']['dzpro_page_title'])?>"><?php if($sub_page_array['active'] == 1){ ?><strong><?=prepareStringHtml($sub_page_array['page']['dzpro_page_name'])?></strong><?php }else{ ?><?=prepareStringHtml($sub_page_array['page']['dzpro_page_name'])?><?php } ?></a></li>
		<?php } ?>
			</ul>
		<?php } ?>
		</li>
		<?php $count++; } ?>
	</ul>
</div>
<?php } ?>