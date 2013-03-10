<?php

/************************************************************************/
/********************* GET BASE URL *************************************/
/************************************************************************/
function getBaseUrl(){
	if(strpos($_SERVER['REQUEST_URI'], '?')){ return preg_replace('/\?(.*)$/', '', $_SERVER['REQUEST_URI']); }else{ return $_SERVER['REQUEST_URI']; }
}

/************************************************************************/
/********************* MANIPULATE AND PRINT GET STRING ******************/
/************************************************************************/
function addToGetString($key = null, $value = null, $remove_keys = null, $remove_values = null){
	$get_array = isset($_GET) ? $_GET : array();
	if(have($remove_keys)){ $remove_keys = (array)$remove_keys; }
	if(have($remove_values)){ $remove_values = (array)$remove_values; }
	if(have($remove_keys)){
		foreach($remove_keys as $remove_key => $remove_key_value){  
			if(isset($get_array[$remove_key_value])){
				unset($get_array[$remove_key_value]); 
			}
			if(have($remove_values)){ 
				foreach($get_array as $get_key => $get_value){
					if(!is_array($get_value)){
						if($get_key == $remove_key_value and isset($remove_values[$remove_key]) and $remove_values[$remove_key] == $get_value){
							unset($get_array[$get_key]);
						}
					}else{
						foreach($get_value as $get_value_key => $get_value_value){
							if(isset($get_array[$get_key][$get_value_key]) and isset($remove_values[$remove_key]) and $remove_values[$remove_key] == $get_array[$get_key][$get_value_key]){
								unset($get_array[$get_key][$get_value_key]);
							}
						}
					}
				}
			} 
		} 
	}
	if(is_array($key)){ 
		$key_value = array_combine($key, $value); 
		foreach($key_value as $this_key => $this_value){ 
			if(substr($this_key, -2) == '[]'){ 
				if(!in_array($this_value, $get_array[$this_key])){
					$get_array[$this_key][] = $this_value;
				}
			}else{ 
				$get_array[$this_key] = $this_value; 
			} 
		} 
	}else{ 
		$get_array[$key] = $value; 
	}
	$return_get_string = '?'; 
	foreach($get_array as $get_key => $get_value){ 
		if(!empty($get_value)){ 
			if(is_array($get_value)){ 
				foreach($get_value as $value_instance){ 
					$return_get_string .= '&amp;' . urlencode($get_key) . '[]=' . urlencode($value_instance); 
				} 
			}else{ 
				$return_get_string .= '&amp;' . urlencode($get_key) . '=' . urlencode($get_value); 
			} 
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
	$get_array = $_GET; $return_get_string = '?'; foreach($get_array as $get_key => $get_value){ if(!empty($get_value)){ if(is_array($get_value)){ foreach($get_value as $value_instance){ $return_get_string .= '&amp;' . urlencode($get_key) . '[]=' . urlencode($value_instance); } }else{ $return_get_string .= '&amp;' . urlencode($get_key) . '=' . urlencode($get_value); } } }
	return $return_get_string;
} //getGetString

/************************************************************************/
/******************* BUILD HIDDEN FIELDS BLOCK FROM GET *****************/
/************************************************************************/
function buildHiddenFieldFromGet($remove_keys = null){
	$get_array = $_GET;
	if(isset($remove_keys) and !empty($remove_keys)){ if(is_array($remove_keys)){ foreach($remove_keys as $remove_this_key){ 
		if(isset($get_array[$remove_this_key])){ unset($get_array[$remove_this_key]); } } }else{ if(isset($get_array[$remove_keys])){ unset($get_array[$remove_keys]); } } 
	}	
	$return_hidden_string = ''; foreach($get_array as $get_key => $get_value){ if(!empty($get_value)){ if(is_array($get_value)){ foreach($get_value as $value_instance){ $return_hidden_string .= '<input type="hidden" name="' . $get_key . '[]" value="' . $value_instance . '" />'; } }else{ $return_hidden_string .= '<input type="hidden" name="' . $get_key . '" value="' . $get_value . '" />'; } } }
	return $return_hidden_string;
} //buildHiddenFieldFromGet

/************************************************************************/
/******************* UNLOAD PAGE ****************************************/
/************************************************************************/	
function pageUnload($add_to_stack = true, $save_intelligence = true){
	global $db;
	if($add_to_stack){ $Navigation = new Navigation($db); $Navigation->addToStack($_SERVER['REQUEST_URI']); }
	if($save_intelligence and !isWebSpider() and isset($_SERVER['HTTP_USER_AGENT']) and !empty($_SERVER['HTTP_USER_AGENT'])){ stackVisitorLocation(); stackNavigationDetails(); stackVisitorMachineDetails(); insertIntelligenceStack(); }
	exit();
} //pageUnload

/************************************************************************/
/******************* HAVE VARIALE ***************************************/
/************************************************************************/
function have($var){
	if(isset($var)){ if(!empty($var)){ return true; } }
	return false;
}

/************************************************************************/
/******************* HEX TO RGB *****************************************/
/************************************************************************/
function hexToRgb($hex = null){
	if(substr($hex,0,1) == '#') $hex = substr($hex,1); if(strlen($hex) == 3){ $hex = substr($hex,0,1) . substr($hex,0,1) . substr($hex,1,1) . substr($hex,1,1) . substr($hex,2,1) . substr($hex,2,1); } 
	if(strlen($hex) != 6) return false; $rgb['red'] = hexdec(substr($hex,0,2)); $rgb['green'] = hexdec(substr($hex,2,2)); $rgb['blue'] = hexdec(substr($hex,4,2)); return $rgb;
}

/************************************************************************/
/******************* FORCE SECURE CONNECTION ****************************/
/************************************************************************/
function forceSecureConnection(){ 
	if($_SERVER["HTTPS"] != 'on'){ header('HTTP/1.1 301 Moved Permanently'); header('Location: https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']); exit(0); }
}

/************************************************************************/
/******************* FORCE RELOAD AND STOP ******************************/
/************************************************************************/
function forceReloadAndStop(){
	?>
	<script type="text/javascript">
		$().ready(function(){
			try {
				window.parent.location.href = window.parent.location.href;
			} catch (error) { alert(error); }
		});
	</script>
	<?php
	exit(0);
}

/************************************************************************/
/******************* GET FULL URL ***************************************/
/************************************************************************/
function getFullUrl(){ 
	return 'http' . (empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? 's' : null) . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; 
} 
?>