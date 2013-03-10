<?php 
/***************************************************************************/
/****************** PRINT STATUS BAR ***************************************/
/***************************************************************************/
function printStatusBar($value = null, $max = 100, $markers = array()){
	?>
		<div class="status_holder">
			<div class="status_bar">
				<div class="status_bar_overlay" style="width:<?=ceil(($value/$max)*100)?>%;"><!-- overlay --></div>
			</div>
			<?php if(have($markers)){ ?>
			<table cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<?php foreach($markers as $marker){ ?>
						<td style="width: <?=floor(100/sizeof($markers))?>%">
							<?=$marker?>
						</td>
						<?php } ?>
					</tr>
				</tbody>
			</table>
			<?php } ?>
		</div>
	<?php
}
?>