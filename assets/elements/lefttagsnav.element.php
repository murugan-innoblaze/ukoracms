<?php if(isset($this->tags) and have($this->tags)){ ?>
<div class="bucket_left">
	<h2>Categories</h2>
	<ul>
		<?php foreach($this->tags as $rtag){ ?>
		<li><a href="/tag/<?=prepareStringForUrl($rtag['dzpro_tag_name'])?>/" title="<?=prepareTag($rtag['dzpro_tag_title'])?>" <?php if(isset($this->the_tag['tag']['dzpro_tag_id']) and (int)$this->the_tag['tag']['dzpro_tag_id'] == (int)$rtag['dzpro_tag_id']){ ?>class="active"<?php } ?>><?=prepareStringHtml(compressString(ucwords(strtolower($rtag['dzpro_tag_name'])), 30))?></a></li>							
		<?php } ?>
	</select>
</div>
<?php } ?>