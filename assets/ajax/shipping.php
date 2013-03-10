<?php

//where are we
define('RELATIVE_ASSETS_PATH', '..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//start a session
assureSession();

//get address id
$address_id = isset($_POST['address_id']) ? (int)$_POST['address_id'] : false; 

//check
if(!$address_id){ exit(0); }

//cart class
$Cart = new Cart($db);

//get shipping options
$arrival_options = $Cart->getShippingOptionsArray($address_id);

//current shipping method
$shipping_method = $Cart->getShipmentMethod($address_id);

//get subtotal
$this_address_subtotal = $Cart->getShipmentSubtotal($address_id);

//check again
if(!have($arrival_options)){ ?>
<script type="text/javascript" src="/assets/js/fboxusage.js"></script>
No arrival options. 
<strong style="color: red;">&bull; Invalid address &bull;</strong> 
<a href="/my/addresses/?action=edit&amp;record_id=<?=(int)$address_id?>" class="fancybox_iframe address_link" title="Click here to review and fix this shipping address" onclick="javascript:return confirm('After you click OK you will be able to edit your shipping address. Please make sure that the zipcode is correct. Thank you!');">click here to fix</a>
<?php exit(0); }

if(have($arrival_options)){
?>
<select name="arrival_options">
	<option value="">pick delivery date</option>
	<?php foreach($arrival_options as $arrival_date_unix => $arrival_date_array){ ?>
	<?php if(($arrival_date_array['shipping_method']['type'] == '90' or $arrival_date_array['shipping_method']['type'] == '92') and $this_address_subtotal >= 100){ $arrival_date_array['shipping_method']['price'] = 0; $arrival_date_array['shipping_method']['name'] = 'Free ground shipping'; } ?>
	<?php $shipping_method_encrypted_array = base64_encode(encryptString(json_encode($arrival_date_array), 'shipping encryption key 12312390')); ?>
	<option value="<?=$shipping_method_encrypted_array?>" <?php if(isset($shipping_method['dzpro_user_shipping_string']) and $shipping_method['dzpro_user_shipping_string'] == $shipping_method_encrypted_array){ ?>selected="selected"<?php } ?>>
		<!-- shipping date: <?=$arrival_date_array['shipping_date_string']?> --> 
		<?=$arrival_date_array['arrival_date_string']?> 
		[<?=$arrival_date_array['shipping_method']['name']?>]
		($<?=number_format($arrival_date_array['shipping_method']['price'], 2)?>)
	</option>
	<?php } ?>
</select>
<?php } ?>