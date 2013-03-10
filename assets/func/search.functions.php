<?php
/****************************************************************************************/
/******************************** DO USER SEARCH ****************************************/
/****************************************************************************************/
function searchUsers($search_string = null){
	if(!empty($search_string)){ $usql = "SELECT *, MATCH(dzpro_user_meta_value) AGAINST ('" . mysql_real_escape_string($search_string) . "') AS score FROM dzpro_user_meta LEFT JOIN dzpro_users USING ( dzpro_user_id ) WHERE MATCH(dzpro_user_meta_value) AGAINST ('" . mysql_real_escape_string($search_string) . "') > 0.02 GROUP BY dzpro_user_id ORDER BY score DESC LIMIT " . SEARCH_USER_RESULTS; $uresult = @mysql_query($usql); if(mysql_num_rows($uresult) > 0){ $return_array = array(); while($urow = mysql_fetch_assoc($uresult)){ $return_array[] = $urow; } mysql_free_result($uresult); return $return_array; } }
	return null;
}

/****************************************************************************************/
/******************************** DO ARTICLE SEARCH *************************************/
/****************************************************************************************/
function searchArticles($search_string = null){
	if(!empty($search_string)){ $asql = "SELECT *, MATCH(article_name, article_excerpt, article_html, article_author, article_author_credentials, article_publisher) AGAINST ('" . mysql_real_escape_string($search_string) . "') AS score FROM articles WHERE MATCH(article_name, article_excerpt, article_html, article_author, article_author_credentials, article_publisher) AGAINST ('" . mysql_real_escape_string($search_string) . "') > 0.02 ORDER BY score DESC LIMIT " . SEARCH_ARTICLE_RESULTS; $aresult = @mysql_query($asql); if(mysql_num_rows($aresult) > 0){ $return_array = array(); while($arow = mysql_fetch_assoc($aresult)){ $return_array[] = $arow; } mysql_free_result($aresult); return $return_array; } }
	return null;
}

/****************************************************************************************/
/******************************** SEARCH PAGES ******************************************/
/****************************************************************************************/
function searchPages($search_string = null){
	if(empty($search_string)){ return null; }
	$result = @mysql_query(" SELECT *, MATCH( dzpro_page_name, dzpro_page_keywords, dzpro_page_description, dzpro_page_title ) AGAINST ('" . mysql_real_escape_string($search_string) . "') AS score FROM dzpro_pages WHERE MATCH ( dzpro_page_name, dzpro_page_keywords, dzpro_page_description, dzpro_page_title ) AGAINST ( '" . mysql_real_escape_string($search_string) . "' ) > 0.02 ORDER BY score DESC LIMIT " . SEARCH_PAGES_RESULTS); if(mysql_num_rows($result) > 0){ $return_array = array(); while($row = mysql_fetch_assoc($result)){ $return_array[$row['dzpro_page_id']] = $row; } mysql_free_result($result); return $return_array; }
	return null;
}

/****************************************************************************************/
/******************************** SEARCH PRODUCTS ***************************************/
/****************************************************************************************/
function searchProducts($search_string = null){
	if(empty($search_string)){ return null; }
	$result = @mysql_query(" SELECT *, MATCH( product_name, product_description_html, product_features_html ) AGAINST ('" . mysql_real_escape_string($search_string) . "') AS score FROM products WHERE MATCH ( product_name, product_description_html, product_features_html ) AGAINST ( '" . mysql_real_escape_string($search_string) . "' IN BOOLEAN MODE) > 0 ORDER BY score DESC LIMIT " . SEARCH_PAGES_RESULTS); if(mysql_num_rows($result) > 0){ $return_array = array(); while($row = mysql_fetch_assoc($result)){ $return_array[$row['dzpro_page_id']] = $row; } mysql_free_result($result); return $return_array; }
	return null;
}

?>