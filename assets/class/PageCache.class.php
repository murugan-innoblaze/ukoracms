<?php

class PageCache extends Page {

	/*******************************************/
	/*********** PAGE CONSTRUCTOR **************/
	/*******************************************/
	function __construct($db, $cache_expiration = '-1 hour'){

		//construct parent
		parent::__construct($db);
		
		//page errors
		$this->page_errors = array();
		
		//cache expiration
		$this->cache_expiration = $cache_expiration;
		
		//assume we're not saving this page
		$this->save_page_cache = false;
		
		//page key
		$this->page_cache_key = $_SERVER['REQUEST_URI'];
		
		//page content
		$this->page_cache_content = null; 
		
		//memcache??
		$this->Memcache = null; //class holder
		$this->Memcache_compress_data = false; //are we compressing data (requires zlib)
		$this->page_cache_memcache = self::tryMemcacheConnect();
		
		//handle no cache key
		if(isset($_GET['nocache']) and $this->page_cache_memcache){ $this->Memcache->delete(HOST_NAME . ':pageCache:' . $this->page_cache_key); }
		
		//start output buffer
		//if(self::shouldWeGetCached()){ if(self::seeIfWeHaveCachedVersion()){ echo $this->page_cache_content; pageUnload(); exit(); } self::startOutputBuffer(); }
		
	}

	/*******************************************/
	/*********** TRY TO USE MEMCACHED **********/
	/*******************************************/
	protected function tryMemcacheConnect(){
		if(class_exists('Memcache')){ $this->Memcache = new Memcache; if(false !== $this->Memcache->connect('localhost', 11211)){ return true; } }
		return false;	
	}

	/*******************************************/
	/*********** GET CACHE DURATION ************/
	/*******************************************/	
	protected function getCacheDuration(){
		return date('U') - strtotime($this->cache_expiration);
	}

	/*******************************************/
	/*********** CHECK FOR PAGE ERRORS *********/
	/*******************************************/
	protected function checkForScriptErrors(){
		$this->page_errors = error_get_last(); if(have($this->page_errors)){ return true; }
		return false;
	}

	/*******************************************/
	/*********** GET CACHE *********************/
	/*******************************************/
	protected function shouldWeGetCached(){
		if(have($_POST)){ return false; }
		if(self::checkForScriptErrors()){ return false; }
		if(activeUserSession()){ return false; }
		return true;
	}
	
	/*******************************************/
	/*********** START OUTPUT BUFFER ***********/
	/*******************************************/	
	public function startOutputBuffer(){
	
		//we are saving this page
		$this->save_page_cache = true;
		
		//start the output buffer
		ob_start();
		
	}

	/*******************************************/
	/*********** SEE IF WE HAVE CACHED *********/
	/*******************************************/
	public function seeIfWeHaveCachedVersion(){
		if(!have($this->page_cache_key)){ return false; }
		if(isset($_GET['nocache'])){ return false; }
		if($this->page_cache_memcache){ $this->page_cache_content = $this->Memcache->get(HOST_NAME . ':pageCache:' . $this->page_cache_key); if(have($this->page_cache_content)){ return true; } }else{ $result = @mysql_query(" SELECT dzpro_cache_content FROM dzpro_cache WHERE dzpro_cache_key = '" . mysql_real_escape_string($this->page_cache_key) . "' AND dzpro_cache_last_modified > '" . mysql_real_escape_string(date('Y-m-d H:i:s', strtotime($this->cache_expiration))) . "' "); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $this->page_cache_content = $row['dzpro_cache_content']; } mysql_free_result($result); return true; } }
		return false;
	}
	
	/*******************************************/
	/*********** SAVE OUTPUT BUFFER ************/
	/*******************************************/
	public function savePageCache(){
		$this->page_cache_content = ob_get_contents(); ob_flush();
		if(have($this->page_cache_content) and $this->save_page_cache === true){ if($this->page_cache_memcache){ if(false === $this->Memcache->replace(HOST_NAME . ':pageCache:' . $this->page_cache_key, $this->page_cache_content, $this->Memcache_compress_data, self::getCacheDuration())){ $this->Memcache->set(HOST_NAME . ':pageCache:' . $this->page_cache_key, $this->page_cache_content, $this->Memcache_compress_data, self::getCacheDuration()); } }else{ @mysql_query(" INSERT INTO dzpro_cache ( dzpro_cache_key, dzpro_cache_content, dzpro_cache_date_added ) VALUES ( '" . mysql_real_escape_string($this->page_cache_key) . "', '" . mysql_real_escape_string($this->page_cache_content) . "', NOW() ) ON DUPLICATE KEY UPDATE dzpro_cache_content = '" . mysql_real_escape_string($this->page_cache_content) . "', dzpro_cache_date_added = NOW(), dzpro_cache_last_modified = NOW() "); } } 
		return null;
	}

}

?>