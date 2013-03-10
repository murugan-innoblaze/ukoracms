<?php
class Widgets extends Form {
	
	/*****************************************************/
	/**************** CONSTRUCTOR ************************/
	/*****************************************************/
	function __construct($db, $parameters = array()){
	
		//build parent
		parent::__construct($db, null, $parameters, array());
	
		//database connection
		$this->db = $db;
		
		//tag id
		$this->tag_widgets = array(); $this->tag_id = self::checkTagId();
		
		//load widget from ajax call
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'load_widget_content' and isset($_POST['widget_id']) and is_numeric($_POST['widget_id'])){ self::printWidgetFromAjaxCall(); exit(0); }
		
	}
	
	/*****************************************************/
	/***************** PRINTING WIDGET *******************/
	/*****************************************************/		
	public function setWidgets($widgets = array()){
		if(!have($widgets)){ return false; }
		$this->tag_widgets = $widgets;
	}

	/*****************************************************/
	/***************** PRINTING WIDGET *******************/
	/*****************************************************/		
	public function printWidgetFromAjaxCall(){
		if(!isset($this->tag_widgets[$_POST['widget_id']]) or empty($this->tag_widgets[$_POST['widget_id']])){ return null; }
		self::buildIntelligenceDataArray();
		?>
			<script type="text/javascript">
				<!--
					<?php 
					switch(true){ 
						/********************************************************************************************/
						/********************************************************************************************/
						/************************************** DATA PIE CHART **************************************/
						/********************************************************************************************/
						/********************************************************************************************/
						case($this->tag_widgets[$_POST['widget_id']]['dzpro_widget_type'] == 'pie chart'):
							$background_color = '#ffffff';
					?>
							function drawChart<?=$_POST['widget_id']?>(){
								var data = new google.visualization.DataTable();
								data.addColumn('string', '<?=prepareTag($this->tag_widgets[$_POST['widget_id']]['dzpro_widget_name'])?>');
								data.addColumn('number', 'Hits');
								data.addRows(<?=sizeof($this->intelligence_value_hits)?>);
								<?php if(isset($this->intelligence_value_hits) and !empty($this->intelligence_value_hits)){ $this_index = 0; foreach($this->intelligence_value_hits as $meta_value => $meta_hits){ ?>
								data.setValue(<?=(int)$this_index?>, 0, '<?=prepareTag($meta_value)?>');
								data.setValue(<?=(int)$this_index?>, 1, <?=(int)$meta_hits?>);
								<?php $this_index++; } } ?>
								new google.visualization.PieChart(document.getElementById('widget_iframe_load_<?=$_POST['widget_id']?>')).draw(data, {width: 600, height: 300});
							}
							setTimeout(function(){ drawChart<?=$_POST['widget_id']?>(); }, <?=JAVASCRIPT_DELAY_FOR_WIDGET_LOAD?>);
					<?php
						break;
						
						/********************************************************************************************/
						/********************************************************************************************/
						/************************************** DATA LINE TYPE **************************************/
						/********************************************************************************************/
						/********************************************************************************************/
						case($this->tag_widgets[$_POST['widget_id']]['dzpro_widget_type'] == 'line chart'):
							$background_color = '#ffffff';
					?>
							function drawChart<?=$_POST['widget_id']?>(){
								var data = new google.visualization.DataTable();					
								data.addRows(<?=sizeof($this->intelligence_data_array['dates'])?>);
								data.addColumn('string', 'Date');
								<?php if(isset($this->intelligence_data_array['keys']) and !empty($this->intelligence_data_array['keys'])){ foreach($this->intelligence_data_array['keys'] as $meta_id => $meta_value){ ?>
								data.addColumn('number', '<?=prepareTag($meta_value)?>');
								<?php } } ?>
								<?php if(isset($this->intelligence_data_array['dates']) and !empty($this->intelligence_data_array['dates'])){ $this_index = 0; foreach($this->intelligence_data_array['dates'] as $date_string => $meta_array){ $subkey = 0; ?>
								data.setValue(<?=(int)$this_index?>, <?=$subkey++?>, '<?=$date_string?>');
									<?php if(isset($meta_array) and !empty($meta_array)){ foreach($meta_array as $meta_key => $meta_row){ ?>
										data.setValue(<?=(int)$this_index?>, <?=$subkey++?>, <?=(int)$meta_row['hits']?>);
									<?php } } $this_index++; ?>
								<?php } } ?>
								new google.visualization.LineChart(document.getElementById('widget_iframe_load_<?=$_POST['widget_id']?>')).draw(data, {width: 600, height: 300});	
							}
							setTimeout(function(){ drawChart<?=$_POST['widget_id']?>(); }, <?=JAVASCRIPT_DELAY_FOR_WIDGET_LOAD?>);
					<?php	
						break;
						
						/********************************************************************************************/
						/********************************************************************************************/
						/************************************** MOTION CHART ****************************************/
						/********************************************************************************************/
						/********************************************************************************************/							
						case($this->tag_widgets[$_POST['widget_id']]['dzpro_widget_type'] == 'motion chart'):
							$background_color = '#ffffff';
					?>
							function drawChart<?=$_POST['widget_id']?>(){
								var data = new google.visualization.DataTable();					
								data.addRows(<?=(int)$this->intelligence_data_array['total_rows']?>);
								data.addColumn('string', 'Title');
								data.addColumn('date', 'Date');
								data.addColumn('number', 'Hits');
								<?php $this_index = 0; if(isset($this->intelligence_data_array['dates']) and !empty($this->intelligence_data_array['dates'])){ foreach($this->intelligence_data_array['dates'] as $date_string => $meta_array){ ?>
									<?php if(isset($meta_array) and !empty($meta_array)){ foreach($meta_array as $meta_key => $meta_row){ $subkey = 0; ?>
										data.setValue(<?=(int)$this_index?>, <?=$subkey++?>, '<?=prepareTag($meta_row['row']['dzpro_intelligence_meta_value'])?>');
										data.setValue(<?=(int)$this_index?>, <?=$subkey++?>, new Date(<?=date('Y', strtotime($date_string))?>, <?=(date('m', strtotime($date_string)) - 1)?>, <?=date('d', strtotime($date_string))?>));
										data.setValue(<?=(int)$this_index?>, <?=$subkey++?>, <?=(int)$meta_row['hits']?>);
									<?php $this_index++; } } ?>
								<?php } } ?>
								new google.visualization.MotionChart(document.getElementById('widget_iframe_load_<?=$_POST['widget_id']?>')).draw(data, {width: 600, height: 300});	
							}
							setTimeout(function(){ drawChart<?=$_POST['widget_id']?>(); }, <?=JAVASCRIPT_DELAY_FOR_WIDGET_LOAD?>);
					<?php
						break;

						/********************************************************************************************/
						/********************************************************************************************/
						/************************************** US CITY MAP *****************************************/
						/********************************************************************************************/
						/********************************************************************************************/
						case($this->tag_widgets[$_POST['widget_id']]['dzpro_widget_type'] == 'us city map'):
							$background_color = '#e9f6fd';
					?>
   							function drawChart<?=$_POST['widget_id']?>(){
								var data = new google.visualization.DataTable();
								data.addRows(<?=sizeof($this->intelligence_value_hits)?>);
								data.addColumn('string', 'City');
								data.addColumn('number', 'Hits');
								<?php if(isset($this->intelligence_value_hits) and !empty($this->intelligence_value_hits)){ $this_index = 0; foreach($this->intelligence_value_hits as $meta_value => $meta_hits){ ?>
								data.setValue(<?=(int)$this_index?>, 0, '<?=prepareTag($meta_value)?>');
								data.setValue(<?=(int)$this_index?>, 1, <?=(int)$meta_hits?>);
								<?php $this_index++; } } ?>
								new google.visualization.GeoMap(document.getElementById('widget_iframe_load_<?=$_POST['widget_id']?>')).draw(data, {width: 600, height: 300, region: 'US', dataMode: 'markers'});
							}
							setTimeout(function(){ drawChart<?=$_POST['widget_id']?>(); }, <?=JAVASCRIPT_DELAY_FOR_WIDGET_LOAD?>);
					<?php
						break;

						/********************************************************************************************/
						/********************************************************************************************/
						/************************************** US STATE MAP ****************************************/
						/********************************************************************************************/
						/********************************************************************************************/
						case($this->tag_widgets[$_POST['widget_id']]['dzpro_widget_type'] == 'us state map'):
							$background_color = '#e9f6fd';
					?>
   							function drawChart<?=$_POST['widget_id']?>(){
								var data = new google.visualization.DataTable();
								data.addRows(<?=sizeof($this->intelligence_value_hits)?>);
								data.addColumn('string', 'City');
								data.addColumn('number', 'Hits');
								<?php if(isset($this->intelligence_value_hits) and !empty($this->intelligence_value_hits)){ $this_index = 0; foreach($this->intelligence_value_hits as $meta_value => $meta_hits){ if(isset($this->states[$meta_value])){ ?>
								data.setValue(<?=(int)$this_index?>, 0, '<?=prepareTag($this->states[$meta_value])?>');
								data.setValue(<?=(int)$this_index?>, 1, <?=(int)$meta_hits?>);
								<?php $this_index++; } } } ?>
								new google.visualization.GeoMap(document.getElementById('widget_iframe_load_<?=$_POST['widget_id']?>')).draw(data, {width: 600, height: 300, region: 'US'});
							}
							setTimeout(function(){ drawChart<?=$_POST['widget_id']?>(); }, <?=JAVASCRIPT_DELAY_FOR_WIDGET_LOAD?>);
					<?php
						break;
						
						/********************************************************************************************/
						/********************************************************************************************/
						/************************************** WORLD MAP *******************************************/
						/********************************************************************************************/
						/********************************************************************************************/
						case($this->tag_widgets[$_POST['widget_id']]['dzpro_widget_type'] == 'world map'):
							$background_color = '#e9f6fd';
					?>
   							function drawChart<?=$_POST['widget_id']?>(){
								var data = new google.visualization.DataTable();
								data.addRows(<?=sizeof($this->intelligence_value_hits)?>);
								data.addColumn('string', 'Country');
								data.addColumn('number', 'Hits');
								<?php if(isset($this->intelligence_value_hits) and !empty($this->intelligence_value_hits)){ $this_index = 0; foreach($this->intelligence_value_hits as $meta_value => $meta_hits){ ?>
								data.setValue(<?=(int)$this_index?>, 0, '<?=prepareTag($meta_value)?>');
								data.setValue(<?=(int)$this_index?>, 1, <?=(int)$meta_hits?>);
								<?php $this_index++; } } ?>
								new google.visualization.GeoMap(document.getElementById('widget_iframe_load_<?=$_POST['widget_id']?>')).draw(data, {width: 600, height: 300, region: 'world'});
							}
							setTimeout(function(){ drawChart<?=$_POST['widget_id']?>(); }, <?=JAVASCRIPT_DELAY_FOR_WIDGET_LOAD?>);
					<?php
						break;
						
						/********************************************************************************************/
						/********************************************************************************************/
						/************************************** DEFAULT TYPE ****************************************/
						/********************************************************************************************/
						/********************************************************************************************/							
						default:
							$background_color = '#ffffff';
					?>
					
					<?php	
						break;
					}
					?>
				//-->
			</script>
			<div id="widget_iframe_load_<?=$_POST['widget_id']?>" style="background-color: <?=$background_color?>;"><div class="background-loader-for-widget" style="background-color: <?=$background_color?>;"><!-- loader --></div><!-- graph loads here --></div>
		<?php
	}

	/*****************************************************/
	/***************** BUILD INTELLIGENCE ARRAY **********/
	/*****************************************************/	
	protected function buildIntelligenceDataArray(){
		if(!isset($this->tag_widgets[$_POST['widget_id']]['dzpro_intelligence_data_id']) or empty($this->tag_widgets[$_POST['widget_id']]['dzpro_intelligence_data_id'])){ return array(); }
		$this->intelligence_data_array = array();
		$this->intelligence_value_hits = array();
		$total_rows = 0;
		$variations_limit = ((int)$this->tag_widgets[$_POST['widget_id']]['dzpro_widget_variations_limit'] > 2) ? (int)$this->tag_widgets[$_POST['widget_id']]['dzpro_widget_variations_limit'] : 2;
		$even_odd_query_string = isset($this->tag_widgets[$_POST['widget_id']]['even']) ? (($this->tag_widgets[$_POST['widget_id']]['even'] === true) ? " AND dzpro_identity_id % 2 = 0 " : " AND dzpro_identity_id % 2 = 1 ") : null;
		$result = @mysql_query("SELECT * FROM dzpro_intelligence_data LEFT JOIN dzpro_intelligence_meta USING ( dzpro_intelligence_data_id ) WHERE dzpro_intelligence_data_id = " . (int)$this->tag_widgets[$_POST['widget_id']]['dzpro_intelligence_data_id'] . " ORDER BY dzpro_intelligence_meta_count DESC LIMIT " . (int)$variations_limit) or handleError(1, mysql_error()); if(mysql_num_rows($result) > 0){ 
			while($row = mysql_fetch_assoc($result)){
				$this->intelligence_value_hits[$row['dzpro_intelligence_meta_value']] = $row['dzpro_intelligence_meta_count'];
				for($d = $this->tag_widgets[$_POST['widget_id']]['dzpro_widget_limit']; $d >= 0; $d--){ 
					switch($this->tag_widgets[$_POST['widget_id']]['dzpro_widget_interval']){
						case 'hours':
							$date_to = date('Y-m-d H:i:s', strtotime('-' . $d . ' hours')); 
							$date_from = date('Y-m-d H:i:s', strtotime('-' . ($d + 1) . ' hours'));
							$date_string = date('ha M jS, Y', strtotime($date_from)); 
						break;
						case 'days':
							$date_to = date('Y-m-d 23:59:59', strtotime('-' . $d . ' days')); 
							$date_from = date('Y-m-d 00:00:00', strtotime('-' . $d . ' days'));
							$date_string = date('M jS, Y', strtotime($date_from)); 			
						break;
						case 'weeks':
							$date_to = date('Y-m-d 23:59:59', strtotime('-' . $d . ' weeks')); 
							$date_from = date('Y-m-d 23:59:59', strtotime('-' . ($d + 1) . ' weeks'));
							$date_string = date('M jS, Y', strtotime($date_from));
						break;
						case 'months':
							$date_to = date('Y-m-d 23:59:59', strtotime('-' . $d . ' months')); 
							$date_from = date('Y-m-d 23:59:59', strtotime('-' . ($d + 1) . ' months'));
							$date_string = date('M, Y', strtotime($date_from)); 
						break;
						case 'years':
							$date_to = date('Y-m-d 23:59:59', strtotime('-' . $d . ' years')); 
							$date_from = date('Y-m-d 23:59:59', strtotime('-' . ($d + 1) . ' years'));
							$date_string = date('Y', strtotime($date_from));
						break;
						case 'all time':
							$date_to = date('Y-m-d 23:59:59');
							$date_from = '1979-01-01 00:00:00';
							$date_string = 'all time';
						break;
					}
					$date_count_result = @mysql_query("SELECT COUNT(*) AS hits FROM dzpro_intelligence WHERE dzpro_intelligence_data_id = " . (int)$row['dzpro_intelligence_data_id'] . " " . $even_odd_query_string . " AND dzpro_intelligence_meta_id = " . (int)$row['dzpro_intelligence_meta_id'] . " AND dzpro_intelligence_date_added BETWEEN '" . mysql_real_escape_string($date_from) . "' AND '" . mysql_real_escape_string($date_to) . "'") or handleError(1, mysql_error()); 
					if(mysql_num_rows($date_count_result) > 0){ 
						while($row_count = mysql_fetch_assoc($date_count_result)){ 
							$this->intelligence_data_array['dates'][$date_string][$row['dzpro_intelligence_meta_id']]['hits'] = $row_count['hits']; 
							$this->intelligence_data_array['dates'][$date_string][$row['dzpro_intelligence_meta_id']]['row'] = $row;
							$this->intelligence_data_array['keys'][$row['dzpro_intelligence_meta_id']] = $row['dzpro_intelligence_meta_value'];
							$total_rows++; 
						} 
						mysql_free_result($date_count_result); 
					} 
				} 
			} 
			mysql_free_result($result); 
		} 
		$this->intelligence_data_array['total_rows'] = $total_rows;
	}

	/*****************************************************/
	/***************** VERIFY TAG ID *********************/
	/*****************************************************/		
	protected function checkTagId(){
		if(!isset($_GET['tag_id']) or empty($_GET['tag_id']) or !is_numeric($_GET['tag_id'])){ return null; }
		$result = @mysql_query("SELECT * FROM dzpro_widget_tags LEFT JOIN dzpro_widget_to_tag USING ( dzpro_widget_tag_id ) LEFT JOIN dzpro_widgets USING ( dzpro_widget_id ) WHERE dzpro_widget_tag_id = " . (int)$_GET['tag_id'] . " ORDER BY dzpro_widget_orderfield ASC") or die(mysql_error()); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $this->tag_widgets[$row['dzpro_widget_id']] = $row; } mysql_free_result($result); return (int)$_GET['tag_id']; }
		return false;
	}

	/*****************************************************/
	/***************** WIDGET HEAD BLOCK *****************/
	/*****************************************************/	
	public function printWidgetHeadBlock(){
		?>
			<script type="text/javascript" src="http://www.google.com/jsapi"></script>
			<script type="text/javascript">
				<!--
					google.load("visualization", "1", {packages:["corechart"]}); 
				//-->
			</script>
		<?php
	}

	/*****************************************************/
	/***************** LIST WIDGET TAGS ******************/
	/*****************************************************/	
	public function printWidgetList(){
		?>
			<script type="text/javascript">
				<!-- 
					$().ready(function(){
						$('li a.form_link', '#bucket').click(function(){
							$('li.selected', '#bucket').removeClass('selected');
							$(this).parents('li').addClass('selected');
						});
					});
				//-->
			</script>
			<ul class="listing_parent">				
				<?php
					$tag_result = @mysql_query("SELECT * FROM dzpro_widget_tags ORDER BY dzpro_widget_tag_orderfield"); if(mysql_num_rows($tag_result) > 0){ while($tag_result_row = mysql_fetch_assoc($tag_result)){
						$selected = (isset($_GET['tag_id']) and $_GET['tag_id'] == $tag_result_row['dzpro_widget_tag_id']) ? ' selected ' : null;
				?>
				<li class="record_listing <?=$selected?>">
					<a href="<?=addToGetString(array('tag_id'), array((int)$tag_result_row['dzpro_widget_tag_id']))?>" title="<?=prepareTag($tag_result_row['dzpro_widget_tag_name'])?>" class="form_link"><!-- block --></a>
					<span class="date"><strong><?=date('M j', strtotime($tag_result_row['dzpro_widget_tag_date_added']))?></strong> <?=date('g:ia', strtotime($tag_result_row['dzpro_widget_tag_date_added']))?></span>
					<strong class="title" title="<?=prepareTag($tag_result_row['dzpro_widget_tag_name'])?>"><?=prepareStringHtml(limitString($tag_result_row['dzpro_widget_tag_name'], LISTING_NAME_STR_LENGTH))?></strong>
					<strong class="sub" title="<?=prepareTag($tag_result_row['dzpro_widget_tag_description'])?>"><?=prepareStringHtml(limitString($tag_result_row['dzpro_widget_tag_description'], LISTING_DESCRIPTION_STR_LENGTH))?></strong>
					<p><?=prepareStringHtml(limitString($tag_result_row['dzpro_widget_tag_name'], LISTING_NAME_STR_LENGTH))?></p>
					<img src="<?=ASSETS_PATH?>/img/manager/bucket_right_arrow.png" alt="arrow" class="arrow_img" />
				</li>														
				<?php
					} mysql_free_result($tag_result); }
				?>
			</ul>		
		<?php
	}

	/*****************************************************/
	/**************** BUILD WIDGETS FOR TAG **************/
	/*****************************************************/
	public function printWidgetsForTag($differentiating_string = 'load_widget_content'){
		if(empty($this->tag_widgets)){ return null; }
			?>
				<div class="form_area" style="margin-top: -54px;">
			<?php
		foreach($this->tag_widgets as $widget_id => $widget_array){
			?>
					<div class="form_content_block">
						<div class="content_block_header">
							<table cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td style="vertical-align: middle;">
											<?=prepareStringHtml($widget_array['dzpro_widget_name'])?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<script type="text/javascript">
							<!--
								$().ready(function(){
									$.ajax({
										url : '<?=$_SERVER['PHP_SELF']?><?=str_ireplace('&amp;', '&', addToGetString(array('ajax'), array($differentiating_string)))?>',
										type : 'post',
										data: 'widget_id=<?=(int)$widget_array['dzpro_widget_id']?>',
										success: function(html){ if(html.length > 20){ $('#widget_target_id_<?=(int)$widget_array['dzpro_widget_id']?>').html(html); } },
										error: function(error){ alert('error:' + error + ' url:' + '<?=$_SERVER['PHP_SELF']?><?=str_ireplace('&amp;', '&', addToGetString(array('ajax'), array($differentiating_string)))?>' + ' data:' + 'widget_id=<?=(int)$widget_array['dzpro_widget_id']?>'); }
									});
								});
							//-->
						</script>
					    <div class="widget_target" id="widget_target_id_<?=(int)$widget_array['dzpro_widget_id']?>">
					    	<div class="background-loader-for-widget"><!-- loader --></div>
					    	<!-- graph loads here -->
					    </div><!-- end .widget_target -->
					</div><!-- end .form_content_block -->
			<?php
		}
			?>
				</div><!-- end form_area -->
			<?php
	}

} 
?>