<?php
/************************************************************************/
/******************* RETURN ITEMS ARRAY FOR PAGE ************************/
/************************************************************************/
function returnItemForPage($page_id = null){
	if(empty($page_id)){ return false; }
	$shop_items = array(); $result = @mysql_query("SELECT * FROM dzpro_shop_items LEFT JOIN dzpro_shop_item_to_option USING ( dzpro_shop_item_id ) LEFT JOIN dzpro_shop_item_options USING ( dzpro_shop_item_option_id ) LEFT JOIN dzpro_shop_item_to_page USING ( dzpro_shop_item_id ) WHERE dzpro_shop_item_available_from < NOW() AND NOW() < dzpro_shop_item_available_to AND dzpro_page_id = " . (int)$page_id . " ORDER BY dzpro_shop_item_available_to ASC "); if(mysql_num_rows($result) > 0){	while($row = mysql_fetch_assoc($result)){ foreach($row as $key => $value){ if(matchPrepend('dzpro_shop_item', $key) and !empty($row['dzpro_shop_item_id'])){ $shop_items[$row['dzpro_shop_item_id']]['product'][$key] = $value; } } foreach($row as $key => $value){ if(matchPrepend('dzpro_shop_item_option', $key) and !empty($row['dzpro_shop_item_option_id'])){ $shop_items[$row['dzpro_shop_item_id']]['options'][$row['dzpro_shop_item_option_id']][$key] = $value; } } } } return $shop_items;
}

/************************************************************************/
/******************* RETURN DEFAULT ORDER STATUS ************************/
/************************************************************************/
function returnDefaultOrderStatus(){
	$result = @mysql_query(" SELECT * FROM dzpro_order_status ORDER BY dzpro_order_status_orderfield ASC LIMIT 1 "); if(mysql_num_rows($result) > 0){ $return = array(); while($row = mysql_fetch_assoc($result)){ $return = $row; } mysql_free_result($result); return $return; }
	return false;	
}
?>