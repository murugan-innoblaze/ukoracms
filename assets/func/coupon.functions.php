<?php

/************************************************************************/
/******************* APPLY COUPON ***************************************/
/************************************************************************/
function applyCoupons($amount = null, $log_usage = false, $order_id = null){
	if(!have($amount)){ return false; } $original_amount = $amount; 
	if(have($_SESSION['coupon_stack'])){ foreach($_SESSION['coupon_stack'] as $coupon_key => $coupon_array){ if(have($coupon_array['dzpro_coupon_type']) and $coupon_array['dzpro_coupon_type'] == 'discount' and $coupon_array['dzpro_coupon_min_amount'] <= $original_amount){ $amount -= $coupon_array['dzpro_coupon_amount']; if($log_usage){ logCouponToDatabase($coupon_key, $original_amount, $coupon_array['dzpro_coupon_amount'], $order_id); } } } foreach($_SESSION['coupon_stack'] as $coupon_key => $coupon_array){ if(have($coupon_array['dzpro_coupon_type']) and $coupon_array['dzpro_coupon_type'] == 'percentage' and $coupon_array['dzpro_coupon_min_amount'] <= $original_amount){ $amount -= ( ($coupon_array['dzpro_coupon_percentage']/100) * $amount ); if($log_usage){ logCouponToDatabase($coupon_key, $original_amount, ( ($coupon_array['dzpro_coupon_percentage']/100) * $amount ), $order_id); } } } }
	return $amount;
}

/************************************************************************/
/******************* APPLY COUPON ALIAS *********************************/
/************************************************************************/
function applyCoupon($amount = null, $log_usage = false, $order_id = null){ return applyCoupons($amount, $log_usage, $order_id); }

/************************************************************************/
/******************* LOG COUPONS TO DATABASE ****************************/
/************************************************************************/
function logCouponToDatabase($coupon_key = null, $amount = null, $coupon_discount = null, $order_id = null){
	if(!have($coupon_key)){ return false; } if(!have($amount)){ return false; } if(!have($coupon_discount)){ return false; }
	return mysql_insert(" INSERT INTO dzpro_coupon_log ( dzpro_user_id, dzpro_visitor_id, dzpro_order_id, dzpro_coupon_key, dzpro_coupon_log_amount, dzpro_coupon_log_discount, dzpro_coupon_log_date_added ) VALUES ( '" . mysql_real_escape_string(getUserId()) . "', '" . mysql_real_escape_string(getVisitorId()) . "', '" . mysql_real_escape_string((int)$order_id) . "', '" . mysql_real_escape_string($coupon_key) . "', '" . mysql_real_escape_string($amount) . "', '" . mysql_real_escape_string($coupon_discount) . "', NOW() ) "); 
}

/************************************************************************/
/******************* GET COUPON DISCOUNT ********************************/
/************************************************************************/
function getCouponsDiscount($amount = null){
	if(!have($amount)){ return false; }
	return $amount - applyCoupons($amount);
}

/************************************************************************/
/******************* GET COUPON DISCOUNT ALIAS **************************/
/************************************************************************/
function getCouponDiscount($amount = null){
	if(!have($amount)){ return false; }
	return getCouponsDiscount($amount);
}

/************************************************************************/
/******************* GET COUPONS APPLIED ********************************/
/************************************************************************/
function getCouponsApplied($amount = null){
	if(!have($amount)){ return false; }
	$original_amount = $amount; 
	$return = array();
	if(have($_SESSION['coupon_stack'])){ 
		foreach($_SESSION['coupon_stack'] as $coupon_key => $coupon_array){ 
			$return[$coupon_key]['active'] = (have($coupon_array['dzpro_coupon_type']) and $coupon_array['dzpro_coupon_min_amount'] <= $original_amount);
			$return[$coupon_key]['coupon'] = $_SESSION['coupon_stack'][$coupon_key];
		} 
	}
	return $return;
}

/************************************************************************/
/******************* GET COUPONS APPLIED ********************************/
/************************************************************************/
function clearCoupons(){
	assureSession();
	if(isset($_SESSION['coupon_stack'])){
		$_SESSION['coupon_stack'] = array();
		unset($_SESSION['coupon_stack']);
		return true;
	}
	return false;
}
?>