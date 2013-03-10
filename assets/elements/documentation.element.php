<?php $documentation = mysql_query_on_key(" SELECT * FROM documentation WHERE documentation_active = 1 ORDER BY documentation_orderfield ASC ", 'documentation_id'); if(have($documentation)){ foreach($documentation as $documentation_id => $documentation_row){ ?>
<h2><?=prepareStringHtmlFlat($documentation_row['documentation_name'])?></h2>
<?=$documentation_row['documentation_main_html']?>
<?php if(is_file(DOCUMENT_ROOT . $documentation_row['documentation_1_image'])){ ?>
<div class="actions"><h3>Tutorial</h3><img src="<?=$documentation_row['documentation_1_image']?>" title="<?=prepareTag($documentation_row['documentation_name'])?>" style="border: 1px solid #dddddd;" /><?php if(have($documentation_row['documentation_1_caption'])){ echo '<p style="width: 590px; padding: 5px 5px 0px 5px;">' . prepareStringHtmlFlat($documentation_row['documentation_1_caption']) . '</p>'; } ?></div>
<?php } ?>
<?php if(is_file(DOCUMENT_ROOT . $documentation_row['documentation_2_image'])){ ?>
<div class="actions"><h3>Tutorial</h3><img src="<?=$documentation_row['documentation_2_image']?>" title="<?=prepareTag($documentation_row['documentation_name'])?>" style="border: 1px solid #dddddd;" /><?php if(have($documentation_row['documentation_2_caption'])){ echo '<p style="width: 590px; padding: 5px 5px 0px 5px;">' . prepareStringHtmlFlat($documentation_row['documentation_2_caption']) . '</p>'; } ?></div>
<?php } ?>
<?php if(is_file(DOCUMENT_ROOT . $documentation_row['documentation_3_image'])){ ?>
<div class="actions"><h3>Tutorial</h3><img src="<?=$documentation_row['documentation_3_image']?>" title="<?=prepareTag($documentation_row['documentation_name'])?>" style="border: 1px solid #dddddd;" /><?php if(have($documentation_row['documentation_3_caption'])){ echo '<p style="width: 590px; padding: 5px 5px 0px 5px;">' . prepareStringHtmlFlat($documentation_row['documentation_3_caption']) . '</p>'; } ?></div>
<?php } ?>
<?php if(have($documentation_row['documentation_code'])){ ?><h3>Code example</h3><pre class="prettyprint"><?=htmlentities($documentation_row['documentation_code'])?></pre><?php } ?>
<?php } } ?>