<?php

class Contact {
	
	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
	
		//need session for captcha
		assureSession();
		
		//database connection
		$this->db = $db;
		
		//dropzone id
		$this->required_key = null;
		$this->required_value = null;

		//iframe intend setting
		$this->prepare_for_iframe = false;

		//set table name
		$this->table = $table_name;
		
		//table information
		$this->straight_fields = array();
		$this->fields_field_key = array();
		$this->fields = array();
				
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
		if(self::checkValues() and $this->primary_value == false and !isset($_POST['this_is_the_primary_value']) and !empty($_POST)){ self::insertRecord(); }

		//if there is a primary key ... lets load
		if($this->primary_value){ self::loadFormValuesFromRow(); }

		//build aternative array // rebuild alternate fields
		$this->alternate_fields = self::buildAlternateArray();

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
			$result = mysql_query($sql) or die(mysql_error());
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
				$return_array[$counter]['post_value'] = (isset($_POST[$row['Field']]) and !empty($_POST[$row['Field']])) ? $_POST[$row['Field']] : null;
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
			$result = mysql_query($sql) or die(mysql_error());
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
			$result = mysql_query($sql) or die(mysql_error());
			if(mysql_num_rows($result) > 0){
				while($row = mysql_fetch_assoc($result)){
					foreach($row as $row_field => $row_value){
						$this->fields[$field_map[$row_field]]['post_value'] = (!isset($_POST[$row_field])) ? stripslashes(stripslashes($row_value)) : stripslashes(stripslashes($_POST[$row_field]));
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
							$result_get_assoc = mysql_query($sql_get_assoc) or die(mysql_error());
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
				if($mainTableCheck){ $this->row_name = $val; }
			break;
			
			//row description
			case(substr($val, -11) == 'description'):
				$return_array['field_type'] = 'description';
				if($mainTableCheck){ $this->row_description = $val; }
			break;
			
			//row description alternatives
			case(substr($val, -7) == 'address'):
				$return_array['field_type'] = 'description';
				if($mainTableCheck){ $this->row_description = $val; }
			break;			
		
			//states select field
			case(substr($val, -5) == 'state'):
				$return_array['field_type'] = 'state';
				if($mainTableCheck){ $return_array['states_array'] = $this->states; }
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
				if($mainTableCheck){ $this->alt_orderfield = $val; $this->orderfield = $val; $this->order_by_direction = ' ASC'; }
			break;
			
			//this is an orderfield
			case(substr($val, -6) == '_order'):
				$return_array['field_type'] = 'orderfield';
				if($mainTableCheck){ $this->alt_orderfield = $val; $this->orderfield = $val; $this->order_by_direction = ' ASC'; }
			break;
			
			//alternate lookup
			case(substr($val, -3) == '_id' and substr($val, -10) != '_parent_id' and $mainTableCheck):
				if(false !== ($related_options_array = self::findRelatedTableInfo($val))){ $return_array['related_options'] = $related_options_array; }
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
			$result = mysql_query($sql) or die(mysql_error());
			if(mysql_num_rows($result) > 0){
				while($row = mysql_fetch_assoc($result)){
					$table = $row['Name'];
					if($this->table == $row['Name']){ $this->table_comments = $row['Comment']; }
					if(isset($table) and substr($table, 0, strlen(TABLES_PREPEND)) == TABLES_PREPEND and $table != $this->table){
						$sql_table = "SHOW FULL COLUMNS FROM " . mysql_real_escape_string($table);
						$result_table = mysql_query($sql_table) or die(mysql_error());
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
					case(isset($field_array['field_type']) and $field_array['field_type'] == 'date_added'):
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
			$result = mysql_query($sql) or die(mysql_error());
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
		if(!isset($this->tried_to_find_tables[$field_name])){ self::mapAllTables(); }
		if(!empty($this->no_key_tables)){
			foreach($this->no_key_tables as $table_name => $table_array){
				if(sizeof($table_array) <= 4){
					$found_primary_key = false;
					foreach($table_array as $field_name => $field_array){
						if($field_name == $this->primary_key){
							$found_primary_key = true;
						}
						if($field_name != $this->primary_key and substr($field_array['Type'], 0, 3) == 'int' and substr($field_name, -3) == '_id'){
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
		if(isset($_POST) and !empty($_POST)){ if(!isset($_POST[$this->table . '_captcha']) or !isset($_SESSION['captcha']) or strtolower($_POST[$this->table . '_captcha']) != strtolower($_SESSION['captcha'])){ $this->event = 'Please enter captcha'; return false; } }
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
					mysql_query($sql) or die(mysql_error());
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
						mysql_query($insert_sql) or die(mysql_error());	
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
			$result = mysql_query($sql) or die(mysql_error());
			if(mysql_insert_id() > 0){
				$this->event = 'inserted';
				self::insertAssociations(mysql_insert_id());
				self::sendEmail();
				return true;
			}else{
				$this->event = 'not inserted';
				return false;
			}
		}
	}

	/*************************************************************/
	/*********************** SEND EMAIL **************************/
	/*************************************************************/
	protected function sendEmail(){
		if(isset($this->fields) and !empty($this->fields)){
			$email_body = '';
			foreach($this->fields as $field){
				if(false !== ($return = self::checkInputField($field))){
					$email_body .= $field['Field'] . ": " . $return['post_value'] . "\n\n";
				}
			}
			if(false !== mail($this->send_to_email, 'Message from ' . HOST_NAME, $email_body)){
				addToIntelligenceStack('contact form send', $this->table); //save contact view
			}else{
				addToIntelligenceStack('contact form sending failed', $this->table); //save contact view
			}
		}
	}
	
	/*************************************************************/
	/*********************** BUILD HEAD JS BLOCK *****************/
	/*************************************************************/
	public function buildHeadBlock(){
		if(isset($this->need_upload) and $this->need_upload === true){
		?>
		<link href="<?=ASSETS_PATH?>/upl/uploadify.css" type="text/css" rel="stylesheet" />
		<script type="text/javascript" src="<?=ASSETS_PATH?>/upl/swfobject.js"></script>
		<script type="text/javascript" src="<?=ASSETS_PATH?>/upl/jquery.uploadify.v2.1.1.min.js"></script>
		<?php
		} //end if ..need_upload
		?>
		<?php
		if(isset($this->prepareForHtmlEditor) and $this->prepareForHtmlEditor === true){
		?>
		<script type="text/javascript" src="<?=ASSETS_PATH?>/mce/tiny_mce.js"></script>
		<?php
		} //end if ..in iframe
		?>
		<link type="text/css" href="<?=ASSETS_PATH?>/css/form.css" rel="stylesheet" media="all" />
		<script type="text/javascript">
			<!--
				$().ready(function(){
					/**********************************/
					/****** remove default values *****/
					/**********************************/
					$('input.save_button', '#form_<?=$this->table?>').live('click', <?=$this->table?>_prepareSubmit);
					//$('#form_<?=$this->table?>').live('submit', <?=$this->table?>_prepareSubmit);

					/**********************************/
					/****** title val swaps ***********/
					/**********************************/
					$('input, textarea', '#form_<?=$this->table?>').each(function(){
						if($(this).val() == ''){
							$(this).val($(this).attr('title'));
						}else{
							if($(this).val() == $(this).attr('title')){
								$(this).removeClass('touched');
							}else{
								$(this).addClass('touched');
							}
						}
					});
					$('input, textarea', '#form_<?=$this->table?>').focus(function(){
						if($(this).val() == $(this).attr('title')){
							$(this).val('').removeClass('touched');
						}else{
							if($(this).val() == ''){
								$(this).removeClass('touched');
							}else{
								$(this).addClass('touched');
							}
						}
					});
					$('input, textarea', '#form_<?=$this->table?>').blur(function(){
						if($(this).val() == ''){
							$(this).val($(this).attr('title')).removeClass('touched');
						}else{
							if($(this).val() == $(this).attr('title')){
								$(this).removeClass('touched');
							}else{
								$(this).addClass('touched');
							}
						}
					});

					/**********************************/
					/****** remove value button *******/
					/**********************************/
					$('.close_icon', '#form_<?=$this->table?>').click(function(){
						$(this).parent().children('input, textarea').val($(this).parent().children('input, textarea').attr('title'));
						$('#input_row_' + $(this).parent().children('input, textarea').removeClass('touched').attr('name')).removeClass('problem').removeClass('checked');
					});
					<?php
						if($this->need_upload === true){
					?>
					$('.close_icon_uploader', '#form_<?=$this->table?>').live('click', function(){
						$(this).parent().children('.file_dialogue_target').html('').hide();
						$(this).parent().children('input[type=hidden]').attr('value', '');
						$('#input_row_' + $(this).parent().children('input, textarea').attr('name')).removeClass('problem').removeClass('checked');
					});
					<?php
						} //need this to handle uploaded close button click
					?>
					
					/**********************************/
					/****** check input trigger *******/
					/**********************************/
					$('input, textarea', '#form_<?=$this->table?>').keyup(function(){
						var input_name = $(this).attr('name');
						var input_val = $(this).val();
						var input_title = $(this).attr('title');
						var input_status = <?=$this->table?>_checkInput(input_name, input_val, input_title);
						if(input_status == 'false'){
							$('#input_row_' + input_name).addClass('problem');
						}
						if(input_status == 'true'){
							$('#input_row_' + input_name).removeClass('problem').addClass('checked');
						}
						if(input_status == 'empty'){
							$('#input_row_' + input_name).removeClass('problem').removeClass('checked');
						}
					});
					$('input, textarea', '#form_<?=$this->table?>').blur(function(){
						var input_name = $(this).attr('name');
						var input_val = $(this).val();
						var input_title = $(this).attr('title');
						var input_status = <?=$this->table?>_checkInput(input_name, input_val, input_title);
						if(input_status == 'false'){
							$('#input_row_' + input_name).addClass('problem');
						}
						if(input_status == 'true'){
							$('#input_row_' + input_name).removeClass('problem').addClass('checked');
						}
						if(input_status == 'empty'){
							$('#input_row_' + input_name).removeClass('problem').removeClass('checked');
						}
					});
				});

				/**********************************/
				/****** check before submission ***/
				/**********************************/
				function <?=$this->table?>_prepareSubmit(){
					$('input, textarea', '#form_<?=$this->table?>').each(function(){
						if($(this).val() == $(this).attr('title')){
							$(this).val('');
						}
					});
					$('#form_<?=$this->table?>').submit();				
				}
				
				/**********************************/
				/****** check input values ********/
				/**********************************/
				function <?=$this->table?>_checkInput(name, value, title){
					switch(name){
						<?php
						foreach($this->fields as $key => $value){
							switch(true){
								
								//star means field can't be empty
								case($value['field_regex'] == '*'):
					?>
						case '<?=$value['Field']?>':
							if(value == '' || value == title){
								return 'empty';		
							}else{
								if(value != ''){
									return 'true';
								}else{
									return 'false';
								}
							}
						break;						
					<?php
								break;
								
								//regular regex
								case(strlen($value['field_regex']) > 3):
					?>
						case '<?=$value['Field']?>':
							if(value == '' || value == title){
								return 'empty';		
							}else{
								if(value.match(<?=$value['field_regex']?>)){
									return 'true';
								}else{
									return 'false';
								}
							}
						break;
					<?php
								break;

							} //end switch field_regex
						} //end foreach
						?>
						default:
							return 'neutral';
						break;
					}
				}
			-->
		</script>
		<?php
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
	/*********************** BUILD EVENT BLOCK *******************/
	/*************************************************************/
	public function showEventBlock(){
		$return = true; //lets assume we want to show the form
		$frame_class = ($this->prepare_for_iframe === true) ? 'iframe' : null;
		if(isset($this->event) and !empty($this->event)){
			switch($this->event){
				case 'inserted':
		?>
			<div class="form_message <?=$frame_class?>">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/notification-icon.gif" alt="Notification Icon" /> Message has been send</td></tr></tbody></table>
				<button>Close <span class="countdown">[8]</span></button>
			</div><!-- .form_message -->
		<?php		
					$return = false;
				break;
				case 'not inserted':
		?>
			<div class="form_message <?=$frame_class?>">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/notification-icon.gif" alt="Notification Icon" /> Message could not be send</td></tr></tbody></table>
				<button>Close <span class="countdown">[8]</span></button>
			</div><!-- .form_message -->
		<?php		
					$return = true;
				break;
				default:
		?>
			<div class="form_message <?=$frame_class?>">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/notification-icon.gif" alt="Notification Icon" /> <?=htmlentities($this->event)?></td></tr></tbody></table>
				<button>Close <span class="countdown">[8]</span></button>
			</div><!-- .form_message -->
		<?php		
					$return = true;
				break;
			}
		?>
			<script type="text/javascript">
				<!--
					function closeMessageButton(){ clearInterval(intervalVar); $('.form_message').slideUp(200); }
					var intervalVar = '';
					function showMessage(mssg){
						clearInterval(intervalVar);
						$('div.form_message #message_load_target').text(mssg);
						$('div.form_message').show();
						var startTimeMssgClose = 8;
						intervalVar = setInterval(function(){
							startTimeMssgClose = startTimeMssgClose - 1;
							$('.countdown').html('[' + startTimeMssgClose + ']');
							if(startTimeMssgClose == 0){ closeMessageButton(); }
						}, 1000);
					}
					$().ready(function(){
						$('.form_message button').click(closeMessageButton);
						var startTimeMssgClose = 8;
						setInterval(function(){
							startTimeMssgClose = startTimeMssgClose - 1;
							$('.countdown').html('[' + startTimeMssgClose + ']');
							if(startTimeMssgClose == 0){ closeMessageButton(); }
						}, 1000);
					});
				//-->
			</script>
		<?php
		}else{
		?>
			<script type="text/javascript">
				<!--
					function closeMessageButton(){ clearInterval(intervalVar); $('.form_message').slideUp(200); }
					var intervalVar = '';
					function showMessage(mssg){
						clearInterval(intervalVar);
						$('div.form_message #message_load_target').text(mssg);
						$('div.form_message').show();
						var startTimeMssgClose = 8;
						intervalVar = setInterval(function(){
							startTimeMssgClose = startTimeMssgClose - 1;
							$('.countdown').html('[' + startTimeMssgClose + ']');
							if(startTimeMssgClose == 0){ closeMessageButton(); }
						}, 1000);
					}
					$().ready(function(){ $('.form_message button').click(closeMessageButton); });
				//-->
			</script>
			<div class="form_message <?=$frame_class?>" style="display: none;">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/notification-icon.gif" alt="Notification Icon" /><span id="message_load_target"><!-- message loads here --></span></td></tr></tbody></table>
				<button>Close Message <span class="countdown">[8]</span></button>
			</div><!-- .form_message -->		
		<?php
		}
		return $return;
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
	private function getParentSelectArray(){
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
						" . mysql_real_escape_string($this->primary_key) . ",
						" . mysql_real_escape_string($this->row_name) . "
					FROM 
						" . mysql_real_escape_string($this->table) . "
					WHERE 
						" . $sticky_sql . "
					";
		$result = mysql_query($sql) or die('sql:' . $sql . ' error:' . mysql_error());
		if(mysql_num_rows($result) > 0){
			$return = array();
			while($row = mysql_fetch_assoc($result)){
				$return[$row[$this->primary_key]] = $row[$this->row_name];
			}	
			mysql_free_result($result);
			return $return;
		}
		return array();
	}
	
	/*************************************************************/
	/*********************** BUILD FORM BLOCK ********************/
	/*************************************************************/
	public function buildFormBlock(){
		$this_form_identity_string = (isset($_POST['form_time_' . $this->table])) ? $_POST['form_time_' . $this->table] : date('Y-m-d-H-i-s');
		$frame_class = ($this->prepare_for_iframe === true) ? 'iframe' : null;
		?>
											<form class="form_area <?=$frame_class?>" method="post" id="form_<?=$this->table?>">
												<input type="hidden" name="form_time_<?=$this->table?>" value="<?=$this_form_identity_string?>" />		
		<?php
		if(isset($this->primary_value) and $this->primary_value > 0){
		?>
												<input type="hidden" name="this_is_the_primary_value" value="<?=(int)$this->primary_value?>" />
		<?php
		}
		foreach($this->fields as $key => $value){
		
			//if sticky field .. then skip
			$sticky_keys = array_keys($this->sticky_fields);
			if(in_array($value['Field'], $sticky_keys)){ continue; }
			
			switch(true){
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** SELECT A STATE BOX ******************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (isset($value['field_type']) and $value['field_type'] == 'state' and isset($value['states_array']) and !empty($value['states_array'])):
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<select name="<?=$value['Field']?>">
																			<option value=""> --------- </option>
		<?php
					foreach($value['states_array'] as $state_key => $state_value){
						$this_selected = (isset($value['post_value']) and !empty($value['post_value']) and $value['post_value'] == $state_key) ? ' selected="selected" ' : '';
		?>
																			<option value="<?=$state_key?>" <?=$this_selected?>><?=$state_value?></option>
		<?php			
					}
		?>
																		</select>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>			
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** NAME FIELD DO DYNAMIC LOOKUP ********************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (isset($value['field_type']) and $value['field_type'] == 'name' and $this->primary_value == null):
					if(isset($_SESSION['front-end-user']['dzpro_user_name'])){
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder" style="padding-left: 7px;">
																		<strong><?=$_SESSION['front-end-user']['dzpro_user_name']?></strong>
																		<input name="<?=$value['Field']?>" type="hidden" value="<?=$_SESSION['front-end-user']['dzpro_user_name']?>" />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>
		<?php
					}else{
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=$value['post_value']?>" <?=$max_length?> autocomplete="off" id="search_field_<?=$value['Field']?>" />
																		<div class="intelli_search_holder"><!-- search results load here --></div>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>
		<?php
					}
				break;
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** UPLOAD A FILE TO THIS FIELD *********************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (isset($value['field_type']) and $value['field_type'] == 'file'):
					$post_string = (isset($value['post_value']) and is_file(DOCUMENT_ROOT . $value['post_value'])) ? '<img src="' . ASSETS_PATH. '/img/file_icon.png" alt="file" /> ' . substr($value['post_value'], strrpos($value['post_value'], '/')) . ' (' . filesize(DOCUMENT_ROOT . $value['post_value']) . ' Bytes)' : '';
					$required_field_seperator_path = (isset($this->required_value) and !empty($this->required_value)) ? md5($this->required_value) . '/' : '';
		?>
												<script type="text/javascript">
													<!--
														$().ready(function(){
														  	$('#file_upload_<?=$value['Field']?>').uploadify({
														    	'uploader' : '<?=ASSETS_PATH?>/upl/uploadify.swf',
														    	'script' : '<?=ASSETS_PATH?>/upl/uploadFile.php',
														    	'cancelImg' : '<?=ASSETS_PATH?>/upl/cancel.png',
														    	'folder' : '<?=UPLOADS_PATH?>/<?=$required_field_seperator_path . $this->table?>/<?=$value['Field']?>/<?=$this_form_identity_string?>',
														    	'buttonImg' : '<?=ASSETS_PATH?>/img/upload_file_button.png',
														    	'wmode' : 'transparent',
														    	'fileExt' : '*.doc;*.txt;*.pdf',
  																'fileDesc' : 'Document Files',  
  																'multi' : false,
  																'auto' : true,
  																'onComplete' : function(event, ID, fileObj, response, data){
  																	$('#file_upload_target_<?=$value['Field']?>').html('<input type="hidden" name="<?=$value['Field']?>" value="' + response + '" /><img src="<?=ASSETS_PATH?>/img/file_icon.png" alt="file" /> ' + fileObj.name + ' (' + fileObj.size + ' Bytes)').fadeIn(200);
  																}
														  	});
															<?php
																if(!empty($post_string)){
															?>
															$('#file_upload_target_<?=$value['Field']?>').fadeIn(200);
															<?php	
																}
															?>
														});
													//-->
												</script>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon_uploader"><!-- block --></div>
																		<input name="file_upload_<?=$value['Field']?>" id="file_upload_<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="file" value="<?=$value['post_value']?>" />
																		<input name="<?=$value['Field']?>" value="<?=$value['post_value']?>" type="hidden" />
																		<div class="file_dialogue_target" id="file_upload_target_<?=$value['Field']?>"><?=$post_string?><!-- form element load target --></div>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>	
		<?php
				break;		

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** UPLOAD AN IMAGE TO THIS FIELD *******************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (isset($value['field_type']) and $value['field_type'] == 'image'):
					$post_string = (isset($value['post_value']) and is_file(DOCUMENT_ROOT . $value['post_value'])) ? '<img src="' . $value['post_value'] . '" alt="image" />' : '';
					$required_field_seperator_path = (isset($this->required_value) and !empty($this->required_value)) ? md5($this->required_value) . '/' : '';
		?>
												<script type="text/javascript">
													<!--
														$().ready(function(){
														  	$('#image_upload_<?=$value['Field']?>').uploadify({
														    	'uploader' : '<?=ASSETS_PATH?>/upl/uploadify.swf',
														    	'script' : '<?=ASSETS_PATH?>/upl/uploadImage.php',
														    	'cancelImg' : '<?=ASSETS_PATH?>/upl/cancel.png',
														    	'folder' : '<?=UPLOADS_PATH?>/<?=$required_field_seperator_path . $this->table?>/<?=$value['Field']?>/<?=$this_form_identity_string?>',
														    	'buttonImg' : '<?=ASSETS_PATH?>/img/upload_image_button.png',
														    	'wmode' : 'transparent',
														    	'fileExt' : '*.jpg;*.gif;*.png',
  																'fileDesc' : 'Image Files',  
  																'multi' : false,
  																'auto' : true,
  																'onComplete' : function(event, ID, fileObj, response, data){
    																$('#image_upload_target_<?=$value['Field']?>').html('<img src="' + response + '" alt="' + fileObj.name + '" />').fadeIn(200);
    																$('input[name=<?=$value['Field']?>]', '#input_row_<?=$value['Field']?>').attr('value', response);
    															}
														  	});
															<?php
																if(!empty($post_string)){
															?>
															$('#image_upload_target_<?=$value['Field']?>').fadeIn(200);
															<?php	
																}
															?>
														});
													//-->
												</script>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon_uploader"><!-- block --></div>
																		<input name="image_upload_<?=$value['Field']?>" id="image_upload_<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="file" value="<?=$value['post_value']?>" />
																		<input name="<?=$value['Field']?>" value="<?=$value['post_value']?>" type="hidden" />
																		<div class="file_dialogue_target" id="image_upload_target_<?=$value['Field']?>"><?=$post_string?><!-- form element load target --></div>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>	
		<?php
				break;	

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** SELECT DATE FIELD *******************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case ($value['Type'] == 'date'):
		?>
												<script type="text/javascript">
													<!--
														$().ready(function(){
															$("#datepicker_<?=$value['Field']?>").datepicker();
														});
													//-->
												</script>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" id="datepicker_<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=(isset($value['post_value']) and !empty($value['post_value'])) ? date('m/d/Y', strtotime($value['post_value'])) : ''?>" <?=$max_length?>  autocomplete="off" />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>			
		<?php		
				break;
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** SELECT BOX FROM ENUM ****************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 4) == 'enum'):
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<select name="<?=$value['Field']?>">
		<?php
					$enum_matches = array();
					@preg_match_all('/\'([^\']+)\'/i', $value['Type'], $enum_matches);
					if(isset($enum_matches[1]) and !empty($enum_matches[1])){
						foreach($enum_matches[1] as $enum_value){
							//select posted value
							$this_selected = (isset($value['post_value']) and !empty($value['post_value']) and $value['post_value'] == $enum_value) ? ' selected="selected" ' : null;
							//select default value
							$this_selected = ((!isset($value['post_value']) or empty($value['post_value'])) and $value['Default'] == $enum_value) ? ' selected="selected" ' : $this_selected;
		?>
																			<option value="<?=$enum_value?>" <?=$this_selected?>><?=$enum_value?></option>																	
		<?php
						}
					}
		?>
																		</select>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>				
		<?php
				break;
		
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** TINY INT SWITCH *********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case ($value['Type'] == 'tinyint(1)'):
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<select name="<?=$value['Field']?>">
																			<option value="0" <?php if(isset($value['post_value']) and $value['post_value'] == 0){ echo 'selected="seletected"'; } ?>>off</option>
																			<option value="1" <?php if(isset($value['post_value']) and $value['post_value'] == 1){ echo 'selected="seletected"'; } ?>>on</option>
																		</select>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>				
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** RELATED TABLE ***********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (isset($value['related_options']) and !empty($value['related_options']) and $value['Field'] != 'dzpro_user_id'):
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<select name="<?=$value['Field']?>">
																			<option value=""> ----- </option>
		<?php
					foreach($value['related_options'] as $option_key => $option_value){
						$this_selected = (isset($value['post_value']) and $value['post_value'] == $option_key) ? ' selected="selected" ' : '';
		?>
																			<option value="<?=$option_key?>" <?=$this_selected?>><?=$option_value['option_name']?></option>
		<?php
					}		
		?>
																		</select>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>		
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** USER ID *****************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (isset($value['related_options']) and !empty($value['related_options']) and $value['Field'] == 'dzpro_user_id'):
					if(isset($_SESSION['front-end-user']['dzpro_user_name']) and !empty($_SESSION['front-end-user']['dzpro_user_name'])){
		?>
												<input type="hidden" name="<?=$value['Field']?>" value="<?=(int)$_SESSION['front-end-user']['dzpro_user_id']?>" />
		<?php 
					}
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** PRICE FIELD *************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 5) == 'float' and substr($value['Field'], -5) == 'price'):
					$float_info_matches = array();
					if(false !== preg_match('/^float\(([0-9]+)\,([0-9]+)\)$/i', $value['Type'], $float_info_matches)){
						$value['post_value'] = number_format($value['post_value'], $float_info_matches[2]);
					}
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=$value['post_value']?>" <?=$max_length?>  autocomplete="off" />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>			
		<?php
				break;
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** FLOAT FIELD *************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 5) == 'float'):
					$float_info_matches = array();
					if(false !== preg_match('/^float\(([0-9]+)\,([0-9]+)\)$/i', $value['Type'], $float_info_matches)){
						$value['post_value'] = number_format($value['post_value'], $float_info_matches[2]);
					}
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=$value['post_value']?>" <?=$max_length?>  autocomplete="off" />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>			
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** PASSWORD FIELD **********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 7) == 'varchar' and (substr($value['Field'], -9) == '_password' or substr($value['Field'], -3) == '_pw')):
					$matches = array();
					preg_match('/[0-9]+/', $value['Type'], $matches);
					$max_length = (isset($matches[0]) and !empty($matches[0])) ? ' maxlength="' . (int)$matches[0] . '" ' : '';			
		?>									
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="password" value="<?=$value['post_value']?>" <?=$max_length?>  autocomplete="off" />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>
		<?php
				break;

				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** VARCHAR FIELD ***********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 7) == 'varchar' and substr($value['Field'], -6) == '_email'):
if(isset($_SESSION['front-end-user']['dzpro_user_email']) and !empty($_SESSION['front-end-user']['dzpro_user_email'])){
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder" style="padding-left: 7px;">
																		<strong><?=$_SESSION['front-end-user']['dzpro_user_email']?></strong>
																		<input name="<?=$value['Field']?>" type="hidden" value="<?=$_SESSION['front-end-user']['dzpro_user_email']?>" />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>
		<?php
					}else{
						$matches = array();
						preg_match('/[0-9]+/', $value['Type'], $matches);
						$max_length = (isset($matches[0]) and !empty($matches[0])) ? ' maxlength="' . (int)$matches[0] . '" ' : '';					
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=$value['post_value']?>" <?=$max_length?> autocomplete="off" id="search_field_<?=$value['Field']?>" />
																		<div class="intelli_search_holder"><!-- search results load here --></div>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>
		<?php
					}		
				break;
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** VARCHAR FIELD ***********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 7) == 'varchar'):
					$matches = array();
					preg_match('/[0-9]+/', $value['Type'], $matches);
					$max_length = (isset($matches[0]) and !empty($matches[0])) ? ' maxlength="' . (int)$matches[0] . '" ' : '';
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=$value['post_value']?>" <?=$max_length?>  autocomplete="off" />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>		
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** INT FIELD ***************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 3) == 'int' and $value['Key'] != 'PRI' and $value['field_type'] != 'orderfield'):
					$matches = array();
					preg_match('/[0-9]+/', $value['Type'], $matches);
					$max_length = (isset($matches[0]) and !empty($matches[0])) ? ' maxlength="' . (int)$matches[0] . '" ' : '';
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=$value['post_value']?>" <?=$max_length?>  autocomplete="off" />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>		
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** HTML AREA FIELD *********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 4) == 'text' and substr($value['Field'], -4) == 'html'):
		?>
												<script type="text/javascript">
													<!--
														tinyMCE.init({
															mode : "textareas",
															theme : "advanced",
															editor_selector : "html_editor_<?=$value['Field']?>",
															plugins : "pagebreak,style,paste,directionality,visualchars,nonbreaking,xhtmlxtras,template",
															theme_advanced_buttons1 : "pagebreak,styleprops,removeformat,hr,|,pastetext,pasteword,bullist,numlist,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull",
															theme_advanced_buttons2 : "outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,cleanup,forecolor,backcolor,code",
															theme_advanced_buttons3 : "styleselect,formatselect,fontselect,fontsizeselect,sub,sup",
															theme_advanced_buttons4 : "",
															theme_advanced_toolbar_location : "top",
															theme_advanced_toolbar_align : "left"
														});
													//-->
												</script>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="textarea">
																	<div class="inner_holder">
																		<textarea title="<?=$value['error_mssg']?>" name="<?=$value['Field']?>" style="height: 400px; padding: 0;" class="html_editor_<?=$value['Field']?>"><?=$value['post_value']?></textarea>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>		
		<?php		
				break;
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** TEXT AREA FIELD *********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 4) == 'text'):
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="textarea">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<textarea title="<?=$value['error_mssg']?>" name="<?=$value['Field']?>"><?=$value['post_value']?></textarea>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>		
		<?php		
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** TEXT AREA FIELD - MEDIUM TEXT *******************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 10) == 'mediumtext'):
		?>
												<div class="input_row" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=$value['field_name']?>
																</td>
																<td class="textarea">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<textarea title="<?=$value['error_mssg']?>" name="<?=$value['Field']?>"><?=$value['post_value']?></textarea>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>		
		<?php		
				break;
				
			}
		}
		
		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** ASSOCIATION BLOCKS ******************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/		
		if(isset($this->associative_map_tables) and !empty($this->associative_map_tables)){
		?>
												<script type="text/javascript">
													<!-- 
														$().ready(function(){
															$('td.associations ul li').click(function(event){
																if(event.target.nodeName != 'INPUT'){
																	if(false === $(this).children('input[type=checkbox]').attr('checked')){
																		$(this).children('input[type=checkbox]').attr('checked', true);
																		$(this).addClass('selected');
																	}else{
																		$(this).children('input[type=checkbox]').attr('checked', false);
																		$(this).removeClass('selected');
																	}
																}else{
																	if(false === $(this).children('input[type=checkbox]').attr('checked')){
																		$(this).removeClass('selected');
																	}else{
																		$(this).addClass('selected');
																	}
																}
															});
														});
													//-->
												</script>		
		<?php
			foreach($this->associative_map_tables as $assoc_table_name => $assoc_data_array){
				if(
					isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']) and
					isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field']) and
					isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']) and
					isset($this->associative_map_tables[$assoc_table_name]['assoc_table']['table']) and !empty($this->associative_map_tables[$assoc_table_name]['assoc_table']['table']) and
					isset($this->associative_map_tables[$assoc_table_name]['assoc_table']['native_key']) and !empty($this->associative_map_tables[$assoc_table_name]['assoc_table']['native_key']) and
					isset($this->associative_map_tables[$assoc_table_name]['assoc_table']['foreign_key']) and !empty($this->associative_map_tables[$assoc_table_name]['assoc_table']['foreign_key'])
				){
					$sticky_fields_for_assoc = self::filterStickyFieldsForTable($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']);
					if(isset($sticky_fields_for_assoc) and !empty($sticky_fields_for_assoc)){
						$sticky_sql = "";
						foreach($sticky_fields_for_assoc as $sticky_field_name => $sticky_field_value){
							$sticky_sql .= "
									" . mysql_real_escape_string($sticky_field_name) . " = '" . mysql_real_escape_string($sticky_field_value) . "'		
								AND
									";
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
									" . mysql_real_escape_string($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']) . ",
									" . mysql_real_escape_string($this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field']) . "
								FROM 
									" . mysql_real_escape_string($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']) . "
								WHERE 
									" . $sticky_sql . "				
							";
					if(isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['orderfield']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['orderfield'])){
						$sql .= "
								ORDER BY 
									" . mysql_real_escape_string($this->associative_map_tables[$assoc_table_name]['foreign_table']['orderfield']) . "
							";
					}
					$result = mysql_query($sql) or die(mysql_error());
					if(mysql_num_rows($result) > 0){
		?>
												<div class="input_row" id="input_row_<?=$assoc_table_name?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=preg_replace('/[^a-z]+/i', ' ', str_ireplace(TABLES_PREPEND ,'', $this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']))?>
																</td>
																<td class="associations">
																	<div class="inner_holder">
																		<ul>		
		<?php
						while($row = mysql_fetch_assoc($result)){
							if(!isset($_POST) or empty($_POST)){
								$checked_attr = (isset($this->associative_map_tables[$assoc_table_name]['existing_keys']) and in_array($row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']], $this->associative_map_tables[$assoc_table_name]['existing_keys'])) ? ' checked="checked" ' : '';
								$selected_attr = (isset($this->associative_map_tables[$assoc_table_name]['existing_keys']) and in_array($row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']], $this->associative_map_tables[$assoc_table_name]['existing_keys'])) ? ' class="selected" ' : '';
							}else{
								$post_keys = array_keys($_POST);
								$checked_attr = (in_array($assoc_table_name . '_' . (int)$row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']], $post_keys)) ? ' checked="checked" ' : '';
								$selected_attr = (in_array($assoc_table_name . '_' . (int)$row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']], $post_keys)) ? ' class="selected" ' : '';
							}
		?>
																			<li <?=$selected_attr?>>
																				<input type="checkbox" name="<?=$assoc_table_name?>_<?=(int)$row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']]?>" value="true" <?=$checked_attr?> /> <?=htmlentities($row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field']])?>
																			</li>
		<?php
						}
						mysql_free_result($result);
		?>
																		</ul>
																	</div><!-- end .inner_holder -->
																</td><!-- end .associations -->
															</tr>
														</tbody>
													</table>	
												</div><!-- end .input_row -->
		
		<?php
					} //if found rows
				} //if valid associative array
			} //foreach associative candidate
		} //if we have the associative array

		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** BUTTONS BLOCK ***********************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/			
		?>
												<div class="input_row" id="input_row_<?=$this->table?>_captcha">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	copy this
																</td>
																<td class="input">
																	<table style="border: none; padding; 0; margin: 0;">
																		<tbody>
																			<tr>
																				<td style="width: 192px;">
																					<img src="/assets/captcha/generate.php" alt="captcha" style="float: left;" />
																				</td>
																				<td>
																					<div class="inner_holder">
																						<input type="text" name="<?=$this->table?>_captcha" title="Copy this" />
																					</div><!-- .inner_holder -->
																				</td>
																			</tr>
																		</tbody>
																	</table>
																</td>
															</tr>
														</tbody>
													</table>
												</div>
												<div class="button_row">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td style="text-align: left;">
																	<input type="reset" value="Reset" />	
																</td>
																<td style="text-align: right;">
																	<button class="cancel_button" onclick="javascript:window.location.href='<?=addToGetString(null, null, array('action','record_id'))?>';return false;">Cancel</button>
																</td>
																<td style="text-align: right; width: 84px;">
																	<input type="submit" name="form_submit" value="Send" class="save_button" onclick="javascript:return false;" />
																</td>
															</tr>
														</tbody>
													</table>
												</div>
											</form>
		<?php
	}
	
}
?>