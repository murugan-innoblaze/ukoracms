<?php
/*******************************************************************************/
/************************ RECORD SEARCH ****************************************/
/*******************************************************************************/
function recordSuggest($search = null, $clean = 0){
	if(empty($search)){ return false; }
	@mysql_query("INSERT INTO dzpro_searches ( dzpro_search_string, dzpro_search_clean, dzpro_search_date_added ) VALUES ( '" . mysql_real_escape_string($search) . "', '" . mysql_real_escape_string($clean) . "', NOW() )"); @mysql_query("UPDATE dzpro_suggest SET dzpro_suggest_weight = dzpro_suggest_weight + 1 WHERE dzpro_suggest_string = '" . mysql_real_escape_string($search) . "'"); if(mysql_affected_rows() == 0){ @mysql_query("INSERT INTO dzpro_suggest ( dzpro_suggest_weight , dzpro_suggest_string ) VALUES ( 0 , '" . mysql_real_escape_string($search) . "' )"); }
}
?>