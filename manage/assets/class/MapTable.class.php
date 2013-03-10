<?php

class MapTable {
	
	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
	
		//database connection
		$this->db = $db;
		
		//dropzone id
		$this->required_key = null;
		$this->required_value = null;

		//iframe intend setting
		$this->prepare_for_iframe = false;
		
		//show total count
		$this->showTotalCountInHeader = false;

		//show suggest
		$this->showNameSuggest = true;

		//set table name
		$this->table = $table_name;
		
		//don't allow delete?
		$this->dontAllowDelete = false;
		
		//table information
		$this->straight_fields = array();
		$this->fields_field_key = array();
		$this->fields = array();
				
		//holds the selected row
		$this->selected_row = array();		
				
		//set the tables array var
		$this->tables = array();
		$this->tables_by_table_name = array();
		$this->tried_to_find_tables = array();
		
		//this array holds tables with table name as key with a primary key
		$this->tables_table_key = array();
		
		//if upload field are there we'll load the js and css etc
		$this->need_upload = false;
		
		//if we need html editor
		$this->prepareForHtmlEditor = false;
		
		//save the event
		$this->event = null;
		
		//find the row name and description etc..
		$this->table_comments = null;
		$this->row_name = null;
		$this->row_description = null;
		$this->orderfield = null;
		$this->results_limit = DEFAULT_RESULTS_LIMIT;
		
		//whitelist foreign
		$this->whitelistForeignTables = array();
		
		//blacklist associative tables
		$this->blackListAssociativeTables = array();
		
		//there might be associative tables etc we'll find them and build the needed ui
		$this->associative_map_tables = array();
		
		//get table record start
		$this->table_start = (isset($_GET['start']) and (int)$_GET['start'] > 0) ? (int)$_GET['start'] : 0;
		$this->table_query_total = DEFAULT_RESULTS_LIMIT;
				
		//lets save the foreign table with this primary key
		$this->show_foreign_table = (isset($_GET['action']) and $_GET['action'] == 'new') ? false : true; //no iframes
		$this->foreign_tables_with_primary_key = array();
		
		//set filter
		$this->filter_key = isset($_GET['filter_key']) ? $_GET['filter_key'] : null;
		$this->filter_value = isset($_GET['filter_value']) ? $_GET['filter_value'] : null;
		
		//there might be 'sticky' fields
		$this->sticky_fields = array();
		
		//find the primary key ... and value if its there
		$this->primary_key = null;
		$this->primary_value = isset($_GET['record_id']) ? (int)$_GET['record_id'] : false;

		//search query
		$this->search_query = isset($_GET['record_search']) ? $_GET['record_search'] : false;

		//set class parameters
		if(!empty($parameters)){ foreach($parameters as $param_key => $param_value){ if(!isset($this->$param_key)){ $this->$param_key = $param_value; } } }

		//map indexes
		$this->indexes = self::getIndexInformation();
		
		//form tools
		$this->form_tools = null;
		
		//run field query
		self::getTableInfoQuery();
		
		//get sticky fields
		self::setStickyFields($sticky_fields);		
		
		//map fields
		$this->fields = self::getTableInfo();
		
		//build aternative array
		$this->alternate_fields = self::buildAlternateArray();
		
		//get sticky fields
		self::assignStickyFields();

		//find associated tables
		self::findAssociations(); if(isset($_POST) and !empty($_POST)){ self::mapSubmittedAssociations(); }
		
		//find foreign tables with this primary key
		self::findForeignTablesWithPrimaryKey();

		//if form submitted ... lets check them and if correct insert the record
		if(self::checkValues() and $this->primary_value == false and !isset($_POST['this_is_the_primary_value']) and !isset($_GET['ajax']) and !empty($_POST) and !isset($_POST['ajax'])){ self::insertRecord(); self::whereDoWeGoNext(); }
		
		//if form submitted
		if(self::checkValues() and $this->primary_value > 0 and $_POST['this_is_the_primary_value'] == $this->primary_value and !isset($_GET['ajax']) and !isset($_POST['ajax'])){ self::updateRecord(); }

		//delete row
		if(isset($_POST['form_submit']) and $_POST['form_submit'] == 'Delete Record' and !isset($_GET['ajax']) and !isset($_POST['ajax'])){ self::deleteRecord(); }

		//if there is a primary key ... lets load
		if($this->primary_value){ self::loadFormValuesFromRow(); }

		//build aternative array // rebuild alternate fields
		$this->alternate_fields = self::buildAlternateArray();
		
		//get active field
		$this->active_field = self::getActiveFieldName();

		//build conditional fields array
		$this->conditional_fields = array();

		//do export - csv
		if(isset($_GET['export']) and $_GET['export'] == 'csv'){ header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="' . $this->table . '-' . date('Y-m-d') . '.csv"'); header('Content-Transfer-Encoding: binary'); echo self::doCsvExport(); exit(0); }

		//do export - mysql dump
		if(isset($_GET['export']) and $_GET['export'] == 'mysql-dump'){ header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="' . $this->table . '-' . date('Y-m-d') . '.sql"'); header('Content-Transfer-Encoding: binary'); echo self::doMysqlDump(); exit(0); }
	
		//this is for the ajax intelligent search on the name field
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'name_field_search' and isset($_POST['search_string_' . $this->table]) and !empty($_POST['search_string_' . $this->table])){ self::getAjaxResult($_POST['search_string_' . $this->table]); exit(0); }
		
		//we are reordering
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'reorderSubmit' and isset($_POST['orderString_' . $this->table]) and !empty($_POST['orderString_' . $this->table])){ self::reorderRecords(json_decode(stripslashes($_POST['orderString_' . $this->table]))); exit(0); }

		//changing activity status
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'changeActive' and isset($_POST['record_id']) and isset($_POST['active_value']) and !empty($this->active_field)){ echo self::changeActiveSettingForRecord($_POST['record_id'], $_POST['active_value']); exit(0); }
		
		//get records
		$this->show_records = self::buildFromListingArray();
		
	}

	/*************************************************************/
	/*********************** SET FROM TOOLS ARRAY ****************/
	/*************************************************************/
	public function setFormTools($form_tools_array = array()){
		$this->form_tools = $form_tools_array;
	}

	/*************************************************************/
	/*********************** SHOW NAME SUGGEST *******************/
	/*************************************************************/	
	public function showSuggestOnName($value = true){
		$this->showNameSuggest = $value;
	}
	
	/*************************************************************/
	/*********************** SHOW TOTAL COUNT HEADER *************/
	/*************************************************************/	
	public function showTotalCountInHeader($value = true){
		$this->showTotalCountInHeader = $value;
	}

	/*************************************************************/
	/*********************** SET SHOW MAX RECORDS ****************/
	/*************************************************************/	
	public function setShowMaxResults($max = 12){
		$this->results_limit = $max;
	}

	/*************************************************************/
	/*********************** SET SHOW MAX RECORDS ****************/
	/*************************************************************/		
	public function doMysqlDump(){
 		$result = mysql_query("SELECT * FROM " . mysql_real_escape_string($this->table) . "");
    	$num_fields = mysql_num_fields($result);
       	$return = "DROP TABLE " . mysql_real_escape_string($this->table);
    	$row2 = mysql_fetch_row(mysql_query("SHOW CREATE TABLE " . mysql_real_escape_string($this->table)));
    	$return.= "\n\n" . $row2[1] . ";\n\n";
    	for($i = 0; $i < $num_fields; $i++){
			while($row = mysql_fetch_row($result)){
				$return.= "INSERT INTO " . mysql_real_escape_string($this->table) . " VALUES(";
        		for($j=0; $j<$num_fields; $j++){
					$row[$j] = addslashes($row[$j]);
					$row[$j] = ereg_replace("\n", "\\n", $row[$j]);
					if(isset($row[$j])){ $return .= '"' . $row[$j] . '"' ; }else{ $return.= '""'; }
          			if($j<($num_fields-1)){ $return .= ','; }
				}
        		$return .= ");\n";
      		}
		}
 		$return .= "\n\n\n";
 		registerAdminActivity('mysql dump', $this->table);
 		return $return;
	}

	/*************************************************************/
	/*********************** SET SHOW MAX RECORDS ****************/
	/*************************************************************/		
	public function doCsvExport(){
 		$result = mysql_query("SELECT * FROM " . mysql_real_escape_string($this->table) . "");
    	$num_fields = mysql_num_fields($result);
		$return = null;
    	for($i = 0; $i < $num_fields; $i++){
			while($row = mysql_fetch_row($result)){
        		for($j=0; $j<$num_fields; $j++){
					$row[$j] = addslashes($row[$j]);
					$row[$j] = ereg_replace("\n", "\\n", $row[$j]);
					if(isset($row[$j])){ $return .= '"' . $row[$j] . '"' ; }else{ $return.= '""'; }
          			if($j<($num_fields-1)){ $return .= ','; }
				}
        		$return .= "\n";
      		}
		}
 		$return .= "\n\n\n";
 		registerAdminActivity('csv export', $this->table);
 		return $return;
	}

	/*************************************************************/
	/*********************** WE ARE ADDING A NEW RECORD **********/
	/*************************************************************/
	protected function whereDoWeGoNext(){
		if(have($_POST['form_submit_and_new'])){ header('Location: ' . str_ireplace('&amp;', '&', addToGetString(array('action'), array('new'), array('record_id')))); exit(0); }
		if(have($this->new_insert_id) and defined('STICK_ON_RECORD_AFTER_INSERT')){ header('Location: ' . str_ireplace('&amp;', '&', addToGetString(array('action', 'record_id'), array('edit', $this->new_insert_id), array()))); exit(0); }
	}

	/*************************************************************/
	/*********************** CHANGE ACTIVE VALUE *****************/
	/*************************************************************/		
	protected function changeActiveSettingForRecord($record_id, $active_value){
		if(!have($this->active_field)){ return null; }
		@mysql_query(" UPDATE " . mysql_real_escape_string($this->table) . " SET " . mysql_real_escape_string($this->active_field) . " = " . (int)$active_value . " WHERE " . mysql_real_escape_string($this->primary_key) . " = " . (int)$record_id);
		if(mysql_affected_rows() > 0){ registerAdminActivity('record status change', 'record ' . (int)$record_id . ' ' . (((int)$active_value == 0) ? 'deactivated' : 'activated')); return json_encode(array('id' => (int)$record_id, 'active' => $active_value)); }
		return null;
	}
	
	/*************************************************************/
	/*********************** BLACKLIST ASSOCIATIVE ***************/
	/*************************************************************/		
	public function blackListAssociativeTables($array = array()){
		$this->blackListAssociativeTables = $array;
	}
	
	/*************************************************************/
	/*********************** SET IFRAME PREP SHOW IN IFRAME ******/
	/*************************************************************/	
	public function whitelistForeignTables($array = array()){
		$this->whitelistForeignTables = $array;
	}

	/*************************************************************/
	/*********************** SET IFRAME PREP SHOW IN IFRAME ******/
	/*************************************************************/	
	public function prepareForIframe(){
		$this->prepare_for_iframe = true;
	}

	/*************************************************************/
	/*********************** SHOW IFRAME FOREIGN TABLES **********/
	/*************************************************************/		
	public function setShowForeignTables($value = false){
		$this->show_foreign_table = $value;
	}
	
	/*************************************************************/
	/*********************** SHOW FOREIGN TABLES *****************/
	/*************************************************************/	
	public function showForeignTablesWithPrimaryKey(){
		echo '<div style="background-color: white; padding: 12px; border: 1px solid yellow; margin: 6px;">';
		if(isset($this->foreign_tables_with_primary_key) and !empty($this->foreign_tables_with_primary_key)){
			$tables = array_keys($this->foreign_tables_with_primary_key);
			echo '<h2>Allright - you\'ve got the following tables:</h2>';
			echo '<ul>';
			foreach($tables as $table){
				echo '<li>' . $table . '</li>';
			}
			echo '</ul>';
		}else{
			echo '<h2>No foreign tables with this primary key found.</h2>';
		}
		echo '</div>';
	}

	/*************************************************************/
	/*********************** HANDLE STICKY FIELDS ****************/
	/*************************************************************/		
	public function setStickyFields($array){
		if(isset($array) and !empty($array) and isset($this->straight_fields) and !empty($this->straight_fields)){
			$fields_array = array_keys($this->straight_fields);
			foreach($array as $sticky_field_name => $sticky_field_value){
				if(in_array($sticky_field_name, $fields_array)){
					$this->sticky_fields[$sticky_field_name] = $sticky_field_value;
				}
			}
		}
	}

	/*************************************************************/
	/*********************** ASSIGN STICKY FIELDS ****************/
	/*************************************************************/	
	protected function assignStickyFields(){
		if(isset($this->fields) and !empty($this->fields)){
			$sticky_keys = array_keys($this->sticky_fields);
			foreach($this->fields as $field_key => $field_array){
				if(in_array($field_array['Field'], $sticky_keys)){
					$this->fields[$field_key]['post_value'] = $this->sticky_fields[$field_array['Field']];
				}
			}
		}	
	}

	/*************************************************************/
	/*********************** MAP FIELDS **************************/
	/*************************************************************/		
	protected function getTableInfoQuery(){
		if(!empty($this->table)){
			$return_array = array();
			$sql = "SHOW FULL COLUMNS FROM " . mysql_real_escape_string($this->table);
			$result = mysql_query($sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
			if(mysql_num_rows($result)){
				$counter = 0;
				while($row = mysql_fetch_assoc($result)){	
					$return_array[] = $row;
					$this->straight_fields[] = $row;
					$this->fields_field_key[$row['Field']] = $row; 
				}
				mysql_free_result($result);
			}
			return $return_array;
		}else{
			return array();
		}
	}

	/*************************************************************/
	/*********************** GET TABLE INFO **********************/
	/*************************************************************/
	protected function getTableInfo(){
		if(!empty($this->table) and isset($this->straight_fields) and !empty($this->straight_fields)){
			$return_array = array();
			$counter = 0;
			foreach($this->straight_fields as $row){
				if($row['Key'] == 'PRI' and $this->primary_key == null){
					$this->primary_key = $row['Field'];
				}
				foreach($row as $row_key => $row_val){
					switch($row_key){
						case 'Field':
							if(isset($row_val) and !empty($row_val)){
								$return_array[$counter] = self::specialField($row_key, $row_val);
							}
						break;
						case 'Comment':
							if(!empty($row_val)){
								$comment_pieces = explode('|||', $row_val);
								if(isset($comment_pieces[0]) and !empty($comment_pieces[0])){ $return_array[$counter]['field_name'] = $comment_pieces[0]; }
								if(isset($comment_pieces[1]) and !empty($comment_pieces[1])){ $return_array[$counter]['field_regex'] = $comment_pieces[1]; }
								if(isset($comment_pieces[2]) and !empty($comment_pieces[2])){ $return_array[$counter]['error_mssg'] = $comment_pieces[2]; }
								if(isset($comment_pieces[3]) and !empty($comment_pieces[3])){ $return_array[$counter]['conditional_fields'] = explode('//', $comment_pieces[3]); }
							}
						break;
						case 'Type':
							$type_match_array = array();
							if(preg_match('/varchar\(([0-9]+)\)/', $row_val, $type_match_array)){
								if(isset($type_match_array[1]) and !empty($type_match_array[1])){
									$return_array[$counter]['max_char_count'] = $type_match_array[1];
								}
							}
							$return_array[$counter][$row_key] = $row_val;
						break;
						default:
							$return_array[$counter][$row_key] = $row_val;							
						break;
					}
				}
				//associate indexes
				if(isset($this->indexes[$return_array[$counter]['Field']]) and !empty($this->indexes[$return_array[$counter]['Field']])){
					$return_array[$counter]['index'] = $this->indexes[$return_array[$counter]['Field']];
				}
				//load the post values
				$return_array[$counter]['post_value'] = isset($_POST[$row['Field']]) ? stripAllSlashes($_POST[$row['Field']]) : null;
				$counter++;
			}
			return $return_array;
		}else{
			return array();
		}
	}
	
	/*************************************************************/
	/*********************** BUILD ALTERNATE ARRAY ***************/
	/*************************************************************/		
	protected function buildAlternateArray(){
		if(isset($this->fields) and !empty($this->fields)){
			$return_array = array();
			foreach($this->fields as $key => $field){
				$return_array[$field['Field']] = $field;
			}
			return $return_array;
		}else{
			return array();
		}
	}
	
	/*************************************************************/
	/*********************** GET INDEX INFORMATION ***************/
	/*************************************************************/		
	protected function getIndexInformation(){
		if(!empty($this->table)){
			$return_array = array();
			$sql = "SHOW INDEXES FROM " . mysql_real_escape_string($this->table);
			$result = mysql_query($sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
			if(mysql_num_rows($result)){
				$counter = 0;
				while($row = mysql_fetch_assoc($result)){
					$return_array[$row['Column_name']] = $row;
				}
				mysql_free_result($result);
			}
			return $return_array;
		}else{
			return array();
		}
	}	

	/*************************************************************/
	/*********************** REORDER RECORDS *********************/
	/*************************************************************/		
	protected function reorderRecords($array = array()){
		if(!empty($array) and isset($this->alt_orderfield)){
			foreach($array as $order_int => $primary_value){
				$sql = "
							UPDATE 
								" . mysql_real_escape_string($this->table) . " 
							SET 
								" . mysql_real_escape_string($this->alt_orderfield) . " = " . (int)$order_int . " 
							WHERE 
								" . mysql_real_escape_string($this->primary_key) . " = " . (int)$primary_value . "
						";
				if(isset($this->sticky_fields) and !empty($this->sticky_fields)){
					foreach($this->sticky_fields as $sticky_key => $sticky_value){
						$sql .= "
							AND
								" . mysql_real_escape_string($sticky_key) . " = '" . mysql_real_escape_string($sticky_value) . "'
								";	
					}
				}
				$sql .= "
							AND	
								1 = 1
							LIMIT 
								1
						";
				@mysql_query($sql, $this->db);
				registerAdminActivity('records reordered');
			}
		}
	}
		
	/*************************************************************/
	/*********************** SET ALL POST VALUES *****************/
	/*************************************************************/	
	protected function loadFormValuesFromRow(){
		if(isset($this->fields) and !empty($this->fields) and $this->primary_value > 0){
			$sql = "
						SELECT 
					";
			$field_map = array(); //lets remember the keys
			foreach($this->fields as $key => $field){
				if(self::checkInputField($field)){
					$sql .= $field['Field'] . ",";
					$field_map[$field['Field']] = $key; //lets remember the key associations
				}
			}
			$sql = substr($sql, 0, -1) . " 
						FROM 
							" . mysql_real_escape_string($this->table) . " 
						WHERE 
							" . mysql_real_escape_string($this->primary_key) . " = " . (int)$this->primary_value . "
				";
			if(isset($this->sticky_fields) and !empty($this->sticky_fields)){
				foreach($this->sticky_fields as $sticky_key => $sticky_value){
					$sql .= "
						AND
							" . mysql_real_escape_string($sticky_key) . " = '" . mysql_real_escape_string($sticky_value) . "'
								";	
				}
			}
			$sql .= "
						AND	
							1 = 1
						LIMIT 
							1
					";
			$result = mysql_query($sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
			if(mysql_num_rows($result) > 0){
				while($row = mysql_fetch_assoc($result)){
					foreach($row as $row_field => $row_value){
						$this->fields[$field_map[$row_field]]['post_value'] = (!isset($_POST[$row_field])) ? stripslashes(stripslashes($row_value)) : stripslashes(stripslashes($_POST[$row_field]));
						$this->fields[$field_map[$row_field]]['db_value'] = stripslashes(stripslashes($row_value));
						$this->selected_row = $row;
					}	
				}
				mysql_free_result($result);
				if(isset($this->associative_map_tables) and !empty($this->associative_map_tables)){
					foreach($this->associative_map_tables as $assoc_table_name => $assoc_data_array){
						if(
							isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']) and
							isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field']) and
							isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']) and
							isset($this->associative_map_tables[$assoc_table_name]['assoc_table']['table']) and !empty($this->associative_map_tables[$assoc_table_name]['assoc_table']['table']) and
							isset($this->associative_map_tables[$assoc_table_name]['assoc_table']['native_key']) and !empty($this->associative_map_tables[$assoc_table_name]['assoc_table']['native_key']) and
							isset($this->associative_map_tables[$assoc_table_name]['assoc_table']['foreign_key']) and !empty($this->associative_map_tables[$assoc_table_name]['assoc_table']['foreign_key'])
						){
							$sql_get_assoc = "
												SELECT 
													" . mysql_real_escape_string($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']) . "
												FROM 
													" . mysql_real_escape_string($assoc_table_name) . "
												WHERE 
													" . mysql_real_escape_string($this->primary_key) . " = " . (int)$this->primary_value . "
											";
							$result_get_assoc = mysql_query($sql_get_assoc, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
							if(mysql_num_rows($result_get_assoc) > 0){
								while($result_get_row = mysql_fetch_assoc($result_get_assoc)){
									$this->associative_map_tables[$assoc_table_name]['existing_keys'][$result_get_row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']]] = $result_get_row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']]; 	
								}
								mysql_free_result($result_get_assoc);
							}
						}
					}
				}
			}else{
				//i guess the row wasn't there
				$this->primary_value = false;
				return false;
			}
		}else{
			return false;
		}
	}
	
	/*************************************************************/
	/*********************** SPECIAL FIELD? **********************/
	/*************************************************************/
	protected function specialField($key, $val, $mainTableCheck = true){
		$return_array = array();
		switch(true){
		
			//row name
			case(substr($val, -4) == 'name'):
				$return_array['field_type'] = 'name';
				if($mainTableCheck and !have($this->row_name)){ $this->row_name = $val; }
			break;
			
			//row description
			case(substr($val, -11) == 'description'):
				$return_array['field_type'] = 'description';
				if($mainTableCheck and !have($this->row_description)){ $this->row_description = $val; }
			break;
			
			//row description alternatives
			case(substr($val, -7) == 'address'):
				$return_array['field_type'] = 'description';
				if($mainTableCheck and !have($this->row_description)){ $this->row_description = $val; }
			break;			
		
			//states select field
			case(substr($val, -5) == 'state'):
				$return_array['field_type'] = 'state';
				if($mainTableCheck){ $return_array['states_array'] = $this->states; }
			break;
			
			//this is a file upload
			case(substr($val, -4) == '_pdf'):
				$return_array['field_type'] = 'file';
				if($mainTableCheck){ $this->need_upload = true; }
			break;

			//this is a file upload
			case(substr($val, -4) == 'file'):
				$return_array['field_type'] = 'file';
				if($mainTableCheck){ $this->need_upload = true; }
			break;

			//this is a file upload
			case(substr($val, -4) == 'html'):
				$return_array['field_type'] = 'html';
				if($mainTableCheck){ $this->prepareForHtmlEditor = true; }
			break;
					
			//this is a image upload
			case(substr($val, -5) == 'image'):
				$return_array['field_type'] = 'image';
				if($mainTableCheck){ $this->need_upload = true; }
			break;
			
			//this is a date added field
			case(substr($val, -10) == 'date_added'):
				$return_array['field_type'] = 'date_added';
				if($mainTableCheck){ $this->orderfield = $val; $this->date_added_field = $val; $this->order_by_direction = ' DESC'; }
			break;
			
			//this is a order field
			case(substr($val, -11) == '_orderfield'):
				$return_array['field_type'] = 'orderfield';
				if($mainTableCheck){ $this->alt_orderfield = $val; $this->orderfield = $val; $this->order_by_direction = ' ASC'; $this->need_sortable = true; }
			break;
			
			//this is an orderfield
			case(substr($val, -6) == '_order'):
				$return_array['field_type'] = 'orderfield';
				if($mainTableCheck){ $this->alt_orderfield = $val; $this->orderfield = $val; $this->order_by_direction = ' ASC'; $this->need_sortable = true; }
			break;
			
			//alternate lookup
			case(substr($val, -3) == '_id' and substr($val, -10) != '_parent_id' and $mainTableCheck):
				if(false !== ($related_options_array = self::findRelatedTableInfo($val))){
					$return_array['related_options'] = $related_options_array;
				}
			break;
			
			//parent id lookup on same table
			case(substr($val, -10) == '_parent_id'  and $mainTableCheck):
				$return_array['parent_options'] = true;
			break;
			
		}
		$return_array[$key] = $val;
		return $return_array;
	}
	
	/*************************************************************/
	/*********************** FIND RELATED TABLE INFO *************/
	/*************************************************************/
	protected function mapAllTables(){
		if(!isset($this->tables) or empty($this->tables)){
			$sql = "SHOW TABLE STATUS";
			$result = mysql_query($sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
			if(mysql_num_rows($result) > 0){
				while($row = mysql_fetch_assoc($result)){
					$table = $row['Name'];
					if($this->table == $row['Name']){ $this->table_comments = $row['Comment']; }
					$this->table_comments_array[$row['Name']] = $row['Comment'];
					if(isset($table) and substr($table, 0, strlen(TABLES_PREPEND)) == TABLES_PREPEND and $table != $this->table){
						$sql_table = "SHOW FULL COLUMNS FROM " . mysql_real_escape_string($table);
						$result_table = mysql_query($sql_table, $this->db) or handleError(1, 'sql:' . $sql_table . ' error:' . mysql_error());
						if(mysql_num_rows($result_table) > 0){
							$this_array_key = 'temp_key';
							$found_key = false;
							$just_fields_array = array();
							$just_fields_count = 0;
							$this->no_key_tables[$table] = array();
							$this->tables_table_key[$table] = array();
							$this->tables[$this_array_key] = array();
							while($row_table = mysql_fetch_assoc($result_table)){
								$this->tables_by_table_name[$table][$row_table['Field']] = $row_table;
								if($row_table['Key'] == 'PRI'){
									$this->tables[$row_table['Field']] = $this->tables[$this_array_key];
									unset($this->tables[$this_array_key]);
									$this_array_key = $row_table['Field'];
									$found_key = true;
								}
								$just_fields_array = $row_table;
								foreach($row_table as $key_table => $value_table){
									$this->tables[$this_array_key][$row_table['Field']][$key_table] = $value_table;
									$this->no_key_tables[$table][$row_table['Field']][$key_table] = $value_table;
									$this->tables_table_key[$table][$row_table['Field']][$key_table] = $value_table;
								}
								$just_fields_count++;
							}
							mysql_free_result($result_table);
							if($found_key === true){
								unset($this->no_key_tables[$table]);
							}else{
								unset($this->tables_table_key[$table]);
							}
							$this->tables[$this_array_key]['table'] = $table;
						}
					}
				}	
				mysql_free_result($result);
			}
		}	
	}
	
	/*************************************************************/
	/*********************** FILTER OUT BAD STICKY FIELDS ********/
	/*************************************************************/
	protected function filterStickyFieldsForTable($table_name){
		$return_array = array();
		if(!isset($this->tried_to_find_tables[$table_name])){ self::mapAllTables($table_name); }
		if(isset($this->tables_by_table_name[$table_name]) and !empty($this->tables_by_table_name[$table_name])){
			$fields_array = array();
			foreach($this->tables_by_table_name[$table_name] as $row_key => $row_val){
				$fields_array[] = $row_val['Field'];
			}
			if(isset($this->sticky_fields) and !empty($this->sticky_fields)){
				foreach($this->sticky_fields as $sticky_field_name => $sticky_field_value){
					if(in_array($sticky_field_name, $fields_array)){
						$return_array[$sticky_field_name] = $sticky_field_value;
					}
				}
			}
		}
		return $return_array;
	}
	
	/*************************************************************/
	/*********************** FIND RELATED TABLE INFO *************/
	/*************************************************************/
	protected function findRelatedTableInfo($field_name){
		//Map tables and fields
		if(!isset($this->tried_to_find_tables[$field_name])){ self::mapAllTables($field_name); }
		if(isset($this->tables[$field_name]) and !empty($this->tables[$field_name])){
			$return_array = array();
			foreach($this->tables[$field_name] as $row_key => $row_val){
				$return_array[$row_key] = self::specialField($row_key, $row_val['Field'], false);
				if(isset($row_val) and is_array($row_val)){
					foreach($row_val as $field_key => $field_val){
						$return_array[$row_key][$field_key] = $field_val;
					}
				}else{
					$return_array[$row_key] = $row_val;
				}						
			}
			foreach($return_array as $field_name => $field_array){
				switch(true){
					case(isset($field_array['Key']) and $field_array['Key'] == 'PRI'):
						$this_primary_key = $field_array['Field'];
					break;
					case(isset($field_array['field_type']) and $field_array['field_type'] == 'name'):
						$this_name_field = $field_array['Field'];
					break;
					case(isset($field_array['field_type']) and $field_array['field_type'] == 'description'):
						$this_description_field = $field_array['Field'];
					break;
					case(isset($field_array['field_type']) and $field_array['field_type'] == 'orderfield'):
						$this_order_by_string = " ORDER BY " . mysql_real_escape_string($field_array['Field']) . " ASC"; 
					break;
					case(!isset($this_order_by_string) and isset($field_array['field_type']) and $field_array['field_type'] == 'date_added'):
						$this_order_by_string = " ORDER BY " . mysql_real_escape_string($field_array['Field']) . " DESC"; 
					break;
				}
			}
			$filtered_sticky_fields = self::filterStickyFieldsForTable($return_array['table']);
			if(isset($filtered_sticky_fields) and !empty($filtered_sticky_fields)){
				$sticky_sql = "";
				foreach($filtered_sticky_fields as $sticky_field_name => $sticky_field_value){
					$sticky_sql .= "
						" . mysql_real_escape_string($sticky_field_name) . " = '" . mysql_real_escape_string($sticky_field_value) . "'		
					AND";
				}
				$sticky_sql .= "
						1 = 1
								";
			}else{
				$sticky_sql = "
						1 = 1				
								";
			}
			$sql = "
						SELECT 
							" . mysql_real_escape_string($this_primary_key) . " AS option_key";
			if(isset($this_name_field) and !empty($this_name_field)){
				$sql .= ",
							" . mysql_real_escape_string($this_name_field) . " AS option_name";
			}
			if(isset($this_description_field) and !empty($this_description_field)){
				$sql .= ",
							" . mysql_real_escape_string($this_description_field) . " AS option_description";
			}
			$sql .= "
						FROM 
							" . mysql_real_escape_string($return_array['table']) . "
						WHERE	
							" . $sticky_sql . "
					";
			if(isset($this_order_by_string) and !empty($this_order_by_string)){
				$sql .= $this_order_by_string;
			}else{
				$sql .= " ORDER BY 
							" . mysql_real_escape_string($this_name_field) . " ASC";
			}
			$result = mysql_query($sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
			if(mysql_num_rows($result) > 0){
				$return_this_options_array = array();
				while($row = mysql_fetch_assoc($result)){
					$return_this_options_array[$row['option_key']] = $row;
				}
				mysql_free_result($result);
				return $return_this_options_array;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	/*************************************************************/
	/*********************** MAP ASSOCIATIONS ********************/
	/*************************************************************/	
	protected function findAssociations(){
		if(!isset($this->tried_to_find_tables)){ self::mapAllTables(); }
		/*
			//$this->associative_map_tables[$assoc_table_name]['foreign_table']['table'] = array();
			//$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name'] = '';
			//$this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field'] = '';
			//$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key'] = '';
			//$this->associative_map_tables[$assoc_table_name]['foreign_table']['orderfield'] = '';
			//$this->associative_map_tables[$assoc_table_name]['assoc_table']['table'] = array();
			//$this->associative_map_tables[$assoc_table_name]['assoc_table']['table_name'] = '';
			//$this->associative_map_tables[$assoc_table_name]['assoc_table']['native_key'] = $this->primary_key;
			//$this->associative_map_tables[$assoc_table_name]['assoc_table']['foreign_key'] = '';
			//$this->associative_map_tables[$assoc_table_name]['assoc_table']['orderfield'] = '';
		*/
		if(!empty($this->no_key_tables)){
			foreach($this->no_key_tables as $table_name => $table_array){
				if(sizeof($table_array) <= 4){
					$found_primary_key = false;
					foreach($table_array as $field_name => $field_array){
						if($field_name == $this->primary_key){
							$found_primary_key = true;
						}
						if(isset($table_name) and isset($field_name) and isset($this->tables[$field_name]['table']) and $field_name != $this->primary_key and substr($field_array['Type'], 0, 3) == 'int' and substr($field_name, -3) == '_id'){
							$this->associative_map_tables[$table_name]['foreign_table']['table_key'] = $field_name;	
							$this->associative_map_tables[$table_name]['foreign_table']['table_name'] = $this->tables[$field_name]['table'];
							$this->associative_map_tables[$table_name]['foreign_table']['table'] = $this->tables[$field_name];
							$this->associative_map_tables[$table_name]['assoc_table']['table'] = $table_array;
							$this->associative_map_tables[$table_name]['assoc_table']['foreign_key'] = $field_name;
						}
						if(substr($field_array['Type'], 0, 3) == 'int' and substr($field_name, -11) == '_orderfield'){
							$this->associative_map_tables[$table_name]['assoc_table']['orderfield'] = $field_name;
						}
					}
					if($found_primary_key === true){
						$this->associative_map_tables[$table_name]['assoc_table']['table_name'] = $table_name;
						$this->associative_map_tables[$table_name]['assoc_table']['native_key'] = $this->primary_key;
					}
				}
				//now lets find the foreign tables name
				if(isset($this->associative_map_tables[$table_name]['foreign_table']['table']) and !empty($this->associative_map_tables[$table_name]['foreign_table']['table'])){
					foreach($this->associative_map_tables[$table_name]['foreign_table']['table'] as $foreign_field_name => $foreign_field_array){
						if(substr($foreign_field_array['Field'], -5) == '_name' and substr($foreign_field_array['Type'], 0, 8) == 'varchar('){
							$this->associative_map_tables[$table_name]['foreign_table']['show_field'] = $foreign_field_name;
						}
						if(!isset($this->associative_map_tables[$table_name]['foreign_table']['show_field']) and substr($foreign_field_array['Type'], 0, 8) == 'varchar('){
							$this->associative_map_tables[$table_name]['foreign_table']['show_field'] = $foreign_field_name;
						}
						if(substr($foreign_field_array['Field'], -11) == '_orderfield' and substr($foreign_field_array['Type'], 0, 4) == 'int('){
							$this->associative_map_tables[$table_name]['foreign_table']['orderfield'] = $foreign_field_name . ' ASC';
						}
						if(substr($foreign_field_array['Field'], -11) == '_date_added' and substr($foreign_field_array['Type'], 0, 8) == 'datetime' and !isset($this->associative_map_tables[$table_name]['foreign_table']['orderfield'])){
							$this->associative_map_tables[$table_name]['foreign_table']['orderfield'] = $foreign_field_name . ' DESC';
						}
					}
				}
			}
		}
	}

	/*************************************************************/
	/*********************** MAP SUBMITTED ASSOCIATIONS **********/
	/*************************************************************/		
	protected function mapSubmittedAssociations(){
		if(isset($_POST) and !empty($_POST)){
			if(isset($this->associative_map_tables) and !empty($this->associative_map_tables)){
				foreach($this->associative_map_tables as $assoc_table_name => $assoc_data_array){
					if(
						isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']) and
						isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field']) and
						isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']) and
						isset($this->associative_map_tables[$assoc_table_name]['assoc_table']['table']) and !empty($this->associative_map_tables[$assoc_table_name]['assoc_table']['table']) and
						isset($this->associative_map_tables[$assoc_table_name]['assoc_table']['native_key']) and !empty($this->associative_map_tables[$assoc_table_name]['assoc_table']['native_key']) and
						isset($this->associative_map_tables[$assoc_table_name]['assoc_table']['foreign_key']) and !empty($this->associative_map_tables[$assoc_table_name]['assoc_table']['foreign_key'])
					){
						foreach($_POST as $post_key => $post_value){
							if(substr($post_key, 0, strlen($assoc_table_name)) == $assoc_table_name){
								$foreign_key_value = substr($post_key, strlen($assoc_table_name) + 1);
								$this->associative_map_tables[$assoc_table_name]['foreign_key_values'][$foreign_key_value] = $foreign_key_value;
							}
						}
					} //end if
				} //foreach
			} //check if there is a associative
		} //end isset($_POST)
	}

	/*************************************************************/
	/*********************** FIND FOREIGN TABLES *****************/
	/*************************************************************/
	protected function findForeignTablesWithPrimaryKey(){
		if(isset($this->tables_table_key) and !empty($this->tables_table_key)){
			foreach($this->tables_table_key as $table_name => $table_array){
				if(isset($table_array) and !empty($table_array)){
					foreach($table_array as $field_name => $field_array){
						if(isset($this->primary_key) and !empty($this->primary_key) and $field_name == $this->primary_key){
							$this->foreign_tables_with_primary_key[$table_name] = $table_array;
						}
					}
				}
			}
		}
	}

	/*************************************************************/
	/*********************** CHECK FIELD *************************/
	/*************************************************************/
	protected function checkInputField($field){
		if(isset($field) and !empty($field)){
			switch(true){
				//primary key
				case($field['Key'] == 'PRI'):
					return false;
				break;
				
				//password - require password when not super user - otherwise keep hash unaltered
				case(substr($field['Type'], 0, 7) == 'varchar' and (substr($field['Field'], -9) == '_password' or substr($field['Field'], -3) == '_pw')):
					if(!have($field['post_value'])){ if(self::isSuperUser() and isset($_POST[$field['Field'] . '_hash']) and have($_POST[$field['Field'] . '_hash'])){ $field['post_value'] = stripslashes($_POST[$field['Field'] . '_hash']); } return $field; }else{ $field['post_value'] = saltString($field['post_value']); }
					return $field;
				break;				
				
				//float value
				case(substr($field['Type'], 0, 5) == 'float'):
					return $field;
				break;
				
				//enum
				case(substr($field['Type'], 0, 4) == 'enum'):
					return $field;
				break;
				
				//tinyint
				case(substr($field['Type'], 0, 7) == 'tinyint'):
					return $field;
				break;

				//int
				case(substr($field['Type'], 0, 3) == 'int' and $field['field_type'] != 'orderfield'):
					return $field;
				break;

				//datetime
				case(substr($field['Type'], 0, 8) == 'datetime' and substr($field['Field'], -11) != '_date_added'):
					return $field;
				break;
				
				//time
				case($field['Type'] == 'time'):
					return $field;
				break;
				
				//date
				case($field['Type'] == 'date'):
					$field['post_value'] = date('Y-m-d', strtotime($field['post_value']));
					return $field;
				break;
				
				//find varchar(12)
				case(preg_match('/^varchar\(([0-9]+)\)$/', $field['Type'])):
					return $field;
				break;
				
				//find text area
				case($field['Type'] == 'text'):
					return $field;
				break;
				
				//find date added
				case(substr($field['Field'], -10) == 'date_added'):
					$field['post_value'] = date('Y-m-d H:i:s');
					$return = !isset($_POST['this_is_the_primary_value']) ? $field : false;
					return $return;
				break;
				
				//find timestamp
				case($field['Type'] == 'timestamp'):
					return false;
				break;
				
				//default
				default:
					return false;
				break;
			}
		}else{
			return false;
		}
	}
	
	/*************************************************************/
	/*********************** CHECK VALUES ************************/
	/*************************************************************/	
	protected function checkValues(){
		$all_good = false;
		if(isset($this->fields) and !empty($this->fields)){
			$all_good = true;
			foreach($this->fields as $field){
				if(isset($field['field_regex']) and strlen($field['field_regex']) > 3){
					if(!preg_match($field['field_regex'], $field['post_value'])){
						$all_good = false;
					}
				}
				if(isset($field['field_regex']) and $field['field_regex'] == '*'){
					if(!isset($field['post_value']) or empty($field['post_value'])){
						$all_good = false;
					}
				}
			}
		}
		if(false === $all_good and isset($_POST) and !empty($_POST)){
			$this->event = 'Some field(s) are invalid';
		}
		return $all_good;
	}

	/*************************************************************/
	/*********************** INSERT ASSOCIATIONS *****************/
	/*************************************************************/
	protected function insertAssociations($primary_key_value, $delete_only = false){
		if(isset($this->associative_map_tables) and !empty($this->associative_map_tables)){
			foreach($this->associative_map_tables as $assoc_table_name => $assoc_data_array){
				if($this->associative_map_tables[$assoc_table_name]['assoc_table']['native_key'] == $this->primary_key){
					$sql = "
								DELETE FROM 
									" . mysql_real_escape_string($assoc_table_name) . "
								WHERE 
									" . mysql_real_escape_string($this->primary_key) . " = '" . mysql_real_escape_string($primary_key_value) . "'
							";
					mysql_query($sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
					if(
						isset($this->associative_map_tables[$assoc_table_name]['foreign_key_values']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_key_values']) and 
						false === $delete_only
					){
						$insert_sql = "
										INSERT INTO 
											" . mysql_real_escape_string($assoc_table_name) . "
										(
											" . mysql_real_escape_string($this->primary_key) . ",
											" . mysql_real_escape_string($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']) . "
										) VALUES
									";
						foreach($this->associative_map_tables[$assoc_table_name]['foreign_key_values'] as $key_value){
							$insert_sql .= "
										(	
											'" . mysql_real_escape_string($primary_key_value) . "',
											'" . mysql_real_escape_string($key_value) . "'	
										),";
						}
						$insert_sql = substr($insert_sql, 0, -1);
						mysql_query($insert_sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());	
					}
				}			
			}
		}
	}
	
	/*************************************************************/
	/*********************** INSERT RECORD ***********************/
	/*************************************************************/
	protected function insertRecord(){
		if(isset($this->fields) and !empty($this->fields)){
			$sql = "
				INSERT INTO 
					" . $this->table . "
				(
					";
			foreach($this->fields as $field){
				if(false !== self::checkInputField($field)){
					$sql .= mysql_real_escape_string($field['Field']) . ",";
				}
			}
			$sql = substr($sql, 0, -1) . "
				) VALUES (
			";
			foreach($this->fields as $field){
				if(false !== ($return = self::checkInputField($field))){
					$sql .= "'" . mysql_real_escape_string($return['post_value']) . "',";			
				}
			}
			$sql = substr($sql, 0, -1) . "
				)
			";
			$result = mysql_query($sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
			if(mysql_insert_id() > 0){
				$this->event = 'inserted';
				$this->new_insert_id = mysql_insert_id();
				self::insertAssociations(mysql_insert_id());
				registerAdminActivity('new record created', self::getRecordDescription());
				return true;
			}else{
				$this->event = 'not inserted';
				return false;
			}
		}
	}

	/*************************************************************/
	/*********************** GET RECORD DESCRIPTION **************/
	/*************************************************************/	
	protected function getRecordDescription(){
		$return = null;
		if(have($this->fields)){
			foreach($this->fields as $field){
				if(
					substr($field['Type'], 0, 7) == 'varchar' or 
					substr($field['Type'], 0, 4) == 'char' or 
					substr($field['Type'], 0, 4) == 'enum' or 
					substr($field['Type'], 0, 4) == 'text'
				){
					$return .= '<strong>' . limitString(strip_tags($field['field_name']), 40) . '</strong>: ' . limitString(strip_tags($field['post_value']), 160) . '<br />';	
				}
			}
		}
		return $return;
	}
	
	/*************************************************************/
	/*********************** UPDATE RECORD ***********************/
	/*************************************************************/
	protected function checkForPassword(){
		$return = true; if(!self::isSuperUser() and isset($this->fields) and !empty($this->fields)){ $passwords = array(); foreach($this->fields as $field){ if(substr($field['Type'], 0, 7) == 'varchar' and (substr($field['Field'], -9) == '_password' or substr($field['Field'], -3) == '_pw')){ $passwords[] = $field; } } if(have($passwords)){ $return = false; foreach($passwords as $pw_field){ if(isset($_POST[$pw_field['Field'] . '_hash']) and have($_POST[$pw_field['Field'] . '_hash']) and $_POST[$pw_field['Field'] . '_hash'] == saltString($pw_field['post_value'])){ $return = true; }	} } }
		return $return;
	}	

	/*************************************************************/
	/*********************** UPDATE RECORD ***********************/
	/*************************************************************/	
	protected function updateRecord(){
		if(!self::checkForPassword()){ $this->event = 'password required'; return false; }
		if(isset($this->fields) and !empty($this->fields)){
			if(isset($this->sticky_fields) and !empty($this->sticky_fields)){
				$sticky_sql = "";
				foreach($this->sticky_fields as $sticky_field_name => $sticky_field_value){
					$sticky_sql .= "
						" . mysql_real_escape_string($sticky_field_name) . " = '" . mysql_real_escape_string($sticky_field_value) . "'		
					AND";
				}
				$sticky_sql .= "
						1 = 1
								";
			}else{
				$sticky_sql = "
						1 = 1				
								";
			}
			$sql = "
				UPDATE 
					" . $this->table . "
				SET
			";
			foreach($this->fields as $field){
				if(false !== ($this_field = self::checkInputField($field))){
					$sql .= mysql_real_escape_string($this_field['Field']) . " = '" . mysql_real_escape_string($this_field['post_value']) . "',";
				}
			}
			$sql = substr($sql, 0, -1) . "
				WHERE
					" . mysql_real_escape_string($this->primary_key) . " = '" . (int)$this->primary_value . "'
				AND 
					" . $sticky_sql . "
				LIMIT 
					1
			";
			$result = mysql_query($sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
			self::insertAssociations($this->primary_value);
			if(mysql_affected_rows() > 0){
				registerAdminActivity('record updated', self::getRecordDescription());
				$this->event = 'updated';
				return true;
			}else{
				$this->event = 'not updated';
				return false;
			}
		}	
	}
	
	/*************************************************************/
	/*********************** DELETE RECORD ***********************/
	/*************************************************************/		
	protected function deleteRecord(){
		if(isset($this->sticky_fields) and !empty($this->sticky_fields)){
			$sticky_sql = "";
			foreach($this->sticky_fields as $sticky_field_name => $sticky_field_value){
				$sticky_sql .= "
					" . mysql_real_escape_string($sticky_field_name) . " = '" . mysql_real_escape_string($sticky_field_value) . "'		
				AND";
			}
			$sticky_sql .= "
					1 = 1
							";
		}else{
			$sticky_sql = "
					1 = 1				
							";
		}
		$sql = "
			DELETE FROM 
				" . $this->table . "
			WHERE 
				" . mysql_real_escape_string($this->primary_key) . " = '" . (int)$this->primary_value . "'
			AND 
				" . $sticky_sql . "
			LIMIT 
				1
		";
		$result = mysql_query($sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
		if(mysql_affected_rows() > 0){
			$this->event = 'deleted';
			registerAdminActivity('record deleted', self::getRecordDescription());
			self::insertAssociations($this->primary_value, true); //delete only
			return true;
		}else{
			$this->event = 'not deleted';
			return false;
		}
	}

	/*************************************************************/
	/*********************** PRINT TABLE TITLE *******************/
	/*************************************************************/
	public function getTableName(){
		if(!empty($this->table_comments)){
			return $this->table_comments;
		}else{
			return str_ireplace(array('-', '_', TABLES_PREPEND), array(' ', ' ', ''), $this->table);
		}
	}

	/*************************************************************/
	/*********************** GET ACTIVE FIELD NAME ***************/
	/*************************************************************/
	protected function getActiveFieldName(){
		if(!empty($this->fields)){
			foreach($this->fields as $field){
				if(substr($field['Field'], -6) == 'active' and substr($field['Type'], 0, 10) == 'tinyint(1)'){
					return $field['Field'];
				}
			}
		}
		return null;
	}

	/*************************************************************/
	/*********************** GET ALTERNATIVE NAME ****************/
	/*************************************************************/	
	protected function getAternativeName($exclude_fields = array()){
		if(!empty($this->fields)){
			foreach($this->fields as $field){
				if(!in_array($field['Field'], $exclude_fields) and 
					substr($field['Field'], -8) != 'password' and 
					substr($field['Field'], -5) != 'image' and
					substr($field['Field'], -4) != 'file' and
					(
						substr($field['Type'], 0, 7) == 'varchar' or 
						substr($field['Type'], 0, 4) == 'char' or 
						substr($field['Type'], 0, 4) == 'enum' or 
						substr($field['Type'], 0, 4) == 'text'
					)
				){ 
					return $field['Field'];
				}
			}
			foreach($this->fields as $field){
				if(!in_array($field['Field'], $exclude_fields) and 
					(
						substr($field['Type'], 0, 7) == 'varchar' or 
						substr($field['Type'], 0, 4) == 'char' or 
						substr($field['Type'], 0, 4) == 'enum' or 
						substr($field['Type'], 0, 4) == 'text'
					)
				){ 
					return $field['Field'];
				}
			}
			foreach($this->fields as $field){
				if(!in_array($field['Field'], $exclude_fields) and 
					$field['Key'] != 'PRI' and 
					(
						substr($field['Type'], 0, 3) == 'int' or 
						substr($field['Type'], 0, 4) == 'date' or 
						substr($field['Type'], 0, 5) == 'float'
					)
				){ 
					return $field['Field'];
				}
			}
			return null;
		}else{
			return null;
		}
	}

	/*************************************************************/
	/*********************** GET PARENT ARRAY ********************/
	/*************************************************************/
	protected function getParentSelectArray(){
		if(empty($this->row_name)){ $this->row_name = self::getAternativeName(); }	
		if(isset($this->sticky_fields) and !empty($this->sticky_fields)){
			$sticky_sql = "";
			foreach($this->sticky_fields as $sticky_field_name => $sticky_field_value){
				$sticky_sql .= "
					" . mysql_real_escape_string($sticky_field_name) . " = '" . mysql_real_escape_string($sticky_field_value) . "'		
				AND";
			}
			$sticky_sql .= "
					1 = 1
							";
		}else{
			$sticky_sql = "
					1 = 1				
							";
		}
		$sql = "	
					SELECT
						*
					FROM 
						" . mysql_real_escape_string($this->table) . "
				";
		if(isset($this->date_added_field) and !empty($this->date_added_field) and !have($this->orderfield) and !have($this->alt_orderfield)){
			$sql .= "
					WHERE 
						" . $sticky_sql . "
					ORDER BY 
						" . mysql_real_escape_string($this->date_added_field) . " " . $this->order_by_direction . "
					";
		}elseif(isset($this->alt_orderfield)){
			$sql .= "
					WHERE 
						" . $sticky_sql . "
					ORDER BY 
						" . mysql_real_escape_string($this->alt_orderfield) . " " . $this->order_by_direction . "
					";
		}elseif(isset($this->orderfield)){
			$sql .= "
					WHERE 
						" . $sticky_sql . "
					ORDER BY 
						" . mysql_real_escape_string($this->orderfield) . " " . $this->order_by_direction . "
					";
		}			
		$result = mysql_query($sql, $this->db) or die('sql:' . $sql . ' error:' . mysql_error());
		if(mysql_num_rows($result) > 0){
			$return = array();
			while($row = mysql_fetch_assoc($result)){
				$return[$row[$this->primary_key]] = $row;
			}	
			mysql_free_result($result);
			//add level to array - for indentation
			$return = self::stepThroughParentArray($return, 0, 0);
			return $return;
		}
		return array();
	}

	/*************************************************************/
	/*********************** STEP THROUGH PARENT ARRAY ***********/ //recursive!
	/*************************************************************/
	protected function stepThroughParentArray($array = array(), $parent_id = 0, $level = 0){
		$level++; $return = array(); if(have($array)){ foreach($array as $array_key => $array_value){ if($array_value['dzpro_page_parent_id'] == $parent_id){ $return[$array_key] = $array_value; $return[$array_key]['level'] = $level; $inner_array = self::stepThroughParentArray($array, $array_key, $level); $return = $return + $inner_array; } } }
		return $return;
	}
	
	/*************************************************************/
	/*********************** BUILD FORM LIST ARRAY ***************/
	/*************************************************************/
	protected function buildFromListingArray(){
		$return = array();
		if(empty($this->row_name)){ $this->row_name = self::getAternativeName(); }
		if(empty($this->row_description)){ $this->row_description = self::getAternativeName(array($this->row_name)); }
		$this->row_name_alt = self::getAternativeName(array($this->row_name, $this->row_description));
		if(!empty($this->row_name) and !empty($this->date_added_field)){
			if(isset($this->sticky_fields) and !empty($this->sticky_fields)){
				$sticky_sql = "";
				foreach($this->sticky_fields as $sticky_field_name => $sticky_field_value){
					$sticky_sql .= "
						" . mysql_real_escape_string($sticky_field_name) . " = '" . mysql_real_escape_string($sticky_field_value) . "'		
					AND";
				}
				$sticky_sql .= "
						1 = 1
								";
			}else{
				$sticky_sql = "
						1 = 1				
								";
			}
			$sql = "
					SELECT SQL_CALC_FOUND_ROWS
						*,
						" . mysql_real_escape_string($this->primary_key) . ",
						" . mysql_real_escape_string($this->row_name) . ",
			";
			if(!empty($this->row_description)){
				$sql .= "
						" . mysql_real_escape_string($this->row_description) . ",	
					";
			}
			if(!empty($this->row_name_alt)){
				$sql .= "
						" . mysql_real_escape_string($this->row_name_alt) . ",	
					";
			}
			if(!empty($this->active_field)){
				$sql .= "
						" . mysql_real_escape_string($this->active_field) . ",
					";
			}
			$sql .= "
						" . mysql_real_escape_string($this->date_added_field);
			if(isset($this->search_query) and !empty($this->search_query)){
				$sql .= ",
						MATCH (";
				foreach($this->fields as $field){
					if(isset($field['index']['Index_type']) and $field['index']['Index_type'] == 'FULLTEXT'){
						$sql .= $field['Field'] . ",";
					}
				}		
				$sql = substr($sql, 0, -1) . ") AGAINST ('" . mysql_real_escape_string($this->search_query) . "' IN BOOLEAN MODE) AS score";
			}
			$sql .= "
					FROM 
						" . mysql_real_escape_string($this->table) . "
					";
			if(empty($this->filter_key) and empty($this->filter_value)){
				if(isset($this->search_query) and !empty($this->search_query)){
					$sql .= "
					WHERE
					(	
						MATCH (";
					foreach($this->fields as $field){
						if(isset($field['index']['Index_type']) and $field['index']['Index_type'] == 'FULLTEXT'){
							$sql .= $field['Field'] . ",";
						}
					}		
					$sql = substr($sql, 0, -1) . ") AGAINST ('" . mysql_real_escape_string($this->search_query) . "' IN BOOLEAN MODE) > 0.2	
					";
					if(is_numeric($this->search_query)){
						$sql .= "
					OR
					" . mysql_real_escape_string($this->primary_key) . " = " . mysql_real_escape_string((int)$this->search_query) . " 
							";
					}
					$sql .= "
					)
					AND	
						" . $sticky_sql . "
					ORDER BY 
						score DESC
							";		
					if(!isset($_GET['viewall'])){
						$sql .= "
					LIMIT 
						" . $this->table_start . ", " . $this->results_limit . "	
							";
					}
				}else{
					if(isset($this->date_added_field) and !empty($this->date_added_field) and !have($this->orderfield) and !have($this->alt_orderfield)){
						$sql .= "
					WHERE 
						" . $sticky_sql . "
					ORDER BY 
						" . mysql_real_escape_string($this->date_added_field) . " " . $this->order_by_direction . "
								";
					}elseif(isset($this->alt_orderfield)){
						$sql .= "
					WHERE 
						" . $sticky_sql . "
					ORDER BY 
						" . mysql_real_escape_string($this->alt_orderfield) . " " . $this->order_by_direction . "
								";
					}elseif(isset($this->orderfield)){
						$sql .= "
					WHERE 
						" . $sticky_sql . "
					ORDER BY 
						" . mysql_real_escape_string($this->orderfield) . " " . $this->order_by_direction . "
								";
					}
					if(!isset($_GET['viewall'])){
						$sql .= "
					LIMIT 
						" . $this->table_start . ", " . $this->results_limit . "	
							";
					}
				}
			}else{
				$sql .= "
					WHERE
						";
				if(isset($this->search_query) and !empty($this->search_query)){
					$sql .= "
						(
						MATCH (";
					foreach($this->fields as $field){
						if(isset($field['index']['Index_type']) and $field['index']['Index_type'] == 'FULLTEXT'){
							$sql .= $field['Field'] . ",";
						}
					}		
					$sql = substr($sql, 0, -1) . ") AGAINST ('" . mysql_real_escape_string($this->search_query) . "' IN BOOLEAN MODE) > 0.2	
					";
					if(is_numeric($this->search_query)){
						$sql .= "
					OR
					" . mysql_real_escape_string($this->primary_key) . " = " . mysql_real_escape_string((int)$this->search_query) . "
							";
					}
					$sql .= "
					)
					AND
					";
				}
				$sql .= "
						" . mysql_real_escape_string($this->filter_key) . " = '" . mysql_real_escape_string($this->filter_value) . "'
					AND
						" . $sticky_sql . "
					";
					if(isset($this->date_added_field) and !empty($this->date_added_field) and !have($this->orderfield) and !have($this->alt_orderfield)){
						$sql .= "
					ORDER BY 
						" . mysql_real_escape_string($this->date_added_field) . " " . $this->order_by_direction . "
								";
					}elseif(have($this->alt_orderfield)){
						$sql .= "
					ORDER BY 
						" . mysql_real_escape_string($this->alt_orderfield) . " " . $this->order_by_direction . "
								";
					}elseif(have($this->orderfield)){
						$sql .= "
					ORDER BY 
						" . mysql_real_escape_string($this->orderfield) . " " . $this->order_by_direction . "
								";
					}
					if(!isset($_GET['viewall'])){
						$sql .= "
					LIMIT 
						" . $this->table_start . ", " . $this->results_limit . "	
							";
					}
			}
			$result = mysql_query($sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
			$found_rows_sql = "SELECT FOUND_ROWS();";
			$found_rows_result = mysql_query($found_rows_sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
			if(mysql_num_rows($found_rows_result) > 0){
				$found_rows_row = mysql_fetch_row($found_rows_result);
				$this->table_query_total = $found_rows_row[0];
				mysql_free_result($found_rows_result);
			}
			while($row = mysql_fetch_assoc($result)){
				$return[] = $row;
			}
			mysql_free_result($result);
		}
		return $return;
	}

	/*************************************************************/
	/*********************** BUILD FORM LIST *********************/
	/*************************************************************/
	public function dontAllowDelete(){
		$this->dontAllowDelete = true;
	}

	/*************************************************************/
	/*********************** BUILD FORM BLOCK ********************/
	/*************************************************************/
	public function getAjaxResult($query){
		if(!empty($this->row_name) and !empty($this->row_description) and isset($query) and !empty($query) and isset($this->fields) and !empty($this->fields)){
			if(isset($this->sticky_fields) and !empty($this->sticky_fields)){
				$sticky_sql = "";
				foreach($this->sticky_fields as $sticky_field_name => $sticky_field_value){
					$sticky_sql .= "
						" . mysql_real_escape_string($sticky_field_name) . " = '" . mysql_real_escape_string($sticky_field_value) . "'		
					AND";
				}
				$sticky_sql .= "
						1 = 1
								";
			}else{
				$sticky_sql = "
						1 = 1				
								";
			}
			$sql = "
					SELECT 
						" . mysql_real_escape_string($this->primary_key) . ",
						" . mysql_real_escape_string($this->row_name) . ",
						" . mysql_real_escape_string($this->row_description) . ",
						MATCH (";
				foreach($this->fields as $field){
					if(isset($field['index']['Index_type']) and $field['index']['Index_type'] == 'FULLTEXT'){
						$sql .= $field['Field'] . ",";
					}
				}		
				$sql = substr($sql, 0, -1) . ") AGAINST ('" . mysql_real_escape_string($query) . "*' IN BOOLEAN MODE) AS score
					FROM 
						" . mysql_real_escape_string($this->table) . "
					WHERE
						MATCH (";
				foreach($this->fields as $field){
					if(isset($field['index']['Index_type']) and $field['index']['Index_type'] == 'FULLTEXT'){
						$sql .= $field['Field'] . ",";
					}
				}		
				$sql = substr($sql, 0, -1) . ") AGAINST ('" . mysql_real_escape_string(str_replace(' ', ' +', $query)) . "*' IN BOOLEAN MODE) > 0.5
					AND 
						" . $sticky_sql . "
					ORDER BY 
						score DESC
					LIMIT 
						" . INTELLIGENT_RESULTS_LIMIT . "	
					";
			$result = mysql_query($sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
			if(mysql_num_rows($result) > 0){
				echo '<span class="suggestions">suggestions</span><ul class="intelli_search_results">';
				$counter = 0;
				while($row = mysql_fetch_assoc($result)){
					echo '<li><a href="' . $_SERVER['PHP_SELF'] . addToGetString(array('action', 'record_id'), array('edit', (int)$row[$this->primary_key]), array('ajax')) . '" title="' . htmlentities($row[$this->row_name]) . '" id="search_res_' . $this->table . '_' . $counter++ . '">' . htmlentities($row[$this->row_name]) . '</a></li>';
				}
				mysql_free_result($result);
				echo '</ul>';
			}
		}
	}

	/*************************************************************/
	/*********************** SET FILTER VALUES *******************/
	/*************************************************************/
	protected function presetFilterKeyValuePair(){
		if(have($this->fields)){ foreach($this->fields as $key => $value){ if(have($this->filter_value) and $this->filter_key == $value['Field']){ $this->fields[$key]['post_value'] = $this->filter_value; } } }
	}

	/*************************************************************/
	/*********************** SEE IF SUPER USER *******************/
	/*************************************************************/	
	public function isSuperUser(){
		if(isset($_SESSION['dzpro_admin_super']) and $_SESSION['dzpro_admin_super'] == 1){ return true; }
		return false;	
	}

	/*************************************************************/
	/*********************** SEE IF EDITABLE *********************/
	/*************************************************************/
	protected function canFieldBeEdited($field = null){
		if(!have($field)){ return false; }
		if(self::isSuperUser()){ return true; }
		if(have($field['field_regex']) and $field['field_regex'] != 'x'){ return true; }
		return false;
	}

	/*************************************************************/
	/*********************** SEE IF EDITABLE *********************/
	/*************************************************************/
	protected function shouldFieldBeShown($field = null){
		if(!have($field)){ return false; }
		if(isset($field['field_name']) and have($field['field_name'])){ return true; }
		return false;
	}

	/*************************************************************/
	/*********************** DO RECORD COUNT *********************/
	/*************************************************************/	
	public function getTotalRecordCount(){
		if(isset($this->sticky_fields) and !empty($this->sticky_fields)){
			$sticky_sql = "";
			foreach($this->sticky_fields as $sticky_field_name => $sticky_field_value){
				$sticky_sql .= "
					" . mysql_real_escape_string($sticky_field_name) . " = '" . mysql_real_escape_string($sticky_field_value) . "'		
				AND ";
			}
			$sticky_sql .= "
					1 = 1
							";
		}else{
			$sticky_sql = "
					1 = 1				
							";
		}
		if(have($this->filter_key) and have($this->filter_value)){
			$filter_string = " " . mysql_real_escape_string($this->filter_key) . " = '" . mysql_real_escape_string($this->filter_value) . "' ";	
		}else{
			$filter_string = " 1 = 1 ";
		}	
		$total_count = 0; $result = @mysql_query(" SELECT COUNT(*) AS total_count FROM " . mysql_real_escape_string($this->table) . " WHERE " . $sticky_sql . " AND " . $filter_string . " "); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $total_count = $row['total_count']; } }
		return $total_count;
	}

	/*************************************************************/
	/*********************** HANDLE FIELD LABEL ******************/
	/*************************************************************/	
	public function handleFieldLabel($label = null){
		if(!have($label)){ return null; }
		return preg_replace(array('/\(([^\)]+)\)/'), array('<span class="label_sub_text">$1</span>'), $label);
	}
	
	/*************************************************************/
	/*********************** GET TABLE COMMENTS ******************/
	/*************************************************************/		
	public function getTableComments($table = null){
		if(!have($table)){ return false; }
		if(isset($this->table_comments_array[$table]) and !empty($this->table_comments_array[$table])){ return $this->table_comments_array[$table]; }
		return false;
	}	

}