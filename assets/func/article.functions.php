<?php
/************************************************************************/
/******************* GET ARTICLE ARRAY **********************************/
/************************************************************************/
function getArticleArray($article_id = null){
	if(empty($article_id)){ return array(); }
	$result = @mysql_query("SELECT * FROM articles WHERE article_id = " . (int)$article_id); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ return $row; } }
	return array();
}
?>