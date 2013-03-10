<?php

/************************************************************************/
/********************* SEND TEXT MESSAGE ********************************/
/************************************************************************/
function sendMessageGV($phone_number = null, $message = null){
	if(!have($phone_number) or !have($message)){ return false; }
	$GV = new GoogleVoice(GOOGLE_VOICE_USERNAME, GOOGLE_VOICE_PASSWORD);
	$response = $GV->sms($phone_number, $message);
	return !preg_match('/(Moved Temporarily|Error)/i', $response);
}

?>