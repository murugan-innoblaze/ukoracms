<?php
/************************************************************************/
/******************* GET INTELLIGENCE VALUES ARRAY **********************/
/************************************************************************/
function getIntelligenceValues($key = null, $user_id = null, $visitor_id = null, $from_date = '-1 month', $to_date = 'now', $limit = 20, $order_by = 'dzpro_intelligence_date_added DESC'){
	if(empty($key)){ return array(); }
	if(!empty($user_id)){ $user_filter_sql = " AND dzpro_intelligence.dzpro_user_id = " . (int)$user_id; }
	if(!empty($visitor_id)){ $visitor_filter_sql = " AND dzpro_intelligence.dzpro_visitor_id = " . (int)$visitor_id; }
	$return_array = array(); $result = @mysql_query("SELECT SQL_CALC_FOUND_ROWS *, COUNT(dzpro_intelligence.dzpro_intelligence_meta_id) AS occurance FROM dzpro_intelligence_meta LEFT JOIN dzpro_intelligence_data ON dzpro_intelligence_data.dzpro_intelligence_data_id = dzpro_intelligence_meta.dzpro_intelligence_data_id LEFT JOIN dzpro_intelligence ON dzpro_intelligence_data.dzpro_intelligence_data_id = dzpro_intelligence.dzpro_intelligence_data_id AND dzpro_intelligence.dzpro_intelligence_meta_id = dzpro_intelligence_meta.dzpro_intelligence_meta_id WHERE dzpro_intelligence_data_name = '" . mysql_real_escape_string($key) . "' AND dzpro_intelligence_date_added BETWEEN '" . mysql_real_escape_string(date('Y-m-d H:i:s', strtotime($from_date))) . "' AND '" . mysql_real_escape_string(date('Y-m-d H:i:s', strtotime($to_date))) . "' " . $user_filter_sql . " " . $visitor_filter_sql . " GROUP BY dzpro_intelligence.dzpro_intelligence_meta_id ORDER BY " . $order_by . " LIMIT " . (int)$limit); $found_rows_result = @mysql_query("SELECT FOUND_ROWS();"); if(mysql_num_rows($found_rows_result) > 0){ $found_rows_row = mysql_fetch_row($found_rows_result); $return_array['total_found'] = $found_rows_row[0]; mysql_free_result($found_rows_result); } if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $return_array['results'][] = $row; } mysql_free_result($result); return $return_array; }
	return array();
}

/************************************************************************/
/******************* ADD TO INTELLIGENCE STACK **************************/
/************************************************************************/
function addToIntelligenceStack($data_name = null, $value = null){
	if(empty($value)){ return null; }
	if(!isWebSpider()){
		assureSession();
		$user_id = isset($_SESSION['front-end-user']['dzpro_user_id']) ? (int)$_SESSION['front-end-user']['dzpro_user_id'] : null;
		$identity_id = isset($_SESSION[IDENTITY_COOKIE_KEY]['dzpro_identity_id']) ? (int)$_SESSION[IDENTITY_COOKIE_KEY]['dzpro_identity_id'] : null;
		$_SESSION['intelligence_stack'][] = array('identity_id' => $identity_id, 'user_id' => $user_id,	'data_name' => $data_name, 'value' => $value);
	}
}

/************************************************************************/
/******************* INTRODUCE INTELLIGENCE META ************************/
/************************************************************************/
function introduceIntelligenceMeta($value, $data_id){
	$count_result = @mysql_query("SELECT dzpro_intelligence_meta_id FROM dzpro_intelligence_meta WHERE dzpro_intelligence_meta_value = '" . mysql_real_escape_string($value) . "' AND dzpro_intelligence_data_id = " . (int)$data_id); 
	if(mysql_num_rows($count_result)){ $count_row = mysql_fetch_row($count_result); }
	if(isset($count_row[0]) and $count_row[0] > 0){
		@mysql_query("UPDATE dzpro_intelligence_meta SET dzpro_intelligence_meta_count = dzpro_intelligence_meta_count + 1 WHERE dzpro_intelligence_meta_id = " . (int)$count_row[0]); 
		return (int)$count_row[0];
	}else{
		@mysql_query("INSERT INTO dzpro_intelligence_meta (dzpro_intelligence_meta_value, dzpro_intelligence_data_id, dzpro_intelligence_meta_date_added) VALUES ('" . mysql_real_escape_string($value) . "', " . (int)$data_id . ", NOW())"); 
		 return mysql_insert_id();
	}
}

/************************************************************************/
/******************* INTRODUCE INTELLIGENCE DATA ************************/
/************************************************************************/
function introduceIntelligenceData($data_name = null){
	if(empty($data_name)){ return false; }
	$count_result = @mysql_query("SELECT dzpro_intelligence_data_id FROM dzpro_intelligence_data WHERE dzpro_intelligence_data_name = '" . mysql_real_escape_string($data_name) . "'"); if(mysql_num_rows($count_result)){ $count_row = mysql_fetch_row($count_result); }
	if(isset($count_row[0]) and $count_row[0] > 0){ 
		return $count_row[0]; 
	}else{
		@mysql_query("INSERT INTO dzpro_intelligence_data (dzpro_intelligence_data_name, dzpro_intelligence_data_date_added) VALUES ('" . mysql_real_escape_string($data_name) . "', NOW())"); if(mysql_insert_id() > 0){ return mysql_insert_id(); }else{ return false; }
	}
}

/************************************************************************/
/******************* SAVE INTELLIGENCE STACK ****************************/
/************************************************************************/
function insertIntelligenceStack(){
	assureSession(); $stack_to_save = isset($_SESSION['intelligence_stack']) ? $_SESSION['intelligence_stack'] : null; $_SESSION['intelligence_stack'] = array(); unset($_SESSION['intelligence_stack']);
	if(!empty($stack_to_save) and !isWebSpider()){
		$insert_sql = "INSERT INTO dzpro_intelligence (dzpro_user_id, dzpro_identity_id, dzpro_intelligence_data_id, dzpro_intelligence_meta_id, dzpro_intelligence_date_added) VALUES ";
		foreach($stack_to_save as $the_stack){ if(false !== ($data_id = introduceIntelligenceData($the_stack['data_name']))){ $insert_sql .= "(" . (int)$the_stack['user_id'] . ", " . (int)$the_stack['identity_id'] . ", " . (int)$data_id . ", '" . introduceIntelligenceMeta($the_stack['value'], (int)$data_id) . "', NOW()),"; } } @mysql_query(substr($insert_sql, 0, -1)); unset($insert_sql); unset($stack_to_save); return true; 
	}else{ return false; }
}

/************************************************************************/
/******************* SAVE INTELLIGENCE STACK - AND EXIT *****************/
/************************************************************************/
function saveStackAndExit(){
	insertIntelligenceStack(); exit(0);
}

/************************************************************************/
/******************* BUILD HIDDEN FIELDS BLOCK FROM GET *****************/
/************************************************************************/
function isWebSpider(){
	if(!isset($_SERVER["HTTP_USER_AGENT"]) or empty($_SERVER["HTTP_USER_AGENT"])){ return false; }
	$agentArray = array("ArchitextSpider", "Googlebot", "TeomaAgent", "Exabot", "AdsBot-Google", "Zyborg", "Gulliver", "Architext spider", "FAST-WebCrawler", "bingbot", "W3C_Validator", "Slurp", "Ask Jeeves", "ia_archiver", "Scooter", "Mercator", "crawler@fast", "Crawler", "InfoSeek Sidewinder", "Googlebot-Image", "ia_archiver", "almaden.ibm.com", "appie 1.1", "augurfind", "baiduspider", "bannana_bot", "bdcindexer", "docomo", "frooglebot", "geobot", "henrythemiragorobot", "sidewinder", "lachesis", "moget", "nationaldirectory-webspider", "naverrobot", "ncsa beta", "netresearchserver", "ng/1.0", "osis-project", "polybot", "pompos", "seventwentyfour", "steeler", "szukacz", "teoma", "turnitinbot", "vagabondo", "zao", "zyborg", "Lycos_Spider_(T-Rex)", "Lycos_Spider_Beta2(T-Rex)", "Fluffy the Spider", "Ultraseek", "MantraAgent", "Moget", "T-H-U-N-D-E-R-S-T-O-N-E", "MuscatFerret", "VoilaBot", "Sleek Spider", "KIT_Fireball", "WISEnut", "WebCrawler", "asterias2.0", "suchtop-bot", "YahooSeeker", "ai_archiver", "Jetbot", "msnbot", "Yanga", "TripAdvisorBot", "Twiceler", "Baiduspider", "OCP HRS", "libwww-perl", "Java", "Mail.Ru", "PHP/5.2", "InternetSeer", "Yandex", "Speedy Spider", "Spider", "NV32ts", "Ascertoge-Validator", "NetSprint", "Jakarta Commons", "CatchBot", "SitiDiBot", "Python-urllib", "SuperPagesBot", "Lynx/", "eventBot", "Yeti", "Loserbot", "MLBot", "semantics webbot", "A1 Keyword Research", "rdfbot", "curl/", "Xenu Link Sleuth", "Mediapartners-Google", "Chilkat", "MJ12bot", "facebook.com", "Google Web Preview", "PostRank", "feedfinder", "butterfly", "Feedfetcher", "NjuiceBot", "backtype.com", "Echofon", "Twitturly", "MetaURI", "Twitterbot", "bitlybot", "xpymep.exe", "simplepie", "gnip.com", "Pearltrees");
	foreach($agentArray as $agent){ if(stripos($_SERVER["HTTP_USER_AGENT"], $agent) !== false){ return true; } }
	return false;
} //isWebSpider

/************************************************************************/
/******************* IS THIS A MOBILE BROWSER ***************************/
/************************************************************************/
function isMobileBrowser(){
    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : ''; $mobile_browser = '0'; if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT']))){ $mobile_browser++; } if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false)){ $mobile_browser++; } if(isset($_SERVER['HTTP_X_WAP_PROFILE'])){ $mobile_browser++; } if(isset($_SERVER['HTTP_PROFILE'])){ $mobile_browser++; } $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4)); $mobile_agents = array(
                        'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
                        'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
                        'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
                        'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
                        'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
                        'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
                        'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
                        'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
                        'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-'
                        ); if(in_array($mobile_ua, $mobile_agents)){ $mobile_browser++; } if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false){ $mobile_browser++; } if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false){ $mobile_browser = 0; } if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false){ $mobile_browser++; } if($mobile_browser > 0){ return true; }else{ return false; }
}

/************************************************************************/
/******************* GET BROWSER DETAILS ********************************/
/************************************************************************/
function getBrowserDetails(){
	//get browser
	$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
	if(preg_match('/opera/i', $userAgent)){ $name = 'opera'; }
	elseif(preg_match('/chrome/i', $userAgent)){ $name = 'chrome'; }
	elseif(preg_match('/webkit/i', $userAgent)){ $name = 'safari'; }
	elseif(preg_match('/msie/i', $userAgent)){ $name = 'msie'; }
	elseif(preg_match('/mozilla/i', $userAgent) && !preg_match('/compatible/', $userAgent)){ $name = 'mozilla'; } 
    else{ $name = 'other'; } 
    //get version
    if(preg_match('/.+(?:rv|it|ra|ie)[\/: ]([\d.]+)/i', $userAgent, $matches)){ $version = $matches[1]; }else{ $version = 'unknown'; } 
    //get platform
    if(preg_match('/linux/i', $userAgent)){ $platform = 'linux'; }
    elseif(preg_match('/iphone/i', $userAgent)){ $platform = 'iphone'; }
    elseif(preg_match('/ipad/i', $userAgent)){ $platform = 'ipad'; }
    elseif(preg_match('/iphone/i', $userAgent)){ $platform = 'iphone'; }
	elseif(preg_match('/xbox/i', $userAgent)){ $platform = 'xbox'; }
	elseif(preg_match('/android/i', $userAgent)){ $platform = 'android'; }
	elseif(preg_match('/ubuntu/i', $userAgent)){ $platform = 'ubuntu'; }
    elseif(preg_match('/macintosh|mac os x/i', $userAgent)){ $platform = 'mac'; }
    elseif(preg_match('/windows|win32/i', $userAgent)){ $platform = 'windows'; }
    else{ $platform = 'ohter'; } 
    return array('browser' => $name, 'version' => $version, 'platform' => $platform, 'userAgent' => $userAgent); 
}

/************************************************************************/
/******************* STACK VISITOR MACHINE DETAILS **********************/
/************************************************************************/	
function stackVisitorMachineDetails(){
	$browser = getBrowserDetails();
	if(isset($browser['version']) and isset($browser['browser'])){ addToIntelligenceStack('browser version', $browser['browser'] . ' ' . $browser['version']); }
	if(isset($browser['browser'])){ addToIntelligenceStack('browser', $browser['browser']); }
	if(isset($browser['platform'])){ addToIntelligenceStack('platform', $browser['platform']); }
	if(isMobileBrowser()){ addToIntelligenceStack('device', 'mobile'); }else{ addToIntelligenceStack('device', 'desktop'); }
}

/************************************************************************/
/******************* STACK PAGE DETAILS *********************************/
/************************************************************************/	
function stackNavigationDetails(){
	if(isset($_SERVER['REQUEST_URI']) and !empty($_SERVER['REQUEST_URI'])){ addToIntelligenceStack('request uri', $_SERVER['REQUEST_URI']); addToIntelligenceStack('page view', 'page view'); }
}

/************************************************************************/
/******************* STACK VISITOR LOCATION *****************************/
/************************************************************************/	
function stackVisitorLocation(){
	if(function_exists('geoip_record_by_name')){
		$data = @geoip_record_by_name($_SERVER['REMOTE_ADDR']);
		if(isset($data['country_code']) and !empty($data['country_code'])){ addToIntelligenceStack('country', $data['country_code']); }
		if(isset($data['region']) and !empty($data['region'])){ addToIntelligenceStack('region', $data['region']); }
		if(isset($data['city']) and !empty($data['city'])){ addToIntelligenceStack('city', $data['city']); }
	}
}
?>