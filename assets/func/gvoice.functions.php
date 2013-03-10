<?php

/************************************************************************/
/********************* SEND TEXT MESSAGE ********************************/
/************************************************************************/
function sendTextMessage($phone_number = null, $message = null){
	if(!have($phone_number) or !have($message)){ return false; }
	return mysql_insert(" INSERT INTO dzpro_text_messages ( dzpro_user_id, dzpro_visitor_id, dzpro_text_message_text, dzpro_text_message_number, dzpro_text_message_date_added ) VALUES ( '" . mysql_real_escape_string(getUserId()) . "', '" . mysql_real_escape_string(getVisitorId()) . "', '" . mysql_real_escape_string($message) . "', '" . mysql_real_escape_string($phone_number) . "', NOW() ) ");
}

?>