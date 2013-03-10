<?php if(have($this->current_page['dzpro_page_section_image'])){ ?>
<div id="section_image">
	<img src="<?=$this->current_page['dzpro_page_section_image']?>" alt="<?=prepareTag($this->current_page['dzpro_page_title'])?>" />
</div>
<?php }