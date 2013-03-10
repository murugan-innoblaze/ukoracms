<?php
/***************************************************************************/
/****************** SAVE CARD DATA *****************************************/
/***************************************************************************/
function saveCardData($card_key = null, $payment_method = null, $card_data = null, $cvv_code = null){
	if(!activeUserSession()){ return false; }
	if(false !== ($card_id = introduceCard($card_key, $payment_method))){ introduceCardDataArray($card_id, $card_data, $cvv_code); }
	return false;
}

/***************************************************************************/
/****************** INTRODUCE CARD *****************************************/
/***************************************************************************/
function introduceCard($card_key = null, $payment_method = null){
	if(empty($card_key)){ return false; }
	if(empty($payment_method)){ return false; }
	$card_id = null; if(false === ($card_id = selectCard($card_key, $payment_method))){ $card_id = insertCard($card_key, $payment_method); }
	return $card_id;
}

/***************************************************************************/
/****************** SELECT CARD ********************************************/
/***************************************************************************/
function selectCard($card_key = null, $payment_method = null){
	if(empty($card_key)){ return false; }
	if(empty($payment_method)){ return false; }
	$result = @mysql_query("SELECT * FROM dzpro_user_cards WHERE dzpro_user_card_key = '" . mysql_real_escape_string($card_key) . "' AND dzpro_payment_method = '" . mysql_real_escape_string($payment_method) . "' AND dzpro_user_id = '" . mysql_real_escape_string(getUserId()) . "'"); if(mysql_num_rows($result) > 0){ $return = false; while($row = mysql_fetch_assoc($result)){ $return = $row['dzpro_user_card_id']; } mysql_free_result($result); return $return; }
	return false;	
}

/***************************************************************************/
/****************** SELECT CARD DETAILS ARRAY ******************************/
/***************************************************************************/
function getCardDetails($card_key = null, $payment_method = null, $cvv_code = null){
	if(empty($card_key)){ return false; } if(empty($payment_method)){ return false; } if(!have($cvv_code)){ return false; }
	$result = @mysql_query("SELECT * FROM dzpro_user_cards LEFT JOIN dzpro_user_card_data USING ( dzpro_user_card_id ) WHERE dzpro_user_card_key = '" . mysql_real_escape_string($card_key) . "' AND dzpro_payment_method = '" . mysql_real_escape_string($payment_method) . "' AND dzpro_user_cards.dzpro_user_id = '" . mysql_real_escape_string(getUserId()) . "'"); if(mysql_num_rows($result) > 0){ $return = false; while($row = mysql_fetch_assoc($result)){ $return[decryptString($row['dzpro_user_card_data_key'], CARD_DATA_SALT . $cvv_code)] = decryptString($row['dzpro_user_card_data_value'], CARD_DATA_SALT . $cvv_code); } mysql_free_result($result); return $return; }
	return false;
}

/***************************************************************************/
/****************** INSERT CARD ********************************************/
/***************************************************************************/
function insertCard($card_key = null, $payment_method = null){
	if(empty($card_key)){ return false; }
	if(empty($payment_method)){ return false; }
	@mysql_query("INSERT INTO dzpro_user_cards ( dzpro_user_card_key, dzpro_payment_method, dzpro_user_id, dzpro_user_card_date_added ) VALUES ( '" . mysql_real_escape_string($card_key) . "', '" . mysql_real_escape_string($payment_method) . "', '" . mysql_real_escape_string(getUserId()) . "', NOW() )");
	if(false !== ($card_id = mysql_insert_id())){ return $card_id; }
	return false;
}

/***************************************************************************/
/****************** INTRODUCT CART DATA ************************************/
/***************************************************************************/
function introduceCardDataArray($card_id, $card_data = null, $cvv_code = null){
	if(empty($card_data)){ return false; } if(!have($cvv_code)){ return false; }
	foreach($card_data as $card_data_key => $card_data_value){ introduceCardData($card_id, $card_data_key, $card_data_value, $cvv_code); }
	return true;
}

/***************************************************************************/
/****************** INTRODUCe CART DATA ************************************/
/***************************************************************************/
function introduceCardData($card_id, $card_data_key = null, $card_data_value = null, $cvv_code = null){
	if(empty($card_data_key)){ return false; } if(empty($card_data_value)){ return false; } if(!have($cvv_code)){ return false; }
	if(false === selectCardData($card_id, $card_data_key, $cvv_code)){ insertCardData($card_id, $card_data_key, $card_data_value, $cvv_code); }
}

/***************************************************************************/
/****************** SELECT CART DATA ***************************************/
/***************************************************************************/
function selectCardData($card_id, $card_data_key = null, $cvv_code = null){
	if(empty($card_data_key)){ return false; } if(!have($cvv_code)){ return false; }
	$result = @mysql_query("SELECT * FROM dzpro_user_card_data WHERE dzpro_user_id = '" . mysql_real_escape_string(getUserId()) . "' AND dzpro_user_card_id = " . (int)$card_id . " AND dzpro_user_card_data_key = '" . mysql_real_escape_string(encryptString($card_data_key, CARD_DATA_SALT . $cvv_code)) . "'"); if(mysql_num_rows($result) > 0){ mysql_free_result($result); return true; }
	return false;	
}

/***************************************************************************/
/****************** INSERT CART DATA ***************************************/
/***************************************************************************/
function insertCardData($card_id, $card_data_key = null, $card_data_value = null, $cvv_code = null){
	if(empty($card_data_key)){ return false; } if(!have($cvv_code)){ return false; } if(empty($card_data_value)){ return false; }
	@mysql_query("INSERT INTO dzpro_user_card_data ( dzpro_user_id, dzpro_user_card_id, dzpro_user_card_data_key, dzpro_user_card_data_value, dzpro_user_card_data_date_added ) VALUES ( '" . mysql_real_escape_string(getUserId()) . "', " . (int)$card_id . ", '" . mysql_real_escape_string(encryptString($card_data_key, CARD_DATA_SALT . $cvv_code)) . "', '" . mysql_real_escape_string(encryptString($card_data_value, CARD_DATA_SALT . $cvv_code)) . "', NOW() )"); if(mysql_insert_id() > 0){ return true; }
	return false;
}

/***************************************************************************/
/****************** GET USER CARDS *****************************************/
/***************************************************************************/
function getUserCards($payment_method = null){
	if(empty($payment_method)){ return false; }
	$return = array(); $result = @mysql_query("SELECT * FROM dzpro_user_cards WHERE dzpro_payment_method = '" . mysql_real_escape_string($payment_method) . "' AND dzpro_user_id = '" . mysql_real_escape_string(getUserId()) . "'"); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $return[$row['dzpro_user_card_id']] = $row; } mysql_free_result($result); } 
	return $return;
}

/***************************************************************************/
/****************** GET CARD TYPE FROM NUMBER ******************************/
/***************************************************************************/
function getCardType($cc_number = null){
	if(!have($cc_number)){ return false; }
	switch(true){
		case(preg_match('/^4\d{3}-?\d{4}-?\d{4}-?\d{4}$/', $cc_number)): return 'Visa'; break;
		case(preg_match('/^5[1-5]\d{2}-?\d{4}-?\d{4}-?\d{4}$/', $cc_number)): return 'MasterCard'; break;
		case(preg_match('/^6011-?\d{4}-?\d{4}-?\d{4}$/', $cc_number)): return 'Discover'; break;
		case(preg_match('/^3[4,7]\d{13}$/', $cc_number)): return 'AmericanExpress'; break;
		case(preg_match('/^3[0,6,8]\d{12}$/', $cc_number)): return 'Diners'; break;
		default: return 'Unknown'; break;
	}
	return false;
}
?>