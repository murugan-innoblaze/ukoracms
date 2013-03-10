<?php
class Facebook { 

	function __construct($db){
		$this->db = $db;
		$this->access_token = isset($_COOKIE['access_token']) ? $_COOKIE['access_token'] : getSiteData('facebook_access_token');
	}
	
	private function getRemoteContents($url = false){
		if(!$url) return false;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$output = curl_exec($ch);
		curl_close($ch); 
		if($output){
			return $output;
		}else{
			return false;
		}
	}
	
	//input type string
	public function fbQuery($query = false){
		if(!$query) return false;
		if(!$this->access_token) return false;
		if(false !== ($return_string = self::getRemoteContents('https://api.facebook.com/method/fql.query?access_token=' . $this->access_token . '&format=json&query=' . urlencode($query)))){
			return json_decode($return_string, true);
		}else{
			return false;
		}
	}
	
	//input type array
	public function fbMultiQuery($queries = array()){
		if(empty($queries)) return false;
		if(!$this->access_token) return false;
		if(false !== ($return_string = self::getRemoteContents('https://api.facebook.com/method/fql.multiquery?access_token=' . $this->access_token . '&format=json&queries=' . urlencode(json_encode($queries))))){
			return json_decode($return_string, true);
		}else{
			return false;
		}
	}
	
	//publish to stream
	public function streamPublish($message = null, $target_id = FACEBOOK_FAN_PAGE_ID){
		if(empty($message)) return false;
		if(empty($target_id)) return false;
		if(!$this->access_token) return false;
		if(false !== ($return_string = self::getRemoteContents('https://api.facebook.com/method/stream.publish?access_token=' . $this->access_token . '&target_id=' . $target_id . '&message=' . urlencode($message)))){
			return $return_string;
		}else{
			return false;
		}
	}
		
}
?>