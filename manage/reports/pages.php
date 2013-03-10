<?php

//where are we
define('RELATIVE_ASSETS_PATH', '../assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//authenticate session
$Auth = new Auth($db);

//default elements
$default_elements = array();
$default_elements[] = array('element_area' => 'head block', 'element_id' => 1);
$default_elements[] = array('element_area' => 'body top', 'element_id' => 2);
$default_elements[] = array('element_area' => 'containter top', 'element_id' => 4);
$default_elements[] = array('element_area' => 'containter top', 'element_id' => 5);
$default_elements[] = array('element_area' => 'container bottom', 'element_id' => 3);

//Page builder
$Page = new Page($db, 'dzpro_pages', array('states' => $states_list, 'default_elements' => $default_elements));

?>
<!DOCTYPE html> 
<html lang="en-us"> 
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
		<?=$Page->buildHeadBlock()?>
		<?php if(!isset($_GET['record_id']) and isset($_GET['action']) and $_GET['action'] == 'new'){ ?>
		<script type="text/javascript"> $().ready(function(){ $('input[name=dzpro_page_name]').keyup(function(event){ var thePageName = $(this).val().replace(/[^a-z0-9]+/ig, '-').toLowerCase(); $('input[name=dzpro_page_slug]').val(thePageName); }); }); </script>
		<?php } ?>
	</head>
	<body>
		<div id="wrapper">
			<table id="outer_table" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td id="left_column">
							<?php require_once RELATIVE_ASSETS_PATH . '/elements/sections.element.php'; ?>
						</td><!-- end left_column -->
						<td id="right_column">
							<?php require_once RELATIVE_ASSETS_PATH . '/elements/subsections.element.php'; ?>
							<?php $show_form = $Page->showEventBlock(); ?>
							<table id="inner_content_table" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td id="inner_content_left">
											<div id="bucket">
												<?=$Page->buildFromToolbar()?>
												<?=$Page->buildFromListing()?>
											</div><!-- end bucket -->
										</td><!-- end inner_content_left -->
										<td id="inner_content_right">
										<?php if(isset($_GET['action']) and ($_GET['action'] == 'new' or $_GET['action'] == 'edit' or $_GET['action'] == 'delete') and $show_form){ ?>
											<?=$Page->buildPageBlock()?>
										<?php } ?>
										</td><!-- end inner_content_right -->	
									</tr>
								</tbody>
							</table><!-- end inner_content_table -->
						</td><!-- end right_column -->
					</tr>
				</tbody>
			</table><!-- end outer_table -->
		</div><!-- end wrapper -->
	</body>
</html>
<?php

//close the database connection
mysql_close($db);
?>