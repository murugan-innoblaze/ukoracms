<a href="/" title="<?=prepareTag(HOST_NAME)?>"><img src="/assets/img/manager/ukora-logo-white.png" alt="<?=prepareTag(HOST_NAME)?>" /></a>
<script type="text/javascript">
	<!--
		$().ready(function(){
			$('a', '#sections_nav').click(function(){
				var original_pos = $('a.selected', '#sections_nav').offset();
				$('a.selected', '#sections_nav').removeClass('selected');
				$(this).addClass('selected');
				var new_pos = $('a.selected', '#sections_nav').offset();
				$('#white_arrow').animate({
					'top' : '+=' + -(original_pos.top - new_pos.top - 0)
				}, 200);
			});
		});
	//-->
</script>
<?php
$sql = "
			SELECT 
				dzpro_section_id,
				dzpro_section_name,
				dzpro_section_slug,
				dzpro_section_description
			FROM 
				dzpro_sections
			LEFT JOIN 
				dzpro_admin_to_section
			USING 
				( dzpro_section_id )
			WHERE 
				dzpro_admin_id = " . (int)$_SESSION['dzpro_admin_id'] . "
			GROUP BY 
				dzpro_section_id
			ORDER BY 
				dzpro_section_orderfield
		";
$result = mysql_query($sql) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
if(mysql_num_rows($result) > 0){
	$first_slug = array();
	preg_match('/^\/([a-z0-9\-\_]+)\//i', $_SERVER['PHP_SELF'], $first_slug);
?>
<ul id="sections_nav">
<?php
	while($row = mysql_fetch_assoc($result)){
		$selected = (isset($first_slug[1]) and strtolower($first_slug[1]) == strtolower($row['dzpro_section_slug'])) ? ' selected' : null;
		$this_image = (isset($first_slug[1]) and strtolower($first_slug[1]) == strtolower($row['dzpro_section_slug'])) ? '<img src="/assets/img/manager/arrow-left-white.png" alt="white arrow" id="white_arrow" />' : null;
		if(isset($first_slug[1]) and strtolower($first_slug[1]) == strtolower($row['dzpro_section_slug'])){
			/******** GLOBAL SECTION ID **********/
			$section_id = (int)$row['dzpro_section_id'];
			/*************************************/
		}
?>
	<li>
		<a href="/<?=strtolower($row['dzpro_section_slug'])?>/" title="<?=htmlentities(ucwords(strtolower($row['dzpro_section_description'])))?>" id="<?=strtolower($row['dzpro_section_slug'])?>" class="<?=strtolower($row['dzpro_section_slug'])?><?=$selected?>">
			<!-- block link -->
		</a>
		<?=htmlentities(ucwords(strtolower($row['dzpro_section_name'])))?>
		<?=$this_image?>
	</li>
<?php
	}
	mysql_free_result($result);
?>
</ul><!-- end sections_nav -->
<?php
}
?>