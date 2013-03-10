<div class="page-header">
	<h1>
		<?=prepareStringHtml(limitString($this->current_page['dzpro_page_title'], 40))?>
		<?php if(isset($this->current_page['dzpro_page_tagline']) and have($this->current_page['dzpro_page_tagline'])){ ?><small><?=prepareStringHtmlFlat(limitString($this->current_page['dzpro_page_tagline'], 40))?></small><?php } ?>
	</h1>
</div>