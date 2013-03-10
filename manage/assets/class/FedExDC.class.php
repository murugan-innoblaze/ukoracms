<?php

class FedExDC {

    var $VERSION = '0.3';
    var $NAME = 'FedExDC';
    var $ERROR_STR = false;

    //this will be the field returned by FedEx
    //containing the binary image data
    var $image_key;

    // FedEx API URI
    var $fedex_uri;

    // referer host
    var $referer;

    // set the timeout
    var $timeout;

    var $FE_TT = array (
        '1002' =>  array ('007','FDXG'),
        '1005' =>  array ('023','FDXE'),
        '2016' =>  array ('021','FDXE'),
        '2017' =>  array ('022','FDXE'),
        '2018' =>  array ('019','FDXE'),
        '2024' =>  array ('025',''),
        '2025' =>  array ('410',''),
        '3000' =>  array ('021','FDXG'),
        '3001' =>  array ('023','FDXG'),
        '3003' =>  array ('211',''),
        '3004' =>  array ('022','FDXG'),
        '5000' =>  array ('402',''),
        '5001' =>  array ('405',''),
        '5002' =>  array ('403','')
    );

    function FedExDC($account, $meter='', $params = array()) {
        $this->account  = $account;
        $this->meter    = $meter;
        $this->time_start = $this->getmicrotime();
        
        // param defaults
        $this->fedex_uri = FEDEX_URI;
        $this->fedex_host = FEDEX_HOST;
        $this->referer = FEDEX_REQUEST_REFERER;
        $this->timeout = FEDEX_REQUEST_TIMEOUT;
        $this->image_key = 188;
        foreach ($params as $key => $value) {
            $this->{$key} = $value;
        }
    }

    function debug($string){
        $this->debug_str .= get_class($this).": $string\n";
    }

    function getError(){
        if($this->ERROR_STR != ""){
            return $this->ERROR_STR;
        }
        return false;
    }

    function setError($str){
    	handleError(1, $str);
        $this->ERROR_STR .= $str;
    }

    function getmicrotime(){
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
    }

    function setData($uti, $vals) {
        $this->sBuf = '';
        if (empty($vals[0]))    $vals[0] = $this->FE_TT[$uti][0];
        if (empty($vals[3025])) $vals[3025] = $this->FE_TT[$uti][1];
        if (isset($this->account) and !array_key_exists(10, $vals)) $this->sBuf .= '10,"' . $this->account . '"';
        if (isset($this->meter) and !array_key_exists(498, $vals))  $this->sBuf .= '498,"' .$this->meter. '"';
        foreach ($vals as $key => $val) {
            if (preg_match('/^([0-9]+)\-?[0-9]?$/', $key)) { //let users use the hyphenated number fields
                $this->sBuf .= "$key,\"$val\"";
            } else { continue; }
        }
        $time = $this->getmicrotime() - $this->time_start;
        $this->debug('setData: build FedEx string ('. $time.')');
        return $this->sBuf .= '99,""';
    }

    function _splitData(){
        $this->rHash = array();
        $count = 0;
        $st_key = 0; //start the first key at 0
        $aFedRet = preg_split('/,"/s', $this->httpBody);
        foreach ($aFedRet as $chunk) {
            preg_match('/(.*)"([\d+\-?]+)/s', $chunk, $match);
            if (empty($match[1])) continue;
            if ($st_key == 99) continue;
            $this->rHash[$st_key] = $match[1];
            $st_key = $match[2]; //this will be the next key
        }
        $time = $this->getmicrotime() - $this->time_start;
        $this->debug('_splitData: Parse FedEx response ('. $time.')');            
        if($this->rHash[2]){
            $this->setError("FedEx Return Error ". $this->rHash[2]." : ".$this->rHash[3]);
        }
        return $this->rHash;
    }

    function label($label_file = false){
        $this->httpLabel = $this->rHash[$this->image_key];
        if($this->httpLabel = preg_replace('/%([0-9][0-9])/e', "chr(hexdec($1))", $this->httpLabel)){
        	$this->debug('separate binary image data');
        	$this->debug('decoded binary label data');
        }
        if($label_file){
            $this->debug('label: trying to write out label to ' . $label_file);
            if(strlen($this->httpLabel) < 100){
           		$this->setError("No valid label returned $label_file");
            	return false;           
            }
            $FH = fopen($label_file, "w+b");
            if(!fwrite($FH, $this->httpLabel)){
           		$this->setError("Can't write to file $label_file");
            	return false;
       		}
        	fclose($FH);
        }else{
      		return $this->httpLabel;
    	}
	}

    function transaction($buf = false){
        if($buf) $this->sBuf = $buf;
        if(FEDEX_REQUEST_TYPE == 'CURL'){
            $meth = '_sendCurl';
        }
        if($this->$meth()){
            $this->_splitData();
            return $this->rHash;
        }else{
            return false;
        }
    }

    function _sendCurl(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->fedex_uri);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
              'Referer: '. $this->referer
	        , 'Host: ' . $this->fedex_host
            , 'User-Agent: '. $this->NAME .'-'. $this->VERSION . ' class ( http://' . HOST_NAME . '/ )'
	        , 'Accept: image/gif, image/jpeg, image/pjpeg, text/plain, text/html, */*'
	        , 'Content-Type: image/gif'
	        , 'Content-Length: '. strlen($this->sBuf)
     	));
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $this->sBuf );
        $this->debug('Sending to FedEx with data length: '.strlen($this->sBuf));
        $this->httpData = curl_exec($ch);
        if (curl_errno($ch) != 0){
            $err = "cURL ERROR: ".curl_errno($ch).": ".curl_error($ch)."<br>";
            $this->setError($err);
            curl_close($ch);
            return false;
        }
        curl_close($ch);

        // separate content from HTTP headers
        if(ereg("^(.*)\r?\n\r?\n", $this->httpData)) {
            $this->debug("found proper headers and document");
            $this->httpBody = ereg_replace("^[^<]*\r\n\r\n","", $this->httpData);
            $this->debug("remove headers, body length: ".strlen($this->httpBody));
        } else {
            $this->debug("headers and body are not properly separated");
            $this->setError('headers and body are not properly separated');
            return false;
        }

        if(strlen($this->httpBody) == 0){
            $this->debug("body contains no data");
            $this->setError("body contains no data");
            return false;
        }
        $time = $this->getmicrotime() - $this->time_start;
        $this->debug('Got response from FedEx ('. $time.')');
        return $this->httpBody;
    }
    
    
    function close_ground($aData) {
        $this->setData(1002, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process close_ground');
            return false;
        }
    }
    
    function cancel_express($aData){
        $this->setData(1005, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process cancel_express');
            return false;
        }
    }

    function ship_express($aData){
        $this->setData(2016, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process ship_express');
            return false;
        }
    }
    
    function global_rate_express($aData){
        $this->setData(2017, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process global_rate');
            return false;
        }
    }
    
    function service_avail($aData){
        $this->setData(2018, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process service_avail');
            return false;
        }
    }

    function rate_services($aData){
        $this->setData(2024, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process rate_services');
            return false;
        }
    }
    
    function fedex_locater($aData){
        $this->setData(2025, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process fedex_locater');
            return false;
        }
    }

    function ship_ground($aData){
        $this->setData(3000, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process ship_ground');
            return false;
        }
    }

    function cancel_ground($aData){
        $this->setData(3001, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process cancel_ground');
            return false;
        }
    }

    function subscribe($aData){
        $this->setData(3003, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process subscribe');
            return false;
        }
    }

    function global_rate_ground($aData){
        $this->setData(3004, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process global_rate');
            return false;
        }
    }

    function sig_proof_delivery($aData){
        $this->image_key = 1471;
        $this->setData(5001, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process sig_proof_delivery');
            return false;
        }
    }
    
    function track($aData){
        $this->setData(5000, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process track');
            return false;
        }
    }
    
    function ref_track($aData){
        $this->setData(5002, $aData);
        if ($aRet = $this->transaction()) {
            return $aRet;
        } else {
            $this->setError('unable to process ref_track');
            return false;
        }
    }
}

?>