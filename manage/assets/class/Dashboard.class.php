<?php

class Dashboard {

	/*****************************************/
	/************ CONSTRUCT DASHBOARD ********/
	/*****************************************/
	function __construct($db){
		
		//database
		$this->db = $db;
		
		//do mysql dump
		if(isset($_GET['action']) and $_GET['action'] == 'mysql-dump'){ header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="database-' . preg_replace('/[^a-z]+/i', '-', HOST_NAME) . '-' . date('Y-m-d-') . time() . '.sql"'); header('Content-Transfer-Encoding: binary'); registerAdminActivity('database backup', 'all tables'); echo get_mysql_dump('*', array('dzpro_intelligence', 'dzpro_user_cards', 'dzpro_user_card_data')); exit(0); }
		
	}

	/*****************************************/
	/************ BUILD NAVIGATION TREE ******/
	/*****************************************/	
	public function buildDashboardHeadBlock(){
		?>
			<link type="text/css" href="<?=ASSETS_PATH?>/css/form.css" rel="stylesheet" media="all" />
			<link type="text/css" href="<?=ASSETS_PATH?>/css/listing.css" rel="stylesheet" media="all" />		
		<?php
	}	

	/*****************************************/
	/************ BUILD NAVIGATION TREE ******/
	/*****************************************/	
	public function printAllNavItems(){
		?>
				<script type="text/javascript">
					$().ready(function(){
						$('li.record_listing a', '#form_listing_parent_dashboard').click(function(){
							$('li.selected', '#form_listing_parent_dashboard').removeClass('selected');
							$(this).parents('li').addClass('selected');
						});
					});
				</script>
				<ul class="listing_parent" id="form_listing_parent_dashboard">				
		<?php
		$show_records = mysql_query_on_key(" SELECT * FROM dzpro_sections LEFT JOIN dzpro_section_pages USING ( dzpro_section_id ) LEFT JOIN dzpro_admin_to_section_page USING ( dzpro_section_page_id ) WHERE dzpro_admin_id = '" . (int)$_SESSION['dzpro_admin_id'] . "' ORDER BY dzpro_section_orderfield, dzpro_section_page_orderfield", 'dzpro_section_page_id'); if(have($show_records)){ foreach($show_records as $row){
			?>
					<li id="list_record_<?=(int)$row['dzpro_section_page_id']?>" class="record_listing">	
						<a href="<?='/' . $row['dzpro_section_slug'] . '/' . $row['dzpro_section_script_name']?>" title="<?=prepareTag($row['dzpro_section_page_name'])?>" class="form_link"><!-- block --></a>
						<strong class="title" title="<?=prepareTag(strip_tags($row['dzpro_section_page_name']))?>"><?=prepareStringHtml(limitString(strip_tags($row['dzpro_section_page_name']), LISTING_NAME_STR_LENGTH))?></strong>
						<strong class="sub" title="<?=prepareTag(strip_tags($row['dzpro_section_name']))?>"><?=prepareStringHtml(limitString(strip_tags($row['dzpro_section_name']), LISTING_DESCRIPTION_STR_LENGTH))?></strong>
						<p><?=prepareStringHtml(limitString(strip_tags('/' . $row['dzpro_section_slug'] . '/' . $row['dzpro_section_script_name']), LISTING_NAME_STR_LENGTH))?></p>
						<img src="<?=ASSETS_PATH?>/img/manager/bucket_right_arrow.png" alt="arrow" class="arrow_img" />
					</li>					
			<?php
		} }
		?>
				</ul>
		<?php		
	}

	/*****************************************/
	/************ BUILD NAVIGATION TREE ******/
	/*****************************************/
	public function printRecentAdminActivity(){
		$activity = mysql_query_flat(" SELECT * FROM dzpro_admin_activity LEFT JOIN dzpro_admins USING ( dzpro_admin_id ) ORDER BY dzpro_admin_activity_date_added DESC LIMIT 20 ");
			?>
				<div class="form_area" style="margin-top: -57px;">
					<div class="form_content_block">
						<div class="content_block_header" style="cursor: default;">
							<table cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td style="width: 170px;">
											Recent Admin Activity						
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="content-block">
							<script type="text/javascript">
								$().ready(function(){
									$('.activity_open_close_ui').click(function(){
										$(this).parents('li').children('.content_holder').toggle();
										if($(this).text() == '[+]'){ $(this).text('[-]'); }else{ $(this).text('[+]'); }
									});
								});
							</script>
							<ul>
			<?php
		if(have($activity)){ foreach($activity as $activity_row){
			?>
								<li>
									<h2><span id="activity_open_close_<?=(int)$activity_row['dzpro_admin_activity_id']?>" class="activity_open_close_ui" style="cursor: pointer;" title="Click for more information">[+]</span> <?=$activity_row['dzpro_admin_activity_name']?> by <?=$activity_row['dzpro_admin_name']?> on <?=date('M j g:ia', strtotime($activity_row['dzpro_admin_activity_date_added']))?><?php if(is_file(DOCUMENT_ROOT . $activity_row['dzpro_admin_activity_path'])){ ?> <span style="font-size: 12px; font-weight: normal;">[<a href="<?=$activity_row['dzpro_admin_activity_path']?>" title="<?=$activity_row['dzpro_admin_activity_path']?>"><?=$activity_row['dzpro_admin_activity_path']?></a>]</span><?php } ?></h2>
									<div style="display: none;" class="content_holder">
										<p><?=$activity_row['dzpro_admin_activity_description']?></p>
										<p><strong>This change was made from: </strong><a href="http://www.ipaddressdetective.com/<?=$activity_row['dzpro_admin_activity_ip']?>.html" title="look up <?=prepareTag($activity_row['dzpro_admin_activity_ip'])?>" target="_blank"><?=$activity_row['dzpro_admin_activity_ip']?></a></p>
									</div>
								</li>
			<?php
		} }else{ 
			?>
								<li>
									<h2>No Recent Activity</h2>
								</li>
			<?php
			}
			?>
							</ul>
						</div>
					</div>
				</div>
			<?php
	}
	
	/*************************************************************/
	/*********************** SEND PASSWORD RESET LINK BLOCK ******/
	/*************************************************************/	
	public function printDoMysqlBackupBlock(){
		?>
				<div class="form_area" style="margin-top: 0px;">
					<div class="form_content_block">
						<div class="content_block_header" style="cursor: default;">
							<table cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td style="width: 170px;">
											Mysql Backups						
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div style="background-color: #dee3e9; padding: 12px;">
							<div class="button_row">
								<table cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td style="text-align: right; width: 138px;">
												<input type="submit" name="mysql_backup" value="Download Database Backup" style="cursor: pointer;" class="form_tools_button" onclick="javascript:window.location='<?=addToGetString('action', 'mysql-dump')?>';" />
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
		<?php
	}
	
}

?>