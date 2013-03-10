<?php

class Users extends FormBasics {

	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
	
		//extend from parent
		parent::__construct($db, $table_name, $parameters, $sticky_fields);

		//set password reset link
		if(have($_GET['ajax']) and $_GET['ajax'] == 'sendPasswordResetLink' and have($_POST['user_id'])){ echo self::sendPasswordResetEmail($_POST['user_id']); exit(0); }
	
		//get order
		$this->user_orders = self::getUserOrders();
	
	}

	/*************************************************************/
	/*********************** GET USER ORDER **********************/
	/*************************************************************/
	protected function getUserOrders(){
		if(!have($this->primary_value)){ return false; }
		$return = array(); $orders = mysql_query_flat(" 	
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
				dzpro_user_id = '" . mysql_real_escape_string($this->primary_value) . "'
			ORDER BY 
				dzpro_orders.dzpro_order_id DESC	
		"); 
		if(have($orders)){ 
			foreach($orders as $order){ 
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
	/*********************** GET USER BY ID **********************/
	/*************************************************************/		
	protected function getUserById($user_id = null){
		if(!have($user_id)){ return false; }
		if(false !== ($user = mysql_query_flat(" SELECT * FROM dzpro_users WHERE dzpro_user_id = '" . mysql_real_escape_string($user_id) . "' "))){	if(isset($user[0])){ return $user[0]; } }
		return false;
	}

	/*************************************************************/
	/*********************** SEND PASSWORD RESET LINK ************/
	/*************************************************************/	
	protected function sendPasswordResetEmail($user_id = null){
		if(!have($user_id)){ return json_encode(array('status' => 0, 'html' => 'No user_id passed')); }
		$user = self::getUserById($user_id);
		if(isset($user['dzpro_user_email'])){
			if(false !== addEmailToOutbox($user['dzpro_user_name'], $user['dzpro_user_email'], 'Reset Password', '<div style="padding: 30px; margin: 15px; border: 1px solid black;"><h1>Reset Password</h1><p>Please click on the link below to reset your password.</p><p><a href="http://' . HOST_NAME . '/reset/' . md5($user['dzpro_user_email'] . SITE_SALT . $user['dzpro_user_password']) . '/" title="Reset Password Link">reset password link</a></p><p>If the above link doesn\'t work just follow the following url to reset your password.</p><p>http://' . HOST_NAME . '/reset/' . md5($user['dzpro_user_email'] . SITE_SALT . $user['dzpro_user_password']) . '/</p></div>')){ return json_encode(array('status' => 1, 'html' => 'Password reset email was sent, it should arrive within 5 minutes.')); }
		}
		return json_encode(array('status' => 0, 'html' => 'Password reset email was NOT sent.'));
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
		/******************************** FOREIGN TABLE BLOCKS ****************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::printForeignTablesBlock();

		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** PRINT USER DETAILS ******************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::showUserDetails();
		
		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** PRINT ORDERS OVERVIEW ***************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/		
		self::showUserOrders();
		
		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** PRINT SEND PASSWORD LINK BLOCK ******************************/
		/**********************************************************************************************/
		/**********************************************************************************************/		
		self::printSendPasswordResetLinkBlock();

	}

	/*************************************************************/
	/*********************** SHOW SHIPMENT ITEMS *****************/
	/*************************************************************/	
	public function showUserDetails(){
		?>
			<div class="form_area" style="margin-top: 0px;">
				<div style="height: 31px; padding: 13px 29px 0 29px; font-size: 16px; color: black; text-shadow: -1px 1px 1px #ffffff; width: 160px;">
					<strong>User Details</strong>
				</div>
				<div class="input_row inner_shadow">
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td class="label">
									phone number
								</td>
								<td class="plain">
									<div class="inner_holder">
										4144914195
										<a href="/store/shipments.php?action=edit&amp;record_id=<?=(int)$shipment['shipment']['dzpro_order_shipment_id']?>" title="Go to shipment #<?=(int)$shipment['shipment']['dzpro_order_shipment_id']?>" style="font-size: 12px; background-color: #5c7493; color: #ffffff; -moz-border-radius: 10px; border-radius: 10px; font-weight: normal; text-shadow: -1px 1px 1px #111; display: block; float: right; padding: 2px 4px; margin-right: 10px;">&nbsp;details&nbsp;</a>									
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>	
			</div><!-- .form_area -->	
		<?php
	}

	/*************************************************************/
	/*********************** SHOW USER ORDERS ********************/
	/*************************************************************/	
	public function showUserOrders(){
		?>
			<div class="form_area" style="margin-top: 0px;">
				<?php if(have($this->user_orders)){ $ocount = 1; foreach($this->user_orders as $order){ ?>
				<div style="height: 31px; padding: 13px 29px 0 29px; font-size: 16px; color: black; text-shadow: -1px 1px 1px #ffffff; width: 160px;">
					<strong>Order #<?=(int)$order['order']['dzpro_order_id']?></strong>
					<a href="/store/orders.php?action=edit&amp;record_id=<?=(int)$order['order']['dzpro_order_id']?>" title="Go to order #<?=(int)$order['order']['dzpro_order_id']?>" style="font-size: 12px; background-color: #222; color: #ffffff; -moz-border-radius: 10px; border-radius: 10px; font-weight: normal; text-shadow: -1px 1px 1px #111; display: block; float: right; padding: 2px 4px;">&nbsp;details&nbsp;</a>
				</div>
				<div class="input_row inner_shadow">
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
	/*********************** SEND PASSWORD RESET LINK BLOCK ******/
	/*************************************************************/	
	protected function printSendPasswordResetLinkBlock(){
		?>
					<div class="form_area" method="post" style="padding: 0px 27px;" id="email_update_form">
						<script type="text/javascript">
							$().ready(function(){
								$('#email_password_reset_now_link').click(function(){
									$.blockUI();
									$.ajax({
										url : '<?=$_SERVER['PHP_SELF']?>?ajax=sendPasswordResetLink',
										type : 'POST',
										data : 'user_id=<?=$this->primary_value?>',
										dataType : 'json',
										success : function(response){
											$.unblockUI();
											if(response != undefined){
												if(response.status == 1){
													alert('Reset email have been send! ' + response.html);
												}else{
													alert(response.html);
												}
											}
										},
										error : function(error){
											$.unblockUI();
											alert('error: ' + error);
										}
									});
								});
							});
						</script>
						<div class="input_iframe" id="input_row_iframe_constants_<?=$this->table?>">					
							<div style="background-color: #dee3e9; padding: 12px;">
								<div class="button_row">
									<table cellpadding="0" cellspacing="0">
										<tbody>
											<tr>
												<td style="text-align: right; width: 138px;">
													<input type="submit" name="email_password_reset_now" id="email_password_reset_now_link" value="Send Password Reset Email" class="form_tools_button" />
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
		<?php
	}

}

?>