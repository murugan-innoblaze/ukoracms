<?php
	$prices = array(
		5 => 'around $5',
		10 => 'around $10',
		15 => 'around $15',
		20 => 'around $20',
		30 => 'around $30',
		50 => 'around $50'
	);
?>
<div class="bucket_left">
	<h2>Price Range</h2>
	<ul style="list-style: none;">
	<?php foreach($prices as $price_target => $price){ $selected = (isset($_GET['priceTarget']) and $_GET['priceTarget'] == $price_target) ? true : false; ?>
		<li>
			<input type="checkbox" name="priceTarget[]" value="<?=(int)$price_target?>" <?php if($selected){ ?>checked="checked"<?php } ?> onclick="javascript:$.blockUI();window.location='<?=($selected) ? addToGetString(null, null, array('priceTarget', 'start')) : addToGetString('priceTarget', $price_target, 'start')?>';" />
			<a href="<?=($selected) ? addToGetString(null, null, array('priceTarget', 'start')) : addToGetString('priceTarget', $price_target, 'start')?>" title="<?=prepareTag($price)?>" onclick="javascript:$.blockUI();">
				<?=ucwords(prepareStringHtml($price))?>
			</a>
		</li>
	<?php } ?>
	</ul>
</div>