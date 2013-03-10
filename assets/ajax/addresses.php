<?php

//where are we
define('RELATIVE_ASSETS_PATH', '..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//start a session
assureSession();

//get the addresses
$addresses = getUserAddresses();

//if none -- just stop
if(!have($addresses)){ 
	?>
		<option>please connect</option>
	<?php
}

//address name
$address_name = isset($_POST['name']) ? $_POST['name'] : 'address';

//print select box
$address_options = array(); foreach($addresses as $address_id_option => $address_option){ $address_options[$address_id_option] = ($address_id_option > 0) ? 'ship to ' . $address_option['dzpro_user_shipping_option_name'] . ' (' . $address_option['dzpro_user_shipping_option_address'] . ', ' . $address_option['dzpro_user_shipping_option_city'] . ')' : $address_option; }

//print select box options
printSelectBoxOptions($address_options, $address_name);

//stop
exit(0);

?>