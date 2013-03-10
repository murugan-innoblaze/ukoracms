<?php

/**************************************************************/
/************** CLASS TO CREATE SIGNATURE *********************/
/**************************************************************/
class SignatureUtils { 
    public static function signParameters(array $parameters, $key, $httpMethod, $host, $requestURI, $algorithm){
        $stringToSign = null; $stringToSign = self::_calculateStringToSignV2($parameters, $httpMethod, $host, $requestURI);
        return self::_sign($stringToSign, $key, $algorithm);
    }
    private static function _calculateStringToSignV2(array $parameters, $httpMethod, $hostHeader, $requestURI){
        if($httpMethod == null){ handleError(1, "HttpMethod cannot be null"); }
        $data = $httpMethod; $data .= "\n"; if($hostHeader == null){ $hostHeader = ""; } $data .= $hostHeader; $data .= "\n"; if(!isset($requestURI)){ $requestURI = "/"; }
		$uriencoded = implode("/", array_map(array("SignatureUtils", "_urlencode"), explode("/", $requestURI))); $data .= $uriencoded; $data .= "\n";
        uksort($parameters, 'strcmp'); $data .= self::_getParametersAsString($parameters);
        return $data;
    }
    private static function _urlencode($value) {
		return str_replace('%7E', '~', rawurlencode($value));
    }
    public static function _getParametersAsString(array $parameters) {
        $queryParameters = array(); foreach($parameters as $key => $value){ $queryParameters[] = $key . '=' . self::_urlencode($value); }
        return implode('&', $queryParameters);
    }
    private static function _sign($data, $key, $algorithm) {
        if($algorithm === 'HmacSHA1'){ $hash = 'sha1'; }elseif($algorithm === 'HmacSHA256'){ $hash = 'sha256'; }else{ handleError(1, "Non-supported signing method specified"); }
        return base64_encode(hash_hmac($hash, $data, $key, true));
    }
}

/**************************************************************/
/************** GET PAY WITH AMAZON URL - GET *****************/
/**************************************************************/
function amazonGetPayNowButtonURL($amount = 0, $description = null, $reference_id = null, $return_url = null, $abandon_url = null, $ipn_url = null){

	//fields
    $fields_array["accessKey"] = AMAZON_AWSACCESSID;
    $fields_array["signatureMethod"] = 'HmacSHA256';
    $fields_array["signatureVersion"] = '2';
    $fields_array["amount"] = 'USD ' . $amount;
    $fields_array["description"] = $description;
    $fields_array["referenceId"] = $reference_id;
    $fields_array["immediateReturn"] = 0;
    $fields_array["returnUrl"] = $return_url;
    $fields_array["abandonUrl"] = $abandon_url;
    $fields_array["processImmediate"] = 0;
    $fields_array["ipnUrl"] = $ipn_url;
    $fields_array["cobrandingStyle"] = 'logo';
    $fields_array["collectShippingAddress"] = 0;
    $serviceEndPoint = parse_url(AMAXON_PIPELINE); 
    $fields_array["signature"] = SignatureUtils::signParameters($fields_array, AMAZON_AWSSECRETKEY, 'GET', $serviceEndPoint['host'], $serviceEndPoint['path'], $fields_array["signatureMethod"]);
    
    //return path
    return AMAXON_PIPELINE . '?' . SignatureUtils::_getParametersAsString($fields_array);
    
}

/**************************************************************/
/************** CONFIRM AMAZON PAYMENT ************************/
/**************************************************************/
function confirmAmazonPayment($params = array(), $end_point_url = null){

	// Compose the cURL request
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, AMAZON_FPS_PROD_ENDPOINT . '?Action=VerifySignature&UrlEndPoint=' . rawurlencode($end_point_url) . '&HttpParameters=' . rawurlencode(http_build_query($params, '', '&')) . '&Version=2008-09-17');
	curl_setopt($ch, CURLOPT_FILETIME, false);
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_CAINFO, DOCUMENT_ROOT . AMAXON_CERTIFICATE_LOCATION);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_NOSIGNAL, true);
	curl_setopt($ch, CURLOPT_USERAGENT, SITE_NAME); //original: ASPDonation-PHP-2.0-2010-09-13
	if(extension_loaded('zlib')){ curl_setopt($ch, CURLOPT_ENCODING, ''); }
	$response = curl_exec($ch);
	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$responseBody = substr($response, $headerSize);	
	curl_close($ch);
	
	//parse the xml
	$xml = new SimpleXMLElement($responseBody);

	//return boolean
	return (isset($xml->VerifySignatureResult->VerificationStatus) and 'Success' === (string)$xml->VerifySignatureResult->VerificationStatus);
	
}

/**************************************************************/
/************** SETTLE AMAZON PAYMENT *************************/
/**************************************************************/
function settleAmazonPayment($transcation_id = null){

    //build the fields array
    $fields_array["Action"] = 'Settle';
    $fields_array["AWSAccessKeyId"] = AMAZON_AWSACCESSID;
    $fields_array["ReserveTransactionId"] = $transcation_id;
    $fields_array["SignatureMethod"] = 'HmacSHA256';
    $fields_array["SignatureVersion"] = '2';
    $fields_array["Timestamp"] = gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time());
    $fields_array["Version"] = '2008-09-17';
    $serviceEndPoint = parse_url(AMAZON_FPS_PROD_ENDPOINT); 
    $fields_array["Signature"] = SignatureUtils::signParameters($fields_array, AMAZON_AWSSECRETKEY, 'GET', $serviceEndPoint['host'], $serviceEndPoint['path'], $fields_array["SignatureMethod"]);
 
	//make the request
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, AMAZON_FPS_PROD_ENDPOINT . '?' . SignatureUtils::_getParametersAsString($fields_array));
	curl_setopt($ch, CURLOPT_FILETIME, false);
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_CAINFO, DOCUMENT_ROOT . AMAXON_CERTIFICATE_LOCATION);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_NOSIGNAL, true);
	curl_setopt($ch, CURLOPT_USERAGENT, SITE_NAME);
	if(extension_loaded('zlib')){ curl_setopt($ch, CURLOPT_ENCODING, ''); }
	$response = curl_exec($ch);
	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$responseBody = substr($response, $headerSize);	
	curl_close($ch);

	//parse the xml
	$xml = new SimpleXMLElement($responseBody);
	
	//return 
	return (isset($xml->SettleResult->TransactionStatus) and (string)$xml->SettleResult->TransactionStatus === 'Pending');
	
}

?>