<?php
/********************************************************/
/**************** DOES THE ORDER EXIST ******************/
/********************************************************/
function haveOrder($order_id = null){
	if(!have($order_id)){ return false; }
	return mysql_query_got_rows(" SELECT * FROM dzpro_orders WHERE dzpro_order_id = " . (int)$order_id);
}

/********************************************************/
/**************** DOES ITEM PID EXIST *******************/
/********************************************************/
function haveItemPid($item_pid = null){
	if(!have($item_pid)){ return false; } 
	return mysql_query_got_rows(" SELECT * FROM dzpro_shop_items WHERE dzpro_shop_item_pid IS NOT NULL AND dzpro_shop_item_pid = '" . mysql_real_escape_string($item_pid) . "'");
}

/********************************************************/
/**************** UPDATE ITEM QUANTITY BY PID ***********/
/********************************************************/
function updateItemQuantity($item_pid = null, $item_quantity = null){
	if(empty($item_pid)){ return false; }
	if(empty($item_quantity)){ return false; }
	if(!haveItemPid($item_pid)){ return false; }
	return mysql_update(" UPDATE dzpro_shop_items SET dzpro_shop_item_quantity = " . (int)$item_quantity . " WHERE dzpro_shop_item_pid IS NOT NULL AND dzpro_shop_item_pid = '" . mysql_real_escape_string($item_pid) . "' ");
}
?>