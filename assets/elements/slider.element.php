<?php $slides = mysql_query_on_key(" SELECT * FROM features WHERE feature_active = 1 ORDER BY feature_orderfield ASC ", 'feature_id'); if(have($slides) and getVisitorPageViews() < 10000){ ?>
<script type="text/javascript" src="/assets/js/main-callouts-slider.js"></script>
<div id="slider">
	<div id="slider_inner">
		<div id="slider_left_arrow"><!-- left arrow --></div>
		<div id="slider_right_arrow"><!-- right arrow --></div>
		<div id="slides_holder">
			<table cellpadding="0" cellspacing="0" id="slides_holder_table">
				<tr>
					<?php $count = 1; foreach($slides as $slide){ ?>
					<td>
						<div id="theslide_<?=(int)$count?>" class="the_slides <?=($count == 1) ? 'first_load' : null?>">
							<!-- the slide -->
							<a href="<?=$slide['feature_link']?>" title="<?=prepareTag($slide['feature_name'])?>"><img src="<?=$slide['feature_image']?>" alt="<?=prepareTag($slide['feature_description'])?>" style="width: 1024px; height: 270px;" /></a>
							<!-- <?=$slide['feature_description']?> -->
							<strong class="h"><?=prepareStringHtml($slide['feature_name'])?></strong>
							<p class="h"><?=prepareStringHtml($slide['feature_description'])?></p>
						</div>
					</td>
					<?php $count++; } ?>
				</tr>
			</table><!-- end slides_holder_table -->	
		</div><!-- end slides_holder -->		
		<div id="slider_bottom">
			<table cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<?php $count = 1; foreach($slides as $slide){ ?>
						<td>
							<span class="icon <?=($count == 1) ? 'active' : null?>" id="theicon_<?=(int)$count?>"><!-- block --></span>
						</td>
						<?php $count++; } ?>
					</tr>
				</tbody>
			</table>
		</div>
	</div><!-- end slider_inner -->
</div><!-- end slider -->
<?php }else{ ?>
<div style="height: 15px;"><!-- spacer --></div>
<?php } ?>