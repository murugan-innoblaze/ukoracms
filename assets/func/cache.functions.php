<?php

/****************************************************************************************/
/******************************** SAVE CACHE ********************************************/
/****************************************************************************************/
function saveCache($key = null, $value = null, $durantion = 600){
	if(!have($key)){ return false; }
	if(!class_exists('Memcache')){ return false; }
	$Memcache = new Memcache;
	if(false !== $Memcache->connect('localhost', 11211)){ 
		if(false === $Memcache->replace(HOST_NAME . ':cache:' . $key, $value, false, $durantion)){ 
			$Memcache->set(HOST_NAME . ':cache:' . $key, $value, false, $durantion); 
		}
		return true;
	}
	return false;
}

/****************************************************************************************/
/******************************** GET CACHE *********************************************/
/****************************************************************************************/
function getCache($key = null){
	if(!have($key)){ return false; }
	if(!class_exists('Memcache')){ return false; }
	$Memcache = new Memcache;
	if(false !== $Memcache->connect('localhost', 11211)){ return $Memcache->get(HOST_NAME . ':cache:' . $key); }
	return false;
}

?>