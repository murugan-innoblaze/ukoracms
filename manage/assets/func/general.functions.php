<?php
/************************************************************************/
/********************* MANIPULATE AND PRINT GET STRING ******************/
/************************************************************************/
function addToGetString($key, $value, $remove_keys = null){
	$get_array = $_GET;
	if(isset($remove_keys) and !empty($remove_keys)){
		if(is_array($remove_keys)){
			foreach($remove_keys as $remove_this_key){
				if(isset($get_array[$remove_this_key])){
					unset($get_array[$remove_this_key]);
				}
			}
		}else{
			if(isset($get_array[$remove_keys])){
				unset($get_array[$remove_keys]);
			}	
		}	
	}
	if(is_array($key)){
		$key_value = array_combine($key, $value);
		foreach($key_value as $this_key => $this_value){
			$get_array[$this_key] = $this_value;
		}	
	}else{
		$get_array[$key] = $value;
	}
	$return_get_string = '?';
	foreach($get_array as $get_key => $get_value){
		if(!empty($get_value)){
			$return_get_string .= '&amp;' . urlencode($get_key) . '=' . urlencode($get_value);
		}
	}
	return $return_get_string;
}

/************************************************************************/
/********************* MANIPULATE AND PRINT GET STRING ******************/
/************************************************************************/
function addToGetStringAjax($key, $value, $remove_keys = null){
	return str_ireplace(array('&amp;'), array('&'), addToGetString($key, $value, $remove_keys));
}

/************************************************************************/
/******************** GET THE CURRENT GET STRING ************************/
/************************************************************************/
function getGetString(){
	$get_array = $_GET;
	$return_get_string = '?';
	foreach($get_array as $get_key => $get_value){
		if(!empty($get_value)){
			$return_get_string .= '&amp;' . urlencode($get_key) . '=' . urlencode($get_value);
		}
	}
	return $return_get_string;
}

/************************************************************************/
/******************* BUILD HIDDEN FIELDS BLOCK FROM GET *****************/
/************************************************************************/
function buildHiddenFieldFromGet($remove_keys = null){
	$get_array = $_GET;
	if(isset($remove_keys) and !empty($remove_keys)){
		if(is_array($remove_keys)){
			foreach($remove_keys as $remove_this_key){
				if(isset($get_array[$remove_this_key])){
					unset($get_array[$remove_this_key]);
				}
			}
		}else{
			if(isset($get_array[$remove_keys])){
				unset($get_array[$remove_keys]);
			}	
		}	
	}	
	$return_hidden_string = '';
	foreach($get_array as $get_key => $get_value){
		if(!empty($get_value)){
			$return_hidden_string .= '<input type="hidden" name="' . $get_key . '" value="' . $get_value . '" />';
		}
	}
	return $return_hidden_string;
}

/************************************************************************/
/******************* DOES THIS VALUE EXIST ******************************/
/************************************************************************/
function have($string = null){
	if(isset($string) and !empty($string)){ return true; }
	return false;
}

/************************************************************************/
/******************* GET FULL URL ***************************************/
/************************************************************************/
function getFullUrl(){ 
	return 'http' . (empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? 's' : null) . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; 
} 
?>