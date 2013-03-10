<?php

class Cart extends Payment { 
	
	/***************************************/
	/*********** PAGE CONSTRUCTOR **********/
	/***************************************/	
	function __construct($db){
		
		//database connection
		$this->db = $db;
		
		//assure ession
		assureSession();
		
		//cart array
		$this->cart = array();
		
		//cart total quantity
		$this->cart_quantity = null;
		
		//cart with products
		$this->cart_products = array();
		
		//cart message
		$this->cart_message = null;
		
		//request checkout
		$this->request_checkout = false;
		
		//payment method
		$this->payment_method = null;
		
		//payment details
		$this->payment_details = array();
		
		//create of select cart
		self::createOrSelectCart();

		//refresh cart
		if(isset($_GET['refresh_cart']) and !empty($_GET['refresh_cart'])){ self::killCart(); }
		
		//add item to cart
		if(isset($_POST['item_id']) and !empty($_POST['item_id'])){ self::addItemToCart($_POST['item_id']); self::recordItemHit($_POST['item_id']); self::setCartAgreed(0); }
		
		//update delivery address for all
		if(isset($_POST['old_address_id']) and isset($_POST['new_address_id']) and isset($_POST['scope']) and $_POST['scope'] == 'all'){ self::updateAllAddresses($_POST['old_address_id'], $_POST['new_address_id']); self::setCartAgreed(0); }

		//update delivery address for item
		if(isset($_POST['address_cart_item_id']) and isset($_POST['new_address_id']) and isset($_POST['scope']) and $_POST['scope'] == 'item'){ self::updateCartItemAddress($_POST['address_cart_item_id'], $_POST['new_address_id']); self::setCartAgreed(0); }
			
		//update item
		if(isset($_POST['cart_item_id']) and !empty($_POST['cart_item_id'])){ self::updateItemToCart(); self::setCartAgreed(0); }
	
		//update shipment message
		if(isset($_POST['message']) and isset($_POST['address_id']) and !empty($_POST['address_id'])){ self::setShipmentMessage($_POST['address_id'], $_POST['message']); exit(0); }
		
		//shipping string
		if(isset($_POST['shipping_string']) and isset($_POST['address_id']) and !empty($_POST['address_id'])){ self::setShipmentMethod($_POST['address_id'], $_POST['shipping_string']); self::setCartAgreed(0); }
		
		//add product options
		if(isset($_POST['options_string']) and !empty($_POST['options_string'])){ self::setCartItemOptions($_POST['options_string']); }
		
		//mark agreed to conditions
		if(isset($_POST['agreed_to_conditions']) and ($_POST['agreed_to_conditions'] == 0 or $_POST['agreed_to_conditions'] == 1)){ self::setCartAgreed($_POST['agreed_to_conditions']); }
	
		//build cart array
		$this->cart_array = array(); self::buildCartArray();
		
		//first payment actions
		if(isset($_GET['pmethod']) and have($_GET['pmethod'])){ self::intiatePaymentAction($_GET['pmethod']); }

		/***************************************************************************/
		/****************** TRY PREVIOUSLY SAVED CARD AND CHECKOUT *****************/
		/***************************************************************************/
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'processPreviousCard'){
			
			//check user session
			if(!activeUserSession()){ echo json_encode(array('status' => 'false', 'message' => 'Your login has expired, please refresh the page and login again.')); exit(0); }
			
			//build parent (Payment)
			parent::__construct($this->db);

			//payment method
			$this->payment_method = have($_POST['paymentMethod']) ? $_POST['paymentMethod'] : null;
			
			//set payment method
			parent::setPaymentMethod($this->payment_method);
			
			//set card key
			$this_card_key = (isset($_POST['card_key']) and have($_POST['card_key'])) ? $_POST['card_key'] : null;
			
			//get cvv code
			$cvv_code = (isset($_POST['cvv_code']) and have($_POST['cvv_code'])) ? $_POST['cvv_code'] : null;
			
			//process the payment
			$this->payment_details = parent::processSavedCardPayment(
				self::getTotalAmount(), 
				//0.08,
				$this_card_key, 
				$this->payment_method,
				$cvv_code
			);
			
			//if payment is successfull - build the order
			if(have($this->payment_details['status']) and $this->payment_details['status'] == 'true'){ self::buildTheOrder(); }
			
			//return json encoded array
			echo json_encode($this->payment_details);
			
			//stop
			exit(); 
		
		}

		/***************************************************************************/
		/****************** TRY CREDIT CARD PAYMENT AND CHECKOUT *******************/
		/***************************************************************************/
		if(isset($_POST['cc_request']) and have($_POST['cc_request'])){ 

			//check user session
			if(!activeUserSession()){ echo json_encode(array('status' => 'false', 'message' => 'Your login has expired, please refresh the page and login again.')); exit(0); }
		
			//build parent (Payment)
			parent::__construct($this->db);
		
			//set payment method
			parent::setPaymentMethod('authorize.net');
			
			//process the payment
			$this->payment_details = parent::processPaymentRequest(
				self::getTotalAmount(),
				//0.12,
				isset($_POST['cc_number']) ? $_POST['cc_number'] : null,
				$_POST['cc_year'],
				$_POST['cc_month'],
				$_POST['cc_csv'],
				$_POST['cc_name'],
				$_POST['cc_address'],
				$_POST['cc_city'],
				$_POST['cc_state'],
				$_POST['cc_zip'],
				isset($_POST['do_not_remember']) ? $_POST['do_not_remember'] : null
			);
			
			//if payment is successfull - build the order
			if(isset($this->payment_details['status']) and $this->payment_details['status'] == 'true'){ self::buildTheOrder(); }
			
			//return json encoded array
			echo json_encode($this->payment_details);
			
			//stop
			exit(); 
		
		}
		
		/***************************************************************************/
		/****************** TRY PAYPAL EXPRESS PAYMENT AND CHECKOUT ****************/
		/***************************************************************************/
		if(isset($_POST['paypal_request']) and $_POST['paypal_request'] == 'true'){
		
			//check user session
			if(!activeUserSession()){ echo json_encode(array('status' => 'false', 'message' => 'Your login has expired, please refresh the page and login again.')); exit(0); }		
		
			//build parent (Payment)
			parent::__construct($this->db);
		
			//set payment method
			parent::setPaymentMethod('paypalexpress');
			
			//try the payment
			$this->payment_details = parent::processPaymentRequestFlexible(array(
				'amount' => self::getTotalAmount(),
				//'amount' => 0.09,
				'PayerID' => isset($_POST['PayerID']) ? $_POST['PayerID'] : null,
				'token' => isset($_POST['token']) ? $_POST['token'] : null
			));
			
			//if payment is successfull - build the order
			if(isset($this->payment_details['status']) and $this->payment_details['status'] == 'true'){ self::buildTheOrder(); }
			
			//return json encoded array
			echo json_encode($this->payment_details);
			
			//stop
			exit();			
		
		}

		/***************************************************************************/
		/****************** CONFIRM AMAZON PAYMENT AND CHECKOUT ********************/
		/***************************************************************************/
		if(isset($_GET['transactionId']) and isset($_GET['signature']) and isset($_GET['signatureMethod']) and isset($_GET['pmethod']) and $_GET['pmethod'] == 'amazon'){

			//check user session
			if(!activeUserSession()){ echo json_encode(array('status' => 'false', 'message' => 'Your login has expired, please refresh the page and login again.')); exit(0); }

			//build parent (Payment)
			parent::__construct($this->db);
		
			//set payment method
			parent::setPaymentMethod('amazonpayments');
			
			//get the parameters
			$need_keys = array(
				'transactionId', 
				'transactionDate', 
				'signatureVersion', 
				'signatureMethod', 
				'status', 
				'buyerEmail', 
				'referenceId', 
				'operation', 
				'transactionAmount', 
				'recipientEmail', 
				'buyerName', 
				'signature', 
				'recipientName', 
				'paymentMethod', 
				'certificateUrl', 
				'paymentReason'
			);
			
			//build parameters array
			$params = array(); foreach($need_keys as $amazon_key){ $params[$amazon_key] = isset($_GET[$amazon_key]) ? $_GET[$amazon_key] : null; }
		
			//set the return path
			$params['return_path'] = 'https://www.wisconsincheesemart.com/cart/pmethod/amazon/';
		
			//try the payment
			$this->payment_details = parent::processPaymentRequestFlexible($params);
		
			//if payment is successfull - build the order
			if(isset($this->payment_details['status']) and $this->payment_details['status'] == 'true'){ self::buildTheOrder(); header('Location: ?completed=true'); exit(0); }
			
		}

		//refresh just cart
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'justcart'){ self::printCartContents(); exit(); }

	}

	/***************************************************************************/
	/****************** INITIATE PAYMENT ACTIONS *******************************/
	/***************************************************************************/
	protected function intiatePaymentAction($pmethod = null){
		switch($pmethod){
		
			//requested to pay with creditcard
			case 'cc':

				//requested checkout
				$this->request_checkout = true;
							
			break;
			
			//requested to pay with paypal (express)
			case 'paypal':
				
				//requested checkout
				$this->request_checkout = true;
				
				//do the redirect - if needed
				if(!(isset($_GET['PayerID']) and have($_GET['PayerID']) and isset($_GET['token']) and have($_GET['token']))){ $ppresponse = paypalCallShortcutExpressCheckout(0.16, "USD", "Sale", 'https://www.wisconsincheesemart.com/cart/pmethod/paypal/', 'https://www.wisconsincheesemart.com/cart/'); if(strtoupper($ppresponse["ACK"]) == "SUCCESS" || strtoupper($ppresponse["ACK"]) == "SUCCESSWITHWARNING"){ redirectToPayPal($ppresponse["TOKEN"]); }else{ handleError(1, 'Paypal as a payment method returned an error. Details: ' . serialize($ppresponse)); } }
				
			break;
			
			//requested to pay via amazon
			case 'amazon':
	
				//requested checkout
				$this->request_checkout = true;
				
			break;
			
			default:
				return false;
			break;
		}
	}

	/***************************************************************************/
	/****************** SELECT CART ********************************************/
	/***************************************************************************/
	protected function buildTheOrder(){
		
		//add the order row
		$order_id = mysql_insert(" INSERT INTO dzpro_orders ( dzpro_cart_id, dzpro_order_customer_name, dzpro_order_customer_email, dzpro_order_customer_address, dzpro_order_customer_city, dzpro_order_customer_state, dzpro_order_customer_zipcode, dzpro_visitor_id, dzpro_user_id, dzpro_order_date_added ) VALUES ( '" . mysql_real_escape_string((int)$this->cart['dzpro_cart_id']) . "', '" . mysql_real_escape_string(getUserName()) . "', '" . mysql_real_escape_string(getUserEmail()) . "', '" . mysql_real_escape_string(getUserData('address')) . "', '" . mysql_real_escape_string(getUserData('city')) . "', '" . mysql_real_escape_string(getUserData('state')) . "', '" . mysql_real_escape_string(getUserData('zip')) . "', '" . mysql_real_escape_string(getVisitorId()) . "', '" . mysql_real_escape_string(getUserId()) . "', NOW() ) ");
		
		//update cart completed
		self::updateCartCompleted($order_id);
		
		//clear cart totals sessions
		self::resetCartTotalSession();
		
		//add order shipments
		if(false !== $order_id){
			if(isset($this->cart_array) and have($this->cart_array)){	
				foreach($this->cart_array as $address_id => $cart_array){
					
					//lets insert shipping details
					$shipment_id = mysql_insert(" INSERT INTO dzpro_order_shipments ( dzpro_order_id, dzpro_order_shipment_type, dzpro_order_shipment_name, dzpro_order_shipment_company, dzpro_order_shipment_phone, dzpro_order_shipment_address, dzpro_order_shipment_address_2, dzpro_order_shipment_city, dzpro_order_shipment_state, dzpro_order_shipment_zip, dzpro_order_shipment_message, dzpro_order_shipment_method, dzpro_order_shipment_method_type, dzpro_order_shipment_method_type_name, dzpro_order_shipment_cost, dzpro_order_shipment_shipping_date, dzpro_order_shipment_delivery_date, dzpro_order_shipment_date_added ) VALUES ( '" . mysql_real_escape_string((int)$order_id) . "', '" . mysql_real_escape_string($cart_array['address']['dzpro_user_shipping_option_type']) . "', '" . mysql_real_escape_string($cart_array['address']['dzpro_user_shipping_option_name']) . "', '" . mysql_real_escape_string($cart_array['address']['dzpro_user_shipping_option_company']) . "', '" . mysql_real_escape_string($cart_array['address']['dzpro_user_shipping_option_phone']) . "', '" . mysql_real_escape_string($cart_array['address']['dzpro_user_shipping_option_address']) . "', '" . mysql_real_escape_string($cart_array['address']['dzpro_user_shipping_option_address_2']) . "', '" . mysql_real_escape_string($cart_array['address']['dzpro_user_shipping_option_city']) . "', '" . mysql_real_escape_string($cart_array['address']['dzpro_user_shipping_option_state']) . "', '" . mysql_real_escape_string($cart_array['address']['dzpro_user_shipping_option_zipcode']) . "', '" . mysql_real_escape_string($cart_array['shipping']['dzpro_cart_shipping_message']) . "', '" . mysql_real_escape_string($cart_array['shipping']['dzpro_cart_shipping_method']) . "', '" . mysql_real_escape_string($cart_array['shipping']['dzpro_cart_shipping_method_type']) . "', '" . mysql_real_escape_string($cart_array['shipping']['dzpro_cart_shipping_method_type_name']) . "', '" . mysql_real_escape_string($cart_array['shipping']['dzpro_cart_shipping_cost']) . "', '" . mysql_real_escape_string(date('Y-m-d', strtotime($cart_array['shipping']['dzpro_cart_shipping_date']))) . "', '" . mysql_real_escape_string(date('Y-m-d', strtotime($cart_array['shipping']['dzpro_cart_shipping_delivery_date']))) . "', NOW() ) ");
					
					//cart subtotal -- to calculate discounts
					$subtotal = 0;
					
					//now lets add the order items
					if(isset($cart_array['items']) and have($cart_array['items'])){
						foreach($cart_array['items'] as $item){
						
							//adjust quantity
							self::updateShopQuantity((int)$item['item']['dzpro_shop_item_id'], (int)$item['item']['dzpro_cart_item_quantity']);
						
							//adjust subtotal
							$subtotal += $item['item']['dzpro_cart_item_quantity'] * $item['item']['dzpro_shop_item_price'];
							
							//insert the item
							$item_id = mysql_insert(" INSERT INTO dzpro_order_items ( dzpro_order_id, dzpro_order_shipment_id, dzpro_order_item_pid, dzpro_shop_item_id, dzpro_order_item_name, dzpro_order_item_tax, dzpro_order_item_quantity, dzpro_order_item_price, dzpro_order_item_weight, dzpro_order_item_date_added ) VALUES ( '" . mysql_real_escape_string((int)$order_id) . "', '" . mysql_real_escape_string((int)$shipment_id) . "', '" . mysql_real_escape_string($item['item']['dzpro_shop_item_pid']) . "', '" . mysql_real_escape_string($item['item']['dzpro_shop_item_id']) . "', '" . mysql_real_escape_string($item['item']['dzpro_shop_item_name']) . "', '" . mysql_real_escape_string($item['item']['dzpro_shop_item_tax']) . "', '" . mysql_real_escape_string($item['item']['dzpro_cart_item_quantity']) . "', '" . mysql_real_escape_string($item['item']['dzpro_shop_item_price']) . "', '" . mysql_real_escape_string($item['item']['dzpro_shop_item_weight']) . "', NOW() ) ");
							
							//now lets insert item options
							if(isset($item['options']) and have($item['options'])){ 
								foreach($item['options'] as $option){
									
									$item_option_id = mysql_insert(" INSERT INTO dzpro_order_item_options ( dzpro_order_item_id, dzpro_shop_item_option_id, dzpro_order_item_option_name, dzpro_order_item_option_amount, dzpro_order_item_option_date_added ) VALUES ( '" . mysql_real_escape_string((int)$item_id) . "', '" . mysql_real_escape_string($option['dzpro_shop_item_option_id']) . "', '" . mysql_real_escape_string($option['dzpro_shop_item_option_name']) . "', '" . mysql_real_escape_string($option['dzpro_shop_item_option_amount']) . "', NOW() ) ");
								
								}
							}
							
						}
					}
					
					//build discount array for order totals
					$discount = getCouponDiscount($subtotal); if($discount > 0){ $discounts[$shipment_id] = $discount; }
					applyCoupons($subtotal, true, $order_id);
					
				}
			}
		}
		
		//add subtotal
		mysql_insert(" INSERT INTO dzpro_order_totals ( dzpro_order_id, dzpro_order_total_fee, dzpro_order_total_name, dzpro_order_total_value, dzpro_order_total_date_added ) VALUES ( '" . mysql_real_escape_string((int)$order_id) . "', 0, 'Subtotal', '" . mysql_real_escape_string(self::getSubtotalAmount()) . "', NOW() ) ");
		//add shipping
		mysql_insert(" INSERT INTO dzpro_order_totals ( dzpro_order_id, dzpro_order_total_fee, dzpro_order_total_name, dzpro_order_total_value, dzpro_order_total_date_added ) VALUES ( '" . mysql_real_escape_string((int)$order_id) . "', 0, 'Shipping', '" . mysql_real_escape_string(self::getTotalShipping()) . "', NOW() ) ");
		//add total
		mysql_insert(" INSERT INTO dzpro_order_totals ( dzpro_order_id, dzpro_order_total_fee, dzpro_order_total_name, dzpro_order_total_value, dzpro_order_total_date_added ) VALUES ( '" . mysql_real_escape_string((int)$order_id) . "', 0, 'Total', '" . mysql_real_escape_string(self::getTotalAmount()) . "', NOW() ) ");			
		
		//add payment method
		mysql_insert(" INSERT INTO dzpro_order_payments ( dzpro_order_id, dzpro_payment_method_id, dzpro_order_payment_ref, dzpro_order_payment_amount, dzpro_order_payment_date_added ) VALUES ( '" . mysql_real_escape_string((int)$order_id) . "', '" . mysql_real_escape_string($this->payment_method) . "', '" . mysql_real_escape_string($this->transaction_id) . "', '" . mysql_real_escape_string(self::getTotalAmount()) . "', NOW() ) ");
		
		//add order status
		$first_order_status = self::getDefaultOrderStatus();
		mysql_insert(" INSERT INTO dzpro_order_status_history ( dzpro_order_id, dzpro_order_status_id, dzpro_order_status_history_message, dzpro_order_status_history_notified, dzpro_order_status_history_date_added ) VALUES ( '" . mysql_real_escape_string((int)$order_id) . "', '" . mysql_real_escape_string($first_order_status['dzpro_order_status_id']) . "', '" . mysql_real_escape_string('Your order has been placed') . "', 1, NOW() ) ");

		//discounts - from discounts
		if(isset($discounts) and have($discounts)){
			foreach($discounts as $ship_id => $dvalue){
				mysql_insert(" INSERT INTO dzpro_order_totals ( dzpro_order_id, dzpro_order_total_fee, dzpro_order_total_discount, dzpro_order_total_name, dzpro_order_total_value, dzpro_order_total_date_added ) VALUES ( '" . mysql_real_escape_string((int)$order_id) . "', 0, 1, 'Discount On Shipment " . (int)$ship_id . "', '" . mysql_real_escape_string($dvalue) . "', NOW() ) ");
			}
		}		
		
		//send order email to customer (add to outbox)
		self::sendOrderEmail($order_id);
		
		//clear coupons - since we'll need to verify the egilibility again for the next usage
		clearCoupons();
		
	}

	/***************************************************************************/
	/****************** SEND ORDER EMAIL SHOP **********************************/
	/***************************************************************************/
	protected function sendOrderEmail($order_id = null){
		if(!have($order_id)){ return false; }
		if(!have($this->cart_array)){ return false; }
		$email_subject = 'Receipt for order #' . (int)$order_id;
		$email_body = '
		<div style="background-color: #eedbb0; padding: 20px 0; text-align: center; line-height: 150%;">
			<div style="padding: 20px; font-family: Verdana; font-size: 12px; width: 560px; margin: 0 auto; text-align: left;">
				<table cellspacing="0" cellpadding="0" style="width: 100%;">
					<tbody>
						<tr>
							<td style="vertical-align: middle; padding: 0 30px 0 0;">
								<a href="http://' . HOST_NAME . '" title="' . SITE_NAME . '"><img src="http://' . HOST_NAME . '/assets/layout/cheesemart-logo.png" alt="' . SITE_NAME . '" /></a>
							</td>
							<td style="vertical-align: middle">
								<p><strong>Hi ' . getUserName() . ',</strong></p>
								<p>Thank you for your order (#' . (int)$order_id . ')! Upon shipping we will send you an additional email with the tracking number. If you want to see your order on our website <a href="http://' . HOST_NAME . '/account/order-' . (int)$order_id . '/" title="My account">click here</a>.</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div style="padding: 20px; font-family: Verdana; font-size: 12px; width: 560px; background-color: white; border: 1px solid #412100; margin: 0 auto; text-align: left;">
				<table cellspacing="0" cellpadding="0">
					<tbody>
						<tr>
							<td style="vertical-align: top; padding: 5px;">
								<p><strong>Packages:</strong> ' . self::getTotalPackages() . '<br />
								We are shipping ' . self::getTotalPackages() . ' package' . ((self::getTotalPackages()) > 1 ? 's' : null) . ' for you.</p>
							</td>
							<td style="vertical-align: top; padding: 5px;">
								<p><strong>Products:</strong> ' . self::getProductCount() . '<br />
								In total you are shipping ' . self::getProductCount() . ' items.</p>
							</td>
							<td style="vertical-align: top; padding: 5px;">
								<p><strong>Subtotal:</strong> $' . number_format(self::getSubtotalAmount(), 2) . '<br />
								The total for all items in ' . ((self::getTotalPackages()) > 1 ? 'all packages' : 'this package') . ' is $' . number_format(self::getSubtotalAmount(), 2) . '.</p>
							</td>
							<td style="vertical-align: top; padding: 5px;">
								<p><strong>Shipping:</strong> $' . number_format(self::getTotalShipping(), 2) . '<br />
								The ' . ((self::getTotalPackages() > 1) ? 'total' : null) . ' shipping cost for ' . self::getTotalPackages() . ' package' . ((self::getTotalPackages() > 1) ? 's' : null) . ' is $' . number_format(self::getTotalShipping(), 2) . '.</p>
							</td>							
							<td style="vertical-align: top; padding: 5px;">
								<p><strong>Total:</strong> $' . number_format(self::getTotalAmount(), 2) . '<br />
								The total amount paid is $' . number_format(self::getTotalAmount(), 2) . '.</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div style="height: 20px;"><!-- spacer --></div>			
			<div style="padding: 20px; font-family: Verdana; font-size: 12px; width: 560px; background-color: white; border: 1px solid #412100; margin: 0 auto; text-align: left;">
				<h2>Payment Details</h2>
				<p>
					<strong>Payment Method:</strong> ' . preg_replace(array('/^.*authorize.*$/i', '/^.*paypal.*$/i', '/^.*amazon.*$/i'), array('creditcard', 'paypal', 'amazon'), $this->payment_method) . '<br />
					<strong>Transaction Id:</strong> ' . $this->transaction_id . '<br />
					<strong>Date: </strong> ' . date('Y-m-d H:i:s') . '<br />
					<strong>Amount: </strong> $' . number_format(self::getTotalAmount(), 2) . '
				</p>
			</div>
			<div style="height: 20px;"><!-- spacer --></div>
		';
		$shipment_count = 1; foreach($this->cart_array as $shipment){
			$email_body .= '
			<div style="padding: 20px; font-family: Verdana; font-size: 12px; width: 560px; background-color: white; border: 1px solid #412100; margin: 0 auto; text-align: left;">
				<h1 style="margin: 0; padding: 0 0 10px;">Shipment #' . (int)$shipment_count . '</h1>
				<hr />
				<table cellspacing="0" cellpadding="0" style="width: 100%;">
					<tbody>
						<tr>
							<td style="width: 50%; vertical-align: top;">
								<h2>Shipping Address</h2>
			';
			if(have($shipment['address']['dzpro_user_shipping_option_company'])){	
				$email_body .= '
								' . $shipment['address']['dzpro_user_shipping_option_company'] . '<br />Attn: 
				';
			}
			$email_body .= '
								' . $shipment['address']['dzpro_user_shipping_option_name'] . ' (' . $shipment['address']['dzpro_user_shipping_option_phone'] . ')<br />
								' . $shipment['address']['dzpro_user_shipping_option_address'] . '<br />
								' . $shipment['address']['dzpro_user_shipping_option_city'] . ', ' . $shipment['address']['dzpro_user_shipping_option_state'] . ' ' . $shipment['address']['dzpro_user_shipping_option_zipcode'] . '
								
							</td>
							<td style="width: 50%; vertical-align: top;">
								<h2>About this shipment</h2>
								<strong>Method:</strong> ' . $shipment['address']['dzpro_cart_shipping_method_type_name'] . '<br />
								<strong>Cost:</strong> $' . number_format($shipment['address']['dzpro_cart_shipping_cost'], 2) . '<br />
								<strong>Estimated Delivery Date:</strong><br /> ' . date('l, F jS Y', strtotime($shipment['address']['dzpro_cart_shipping_delivery_date'])) . '
							</td>
						</tr>
					</tbody>
				</table>
				<div style="height: 10px;"><!-- spacer --></div>
				<hr />			
				<h2>Contents</h2>
				<table cellspacing="0" cellpadding="0" style="width: 100%;">
					<thead>
						<th style="padding: 5px 0px;">Quantity</th>
						<th style="padding: 5px 0px;">Item</th>
						<th style="padding: 5px 0px;">Price</th>
					</thead>
					<tbody>
			';
			if(have($shipment['items'])){
				foreach($shipment['items'] as $item){
					$email_body .= '
						<tr>
							<td style="width: 50px; padding: 5px 0px;">' . prepareStringHtml($item['item']['dzpro_cart_item_quantity']) . '</td>
							<td style="padding: 5px 0px;">' . prepareStringHtml($item['item']['dzpro_shop_item_name']);
					if(isset($item['options']) and have($item['options'])){
						foreach($item['options'] as $option){
							$email_body .= '
								<br />+' . $option['dzpro_shop_item_option_name'] . ' ($' . number_format($option['dzpro_shop_item_option_amount'], 2) . ')
							';
						}
					}
					$email_body .= '							
							</td>
							<td style="width: 70px; padding: 5px 0px;">';
					$item_price = $item['item']['dzpro_shop_item_price']; if(isset($item['options']) and have($item['options'])){ foreach($item['options'] as $option){ $item_price += $option['dzpro_shop_item_option_amount']; } }
					$email_body .= number_format($item['item']['dzpro_cart_item_quantity'] * $item_price, 2) . '							
							</td>
						</tr>
					';
				}
			}
			$email_body .= '
					</tbody>
				</table>
			';
			if(isset($shipment['shipping']['dzpro_cart_shipping_message']) and have($shipment['shipping']['dzpro_cart_shipping_message'])){
				$email_body .= '
				<div style="height: 10px;"><!-- spacer --></div>
				<hr />			
				<h2>Message</h2>
				<div style="font-size: 42px; line-height: 150%; padding: 50px 50px; border: 4px dashed #ccc; text-align: center;">
					' . prepareStringHtmlFlat($shipment['shipping']['dzpro_cart_shipping_message']) . '
				</div>
				';
			}
			$email_body .= '	
			</div>
			';
			if($shipment_count != sizeof($this->cart_array)){
				$email_body .= '
			<div style="height: 20px;"><!-- spacer --></div>
				';
			}
		$shipment_count++; }
		$email_body .= '
			<div style="padding: 20px; font-family: Verdana; font-size: 12px; width: 560px; margin: 0 auto; text-align: left;">
				' . getStaticContent('order_email_content') . '
			</div>
		</div>
		';
		if(addEmailToOutbox(getUserName(), getUserEmail(), $email_subject, $email_body)){ return true; }
		return false;
	}

	/***************************************************************************/
	/****************** UPDATE SHOP QUANTITY ***********************************/
	/***************************************************************************/	
	protected function updateShopQuantity($shop_item_id = null, $quantity = null){
		if(!have($shop_item_id) or !have($quantity)){ return false; }
		return mysql_update(" UPDATE dzpro_shop_items SET dzpro_shop_item_quantity = dzpro_shop_item_quantity - " . (int)$quantity . " WHERE dzpro_shop_item_id = " . (int)$shop_item_id . " ");
	}

	/***************************************************************************/
	/****************** UPDATE CART COMPLETED **********************************/
	/***************************************************************************/	
	protected function updateCartCompleted($order_id){
		return mysql_update(" UPDATE dzpro_carts SET dzpro_order_id = " . (int)$order_id . " WHERE dzpro_cart_id = '" . mysql_real_escape_string($this->cart['dzpro_cart_id']) . "' ");
	}

	/***************************************************************************/
	/****************** RESET CART TOTALS SESSION ******************************/
	/***************************************************************************/	
	protected function resetCartTotalSession(){
		if(isset($_SESSION['cart']) and !empty($_SESSION['cart'])){ foreach($_SESSION['cart'] as $key => $value){ $_SESSION['cart'][$key] = null; } } 
		return true;
	}

	/***************************************************************************/
	/****************** GET DEFAULT ORDER STATUS *******************************/
	/***************************************************************************/
	protected function getDefaultOrderStatus(){
		return mysql_query_get_row(" SELECT * FROM dzpro_order_status ORDER BY dzpro_order_status_orderfield LIMIT 1 ");
	}

	/***************************************************************************/
	/****************** SELECT CART ********************************************/
	/***************************************************************************/
	protected function createOrSelectCart(){
		if(false !== self::selectCart()){ self::updateCart(); }else{ self::createCart(); self::selectCart(); }
	}

	/***************************************************************************/
	/****************** UPDATE CART ********************************************/
	/***************************************************************************/
	protected function updateCart(){
		return mysql_update(" UPDATE dzpro_carts SET dzpro_cart_hits = dzpro_cart_hits + 1, dzpro_cart_last_modified = NOW(), dzpro_user_id = '" . mysql_real_escape_string(getUserId()) . "',	dzpro_visitor_id = '" . mysql_real_escape_string(getVisitorId()) . "', dzpro_cart_name = '" . mysql_real_escape_string(self::getCartName()) . "', dzpro_cart_description = '" . mysql_real_escape_string(self::getCartDescription()) . "' WHERE dzpro_cart_id = " . (int)$this->cart['dzpro_cart_id']);
	}

	/***************************************************************************/
	/****************** UPDATE CART AGREED TO **********************************/
	/***************************************************************************/	
	protected function setCartAgreed($value = 0){
		$this->cart['dzpro_cart_agreed'] = $value;
		return mysql_update(" UPDATE dzpro_carts SET dzpro_cart_agreed = " . (int)$value . " WHERE dzpro_cart_id = " . (int)$this->cart['dzpro_cart_id'] . " "); 
	}

	/***************************************************************************/
	/****************** CREATE CART ********************************************/
	/***************************************************************************/
	protected function createCart(){
		return mysql_insert(" INSERT INTO dzpro_carts ( dzpro_visitor_id, dzpro_user_id, dzpro_cart_name, dzpro_cart_description, dzpro_cart_date_added, dzpro_cart_last_modified, dzpro_cart_hits ) VALUES ( '" . mysql_real_escape_string(getVisitorId()) . "', '" . mysql_real_escape_string(getUserId()) . "', '" . mysql_real_escape_string(self::getCartName()) . "', '" . mysql_real_escape_string(self::getCartDescription()) . "', NOW(), NOW(), 1 )");
	}

	/***************************************************************************/
	/****************** SELECT CART ********************************************/
	/***************************************************************************/
	protected function selectCart(){
		$result = @mysql_query(" SELECT * FROM dzpro_carts WHERE ( ( dzpro_visitor_id = '" . getVisitorId() . "' AND dzpro_visitor_id IS NOT NULL ) ) AND dzpro_cart_last_modified > '" . date('Y-m-d H:i:s', strtotime(CART_EXPIRATION_TIME)) . "' AND dzpro_cart_removed = 0 AND dzpro_order_id = 0 ORDER BY dzpro_cart_last_modified DESC LIMIT 1") or handleError(1, mysql_error()); if(mysql_num_rows($result)){ while($row = mysql_fetch_assoc($result)){ $this->cart = $row; } mysql_free_result($result); return $this->cart['dzpro_cart_id']; }
		return false;
	}

	/***************************************************************************/
	/****************** KILL CART **********************************************/
	/***************************************************************************/
	protected function killCart(){
		if(mysql_update(" UPDATE dzpro_carts SET dzpro_cart_removed = 1 WHERE ( dzpro_visitor_id = '" . getVisitorId() . "' AND dzpro_visitor_id IS NOT NULL ) OR ( dzpro_user_id = '" . mysql_real_escape_string(getUserId()) . "' AND dzpro_user_id IS NOT NULL ) ")){ $_SESSION['cart'] = array(); header('Location: ' . str_ireplace('refresh_cart=true', '', $_SERVER['REQUEST_URI'])); exit(0); }
		return false;
	}	
		
	/***************************************************************************/
	/****************** UPDATE CART LAST MODIFIED ******************************/
	/***************************************************************************/	
	protected function updateCartModified(){
		if(!have($this->cart['dzpro_cart_id'])){ return false; }
		return mysql_update(" UPDATE dzpro_carts SET dzpro_cart_last_modified = NOW() WHERE dzpro_cart_id = " . (int)$this->cart['dzpro_cart_id']); 
	}

	/***************************************************************************/
	/****************** GET CART NAME ******************************************/
	/***************************************************************************/
	protected function getCartName(){
		$return = date('Y-m-d H:i:s');
		if(false !== ($name = getUserName())){ $return .= ' ' . $name; }
		return $return;
	}

	/***************************************************************************/
	/****************** GET CART DESCRIPTION ***********************************/
	/***************************************************************************/	
	protected function getCartDescription(){
		$return = (int)$this->cart_quantity . ' items in cart.' . "\n" . 'Cart created ' . date('Y-m-d H:i:s') . "\n"; 
		return $return;
	}

	/***************************************************************************/
	/****************** CHECK FOR ALL OPTIONS **********************************/
	/***************************************************************************/	
	protected function matchAllOptions($item_id = null, $options = array()){
		$existing = array(); $existing_prep = mysql_query_on_key(" SELECT dzpro_shop_item_option_id FROM dzpro_cart_item_options LEFT JOIN dzpro_cart_items USING ( dzpro_cart_item_id ) WHERE dzpro_cart_item_id = " . (int)$item_id . " AND dzpro_cart_id = " . (int)$this->cart['dzpro_cart_id'] . " ", 'dzpro_shop_item_option_id'); if(have($existing_prep)){ foreach($existing_prep as $existing_prep_value){ if(!in_array($existing_prep_value['dzpro_shop_item_option_id'], $existing)){ $existing[] = $existing_prep_value['dzpro_shop_item_option_id']; } } }
		return compareArrayValues($options, $existing);
	}

	/***************************************************************************/
	/****************** SELECT CART ITEM ***************************************/
	/***************************************************************************/	
	protected function selectCartItem($item_id = null, $address_id = null, $options = array()){
		if(!have($item_id)){ return false; } if(!have($this->cart['dzpro_cart_id'])){ return false; }
		$cart_item = array(); $query = " SELECT DISTINCT dzpro_cart_item_id FROM dzpro_cart_items "; if(have($options)){ $query .= " LEFT JOIN dzpro_cart_item_options USING ( dzpro_cart_item_id ) "; } $query .= " WHERE dzpro_cart_id = " . (int)$this->cart['dzpro_cart_id'] . " AND dzpro_shop_item_id = " . (int)$item_id . " "; if(have($address_id)){ $query .= " AND dzpro_user_shipping_option_id = " . (int)$address_id . " "; } if(have($options)){ $query .= " AND dzpro_shop_item_option_id IN ( '" . implode("','", $options) . "') "; } $result = @mysql_query($query) or handleError(1, 'sql:' . $query . ' error:' . mysql_error());  if(mysql_num_rows($result)){ while($row = mysql_fetch_assoc($result)){ if(self::matchAllOptions($row['dzpro_cart_item_id'], $options)){ return $row['dzpro_cart_item_id']; } } }
		return false;
	}

	/***************************************************************************/
	/****************** DOES OPTION EXIST **************************************/
	/***************************************************************************/	
	protected function doesOptionExist($option_id = null){
		if(!have($option_id)){ return false; }
		return mysql_query_got_rows(" SELECT * FROM dzpro_shop_item_options WHERE dzpro_shop_item_option_id = " . (int)$option_id . " ");
	}

	/***************************************************************************/
	/****************** DOES ITEM EXIST ****************************************/
	/***************************************************************************/	
	protected function doesItemExist($item_id = null){
		if(!have($item_id)){ return false; }
		return mysql_query_got_rows(" SELECT * FROM dzpro_shop_items WHERE dzpro_shop_item_id = " . (int)$item_id . " ");
	}

	/***************************************************************************/
	/****************** INSERT CART ITEM ***************************************/
	/***************************************************************************/
	protected function insertCartItem($item_id = null, $address_id = null, $quantity = 1, $options = array()){
		if(!have($item_id)){ return false; } if(!self::doesItemExist($item_id)){ return false; }
		@mysql_query(" INSERT INTO dzpro_cart_items ( dzpro_cart_id, dzpro_shop_item_id, dzpro_user_shipping_option_id, dzpro_cart_item_quantity, dzpro_cart_item_date_added ) VALUES ( " . (int)$this->cart['dzpro_cart_id'] . ", " . (int)$item_id . ", " . (int)$address_id . ", " . (int)$quantity . ", NOW() ) ") or handleError(1, mysql_error()); if(false !== ($cart_item_id = mysql_insert_id()) and have($options)){ foreach($options as $option_id){ if(!self::doesOptionExist($option_id)){ continue; } @mysql_query(" INSERT INTO dzpro_cart_item_options ( dzpro_shop_item_option_id, dzpro_cart_item_id, dzpro_cart_item_option_date_added ) VALUES ( " . (int)$option_id . ", " . (int)$cart_item_id . ", NOW() ) ") or handleError(1, mysql_error()); } } return true;
	}

	/***************************************************************************/
	/****************** UPDATE CART ITEM ***************************************/
	/***************************************************************************/
	protected function updateCartItem($item_id = null, $quantity = 1){
		if(!have($item_id)){ return false; }
		@mysql_query(" UPDATE dzpro_cart_items SET dzpro_cart_item_quantity = " . (int)$quantity . " WHERE dzpro_cart_item_id = " . $item_id . " ");
	}
	
	/***************************************************************************/
	/****************** ADD ITEM TO CART ***************************************/
	/***************************************************************************/	
	public function addItemToCart($item_id = null){
		if(!have($item_id) or !is_numeric($item_id)){ return false; } $quantity = (isset($_POST['quantity']) and !empty($_POST['quantity'])) ? (int)$_POST['quantity'] : 1; 
		$address = (isset($_POST['address']) and !empty($_POST['address'])) ? (int)$_POST['address'] : null; self::resetShippingDetails($address);
		$options = array(); foreach($_POST as $post_key => $post_value){ if(matchPrepend('option_', $post_key) and !in_array($post_value, $options) and is_numeric($post_value)){ $options[] = (int)$post_value; } }
		if(false !== ($cart_item_id = self::selectCartItem($item_id, $address, $options))){ self::updateCartItem($cart_item_id, $quantity); }else{ self::insertCartItem($item_id, $address, $quantity, $options); }
	}
	
	/***************************************************************************/
	/****************** RECORD ITEM HIT ****************************************/
	/***************************************************************************/
	public function recordItemHit($item_id = null){
		if(!have($item_id) or !is_numeric($item_id)){ return false; } $quantity = (isset($_POST['quantity']) and !empty($_POST['quantity'])) ? (int)$_POST['quantity'] : 1; 
		return mysql_update(" UPDATE dzpro_shop_items SET dzpro_shop_item_hits = dzpro_shop_item_hits + " . (int)$quantity . " WHERE dzpro_shop_item_id = " . (int)$item_id . " ");
	}

	/***************************************************************************/
	/****************** UPDATE CART ITEM ***************************************/
	/***************************************************************************/	
	protected function updateItemToCart(){
		if(!isset($_POST['cart_item_id']) or !is_numeric($_POST['cart_item_id'])){ return false; }
		if(false !== self::checkCartItemQuantity()){
			$cart_item_row = mysql_query_get_row(" SELECT * FROM dzpro_cart_items WHERE dzpro_cart_item_id = " . (int)$_POST['cart_item_id'] . " "); if(isset($cart_item_row['dzpro_user_shipping_option_id'])){ self::resetShippingDetails($cart_item_row['dzpro_user_shipping_option_id']); } @mysql_query(" UPDATE dzpro_cart_items SET dzpro_cart_item_quantity = " . (int)$_POST['quantity'] . " WHERE dzpro_cart_item_id = " . (int)$_POST['cart_item_id']);
			if(mysql_affected_rows() > 0){ if((int)$_POST['quantity'] == 0){ self::removeCartItem($_POST['cart_item_id']); } return true; }
		}
		return false;
	}

	/***************************************************************************/
	/****************** REMOVE CART ITEM ***************************************/
	/***************************************************************************/		
	protected function removeCartItem($cart_item_id = null){
		if(!have($cart_item_id)){ return false; }
		@mysql_query(" DELETE FROM dzpro_cart_items WHERE dzpro_cart_item_id = " . (int)$cart_item_id . " ") or handleError(1, mysql_error());
		if(mysql_affected_rows()){ return true; }
		return false;
	}
	
	/***************************************************************************/
	/****************** CHECK CART ITEM QUANTITY *******************************/
	/***************************************************************************/	
	protected function checkCartItemQuantity(){
		$the_cart_item = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : false;
		$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : false;
		$result = @mysql_query(" SELECT * FROM dzpro_cart_items LEFT JOIN dzpro_shop_items USING ( dzpro_shop_item_id ) WHERE dzpro_shop_item_quantity < " . (int)$_POST['quantity'] . " AND dzpro_cart_item_id = " . (int)$the_cart_item . " ") or die(mysql_error()); if(mysql_num_rows($result)){ while($row = mysql_fetch_assoc($result)){ $this->cart_message = 'There are only ' . (int)$row['dzpro_shop_item_quantity'] . ' ' . compressString($row['dzpro_shop_item_name'], 60) . ' items available.'; } mysql_free_result($result); return false; }
		return true;
	}

	/***************************************************************************/
	/****************** UPDATE ALL ADDRESSES ***********************************/
	/***************************************************************************/	
	protected function updateAllAddresses($old_address_id = null, $new_address_id = null){
		@mysql_query(" UPDATE dzpro_cart_items SET dzpro_user_shipping_option_id = '" . mysql_real_escape_string((int)$new_address_id) . "' WHERE dzpro_user_shipping_option_id = '" . mysql_real_escape_string((int)$old_address_id) . "' AND dzpro_cart_id = '" . mysql_real_escape_string((int)$this->cart['dzpro_cart_id']) . "' ") or handleError(1, mysql_error()); if(mysql_affected_rows()){ self::resetShippingDetails($old_address_id); self::resetShippingDetails($new_address_id); return true; }
		return false;
	}

	/***************************************************************************/
	/****************** UPDATE CART ITEM ADDRESS *******************************/
	/***************************************************************************/	
	protected function updateCartItemAddress($cart_item_id = null, $new_address_id = null){
		$item = mysql_query_get_row(" SELECT * FROM dzpro_cart_items WHERE dzpro_cart_item_id = '" . mysql_real_escape_string((int)$cart_item_id) . "' AND dzpro_cart_id = '" . mysql_real_escape_string((int)$this->cart['dzpro_cart_id']) . "' "); if(have($item['dzpro_user_shipping_option_id'])){ self::resetShippingDetails($item['dzpro_user_shipping_option_id']); }
		@mysql_query(" UPDATE dzpro_cart_items SET dzpro_user_shipping_option_id = '" . mysql_real_escape_string((int)$new_address_id) . "' WHERE dzpro_cart_item_id = '" . mysql_real_escape_string((int)$cart_item_id) . "' AND dzpro_cart_id = '" . mysql_real_escape_string((int)$this->cart['dzpro_cart_id']) . "' ") or handleError(1, mysql_error()); if(mysql_affected_rows()){ self::resetShippingDetails($new_address_id); return true; }
		return false;
	}

	/***************************************************************************/
	/****************** UPDATE CART ITEM OPTIONS *******************************/
	/***************************************************************************/	
	protected function setCartItemOptions($options_string = null){
		
		//obviously we'll need this
		if(!have($options_string)){ return false; }
		
		//remake the array
		$options_array = json_decode(stripslashes($options_string)); $options_array = objectToArray($options_array); if(!is_array($options_array)){ return false; }

		//find the item id
		$the_cart_item_id = null;

		//simple option key array
		$option_keys = array();

		//add the options
		foreach($options_array as $option_key => $cart_item_id){
			
			//set cart item id
			$the_cart_item_id = $cart_item_id;
			
			//build options keys
			$option_keys[] = substr($option_key, 7);
			
		}
		
		//remove old options
		@mysql_query(" DELETE FROM dzpro_cart_item_options WHERE dzpro_cart_item_id = '" . mysql_real_escape_string((int)$the_cart_item_id) . "' ") or handleError(1, mysql_error());
		
		//add the options
		if(have($option_keys)){
			
			//add the options
			foreach($option_keys as $option_id){
				@mysql_query(" INSERT INTO dzpro_cart_item_options (dzpro_cart_item_id, dzpro_shop_item_option_id, dzpro_cart_item_option_date_added) VALUES ('" . mysql_real_escape_string((int)$the_cart_item_id) . "', '" . mysql_real_escape_string((int)$option_id) . "', NOW()) ") or handleError(1, mysql_error());
			}
		}
		
		return true;

	}

	/***************************************************************************/
	/****************** IS OTIONS ASSOCIATED WITH ITEM *************************/
	/***************************************************************************/		
	protected function isOptionAssociatedWithItem($item_id = null, $option_id = null){
		if(!have($item_id)){ return false; } if(!have($option_id)){ return false; } if(!have($this->cart_array)){ return false; }
		return mysql_query_got_rows(" SELECT * FROM dzpro_cart_item_options WHERE dzpro_cart_item_id = '" . mysql_real_escape_string((int)$item_id) . "' AND dzpro_shop_item_option_id = '" . mysql_real_escape_string((int)$option_id) . "' ");
	}
	
	/***************************************************************************/
	/****************** UPDATE CART ITEM ***************************************/
	/***************************************************************************/	
	protected function buildCartArray(){
		if(!have($this->cart['dzpro_cart_id'])){ return false; }
		$records = mysql_query_flat(" 
			SELECT 
				*, 
				IF(dzpro_user_shipping_options.dzpro_user_shipping_option_id = NULL,0,dzpro_cart_items.dzpro_user_shipping_option_id) AS shipping_option_id, 
				IF(dzpro_user_shipping_options.dzpro_user_shipping_option_id = 0 OR dzpro_user_shipping_options.dzpro_user_shipping_option_id IS NULL,100000000,dzpro_user_shipping_options.dzpro_user_shipping_option_id) AS shipping_option_id_temp_orderfield 
			FROM 
				dzpro_carts 
			LEFT JOIN 
				dzpro_cart_items USING ( dzpro_cart_id ) 
			LEFT JOIN 
				dzpro_cart_shipping ON dzpro_cart_items.dzpro_user_shipping_option_id = dzpro_cart_shipping.dzpro_user_shipping_option_id AND dzpro_cart_shipping.dzpro_cart_id = dzpro_cart_items.dzpro_cart_id
			LEFT JOIN 
				dzpro_user_shipping_options ON dzpro_user_shipping_options.dzpro_user_shipping_option_id = dzpro_cart_items.dzpro_user_shipping_option_id
			LEFT JOIN 
				dzpro_cart_item_options USING ( dzpro_cart_item_id ) 
			LEFT JOIN 
				dzpro_shop_items USING ( dzpro_shop_item_id )
			LEFT JOIN
				dzpro_shop_item_to_option USING ( dzpro_shop_item_id )
			LEFT JOIN
				dzpro_shop_item_options ON dzpro_shop_item_options.dzpro_shop_item_option_id = dzpro_shop_item_to_option.dzpro_shop_item_option_id			
			WHERE 
				dzpro_cart_items.dzpro_cart_id = " . (int)$this->cart['dzpro_cart_id'] . " 
			GROUP BY
				dzpro_shop_item_options.dzpro_shop_item_option_id,
				dzpro_cart_item_option_id,
				dzpro_cart_item_id, 
				dzpro_cart_items.dzpro_user_shipping_option_id 
			ORDER BY 
				shipping_option_id_temp_orderfield DESC, 
				dzpro_cart_item_date_added DESC 
		");
		if(have($records)){ 
		 	foreach($records as $row){ 
		 		$this->cart_array[$row['shipping_option_id']]['address'] = $row; 
		 		$this->cart_array[$row['shipping_option_id']]['cart'] = $row; 
				if(have($row['dzpro_cart_shipping_id'])){
			 		$this->cart_array[$row['shipping_option_id']]['shipping'] = $row;
				}
		 		if(have($row['dzpro_cart_item_id'])){ 
		 			$this->cart_array[$row['shipping_option_id']]['items'][$row['dzpro_cart_item_id']]['item'] = $row; 
		 		} 
		 		if(have($row['dzpro_cart_item_option_id'])){ 
		 			$this->cart_array[$row['shipping_option_id']]['items'][$row['dzpro_cart_item_id']]['options'] = mysql_query_on_key(" SELECT * FROM dzpro_cart_item_options LEFT JOIN dzpro_shop_item_options USING ( dzpro_shop_item_option_id ) WHERE dzpro_shop_item_option_id > 0 AND dzpro_cart_item_id = '" . mysql_real_escape_string((int)$row['dzpro_cart_item_id']) . "' ", 'dzpro_cart_item_option_id'); 
		 		} 
		 		if(have($row['dzpro_shop_item_option_id'])){ 
		 			$this->cart_array[$row['shipping_option_id']]['items'][$row['dzpro_cart_item_id']]['shop_options'][$row['dzpro_shop_item_option_id']] = $row; 
		 		} 
			} 
		}
	}

	/***************************************************************************/
	/****************** GET SHIPPING WEIGHT ************************************/
	/***************************************************************************/	
	protected function getShipmentWeight($shipping_option_id = null){
		if(!have($shipping_option_id)){ handleError(1, 'Did not get shipping option: getShipmentWeight'); return false; }
		if(!have($this->cart_array[$shipping_option_id]['items'])){ handleError(1, 'Did not find any items: ' . $shipping_option_id); return false; }
		$weight = (false !== getStaticContent('shipping_base_weight')) ? getStaticContent('shipping_base_weight') : 0; foreach($this->cart_array[$shipping_option_id]['items'] as $item){ $weight += ($item['item']['dzpro_cart_item_quantity'] * $item['item']['dzpro_shop_item_weight']); } return ceil($weight);
	}

	/***************************************************************************/
	/****************** IS THIS A RESIDENTIAL ADDRESS? *************************/
	/***************************************************************************/	
	protected function isThisResidential($shipping_option_id = null){
		if(!have($shipping_option_id)){ handleError(1, 'Did not get shipping option: getShipmentWeight'); return false; }
		return (isset($this->cart_array[$shipping_option_id]['address']['dzpro_user_shipping_option_company']) and empty($this->cart_array[$shipping_option_id]['address']['dzpro_user_shipping_option_company']));
	}

	/***************************************************************************/
	/****************** GET SHIPPING OPTIONS ***********************************/
	/***************************************************************************/	
	protected function getShippingOptions($shipping_option_id = null){
		
		//get what we need
		if(!have($shipping_option_id)){ handleError(1, 'Did not get shipping option: getShippingOptions'); return false; }
		if(!have($this->cart_array[$shipping_option_id]['address'])){ handleError(1, 'Did not find shipping option: ' . $shipping_option_id); return false; }
		$handling_fee = getStaticContent('Shipping_handling_fee'); if(!have($handling_fee)){ $handling_fee = 1; }
		$zipcode = $this->cart_array[$shipping_option_id]['address']['dzpro_user_shipping_option_zipcode'];
		$shipment_weight = self::getShipmentWeight($shipping_option_id);
		$residential_address = self::isThisResidential($shipping_option_id);
		
		//set cache key
		$cache_key = md5($handling_fee . $zipcode . $shipment_weight . $residential_address);
		
		//set empty quote
		$shipping_quote = array();
		
		//get shipping option array
		if(!have($shipping_quote = getCache($cache_key))){
			$ShippingQuote = new ShippingQuote($this->db);
			$ShippingQuote->setShippingType('FedEx');
			$ShippingQuote->setShippingHandlingFee($handling_fee);
			$ShippingQuote->setShippingDetails($zipcode, $shipment_weight, $residential_address);
			$ShippingQuote->getShippingQuote();
			$shipping_quote = $ShippingQuote->convertShippingQuote(); //gets a more organized options array
			saveCache($cache_key, $shipping_quote, 600);
		}
		
		//the result
		return $shipping_quote;

	}

	/***************************************************************************/
	/****************** GET MAX TRANSIT TIME ***********************************/
	/***************************************************************************/
	protected function getMaxTransitTime($shipping_option_id = null){
		if(!have($shipping_option_id)){ handleError(1, 'Did not get shipping option: getMaxTransitTime'); return false; }
		if(!have($this->cart_array[$shipping_option_id]['items'])){ handleError(1, 'Did not find any items: ' . $shipping_option_id); return false; }
		$max_transit = getStaticContent('max_transit'); foreach($this->cart_array[$shipping_option_id]['items'] as $item){ if($item['item']['dzpro_shop_item_type'] != 'non-perishable' and $item['item']['dzpro_shop_item_transit'] < $max_transit){ $max_transit = $item['item']['dzpro_shop_item_transit']; } } return $max_transit;
	}

	/***************************************************************************/
	/****************** IS THIS A SHIPPING DAY *********************************/
	/***************************************************************************/	
	protected function isThisAShippingDay($timestamp = null){
		if(!have($timestamp)){ return false; }
		$shipping_day = mysql_query_flat(" SELECT * FROM dzpro_holidays WHERE '" . mysql_real_escape_string(date('Y-m-d H:i:s', $timestamp)) . "' BETWEEN dzpro_holiday_start_date AND dzpro_holiday_end_date "); if(have($shipping_day)){ foreach($shipping_day as $shipping_day_array){ if(isset($shipping_day_array['dzpro_holiday_shipping']) and $shipping_day_array['dzpro_holiday_shipping'] == 'no'){ return false; } } } 
		return true;
	}

	/***************************************************************************/
	/****************** GET SHIPPING OPTIONS ARRAY *****************************/
	/***************************************************************************/	
	public function getShippingOptionsArray($shipping_option_id = null){
		
		//check prerequisites
		if(!have($shipping_option_id)){ handleError(1, 'Did not find shipping option: ' . $shipping_option_id); return false; }
		
		//get shipping option array
		if(false === ($shipping_options_array = self::getShippingOptions($shipping_option_id))){ handleError(1, 'Could not load shipping options'); return false; }
		if(false === ($max_transit_time = self::getMaxTransitTime($shipping_option_id))){ handleError(1, 'Could not get max transit time'); return false; }
		if(false === ($shipping_date_options_reach = getStaticContent('shipping_date_reach'))){ handleError(1, 'Could not get shipping date reach: shipping_date_reach'); return false; }
				
		//first possible shipping day
		$first_shipping_day = (strtotime(date('Y-m-d') . ' ' . $shipping_date_options_reach) > date('U')) ? strtotime('today') : strtotime('tomorrow');
	
		//arrival date array
		$arrival_date_array = array();
		
		//step through delivery candidates
		for($i = $first_shipping_day + ONE_DAY; $i < strtotime(date('Y-m-d')) + ( $shipping_date_options_reach * ONE_DAY ); $i += ONE_DAY){
			
			//delivery possible ?
			$can_we_deliver = true;			
					
			//shipping date
			$shipping_date = null;
			
			//start counting back -- picking cheapest shipping option -- getting the package delivered on picked day
			foreach($shipping_options_array['FedEx']['price'] as $option_array){
				
				//if the transit time is longer than max transit - lets forget
				if($option_array['transit'] > $max_transit_time){ continue; }
				
				//not enough time to use this shipping option
				if($option_array['transit'] > ($i - $first_shipping_day) / ONE_DAY){ continue; }
				
				$pointer = $i;
				$incrementer = 0;
				$transit_incrementer = 0;
				while($pointer > $first_shipping_day and $incrementer < $max_transit_time){
					$pointer -= ONE_DAY;
					if(self::isThisAShippingDay($pointer) and isset($option_array['delivery_days'][date('N', $pointer)]) and $option_array['delivery_days'][date('N', $pointer)] !== false){ $transit_incrementer++; }
					if(
						$transit_incrementer == $option_array['transit'] and 
						!isset($arrival_date_array[$i]) and 
						isset($option_array['delivery_days'][date('N', $i)]) and 
						$option_array['delivery_days'][date('N', $i)] === true and 
						self::isThisAShippingDay($pointer) and 
						isset($option_array['delivery_days'][date('N', $pointer)]) and 
						$option_array['delivery_days'][date('N', $pointer)] === true
					){ 
						$arrival_date_array[$i]['shipping_method'] = $option_array; 
						$arrival_date_array[$i]['shipping_date'] = $pointer; 
						$arrival_date_array[$i]['shipping_date_string'] = date('l, F jS Y', $pointer);
						$arrival_date_array[$i]['arrival_date'] = $i;
						$arrival_date_array[$i]['arrival_date_string'] = date('l, F jS Y', $i);
						$arrival_date_array[$i]['method'] = 'FedEx';
					}
					$incrementer++;
				}
				
			}			
		}
		return $arrival_date_array;		
	}

	/***************************************************************************/
	/****************** GET PACKAGE COUNT **************************************/
	/***************************************************************************/
	protected function getTotalPackages(){
		$return = 0; if(isset($this->cart_array) and have($this->cart_array)){ foreach($this->cart_array as $address_id => $cart_array){ if(isset($cart_array['shipping']['dzpro_cart_shipping_cost'])){ $return++; } } }
		return $return;
	}
	
	/***************************************************************************/
	/****************** GET PRODUCT COUNT **************************************/
	/***************************************************************************/	
	protected function getProductCount(){
		$return = 0; if(isset($this->cart_array) and have($this->cart_array)){ foreach($this->cart_array as $address_id => $cart_array){ if(isset($cart_array['items']) and have($cart_array['items'])){ foreach($cart_array['items'] as $item){ $return += $item['item']['dzpro_cart_item_quantity']; } } } } return $return;
	}

	/***************************************************************************/
	/****************** GET TOTAL AMOUNT ***************************************/
	/***************************************************************************/	
	protected function getTotalAmount(){
		$return = self::getSubtotalAmount() + self::getTotalShipping();  
		return $return;	
	}

	/***************************************************************************/
	/****************** GET SUBTOTAL AMOUNT ************************************/
	/***************************************************************************/
	public function getShipmentSubtotal($for_address_id = null){
		if(!have($for_address_id)){ return false; }
		$return = 0;
		if(isset($this->cart_array) and have($this->cart_array)){ 
			foreach($this->cart_array as $address_id => $cart_array){ 
				if(isset($cart_array['items']) and have($cart_array['items']) and $for_address_id == $address_id){ 
					foreach($cart_array['items'] as $item){ 
						$return +=  $item['item']['dzpro_cart_item_quantity'] * $item['item']['dzpro_shop_item_price']; 
						if(isset($item['options']) and have($item['options'])){ 
							foreach($item['options'] as $option){ 
								$return += $item['item']['dzpro_cart_item_quantity'] * $option['dzpro_shop_item_option_amount']; 
							} 
						} 
					} 
				} 
			} 
		}
		return $return;
	}

	/***************************************************************************/
	/****************** GET SUBTOTAL AMOUNT ************************************/
	/***************************************************************************/	
	protected function getSubtotalAmount(){
		$return = 0; 
		if(isset($this->cart_array) and have($this->cart_array)){ 
			foreach($this->cart_array as $address_id => $cart_array){ 
				if(isset($cart_array['items']) and have($cart_array['items'])){ 
					foreach($cart_array['items'] as $item){ 
						$return +=  $item['item']['dzpro_cart_item_quantity'] * $item['item']['dzpro_shop_item_price']; 
						if(isset($item['options']) and have($item['options'])){ 
							foreach($item['options'] as $option){ 
								$return += $item['item']['dzpro_cart_item_quantity'] * $option['dzpro_shop_item_option_amount']; 
							} 
						} 
					}
					$return = $return - getCouponDiscount($return);
				} 
			} 
		} 
		return $return;	
	}

	/***************************************************************************/
	/****************** GET TOTAL SHIPPING *************************************/
	/***************************************************************************/	
	protected function getTotalShipping(){
		$return = 0; 
		if(isset($this->cart_array) and have($this->cart_array)){ 
			foreach($this->cart_array as $address_id => $cart_array){ 
				if(isset($cart_array['shipping']['dzpro_cart_shipping_cost'])){ 
					$return += $cart_array['shipping']['dzpro_cart_shipping_cost']; 
				} 
			} 
		} 
		return $return;	
	}

	/***************************************************************************/
	/****************** SET SHIPMENT MESSAGE ***********************************/
	/***************************************************************************/	
	protected function setShipmentMessage($address_id = null, $message = null){
		if(!have($address_id)){ return false; }
		if(false !== ($shipping_row = self::getShipmentMethod($address_id))){ @mysql_query(" UPDATE dzpro_cart_shipping SET dzpro_cart_shipping_message = '" . mysql_real_escape_string($message) . "' WHERE dzpro_cart_shipping_id = '" . mysql_real_escape_string((int)$shipping_row['dzpro_cart_shipping_id']) . "' ") or handleError(1, mysql_error()); }else{ @mysql_query(" INSERT INTO dzpro_cart_shipping ( dzpro_cart_id, dzpro_user_shipping_option_id, dzpro_cart_shipping_message, dzpro_cart_shipping_date_added ) VALUES ( '" . mysql_real_escape_string((int)$this->cart['dzpro_cart_id']) . "', '" . mysql_real_escape_string($address_id) . "', '" . mysql_real_escape_string($message) . "', NOW() ) ") or handleError(1, mysql_error()); }
	}

	/***************************************************************************/
	/****************** GET SHIPMENT METHOD ************************************/
	/***************************************************************************/	
	public function getShipmentMethod($address_id = null){
		if(!have($address_id)){ return false; }
		$shipping_row = mysql_query_get_row(" SELECT * FROM dzpro_cart_shipping WHERE dzpro_cart_id = '" . mysql_real_escape_string((int)$this->cart['dzpro_cart_id']) . "' AND dzpro_user_shipping_option_id = '" . mysql_real_escape_string($address_id) . "' ");
		if(have($shipping_row)){ return $shipping_row; }
		return false;
	}

	/***************************************************************************/
	/****************** SET SHIPMENT METHOD ************************************/
	/***************************************************************************/	
	protected function setShipmentMethod($address_id = null, $shipment_string = null){
		if(!have($address_id)){ return false; }
		if(!have($shipment_string)){ return false; }
		
		//decode the shipping details
		$shipment_details_object = json_decode(decryptString(base64_decode($shipment_string), 'shipping encryption key 12312390')); if(have($shipment_details_object) and is_object($shipment_details_object)){ $shipment_details = objectToArray($shipment_details_object); }
		
		//update shipping	
		if(false !== ($shipping_row = self::getShipmentMethod($address_id))){ 
			@mysql_query(" 
				UPDATE 
					dzpro_cart_shipping 
				SET 
					dzpro_cart_id = '" . mysql_real_escape_string((int)$this->cart['dzpro_cart_id']) . "',
					dzpro_user_shipping_string = '" . mysql_real_escape_string($shipment_string) . "',
					dzpro_user_shipping_option_id = '" . mysql_real_escape_string($address_id) . "',
					dzpro_cart_shipping_method = '" . mysql_real_escape_string($shipment_details['method']) . "',
					dzpro_cart_shipping_method_type = '" . mysql_real_escape_string($shipment_details['shipping_method']['type']) . "',
					dzpro_cart_shipping_method_type_name = '" . mysql_real_escape_string($shipment_details['shipping_method']['name']) . "',
					dzpro_cart_shipping_cost = '" . mysql_real_escape_string($shipment_details['shipping_method']['price']) . "',
					dzpro_cart_shipping_date = '" . mysql_real_escape_string(date('Y-m-d', $shipment_details['shipping_date'])) . "',
					dzpro_cart_shipping_delivery_date = '" . mysql_real_escape_string(date('Y-m-d', $shipment_details['arrival_date'])) . "'
				WHERE 
					dzpro_cart_shipping_id = '" . mysql_real_escape_string((int)$shipping_row['dzpro_cart_shipping_id']) . "' 
			") or handleError(1, mysql_error()); 
		}else{ 
			@mysql_query(" 
				INSERT INTO 
					dzpro_cart_shipping 
				( 
					dzpro_cart_id,
					dzpro_user_shipping_string,
					dzpro_user_shipping_option_id, 
					dzpro_cart_shipping_method,
					dzpro_cart_shipping_method_type,
					dzpro_cart_shipping_method_type_name,
					dzpro_cart_shipping_cost,
					dzpro_cart_shipping_date,
					dzpro_cart_shipping_delivery_date,
					dzpro_cart_shipping_date_added 
				) VALUES ( 
					'" . mysql_real_escape_string((int)$this->cart['dzpro_cart_id']) . "',
					'" . mysql_real_escape_string($shipment_string) . "',
					'" . mysql_real_escape_string($address_id) . "',
					'" . mysql_real_escape_string($shipment_details['method']) . "',
					'" . mysql_real_escape_string($shipment_details['shipping_method']['type']) . "',
					'" . mysql_real_escape_string($shipment_details['shipping_method']['name']) . "',
					'" . mysql_real_escape_string($shipment_details['shipping_method']['price']) . "',
					'" . mysql_real_escape_string(date('Y-m-d', $shipment_details['shipping_date'])) . "',
					'" . mysql_real_escape_string(date('Y-m-d', $shipment_details['arrival_date'])) . "',
					NOW() 
				) 
			") or handleError(1, mysql_error()); 
		}
	}

	/***************************************************************************/
	/****************** RESET SHIPPING DETAILS *********************************/
	/***************************************************************************/	
	protected function resetShippingDetails($address_id = null){
		if(false !== ($shipping_row = self::getShipmentMethod($address_id))){ 
			@mysql_query(" 
				UPDATE 
					dzpro_cart_shipping 
				SET 
					dzpro_cart_id = '" . mysql_real_escape_string((int)$this->cart['dzpro_cart_id']) . "',
					dzpro_user_shipping_string = '',
					dzpro_user_shipping_option_id = '" . mysql_real_escape_string($address_id) . "',
					dzpro_cart_shipping_method = NULL,
					dzpro_cart_shipping_method_type = NULL,
					dzpro_cart_shipping_method_type_name = NULL,
					dzpro_cart_shipping_cost = NULL,
					dzpro_cart_shipping_date = NULL,
					dzpro_cart_shipping_delivery_date = NULL
				WHERE 
					dzpro_cart_shipping_id = '" . mysql_real_escape_string((int)$shipping_row['dzpro_cart_shipping_id']) . "' 
			") or handleError(1, mysql_error()); 
			return true;
		}
		return false;
	}
	
	/***************************************************************************/
	/****************** GET SIMPLE STATS ***************************************/
	/***************************************************************************/
	public function getSimpleStats(){
		$_SESSION['cart']['product_count'] = self::getProductCount();
		$_SESSION['cart']['total_amount'] = self::getTotalAmount();
		return array(
			'total_products' => (int)$_SESSION['cart']['product_count'], 
			'total_amount' => number_format($_SESSION['cart']['total_amount'], 2)
		);
	}

	/***************************************************************************/
	/****************** CHECK CART ITEGRITY ************************************/
	/***************************************************************************/
	protected function checkCartIntegrity(){
		
		//assume we are good
		$return = true;
		
		//check if agreed to terms and conditions
		if(!isset($this->cart['dzpro_cart_agreed']) or $this->cart['dzpro_cart_agreed'] == 0){ $return = false; }
		
		//check if there is a cart array
		if(!isset($this->cart_array) or !have($this->cart_array)){ $return = false; }
		
		//check if all shipping methods and addressed have been picked
		if(isset($this->cart_array) and have($this->cart_array)){ foreach($this->cart_array as $address_id => $cart_array){ if(!isset($cart_array['shipping']['dzpro_cart_shipping_cost']) or !($cart_array['shipping']['dzpro_cart_shipping_cost'] >= 0) or !isset($cart_array['shipping']['dzpro_user_shipping_option_name']) or !have($cart_array['shipping']['dzpro_user_shipping_option_name'])){ $return = false; } } }
		
		//check if user is logged in
		if(!activeUserSession()){ $return = false; }
		
		return $return;
	}


	/***************************************************************************/
	/****************** PRINT THE CART *****************************************/
	/***************************************************************************/
	public function printTheCart(){	
		self::printCartHeader();
		if(self::checkCartIntegrity() and $this->request_checkout){ self::printPaymentUI(); }else{ self::printCartUI(); }
		return true;
	}

	/***************************************************************************/
	/****************** PRINT CART HEADER **************************************/
	/***************************************************************************/
	protected function printCartHeader(){
		$checkout = (self::checkCartIntegrity() and $this->request_checkout) ? true : false;
		?>
			<script type="text/javascript" src="/assets/js/cartUI.js"></script>
			<div id="cart_header">
				<img src="/assets/layout/cart-outer-decal.png" alt="left decal" class="left_outer_decal" />
				<img src="/assets/layout/cart-outer-decal.png" alt="right decal" class="right_outer_decal" />
				<table cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td>
								<img src="/assets/title/title.php?text=<?=urlencode('CART')?>&amp;size=24&amp;color=<?=(!$checkout) ? '412100' : 'e6d2ae'?>&amp;bgcolor=d0a67a&amp;font=9" alt="Cart" onclick="javascript:window.location='/cart/';" />
								<?php if(!$checkout){ ?>
								<div class="relative">
									<img src="/assets/layout/cart-decal.png" class="decal" alt="decal" />
								</div>
								<?php } ?>
							</td>
							<td>
								<img src="/assets/title/title.php?text=<?=urlencode('CHECKOUT')?>&amp;size=24&amp;color=<?=($checkout) ? '412100' : 'e6d2ae'?>&amp;bgcolor=d0a67a&amp;font=9" alt="Checkout" onclick="javascript:<?php if(!self::checkCartIntegrity()) { ?>$('#incomplete_cart_message').show();<?php } ?>window.location='#cart_summary';" />
								<?php if($checkout){ ?>
								<div class="relative">
									<img src="/assets/layout/cart-decal.png" class="decal" alt="decal" />
								</div>
								<?php } ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		<?php
	}

	/***************************************************************************/
	/****************** PRINT PAYMENT OPTIONS BLOCK ****************************/
	/***************************************************************************/	
	protected function printPaymentOptionsBlock(){
		?>
			<div id="cart_payment_callout">
				<p><strong>Available payment methods</strong> <em>(Please pick one)</em></p>
				<table cellpadding="0" cellspacing="0" style="margin: 0 auto 0 0; text-align: center;">
					<tbody>
						<tr>
							<td>
								<a href="/cart/pmethod/cc/" title="Pay with Credit Card" class="payment_option radius_5 <?php if(isset($_GET['pmethod']) and $_GET['pmethod'] == 'cc'){ echo 'selected'; } ?>" onclick="javascript:$.blockUI();return true;">
									<img src="/assets/layout/poption_cc.jpg" title="Pay with Credit Card" /><br />
									<input type="radio" name="pmethod" value="cc" <?php if(isset($_GET['pmethod']) and $_GET['pmethod'] == 'cc'){ echo 'checked="checked"'; } ?> onclick="javascript:$.blockUI();window.location='/cart/pmethod/cc/';return true;" /> Pay with Credit Card 
								</a>
							</td>
							<td>
								<a href="/cart/pmethod/paypal/" title="Pay with PayPal" class="payment_option radius_5 <?php if(isset($_GET['pmethod']) and $_GET['pmethod'] == 'paypal'){ echo 'selected'; } ?>" onclick="javascript:$.blockUI();if(confirm('You chose to use your paypal account to pay for this order. After you return, you\'ll need to click on \'Complete Order\' to confirm your payment. Ready to goto paypal.com?')){ return true; }else{ $.unblockUI(); return false; }">
									<img src="/assets/layout/poption_pp.gif" title="Pay with PayPal" /><br />
									<input type="radio" name="pmethod" value="paypal" <?php if(isset($_GET['pmethod']) and $_GET['pmethod'] == 'paypal'){ echo 'checked="checked"'; } ?> onclick="javascript:$.blockUI();window.location='/cart/pmethod/paypal/';return true;" /> Pay with PayPal
								</a>
							</td>
							<td>
								<a href="/cart/pmethod/amazon/" title="Pay with Amazon" class="payment_option radius_5 <?php if(isset($_GET['pmethod']) and $_GET['pmethod'] == 'amazon'){ echo 'selected'; } ?>" onclick="javascript:$.blockUI();return true;">
									<img src="/assets/layout/poption_amazon.png" title="Pay with Amazon" /><br />
									<input type="radio" name="pmethod" value="amazon" <?php if(isset($_GET['pmethod']) and $_GET['pmethod'] == 'amazon'){ echo 'checked="checked"'; } ?> onclick="javascript:$.blockUI();window.location='/cart/pmethod/amazon/';return true;" /> Pay with Amazon
								</a>
							</td>
						</tr>
					</tbody>
				</table>
			</div><!-- end cart_payment_callout -->
		<?php
	}

	/***************************************************************************/
	/****************** PRINT CART DETAILS *************************************/
	/***************************************************************************/	
	protected function printCartDetails(){
		?>
			<div id="cart_details_block">
				<table cellspacing="0" cellpadding="0">
					<tbody>
						<tr>
							<td>
								<p><strong>Packages:</strong> <span class="highlight"><?=self::getTotalPackages()?></span><br />
								<span style="font-size: 11px;">We are shipping <?=self::getTotalPackages()?> package<?=((self::getTotalPackages()) > 1 ? 's' : null)?> for you.</span></p>
							</td>
							<td>
								<p><strong>Products:</strong> <span class="highlight"><?=self::getProductCount()?></span><br />
								<span style="font-size: 11px;">In total you are shipping <?=self::getProductCount()?> items.</span></p>
							</td>
							<td>
								<p><strong>Subtotal:</strong> <span class="highlight">$<?=number_format(self::getSubtotalAmount(), 2)?></span><br />
								<span style="font-size: 11px;">The total for all items in <?=((self::getTotalPackages()) > 1 ? 'all packages' : 'this package')?> is $<?=number_format(self::getSubtotalAmount(), 2)?>.</span></p>
							</td>
							<td>
								<p><strong>Shipping:</strong> <span class="highlight">$<?=number_format(self::getTotalShipping(), 2)?></span><br />
								<span style="font-size: 11px;">The <?=((self::getTotalPackages() > 1) ? 'total' : null)?> shipping cost for <?=self::getTotalPackages()?> package<?=((self::getTotalPackages() > 1) ? 's' : null)?> is $<?=number_format(self::getTotalShipping(), 2)?>.</span></p>
							</td>							
							<td>
								<p><strong>Total:</strong> <span class="highlight">$<?=number_format(self::getTotalAmount(), 2)?></span><br />
								<span style="font-size: 11px;">The total amount due is $<?=number_format(self::getTotalAmount(), 2)?>.</span></p>
							</td>
						</tr>
					</tbody>
				</table>
				<p><strong>Want to make some changes? <a href="/cart/" title="Go Back">Go back to the shopping cart</a></strong></p>				
			</div><!-- end cart_details_block -->
		<?php
	}

	/***************************************************************************/
	/****************** PRINT THE PAYMENT UI ***********************************/
	/***************************************************************************/
	protected function printPaymentUI(){
		
		//print cart details
		self::printCartDetails();
		
		echo '<div style="height: 10px;"><!-- spacer --></div>';
		
		//print payment options
		self::printPaymentOptionsBlock();
		
		echo '<div style="height: 10px;"><!-- spacer --></div>';
		
		//print payment info block
		switch((isset($_GET['pmethod'])) ? $_GET['pmethod'] : null){
			
			//amazon
			case 'amazon':
				?>
					<div id="amazon_canvas">
						<div id="payment_response_message" class="problem_mssg"<?=(isset($this->payment_details['status']) and $this->payment_details['status'] == 'false') ? 'style="display: block;"' : null?>><?=(isset($this->payment_details['status']) and $this->payment_details['status'] == 'false') ? prepareStringHtml($this->payment_details['message']) : null?><!-- response message loads here --></div>
						<table cellpadding="0" cellspacing="0">
							<tbody>
								<tr>
									<td style="padding: 0 25px 0 0;">
										<img src="/assets/layout/poption_amazon.png" alt="Pay with Amazon" />
									</td>
									<td>
										<p><strong>You chose to pay with Amazon</strong><br />
										Please click on 'complete order' below to pay using your Amazon account. <br />
										<strong style="color: red;">Note:</strong> After you confirm your payment on amazon.com please click 'Continue' to return to this website.</p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<div style="height: 10px;"><!-- spacer --></div>
					<div id="confirm_button_area">
						<a href="<?=amazonGetPayNowButtonURL(self::getTotalAmount(), 'Wisconsin Cheese Mart Order', md5($this->cart['dzpro_cart_id']), 'https://www.wisconsincheesemart.com/cart/pmethod/amazon/', 'https://www.wisconsincheesemart.com/cart/', 'https://www.wisconsincheesemart.com/cart/pmethod/amazon/')?>" title="Pay with my Amazon account" onclick="javascript:$.blockUI(); if(confirm('After completing your payment on amazon.com you need to click on \'Continue\' to complete your order. Ready to go to amazon.com?')){ return true; }else{ $.unblockUI(); return false; }">
							<img src="/assets/layout/complete-order-button.png" alt="Pay with Amazon" />
						</a>
					</div>
				<?php
			break;
			
			//paypal
			case 'paypal':
				?>
					<div id="paypal_canvas">
						<div id="payment_response_message" class="problem_mssg"><!-- response message loads here --></div>
						<table cellpadding="0" cellspacing="0">
							<tbody>
								<tr>
									<td style="padding: 0 25px 0 0;">
										<img src="/assets/layout/poption_pp.gif" alt="Paypal" />
									</td>
									<td>
										<p><strong>You chose to pay with Paypal</strong><br />
										You've already confirmed your paypal account. To complete your order just click 'Complete Order' below.<br />
										<strong style="color: red;">Note:</strong> You need to click 'Complete Order' to confirm your Paypal payment.</p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<div style="height: 10px;"><!-- spacer --></div>
					<div id="confirm_button_area">
						<form method="post" id="paypal_data_form">
							<?=buildHiddenFieldFromGet()?>
							<input type="image" src="/assets/layout/complete-order-button.png" id="confirm_order_button_paypal" onclick="javascript: return false;" />
						</form>
					</div><!-- end confirm_button_area -->				
				<?php			
			break;
			
			//credit card
			case 'cc':
				?>
					<div id="cc_canvas">
						<div id="payment_response_message" class="problem_mssg"><!-- response message loads here --></div>
						<?php $used_cards = getUserCards('authorize.net'); if(have($used_cards)){ ?>
						<div id="previous_payment_methods">
							<table cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td style="padding: 0 5px;"><strong>Use previous card: </strong></td>
										<td style="padding: 0 5px;">
											<select name="payment_method">
												<option value="">Choose previously used card</option>
												<?php foreach($used_cards as $card_id => $card_array){ ?>
												<option value="<?=$card_array['dzpro_user_card_key']?>"><?=$card_array['dzpro_user_card_key']?></option>
												<?php } ?>
											</select>							
										</td>
										<td style="width: 25px;"><!-- spacer --></td>
										<td style="padding: 0 5px;" class="cvv_holder_previous_card">
											<strong>Enter CVV code:</strong><br />
											<span style="font-size: 10px;">(on back of card)</span>
										</td>
										<td style="padding: 0 5px;" class="cvv_holder_previous_card">
											<input name="cvv_code" id="cvv_code_holder" value="" class="radius_3 shadow_5_inner" maxlength="4" style="padding: 3px 3px 2px 3px; color: #412100; font-family: inherit; border: 1px solid #d0a67a; font-size: 12px; background-color: white; width: 50px;" />
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div id="use_previous_card_or_add">
							 -- or enter a <strong>new</strong> card below --
						</div>
						<?php } ?>
						<form class="payment_form" id="payment_values_form">
							<table cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td style="vertical-align: top; padding: 15px 25px 0 0;">
											<table cellpadding="0" cellspacing="0">
												<tbody>
													<tr>
														<td class="label">
															<label>Name On Card:</label>
														</td>
														<td class="input">
															<input type="text" name="cc_name" value="<?=getUserData('name')?>" class="radius_3 shadow_5_inner" />
														</td>
													</tr>
													<tr>
														<td class="label">
															<label>Billing Address:</label>
														</td>
														<td class="input">
															<input type="text" name="cc_address" value="<?=getUserData('address')?>" class="radius_3 shadow_5_inner" />
														</td>
													</tr>
													<tr>
														<td class="label">
															<label>Billing City:</label>
														</td>
														<td class="input">
															<input type="text" name="cc_city" value="<?=getUserData('city')?>" class="radius_3 shadow_5_inner" />
														</td>
													</tr>
													<tr>
														<td class="label">
															<label>Billing State:</label>
														</td>
														<td class="input">
															<?php printStateSelectBox('cc_state', array(), getUserData('state')); ?>
														</td>
													</tr>
													<tr>
														<td class="label">
															<label>Billing Zipcode:</label>
														</td>
														<td class="input">
															<input type="text" name="cc_zip" value="<?=getUserData('zip')?>" class="radius_3 shadow_5_inner" />
														</td>
													</tr>
												</tbody>
											</table>
										</td>
										<td style="vertical-align: top; padding: 15px 0 0 0;">
											<table cellpadding="0" cellspacing="0">
												<tbody>
													<tr>
														<td class="label">
															<label>Credit Card Number:</label>
														</td>
														<td class="input">
															<input type="text" name="cc_number" value="" class="radius_3 shadow_5_inner" />
														</td>
													</tr>
													<tr>
														<td class="label">
															<label>Expiration Date:</label>
														</td>
														<td class="input">
															<select name="cc_month">
																<?php for($m = 1; $m <= 12; $m ++){ ?><option value="<?=$m?>"><?=$m?></option><?php } ?>
															</select>-
															<select name="cc_year">
																<?php for($y = date('Y'); $y < date('Y') + 12; $y++){ ?><option value="<?=$y?>"><?=$y?></option><?php } ?>
															</select>
														</td>
													</tr>
													<tr>
														<td class="label">
															<label>CVV Code:</label>
														</td>
														<td class="input">
															<input type="text" name="cc_csv" value="" class="radius_3 shadow_5_inner" />
														</td>
													</tr>
													<tr>
														<td colspan="2" style="padding: 5px 50px;">
															<p><strong>We will remember your details for your next purchase.</strong><br />
															If you don't want us to remember your card, please check this box: <input type="checkbox" name="do_not_remember" value="yes" /></p>
														</td>
													</tr>
												</tbody>
											</table>
										</td>
									</tr>
								</tbody>
							</table>
						</form>
					</div><!-- end cc_canvas -->
					<div style="height: 10px;"><!-- spacer --></div>
					<div id="confirm_button_area">
						<input type="image" src="/assets/layout/complete-order-button.png" id="confirm_order_button" />
					</div><!-- end confirm_button_area -->
				<?php
			break;
			
		}
		
	}

	/***************************************************************************/
	/****************** PRINT THE CART UI **************************************/
	/***************************************************************************/	
	protected function printCartUI(){	
		?>
			<div id="the_cart_canvas">
				<?php self::printCartContents(); ?>
			</div><!-- end the_cart_canvas -->
		<?php
	}

	/***************************************************************************/
	/****************** PRINT CART CONTENTS ************************************/
	/***************************************************************************/	
	protected function printCartContents(){
		
		//set new cart totals
		self::getSimpleStats();
		
		//print cart contents
		if(have($this->cart_array)){
			?>
			<script type="text/javascript">
				<!--
					$().ready(function(){ updateItemCount('<?=self::getProductCount()?>'); updateCartTotal('<?=number_format(self::getTotalAmount(), 2)?>'); updateShippingOptions(); });
				//-->
			</script>
			<script type="text/javascript" src="/assets/js/fboxusage.js"></script>
			<?php if(activeUserSession()){ $addresses = getUserAddresses(); if(!have($addresses) or sizeof($addresses) == 1){ ?>
			<div class="problem_mssg">You haven't added any shipping addresses yet. You'll need to do so to complete your order. <br /><a href="/my/addresses/?action=new" title="Add a shipping address" class="fancybox_iframe address_link">Click here to add a shipping address</a></div>
			<div style="height: 10px;"><!-- spacer --></div>
			<?php } } ?>
			<?php if(isset($this->cart_message) and have($this->cart_message)){ ?>
			<div class="problem_mssg"><?=prepareStringHtml($this->cart_message)?></div>
			<div style="height: 10px;"><!-- spacer --></div>
			<?php } ?>
			<div style="padding: 4px 0px; text-align: center; font-size: 10px;">Cart autosaved <?=date('H:i:s')?>, want to start fresh? <a href="<?=addToGetString('refresh_cart', 'true')?>" title="Clear your cart">Click here to clear your cart</a>.</div>
			<?php $z_index_helper = 99; foreach($this->cart_array as $address_id => $cart_array){ ?>
			<div class="cheader">
				<span class="address">
					<span style="color: #111111; font-weight: normal;">Shipping Address:</span> 
					<?php if(have($cart_array['address']['dzpro_user_shipping_option_name'])){ ?>
					<?=$cart_array['address']['dzpro_user_shipping_option_name']?>, <?=$cart_array['address']['dzpro_user_shipping_option_address']?>, <?=$cart_array['address']['dzpro_user_shipping_option_city']?>
					<?php }else{ ?>
					Please pick a shipping address
					<?php } ?>
				</span>
				<?php if($address_id != 0){ ?><span id="pick_address_<?=(int)$address_id?>" class="change_address radius_3"><?=($address_id > 0) ? 'change' : 'pick'?> address</span><?php } ?>
				<div id="pick_address_for_<?=(int)$address_id?>" class="address_holder" <?php if($address_id == 0){ ?>style="display: block;"<?php } ?>>
					<?php if(activeUserSession()){ ?>
						<?php $addresses = getUserAddresses(); if(!have($addresses) or sizeof($addresses) == 1){ ?>
						<select name="address" class="addresses" onchange="javascript:updateShippingAddressForAllItems($(this), <?=(int)$address_id?>); return false;"><option value="">you haven't added any shipping addresses yet</option></select>
						<a href="/my/addresses/?action=new" title="Add a shipping address" class="fancybox_iframe address_link">please add a shipping address</a>
						<?php }else{ ?>
						<?php foreach($addresses as $address_id_option => $address_option){ $addresses[$address_id_option] = ($address_id_option > 0) ? 'ship to ' . $address_option['dzpro_user_shipping_option_name'] . ' (' . $address_option['dzpro_user_shipping_option_address'] . ', ' . $address_option['dzpro_user_shipping_option_city'] . ')' : $address_option; } printSelectBox($addresses, 'address', $address_id, array('class' => 'addresses', 'onchange' => 'javascript:updateShippingAddressForAllItems($(this), ' . (int)$address_id . '); return false;')); ?>
						<a href="/my/addresses/?action=new" title="Add Address" class="fancybox_iframe address_link">add</a>
						<?php if(have($address_id)){ ?>
						&bull; <a href="/my/addresses/?action=edit&amp;record_id=<?=(int)$address_id?>" title="Edit This Address" class="fancybox_iframe address_link">edit</a>
						<?php } ?>
						&bull; <a href="/my/addresses/" title="Add/Edit Addresses" class="fancybox_iframe address_link">manage</a>
						<?php } ?>
					<?php }else{ ?>
					<a href="/connect/" title="Register/Connect or Login to pick a delivery address" class="connect address_link">login to pick delivery address</a>
					<?php } ?>
				</div>
			</div><!-- end .cheader -->
			<table cellpadding="0" cellspacing="0" class="cart_summary_table">
				<tbody>
					<?php foreach($cart_array['items'] as $item){ $z_index_helper--; ?>
					<tr>
						<td class="pimage">
							<img src="<?=(is_file(DOCUMENT_ROOT . $item['item']['dzpro_shop_item_thumb_image'])) ? $item['item']['dzpro_shop_item_thumb_image'] : '/assets/layout/nocheeseimage.jpg'?>" alt="<?=prepareTag($item['item']['dzpro_shop_item_name'])?>" />
						</td>
						<td class="quantity" id="cart_item_id_<?=(int)$item['item']['dzpro_cart_item_id']?>">
							<div class="ui_holder">
								<input name="quantity" value="<?=(int)$item['item']['dzpro_cart_item_quantity']?>" class="radius_3" />
								<div class="modify_cart update_cart radius_3 shadow_5">update</div>
							</div>
						</td>
						<td class="pname"><?=prepareStringHtmlFlat($item['item']['dzpro_shop_item_name'])?> ($<?=number_format($item['item']['dzpro_shop_item_price'], 2)?> each) <?php if(isset($item['options']) and have($item['options'])){ echo '<span style="font-size: 11px;">'; foreach($item['options'] as $option){ echo '<br />+' . $option['dzpro_shop_item_option_name'] . ' ($' . number_format($option['dzpro_shop_item_option_amount'], 2) . ')'; } echo '</span>'; } ?></td>
						<td class="pprice"><?php $item_price = $item['item']['dzpro_shop_item_price']; if(isset($item['options']) and have($item['options'])){ foreach($item['options'] as $option){ $item_price += $option['dzpro_shop_item_option_amount']; } } ?>$<?=number_format($item['item']['dzpro_cart_item_quantity'] * $item_price, 2)?></td>
						<td class="poption">
							<?php if(isset($item['shop_options'])){ ?>
							<div class="pick_options" style="z-index: <?=($z_index_helper > 0) ? $z_index_helper : 1?>;">
								<a href="javascript:void(0);" title="product options" class="change_item_options">product<br /> options</a>
								<div class="cart_overlay_outer">
									<div class="cart_overlay_middle">
										<div class="cart_overlay_inner">
											<div class="close"><!-- close icon --></div>
											<div class="options_holder">
												<p><span style="font-weight: bold;"><?=$item['item']['dzpro_shop_item_name']?> Options</span></p>
												<p><span style="font-size: 11px; line-height: 100%;"><strong>Note:</strong> Please pick from these available options, check the ones you want and click 'update options'</span></p>
												<p class="options_container">
													<?php foreach($item['shop_options'] as $shop_option_id => $shop_option_array){ ?>
													<input type="checkbox" name="option_<?=(int)$shop_option_id?>" value="<?=(int)$item['item']['dzpro_cart_item_id']?>" <?php if(self::isOptionAssociatedWithItem((int)$item['item']['dzpro_cart_item_id'], $shop_option_id)){ echo 'checked="checked"'; } ?> /> <?=prepareStringHtmlFlat($shop_option_array['dzpro_shop_item_option_name'])?> ($<?=number_format($shop_option_array['dzpro_shop_item_option_amount'], 2)?>) <br />
													<?php } ?>
												</p>
												<div class="radius_3 add_options">update options</div>
												<div style="clear:both;"><!-- clear --></div>
											</div>
										</div><!-- end cart_overlay_inner -->
									</div><!-- end cart_overlay_middle -->
								</div><!-- end cart_overlay_outer -->
							</div><!-- end pick_options -->
							<?php }else{ ?>
							&nbsp;<!-- no options -->
							<?php } ?>
						</td>
						<td class="pchange">
							<?php if(sizeof($cart_array['items']) > 1){ ?>
							<div class="change_shipping" style="z-index: <?=($z_index_helper > 0) ? $z_index_helper : 1?>;">
								<a href="javascript:void(0);" title="change shipping address" class="change_shipping_address">change<br /> shipping<br /> address</a>
								<div class="cart_overlay_outer">
									<div class="cart_overlay_middle">
										<div class="cart_overlay_inner">
											<div class="close"><!-- close icon --></div>
											<div class="label">
												<?php if(activeUserSession()){ ?>
												Pick alternate delivery address <a href="/my/addresses/?action=new" title="Add/Edit Addresses" class="fancybox_iframe address_link" style="font-weight: normal;">add new</a>
												<?php }else{ ?>
												<a href="/connect/" title="Register/Connect or Login to pick a delivery address" class="connect address_link">login</a> to pick delivery address
												<?php } ?>
											</div>
											<?php if(isset($addresses) and have($addresses)){ printSelectBox($addresses, 'address', $address_id, array('class' => 'addresses', 'onchange' => 'javascript:updateShippingAddressForItem($(this), ' . (int)$item['item']['dzpro_cart_item_id'] . '); return false;')); } ?>
											<div style="padding: 60px 20px 10px 12px;">
												<?php if(activeUserSession()){ ?>
												<p style="font-size: 11px; line-height: 100%;"><strong>Note:</strong> Please pick an alternate shipping address for just the <em><?=prepareStringHtmlFlat($item['item']['dzpro_shop_item_name'])?></em>. If you want to ship all items to another address please click 'change address' above.</p>
												<p style="font-size: 11px; line-height: 100%;"><strong>Note:</strong> If you need to add a new shipping address, just click 'add new' above the select box.</p>
												<?php }else{ ?>
												<p style="font-size: 11px; line-height: 100%;"><strong>Note:</strong> You're not currently logged in, please <a href="/connect/" title="Register/Connect or Login to pick a delivery address" class="connect address_link">login</a> to select a shipping address.</p>
												<?php } ?>
											</div>
										</div><!-- end cart_overlay_inner -->
									</div><!-- end cart_overlay_middle -->
								</div><!-- end cart_overlay_outer -->
							</div><!-- end change_shipping -->
							<?php }else{ ?>
							&nbsp;
							<?php } ?>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			<div class="bottom_holder">
				<div class="message_holder">
					<strong>Message</strong> <span style="font-size: 10px;">(optional)</span><br />
					<textarea name="package_message" class="radius_3" id="p_message_<?=(int)$address_id?>"><?php $shipping_row = self::getShipmentMethod((int)$address_id); echo $shipping_row['dzpro_cart_shipping_message']; ?></textarea>
				</div><!-- end message_holder -->
				<div class="shipping_icon">
					<img src="/assets/layout/cart-box-icon.jpg" alt="shipping box" />
				</div>
				<div class="shipping_ui">
					<?php $shipping_row = self::getShipmentMethod((int)$address_id); ?>
					<strong><?php if($shipping_row['dzpro_cart_shipping_cost'] > 0){ ?>Change<?php }else{ ?>Pick<?php } ?> Delivery Date</strong> <a href="/assets/ajax/shipping_explanation.php" title="Why the difference in shipping cost" style="font-size: 10px;" class="fancybox" target="_blank">why the different cost</a><br />
					<div class="shipping_option_holder" id="shipping_option_holder_for_<?=(int)$address_id?>"><div class="activity_indicator"><!-- animation --></div><!-- delivery options load here --></div>
					<div class="shipping_destination_holder" id="shipping_destination_holder_for_<?=(int)$address_id?>"><strong>Estimated Delivery Date:</strong> <?=(($address_id > 0) ? ((strtotime($shipping_row['dzpro_cart_shipping_delivery_date']) > time()) ? date('l, F jS Y', strtotime($shipping_row['dzpro_cart_shipping_delivery_date'])) : ((activeUserSession()) ? 'pick delivery date' : 'please <a href="/connect/" title="Register/Connect or Login to pick a delivery method" class="connect address_link">login</a> to pick delivery date')) : 'please pick a shipping address first'); ?></div>
					<div class="shipping_method_holder"><strong>Shipping Method:</strong> <?=(($address_id > 0) ? ((strtotime($shipping_row['dzpro_cart_shipping_delivery_date']) > time()) ? $shipping_row['dzpro_cart_shipping_method_type_name'] : ((activeUserSession()) ? 'pick delivery date' : 'please <a href="/connect/" title="Register/Connect or Login to pick a delivery method" class="connect address_link">login</a> to pick delivery date')) : 'please pick a shipping address first'); ?></div>
				</div><!-- end shipping_ui -->
				<div class="shipment_totals">
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td class="left">
									<strong>Subtotal:</strong>
								</td>
								<td class="right">
									<?php $subtotal = 0; if(isset($cart_array['items']) and have($cart_array['items'])){ foreach($cart_array['items'] as $item){ $item_price = $item['item']['dzpro_shop_item_price']; if(isset($item['options']) and have($item['options'])){ foreach($item['options'] as $option){ $item_price += $option['dzpro_shop_item_option_amount']; } } $subtotal += $item['item']['dzpro_cart_item_quantity'] * $item_price; } } ?>
									$<?=number_format($subtotal, 2)?>
								</td>
							</tr>
							<tr>
								<td class="left">
									<strong>Shipping:</strong>
								</td>
								<td class="right" style="padding: 0px 0px 3px 0px;">
									$<?php if(isset($shipping_row['dzpro_cart_shipping_cost']) and have($shipping_row['dzpro_cart_shipping_cost']) and isset($shipping_row['dzpro_cart_shipping_method_type']) and have($shipping_row['dzpro_cart_shipping_method_type'])){ echo number_format($shipping_row['dzpro_cart_shipping_cost'], 2); }else{ echo '--.--'; } ?>
								</td>
							</tr>
							<?php $discount =  getCouponDiscount($subtotal); if($discount > 0){ ?>
							<tr>
								<td class="left">
									<strong>Discount:</strong>
								</td>
								<td class="right" style="padding: 0px 0px 3px 0px; font-style: italic;">
									-$<?=number_format($discount, 2)?>
								</td>
							</tr>
							<?php } ?>
							<tr>
								<td class="left">
									&nbsp;
								</td>
								<td class="right" style="font-weight: bold; color: #89151c; border-top: 1px solid #d69730; padding: 2px 0px;">
									$<?php if(isset($shipping_row['dzpro_cart_shipping_cost']) and have($shipping_row['dzpro_cart_shipping_cost']) and isset($shipping_row['dzpro_cart_shipping_method_type']) and have($shipping_row['dzpro_cart_shipping_method_type'])){ echo number_format($subtotal + $shipping_row['dzpro_cart_shipping_cost'] - $discount, 2); }else{ echo '--.--'; } ?>
								</td>
							</tr>
						</tbody>
					</table>
				</div><!-- end shipment_totals -->
			</div><!-- end bottom_holder -->
			<div style="height: 10px;"><!-- spacer --></div>
			<?php
			}
			?>
			<div id="cart_summary">
				<strong>Total Amount:</strong> <span class="highlight">$<?=number_format(self::getTotalAmount(), 2)?></span>	
			</div><!-- end cart_summary -->
			<div style="height: 10px;"><!-- spacer --></div>			
			<div id="incomplete_cart_message" style="display: none;">
				<div class="problem_mssg">This cart is incomplete, please complete the items listed below</div>
				<div style="height: 10px;"><!-- spacer --></div>
			</div>
			<?php
			$cart_messages = array(); 
			if(activeUserSession()){
				foreach($this->cart_array as $address_id => $cart_array){ 
					if(
						(
							isset($cart_array['shipping']['dzpro_cart_shipping_cost']) 
								and 
							have($cart_array['shipping']['dzpro_cart_shipping_cost']) 
								and 
							!(
								$cart_array['shipping']['dzpro_cart_shipping_cost'] >= 0
							)
						)
					){
						$cart_messages[] = '<p><strong style="color: red;">Note:</strong> You need to pick a delivery date for your shipment to ' . $cart_array['address']['dzpro_user_shipping_option_name'] . ' at ' . $cart_array['address']['dzpro_user_shipping_option_address'] . ', ' . $cart_array['address']['dzpro_user_shipping_option_city'] . '</p>';
					}
					if(
						!isset($cart_array['address']['dzpro_user_shipping_option_name']) 
							or 
						!have($cart_array['address']['dzpro_user_shipping_option_name'])
					){
						$cart_messages[] = '<p><strong style="color: red;">Note:</strong> You need to pick a shipping address for some of your items. Please click \'change address\' above.</p>';
					}
					if(
						isset($cart_array['address']['dzpro_user_shipping_option_name']) 
							and 
						have($cart_array['address']['dzpro_user_shipping_option_name']) 
							and 
						(
							!isset($cart_array['shipping']['dzpro_cart_shipping_cost']) 
								or 
							!have($cart_array['shipping']['dzpro_cart_shipping_cost'])
								or 
							!isset($cart_array['shipping']['dzpro_cart_shipping_method']) 
								or 
							!have($cart_array['shipping']['dzpro_cart_shipping_method'])
								or 
							!isset($cart_array['shipping']['dzpro_cart_shipping_method_type']) 
								or 
							!have($cart_array['shipping']['dzpro_cart_shipping_method_type'])
						)
					){
						$cart_messages[] = '<p><strong style="color: red;">Note:</strong> You need to pick a delivery date for your shipment to ' . $cart_array['address']['dzpro_user_shipping_option_name'] . ' at ' . $cart_array['address']['dzpro_user_shipping_option_address'] . ', ' . $cart_array['address']['dzpro_user_shipping_option_city'] . '</p>';
					}
				} 
			}else{ 
				$cart_messages[] = '<p>You need to be <a href="/connect/" title="Register/Connect or Login" class="connect">login</a> to continue. Please <a href="/connect/" title="Register/Connect or Login" class="connect">login</a> or <a href="/connect/" title="Register/Connect or Login" class="connect">register</a> now.</p>';
			}
			if(isset($cart_messages) and have($cart_messages)){
			?>
			<div id="cart_messages">				
				<p><strong>Please complete the following items to continue</strong></p>
				<?php		
					foreach($cart_messages as $cart_message){
						echo $cart_message;
					}
				?>
			</div><!-- end cart_messages -->
			<?php	
				}else{
			?>
			<div id="cart_conditions_callout">
				<strong>Terms and Conditions</strong> <em>(You need to agree to our terms and conditions to continue to checkout)</em>
				<div class="conditions"><?=getStaticContent('terms_and_conditions')?></div>
				<div style="height: 5px;"><!-- spacer --></div>
				<input type="checkbox" name="agree_to_terms_and_conditions" id="agree_to_terms_and_conditions" value="yes" <?php if(isset($this->cart['dzpro_cart_agreed']) and $this->cart['dzpro_cart_agreed'] == 1){ echo 'checked="checked"'; } ?> /> I agree to the terms and conditions
				<div style="height: 5px;"><!-- spacer --></div>
			</div>
			<?php if($this->cart['dzpro_cart_agreed'] == 1){ ?>
			<div style="height: 10px;"><!-- spacer --></div>
			<?php self::printPaymentOptionsBlock(); ?>
			<?php }else{ ?>
			<div style="padding: 5px 15px;"><strong style="color: red;">Note:</strong> You need to agree to our terms and conditions to continue to checkout</div>
			<?php } ?>
			<?php	
				}
			?>
			<?php 
		}else{
			if(isset($_GET['completed'])){
			?>
			<h1>Thanks for your business!</h1>
			<h2>Your order has been completed</h2>
			<p>You will receive an order confimation email within a couple of minutes.<br /> Thank you for choosing the Wisconsin Cheese Mart!<br /> If you want you can check your order online, just click on <a href="/account/" title="My Account">account</a>.</p>
			<p>The Wisconsin Cheese Mart</p>
			<?php
			}else{
				echo '<h1>Your cart is empty.</h1><h2>Ready to start shopping for some cheese?</h2>';
				$tags = mysql_query_on_key(" SELECT * FROM dzpro_tags ", 'dzpro_tag_id'); if(have($tags)){ echo '<ul>'; foreach($tags as $tag){ echo '<li><a href="/tag/' . prepareStringForUrl($tag['dzpro_tag_name']) . '/" title="' . prepareTag($tag['dzpro_tag_title']) . '">' . prepareStringHtml(ucwords(strtolower($tag['dzpro_tag_name']))) . '</a></li>'; } echo '</ul>'; }
			}
		}
	}
	
}

?>