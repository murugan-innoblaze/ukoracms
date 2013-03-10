<?php
/*****************************************************************************/
/************************* GET ARTICLES FOR TAG ARRAY ************************/
/*****************************************************************************/
function getArticleIdByCatPath($path, $cat_table_path_field, $cat_table_name, $assoc_table_name, $assoc_table_field){
	if(empty($path)){ return array(); }
	$result = @mysql_query("SELECT * FROM " . mysql_real_escape_string($cat_table_name) . " LEFT JOIN " . mysql_real_escape_string($assoc_table_name) . " USING ( " . mysql_real_escape_string($assoc_table_field) . " ) LEFT JOIN articles USING ( article_id ) WHERE  " . mysql_real_escape_string($cat_table_name) . "." . mysql_real_escape_string($cat_table_path_field) . " = '" . mysql_real_escape_string($path) . "'"); if(mysql_num_rows($result) > 0){ $return_array = array(); while($row = mysql_fetch_assoc($result)){ $return_array[$row['article_id']] = $row; } mysql_free_result($result); return $return_array; }
	return array();
}

/*****************************************************************************/
/************************* PLACE TAGS IN STRING ******************************/
/*****************************************************************************/
function placeTagLinks($string = null, $dzpro_tags = array()){
	if(have($dzpro_tags)){ $tags = array(); $replacements = array(); $html_element_reference = array(); $html_element_matches = array(); $html_element_close_matches = array(); $link_element_matches = array(); $all_element_matches = array(); preg_match_all('/\<a [^\>]+\>.+?\<\/a\>/msi', $string, $link_element_matches); preg_match_all('/\<[^a^\/]{1}[^\>]+\>/', $string, $html_element_matches); preg_match_all('/\<\/[^a]{1}[^\>]+\>/', $string, $html_element_close_matches); if(have($html_element_matches[0])){ $all_element_matches = array_merge($all_element_matches, $html_element_matches[0]); } if(have($html_element_close_matches[0])){ $all_element_matches = array_merge($all_element_matches, $html_element_close_matches[0]); } if(have($link_element_matches[0])){ $all_element_matches = array_merge($all_element_matches, $link_element_matches[0]); } if(have($all_element_matches)){ foreach($all_element_matches as $html_element){ if(!in_array($html_element, $html_element_reference)){ $html_element_reference[$html_element] = '{-{-{' . base64_encode($html_element) . '}-}-}'; } } } if(have($html_element_reference)){ foreach($html_element_reference as $find_element => $swap_element){ $string = str_ireplace($find_element, $swap_element, $string); } } foreach($dzpro_tags as $key => $tag){ $tags[] = '/([^a-z^|^0-9]{1})(' . strtolower($tag['dzpro_tag_name']) . ')([^a-z^|^0-9]{1})/'; $tags[] = '/([^a-z^|^0-9]{1})(' . ucfirst($tag['dzpro_tag_name']) . ')([^a-z^|^0-9]{1})/'; $tags[] = '/([^a-z^|^0-9]{1})(' . ucwords($tag['dzpro_tag_name']) . ')([^a-z^|^0-9]{1})/'; $tag_prep[] = '\1|-|-|' . base64_encode(strtolower($tag['dzpro_tag_name'])) . '|-|-|\3'; $tag_prep[] = '\1|-|-|' . base64_encode(ucfirst($tag['dzpro_tag_name'])) . '|-|-|\3'; $tag_prep[] = '\1|-|-|' . base64_encode(ucwords($tag['dzpro_tag_name'])) . '|-|-|\3'; $tag_reference[] = '|-|-|' . base64_encode(strtolower($tag['dzpro_tag_name'])) . '|-|-|'; $tag_reference[] = '|-|-|' . base64_encode(ucfirst($tag['dzpro_tag_name'])) . '|-|-|'; $tag_reference[] = '|-|-|' . base64_encode(ucwords($tag['dzpro_tag_name'])) . '|-|-|'; $replacements[] = '<a href="/tag/' . prepareStringForUrl($tag['dzpro_tag_name']) . '/" title="' . prepareTag($tag['dzpro_tag_description']) . '">' . prepareStringHtml(strtolower($tag['dzpro_tag_name'])) . '</a>'; $replacements[] = '<a href="/tag/' . prepareStringForUrl($tag['dzpro_tag_name']) . '/" title="' . prepareTag($tag['dzpro_tag_description']) . '">' . prepareStringHtml(ucfirst($tag['dzpro_tag_name'])) . '</a>'; $replacements[] = '<a href="/tag/' . prepareStringForUrl($tag['dzpro_tag_name']) . '/" title="' . prepareTag($tag['dzpro_tag_description']) . '">' . prepareStringHtml(ucwords($tag['dzpro_tag_name'])) . '</a>'; } $string = preg_replace($tags, $tag_prep, $string); $string = str_ireplace($tag_reference, $replacements, $string); if(have($html_element_reference)){ foreach($html_element_reference as $find_element => $swap_element){ $string = str_ireplace($swap_element, $find_element, $string); } } } return $string;
}
?>