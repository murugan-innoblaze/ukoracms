<?php
$sql = "
			SELECT 
				dzpro_section_page_id,
				dzpro_section_page_name,
				dzpro_section_script_name
			FROM
				dzpro_section_pages
			LEFT JOIN
				dzpro_admin_to_section_page
			USING 
				( dzpro_section_page_id )
			WHERE 
				dzpro_section_id = " . (int)$section_id . "
			AND	
				dzpro_admin_id = " . (int)$_SESSION['dzpro_admin_id'] . "
			GROUP BY 
				dzpro_section_page_id
			ORDER BY
				dzpro_section_page_orderfield ASC
		";
$result = mysql_query($sql) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
?>
<script type="text/javascript">
	$().ready(function(){
		$('a', '#top_right_nav').click(function(){
			$('a.selected', '#top_right_nav').removeClass('selected');
			$(this).addClass('selected');
		});
	});
</script>
<ul id="top_right_nav">
	<li>
		<a href="/?logout=true" title="Logout" class="logout">logout</a>
	</li>
	<li style="float: right; color: white; font-size: 13px; padding: 7px 14px; text-shadow: -1px 1px 1px #222;">
		Welcome <?php if($_SESSION['dzpro_admin_super'] == 1){ ?><img src="/assets/img/manager/super-user-icon.png" alt="Super User" style="vertical-align: middle; margin: 0 5px 3px 5px;" /><?php } ?><strong><?=prepareTag($_SESSION['dzpro_admin_name'])?></strong>
	</li>
<?php
if(mysql_num_rows($result) > 0){
	$second_slug = array();
	preg_match('/^\/[a-z0-9\-\_]+\/([a-z0-9\-\_]+\.php)/i', $_SERVER['PHP_SELF'], $second_slug);
	while($row = mysql_fetch_assoc($result)){
		$selected = (isset($second_slug[1]) and strtolower($second_slug[1]) == strtolower($row['dzpro_section_script_name'])) ? ' selected' : null;
		if(isset($second_slug[1]) and strtolower($second_slug[1]) == strtolower($row['dzpro_section_script_name'])){
			/******** GLOBAL SECTION ID **********/
			$section_page_id = (int)$row['dzpro_section_page_id'];
			/*************************************/
		}
?>
	<li>
		<a href="<?=$row['dzpro_section_script_name']?>" title="<?=htmlentities(ucwords(strtolower($row['dzpro_section_page_name'])))?>" class="<?=$selected?>"><?=htmlentities(ucwords(strtolower($row['dzpro_section_page_name'])))?></a>
	</li>
<?php	
	}
	mysql_free_result($result);
}
?>
	<li style="clear: both;"></li>
</ul><!-- end top_right_nav -->