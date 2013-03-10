<?php

//get the staff emails
$staff_email_list = array(); $result = @mysql_query(" SELECT DISTINCT * FROM staff WHERE staff_email IS NOT NULL GROUP BY staff_email "); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $staff_email_list[] = $row; } mysql_free_result($result); }

//get the first appointment tomorrow
$the_first_appointment = array(); $result = @mysql_query(" SELECT * FROM dzpro_calendar LEFT JOIN dzpro_calendar_jumpers USING ( dzpro_calendar_id ) WHERE dzpro_calendar_active = 1 AND dzpro_calendar_date = '" . mysql_real_escape_string(date('Y-m-d', strtotime('+1 day'))) . "' AND dzpro_calendar_time != '00:00:00' AND dzpro_calendar_jumper_id IS NOT NULL ORDER BY UNIX_TIMESTAMP(CONCAT(dzpro_calendar_date, ' ', dzpro_calendar_time)) ASC LIMIT 1"); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $the_first_appointment = $row; } mysql_free_result($result); }

//get jumper count for the day
$total_jumper_count = null; $result = @mysql_query(" SELECT COUNT(dzpro_calendar_jumper_id) AS total_count FROM dzpro_calendar_jumpers LEFT JOIN dzpro_calendar USING ( dzpro_calendar_id ) WHERE dzpro_calendar_active = 1 AND dzpro_calendar_date = '" . mysql_real_escape_string(date('Y-m-d', strtotime('+1 day'))) . "' AND dzpro_calendar_time != '00:00:00' AND dzpro_calendar_jumper_id IS NOT NULL "); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $total_jumper_count = $row['total_count']; } mysql_free_result($result); }

//build the email string
$theNotificationEmail = '
<div style="border: 5px dotted #eeeeee; padding: 40px; font-family: Arial;">
	<h1>Start Time: ' . date('l, M jS, Y', strtotime($the_first_appointment['dzpro_calendar_date'])) . '</h1>
	<p><strong>We are expecting ' . (int)$total_jumper_count . ' students, the first group is at ' . substr($the_first_appointment['dzpro_calendar_time'], 0, 5) . '.</strong><p>
	<p>Please be at the dropzone, ready to jump, at ' .  date('H:i', strtotime($the_first_appointment['dzpro_calendar_date'] . ' ' . $the_first_appointment['dzpro_calendar_time']) - 1800) . '</p>
	<p>Thank you!</p>
</div>
';

//add the emails to outbox
if(have($staff_email_list) and $total_jumper_count > 0 and have($the_first_appointment)){ foreach($staff_email_list as $staff){  addEmailToOutbox($staff['staff_name'], $staff['staff_email'], 'Start Time Reminder ' . date('l, M jS, Y', strtotime($the_first_appointment['dzpro_calendar_date'])), $theNotificationEmail); } }

?>