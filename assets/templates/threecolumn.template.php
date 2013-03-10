<?php
/* Template name: Three Column Page Template */

//security check
if(!defined('RELATIVE_ASSETS_PATH')){ include '../../page.php'; exit(0); }

//force secure connection
forceSecureConnection();

//handle visitor
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);

//Save template views
addToIntelligenceStack('template view', 'three column page');

//Start page cache
$this->PageCache = new PageCache($this->db, '-1 hours');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en-us">
	<head><?=self::loadPageElements('head block')?></head>
	<body>
		<?=self::loadPageElements('body top')?>
		<div id="wrapper">
			<?=self::loadPageElements('wrapper top')?>
			<script type="text/javascript">
				<!--
					$().ready(function(){
						$('.content_block_3').css('height', ($('.content_block_3').parent().parent().parent().height() - 24) + 'px');
					});
				//-->
			</script>
			<table cellpadding="0" cellspacing="0" class="three_wide_row" style="margin-top: 12px;">
				<body>
					<tr>
						<td class="left">
							<div class="content_block_outer_3">
								<div class="content_block_inner_3">
									<div class="content_block_3">
										<div class="full-content">
											<?=self::loadPageContent('left column content')?>
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
											<?=self::loadPageContent('middle column content')?>
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
											<?=self::loadPageContent('right column content')?>
										</div>
									</div>
								</div>
							</div>			
						</td>
					</tr>
				</tbody>
			</table>			
			<?=self::loadPageElements('wrapper bottom')?>
		</div><!-- end wrapper -->
		<?=self::loadPageElements('page bottom')?>
	</body>
</html>
<?php

//Save page output
$this->PageCache->savePageCache();

?>