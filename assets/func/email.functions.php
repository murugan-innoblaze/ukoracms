<?php
/************************************************************************/
/******************* SEND EMAIL WITH HTML *******************************/
/************************************************************************/
function send_email_html($email, $name, $subject, $message_html){
	$headers  = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/html; charset=iso-8859-1' . "\r\n" .	'To: ' . $name . ' <' . $email . '>' . "\r\n" .	'From: ' . SEND_FROM_NAME . ' <' . SEND_FROM_EMAIL . '>' . "\r\n";
	return mail($email, $subject, $message_html, $headers);
}

/************************************************************************/
/******************* SEND EMAIL WITHOUT HTML ****************************/
/************************************************************************/
function send_email($email, $name, $subject, $message){
	$headers = 'From: ' . SEND_FROM_EMAIL . "\r\n" . 'Reply-To: ' . SEND_FROM_EMAIL . "\r\n" . 'X-Mailer: PHP/' . phpversion();
	return mail($email, $subject, $message, $headers);
}

/********************************************************************************/
/*************************** AD EMAIL TO OUTBOX *********************************/
/********************************************************************************/
function addEmailToOutbox($to_name = null, $to_email = null, $to_subject = null, $to_body = null){
	if(empty($to_name) or empty($to_email) or empty($to_subject) or empty($to_body)){ return false; }
	@mysql_query("INSERT INTO dzpro_outbox (dzpro_outbox_to, dzpro_outbox_email, dzpro_outbox_subject, dzpro_outbox_text, dzpro_outbox_date_added) VALUES ('" . mysql_real_escape_string($to_name) . "', '" . mysql_real_escape_string($to_email) . "', '" . mysql_real_escape_string($to_subject) . "', '" . mysql_real_escape_string($to_body) . "', NOW())"); if(mysql_insert_id() > 0){ return true; }else{ return false; }
}
?>