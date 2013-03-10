<div class="topbar" data-dropdown="dropdown">
	<div class="fill">
		<div class="container">
			<a class="brand" href="/" title="<?=SITE_NAME?>">
				<img src="/assets/layout/ukora-drop-small.png" alt="<?=SITE_NAME?>" style="vertical-align: middle;" /> 
				<?=SITE_NAME?>
			</a>			
			<ul class="nav">
				<?php $main_nav = self::getMenu(1); if(!empty($main_nav)){ foreach($main_nav as $page_id => $page_array){ ?>
				<li class="<?php if($page_array['active'] == 1){ ?>active<?php } ?>">
					<a href="<?=$page_array['path']?>" title="<?=prepareTag($page_array['page']['dzpro_page_title'])?>">
						<?=prepareStringHtml($page_array['page']['dzpro_page_name'])?>
					</a>
				</li>
				<?php } } ?>
			</ul>
			<ul class="secondary-nav">
				<li class="dropdown">
					<a href="#" class="dropdown-toggle">Community</a>
					<ul class="dropdown-menu">
						<?php $community_nav = self::getMenu(2); if(!empty($community_nav)){ foreach($community_nav as $page_id => $page_array){ ?>
						<li class="<?php if($page_array['active'] == 1){ ?>active<?php } ?>">
							<a href="<?=$page_array['path']?>" title="<?=prepareTag($page_array['page']['dzpro_page_title'])?>">
								<?=prepareStringHtml($page_array['page']['dzpro_page_name'])?>
							</a>
						</li>
						<?php } } ?>
                		<li class="divider"></li>
                		<li><a href="#">Another link</a></li>
              		</ul>
            	</li>
			</ul>
			<form action="/connect/" method="post" class="pull-right">
				<button class="btn success connect" type="submit">Connect</button>
			</form>
		</div>
	</div>
</div>
