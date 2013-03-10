<div id="top_navigation">
	<div id="top_navigation_inner">
		<script type="text/javascript" src="/assets/js/topNavigationCheese.js"></script>
		<div id="nav_holder">
			<ul class="left">
				<?php $top_nav = self::getMenu(1); if(!empty($top_nav)){ foreach($top_nav as $page_id => $page_array){ $selected = ($page_array['active'] == 1) ? ' class="active" ' : null; ?>
				<li>
					<a href="<?=$page_array['path']?>" title="<?=prepareTag($page_array['page']['dzpro_page_title'])?>" class="<?php if($page_array['active'] == 1){ ?>active<?php } ?>">
						<?=prepareStringHtml($page_array['page']['dzpro_page_name'])?>
						<span class="decal_left"><!-- decal --></span>
						<span class="decal_right"><!-- decal --></span>
					</a>
					<div class="sub_menu_holer">
						<?php if(have($page_array['subpages'])){ ?>
						<ul>
							<?php foreach($page_array['subpages'] as $sub_page_id => $sub_page_array){ ?>
							<li>
								<a href="<?=$sub_page_array['path']?>" title="<?=prepareTag($sub_page_array['page']['dzpro_page_title'])?>" class="<?php if($sub_page_array['active'] == 1){ ?>active<?php } ?>">
									<?=prepareStringHtml($sub_page_array['page']['dzpro_page_name'])?>
								</a>
							</li>						
							<?php } ?>
						</ul>
						<?php } ?>
					</div>
				</li>
				<?php } } ?>			
			</ul>
			<ul class="right">
				<?php $top_nav_right = self::getMenu(5); if(!empty($top_nav_right)){ foreach($top_nav_right as $page_id => $page_array){ $selected = ($page_array['active'] == 1) ? ' class="active" ' : null; ?>
				<li>
					<a href="<?=$page_array['path']?>" title="<?=prepareTag($page_array['page']['dzpro_page_title'])?>" class="<?php if($page_array['active'] == 1){ ?>active<?php } ?>">
						<?=prepareStringHtml($page_array['page']['dzpro_page_name'])?>
						<span class="decal_left"><!-- decal --></span>
						<span class="decal_right"><!-- decal --></span>
					</a>
					<div class="sub_menu_holer">
						<?php if(have($page_array['subpages'])){ ?>
						<ul>
							<?php foreach($page_array['subpages'] as $sub_page_id => $sub_page_array){ ?>
							<li>
								<a href="<?=$sub_page_array['path']?>" title="<?=prepareTag($sub_page_array['page']['dzpro_page_title'])?>" class="<?php if($sub_page_array['active'] == 1){ ?>active<?php } ?>">
									<?=prepareStringHtml($sub_page_array['page']['dzpro_page_name'])?>
								</a>
							</li>						
							<?php } ?>
						</ul>
						<?php } ?>
					</div>
				</li>
				<?php } } ?>			
			</ul>
		</div>
		<div id="sub_nav_holder">
			<div id="default_links_holder">
			<?php if(have($top_nav)){ foreach($top_nav as $page_id => $page_array){ if($page_array['active'] == 1 and have($page_array['subpages'])){ ?>
			<ul>
				<?php foreach($page_array['subpages'] as $page_id => $page_array){ ?>
				<li>
					<a href="<?=$page_array['path']?>" title="<?=prepareTag($page_array['page']['dzpro_page_title'])?>" class="<?php if($page_array['active'] == 1){ ?>active<?php } ?>">
						<?=prepareStringHtml($page_array['page']['dzpro_page_name'])?>
					</a>
				</li>
				<?php } ?>
			</ul>	
			<?php } } } ?>
			</div>
			<div id="hover_links_holder"><!-- links load here --></div>
		</div>
	</div>
</div>