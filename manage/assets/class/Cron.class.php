<?php
class Cron {

	function __construct($db){
	
		/****************************************************/
		/*************** CONNECT TO DATABASE ****************/
		/****************************************************/
		$this->db = $db;

		/****************************************************/
		/*************** BUILD CRON STAMP *******************/
		/****************************************************/
		$this->current_cron_stamp = array();
		$this->current_cron_stamp['minutes'] = date('i');
		$this->current_cron_stamp['hours'] = date('H');
		$this->current_cron_stamp['days'] = date('d');
		$this->current_cron_stamp['months'] = date('m');
		$this->current_cron_stamp['weekdays'] = date('w');

		/****************************************************/
		/*************** GET CRON STACK *********************/
		/****************************************************/
		$this->cron = self::getCronStack(); self::explodeIntervalStrings();

	}

	/****************************************************/
	/*************** GET CRON STACK *********************/
	/****************************************************/	
	protected function getCronStack(){
		$result = @mysql_query("SELECT * FROM dzpro_cron WHERE dzpro_cron_active = 1"); if(mysql_num_rows($result) > 0){ $return = array(); while($row = mysql_fetch_assoc($result)){ $return[$row['dzpro_cron_id']] = $row; } mysql_free_result($result); return $return; } 
		return null;
	}

	/****************************************************/
	/*************** EXPLODE STRING *********************/
	/****************************************************/
	protected function explodeIntervalStrings(){
		if(!isset($this->cron) or empty($this->cron)){ return null; }
		foreach($this->cron as $cron_id => $cron_array){
			$this->cron[$cron_id]['interval_array'] = self::explodeInterval($cron_array['dzpro_cron_inverval']);
		}
	}

	/****************************************************/
	/*************** EXPLODE INVERVAL STRING ************/
	/****************************************************/
	protected function explodeInterval($interval = null){
		if(empty($interval)){ return array(); }
		$explode = explode(' ', trim($interval));
		$return = array();
		if(isset($explode[0]) and !empty($explode[0])){ $return['minutes'] = self::explodeIntervalBlockMinutes($explode[0]); }
		if(isset($explode[1]) and !empty($explode[1])){ $return['hours'] = self::explodeIntervalBlockHours($explode[1]); }
		if(isset($explode[2]) and !empty($explode[2])){ $return['days'] = self::explodeIntervalBlockDays($explode[2]); }
		if(isset($explode[3]) and !empty($explode[3])){ $return['months'] = self::explodeIntervalBlockMonths($explode[3]); }
		if(isset($explode[4]) and !empty($explode[4])){ $return['weekdays'] = self::explodeIntervalBlockWeek($explode[4]); }
		return $return;
	}

	/****************************************************/
	/*************** EXPLODE INVERVAL MINUTES ***********/
	/****************************************************/	
	protected function explodeIntervalBlockMinutes($string = null){
		if(empty($string)){ return array(); }
		if($string == '*'){ return array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59); }
		$matches = array(); if(preg_match('/\*\/([0-9]+)/', $string, $matches)){ $return = array(); for($i = 0; $i < 60; $i += (int)$matches[1]){ $return[] = $i; } return $return; }
		if(strpos($string, ',') > 0){ $return_array = array(); $string_explode = explode(',', $string); foreach($string_explode as $stringsub){ if(strpos($stringsub, '-') > 0){ $sub_matches = array(); preg_match('/([0-9]+)\-([0-9]+)/', $stringsub, $sub_matches); for($i = (int)$sub_matches[1]; $i <= (int)$sub_matches[2]; $i++){ $return_array[] = $i; } }else{ $return_array[] = $stringsub; } } return $return_array; }
		if(strpos($string, '-') > 0){ $return_array = array(); $sub_matches = array(); preg_match('/([0-9]+)\-([0-9]+)/', $string, $sub_matches); for($i = (int)$sub_matches[1]; $i <= (int)$sub_matches[2]; $i++){ $return_array[] = $i; } return $return_array; }
		if(is_numeric($string)){ return array($string); }
	}

	/****************************************************/
	/*************** EXPLODE INVERVAL HOURS *************/
	/****************************************************/	
	protected function explodeIntervalBlockHours($string = null){
		if(empty($string)){ return array(); }
		if($string == '*'){ return array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23); }
		$matches = array(); if(preg_match('/\*\/([0-9]+)/', $string, $matches)){ $return = array(); for($i = 0; $i < 24; $i += (int)$matches[1]){ $return[] = $i; } return $return; }
		if(strpos($string, ',') > 0){ $return_array = array(); $string_explode = explode(',', $string); foreach($string_explode as $stringsub){ if(strpos($stringsub, '-') > 0){ $sub_matches = array(); preg_match('/([0-9]+)\-([0-9]+)/', $stringsub, $sub_matches); for($i = (int)$sub_matches[1]; $i <= (int)$sub_matches[2]; $i++){ $return_array[] = $i; } }else{ $return_array[] = $stringsub; } } return $return_array; }
		if(strpos($string, '-') > 0){ $return_array = array(); $sub_matches = array(); preg_match('/([0-9]+)\-([0-9]+)/', $string, $sub_matches); for($i = (int)$sub_matches[1]; $i <= (int)$sub_matches[2]; $i++){ $return_array[] = $i; } return $return_array; }
		if(is_numeric($string)){ return array($string); }
	}
	
	/****************************************************/
	/*************** EXPLODE INVERVAL DAYS **************/
	/****************************************************/	
	protected function explodeIntervalBlockDays($string = null){
		if(empty($string)){ return array(); }
		if($string == '*'){ return array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31); }
		$matches = array(); if(preg_match('/\*\/([0-9]+)/', $string, $matches)){ $return = array(); for($i = 1; $i <= 31; $i += (int)$matches[1]){ $return[] = $i; } return $return; }
		if(strpos($string, ',') > 0){ $return_array = array(); $string_explode = explode(',', $string); foreach($string_explode as $stringsub){ if(strpos($stringsub, '-') > 0){ $sub_matches = array(); preg_match('/([0-9]+)\-([0-9]+)/', $stringsub, $sub_matches); for($i = (int)$sub_matches[1]; $i <= (int)$sub_matches[2]; $i++){ $return_array[] = $i; } }else{ $return_array[] = $stringsub; } } return $return_array; }
		if(strpos($string, '-') > 0){ $return_array = array(); $sub_matches = array(); preg_match('/([0-9]+)\-([0-9]+)/', $string, $sub_matches); for($i = (int)$sub_matches[1]; $i <= (int)$sub_matches[2]; $i++){ $return_array[] = $i; } return $return_array; }
		if(is_numeric($string)){ return array($string); }
	}
	
	/****************************************************/
	/*************** EXPLODE INVERVAL MONTHS ************/
	/****************************************************/	
	protected function explodeIntervalBlockMonths($string = null){
		if(empty($string)){ return array(); }
		if($string == '*'){ return array(1,2,3,4,5,6,7,8,9,10,11,12); }
		$matches = array(); if(preg_match('/\*\/([0-9]+)/', $string, $matches)){ $return = array(); for($i = 1; $i <= 12; $i += (int)$matches[1]){ $return[] = $i; } return $return; }
		if(strpos($string, ',') > 0){ $return_array = array(); $string_explode = explode(',', $string); foreach($string_explode as $stringsub){ if(strpos($stringsub, '-') > 0){ $sub_matches = array(); preg_match('/([0-9]+)\-([0-9]+)/', $stringsub, $sub_matches); for($i = (int)$sub_matches[1]; $i <= (int)$sub_matches[2]; $i++){ $return_array[] = $i; } }else{ $return_array[] = $stringsub; } } return $return_array; }
		if(strpos($string, '-') > 0){ $return_array = array(); $sub_matches = array(); preg_match('/([0-9]+)\-([0-9]+)/', $string, $sub_matches); for($i = (int)$sub_matches[1]; $i <= (int)$sub_matches[2]; $i++){ $return_array[] = $i; } return $return_array; }
		if(is_numeric($string)){ return array($string); }
	}		

	/****************************************************/
	/*************** EXPLODE INVERVAL WEEK **************/
	/****************************************************/	
	protected function explodeIntervalBlockWeek($string = null){
		if(empty($string)){ return array(); }
		if($string == '*'){ return array(0,1,2,3,4,5,6); }
		$matches = array(); if(preg_match('/\*\/([0-9]+)/', $string, $matches)){ $return = array(); for($i = 0; $i < 7; $i += (int)$matches[1]){ $return[] = $i; } return $return; }
		if(strpos($string, ',') > 0){ $return_array = array(); $string_explode = explode(',', $string); foreach($string_explode as $stringsub){ if(strpos($stringsub, '-') > 0){ $sub_matches = array(); preg_match('/([0-9]+)\-([0-9]+)/', $stringsub, $sub_matches); for($i = (int)$sub_matches[1]; $i <= (int)$sub_matches[2]; $i++){ $return_array[] = $i; } }else{ $return_array[] = $stringsub; } } return $return_array; }
		if(strpos($string, '-') > 0){ $return_array = array(); $sub_matches = array(); preg_match('/([0-9]+)\-([0-9]+)/', $string, $sub_matches); for($i = (int)$sub_matches[1]; $i <= (int)$sub_matches[2]; $i++){ $return_array[] = $i; } return $return_array; }
		if(is_numeric($string)){ return array($string); }
	}

	/****************************************************/
	/*************** SELECT CRON JOBS *******************/
	/****************************************************/	
	public function selectCronJobs(){
		if(!isset($this->cron) or empty($this->cron)){ return null; }
		foreach($this->cron as $cron_id => $cron_array){
			if(isset($cron_array['interval_array']) and !empty($cron_array['interval_array']) and isset($cron_array['interval_array']['minutes']) and isset($cron_array['interval_array']['hours']) and isset($cron_array['interval_array']['days']) and isset($cron_array['interval_array']['months']) and isset($cron_array['interval_array']['weekdays']) and in_array($this->current_cron_stamp['minutes'], $cron_array['interval_array']['minutes']) and in_array($this->current_cron_stamp['hours'], $cron_array['interval_array']['hours']) and in_array($this->current_cron_stamp['days'], $cron_array['interval_array']['days']) and in_array($this->current_cron_stamp['months'], $cron_array['interval_array']['months']) and in_array($this->current_cron_stamp['weekdays'], $cron_array['interval_array']['weekdays'])){
				self::loadThisCronJob($cron_array['dzpro_cron_path']);
			}	
		}
	}

	/****************************************************/
	/*************** LOAD THIS CRON JOB *****************/
	/****************************************************/	
	protected function loadThisCronJob($cron_file){
		if(is_file(DOCUMENT_ROOT . CRONJOBS_PATH_PATH . $cron_file)){ include_once DOCUMENT_ROOT . CRONJOBS_PATH_PATH . $cron_file; }
	}
	
}
?>