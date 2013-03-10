<?php

/************************************************************************/
/******************* PREPARE STRING FOR URL *****************************/
/************************************************************************/
function removeWhitespace($string = null){
	if(!have($string)){ return null; }
	return trim(preg_replace('/[\s\t\n]+/', ' ', $string));
}

/************************************************************************/
/******************* PREPARE STRING FOR URL *****************************/
/************************************************************************/
function prepareStringForUrl($string = null){
	return strtolower(preg_replace('/[\s]+/i', '-', trim(preg_replace('/[^a-z^0-9]+/i', ' ', $string))));
} //prepareStringForUrl

/************************************************************************/
/******************* STRIP QUOTES FOR USE IN TAG ************************/
/************************************************************************/
function prepareTag($string = null){
	return fixDoubleEncodedEntities(htmlentities(trim(str_replace(array("'", '"'), array('', ''), removeUnknownCharacters($string)))));
} //prepareTag

/************************************************************************/
/******************* BUILD HIDDEN FIELDS BLOCK FROM GET *****************/
/************************************************************************/
function cleanString($string = null){
	return strtolower(preg_replace('/[\s]+/i', '-', trim(preg_replace('/[^a-z^0-9]+/i', ' ', $string))));
} //cleanString

/************************************************************************/
/******************* PREPARE FOR HTML ***********************************/
/************************************************************************/
function prepareStringHtml($string = null){
	return fixDoubleEncodedEntities(htmlentities(trim(stripslashes(strip_tags(removeUnknownCharacters($string))))));
} //prepareStringHtml

/************************************************************************/
/******************* PREPARE FOR HTML NO UCWORDS ************************/
/************************************************************************/
function prepareStringHtmlFlat($string = null){
	return fixDoubleEncodedEntities(htmlentities(trim(stripslashes(strip_tags(removeUnknownCharacters($string))))));
} //prepareStringHtml

/************************************************************************/
/******************* REMOVE UNKOWN CHARACTERS ***************************/
/************************************************************************/
function removeUnknownCharacters($string = null){
	return trim(preg_replace('/[\s]+/', ' ', preg_replace('/[^a-z^0-9^&^;^,^\.^\-^_^\+^\=^\s^\/^:^#^\'^"^!^@^(^)^\$^%^\?\>\<]+/i', ' ', $string)));
}

/************************************************************************/
/******************* FIX DOUBLE ENCODED ENTITIES ************************/
/************************************************************************/
function fixDoubleEncodedEntities($string = null){
	if(empty($string)){ return null; }
	//return $string;
	return preg_replace('/&([a-z0-9;&#]*);([a-z0-9#]+);/msi', '&$2;', $string);
}

/************************************************************************/
/******************* PREPARE STRING LINK ********************************/
/************************************************************************/
function prepareLink($link){
	if(empty($link)){ return null; }
	return htmlentities($link);
}

/************************************************************************/
/******************* LIMIT STRING FOR SO MANY CHARACTERS ****************/
/************************************************************************/
function limitString($string = null, $limit = 30, $string_end = '...'){
	if(empty($string)){ return null; } if(!is_numeric($limit)){ return null; }
	return (strlen($string) > $limit) ? substr($string, 0, (int)$limit) . $string_end : $string;
}

/************************************************************************/
/******************* COMPRESS STRING FOR SO MANY CHARACTERS *************/
/************************************************************************/
function compressString($string = null, $limit = 30, $compress_string = '...'){
	if(empty($string)){ return null; } if(!is_numeric($limit)){ return null; }
	return (strlen($string) > $limit) ? substr($string, 0, ((int)$limit/2) - strlen($compress_string)) . $compress_string . substr($string, -((int)$limit/2)) : $string;
}

/************************************************************************/
/******************* GET TIME DIFFERENCE ********************************/
/************************************************************************/
function convertToTimeAgo($then){
	if(!is_numeric($then)){ $then = strtotime($then); }	$now = time(); $time_difference = $now - $then;
	if(($time_difference / ( 60 * 60 * 24 * 365 * 1 )) >=  1){
		$years = round($time_difference / ( 60 * 60 * 24 * 365 * 1 )); $return = $years . ' year'; if($years > 1){ $return .= 's'; }
	}elseif(($time_difference / ( 60 * 60 * 24 * 30 * 1 )) >= 1){
		$months = round($time_difference / ( 60 * 60 * 24 * 30 * 1 )); $return = $months . ' month'; if($months > 1){ $return .= 's'; }
	}elseif(($time_difference / ( 60 * 60 * 24 * 7 * 1 )) >= 1){
		$weeks = round($time_difference / ( 60 * 60 * 24 * 7 * 1 )); $return = $weeks . ' week'; if($weeks > 1){ $return .= 's'; }
	}elseif(($time_difference / ( 60 * 60 * 24 * 1 )) >= 1){
		$days = round($time_difference / ( 60 * 60 * 24 * 1 )); $return = $days . ' day'; if($days > 1){ $return .= 's'; }
	}elseif(($time_difference / ( 60 * 60 * 1 )) >= 1){
		$hours = round($time_difference / ( 60 * 60 * 1 )); $return = $hours . ' hour'; if($hours > 1){ $return .= 's'; }
	}elseif(($time_difference / ( 60 * 1 )) >= 1){
		$minutes = round($time_difference / ( 60 * 1 )); $return = $minutes . ' minute'; if($minutes > 1){ $return .= 's'; }
	}else{
		$return = $time_difference . ' seconds';
	}
	return $return;
} //convertToTimeAgo

/************************************************************************/
/******************* CONVERT NUMBER FOR URL *****************************/
/************************************************************************/
function convertNumber($string, $reverse = false){
	$convert = array('0' => 'D', '1' => 'r', '2' => 'K', '3' => 'A', '4' => 'q', '5' => 'd', '6' => 'p', '7' => 'P', '8' => 'J', '9' => 'm');
	if(false !== $reverse){	$convert = array_flip($convert); } $return = ''; $array = str_split($string); 
	if(!empty($array)){ foreach($array as $subject){ $return .= $convert[$subject]; } return $return; }else{ return false; }
}

/************************************************************************/
/******************* XML TO ARRAY ***************************************/
/************************************************************************/
function unserialize_xml($input, $callback = null, $recurse = false){
    $data = ((!$recurse) && is_string($input))? simplexml_load_string($input): $input;
    if ($data instanceof SimpleXMLElement) $data = (array) $data;
    if (is_array($data)) foreach ($data as &$item) $item = unserialize_xml($item, $callback, true);
    return (!is_array($data) && is_callable($callback))? call_user_func($callback, $data): $data;
}

/************************************************************************/
/******************* PREPEND MATCH **************************************/
/************************************************************************/
function matchPrepend($prepend, $string){
	if(substr($string, 0, strlen($prepend)) == $prepend){ return true; }
	return false;
}

/************************************************************************/
/******************* LEADING 0 ******************************************/
/************************************************************************/
function leading_zero($the_number = null, $length = 2){
	if(!have($the_number)){ return null; }
	$return = null; if(strlen($the_number) < $length){ for($i = 0; $i < $length - strlen($the_number); $i++){ $return .= '0'; } } $return .= $the_number;
	return $return;
}

/************************************************************************/
/******************* ADD LINKS ******************************************/
/************************************************************************/
function addLinksToText($text = null){
	if(!have($text)){ return null; }
	return trim(preg_replace('/(http[s]?:\/\/[a-z0-9\.\/\-\_]+)[\s\t\n]+/i', '<a href="$1" target="_blank" title="link">link</a> ', $text . ' '));
}

/************************************************************************/
/******************* BREAK UP STRING ************************************/
/************************************************************************/
function breakUpString($string = null){
	$return = '<script type="text/javascript">' . "\n" . '<!--' . "\n" . 'document.write('; for($i = 0; $i <= strlen($string); $i += 4){ $return .= '\'' . substr($string, $i, 4) . '\' + '; } $return .= '\'\');' . "\n" . '//-->' . "\n" . '</script>';
	return $return;
}

/************************************************************************/
/******************* STRIP SLASHES **************************************/
/************************************************************************/
function stripAllSlashes($string = null){
	return stripslashes(preg_replace('/([\\\]+)/', '\\', $string));
}

/************************************************************************/
/******************* SALT IT BABY ***************************************/
/************************************************************************/
function saltString($string = null){
	if(!have($string)){ return false; }
	return md5(md5($string . SITE_SALT) . SITE_SALT);
}

?>