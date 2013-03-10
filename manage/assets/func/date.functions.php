<?php
/*************************************************************/
/*********************** TO DATE *****************************/
/*************************************************************/
function convertDate($date_format = 'Y-m-d H:i:s', $date_string = null){
	if(!have($date_string)){ return null; }
	return date($date_format, strtotime($date_string));
}
?>