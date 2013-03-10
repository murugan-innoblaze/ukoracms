<script type="text/javascript">
	<!--
		$().ready(function(){
			$('.content_block_3').css('height', ($('.content_block_3').parent().parent().parent().height() - 24) + 'px');
		});
	//-->
</script>
<table cellpadding="0" cellspacing="0" class="three_wide_row">
	<body>
		<tr>
			<td class="left">
				<div class="content_block_outer_3">
					<div class="content_block_inner_3">
						<div class="content_block_3">
							<div class="full-content">
								<h2>Freshly Remodeled</h2>
								<p>Come check out our newly remodeled store. And when you're there - don't forget to try some Wisconsin Cheese, Wisconsin beer and wine at the Cheese Bar.</p>
								<embed flashvars="image=http://www.wisconsindairynews.com/portals/0/mnrs/media/184/video/poster.jpg&amp;file=http://www.wisconsindairynews.com/portals/0/mnrs/media/184/video/wdn9_11_4finished.flv&amp;title=finished video&amp;autostart=false&amp;width=305&amp;height=260&amp;displaywidth=320&amp;displayheight=240" allowfullscreen="true" allowscriptaccess="always" src="http://www.wisconsindairynews.com/DesktopModules/Considero.DNN.AliveMediaPlayer/mediaplayer.swf" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" play="true" loop="true" menu="true" height="260" width="305">
							</div>
						</div>
					</div>
				</div>
			</td>
			<td class="center">
				<div class="content_block_outer_3">
					<div class="content_block_inner_3">
						<div class="content_block_3">
							<div class="full-content">
								<h2>Wisconsin Cheese Mart</h2>
								<p>Founded in 1938, The Wisconsin Cheese Mart has grown from a small cheese shop to leader in selling Wisconsin Cheese online. Delicious cheese and Wisconsin, It's a natural partnership. With the Dairy State's healthy combination of fertile land and balanced climate, milk production excels. And Wisconsin's milk is not only impressive in terms of quantity, but also in quality. When the finest milk in the country is put into the hands of skilled and knowledgeable cheese makers, the end result is nothing less than exceptional cheese. </p>
								<p>The Wisconsin Cheese Mart has been part of this tradition since 1938. We search out the best cheeses we can find throughout the state of Wisconsin. We carry over 200 varieties of cheese in our display cases and sell by the pound in our store as well as ship cheese to all 50 states. We use nothing but real Wisconsin Cheese in our gift baskets.</p>
							</div>
						</div>
					</div>
				</div>			
			</td>
			<td class="right">
				<div class="content_block_outer_3">
					<div class="content_block_inner_3">
						<div class="content_block_3">
							<div class="full-content">
								<h2>Cheese Events</h2>
								<p><img src="/assets/layout/wisconsincheesemart-from-street.jpg" alt="Old time photo of Wisconsin Cheese Mart from the street" class="float-left" />When you are visiting Milwaukee, please come see us for one of our cheese &amp; wine tasting events for a real taste of Wisconsin.</p>
								asdf
								<ul>
									<?php $menu = self::getMenu(6); if(!empty($menu)){ $count = 1; foreach($menu as $page_id => $page_array){ $selected = ($page_array['active'] == 1) ? ' class="active" ' : null; ?>
									<li>
										<a href="<?=$page_array['path']?>" title="<?=prepareTag($page_array['page']['dzpro_page_title'])?>" class="<?php if($page_array['active'] == 1){ ?>active<?php } ?>" <?php if($count == sizeof($menu)){ echo 'style="border-bottom: none;"'; } ?>>
											<?=prepareStringHtml($page_array['page']['dzpro_page_name'])?>
										</a>
									</li>
									<?php $count++; } } ?>			
								</ul>
							</div>
						</div>
					</div>
				</div>			
			</td>
		</tr>
	</tbody>
</table>