<?php

/************************************************************/
/******************** GET TEXT MESSAGES .. AND SEND *********/
/************************************************************/
$messages = mysql_query_on_key(" SELECT * FROM dzpro_text_messages WHERE dzpro_text_message_date_sent = '0000-00-00 00:00:00' OR dzpro_text_message_date_sent IS NULL AND dzpro_text_message_failed = 0 LIMIT 12 ", 'dzpro_text_message_id'); if(have($messages)){ foreach($messages as $message_id => $message){
	if(false === ($done = sendMessageGV($message['dzpro_text_message_number'], $message['dzpro_text_message_text']))){
		mysql_update(" UPDATE dzpro_text_messages SET dzpro_text_message_failed = 1 WHERE dzpro_text_message_id = " . (int)$message_id . " ");
		handleError(2, 'Failed to send text message. Details: ' . serialize($message));
		echo 'not sent!';
	}else{
		echo 'sent!';
		mysql_update(" UPDATE dzpro_text_messages SET dzpro_text_message_date_sent = NOW() WHERE dzpro_text_message_id = " . (int)$message_id . " ");
	}
} }

?>