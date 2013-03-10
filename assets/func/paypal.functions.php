<?php

/****************************************************************************************/
/******************************** PAYPAL CONFIRM PAYMENT ********************************/
/****************************************************************************************/
function paypalConfirmPayment($FinalPaymentAmt, $token = null, $payer_id = null, $currencyCodeType = null, $paymentType = null){	
	$token = urlencode($token);
	$paymentType = urlencode($paymentType);
	$currencyCodeType = urlencode($currencyCodeType);
	$payerID = urlencode($payer_id);
	$serverName = urlencode(HOST_NAME);
	$nvpstr = '&TOKEN=' . $token . '&PAYERID=' . $payerID . '&PAYMENTREQUEST_0_PAYMENTACTION=' . $paymentType . '&PAYMENTREQUEST_0_AMT=' . $FinalPaymentAmt;
	$nvpstr .= '&PAYMENTREQUEST_0_CURRENCYCODE=' . $currencyCodeType . '&IPADDRESS=' . $serverName; 
	$resArray = paypalHash_call("DoExpressCheckoutPayment", $nvpstr);
	$ack = strtoupper($resArray["ACK"]);
	return $resArray;
}

/****************************************************************************************/
/******************************** CALL SHORTCUT EXPRESS CHECKOUT ************************/
/****************************************************************************************/
function paypalCallShortcutExpressCheckout($paymentAmount = null, $currencyCodeType = null, $paymentType = null, $returnURL = null, $cancelURL = null){
	$nvpstr = "&PAYMENTREQUEST_0_AMT=" . $paymentAmount;
	$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_PAYMENTACTION=" . $paymentType;
	$nvpstr = $nvpstr . "&RETURNURL=" . $returnURL;
	$nvpstr = $nvpstr . "&CANCELURL=" . $cancelURL;
	$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_CURRENCYCODE=" . $currencyCodeType;
	$resArray = paypalHash_call("SetExpressCheckout", $nvpstr);
	return $resArray;
}

/****************************************************************************************/
/******************************** PAYPAL HASH CALL **************************************/
/****************************************************************************************/
function paypalHash_call($methodName = null, $nvpStr = null){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,PAYPAL_API_ENDPOINT);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode(PAYPAL_VERSION) . "&PWD=" . urlencode(PAYPAL_API_PASSWORD) . "&USER=" . urlencode(PAYPAL_API_USERNAME) . "&SIGNATURE=" . urlencode(PAYPAL_API_SIGNATURE) . $nvpStr . "&BUTTONSOURCE=" . urlencode(PAYPAL_SBNCODE));
	$response = curl_exec($ch);
  	curl_close($ch);
	return paypalDeformatNVP($response);
}

/****************************************************************************************/
/******************************** REDIRECT TO PAYPAL ************************************/
/****************************************************************************************/
function redirectToPayPal($token = null){
	$payPalURL = PAYPAL_PP_URL . $token; header("Location: " . $payPalURL);
}

/****************************************************************************************/
/******************************** DEFORMAT NVP PAYPAL ***********************************/
/****************************************************************************************/
function paypalDeformatNVP($nvpstr = null){
	$intial = 0; $nvpArray = array();
	while(strlen($nvpstr)){
		$keypos = strpos($nvpstr, '=');
		$valuepos = strpos($nvpstr, '&') ? strpos($nvpstr, '&') : strlen($nvpstr);
		$keyval = substr($nvpstr, $intial,$keypos);
		$valval = substr($nvpstr, $keypos + 1, $valuepos - $keypos - 1);
		$nvpArray[urldecode($keyval)] = urldecode($valval);
		$nvpstr = substr($nvpstr, $valuepos + 1, strlen($nvpstr));
	}
	return $nvpArray;
}

?>