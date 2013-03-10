<?php
function getStaticContent($reference = null){
	$return = null; $result = @mysql_query(" SELECT * FROM dzpro_statics WHERE dzpro_static_name = '" . mysql_real_escape_string($reference) . "' "); if(mysql_num_rows($result)){ while($row = mysql_fetch_assoc($result)){ if(have($row['dzpro_static_html'])){ $return = $row['dzpro_static_html']; } } mysql_free_result($result); } return $return;
}
?>