<?php
class Payment { 
	
	function __construct($db){
		
		//The database connection
		$this->db = $db;
		
		//Payment Method
		$this->payment_method = null;
		
		//Payment info
		$this->payment_info = array();
		$this->payment_info['amount'] = null;
		$this->payment_info['cc_number'] = null;
		$this->payment_info['cc_type'] = null;
		$this->payment_info['cc_month'] = null; 
		$this->payment_info['cc_year'] = null;
		$this->payment_info['cc_csv'] = null;
		$this->payment_info['cc_name'] = null;
		$this->payment_info['cc_address'] = null;
		$this->payment_info['cc_city'] = null;
		$this->payment_info['cc_state'] = null;
		$this->payment_info['cc_zip'] = null;      
		
		//Should we remember this card
		$this->remember_payment_details = true;
		
		//Payment Response
		$this->payment_response = array();
		$this->payment_response['status'] = null;
		$this->payment_response['message'] = null;
		
		//Save the transaction id
		$this->transaction_id = null;
		
	}

	/**************************************************************/
	/************** SET PAYMENT METHOD ****************************/
	/**************************************************************/
	public function setPaymentMethod($method = null){
		if(empty($method)){ return false; }
		$this->payment_method = $method;
		return true;
	}
	
	/**************************************************************/
	/************** PROCESS PAYMENT *******************************/
	/**************************************************************/
	public function processPaymentRequest($amount, $cc_number, $cc_year, $cc_month, $cc_csv, $cc_name = null, $cc_address = null, $cc_city = null, $cc_state = null, $cc_zip = null, $do_not_remember = null){
		
		//check for problems
		if(!have($amount)){ return false; }else{ $this->payment_info['amount'] = $amount; }
		if(!have($cc_number) or strlen(preg_replace('/[^0-9]+/', '', $cc_number)) < 14){ return $this->payment_response; }else{ $this->payment_info['cc_number'] = preg_replace('/[^0-9]+/', '', $cc_number); }
		if(!have($cc_month) or (strlen(preg_replace('/[^0-9]+/', '', $cc_month)) != 2 and strlen(preg_replace('/[^0-9]+/', '', $cc_month)) != 1)){ return $this->payment_response; }else{ $this->payment_info['cc_month'] = preg_replace('/[^0-9]+/', '', $cc_month); }
		if(!have($cc_year) or strlen(preg_replace('/[^0-9]+/', '', $cc_year)) != 4){ return $this->payment_response; }else{ $this->payment_info['cc_year'] = preg_replace('/[^0-9]+/', '', $cc_year); }
		if(have($cc_csv) and strlen(preg_replace('/[^0-9]+/', '', $cc_csv)) > 2){ $this->payment_info['cc_csv'] = preg_replace('/[^0-9]+/', '', $cc_csv); }
		if(have($cc_name)){ $this->payment_info['cc_name'] = $cc_name; }
		if(have($cc_address)){ $this->payment_info['cc_address'] = $cc_address; }
		if(have($cc_city)){ $this->payment_info['cc_city'] = $cc_city; }
		if(have($cc_state)){ $this->payment_info['cc_state'] = $cc_state; }
		if(have($cc_zip)){ $this->payment_info['cc_zip'] = $cc_zip; }
		if(have($do_not_remember) and $do_not_remember == 'yes'){ $this->remember_payment_details = false; }
		
		//set card type
		$this->payment_info['cc_type'] = getCardType($this->payment_info['cc_number']);
		
		//pick the correct method
		return self::processPayment();
		
	}
	
	/**************************************************************/
	/************** PROCESS SAVED CARD PAYMENT ********************/
	/**************************************************************/	
	public function processSavedCardPayment($amount = null, $card_key = null, $payment_method = null, $cvv_code = null){
		
		//set the payment method
		self::setPaymentMethod($payment_method);

		//set the amount
		$this->payment_info['amount'] = (have($amount) and $amount > 0) ? $amount : null;

		//get the data from the save card
		$get_saved_card_array = getCardDetails($card_key, $payment_method, $cvv_code);
		
		//set credit card information
		if(have($get_saved_card_array)){ foreach($get_saved_card_array as $card_key => $card_value){ $this->payment_info[$card_key] = $card_value; } }
		
		//obviously we should not save this info - it is already saved
		$this->remember_payment_details = false;
		
		//attempt payment
		return self::processPayment();
		
	}

	/**************************************************************/
	/************** PROCESS PAYMENT *******************************/
	/**************************************************************/
	public function processPaymentRequestFlexible($array = array()){
		
		//set the array or return ..false
		if(!have($array)){ return $this->payment_response; }
		$this->payment_info = $array;
		
		//for security
		$this->remember_payment_details = false;
		
		//pick the correct method
		return self::processPayment();
				
	}
	
	/**************************************************************/
	/******************* PROCESS PAYMENT **************************/
	/**************************************************************/
	protected function processPayment(){
		
		//pick the correct method
		switch(strtolower($this->payment_method)){
			
			//do authorize.net payment
			case 'authorize.net': 
				return self::processAuthorize();
			break;
			
			//do transnational payment
			case 'transnational':
				return self::processTransnational();
			break;
			
			//premier payment services
			case 'ppsgateway':
				return self::ppsGateway();
			break;
			
			//paypal direct (credit card)
			case 'paypaldirect':
				return self::paypalDirect();
			break;
			
			//paypal express (express redirect loop)
			case 'paypalexpress':
				return self::paypalExpress();
			break;
			
			//pay with amazon
			case 'amazonpayments':
				return self::AmazonPayments();
			break;
			
			//show default error
			default:
				$this->payment_response['status'] = 0;
				$this->payment_response['message'] = 'Payment method not supported';
				return $this->payment_response;
			break;
			
		}
			
	}
	
	/**************************************************************/
	/************** LOG PAYMENT REQUEST ***************************/
	/**************************************************************/	
	protected function logPaymentRequest($card_ref = null, $the_amount = null, $the_response = null, $the_reference = null){
		if(!have($card_ref) or !have($the_amount)){ return false; }
		$user_id = function_exists('getUserId') ? getUserId() : null;
		@mysql_query(" INSERT INTO dzpro_payments ( dzpro_user_id, dzpro_payment_method, dzpro_payment_card_ref, dzpro_payment_amount, dzpro_payment_response, dzpro_payment_reference, dzpro_payment_date_added ) VALUES ( '" . mysql_real_escape_string($user_id) . "', '" . mysql_real_escape_string($this->payment_method) . "', '" . mysql_real_escape_string($card_ref) . "', '" . mysql_real_escape_string(str_replace(',', null, $the_amount)) . "', '" . mysql_real_escape_string($the_response) . "', '" . mysql_real_escape_string($the_reference) . "', NOW() ) "); if(mysql_insert_id() > 0){ return true; }
		return false;
	}

	/**************************************************************/
	/************** PAYMENT METHOD - AMAZON PAYMENTS **************/
	/**************************************************************/	
	public function AmazonPayments(){
	
		//get and extract return url
		$return_url = $this->payment_info['return_path']; unset($this->payment_info['return_path']);
		
		//confirm amazon payment
		$response = confirmAmazonPayment($this->payment_info, $return_url);
	
		//handle response
		if(is_bool($response) and $response === true){

			//try to settle payment right away
			if(settleAmazonPayment($this->payment_info['transactionId'])){

				//set successful payment response
				$this->payment_response['status'] = 'true';
				
				//message
				$this->payment_response['message'] = null;
				
				//log payment
				$this->transaction_id = $this->payment_info['transactionId'];
				self::logPaymentRequest('amazon-' . $this->payment_info['buyerEmail'], number_format(preg_replace('/[^0-9^\.]+/', '', $this->payment_info['transactionAmount']), 2), serialize($this->payment_info), $this->payment_info['transactionId']);
				
				//save to intelligence
				if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'success'); }
			
			}else{
			
				//set successful payment response
				$this->payment_response['status'] = 'false';
						
				//messgae
				$this->payment_response['message'] = 'Your Amazon payment was not settled.';
				
				//save to intelligence
				if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'failed'); }
							
			}
			
		}elseif(is_bool($response) and $response === false){

			//set successful payment response
			$this->payment_response['status'] = 'false';
					
			//messgae
			$this->payment_response['message'] = 'Your Amazon payment was not confirmed.';
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'failed'); }
		
		}else{

			//a problem occured
			$this->payment_response['status'] = 'error';
			
			//an error ocurred
			$this->payment_response['message'] = 'Your Amazon payment could not be verified.';
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){  addToIntelligenceStack('payment attempt', 'error'); }
					
		}

		//respond
		return $this->payment_response;	
			
	}

	/**************************************************************/
	/************** PAYMENT METHOD - PAYPAL EXPRESS ***************/
	/**************************************************************/
	public function paypalExpress(){
		
		//get the result
		$response_array = paypalConfirmPayment($this->payment_info['amount'], $this->payment_info['token'], $this->payment_info['PayerID'], "USD", "Sale");

		//handle response
		if(isset($response_array['ACK']) and ($response_array['ACK'] == 'Success' OR $response_array['ACK'] == 'SuccessWithWarning')){

			//set successful payment response
			$this->payment_response['status'] = 'true';
			
			//message
			$this->payment_response['message'] = null;
			
			//log payment
			$this->transaction_id = $response_array['PAYMENTINFO_0_TRANSACTIONID'];
			self::logPaymentRequest('paypal-' . $this->payment_info['PayerID'], number_format($this->payment_info['amount'], 2), serialize($response_array), $response_array['PAYMENTINFO_0_TRANSACTIONID']);
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'success'); }
					
		}elseif(isset($response_array['ACK']) and $response_array['ACK'] == 'Failure'){
		
			//set successful payment response
			$this->payment_response['status'] = 'false';
					
			//messgae
			$this->payment_response['message'] = isset($response_array['L_LONGMESSAGE0']) ? urldecode($response_array['L_LONGMESSAGE0']) : null;
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'failed'); }
			
		}else{
			
			//a problem occured
			$this->payment_response['status'] = 'error';
			
			//an error ocurred
			$this->payment_response['message'] = isset($response_array['L_LONGMESSAGE0']) ? urldecode($response_array['L_LONGMESSAGE0']) : null;
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){  addToIntelligenceStack('payment attempt', 'error'); }
			
		}
	
		//respond
		return $this->payment_response;	   
		
	}

	/**************************************************************/
	/************** PAYMENT METHOD - PAYPAL DIRECT ****************/
	/**************************************************************/	
	public function payPalDirect(){
	
     	//calling to this url
     	$post_url = 'https://api-3t.paypal.com/nvp';
     	
     	//this static info
		$static_submission_array = array(
			"USER" 				=> "sales_api1.thecheesemart.com",
			"PWD" 				=> "WDQETV32K4VVPW2M",
			"VERSION" 			=> "3.2",
			"SIGNATURE" 		=> "AFcWxV21C7fd0v3bYYYRCpSSRl31ANXbEFOLTcB.2v.AwP1VY6OcqSoc",
			"METHOD" 			=> "DoDirectPayment",
			"IPADDRESS"			=> $_SERVER['REMOTE_ADDR'],
			"PAYMENTACTION" 	=> "Sale",
			"CURRENCYCODE" 		=> "USD",
			"BUTTONSOURCE" 		=> "Ukora_CMS"
		);
	
		//card details array
		$card_details_array = array(
			"ACCT"				=> $this->payment_info['cc_number'],
			"CREDITCARDTYPE"	=> getCardType($this->payment_info['cc_number']),
			"EXPDATE"			=> leading_zero($this->payment_info['cc_month'], 2) . $this->payment_info['cc_year'],
			"CVV2"				=> $this->payment_info['cc_csv'],
			"FIRSTNAME"			=> substr($this->payment_info['cc_name'], 0, strpos($this->payment_info['cc_name'], ' ')),
			"LASTNAME"			=> substr($this->payment_info['cc_name'], strrpos($this->payment_info['cc_name'], ' ')),
			"STREET"			=> $this->payment_info['cc_address'],
			"CITY"				=> $this->payment_info['cc_city'],
			"STATE"				=> $this->payment_info['cc_state'],
			"ZIP"				=> $this->payment_info['cc_zip'],
			"COUNTRYCODE"		=> "US"
		);

		//fix name
		if(strpos($this->payment_info['cc_name'], '/') > 0){ $card_details_array['FIRSTNAME'] = substr($this->payment_info['cc_name'], strpos($this->payment_info['cc_name'], '/') + 1); $card_details_array['LASTNAME'] = substr($this->payment_info['cc_name'], 0, strpos($this->payment_info['cc_name'], '/')); }
		
		//if there are exact matches .. for the keys .. than we processing an existing card .. let's set that here
		foreach($card_details_array as $card_details_key => $card_details_value){ if(isset($this->payment_info[$card_details_key])){ $card_details_array[$card_details_key] = $this->payment_info[$card_details_key]; } }
		
		//saving user data
		if(function_exists('saveUserData')){
			saveUserData('name', $this->payment_info['cc_name']);
			saveUserData('first name', substr($this->payment_info['cc_name'], 0, strpos($this->payment_info['cc_name'], ' ')));
			saveUserData('last name', substr($this->payment_info['cc_name'], strrpos($this->payment_info['cc_name'], ' ')));
			saveUserData('address', $this->payment_info['cc_address']);
			saveUserData('city', $this->payment_info['cc_city']);
			saveUserData('state', $this->payment_info['cc_state']);
			saveUserData('zip', $this->payment_info['cc_zip']);
		}
		
		//order details array
		$order_details_array = array(
			"AMT" => number_format($this->payment_info['amount'], 2)	
		);

		//set the post values
		$post_values = array_merge($static_submission_array, $card_details_array, $order_details_array);
		
		//build the post string
		$post_string = ""; foreach($post_values as $key => $value){ $post_string .= "$key=" . urlencode($value) . "&"; } $post_string = rtrim($post_string, "& ");

        $request = curl_init($post_url);
		//curl_setopt($request, CURLOPT_URL, $post_url);        
        curl_setopt($request, CURLOPT_PORT, 443);
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($request, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($request, CURLOPT_POST, 1);
        curl_setopt($request, CURLOPT_POSTFIELDS, $post_string);

         //if an error occurred
		if(!($data = curl_exec($request))){
			
			//a problem occured
			$this->payment_response['status'] = 'error';
			
			//show error message
			$this->payment_response['message'] = 'We could not process your payment at this time.';
		
			return $this->payment_response;
		}
		
		//create array
		$response_array = array(); $data = explode("&", $data); for($i = 0;$i < count($data);$i++) { $rdata = explode("=", $data[$i]); $response_array[$rdata[0]] = $rdata[1]; }
		
		//close curl
		curl_close($request); unset($request);

		//handle response
		if(isset($response_array['ACK']) and ($response_array['ACK'] == 'Success' OR $response_array['ACK'] == 'SuccessWithWarning')){

			//set successful payment response
			$this->payment_response['status'] = 'true';
			
			//message
			$this->payment_response['message'] = null;
			
			//save card for user
			if(!isset($this->payment_info['cc_number']) or empty($this->payment_info['cc_number'])){ $this->payment_info['cc_number'] = $this->payment_info['ACCT']; }
			if(function_exists('saveCardData') and $this->remember_payment_details){
				saveCardData(
					'xxxx-xxxx-xxxx-' . substr($this->payment_info['cc_number'], -4) . ' (' . getCardType($this->payment_info['cc_number']) . ')', 
					$this->payment_method, 
					$card_details_array,
					$this->payment_info['cc_csv']
				);
			}
			
			//log payment
			$this->transaction_id = $response_array['TRANSACTIONID'];
			self::logPaymentRequest('xxxx-xxxx-xxxx-' . substr($card_details_array['ACCT'], -4), number_format($this->payment_info['amount'], 2), serialize($response_array), $response_array['TRANSACTIONID']);
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'success'); }
					
		}elseif(isset($response_array['ACK']) and $response_array['ACK'] == 'Failure'){
		
			//set successful payment response
			$this->payment_response['status'] = 'false';
					
			//messgae
			$this->payment_response['message'] = isset($response_array['L_LONGMESSAGE0']) ? urldecode($response_array['L_LONGMESSAGE0']) : null;
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'failed'); }
			
		}else{
			
			//a problem occured
			$this->payment_response['status'] = 'error';
			
			//an error ocurred
			$this->payment_response['message'] = isset($response_array['L_LONGMESSAGE0']) ? urldecode($response_array['L_LONGMESSAGE0']) : null;
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){  addToIntelligenceStack('payment attempt', 'error'); }
			
		}
	
		//respond
		return $this->payment_response;	   
	
	}

	/**************************************************************/
	/************** PAYMENT METHOD - TRANSNATIONAL ****************/
	/**************************************************************/
	public function ppsGateway(){

		//calling to this url
		$post_url = "https://secure.ppsgateway.com/api/transact.php";

		//this static info
		$static_submission_array = array(
			"username"			=> "",
			"password"			=> "",
			"type"				=> "sale"		
		);

		//card details array
		$card_details_array = array(
			"ccnumber"			=> $this->payment_info['cc_number'],
			"ccexp"				=> leading_zero($this->payment_info['cc_month'], 2) . substr($this->payment_info['cc_year'], -2),
			"cvv"				=> $this->payment_info['cc_csv'],
			"firstname"			=> substr($this->payment_info['cc_name'], 0, strpos($this->payment_info['cc_name'], ' ')),
			"lastname"			=> substr($this->payment_info['cc_name'], strrpos($this->payment_info['cc_name'], ' ')),
			"address1"			=> $this->payment_info['cc_address'],
			"city"				=> $this->payment_info['cc_city'],
			"state"				=> $this->payment_info['cc_state'],
			"zip"				=> $this->payment_info['cc_zip']
		);

		//fix name
		if(strpos($this->payment_info['cc_name'], '/') > 0){ $card_details_array['firstname'] = substr($this->payment_info['cc_name'], strpos($this->payment_info['cc_name'], '/') + 1); $card_details_array['lastname'] = substr($this->payment_info['cc_name'], 0, strpos($this->payment_info['cc_name'], '/')); }
		
		//if there are exact matches .. for the keys .. than we processing an existing card .. let's set that here
		foreach($card_details_array as $card_details_key => $card_details_value){ if(isset($this->payment_info[$card_details_key])){ $card_details_array[$card_details_key] = $this->payment_info[$card_details_key]; } }
		
		//saving user data
		if(function_exists('saveUserData')){
			saveUserData('name', $this->payment_info['cc_name']);
			saveUserData('first name', substr($this->payment_info['cc_name'], 0, strpos($this->payment_info['cc_name'], ' ')));
			saveUserData('last name', substr($this->payment_info['cc_name'], strrpos($this->payment_info['cc_name'], ' ')));
			saveUserData('address', $this->payment_info['cc_address']);
			saveUserData('city', $this->payment_info['cc_city']);
			saveUserData('state', $this->payment_info['cc_state']);
			saveUserData('zip', $this->payment_info['cc_zip']);
		}
		
		//order details array
		$order_details_array = array(
			"amount"			=> number_format($this->payment_info['amount'], 2),
			"orderdescription"	=> "Online transaction"		
		);

		//set the post values
		$post_values = array_merge($static_submission_array, $card_details_array, $order_details_array);
		
		//build the post string
		$post_string = ""; foreach($post_values as $key => $value){ $post_string .= "$key=" . urlencode($value) . "&"; } $post_string = rtrim($post_string, "& ");

		//send the payment information to transnational
		$request = curl_init();
		curl_setopt($request, CURLOPT_URL, $post_url);
		curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($request, CURLOPT_TIMEOUT, 15);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($request, CURLOPT_HEADER, 0);
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
 		curl_setopt($request, CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($request, CURLOPT_POST, 1);
		
		//if an error occurred
		if(!($data = curl_exec($request))){
			
			//a problem occured
			$this->payment_response['status'] = 'error';
			
			//show error message
			$this->payment_response['message'] = 'We could not process your payment at this time.';
		
			return $this->payment_response;
		}
		
		//close curl
		curl_close($request); unset($request);
		
		//create array
		$response_array = array(); $data = explode("&", $data); for($i = 0;$i < count($data);$i++) { $rdata = explode("=", $data[$i]); $response_array[$rdata[0]] = $rdata[1]; }
		
		if(isset($response_array['response']) and $response_array['response'] == 1){

			//set successful payment response
			$this->payment_response['status'] = 'true';
			
			//message
			$this->payment_response['message'] = null;
			
			//save card for user
			if(function_exists('saveCardData') and $this->remember_payment_details){
				saveCardData(
					'xxxx-xxxx-xxxx-' . substr($this->payment_info['cc_number'], -4) . ' (' . getCardType($this->payment_info['cc_number']) . ')', 
					$this->payment_method, 
					$card_details_array,
					$this->payment_info['cc_csv']
				);
			}
			
			//log payment
			$this->transaction_id = $response_array['transactionid'];
			self::logPaymentRequest('xxxx-xxxx-xxxx-' . substr($card_details_array['ccnumber'], -4), number_format($this->payment_info['amount'], 2), serialize($response_array), $response_array['transactionid']);
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'success'); }
					
		}elseif(isset($response_array['response']) and $response_array['response'] == 2){
		
			//set successful payment response
			$this->payment_response['status'] = 'false';
			
			//messgae
			$this->payment_response['message'] = isset($response_array['responsetext']) ? $response_array['responsetext'] : null;
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'failed'); }
			
		}else{
			
			//a problem occured
			$this->payment_response['status'] = 'error';
			
			//an error ocurred
			$this->payment_response['message'] = isset($response_array['responsetext']) ? $response_array['responsetext'] : null;
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){  addToIntelligenceStack('payment attempt', 'error'); }
			
		}
	
		//respond
		return $this->payment_response;	
			
	}

	/**************************************************************/
	/************** PAYMENT METHOD - TRANSNATIONAL ****************/
	/**************************************************************/
	public function processTransnational(){

		//calling to this url
		$post_url = "https://secure.tnbcigateway.com/api/transact.php";

		//this static info
		$static_submission_array = array(
			"username"			=> "",
			"password"			=> "",
			"type"				=> "sale"		
		);

		//card details array
		$card_details_array = array(
			"ccnumber"			=> $this->payment_info['cc_number'],
			"ccexp"				=> leading_zero($this->payment_info['cc_month'], 2) . substr($this->payment_info['cc_year'], -2),
			"cvv"				=> $this->payment_info['cc_csv'],
			"firstname"			=> substr($this->payment_info['cc_name'], 0, strpos($this->payment_info['cc_name'], ' ')),
			"lastname"			=> substr($this->payment_info['cc_name'], strrpos($this->payment_info['cc_name'], ' ')),
			"address1"			=> $this->payment_info['cc_address'],
			"city"				=> $this->payment_info['cc_city'],
			"state"				=> $this->payment_info['cc_state'],
			"zip"				=> $this->payment_info['cc_zip']
		);
		
		//fix name
		if(strpos($this->payment_info['cc_name'], '/') > 0){ $card_details_array['firstname'] = substr($this->payment_info['cc_name'], strpos($this->payment_info['cc_name'], '/') + 1); $card_details_array['lastname'] = substr($this->payment_info['cc_name'], 0, strpos($this->payment_info['cc_name'], '/')); }
		
		//if there are exact matches .. for the keys .. than we processing an existing card .. let's set that here
		foreach($card_details_array as $card_details_key => $card_details_value){ if(isset($this->payment_info[$card_details_key])){ $card_details_array[$card_details_key] = $this->payment_info[$card_details_key]; } }
		
		//saving user data
		if(function_exists('saveUserData')){
			saveUserData('name', $this->payment_info['cc_name']);
			saveUserData('first name', substr($this->payment_info['cc_name'], 0, strpos($this->payment_info['cc_name'], ' ')));
			saveUserData('last name', substr($this->payment_info['cc_name'], strrpos($this->payment_info['cc_name'], ' ')));
			saveUserData('address', $this->payment_info['cc_address']);
			saveUserData('city', $this->payment_info['cc_city']);
			saveUserData('state', $this->payment_info['cc_state']);
			saveUserData('zip', $this->payment_info['cc_zip']);
		}
		
		//order details array
		$order_details_array = array(
			"amount"			=> number_format($this->payment_info['amount'], 2),
			"orderdescription"	=> "Online transaction"		
		);

		//set the post values
		$post_values = array_merge($static_submission_array, $card_details_array, $order_details_array);
		
		//build the post string
		$post_string = ""; foreach($post_values as $key => $value){ $post_string .= "$key=" . urlencode( $value ) . "&"; } $post_string = rtrim($post_string, "& ");
		
		//send the payment information to transnational
		$request = curl_init();
		curl_setopt($request, CURLOPT_URL, $post_url);
		curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($request, CURLOPT_TIMEOUT, 15);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($request, CURLOPT_HEADER, 0);
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($request, CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($request, CURLOPT_POST, 1);
 
		if(!($data = curl_exec($request))){
			
			//a problem occured
			$this->payment_response['status'] = 'error';
			
			//show error message
			$this->payment_response['message'] = 'We could not process your payment at this time.';
		
			return $this->payment_response;
		}
		
		//close curl
		curl_close($request); unset($request);
		
		//create array
		$response_array = array(); $data = explode("&", $data); for($i = 0;$i < count($data);$i++) { $rdata = explode("=", $data[$i]); $response_array[$rdata[0]] = $rdata[1]; }
		
		if(isset($response_array['response']) and $response_array['response'] == 1){

			//set successful payment response
			$this->payment_response['status'] = 'true';
			
			//message
			$this->payment_response['message'] = null;
			
			//save card for user
			if(function_exists('saveCardData') and $this->remember_payment_details){
				saveCardData(
					'xxxx-xxxx-xxxx-' . substr($this->payment_info['cc_number'], -4) . ' (' . getCardType($this->payment_info['cc_number']) . ')', 
					$this->payment_method, 
					$card_details_array,
					$this->payment_info['cc_csv']
				);
			}
			
			//log payment
			$this->transaction_id = $response_array['transactionid'];
			self::logPaymentRequest('xxxx-xxxx-xxxx-' . substr($card_details_array['ccnumber'], -4), number_format($this->payment_info['amount'], 2), serialize($response_array), $response_array['transactionid']);
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'success'); }
					
		}elseif(isset($response_array['response']) and $response_array['response'] == 2){
		
			//set payment response
			$this->payment_response['status'] = 'false';
			
			//messgae
			$this->payment_response['message'] = isset($response_array['responsetext']) ? $response_array['responsetext'] : null;
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'failed'); }
			
		}else{
			
			//a problem occured
			$this->payment_response['status'] = 'error';
			
			//an error ocurred
			$this->payment_response['message'] = isset($response_array['responsetext']) ? $response_array['responsetext'] : null;
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){  addToIntelligenceStack('payment attempt', 'error'); }
			
		}
	
		//respond
		return $this->payment_response;	
					
	}
	
	/**************************************************************/
	/************** PAYMENT METHOD - AUTHORIZE.NET ****************/
	/**************************************************************/
	public function processAuthorize(){
		
		//calling to this url
		//$post_url = "https://test.authorize.net/gateway/transact.dll";
		$post_url = "https://secure.authorize.net/gateway/transact.dll";

		//this static info
		$static_submission_array = array(
			"x_login"			=> "5J3cqE7p",
			"x_tran_key"		=> "53kQ743c2np7Q3Ty",
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",
			"x_type"			=> "AUTH_CAPTURE",
			"x_method"			=> "CC",		
		);
		
		//card details array
		$card_details_array = array(
			"x_card_num"		=> $this->payment_info['cc_number'],
			"x_exp_date"		=> leading_zero($this->payment_info['cc_month'], 2) . substr($this->payment_info['cc_year'], -2),
			"x_first_name"		=> substr($this->payment_info['cc_name'], 0, strpos($this->payment_info['cc_name'], ' ')),
			"x_last_name"		=> substr($this->payment_info['cc_name'], strrpos($this->payment_info['cc_name'], ' ')),
			"x_address"			=> $this->payment_info['cc_address'],
			"x_state"			=> $this->payment_info['cc_state'],
			"x_zip"				=> $this->payment_info['cc_zip']
		);
		
		//if there are exact matches .. for the keys .. than we processing an existing card .. let's set that here
		foreach($card_details_array as $card_details_key => $card_details_value){ if(isset($this->payment_info[$card_details_key])){ $card_details_array[$card_details_key] = $this->payment_info[$card_details_key]; } }
		
		//saving user data
		if(function_exists('saveUserData')){
			saveUserData('name', $this->payment_info['cc_name']);
			saveUserData('first name', substr($this->payment_info['cc_name'], 0, strpos($this->payment_info['cc_name'], ' ')));
			saveUserData('last name', substr($this->payment_info['cc_name'], strrpos($this->payment_info['cc_name'], ' ')));
			saveUserData('address', $this->payment_info['cc_address']);
			saveUserData('city', $this->payment_info['cc_city']);
			saveUserData('state', $this->payment_info['cc_state']);
			saveUserData('zip', $this->payment_info['cc_zip']);
		}
		
		//order details array
		$order_details_array = array(
			"x_amount"			=> number_format($this->payment_info['amount'], 2),
			"x_description"		=> "Online transaction"	
		);
		
		//set the post values
		$post_values = array_merge($static_submission_array, $card_details_array, $order_details_array);
		
		//build the post string
		$post_string = ""; foreach($post_values as $key => $value){ $post_string .= "$key=" . urlencode( $value ) . "&"; } $post_string = rtrim($post_string, "& ");
	
		//send the info to authorize
		$request = curl_init($post_url);
		curl_setopt($request, CURLOPT_HEADER, 0);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($request, CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);
		$post_response = curl_exec($request);
		curl_close($request); unset($request);
	
		//get the response
		$response_array = explode('|', $post_response);
		
		//payment success	
		if(have($response_array[0]) and $response_array[0] == 1){
			
			//set successful payment response
			$this->payment_response['status'] = 'true';
			
			//save card for user
			if(function_exists('saveCardData') and $this->remember_payment_details){
				saveCardData(
					'xxxx-xxxx-xxxx-' . substr($this->payment_info['cc_number'], -4) . ' (' . getCardType($this->payment_info['cc_number']) . ')', 
					$this->payment_method, 
					$card_details_array,
					$this->payment_info['cc_csv']
				);
			}
			
			//log payment
			$this->transaction_id = $response_array[37];
			self::logPaymentRequest('xxxx-xxxx-xxxx-' . substr($card_details_array['x_card_num'], -4), number_format($this->payment_info['amount'], 2), $post_response, $response_array[37]);
		
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'success'); }
		
		}elseif(have($response_array[0]) and $response_array[0] == 4){
		
			//set payment response
			$this->payment_response['status'] = 'error';
		
			//an error ocurred
			$this->payment_response['message'] = isset($response_array[3]) ? $response_array[3] : null;
		
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'error'); }
			
		}else{
			
			//set payment response
			$this->payment_response['status'] = 'false';
		
			//an error ocurred
			$this->payment_response['message'] = isset($response_array['responsetext']) ? $response_array['responsetext'] : null;
			
			//save to intelligence
			if(function_exists('addToIntelligenceStack')){ addToIntelligenceStack('payment attempt', 'failed'); }
		
		}
		
		//payment ref
		$this->payment_response['transaction_ref'] = (have($response_array[6])) ? $response_array[6] : null;
		
		//payment message
		$this->payment_response['message'] = (have($response_array[3])) ? $response_array[3] : null; 
			
		//return response
		return $this->payment_response;
	
	}
	
}
?>