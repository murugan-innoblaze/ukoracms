<?php
class Visitor {

	function __construct($db){
		/*********************************/
		/******** database connection ****/
		/*********************************/
		$this->db = $db;
		/*********************************/
		/******** start session **********/
		/*********************************/
		if(self::session_started() == false){ session_start(); }
		/*********************************/
		/**** search entry point *********/
		/*********************************/
		$this->search_engine = null;
		$this->search_query = null;
		/*********************************/
		/**** other info *****************/
		/*********************************/		
		$this->referrer_url = null;
		/*********************************/
		/**** create id string holder ****/
		/*********************************/
		$this->dzpro_identity_id = 0;
		$this->dzpro_identity_string = '';
		$this->created_dzpro_identity_string = md5(IDENTITY_SALT . microtime());
		/*********************************/
		/******** actions ****************/
		/*********************************/
		if(!isWebSpider()){ self::setOrGetIdentity(); }
	}
	
	/****************************************************************/
	/************* GET OR SET IDENTITY ******************************/
	/****************************************************************/		
	private function setOrGetIdentity(){
		if(isset($_COOKIE[IDENTITY_COOKIE_KEY]) and !empty($_COOKIE[IDENTITY_COOKIE_KEY])){
			$this->dzpro_identity_string = $_COOKIE[IDENTITY_COOKIE_KEY];
			if(false === self::updateIdentity()){
				if(false === self::setIdentity()){
					die('Identity problem');
				}
			}
		}else{
			if(false === self::setIdentity()){
				die('Identity problem');	
			}
		}
	}
	
	/****************************************************************/
	/************* SETTERS ******************************************/
	/****************************************************************/
	private function setIdentity(){
		setcookie(IDENTITY_COOKIE_KEY, $this->created_dzpro_identity_string, time() + IDENTITY_EXPIRATION, '/', COOKIE_URL_DOMAIN);
		return self::insertIdentity();
	}
	
	/****************************************************************/
	/************** GETTERS *****************************************/
	/****************************************************************/
	private function getIdentity($get_this_string){
		$sql = "
					SELECT 
						*
					FROM  
						dzpro_identities
					WHERE 
						dzpro_identity_string = '" . mysql_real_escape_string($get_this_string) . "'
					LIMIT 
						1
				";
		$result = mysql_query($sql) or die(mysql_error());
		if(mysql_num_rows($result) > 0){
			while($row = mysql_fetch_assoc($result)){
				$this->dzpro_identity_id = (int)$row['dzpro_identity_id'];
				//make the row data available
				$_SESSION[IDENTITY_COOKIE_KEY] = $row;
			}
			mysql_free_result($result);
			if(isset($_SESSION[IDENTITY_COOKIE_KEY]) and !empty($_SESSION[IDENTITY_COOKIE_KEY])){
				self::trackIdentityToUserLink();
				if(isset($this->search_engine) and !empty($this->search_engine)){ addToIntelligenceStack('search engine', $this->search_engine); }
				if(isset($this->search_query) and !empty($this->search_query)){ addToIntelligenceStack('search query', $this->search_query); }
				if(isset($this->referrer_url) and !empty($this->referrer_url)){ addToIntelligenceStack('referrer', $this->referrer_url); }
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	/****************************************************************/
	/************** INSERT IDENTITY *********************************/
	/****************************************************************/
	private function insertIdentity(){
		self::setSearchEntry();
		self::setReferrerUrl();
		$sql = "
					INSERT INTO
						dzpro_identities
					(
						dzpro_identity_string,
						dzpro_identity_date_added,
						dzpro_identity_iplong,
						dzpro_identity_search_engine,
						dzpro_identity_search_query,
						dzpro_identity_referrer,
						dzpro_identity_entry_uri,
						dzpro_identity_user_agent			
					) VALUES (
						'" . mysql_real_escape_string($this->created_dzpro_identity_string) . "',
						NOW(),
						'" . mysql_real_escape_string(ip2long($_SERVER['REMOTE_ADDR'])) . "',
						'" . mysql_real_escape_string($this->search_engine) . "',
						'" . mysql_real_escape_string($this->search_query) . "',
						'" . mysql_real_escape_string($this->referrer_url) . "',
						'" . mysql_real_escape_string($_SERVER['REQUEST_URI']) . "',
						'" . mysql_real_escape_string($_SERVER['HTTP_USER_AGENT']) . "'			
					)
				";
		mysql_query($sql) or die(mysql_error());
		if(mysql_insert_id() > 0){
			$this->dzpro_identity_id = mysql_insert_id();
			return self::getIdentity($this->created_dzpro_identity_string);
		}else{
			return false;
		}
	}

	/****************************************************************/
	/************** UPDATE IDENTITY *********************************/
	/****************************************************************/
	private function updateIdentity(){
		$sql = "
					UPDATE 
						dzpro_identities
					SET 
						dzpro_identity_total_visits = dzpro_identity_total_visits + 1,
						dzpro_identity_iplong = '" . mysql_real_escape_string(ip2long($_SERVER['REMOTE_ADDR'])) . "',
						dzpro_identity_user_agent = '" . mysql_real_escape_string($_SERVER['HTTP_USER_AGENT']) . "',
						dzpro_identity_recent_uri = '" . mysql_real_escape_string($_SERVER['REQUEST_URI']) . "'
					WHERE 
						dzpro_identity_string = '" . mysql_real_escape_string($this->dzpro_identity_string) . "'
					LIMIT 
						1
				";
		mysql_query($sql) or die(mysql_error());
		if(mysql_affected_rows() > 0){
			setcookie(IDENTITY_COOKIE_KEY, $this->dzpro_identity_string, time() + IDENTITY_EXPIRATION, '/', COOKIE_URL_DOMAIN);
			return self::getIdentity($this->dzpro_identity_string);
		}else{
			return false;
		}
	}

	/****************************************************************/
	/************** CHECK SESSION EXISTENCE *************************/
	/****************************************************************/	
	private function session_started(){
    	if(isset($_SESSION)){ return true; }else{ return false; }
	}

	/****************************************************************/
	/************** TRACK IDENTITY - USER LINK **********************/
	/****************************************************************/
	private function trackIdentityToUserLink(){
		if(isset($_SESSION['front-end-user']['dzpro_user_id'])){
			$update_sql = "
							UPDATE 
								dzpro_identity_to_user
							SET 
								dzpro_identity_to_user_date_added = NOW()
							WHERE 
								dzpro_user_id = " . (int)$_SESSION['front-end-user']['dzpro_user_id'] . "
							AND 
								dzpro_identity_id = " . (int)$this->dzpro_identity_id . "
						";
			mysql_query($update_sql) or die(mysql_error());
			if(mysql_affected_rows() == 0 and (int)$this->dzpro_identity_id > 0){
				$insert_sql = "
								INSERT INTO 
									dzpro_identity_to_user
								(
									dzpro_user_id,
									dzpro_identity_id,
									dzpro_identity_to_user_date_added
								) VALUES (
									" . (int)$_SESSION['front-end-user']['dzpro_user_id'] . ",
									" . (int)$this->dzpro_identity_id . ",
									NOW()
								)
							";
				mysql_query($insert_sql) or die(mysql_error());
			}
		}
	}
	
	/****************************************************************/
	/************** CHECK SEARCH ENTRY ******************************/
	/****************************************************************/
	private function setSearchEntry(){
		$SeReferer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : null;
		if(!empty($SeReferer)){
			$searchEngines = array(
				'google.' => 'google.com',
				'search.yahoo.' => 'search.yahoo.com',
				'live.' => 'live.com',
				'bing.' => 'bing.com',
				'ask.' => 'ask.com',
				'babylon.' => 'babylon.com',
				'conduit.' => 'conduit.com',
				'search-results.' => 'search-results.com',
				'yandex.' => 'yandex.com',
				'excite.' => 'excite.com'
			);
			$SeQueryMatch = array(); 
			$SeDomainMatch = array();
			if(preg_match('/[&\?](q|p|w|searchfor|as_q|as_epq|s|query|search|text|qkw)=([^&]+)/i', $SeReferer, $SeQueryMatch)){ 
				if(preg_match('/https?:\/\/([^\/]+)\//i', $SeReferer, $SeDomainMatch)){
					$this->search_engine = isset($SeDomainMatch[1]) ? strtolower($SeDomainMatch[1]) : null;
					$this->search_query = isset($SeQueryMatch[2]) ? urldecode($SeQueryMatch[2]) : null;	
					foreach($searchEngines as $searchKey => $searchEngine){
						if(stripos($SeReferer, $searchKey) !== false){ $this->search_engine = $searchEngine; }
					}
				}
			}
		}
	}
	
	/****************************************************************/
	/************** SET REFERRER URL ********************************/
	/****************************************************************/
	private function setReferrerUrl(){
		$Referer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : null;
		if(!empty($Referer)){
			$DomainMatch = array();
			if(preg_match('/https?:\/\/([^\/]+)\//i', $Referer, $DomainMatch)){
			 	$this->referrer_url = isset($DomainMatch[1]) ? strtolower($DomainMatch[1]) : null;
			}
		}	
	}

}
?>