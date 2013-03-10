<?php

class ShippingQuote {

	function __construct($db){
		
		//connect to db
		$this->db = $db;
		
		//assure active session
		assureSession();
		
		//set shipping type
		$this->shipping_type = null;
		
		//origin zipcode
		$this->shipping_origin_zipcode = SHIPPING_ORIGIN_ZIPCODE;
		
		//shipping information
		$this->shipping_information = array();
		
		//set a handeling fee
		$this->shipping_handling_fee = null;
		
		//the quote...
		$this->shipping_quote = array();
		
		//the quote... supercharged :-)
		$this->shipping_quote_ext = array();
		
	}

	/***************************************************************************/
	/****************** SET SHIPPING DETAILS ***********************************/
	/***************************************************************************/
	public function setShippingDetails($zipcode = null, $weight = null, $residential = true, $width = 12, $length = 12, $height = 8){
		if(!have($zipcode) or !have($weight)){ return false; }
		$this->shipping_information['zipcode'] = $zipcode;
		$this->shipping_information['weight'] = $weight;
		$this->shipping_information['residential'] = $residential;
		$this->shipping_information['width'] = $width;
		$this->shipping_information['length'] = $length;
		$this->shipping_information['height'] = $height;
		return true;
	}
	
	/***************************************************************************/
	/****************** SET SHIPPING TYPE **************************************/
	/***************************************************************************/
	public function setShippingType($type = null){
		if(!have($type)){ return false; }
		$this->shipping_type = $type;
	}

	/***************************************************************************/
	/****************** SET SHIPPING TYPE **************************************/
	/***************************************************************************/
	public function setShippingHandlingFee($fee = null){
		if(!have($fee)){ return false; }
		$this->shipping_handling_fee = $fee;
	}

	/***************************************************************************/
	/****************** GET SHIPPING QUOTE *************************************/
	/***************************************************************************/	
	public function getShippingQuote(){
		if(!have($this->shipping_type)){ return false; }
		self::buildShippingQuote();
		return $this->shipping_quote;
	}

	/***************************************************************************/
	/****************** CONVERT SHIPPING QUOTE *********************************/
	/***************************************************************************/
	public function convertShippingQuote(){
		if(!have($this->shipping_type)){ return false; }
		if(!have($this->shipping_quote)){ return false; }
		foreach($this->shipping_quote as $shipping_type => $shipping_quote){
			if(have($shipping_quote)){
				foreach($shipping_quote as $shipping_key => $shipping_array){
					//keyed by transit time
					if(!isset($this->shipping_quote_ext[$shipping_type]['transit'][$shipping_array['transit']]) or (isset($this->shipping_quote_ext[$shipping_type]['transit'][$shipping_array['transit']]) and $this->shipping_quote_ext[$shipping_type]['transit'][$shipping_array['transit']]['transit'] >= $shipping_array['transit'] and $this->shipping_quote_ext[$shipping_type]['transit'][$shipping_array['transit']]['price'] > $shipping_array['price'])){ $this->shipping_quote_ext[$shipping_type]['transit'][$shipping_array['transit']] = $shipping_array; } 
					//keyed by price
					if(!isset($this->shipping_quote_ext[$shipping_type]['price'][(string)$shipping_array['price']]) or (isset($this->shipping_quote_ext[$shipping_type]['price'][(string)$shipping_array['price']]) and $this->shipping_quote_ext[$shipping_type]['price'][(string)$shipping_array['price']]['price'] > $shipping_array['price'] and $this->shipping_quote_ext[$shipping_type]['price'][(string)$shipping_array['price']]['transit'] >= $shipping_array['transit'])){ $this->shipping_quote_ext[$shipping_type]['price'][(string)$shipping_array['price']] = $shipping_array; }
					//keyed smartly
					if(!isset($this->shipping_quote_ext[$shipping_type]['smart'][$shipping_array['transit']]) or (isset($this->shipping_quote_ext[$shipping_type]['smart'][$shipping_array['transit']]) and $this->shipping_quote_ext[$shipping_type]['smart'][$shipping_array['transit']]['price'] > $shipping_array['price'] and $this->shipping_quote_ext[$shipping_type]['smart'][$shipping_array['transit']]['transit'] >= $shipping_array['transit'])){ if(isset($this->shipping_quote_ext[$shipping_type]['smart']) and have($this->shipping_quote_ext[$shipping_type]['smart'])){ $already_have_better_option = false; foreach($this->shipping_quote_ext[$shipping_type]['smart'] as $transit => $t_shipping_array){ if($t_shipping_array['price'] < $shipping_array['price'] and $t_shipping_array['transit'] <= $shipping_array['transit']){ $already_have_better_option = true; } } if(!$already_have_better_option){ $this->shipping_quote_ext[$shipping_type]['smart'][$shipping_array['transit']] = $shipping_array; } } }
				}
			}
			if(isset($this->shipping_quote_ext[$shipping_type]['price'])){ ksort($this->shipping_quote_ext[$shipping_type]['price']); }
			if(isset($this->shipping_quote_ext[$shipping_type]['transit'])){ ksort($this->shipping_quote_ext[$shipping_type]['transit']); }
			if(isset($this->shipping_quote_ext[$shipping_type]['smart'])){ ksort($this->shipping_quote_ext[$shipping_type]['smart']); }
		}
		return $this->shipping_quote_ext;
	}
	
	/***************************************************************************/
	/****************** BUILD SHIPPING QUOTE ***********************************/
	/***************************************************************************/
	protected function buildShippingQuote(){
		switch($this->shipping_type){
			case 'FedEx':
				self::buildFedExRateQuote();
			break;
			case 'USPS':
				self::buildUSPSRateQuote();
			break;
			default:
				return false;
			break;
		}
	}
	
	/***************************************************************************/
	/****************** BUILD SHIPPING QUOTE ***********************************/
	/***************************************************************************/	
	private function buildFedExRateQuote(){
		
		//fedex options reference only
		$shipping_options = array(
			'01' => array('name' => 'Air - Priority Overnight', 'transit' => 1),
            '03' => array('name' => 'Air - 2 Day Air', 'transit' => 2),
            '05' => array('name' => 'Air - Standard Overnight', 'transit' => 1),
           	'06' => array('name' => 'Air - First Overnight', 'transit' => 1),
           	'20' => array('name' => 'Air - Express Saver', 'transit' => 3),
         	'90' => array('name' => 'Ground - Home Delivery', 'transit' => null),
        	'92' => array('name' => 'Ground - Ground Service', 'transit' => null)
      	);
		
		//where are we getting the quote
		$fedex_quote_server = 'gateway.fedex.com/GatewayDC';
	
		//build the request
		$request = '0,"25"'; 																		// TransactionCode
		$request .= '10,"' . FEDEX_ACOUNT_NUMBER . '"'; 											// Sender fedex account number
		$request .= '498,"' . FEDEX_METER_NUMBER . '"'; 											// Meter number
		$request .= '8,"' . STORE_STATE . '"'; 														// Sender state code
		$request .= '9,"' . STORE_ZIPCODE . '"'; 													// Origin postal code
		$request .= '117,"' . STORE_COUNTRY . '"'; 													// Origin country
		$request .= '17,"' . prepareTag($this->shipping_information['zipcode']) . '"'; 				// Recipient zip code
		$request .= '50,"' . STORE_COUNTRY . '"'; 													// Recipient country
		$request .= '75,"LBS"'; 																	// Weight units
		$request .= '1401,"' . prepareTag($this->shipping_information['weight']) . '.0"'; 			// Total weight
		$request .= '440,"' . (($this->shipping_information['residential']) ? 'Y' : 'N') . '"'; 	// Residential address
		$request .= '1273,"1"'; 																	// Package type
		$request .= '1333,"1"';																		// Drop Off Type
		$request .= '1529,"0"'; 																	// Also return list rates
		$request .= '99,""'; 																		// End of record

		//send request to fedex
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, 'https://' . $fedex_quote_server);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Referer: " . SITE_NAME, "Host: " . $fedex_quote_server, "Accept: image/gif,image/jpeg,image/pjpeg,text/plain,text/html,*/*", "Pragma:", "Content-Type:image/gif"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        $fedex_reply = curl_exec($ch);
        curl_close ($ch);
		
		//parse fedex result
		$fedexData = array(); if(have($fedex_reply)){ $current = 0; $length = strlen($fedex_reply); while($current < $length){ $endpos = strpos($fedex_reply, ',', $current); if($endpos === FALSE){ break; } $index = substr($fedex_reply, $current, $endpos - $current); $current = $endpos + 2; $endpos = strpos($fedex_reply, '"', $current); $fedexData[$index] = substr($fedex_reply, $current, $endpos - $current); $current = $endpos + 1; } }
		
		//organize fedex result
		$rates = null; $i = 1; $allowed_rates = explode(',', FEDEX_SHIPPING_TYPES);	while(isset($fedexData['1274-' . $i])){ if(in_array($fedexData['1274-' . $i], $allowed_rates)){ if(isset($fedexData['3058-' . $i])){ $rates[$fedexData['1274-' . $i] . $fedexData['3058-' . $i]] = isset($fedexData['1528-' . $i]) ? $fedexData['1528-' . $i] : $fedexData['1419-' . $i]; }else{ $rates[$fedexData['1274-' . $i]] = isset($fedexData['1528-' . $i]) ? $fedexData['1528-' . $i] : $fedexData['1419-' . $i]; } } $i++; }
		
		//build quote array
		if(have($rates)){ 
			asort($rates); 
			foreach($rates as $rate_key => $rate){ 
				$this->shipping_quote[$this->shipping_type][substr($rate_key, 0, 2)]['name'] = $shipping_options[substr($rate_key, 0, 2)]['name']; 
				$this->shipping_quote[$this->shipping_type][substr($rate_key, 0, 2)]['price'] = $rate + $this->shipping_handling_fee; 
				$this->shipping_quote[$this->shipping_type][substr($rate_key, 0, 2)]['type'] = substr($rate_key, 0, 2); 
				$this->shipping_quote[$this->shipping_type][substr($rate_key, 0, 2)]['transit'] = (have($shipping_options[substr($rate_key, 0, 2)]['transit']) and strlen($rate_key) == 2) ? $shipping_options[substr($rate_key, 0, 2)]['transit'] : substr($rate_key, 2); 
				switch(substr($rate_key, 0, 2)){ 
					case '90': 
						$this->shipping_quote[$this->shipping_type][substr($rate_key, 0, 2)]['delivery_days'] = array(1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => false, 7 => false); 						break; 
					case '92': 
						$this->shipping_quote[$this->shipping_type][substr($rate_key, 0, 2)]['delivery_days'] = array(1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => false, 7 => false); 
					break; 
					default: 
						$this->shipping_quote[$this->shipping_type][substr($rate_key, 0, 2)]['delivery_days'] = array(1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => false, 7 => false); 
					break; 
				} 
			} 
		}
				
		//return
		if(have($this->shipping_quote)){ return true; }else{ return false; }
	
	}
	
	/***************************************************************************/
	/****************** BUILD SHIPPING QUOTE ***********************************/
	/***************************************************************************/	
	private function buildUSPSRateQuote(){

		//set service
		$service = 'FIRST CLASS';

		//where are we getting the usps quote
		//$usps_quote_server = 'http://production.shippingapis.com/ShippingAPI.dll';
		$usps_quote_server = 'http://testing.shippingapis.com/ShippingAPITest.dll';
		
		//build the request
		if($service == 'FIRST CLASS'){ $fctype = '<FirstClassMailType>PARCEL</FirstClassMailType>'; }
		$request = 'API=RateV3&XML=<RateV3Request USERID="' . USPS_USERNAME . '"><Package ID="1ST"><Service>' . $service . '</Service>' . $fctype . '<ZipOrigination>' . STORE_ZIPCODE . '</ZipOrigination><ZipDestination>' . prepareTag($this->shipping_information['zipcode']) . '</ZipDestination><Pounds>' . prepareTag($this->shipping_information['weight']) . '</Pounds><Ounces>0</Ounces><Size>REGULAR</Size><Machinable>TRUE</Machinable></Package></RateV3Request>'; 
		
		//send request to usps server
		$ch = curl_init();  
		curl_setopt($ch, CURLOPT_URL, $usps_quote_server);  
		curl_setopt($ch, CURLOPT_HEADER, 1);  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
		curl_setopt($ch, CURLOPT_POST, 1);  
 		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		$usps_reply = curl_exec($ch);
		curl_close($ch);
		
		var_dump($usps_reply);
		
		//parse sups reply
		$data = strstr($usps_reply, '<?'); $xml_parser = xml_parser_create(); xml_parse_into_struct($xml_parser, $data, $vals, $index); xml_parser_free($xml_parser);  
		
		//parse response array
		$params = array(); $level = array(); foreach($vals as $xml_elem){ if($xml_elem['type'] == 'open'){ if(array_key_exists('attributes', $xml_elem)){ list($level[$xml_elem['level']],$extra) = array_values($xml_elem['attributes']); }else{ $level[$xml_elem['level']] = $xml_elem['tag']; } } if($xml_elem['type'] == 'complete'){ $start_level = 1; $php_stmt = '$params'; while($start_level < $xml_elem['level']){ $php_stmt .= '[$level['.$start_level.']]'; $start_level++; } $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];'; eval($php_stmt); } }  

		print_r($params);

		//$i=1; while($i < 15){ if($params['RateV3RESPONSE']['1ST']["$i"]['RATE']==''){ $i++; }else{ return $params['RateV3RESPONSE']['1ST']["$i"]['RATE']; break; } }

	}

}

?>