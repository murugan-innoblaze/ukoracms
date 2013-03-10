<?php

class Orders extends FormBasics {

	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
	
		//extend from parent
		parent::__construct($db, $table_name, $parameters, $sticky_fields);

		//handle single records search
		self::handleSingleResultRedirect();

		//get order details
		$this->order_details = self::getOrdersDetails();
	
	}

	/*************************************************************/
	/*********************** GET USER ORDER **********************/
	/*************************************************************/
	protected function getOrdersDetails(){
		if(!have($this->primary_value)){ return false; }
		$return = array(); $the_order = mysql_query_flat(" 	
			SELECT 
				* 
			FROM 
				dzpro_orders 
			LEFT JOIN 
				dzpro_order_totals USING ( dzpro_order_id ) 
			LEFT JOIN 
				dzpro_order_payments USING ( dzpro_order_id ) 
			LEFT JOIN 
				dzpro_order_status_history USING ( dzpro_order_id ) 
			LEFT JOIN 
				dzpro_order_items USING ( dzpro_order_id ) 
			LEFT JOIN 
				dzpro_order_shipments USING ( dzpro_order_shipment_id ) 
			LEFT JOIN 
				dzpro_order_item_options USING ( dzpro_order_item_id ) 
			WHERE 
				dzpro_orders.dzpro_order_id = '" . mysql_real_escape_string($this->primary_value) . "'
		"); 
		if(have($the_order)){ 
			foreach($the_order as $order){ 
				if(isset($order['dzpro_order_id']) and have($order['dzpro_order_id'])){
					$return[$order['dzpro_order_id']]['order'] = $order; 
				}
				if(isset($order['dzpro_order_shipment_id']) and have($order['dzpro_order_shipment_id'])){ 
					$return[$order['dzpro_order_id']]['shipments'][$order['dzpro_order_shipment_id']]['shipment'] = $order; 
				} 
				if(isset($order['dzpro_order_item_id']) and have($order['dzpro_order_item_id'])){ 
					$return[$order['dzpro_order_id']]['shipments'][$order['dzpro_order_shipment_id']]['items'][$order['dzpro_order_item_id']]['item'] = $order; 
				} 
				if(isset($order['dzpro_order_item_option_id']) and have($order['dzpro_order_item_option_id'])){ 
					$return[$order['dzpro_order_id']]['shipments'][$order['dzpro_order_shipment_id']]['items'][$order['dzpro_order_item_id']]['options'][$order['dzpro_order_item_option_id']] = $order; 
				}
				if(isset($order['dzpro_order_status_history_id']) and have($order['dzpro_order_status_history_id'])){
					$order[$order['dzpro_order_id']]['status'][$order['dzpro_order_status_history_id']] = $order;
				}
				if(isset($order['dzpro_order_payment_id']) and have($order['dzpro_order_payment_id'])){
					$order[$order['dzpro_order_id']]['payments'][$order['dzpro_order_payment_id']] = $order;
				}
			}
		} return $return;
	}

	/*************************************************************/
	/*********************** SHOW SHIPMENT ITEMS *****************/
	/*************************************************************/	
	public function showOrderDetails(){
		?>
			<div class="form_area" style="margin-top: 0px;">
				<?php if(have($this->order_details)){ $ocount = 1; foreach($this->order_details as $order){ ?>
				<div style="height: 31px; padding: 13px 29px 0 29px; font-size: 16px; color: black; text-shadow: -1px 1px 1px #ffffff; width: 160px;">
					<strong>Order #<?=(int)$order['order']['dzpro_order_id']?></strong>
				</div>
				<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
					<?php if(have($order['shipments'])){ $scount = 1; foreach($order['shipments'] as $shipment){ ?>
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td class="label">
									Shipment #<?=(int)$shipment['shipment']['dzpro_order_shipment_id']?>
								</td>
								<td class="quantity">
									<?=sizeof($shipment['items'])?>
								</td>
								<td class="plain">
									<div class="inner_holder">
										<?=prepareStringHtml($shipment['shipment']['dzpro_order_shipment_name'] . ' ' . $shipment['shipment']['dzpro_order_shipment_address'])?>
										<a href="/store/shipments.php?action=edit&amp;record_id=<?=(int)$shipment['shipment']['dzpro_order_shipment_id']?>" title="Go to shipment #<?=(int)$shipment['shipment']['dzpro_order_shipment_id']?>" style="font-size: 12px; background-color: #5c7493; color: #ffffff; -moz-border-radius: 10px; border-radius: 10px; font-weight: normal; text-shadow: -1px 1px 1px #111; display: block; float: right; padding: 2px 4px; margin-right: 10px;">&nbsp;details&nbsp;</a>									
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<?php if($scount != sizeof($order['shipments'])){ echo '<div class="line"><!-- line --></div>'; } ?>
					<?php } } ?>
				</div>	
				<?php $ocount++; } } ?>
			</div><!-- .form_area -->	
		<?php
	}

	/*************************************************************/
	/*********************** SET DATE FIELD NAME *****************/
	/*************************************************************/		
	protected function handleSingleResultRedirect(){
		if(isset($this->show_records[0][$this->primary_key]) and sizeof($this->show_records) == 1 and isset($this->search_query) and is_numeric($this->search_query)){ header('Location: ' . addToGetStringAjax(array('action', 'record_id'), array('edit', $this->show_records[0][$this->primary_key]), array('record_search'))); exit(0); } return true;
	}
		
	/*************************************************************/
	/*********************** BUILD FORM BLOCK ********************/
	/*************************************************************/
	public function buildFormBlock(){

		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** PRINT TOP BUTTON ROW ****************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/		
		self::printTopFormButtonRow();

		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** PRINT FORM FIELDS *******************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::printFormFields();

		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** CONDITIONAL FIELD CONDITIONS ********************************/
		/**********************************************************************************************/
		/**********************************************************************************************/		
		self::printConditionalFieldsJs();
					
		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** ASSOCIATION BLOCKS ******************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/		
		self::printAssociativeBlocks();

		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** PRINT BOTTOM BUTTON ROW *************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::printBottomFormButtonRow();

		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** SHOW ORDER DETAILS ******************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::showOrderDetails();

		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** FOREIGN TABLE BLOCKS ****************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::printForeignTablesBlock();	
	}
	
}

?>