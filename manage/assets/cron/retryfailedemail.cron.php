<?php

//load mailer class
include_once SMTP_EMAIL_CLASS;

//Connect to SMTP
$Mailer = Mail::factory('smtp', array('host' => SMTP_HOST, 'port' => SMTP_PORT, 'auth' => SMTP_AUTH, 'username' => SMTP_USERNAME, 'password' => SMTP_PASSWORD));

//get the emails - first come first serve
$emails = array(); $result = @mysql_query("SELECT * FROM dzpro_outbox WHERE dzpro_outbox_date_send = '0000-00-00 00:00:00' AND dzpro_outbox_sending_failed = 1 ORDER BY RAND() ASC LIMIT " . (int)SMTP_OUTBOX_SEND_MAX); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $emails[$row['dzpro_outbox_id']] = $row; } }

//Send Email
if(isset($emails) and !empty($emails)){ 
	foreach($emails as $email){ 
		$this_mail = $Mailer->send($email['dzpro_outbox_email'], array('MIME-Version' => '1.0', 'Content-type' => 'text/html; charset=iso-8859-1', 'From' => SMTP_FROM, 'To' => $email['dzpro_outbox_to'] . ' <' . $email['dzpro_outbox_email'] . '>', 'Subject' => $email['dzpro_outbox_subject']), $email['dzpro_outbox_text']); 
		if(true !== PEAR::isError($this_mail)){ @mysql_query("UPDATE dzpro_outbox SET dzpro_outbox_date_send = NOW(), dzpro_outbox_sending_failed = 0 WHERE dzpro_outbox_id = " . (int)$email['dzpro_outbox_id']); }else{ echo $this_mail->getMessage(); }
	} 
}

?>