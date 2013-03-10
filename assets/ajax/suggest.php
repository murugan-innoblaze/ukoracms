<?php
//where are we
define('RELATIVE_ASSETS_PATH', '..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//get vars
$string = isset($_POST['search_string']) ? trim(preg_replace('/[^a-z^0-9]+/msi', ' ', urldecode(stripslashes($_POST['search_string'])))) : false;

//set return string
$array_string = '';

// print the list
if(false !== $string){ $string_sql = $string; $string_array = explode(' ', $string); if(!empty($string_array)){ $string_sql = ''; foreach($string_array as $string_key => $string_word){ if($string_key == sizeof($string_array)){ $string_sql .= ' ' . $string_word . ' '; }else{ $string_sql .= ' +' . $string_word . ' '; } } $string_sql = trim($string_sql); } $sql = " SELECT dzpro_suggest_string, dzpro_suggest_weight FROM dzpro_suggest WHERE MATCH (dzpro_suggest_string) AGAINST ('" . mysql_real_escape_string($string_sql) . "*' IN BOOLEAN MODE) ORDER BY dzpro_suggest_weight DESC LIMIT " . SUGGEST_RESULTS_LIMIT . " "; $result = @mysql_query($sql); if(mysql_num_rows($result) > 0){ $counter = 0; while($row = mysql_fetch_assoc($result)){ $suggest_string = (strlen($row['dzpro_suggest_string']) < SUGGEST_STRING_LENGTH) ? $row['dzpro_suggest_string'] : substr($row['dzpro_suggest_string'], 0, strrpos(substr($row['dzpro_suggest_string'], 0, SUGGEST_STRING_LENGTH), ' ')) . ' ...'; $search_words_array = explode(' ', $string); $search_words_candidates = array(); $search_words_replacements = array(); foreach($search_words_array as $this_search_word){ $search_words_candidates[] = $this_search_word; $search_words_replacements[] = '[[[[[[' . ucfirst($this_search_word) . ']]]]]]'; } $array_string .= '<li><a href="/feeds/' . prepareStringForUrl($row['dzpro_suggest_string']) . '-feeds/" title="' . ucwords(htmlentities($row['dzpro_suggest_string'])) . '" id="search_res_' . $counter++ . '">' . ucwords(str_ireplace(array('[[[[[[', ']]]]]]'), array('<strong>', '</strong>'), str_ireplace($search_words_candidates, $search_words_replacements, htmlentities(strtolower($suggest_string))))) . '</a></li>'; } mysql_free_result($result); } };

echo $array_string;

// Close the database connection
mysql_close($db);
?>
