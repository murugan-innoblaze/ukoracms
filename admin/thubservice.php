<?php

//where are we
define('RELATIVE_ASSETS_PATH', '../assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//assure session
assureSession();

//get the request
if(have($_POST['request'])){ $request = $_POST['request']; }elseif(have($HTTP_POST_VARS['request'])){ $request = $HTTP_POST_VARS['request']; }else{ $status_code = 9999; $status_message = 'missing request'; }

//read the xml - make it an array
$request_array = unserialize_xml(stripcslashes($request));

//validate the admin
if(!validateAdmin($request_array['UserID'], $request_array['Password'])){ $status_code = 9000; $status_message = 'failed login'; }

//check for command
if(!have($request_array['Command'])){ $status_code = 9999; $status_message = 'missing command'; }

//if there was an error - show the correct resonse
if(have($status_code) and have($status_message)){
	$return_xml_string = 
				'<?xml version="1.0" encoding="ISO-8859-1"?>'.
				'<RESPONSE Version="2.8">'.
					'<Envelope>'.
						'<Command>GetOrders</Command>'.
						'<StatusCode>' . $status_code . '</StatusCode>'.
						'<StatusMessage>' . $status_message . '</StatusMessage>'.
						'<Provider>GENERIC</Provider>'.
					'</Envelope>'.
				'</RESPONSE>';
	echo $return_xml_string;
	exit(0);
}

switch($request_array['Command']){
	
	/********************************************************/
	/****************** GET ORDERS **************************/
	/********************************************************/
	case('GetOrders'):
	
		//we will organize the order array in here
		$orders_array = array();
		
		//get the data from the database
		$sql = " 
			SELECT 
				* 
			FROM
				dzpro_orders 
			LEFT JOIN 
				dzpro_order_items USING ( dzpro_order_id ) 
			LEFT JOIN 
				dzpro_order_item_options USING ( dzpro_order_item_id ) 
			LEFT JOIN 
				dzpro_order_totals USING ( dzpro_order_id ) 
			LEFT JOIN 
				dzpro_order_payments USING ( dzpro_order_id )
			LEFT JOIN 
				dzpro_users USING ( dzpro_user_id )
			WHERE 
				dzpro_order_id >= " . (int)$request_array['OrderStartNumber'] . "
			GROUP BY 
				dzpro_order_id
			ORDER BY 
				dzpro_order_id ASC,
				dzpro_order_payment_date_added ASC,
				dzpro_order_total_date_added ASC
			LIMIT 
				" . (int)$request_array['LimitOrderCount'] . "
		"; 
		
		//go through the result
		$result = @mysql_query($sql) or die(mysql_error()); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ 
			
			//get the order details
			if(have($row['dzpro_order_id'])){ foreach($row as $key => $value){ $orders_array[$row['dzpro_order_id']][$key] = $value; } }
			
			//get the items
			if(have($row['dzpro_order_item_id'])){ foreach($row as $key => $value){ $orders_array[$row['dzpro_order_id']]['items'][$row['dzpro_order_item_id']][$key] = $value; } }
			
			//get the item options
			if(have($row['dzpro_order_item_option_id'])){ foreach($row as $key => $value){ $orders_array[$row['dzpro_order_id']]['items'][$row['dzpro_order_item_id']]['options'][$row['dzpro_order_item_option_id']][$key] = $value; } }			
			
			//get the totals
			if(have($row['dzpro_order_total_id']) and $row['dzpro_order_total_discount'] == 0){ foreach($row as $key => $value){ $orders_array[$row['dzpro_order_id']]['totals'][$row['dzpro_order_total_id']][$key] = $value; } }
			
			//get the fees
			if(have($row['dzpro_order_total_id']) and $row['dzpro_order_total_fee'] == 1){ foreach($row as $key => $value){ $orders_array[$row['dzpro_order_id']]['fees'][$row['dzpro_order_total_id']][$key] = $value; } }
			
			//get the payments
			if(have($row['dzpro_order_payment_id'])){ foreach($row as $key => $value){ $orders_array[$row['dzpro_order_id']]['payments'][$row['dzpro_order_payment_id']][$key] = $value; } }
			
			//get the user
			if(have($row['dzpro_user_id'])){ foreach($row as $key => $value){ $orders_array[$row['dzpro_order_id']]['user'][$key] = $value; } }
				
		} mysql_free_result($result); }
	
		//if empty orders
		if(!have($orders_array)){ $status_code = 1000; }else{ $status_code = 0; }
	
		$return_xml_string = 
			'<?xml version="1.0" encoding="ISO-8859-1"?>'.
			'<RESPONSE Version="2.8">'.
				'<Envelope>'.
					'<Command>' . $request_array['Command'] . '</Command>'.
					'<StatusCode>' . $status_code . '</StatusCode>'.
					'<StatusMessage>' . $status_message . '</StatusMessage>'.
					'<Provider>' . THUB_PROVIDER . '</Provider>'.
				'</Envelope>'.
					'<Orders>';
   		foreach($orders_array as $order_id => $order_array){	
   			$return_xml_string .=
					'<Order>'.
						'<OrderID>' . (int)$order_id . '</OrderID>'.
						'<ProviderOrderRef>' . (int)$order_id . '</ProviderOrderRef>'.
						'<Date>' . date('Y-m-d', strtotime($order_array['dzpro_order_date_added'])) . '</Date>'.
						'<Time>' . date('H:i:s', strtotime($order_array['dzpro_order_date_added'])) . '</Time>'.
						'<TimeZone>' . THUB_TIMEZONE . '</TimeZone>'.
						'<UpdatedOn>' . date('Y-m-d H:i:s', strtotime($order_array['dzpro_order_last_modified'])) . '</UpdatedOn>'.
						'<StoreID>' . THUB_STORE_ID . '</StoreID>'.
						'<StoreName>' . THUB_STORE_NAME . '</StoreName>'.
						'<CustomerID>' . (int)$order_array['dzpro_user_id'] . '</CustomerID>'.
						'<CustomerType>' . THUB_CUSTOMER_TYPE . '</CustomerType>'.
						'<Currency>' . THUB_CURRENCY . '</Currency>'.
						'<Bill>'.
							'<PayMethod>' . $order_array['dzpro_payment_method_id'] . '</PayMethod>'.
							'<PayStatus>Cleared</PayStatus>'.
							'<PayDate>' . date('Y-m-d', strtotime($order_array['dzpro_order_total_date_added'])) . '</PayDate>'.
							'<FirstName>' . getUserData('first name', false, (int)$order_array['dzpro_user_id']) . '</FirstName>'.
							'<LastName>' . getUserData('last name', false, (int)$order_array['dzpro_user_id']) . '</LastName>'.
							'<MiddleName></MiddleName>'.
							'<CompanyName></CompanyName>'.
							'<Address1>' . getUserData('address', false, (int)$order_array['dzpro_user_id']) . '</Address1>'.
							'<Address2></Address2>'.
							'<City>' . getUserData('city', false, (int)$order_array['dzpro_user_id']) . '</City>'.
							'<State>' . getUserData('state', false, (int)$order_array['dzpro_user_id']) . '</State>'.
							'<Zip>' . getUserData('zip', false, (int)$order_array['dzpro_user_id']) . '</Zip>'.
								'<Country>' . THUB_SHIP_TO_COUNTRY . '</Country>'.
								'<Email>' . $order_array['dzpro_user_email'] . '</Email>'.
							'<Phone></Phone>'.
						'</Bill>'.
						'<Ship>'.
							'<ShipStatus>Shipped</ShipStatus>'.
							'<ShipDate>' . date('Y-m-d', strtotime($order_array['dzpro_order_date_added'])) . '</ShipDate>'.
							'<Tracking></Tracking>'.
							'<ShipCost>0.00</ShipCost>'.
							'<ShipCarrierName>Electronic</ShipCarrierName>'.
							'<ShipMethod>Electronic Delivery</ShipMethod>'.
							'<FirstName>' . getUserData('first name', false, (int)$order_array['dzpro_user_id']) . '</FirstName>'.
							'<LastName>' . getUserData('last name', false, (int)$order_array['dzpro_user_id']) . '</LastName>'.
							'<MiddleName></MiddleName>'.
							'<CompanyName></CompanyName>'.
							'<Address1>' . getUserData('address', false, (int)$order_array['dzpro_user_id']) . '</Address1>'.
							'<Address2></Address2>'.
							'<City>' . getUserData('city', false, (int)$order_array['dzpro_user_id']) . '</City>'.
							'<State>' . getUserData('state', false, (int)$order_array['dzpro_user_id']) . '</State>'.
							'<Zip>' . getUserData('zip', false, (int)$order_array['dzpro_user_id']) . '</Zip>'.
							'<Country>' . THUB_SHIP_TO_COUNTRY . '</Country>'.
							'<Email>' . $order_array['dzpro_user_email'] . '</Email>'.
							'<Phone></Phone>'.
						'</Ship>'.
						'<Items>';
			if(have($order_array['items'])){
				foreach($order_array['items'] as $item_id => $item_array){
					$this_price = $item_array['dzpro_order_item_price'];
					if(have($item_array['options'])){ foreach($item_array['options'] as $option_id => $option_array){ $this_price += $option_array['dzpro_order_item_option_amount']; } }
					$return_xml_string .= 
							'<Item>'.
								'<ItemCode>' . $item_array['dzpro_order_item_pid'] . '</ItemCode>'.
								'<ItemDescription encoding="yes">' . base64_encode($item_array['dzpro_order_item_name']) . '</ItemDescription>'.
								'<Quantity>' . (int)$item_array['dzpro_order_item_quantity'] . '</Quantity>'.
								'<UnitPrice>' . number_format($this_price, 2) . '</UnitPrice>'.
								'<ItemTotal>' . number_format($item_array['dzpro_order_item_quantity'] * $this_price, 2) . '</ItemTotal>'.
								'<ItemUnitWeight>0</ItemUnitWeight>'.
								'<Length>0</Length>'.
								'<Depth>0</Depth>'.
								'<Height>0</Height>';
					if(have($item_array['options'])){
						$return_xml_string .=
  								'<ItemOptions>';
	      				foreach($item_array['options'] as $option_id => $option_array){
	      					$return_xml_string .=
									'<ItemOption Name="' . $option_array['dzpro_order_item_option_name'] . '" Value="selected" />';
						}
						$return_xml_string .=					
								'</ItemOptions>';
					}
					$return_xml_string .=
							'</Item>';
				}
			}
			$return_xml_string .= 	
						'</Items>'.
						'<Charges>'.
							'<Shipping>0.00</Shipping>'.
							'<Handling>0.00</Handling>';
			$sales_tax = 0;
			$order_sub_total = 0;
			$order_total = 0;
			if(have($order_array['items'])){
				foreach($order_array['items'] as $item_id => $item_array){
					$this_sub_total = 0;
					$this_sub_total_tax = 0;
					$this_price = $item_array['dzpro_order_item_price'];
					if(have($item_array['options'])){ 
						foreach($item_array['options'] as $option_id => $option_array){ 
							$this_price += $option_array['dzpro_order_item_option_amount']; 
						} 
					}
					$this_sub_total = $item_array['dzpro_order_item_quantity'] * $this_price;
					if(have($item_array['dzpro_order_item_tax']) and $item_array['dzpro_order_item_tax'] == 1){
						$this_sub_total_tax += THE_SALES_TAX_RATE * $this_sub_total;
					}
					$sales_tax += $this_sub_total_tax;
					$order_sub_total += $this_sub_total;
				}
			}
			$order_total = $order_sub_total + $sales_tax;
			$return_xml_string .= 
							'<Tax Name="Sales Tax">' . number_format($sales_tax, 2) . '</Tax>';
			if(have($order_array['fees'])){	
				$return_xml_string .=
							'<FeeDetails>';
				foreach($order_array['fees'] as $fee_total_id => $fee_array){
					$return_xml_string .=
								'<FeeDetail>'.
									'<FeeName>' . $fee_array['dzpro_order_total_name'] . '</FeeName>'.
									'<FeeValue>' . $fee_array['dzpro_order_total_value'] . '</FeeName>'.
								'</FeeDetail>';
				}
				$return_xml_string .=
							'</FeeDetails>';
			}
			$return_xml_string .=
							'<Total>' . number_format($order_total, 2) . '</Total>'.
						'</Charges>'.
					'</Order>';
		}
		$return_xml_string .= 
				'</Orders>'.
			'</RESPONSE>';
					
		/*******************************************/
		/************** RETURN THE XML *************/
		/*******************************************/
		echo $return_xml_string; exit(0);
		
	break;

	/********************************************************/
	/****************** UPDATE SHIPPING STATUS **************/  //lets add an order status in case of an update
	/********************************************************/
	case('UpdateOrdersShippingStatus'):
			
		//checking if orders are found
		$orders_found = false;
		
		//building valid return
		$return_xml_string =
			'<?xml version="1.0" encoding="ISO-8859-1"?>'.
			'<RESPONSE Version="2.8">'.
				'<Envelope>'.
					'<Command>' . $request_array['Command'] . '</Command>'.
					'<StatusCode>0</StatusCode>'.
					'<StatusMessage>All Ok</StatusMessage>'.
	   			'</Envelope>'.
	   			'<Orders>';
	   	if(have($request_array['Orders']['Order'])){
			foreach($request_array['Orders']['Order'] as $order){
				if(haveOrder($order['HostOrderID'])){  
					$return_xml_string .=
					'<Order>'.
						'<HostOrderID>' . $order['HostOrderID'] . '</HostOrderID>'.
						'<LocalOrderID>' . $order['LocalOrderID'] . '</LocalOrderID>'.
						'<HostStatus>Success</HostStatus>'.
					'</Order>';
					$orders_found = true;
				}
			}
		}
		$return_xml_string .=
				'</Orders>'.
			'</RESPONSE>';
		
		//failed return string
		$return_xml_string_failed = 
			'<?xml version="1.0" encoding="ISO-8859-1"?>'.
			'<RESPONSE Version="2.8">'.
				'<Envelope> '.
					'<Command>' . $request_array['Command'] . '</Command>'.
					'<StatusCode>9999</StatusCode>'.
					'<StatusMessage>Orders Not Found</StatusMessage>'.
	   			'</Envelope>'.
			'</RESPONSE>';	
					
		/*******************************************/
		/************** RETURN THE XML *************/
		/*******************************************/
		if($orders_found !== false){ echo $return_xml_string; }else{ echo $return_xml_string_failed; } exit(0);		
	
	break;
	
	/********************************************************/
	/****************** UPDATE PAYMENT STATUS ***************/	//lets add a payment record in case of an update
	/********************************************************/
	case('UpdateOrdersPaymentStatus'):

		//checking if orders are found
		$orders_found = false;
		
		//building valid return
		$return_xml_string =
			'<?xml version="1.0" encoding="ISO-8859-1"?>'.
			'<RESPONSE Version="2.8">'.
				'<Envelope>'.
					'<Command>' . $request_array['Command'] . '</Command>'.
					'<StatusCode>0</StatusCode>'.
					'<StatusMessage>All Ok</StatusMessage>'.
	   			'</Envelope>'.
	   			'<Orders>';
	   	if(have($request_array['Orders']['Order'])){
			foreach($request_array['Orders']['Order'] as $order){
				if(haveOrder($order['HostOrderID'])){  
					$return_xml_string .=
					'<Order>'.
						'<HostOrderID>' . $order['HostOrderID'] . '</HostOrderID>'.
						'<LocalOrderID>' . $order['LocalOrderID'] . '</LocalOrderID>'.
						'<HostStatus>Success</HostStatus>'.
					'</Order>';
					$orders_found = true;
				}
			}
		}
		$return_xml_string .=
				'</Orders>'.
			'</RESPONSE>';
		
		//failed return string
		$return_xml_string_failed = 
			'<?xml version="1.0" encoding="ISO-8859-1"?>'.
			'<RESPONSE Version="2.8">'.
				'<Envelope>'.
					'<Command>' . $request_array['Command'] . '</Command>'.
					'<StatusCode>9999</StatusCode>'.
					'<StatusMessage>Orders Not Found</StatusMessage>'.
	   			'</Envelope>'.
			'</RESPONSE>';	
					
		/*******************************************/
		/************** RETURN THE XML *************/
		/*******************************************/
		if($orders_found !== false){ echo $return_xml_string; }else{ echo $return_xml_string_failed; } exit(0);				

	break;

	/********************************************************/
	/****************** UPDATE INVENTORY ********************/	//lets add a payment record in case of an update
	/********************************************************/
	case('UpdateInventory'):
	
		//checking if orders are found
		$items_found = false;
		
		//building valid return
		$return_xml_string =
			'<?xml version="1.0" encoding="ISO-8859-1"?>'.
			'<RESPONSE Version="2.8">'.
				'<Envelope>'.
					'<Command>' . $request_array['Command'] . '</Command>'.
					'<StatusCode>0</StatusCode>'.
					'<StatusMessage>All Ok</StatusMessage>'.
	   			'</Envelope>'.
	   			'<Items>';
	   	if(have($request_array['Items']['Item'])){
			foreach($request_array['Items']['Item'] as $item){
				if(updateItemQuantity($item['ItemCode'], $item['QuantityInStock'])){  
					$return_xml_string .=
					'<Item>'.
						'<ItemCode>' . $item['ItemCode'] . '</ItemCode>'.
						'<InventoryUpdateStatus>0</InventoryUpdateStatus>'.
					'</Item>';
					$items_found = true;
				}else{
					$return_xml_string .=
					'<Item>'.
						'<ItemCode>' . $item['ItemCode'] . '</ItemCode>'.
						'<InventoryUpdateStatus>1</InventoryUpdateStatus>'.
					'</Item>';
				}
			}
		}
		$return_xml_string .=
				'</Items>'.
			'</RESPONSE>';
		
		//failed return string
		$return_xml_string_failed = 
			'<?xml version="1.0" encoding="ISO-8859-1"?>'.
			'<RESPONSE Version="2.8">'.
				'<Envelope>'.
					'<Command>' . $request_array['Command'] . '</Command>'.
					'<StatusCode>9999</StatusCode>'.
					'<StatusMessage>No Items Found</StatusMessage>'.
	   			'</Envelope>'.
			'</RESPONSE>';	
					
		/*******************************************/
		/************** RETURN THE XML *************/
		/*******************************************/
		if($items_found !== false){ echo $return_xml_string; }else{ handleError(1, 'Tried updating items but no items found: ' . serialize($request_array)); echo $return_xml_string_failed; } exit(0);	
	
	break;

	/********************************************************/
	/****************** UNKNOWN COMMAND *********************/
	/********************************************************/
	default:
		
		//Status code - other error
		$status_code = 9999; 
		
		//Status message
		$status_message = 'unknown command [' . $request_array['Command'] . ']';
		
		//handle error
		handleError(1, $status_message);
		
		//build the return
		$return_xml_string = 
			'<?xml version="1.0" encoding="ISO-8859-1"?>'.
			'<RESPONSE Version="2.8">'.
				'<Envelope>'.
					'<Command>' . $request_array['Command'] . '</Command>'.
					'<StatusCode>' . $status_code . '</StatusCode>'.
					'<StatusMessage>' . $status_message . '</StatusMessage>'.
					'<Provider>' . THUB_PROVIDER . '</Provider>'.
				'</Envelope>'.
			'</PESPONSE>';
			
		/*******************************************/
		/************** RETURN THE XML *************/
		/*******************************************/
		echo $return_xml_string; exit(0);
			
	break;

}

?>