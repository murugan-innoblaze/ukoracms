<?php
/************************************************************************/
/******************* GET COMMENT ARRAY **********************************/
/************************************************************************/
function getCommentArray($comment_id = null){
	if(empty($comment_id)){ return array(); }
	$result = @mysql_query("SELECT * FROM dzpro_comments WHERE dzpro_comment_id = " . (int)$comment_id); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ return $row; } }
	return array();
}
?>