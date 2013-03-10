<?php

class Calendar extends Form {
	
	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name){
	
		//run form constructor
		parent::__construct($db, $table_name, $parameters = array(), $sticky_fields = array());
		
		//date field
		$this->date_field_name = null;
		
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
		
		//filter value
		$this->filter_value = isset($_GET['filter_value']) ? $_GET['filter_value'] : null;
		
		//get month
		$this->month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
		
		//get year
		$this->year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
		
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
		if(!have($date)){ return false; }
		$result = @mysql_query(" SELECT " . mysql_real_escape_string($this->primary_key) . " FROM " . mysql_real_escape_string($this->table) . " WHERE " . mysql_real_escape_string($this->date_field_name) . " = '" . mysql_real_escape_string($date) . "' LIMIT 1 "); if(mysql_num_rows($result) > 0){ mysql_free_result($result); return true; }
		return false;
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
							<a href="<?=addToGetString(array('year', 'month', 'filter_key', 'filter_value'), array($this_year, $this_month, $this->date_field_name, (int)$this_year . '-' . (int)$this_month . '-' . (int)$this_day), array('record_id'))?>" title="<?=(int)$this_day?>" class="<?=$outofmonth?> <?=$today?> <?=$selected?>">
								<?php if(self::doesDateHaveCalendarItems((int)$this_year . '-' . (int)$this_month . '-' . (int)$this_day)){ ?><span class="active_calendar_items">.</span><?php } ?>
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
	/*********************** ORGANIZE BY TIME ********************/
	/*************************************************************/	
	protected function organizeRecordsByTime($records = array()){
		if(!have($records)){ return null; }
		$return = array(); foreach($records as $record){ $return[substr($record['dzpro_calendar_time'], 0, 2)][(int)$record['dzpro_calendar_id']] = $record; } ksort($return);
		return $return;
	}
	
	/*************************************************************/
	/*********************** PRINT CALENDAR LIST *****************/
	/*************************************************************/
	public function printCalendarListing(){
		if(!have($this->filter_value)){ return null; }
		$show_records = self::buildFromListingArray(); $records_by_time = self::organizeRecordsByTime($show_records); if(have($records_by_time)){
			$frame_class = ($this->prepare_for_iframe === true) ? 'iframe' : null;
			?>
					<ul class="listing_parent <?=$frame_class?>" id="form_listing_parent_<?=$this->table?>">				
			<?php
			if($this->table_start > 0 and !isset($_GET['viewall'])){
				$new_start = (($this->table_start - $this->results_limit) >= 0) ? ($this->table_start - $this->results_limit) : 0;
			?>
						<li class="prev">
							<a href="<?=addToGetString(array('start'), array($new_start))?>" class="prev-link" title="back"><!-- block --></a>
							<?php if($this->table_query_total > mysql_num_rows($result)){ ?> <a href="<?=addToGetString(array('viewall'), array('1'), array('start'))?>" class="view_all" title="view all records">view all <?=(int)$this->table_query_total?> records</a> <?php } ?>
						</li>
			<?php
			}
			foreach($records_by_time as $time_string => $show_records){
				if($time_string > 0){
			?>
						<li style="height: 17px; background: url('/assets/img/manager/listing-header-bg-rep-x.jpg') top left repeat-x transparent; border-top: 1px solid #797d80; border-bottom: 1px solid #9ca0a3; color: white; font-size: 14px; font-weight: bold; padding: 0px; padding-left: 31px; text-shadow: -1px 1px 1px #777;"><?=$time_string?>:00 (<?=$time_slot_jumpers?>)</li>
			<?php 
				}else{
			?>
						<li style="height: 17px; background: url('/assets/img/manager/bucket_top_rep_x.png') center left repeat-x transparent; border-top: 1px solid #44588c; border-bottom: 1px solid #2b3d6a; color: white; font-size: 14px; font-weight: bold; padding: 0px; padding-left: 31px; text-shadow: -1px 1px 1px #777;"><?=date('M j, Y', strtotime($this->filter_value))?></li>
			<?php	
				}
				foreach($show_records as $row){
					$selected = ((int)$row[$this->primary_key] == $this->primary_value) ? 'selected' : null;
				?>
						<li id="list_record_<?=(int)$row[$this->primary_key]?>" class="record_listing <?=$selected?>">
							<a class="delete_icon" href="<?=addToGetString(array('action','record_id','record_search'), array('delete',(int)$row[$this->primary_key], $this->search_query))?>" title="Delete this record"><!-- block --></a>
							<a href="<?=addToGetString(array('action','record_id','record_search'), array('edit',(int)$row[$this->primary_key],$this->search_query))?>" title="<?=htmlentities($row[$this->row_name])?>" class="form_link"><!-- block --></a>
							<?php if($time_string > 0){ ?><span class="date"><strong><?=date('M j', strtotime($row[$this->date_field_name]))?></strong> <?=date('g:ia', strtotime($row[$this->date_field_name] . ' ' . $row[$this->time_field_name]))?></span><?php } ?>
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
							<?php if($this->table_query_total > sizeof($show_records)){ ?> <a href="<?=addToGetString(array('viewall'), array('1'), array('start'))?>" class="view_all" title="view all records">view all <?=(int)$this->table_query_total?> records</a> <?php } ?>
						</li>
			<?php
			}
			?>
					</ul>
			<?php
		}	
	}
	
}
?>