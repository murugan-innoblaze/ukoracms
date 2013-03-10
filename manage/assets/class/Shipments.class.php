<?php

class Shipments extends Form {
	
	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name){
	
		//run form constructor
		parent::__construct($db, $table_name, $parameters = array(), $sticky_fields = array());
		
		//dont do delete
		$this->dontAllowDelete = true;
		
		//date field
		$this->date_field_name = 'dzpro_order_shipment_shipping_date';
		
		//time field name
		$this->time_field_name = null;
		
		//these are weekdays
		$this->weekdays = array(
			'Sun' => 'Sunday',
			'Mon' => 'Monday',
			'Tue' => 'Tuesday',
			'Wed' => 'Wednesday',
			'Thu' => 'Thursday',
			'Fri' => 'Friday',
			'Sat' => 'Saturday'
		);
		
		//these are the months
		$this->months = array(
			1 => 'January',
			2 => 'February',
			3 => 'March',
			4 => 'April',
			5 => 'May',
			6 => 'June',
			7 => 'July',
			8 => 'August',
			9 => 'September',
			10 => 'October',
			11 => 'November',
			12 => 'December'
		);
		
		//shipment type reference
		$this->shipment_type_reference = array(
			'01' => 'Air - Priority Overnight (by 10:30AM, later for rural)',
    		'03' => 'Air - 2 Day Air',
    		'05' => 'Air - Standard Overnight (by 3PM, later for rural)',
   			'06' => 'Air - First Overnight',
   			'20' => 'Air - Express Saver (3 Day)',
 			'90' => 'Ground - Home Delivery',
			'92' => 'Ground - Ground Service'
		);
		
		//filter value
		$this->filter_value = isset($_GET['filter_value']) ? $_GET['filter_value'] : null;
		
		//get month
		$this->month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
		
		//get year
		$this->year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
		
		//create shipment
		$this->shipment_label_error = null;
				
		//handle single records search
		self::handleSingleResultRedirect();

		//print all packing slips
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'print-all-slips'){ self::printAllPackingSlips(); exit(0); }
		
		//print only not yet printed
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'print-new-slips'){ self::printNotPrintedSlips(); exit(0); }

		//build the items array
		$this->shipment_items_array = self::buildShipmentItemsArray();

		//build label
		$this->shipping_label_path = self::getShippingLabel();
		
		//get last shipment label
		$this->shipment_label = self::getShipmentLabel();
		
		//handle update printed
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'updateMarkPrinted' and isset($_POST['shipment_label_id'])){ 
			self::updateShipmentLabelPrinted((int)$_POST['shipment_label_id']);
			echo 'updated'; 
			exit(0); 
		}

		//get the order details
		$this->shipment_details = self::buildShipmentDetails();
		
	}

	/*************************************************************/
	/*********************** SET DATE FIELD NAME *****************/
	/*************************************************************/		
	protected function handleSingleResultRedirect(){
		if(isset($this->show_records[0][$this->primary_key]) and sizeof($this->show_records) == 1 and isset($this->search_query) and is_numeric($this->search_query) and isset($this->show_records[0]['dzpro_order_shipment_shipping_date'])){ 
			header('Location: ' . addToGetStringAjax(array('action', 'record_id', 'filter_key', 'filter_value'), array('edit', $this->show_records[0][$this->primary_key], 'dzpro_order_shipment_shipping_date', $this->show_records[0]['dzpro_order_shipment_shipping_date']), array('record_search')));
			exit(0);
		}
		return true;
	}

	/*************************************************************/
	/*********************** SET DATE FIELD NAME *****************/
	/*************************************************************/	
	public function setDateFieldName($field = null){
		if(!have($field)){ return false; }
		$this->date_field_name = $field;
	}

	/*************************************************************/
	/*********************** SET TIME FIELD NAME *****************/
	/*************************************************************/		
	public function setTimeFieldName($field = null){
		if(!have($field)){ return false; }
		$this->time_field_name = $field;
	}
	
	/*************************************************************/
	/*********************** GET NEXT DAY ************************/
	/*************************************************************/	
	private function getFixedYear($month, $year){
		switch(true){
			case ($month < 1): return $year - 1; break;
			case ($month > 12): return $year + 1; break;
			default: return $year; break;
		}
	}

	/*************************************************************/
	/*********************** GET NEXT YEAR ***********************/
	/*************************************************************/	
	private function getFixedMonth($month = false){
		$month = ($month === false) ? $this->month : $month;
		switch(true){
			case ($month < 1): return $month + 12; break;
			case ($month > 12): return $month - 12; break;
			default: return $month; break;
		}
	}
	
	/*************************************************************/
	/*********************** GET NEXT DAY ************************/
	/*************************************************************/	
	private function getNextDay($day, $month = null, $year = null){
		$month = ($month == null) ? $this->month : $month;
		$year = ($year == null) ? $this->year : $year;
		$days_in_this_month = cal_days_in_month(CAL_GREGORIAN, self::getFixedMonth($month), self::getFixedYear(self::getFixedMonth($month - 1), $year));
		$days_in_previous_month = cal_days_in_month(CAL_GREGORIAN, self::getFixedMonth($month - 1), self::getFixedYear(self::getFixedMonth($month - 1), $year));
		switch(true){
			case ($day >= $days_in_this_month): return 1; break;
			case ($day < 1): return $days_in_previous_month + $day; break;
			default: return $day + 1; break;
		}
	}
	
	/*************************************************************/
	/*********************** GET WEEDAY OFFSET *******************/
	/*************************************************************/		
	private function getWeekdayOffset(){
		return -(date('N', mktime(1, 1, 1, $this->month, 1, $this->year)) - 1);
	}

	/*************************************************************/
	/*********************** PRINT CALENDAR HEAD *****************/
	/*************************************************************/	
	public function printCalendarHead(){
		?>
			<link type="text/css" href="/assets/css/calendar.css" rel="stylesheet" media="all" />	
		<?php
	}

	/*************************************************************/
	/*********************** DOES DATE HAVE ITEMS? ***************/
	/*************************************************************/
	protected function doesDateHaveCalendarItems($date = null){
		if(!have($date)){ return false; } return mysql_query_row_count(" SELECT " . mysql_real_escape_string($this->primary_key) . " FROM " . mysql_real_escape_string($this->table) . " WHERE " . mysql_real_escape_string($this->date_field_name) . " = '" . mysql_real_escape_string(date('Y-m-d', strtotime($date))) . "' ");
	}

	/*************************************************************/
	/*********************** DOES DATE HAVE (SHIPPED) ITEMS? *****/
	/*************************************************************/
	protected function doesDateHaveCalendarShippedItems($date = null){
		if(!have($date)){ return false; } return mysql_query_row_count(" SELECT " . mysql_real_escape_string($this->primary_key) . " FROM dzpro_order_shipments LEFT JOIN dzpro_order_shipment_labels USING ( dzpro_order_shipment_id ) WHERE dzpro_order_shipment_label_printed = 1 AND " . mysql_real_escape_string($this->date_field_name) . " = '" . mysql_real_escape_string(date('Y-m-d', strtotime($date))) . "' GROUP BY dzpro_order_shipment_id ORDER BY dzpro_order_shipment_label_date_added DESC "); 
	}

	/*************************************************************/
	/*********************** BUILD SHIPMENTS TOOLBAR *************/
	/*************************************************************/	
	public function buildFromToolbar(){
		$filter_out_fields = array('record_id', 'record_search', 'filter_key', 'filter_value', 'start');
		if(isset($this->prepare_for_iframe) and $this->prepare_for_iframe){
			$filter_out_fields = array('record_search', 'filter_key', 'filter_value', 'action');
			$need_to_keep_these = array('filter_key', 'filter_value', 'table_name');
			foreach($filter_out_fields as $filter_out_key => $filter_out_field){
				if(in_array($filter_out_field, $need_to_keep_these)){
					unset($filter_out_fields[$filter_out_key]);
				}
			}
		}
		?>
						<?php if(have($this->form_tools)){ ?>
						<script type="text/javascript">
							<!-- 
								$().ready(function(){
									$('li a.form_link', '#bucket').click(function(){
										$('li.selected', '#bucket').removeClass('selected');
										$(this).parents('li').addClass('selected');
									});
									$('#looking_glass').click(function(){ $('#bucket_search_text').focus(); });
									$('.form_tools_icon').click(function(){
										$('#form_tools_load_area_<?=$this->table?>').toggle();
									});
									$('.form_tools_icon').hover(function(){
										$('.arrow_tools', '#form_tools_load_area_<?=$this->table?>').show();
										$('.arrow_tools_down', '#form_tools_load_area_<?=$this->table?>').hide();
									}, function(){
										$('.arrow_tools', '#form_tools_load_area_<?=$this->table?>').hide();
										$('.arrow_tools_down', '#form_tools_load_area_<?=$this->table?>').show();		
									});
								});
							//-->
						</script>
						<div id="form_tools_load_area_<?=$this->table?>" class="form_tools_window">
							<img src="/assets/img/manager/tools-arrow-icon.gif" alt="arrow" class="arrow_tools" />
							<img src="/assets/img/manager/tools-arrow-down-icon.gif" alt="arrow" class="arrow_tools_down" />
							<table cellpadding="0" cellspacing="0">
								<tbody>
									<?php 
										foreach($this->form_tools as $the_tool){ 
											switch($the_tool){
												case('export'):
									?>
									<tr>
										<td>
											<a href="<?=addToGetString(array('export'), array('csv'))?>" title="Export Data" target="_blank" class="export_link">
												<img src="/assets/img/manager/download-csv-icon.png" alt="Export Table Data" /> Export Table Data
											</a>
										</td>
										<td>
											(CSV format, export)
										</td>
									</tr>
									<tr>
										<td colspan="2" style="height: 7px;"><!-- spacer --></td>
									</tr>
									<tr>
										<td>
											<a href="<?=addToGetString(array('export'), array('mysql-dump'))?>" title="Export Data" target="_blank" class="export_link">
												<img src="/assets/img/manager/mysql-dump.png" alt="Export Table Data" /> Do Mysql Dump
											</a>
										</td>
										<td>
											(Dump table data)
										</td>
									</tr>
									<?php			
												break;
											}
									 	} 
									?>
								</tbody>
							</table>
						</div>
						<?php } ?>
						<script type="text/javascript">
							<!-- 
								$().ready(function(){
									$('li a.form_link', '#bucket').click(function(){
										$('li.selected', '#bucket').removeClass('selected');
										$(this).parents('li').addClass('selected');
									});
									$('#looking_glass').click(function(){ $('#bucket_search_text').focus(); });
								});
							//-->
						</script>
						<div class="bucket_top_nav">
							<img src="<?=ASSETS_PATH?>/img/manager/looking-glass.jpg" alt="glass" id="looking_glass" />
							<form method="get" action="<?=getGetString()?>" id="search_form_<?=$this->table?>">
								<?=buildHiddenFieldFromGet($filter_out_fields)?>
								<input type="text" name="record_search" id="bucket_search_text" value="<?=$this->search_query?>" autocomplete="off" />
							</form>
							<a href="<?=addToGetString(null, null, array('record_search', 'viewall'))?>" title="Clear Results" id="clear_search"><!-- block --></a>
							<?php if(!defined('DO_NOT_ALLOW_ADDING_RECORDS')){ ?>
							<a href="<?=addToGetString('action', 'new', array('record_id', 'record_search'))?>" title="New Record">
								+
							</a>
							<?php } ?>
							<?php if(have($this->form_tools)){ ?><div class="form_tools_icon"><!-- icon <?=$this->table?> --></div><?php } ?>											
							<?php if(have($this->showTotalCountInHeader) and $this->showTotalCountInHeader === true){ ?>
							<div style="position: absolute; top: 13px; right: 44px; color: #677bbc; font-size: 12px; padding: 0px 4px; -moz-border-radius: 7px; border-radius: 7px; background: white;"><?=self::getTotalRecordCount()?></div>
							<?php } ?>
						</div><!-- end bucket_top_nav -->			
		<?php
	}
	
	/*************************************************************/
	/*********************** PRINT CALENDAR **********************/
	/*************************************************************/	
	public function printCalendar(){
		?>
			<div id="calendar_header">
				<a href="<?=addToGetString(array('month', 'year'), array(self::getFixedMonth($this->month - 1), self::getFixedYear($this->month - 1, $this->year)))?>" title="previous month" id="calendar_left_button"><!-- block --></a>
				<a href="<?=addToGetString(array('month', 'year'), array(self::getFixedMonth($this->month + 1), self::getFixedYear($this->month + 1, $this->year)))?>" title="next month" id="calendar_right_button"><!-- block --></a>
				<div id="header_title"><?=$this->months[$this->month]?> <?=$this->year?></div>
			</div>
			<table id="calendar_browsing" cellpadding="0" cellspacing="0">
				<thead>
					<tr>
						<?php foreach($this->weekdays as $week_stort => $week_long){ ?>
						<th title="<?=$week_long?>"><?=$week_stort?></th>
						<?php } ?>
					</tr>
				</thead>
				<body>
					<?php $counter = 0; $max_rows = ceil((cal_days_in_month(CAL_GREGORIAN, $this->month, $this->year) + date('N', mktime(1, 1, 1, $this->month, 1, $this->year))) / 7); for($i = 0; $i < $max_rows; $i++){ ?>
					<tr>						
					<?php
							foreach($this->weekdays as $week_stort => $week_long){
								if(!isset($this_day)){ $this_day = self::getNextDay(self::getWeekdayOffset(), self::getFixedMonth($this->month), $this->year); }
								$outofmonth = (($counter < 12 and $this_day > 12) or ($counter > 12 and $this_day < 12)) ? 'outofmonth' : '';	
								$today = (date('d') == $this_day and date('m') == $this->month and date('Y') == $this->year and empty($outofmonth)) ? 'today' : '';
								switch(true){
									case ($counter < 12 and $this_day > 20): $outofmonth = 'outofmonth'; $this_month = $this->month - 1; $this_year = $this->year; if($this_month < 1){ $this_month+=12; $this_year-=1; } break;
									case ($counter > 28 and $this_day < 10): $outofmonth = 'outofmonth'; $this_month = $this->month + 1; $this_year = $this->year; if($this_month > 12){ $this_month-=12; $this_year+=1; } break;
									default: $outofmonth = ''; $this_month = $this->month; $this_year = $this->year; break;
								}
								$selected = (mktime(1, 1, 1, $this_month, $this_day, $this_year) == mktime(1, 1, 1, date('m', strtotime($this->filter_value)), date('d', strtotime($this->filter_value)), date('Y', strtotime($this->filter_value)))) ? 'selected' : '';
					?>
						<td>
							<a href="<?=addToGetString(array('year', 'month', 'filter_key', 'filter_value'), array($this_year, $this_month, $this->date_field_name, (int)$this_year . '-' . (int)$this_month . '-' . (int)$this_day), array('record_id', 'record_search'))?>" title="<?=(int)$this_day?>" class="<?=$outofmonth?> <?=$today?> <?=$selected?>" id="date_marker_<?=md5(date('Ymd', strtotime((int)$this_year . '-' . (int)$this_month . '-' . (int)$this_day)))?>">
								<?php $shipments_today = self::doesDateHaveCalendarItems((int)$this_year . '-' . (int)$this_month . '-' . (int)$this_day); if(have($shipments_today)){ ?><span class="shipments_holder"><?=$shipments_today?></span><?php } ?>
								<?php $shipped_today = self::doesDateHaveCalendarShippedItems((int)$this_year . '-' . (int)$this_month . '-' . (int)$this_day); if(have($shipped_today)){ ?><span class="shipped_holder"><?=$shipped_today?></span><?php } ?>
								<?php if($shipments_today){ ?><span class="active_calendar_items">.</span><?php } ?>
								<?php if($shipped_today > 0 and $shipped_today == $shipments_today){ ?><img src="/assets/img/manager/little-checkmark.png" alt="checkmark" style="position: absolute; bottom: 0px; left: 0px;" /><?php } ?>
								<?=(int)$this_day?>
							</a>
						</td>
					<?php $this_day = self::getNextDay($this_day, $this_month, $this_year); $counter++;	} ?>
					</tr>
					<?php }	?>
				</body>
			</table>
		<?php	
	}
	
	/*************************************************************/
	/*********************** PRINT CALENDAR LIST *****************/
	/*************************************************************/
	public function printCalendarListing(){
		if(!have($this->filter_value) and !have($this->search_query)){ return null; }
		$frame_class = ($this->prepare_for_iframe === true) ? 'iframe' : null;
		?>
			<script type="text/javascript">
				<!--
					$().ready(function(){
						$('.record_listing', '#form_listing_parent_<?=$this->table?>').hover(function(){
							$('a.selected_alt', '#calendar_browsing').removeClass('selected_alt');
							$('a#' + $(this).attr('rel'), '#calendar_browsing').addClass('selected_alt');
						});
					});
				//-->
			</script>
			<ul class="listing_parent <?=$frame_class?>" id="form_listing_parent_<?=$this->table?>">				
		<?php
		if(isset($this->filter_value) and have($this->filter_value)){
		?>
				<li style="height: 17px; background: url('/assets/img/manager/bucket_top_rep_x.png') center left repeat-x transparent; border-top: 1px solid #44588c; border-bottom: 1px solid #2b3d6a; color: white; font-size: 14px; font-weight: bold; padding: 0px; padding-left: 31px; text-shadow: -1px 1px 1px #777;"><?=date('M j, Y', strtotime($this->filter_value))?></li>
		<?php
		}
		if($this->table_start > 0 and !isset($_GET['viewall'])){
			$new_start = (($this->table_start - $this->results_limit) >= 0) ? ($this->table_start - $this->results_limit) : 0;
		?>
				<li class="prev">
					<a href="<?=addToGetString(array('start'), array($new_start))?>" class="prev-link" title="back"><!-- block --></a>
					<?php if($this->table_query_total > mysql_num_rows($result)){ ?> <a href="<?=addToGetString(array('viewall'), array('1'), array('start'))?>" class="view_all" title="view all records">view all <?=(int)$this->table_query_total?> records</a> <?php } ?>
				</li>
		<?php 
		}
		if(isset($this->show_records) and have($this->show_records)){
			foreach($this->show_records as $row){
				$selected = ((int)$row[$this->primary_key] == $this->primary_value) ? 'selected' : null;
			?>
				<li id="list_record_<?=(int)$row[$this->primary_key]?>" rel="date_marker_<?=md5(date('Ymd', strtotime($row['dzpro_order_shipment_shipping_date'])))?>" class="record_listing <?=$selected?>">
					<?php $shipment_status = self::getShipmentLabel((int)$row['dzpro_order_shipment_id']); if($shipment_status['dzpro_order_shipment_label_printed'] == 1){ ?>
					<img src="/assets/img/manager/shipped-ok.png" alt="shipped ok" style="position: absolute; top: 16px; right: 25px;" />
					<?php }elseif(have($shipment_status['dzpro_order_shipment_label_error'])){ ?>
					<img src="/assets/img/manager/shipped-problem.png" alt="label problem" style="position: absolute; top: 16px; right: 25px;" />
					<?php }else{ ?>
					<img src="/assets/img/manager/shipped-not-sure.png" alt="not sure" style="position: absolute; top: 16px; right: 25px;" />
					<?php } ?>
					<a class="delete_icon" href="<?=addToGetString(array('action', 'record_id', 'filter_key', 'filter_value'), array('delete',(int)$row[$this->primary_key], 'dzpro_order_shipment_shipping_date', $row['dzpro_order_shipment_shipping_date']), array('record_search'))?>" title="Delete this record"><!-- block --></a>
					<a href="<?=addToGetString(array('action', 'record_id', 'filter_key', 'filter_value'), array('edit', (int)$row[$this->primary_key], 'dzpro_order_shipment_shipping_date',  $row['dzpro_order_shipment_shipping_date']), array('record_search'))?>" title="<?=htmlentities($row[$this->row_name])?>" class="form_link"><!-- block --></a>
					<span class="date"><strong><?=date('M j', strtotime($row[$this->date_field_name]))?></strong></span>
					<strong class="title" title="<?=prepareTag($row[$this->row_name])?>"><?=prepareStringHtml(limitString($row[$this->row_name], LISTING_NAME_STR_LENGTH))?></strong>
					<strong class="sub" title="<?=prepareTag($row[$this->row_description])?>"><?=prepareStringHtml(limitString($row[$this->row_description], LISTING_DESCRIPTION_STR_LENGTH))?></strong>
					<p><?=prepareStringHtml(limitString($row[$this->row_name_alt], LISTING_NAME_STR_LENGTH))?></p>
					<img src="<?=ASSETS_PATH?>/img/manager/bucket_right_arrow.png" alt="arrow" class="arrow_img" />
				</li>					
			<?php
			}
		}
		if($this->table_query_total - $this->results_limit > $this->table_start and $this->table_query_total > $this->results_limit and !isset($_GET['viewall'])){
			$new_start = (($this->table_start + $this->results_limit) < $this->table_query_total) ? ($this->table_start + $this->results_limit) : 0;
		?>
				<li class="next">
					<a href="<?=addToGetString(array('start'), array($new_start))?>" class="next-link" title="next"><!-- block --></a>
					<?php if($this->table_query_total > sizeof($this->show_records)){ ?> <a href="<?=addToGetString(array('viewall'), array('1'), array('start'))?>" class="view_all" title="view all records">view all <?=(int)$this->table_query_total?> records</a> <?php } ?>
				</li>
		<?php
		}
		?>
			</ul>
		<?php	
	}

	/*************************************************************/
	/*********************** PRINT ALL PACKING SLIPS *************/
	/*************************************************************/		
	protected function printAllPackingSlips(){
		if(!have($this->filter_value)){ return null; }
		$shipments = mysql_query_flat(" SELECT " . mysql_real_escape_string($this->primary_key) . " FROM " . mysql_real_escape_string($this->table) . " WHERE " . mysql_real_escape_string($this->date_field_name) . " = '" . mysql_real_escape_string(date('Y-m-d', strtotime($this->filter_value))) . "' "); foreach($shipments as $shipment){ self::createPackingSlip((int)$shipment[$this->primary_key]); } return true;
	}

	/*************************************************************/
	/*********************** PRINT NOT PRINTED SLIPS *************/
	/*************************************************************/		
	protected function printNotPrintedSlips(){
		if(!have($this->filter_value)){ return null; }
		$shipments = mysql_query_flat(" SELECT " . mysql_real_escape_string($this->primary_key) . " FROM " . mysql_real_escape_string($this->table) . " WHERE " . mysql_real_escape_string($this->date_field_name) . " = '" . mysql_real_escape_string(date('Y-m-d', strtotime($this->filter_value))) . "' AND dzpro_order_shipment_printed = 0 "); foreach($shipments as $shipment){ self::createPackingSlip((int)$shipment['dzpro_order_shipment_id']); } return true;
	}

	/*************************************************************/
	/*********************** BUILD PACKING SLIP ******************/
	/*************************************************************/	
	protected function createPackingSlip($shipment_id){
		$shipment_sorted = array(); $shipment_array = mysql_query_flat(" SELECT * FROM dzpro_order_shipments LEFT JOIN dzpro_orders USING ( dzpro_order_id ) LEFT JOIN dzpro_order_items USING ( dzpro_order_shipment_id ) LEFT JOIN dzpro_order_item_options USING ( dzpro_order_item_id ) WHERE dzpro_order_shipment_id = " . (int)$shipment_id . " "); if(have($shipment_array)){ foreach($shipment_array as $shipment){ $shipment_sorted['shipment'] = $shipment; $shipment_sorted['order'] = $shipment; if(have($shipment['dzpro_order_item_id'])){ $shipment_sorted['items'][$shipment['dzpro_order_item_id']]['item'] = $shipment; } if(have($shipment['dzpro_order_item_option_id'])){ $shipment_sorted['items'][$shipment['dzpro_order_item_id']]['options'][$shipment['dzpro_order_item_option_id']] = $shipment; } } } if(!have($shipment_sorted)){ return null; }
		mysql_update(" UPDATE dzpro_order_shipments SET dzpro_order_shipment_printed = 1 WHERE dzpro_order_shipment_id = " . (int)$shipment_sorted['shipment']['dzpro_order_shipment_id']);
		?>	
			<div style="padding: 20px; font-family: Verdana; font-size: 12px; height: 920px; position: relative; text-align: left; width: 1024px;">
				<h1 style="text-align: right;">Shipment #<?=(int)$shipment_sorted['shipment']['dzpro_order_shipment_id']?></h1>
				<hr />
				<table cellpadding="0" cellspacing="0" style="width: 100%;">
					<tbody>
						<tr>
							<td style="width: 30%; vertical-align: middle; padding: 20px;">
								<img src="http://www.<?=HOST_NAME?>/assets/layout/cheesemart-logo.png" alt="<?=SITE_NAME?>" />
							</td>
							<td style="width: 30%; vertical-align: top; padding: 20px;">
								<h2>Customer:</h2>
								<p>
									<strong><?=prepareStringHtml($shipment_sorted['order']['dzpro_order_customer_name'])?></strong><br />
									<?php if(have($shipment_sorted['order']['dzpro_order_customer_address'])){ ?>
									<?=prepareStringHtml($shipment_sorted['order']['dzpro_order_customer_address'])?>
									<?php } ?>
									<?php if(have($shipment_sorted['order']['dzpro_order_customer_city'])){ ?>
									<br /><?=prepareStringHtml($shipment_sorted['order']['dzpro_order_customer_city'])?>
									<?php } ?>
									<?php if(have($shipment_sorted['order']['dzpro_order_customer_state'])){ ?>
									<?=prepareStringHtml($shipment_sorted['order']['dzpro_order_customer_state'])?>,
									<?php } ?>
									<?php if(have($shipment_sorted['order']['dzpro_order_customer_zipcode'])){ ?>
									<?=prepareStringHtml($shipment_sorted['order']['dzpro_order_customer_zipcode'])?>
									<?php } ?>
									<br /><em>Reference:</em> Order #<?=(int)$shipment_sorted['order']['dzpro_order_id']?>
								</p>
							</td>
							<td style="width: 400%; vertical-align: top; padding: 20px;">
								<h2>Recipient:</h2>
								<p>
									<strong><?=prepareStringHtml($shipment_sorted['shipment']['dzpro_order_shipment_name'])?></strong><br />
									<?php if(have($shipment_sorted['shipment']['dzpro_order_shipment_company'])){ ?>
									<?=prepareStringHtml($shipment_sorted['shipment']['dzpro_order_shipment_company'])?><br />
									<?php } ?>
									<?=prepareStringHtml($shipment_sorted['shipment']['dzpro_order_shipment_address'])?><br />
									<?=prepareStringHtml($shipment_sorted['shipment']['dzpro_order_shipment_city'])?>, 
									<?=prepareStringHtml($shipment_sorted['shipment']['dzpro_order_shipment_state'])?>
									<?=prepareStringHtml($shipment_sorted['shipment']['dzpro_order_shipment_zip'])?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
				<hr />
				<?php if(have($shipment_sorted['items'])){ ?>
				<h2>Contents</h2>
				<table cellspacing="0" cellpadding="0" style="width: 100%; border: 1px solid #ccc;">
					<thead>
						<th style="padding: 10px; text-align: left;">PID</th>
						<th style="padding: 10px; text-align: left;">Item</th>
						<th style="padding: 10px; text-align: left;">Quantity</th>
					</thead>
					<tbody>
					<?php foreach($shipment_sorted['items'] as $item){ ?>
						<tr>
							<td style="50px; padding: 10px; border-top: 1px solid #ccc; font-weight: bold; text-align: left;"><?=prepareStringHtml($item['item']['dzpro_order_item_pid'])?></td>
							<td style="padding: 10px; border-top: 1px solid #ccc; text-align: left;"><?=prepareStringHtml($item['item']['dzpro_order_item_name'])?>
								<?php if(isset($item['options']) and have($item['options'])){ foreach($item['options'] as $option){ ?>
								<br />+<?=prepareStringHtml($option['dzpro_order_item_option_name'])?>
								<?php } } ?>							
							</td>
							<td style="70px; padding: 10px; border-top: 1px solid #ccc; font-weight: bold; text-align: left;"><?=(int)$item['item']['dzpro_order_item_quantity']?></td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
				<?php } ?>
			</div>
			<?php if(isset($shipment_sorted['shipment']['dzpro_order_shipment_message']) and have($shipment_sorted['shipment']['dzpro_order_shipment_message'])){ ?>
			<div style="font-family: Verdana; font-size: 12px; padding: 20px 20px; line-height: 150%;">
				<hr />
				<h1>Message</h1>
				<div style="font-size: 42px; line-height: 150%; padding: 50px 50px; border: 4px dashed #ccc; text-align: center;">
					<?=prepareStringHtml($shipment_sorted['shipment']['dzpro_order_shipment_message'])?>
				</div>
			</div>
			<?php } ?>
			<div style="page-break-before: always;"><!-- page break --></div>
		<?php
	}

	/*************************************************************/
	/*********************** BUILD SHIPMENT DETAILS **************/
	/*************************************************************/	
	protected function buildShipmentDetails(){
		if(!have($this->primary_value)){ return false; }
		return mysql_query_get_row(" SELECT * FROM dzpro_orders LEFT JOIN dzpro_users USING ( dzpro_user_id ) LEFT JOIN dzpro_order_shipments USING ( dzpro_order_id ) WHERE dzpro_order_shipment_id = " . (int)$this->primary_value . " ");
	}

	/*************************************************************/
	/*********************** GET SHIPMENT LABEL ******************/
	/*************************************************************/	
	protected function getShipmentLabel($shipment_id = null){
		$extra_sql = have($shipment_id) ? " dzpro_order_shipment_id = '" . mysql_real_escape_string($shipment_id) . "' " : " dzpro_order_shipment_id = '" . mysql_real_escape_string($this->primary_value) . "' "; return mysql_query_get_row(" SELECT * FROM dzpro_order_shipment_labels WHERE " . $extra_sql . " ORDER BY dzpro_order_shipment_label_date_added DESC LIMIT 1 ");
	}

	/*************************************************************/
	/*********************** GET SHIPMENT LABEL BY KEY ***********/
	/*************************************************************/	
	protected function setShippingLabelByKey($key = null){
		mysql_update(" UPDATE dzpro_order_shipment_labels SET dzpro_order_shipment_label_date_added = NOW() WHERE dzpro_order_shipment_label_key = '" . mysql_real_escape_string($key) . "' "); 
		$this->shipment_label = mysql_query_get_row(" SELECT * FROM dzpro_order_shipment_labels WHERE dzpro_order_shipment_label_key = '" . mysql_real_escape_string($key) . "' ORDER BY dzpro_order_shipment_label_date_added DESC LIMIT 1 ");
		return $this->shipment_label;
	}

	/*************************************************************/
	/*********************** INSERT SHIPMENT LABEL ***************/
	/*************************************************************/
	protected function insertShipmentLabel($key = null, $type = null, $message = null, $tracking = null, $errors = null){
		if(!have($key) or !have($this->primary_value)){ return false; }
		return mysql_insert(" 
			INSERT INTO 
				dzpro_order_shipment_labels
			(
				dzpro_order_shipment_id,
				dzpro_order_shipment_label_key,
				dzpro_order_shipment_label_tracking,
				dzpro_order_shipment_label_type,
				dzpro_order_shipment_label_message,
				dzpro_order_shipment_label_error,
				dzpro_order_shipment_label_printed,
				dzpro_order_shipment_label_date_added
			) VALUES (
				'" . mysql_real_escape_string($this->primary_value) . "',
				'" . mysql_real_escape_string($key) . "',
				'" . mysql_real_escape_string($tracking) . "',
				'" . mysql_real_escape_string($type) . "',
				'" . mysql_real_escape_string($message) . "',
				'" . mysql_real_escape_string($errors) . "',
				0,
				NOW()
			)
		");
	}

	/*************************************************************/
	/*********************** UPDATE SHIPMENT LABEL ***************/
	/*************************************************************/
	protected function updateShipmentLabelPrinted($shipment_label_id = null){
		if(!have($shipment_label_id)){ return false; }
		self::emailTrackingNumber($shipment_label_id);
		return mysql_update(" UPDATE dzpro_order_shipment_labels SET dzpro_order_shipment_label_printed = 1 WHERE dzpro_order_shipment_label_id = '" . mysql_real_escape_string($shipment_label_id) . "' ");
	}

	/*************************************************************/
	/*********************** EMAIL TRACKING NUMBER ***************/
	/*************************************************************/
	protected function emailTrackingNumber($shipment_label_id = null){
		$shipment = array(); $shipment_rows = mysql_query_flat(" SELECT * FROM dzpro_orders LEFT JOIN dzpro_order_items USING ( dzpro_order_id ) LEFT JOIN dzpro_order_item_options USING ( dzpro_order_item_id ) LEFT JOIN dzpro_order_shipments USING ( dzpro_order_shipment_id ) LEFT JOIN dzpro_order_shipment_labels USING ( dzpro_order_shipment_id ) WHERE dzpro_order_shipment_label_id = '" . mysql_real_escape_string($shipment_label_id) . "' "); if(have($shipment_rows)){ foreach($shipment_rows as $row){ $shipment['label'] = $row; $shipment['order'] = $row; $shipment['shipment'] = $row; if(have($row['dzpro_order_item_id'])){ $shipment['items'][$row['dzpro_order_item_id']]['item'] = $row; } if(have($row['dzpro_order_item_option_id'])){ $shipment['items'][$row['dzpro_order_item_id']]['options'][$row['dzpro_order_item_option_id']] = $row; } } }
		if(!have($shipment)){ return null; }
			$email_body = '
			<div style="background-color: #eedbb0; padding: 20px 0; text-align: center; line-height: 100%; font-family: Verdana;">
				<div style="padding: 20px; font-family: Verdana; font-size: 12px; width: 560px; margin: 0 auto; text-align: left;">
					<table cellspacing="0" cellpadding="0" style="width: 100%;">
						<tbody>
							<tr>
								<td style="vertical-align: middle; padding: 0 30px 0 0;">
									<a href="http://www.' . HOST_NAME . '" title="' . SITE_NAME . '"><img src="http://www.' . HOST_NAME . '/assets/layout/cheesemart-logo.png" alt="' . SITE_NAME . '" /></a>
								</td>
								<td style="vertical-align: middle">
									<p><strong>Hi ' . prepareStringHtml($shipment['order']['dzpro_order_customer_name']) . ',</strong></p>
									<p>We have just shipped your package to ' . prepareStringHtml($shipment['shipment']['dzpro_order_shipment_name']) . ' at ' . prepareStringHtml($shipment['shipment']['dzpro_order_shipment_address']) . ' in ' . prepareStringHtml($shipment['shipment']['dzpro_order_shipment_city']) . '.</p>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div style="height: 20px;"><!-- spacer --></div>
				<div style="padding: 20px; font-family: Verdana; font-size: 12px; width: 560px; background-color: white; border: 1px solid #412100; margin: 0 auto; text-align: left;">
					<table cellpadding="0" cellspacing="0" style="width: 100%;">
						<tbody>
							<tr>
								<td style="width: 50%; padding: 0; vertical-align: top;">
									<h2 style="padding-bottom: 10px;">Shipment Details</h2>
									<p>
										<strong>Order:</strong> #' . (int)$shipment['order']['dzpro_order_id'] . '<br />
										<strong>Shipment:</strong> #' . (int)$shipment['shipment']['dzpro_order_shipment_id'] . '<br />
										<strong>Tracking:</strong> <a href="http://www.fedex.com/Tracking?cntry_code=us&amp;tracknumber_list=' . $shipment['label']['dzpro_order_shipment_label_tracking'] . '&amp;language=english" title="Track this package" target="_blank">' . $shipment['label']['dzpro_order_shipment_label_tracking'] . '</a><br />
										<strong>Estimated Delivery Date:</strong> <br />' . date('l M jS, Y', strtotime($shipment['shipment']['dzpro_order_shipment_delivery_date'])) . '				
									</p>			
								</td>
								<td style="width: 50%; padding: 0; vertical-align: top;">
									<h2 style="padding-bottom: 10px;">Recipient</h2>
									<p>
										<strong>' . prepareStringHtml($shipment['shipment']['dzpro_order_shipment_name']) . '</strong><br />
			';
			if(have($shipment['shipment']['dzpro_order_shipment_company'])){
				$email_body .= ' 	
										' . prepareStringHtml($shipment['shipment']['dzpro_order_shipment_company']) . '<br />
				';
			}
			$email_body .= '
										' . prepareStringHtml($shipment['shipment']['dzpro_order_shipment_address']) . '<br />
										' . prepareStringHtml($shipment['shipment']['dzpro_order_shipment_city']) . ', 
										' . prepareStringHtml($shipment['shipment']['dzpro_order_shipment_state']) . '
										' . prepareStringHtml($shipment['shipment']['dzpro_order_shipment_zipcode']) . '
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					<div style="height: 20px;"><!-- spacer --></div>
			';
			if(have($shipment['items'])){
				$email_body .= '
					<h2>Contents</h2>
					<br />
					<table cellspacing="0" cellpadding="0" style="width: 100%; border: 1px solid #ccc;">
						<thead>
							<th style="padding: 10px; text-align: left;">PID</th>
							<th style="padding: 10px; text-align: left;">Item</th>
							<th style="padding: 10px; text-align: left;">Quantity</th>
						</thead>
						<tbody>
				';
				foreach($shipment['items'] as $item){
					$email_body .= '
							<tr>
								<td style="50px; padding: 10px; border-top: 1px solid #ccc; font-weight: bold; text-align: left;">
									' . prepareStringHtml($item['item']['dzpro_order_item_pid']) . '
								</td>
								<td style="padding: 10px; border-top: 1px solid #ccc; text-align: left;">
									' . prepareStringHtml($item['item']['dzpro_order_item_name']) . '
					';
					if(isset($item['options']) and have($item['options'])){ foreach($item['options'] as $option){
						$email_body .= '
									<br />+' . prepareStringHtml($option['dzpro_order_item_option_name']) . '
						';
					} }
					$email_body .= '							
								</td>
								<td style="70px; padding: 10px; border-top: 1px solid #ccc; font-weight: bold; text-align: left;">' . (int)$item['item']['dzpro_order_item_quantity'] . '</td>
							</tr>
					';
				}
				$email_body .= '
						</tbody>
					</table>
					<div style="height: 20px;"><!-- spacer --></div>
				';
			if(have($shipment['shipment']['dzpro_order_shipment_message'])){
				$email_body .= '
					<h2>Message</h2>
					<div style="padding: 40px; font-size: 40px; text-align: center; line-height: 150%; border: 4px dashed #eeeeee;">
						' . prepareStringHtml($shipment['shipment']['dzpro_order_shipment_message']) . '
					</div>
				';
			}
		}				
		$email_body .= '
				</div>
			</div>
		';
		$email_subject = 'Order: #' . (int)$shipment['order']['dzpro_order_id'] . ' Shipment: #' . (int)$shipment['shipment']['dzpro_order_shipment_id'] . ' has been shipped';
		if(false !== addEmailToOutbox($shipment['order']['dzpro_order_customer_name'], $shipment['order']['dzpro_order_customer_email'], $email_subject, $email_body)){
			return true;
		}		
		return false;
	}

	/*************************************************************/
	/*********************** GET LABEL ID STRING *****************/
	/*************************************************************/		
	protected function getLabelIdString(){
		if(!have($this->selected_row) or !have($this->primary_value)){ return false; }
		return 'label-' . (int)$this->primary_value . '-' . md5($this->selected_row['dzpro_order_id'] . $this->selected_row['dzpro_order_shipment_type'] . $this->selected_row['dzpro_order_shipment_name'] . $this->selected_row['dzpro_order_shipment_company'] . $this->selected_row['dzpro_order_shipment_phone'] . $this->selected_row['dzpro_order_shipment_address'] . $this->selected_row['dzpro_order_shipment_address_2'] . $this->selected_row['dzpro_order_shipment_city'] . $this->selected_row['dzpro_order_shipment_state'] . $this->selected_row['dzpro_order_shipment_zip'] . $this->selected_row['dzpro_order_shipment_method_type'] . $this->selected_row['dzpro_order_shipment_shipping_date']) . '.png';
	}

	/*************************************************************/
	/*********************** IS THIS A EXPRESS SHIPMENT **********/
	/*************************************************************/		
	protected function isExpressShipment(){
		if(!have($this->selected_row) or !have($this->primary_value)){ return null; }
		return !($this->selected_row['dzpro_order_shipment_method_type'] == 92 or $this->selected_row['dzpro_order_shipment_method_type'] == 90);
	}

	/*************************************************************/
	/*********************** IS THIS A GROUND SHIPMENT ***********/
	/*************************************************************/	
	protected function isGroundShipment(){
		if(!have($this->selected_row) or !have($this->primary_value)){ return null; }
		return ($this->selected_row['dzpro_order_shipment_method_type'] == 92);
	}

	/*************************************************************/
	/*********************** CREATE SHIPPING LABEL ***************/
	/*************************************************************/	
	protected function createShippingLabel(){
	
		//need to create label?
		if(!isset($this->selected_row['dzpro_order_shipment_name']) or (isset($this->selected_row['dzpro_order_shipment_name']) and !have($this->selected_row['dzpro_order_shipment_name']))){
			return false;
		}
	
		// create new fedex object
		$FedExDC = new FedExDC(FEDEX_ACOUNT_NUMBER, FEDEX_METER_NUMBER);

		//ship data
		$ship_data = array(
		    4 => FEDEX_FROM_NAME, 						//SEND FROM NAME
		    5 => FEDEX_FROM_ADDRESS, 					//SEND FROM ADDRESS
		    7 => FEDEX_FROM_CITY,						//SEND FROM CITY
		    8 => FEDEX_FROM_STATE, 						//SEND FROM STATE 
		    9 => FEDEX_FROM_ZIP, 						//SEND FROM ZIPCODE
		    183 => FEDEX_FROM_PHONE, 									//SEND FROM PHONE
		    11 => $this->selected_row['dzpro_order_shipment_company'], 	//SEND TO COMPANY
		    12 => $this->selected_row['dzpro_order_shipment_name'], 	//SEND TO NAME		    
		    13 => $this->selected_row['dzpro_order_shipment_address'], 	//SEND TO ADDRESS		    
		    15 => $this->selected_row['dzpro_order_shipment_city'],	 	//SEND TO CITY
		    16 => $this->selected_row['dzpro_order_shipment_state'], 	//SEND TO STATE
		    17 => $this->selected_row['dzpro_order_shipment_zip'], 		//SEND TO ZIPCODE
		    18 => preg_replace('/[^0-9]+/', null, (have($this->selected_row['dzpro_order_shipment_phone']) ? $this->selected_row['dzpro_order_shipment_phone'] : FEDEX_FROM_PHONE)),	//SEND TO PHONE NUMBER 
			57 => 12,										//PACKAGE HEIGHT
			58 => 12, 										//PACKAGE WIDTH
			59 => 12,										//PACKAGE HEIGHT		    
		    75 => 'LBS',									//WEIGHT UNTIL
		    1273 => '01',														//PACKAGING TYPE - 01 MEANS CUSTOM PACKAGING
		    1274 => $this->selected_row['dzpro_order_shipment_method_type'], 	//SHIPPING TYPE
		    23 => '1', 															//1 MEANS PAYED BY SENDER
		    117 => 'US',							//SEND FROM COUNTRY
		    50 => 'US',								//SEND TO COUNTRY
		    1333 => '1',							//DROPOFF TYPE - 1 MEANS REGULAR PICKUP
		    1401 => number_format(self::getShipmentWeight(), 1),	//PACKAGE WEIGHT
		    116 => 1,								//PACKAGE NUMBER - SHOULD BE 1 (NORMALLY)
		    68 => 'USD',							//DEFAULT CURRENCY
		    1368 => 1,								//LABEL TYPE - 2 IS STANDARD
		    1369 => 1,								//PRINTER TYPE - 1 MEANS LASER
		    1370 => 5,																			//PAPER TYPE 5 FOR PLAIN 7 FOR 4x6
			440 => ($this->selected_row['dzpro_order_shipment_type'] == 'no' or empty($this->selected_row['dzpro_order_shipment_company'])) ? 'Y' : 'N', //IS THIS RESIDENTIAL
			3002 => $this->selected_row['dzpro_order_id'],										//INVOICE NUMBER - ORDER NUMBER
			25 => $this->primary_value,															//REFERENCE NUMBER
		);
		
		//Append shipment date
		$days_out = floor((strtotime($this->selected_row['dzpro_order_shipment_shipping_date']) - time())/(24*60*60)) ;
		if($days_out < 10 and $days_out > 1){
			$ship_data[24] = date('Ymd', strtotime($this->selected_row['dzpro_order_shipment_shipping_date'])); 	//SHIPPING DATE
			$ship_data[1119] = 'Y';																					//SHIPPING IN THE FUTURE? Y OR N
		}
			
		//get express label - ground or express
		$response = (!self::isExpressShipment()) ? $FedExDC->ship_ground($ship_data) : $FedExDC->ship_express($ship_data);
		
		//get label
		if($error = $FedExDC->getError()){ handleError(1, $error . ' ' . $FedExDC->debug_str); }else{ $FedExDC->label(DOCUMENT_ROOT . '/assets/labels/' . self::getLabelIdString()); }

		//update the error mssg
		if(isset($response[3]) and have($response[3])){ 
			$this->shipment_label_error = $response[3];
			self::insertShipmentLabel(self::getLabelIdString(), $this->selected_row['dzpro_order_shipment_method_type'], 'no label created', null, $response[3]);
		}else{ 
			self::insertShipmentLabel(self::getLabelIdString(), $this->selected_row['dzpro_order_shipment_method_type'], 'label created', $response[29], null);			
		}
	
	}

	/*************************************************************/
	/*********************** GET SHIPPING LABEL ******************/
	/*************************************************************/
	public function getShippingLabel(){

		//see if label has been created
		if(false === self::setShippingLabelByKey(self::getLabelIdString())){
			
			//create the shipping label
			self::createShippingLabel();
			 
		}else{ 
		
			//set Shipping label array
			self::setShippingLabelByKey(self::getLabelIdString()); 
		
		}
		
		//return the path
		return '/assets/labels/' . self::getLabelIdString();
	
	}

	/*************************************************************/
	/********************** BUILD SHIPMENT ITEMS *****************/
	/*************************************************************/	
	protected function buildShipmentItemsArray(){
		$items_array = array(); $items = mysql_query_flat(" SELECT * FROM dzpro_order_items LEFT JOIN dzpro_order_item_options USING ( dzpro_order_item_id ) WHERE dzpro_order_shipment_id = " . (int)$this->primary_value . " "); if(have($items)){ foreach($items as $item){ if(have($item['dzpro_order_item_id'])){ $items_array[$item['dzpro_order_item_id']]['item'] = $item; } if(have($item['dzpro_order_item_option_id'])){ $items_array[$item['dzpro_order_item_id']]['options'][$item['dzpro_order_item_option_id']] = $item; } } }
		return $items_array;
	}

	/*************************************************************/
	/*********************** GET SHIPMENT WEIGHT *****************/
	/*************************************************************/	
	protected function getShipmentWeight(){
		$weight = 1.5; if(have($this->shipment_items_array)){ foreach($this->shipment_items_array as $item){ $weight += $item['item']['dzpro_order_item_quantity'] * $item['item']['dzpro_order_item_weight']; } } return $weight;
	}

	/*************************************************************/
	/*********************** SHOW SHIPMENT ITEMS *****************/
	/*************************************************************/	
	public function showShipmentItems(){
		?>
			
			<div class="form_area" style="margin-top: -57px;">
				<div style="height: 31px; padding: 13px 29px 0 29px; font-size: 16px; color: black; text-shadow: -1px 1px 1px #ffffff;">
					<strong>Shipment #<?=(int)$this->shipment_details['dzpro_order_shipment_id']?></strong>
					<a href="/store/orders.php?action=edit&amp;record_id=<?=(int)$this->shipment_details['dzpro_order_id']?>" title="Go to order #<?=(int)$this->shipment_details['dzpro_order_id']?>" style="font-size: 12px; background-color: #222; color: #ffffff; -moz-border-radius: 10px; border-radius: 10px; font-weight: normal; text-shadow: -1px 1px 1px #111; display: block; float: right; padding: 2px 4px;">&nbsp;order details&nbsp;</a>
				</div>
				<div class="input_row inner_shadow">
					<?php if(have($this->shipment_items_array)){ $icount = 1; foreach($this->shipment_items_array as $item){ ?>
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td class="label">
									#<?=strtoupper($item['item']['dzpro_order_item_pid'])?>
								</td>
								<td class="quantity">
									<?=(int)$item['item']['dzpro_order_item_quantity']?>
								</td>
								<td class="plain">
									<div class="inner_holder">
										<?=prepareStringHtml($item['item']['dzpro_order_item_name'])?>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<?php if($icount != sizeof($this->shipment_items_array)){ echo '<div class="line"><!-- line --></div>'; } ?>
					<?php $icount++; } } ?>
				</div>	
			</div><!-- .form_area -->		
		<?php
	}

	/*************************************************************/
	/*********************** SHOW SHIPPING LABEL *****************/
	/*************************************************************/
	public function showShippingLabel(){
		?>
			<div class="form_area" style="margin-top: -27px;">
				<div style="height: 31px; padding: 13px 29px 0 29px; font-size: 16px; color: black; text-shadow: -1px 1px 1px #ffffff;">
					<strong>
						Shipping Label #<?=(int)$this->primary_value?>
					</strong>
					<?php if(isset($this->shipment_label['dzpro_order_shipment_label_printed']) and $this->shipment_label['dzpro_order_shipment_label_printed'] == 1){ ?>
					(Shipped) <a href="http://www.fedex.com/Tracking?cntry_code=us&tracknumber_list=<?=$this->shipment_label['dzpro_order_shipment_label_tracking']?>&language=english" title="FedEx Tracking" target="_blank" style="font-size: 12px; background-color: #222; color: #fff; -moz-border-radius: 10px; border-radius: 10px; font-weight: normal; text-shadow: -1px 1px 1px #000; display: block; float: right; padding: 4px 12px;"><?=$this->shipment_label['dzpro_order_shipment_label_tracking']?></a>
					<?php } ?>
				</div>
				<div class="input_row inner_shadow">
					<?php if(is_file(DOCUMENT_ROOT . $this->shipping_label_path)){ ?>
					<script type="text/javascript" src="/assets/js/shortcut.js"></script>
					<script type="text/javascript">
						<!--
							function printShippingLabel(){
								$.blockUI();
								var uniqueName = new Date(); var windowName = 'Print' + uniqueName.getTime(); var printWindow = window.open('about:blank', windowName, 'width=500, height=300');
								printWindow.document.write('<html><head><title>' + windowName + '</title><body>');
								printWindow.document.write($('#print_label_holder').html());
								printWindow.document.write('</body></html>'); printWindow.focus();
								if(navigator.appName == 'Microsoft Internet Explorer'){ window.print(); }else{ printWindow.print(); }
								setTimeout(function(){
									printWindow.close();
									if(confirm('was printing successful?')){				
	    								$.ajax({
											url : '<?=$_SERVER['REQUEST_URI']?>',
											type : 'POST',
											data : 'ajax=updateMarkPrinted&printed_value=1&shipment_label_id=<?=(int)$this->shipment_label['dzpro_order_shipment_label_id']?>',
											success : function(mssg){
												window.location=window.location;
											}, error : function(error){
												alert('some problem occured');
												$.unblockUI();
											}
										});
									}else{
										$.ajax({
											url : '<?=$_SERVER['REQUEST_URI']?>',
											type : 'POST',
											data : 'ajax=updateMarkPrinted&printed_value=0&shipment_label_id=<?=(int)$this->shipment_label['dzpro_order_shipment_label_id']?>',
											success : function(mssg){
												$.unblockUI();
											}, error : function(error){
												alert('some problem occured');
												$.unblockUI();
											}
										});
									}
								}, 1500);
							}
							$().ready(function(){
								shortcut.add('Ctrl+Shift+0', printShippingLabel);
								$('#print-label-button, #print-label-button-top').click(printShippingLabel);
							});
						//-->
					</script>
					<div style="display: none;" id="print_label_holder">
						<img src="<?=$this->shipping_label_path?>" title="Shipping Label" />
					</div>
					<div class="button_row" style="padding: 10px;">
						<table cellpadding="0" cellspacing="0">
							<tbody>
								<tr>
									<td style="text-align: right;">
										<input name="form_submit" id="print-label-button-top" value="Print Shipping Label" class="save_button" type="submit">
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<div style="overflow-x: hidden; text-align: center;">
						<img src="http://<?=MANAGER_DOMAIN?><?=$this->shipping_label_path?>" title="Shipping Label" style="margin: 0 auto; width: 500px;" />
					</div>
					<div class="button_row" style="padding: 10px;">
						<table cellpadding="0" cellspacing="0">
							<tbody>
								<tr>
									<td style="text-align: right;">
										<input name="form_submit" id="print-label-button" value="Print Shipping Label" class="save_button" type="submit">
									</td>
								</tr>
							</tbody>
						</table>
					</div>				
					<?php }else{ ?>
					<div class="problem_mssg"><?=$this->shipment_label_error?></div>
					<?php } ?>
				</div>
			</div><!-- .form_area -->		
		<?php
	}

	/*************************************************************/
	/*********************** SHOW NO SHIPMENTS BLOCK *************/
	/*************************************************************/	
	public function noShipmentsBlock(){
		if(!have($this->filter_value)){ return null; }
		?>
				<div class="form_area">	
					<div class="input_iframe" id="input_row_iframe_constants_<?=$this->table?>">
						<div class="table_name" style="cursor: default;">
							<table cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td style="width: 170px;">
											<?=date('l M jS, Y', strtotime($this->filter_value))?> NO SHIPMENTS
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
		<?php
	}
	
	/*************************************************************/
	/*********************** SHOW DAILY STATUS *******************/
	/*************************************************************/	
	public function showDailyStats(){
		$daily_stats = array(); $daily_stats['count'] = 0; $result = @mysql_query(" 
			SELECT 
				dzpro_order_shipment_id, 
				dzpro_order_shipment_method_type, 
				COUNT(dzpro_order_shipment_id) AS shipment_type_count 
			FROM 
				dzpro_order_shipments 
			WHERE 
				dzpro_order_shipment_shipping_date = '" . mysql_real_escape_string(date('Y-m-d', strtotime($this->filter_value))) . "' 
			GROUP BY 
				dzpro_order_shipment_method_type 
			ORDER BY 
				dzpro_order_shipment_method_type ASC 
		") or die(mysql_error()); if(mysql_num_rows($result)){ while($row = mysql_fetch_assoc($result)){ $daily_stats['count'] += $row['shipment_type_count']; $daily_stats['stats'][$row['dzpro_order_shipment_method_type']] = $row; } mysql_free_result($result); } if(have($daily_stats['stats'])){
		?>
				<div class="form_area">	
					<div class="input_iframe">
						<div class="table_name" style="cursor: default;">
							<table cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td style="width: 170px;">
											<?=date('l M jS, Y', strtotime($this->filter_value))?> Statistics (<?=$daily_stats['count']?> shipments)
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div style="background-color: #dee3e9; padding: 12px;">
							<?php foreach($daily_stats['stats'] as $stats){ ?>
							<div style="padding: 0 0 22px 0px;">
								<span style="font-size: 13px; color: #222; text-shadow: -1px 1px 1px solid white; font-weight: bold; letter-spacing: 1px;">
									<?=(int)$stats['shipment_type_count']?>
									<?=$this->shipment_type_reference[$stats['dzpro_order_shipment_method_type']]?> 
									<span style="color: #999; font-weight: normal;">(<?=number_format($stats['shipment_type_count'] / $daily_stats['count'] * 100, 0)?>%)</span>
								</span>
								<script type="text/javascript">$().ready(function(){ $('#progressbar<?=md5($stats['dzpro_order_shipment_method_type'])?>').progressbar({ value : <?=number_format(($stats['shipment_type_count'] / $daily_stats['count']) * 100, 0)?> }); });</script>
								<div id="progressbar<?=md5($stats['dzpro_order_shipment_method_type'])?>"><!-- progress bar loads here --></div>								
							</div>
							<?php } ?>
						</div>
					</div>
				</div>
		<?php
		}
	}	

	/*************************************************************/
	/*********************** SHOW SHIPPING LABEL *****************/
	/*************************************************************/
	public function shopShipmentStatusUI(){
		?>
				<div class="form_area">	
					<div class="input_iframe">
						<div class="table_name" style="cursor: default;">
							<table cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td style="width: 170px;">
											Shipment Status Log
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div style="background-color: #dee3e9; padding: 12px;">
							<div class="input_row inner_shadow">
								<table cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td class="label">
												shipment message																
											</td>
											<td class="textarea">
												<div class="inner_holder">
													<textarea class="touched"></textarea>
												</div><!-- .inner_holder -->
											</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div style="padding: 0 0 10px 0;">
									<input type="checkbox" name="check_if_placing_shipment_on_hold" value="true" /> Place shipment on hold
							</div>
							<div class="button_row">
								<table cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td style="text-align: left;">
												<input name="form_submit" id="post-shipment-update-notify" value="Post Update And Notify" class="save_button" type="submit">
											</td>
											<td style="text-align: right;">
												<input name="form_submit" id="post-shipment-update" value="Post Update Only" class="save_button" type="submit">
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

	/*************************************************************/
	/*********************** SHOW SHIPPING LABEL *****************/
	/*************************************************************/
	public function showPrintPackingSlips(){
		if(!have($this->filter_value)){ return null; }
		?>
			<script type="text/javascript">
				<!--
					$().ready(function(){
						$('#print-all-packing-slips').click(function(){			
							$.blockUI();
							$.ajax({
								url : '<?=$_SERVER['REQUEST_URI']?>',
								type : 'POST',
								data : 'ajax=print-all-slips',
								success : function(content){
									if(content != undefined && content.length > 100){
										var uniqueName = new Date(); var windowName = 'Print' + uniqueName.getTime(); var printWindow = window.open('about:blank', windowName, 'width=500, height=300');
										if(printWindow == undefined || printWindow == null){								
											alert('Please enable popups on this browser');
										}else{
											printWindow.document.write('<html><head><title>' + windowName + '</title><body>');
											printWindow.document.write(content);
											printWindow.document.write('</body></html>'); printWindow.focus();
											if(navigator.appName == 'Microsoft Internet Explorer'){ window.print(); }else{ printWindow.print(); }
											printWindow.close();
										}
									}else{
										alert('No packing slips generated.');
									}
									$.unblockUI();
								}
							});
						});
						$('#print-new-packing-slips').click(function(){			
							$.blockUI();
							$.ajax({
								url : '<?=$_SERVER['REQUEST_URI']?>',
								type : 'POST',
								data : 'ajax=print-new-slips',
								success : function(content){
									if(content != undefined && content.length > 100){
										var uniqueName = new Date(); var windowName = 'Print' + uniqueName.getTime(); var printWindow = window.open('about:blank', windowName, 'width=500, height=300');
										if(printWindow == undefined || printWindow == null){
											alert('Please enable popups on this browser');
										}else{
											printWindow.document.write('<html><head><title>' + windowName + '</title><body>');
											printWindow.document.write(content);
											printWindow.document.write('</body></html>'); printWindow.focus();
											if(navigator.appName == 'Microsoft Internet Explorer'){ window.print(); }else{ printWindow.print(); }
											printWindow.close();
										}
									}else{
										alert('No packing slips generated.');
									}
									$.unblockUI();
								}
							});
						});
					});
				//-->
			</script>
			<div class="form_area" style="margin-top: -27px;">
				<div style="height: 31px; padding: 13px 29px 0 29px; font-size: 16px; color: black; text-shadow: -1px 1px 1px #ffffff;">
					<strong>
						Print packing slips for <?=date('l M jS, Y', strtotime($this->filter_value))?>
					</strong>
				</div>
				<div class="input_row inner_shadow">
					<div class="button_row" style="padding: 10px;">
						<table cellpadding="0" cellspacing="0">
							<tbody>
								<tr>
									<td style="text-align: left;">
										<input name="form_submit" id="print-all-packing-slips" value="Print All Packing Slips" class="save_button" type="submit">
									</td>
									<td>
										-- or --
									</td>
									<td style="text-align: right;">
										<input name="form_submit" id="print-new-packing-slips" value="Print New Packing Slips" class="save_button" type="submit">
									</td>
								</tr>
							</tbody>
						</table>
					</div>						
				</div>
			</div><!-- .form_area -->		
		<?php
	}
	
}
?>