<?php

class Coupon {

	/***************************************/
	/*********** PAGE CONSTRUCTOR **********/
	/***************************************/	
	function __construct($db){
		
		//database connection
		$this->db = $db;

		//assure active session
		assureSession();
		
		//coupon message
		$this->coupon_message = null;
		
		//need coupon stack
		if(!isset($_SESSION['coupon_stack'])) $_SESSION['coupon_stack'] = array();
		
		//get coupon
		if(isset($_GET['coupon'])){ if(false !== ($coupon = self::validateCoupon($_GET['coupon']))){ self::setCoupon($coupon); } header('Location: ' . addToGetStringAjax(null, null, 'coupon')); exit(0); }

		//post coupon
		if(isset($_POST['coupon'])){ if(false !== ($coupon = self::validateCoupon($_POST['coupon']))){ self::setCoupon($coupon); } }
	
	}
	
	/***************************************/
	/*********** VALIDATE COUPON ***********/
	/***************************************/
	protected function validateCoupon($coupon_key = null){
		if(!have($coupon_key)){ return false; }
		if(false !== ($coupon = mysql_query_get_row(" SELECT * FROM dzpro_coupons WHERE dzpro_coupon_key = '" . mysql_real_escape_string($coupon_key) . "' AND dzpro_coupon_active = 1 AND '" . mysql_real_escape_string(date('Y-m-d')) . "' BETWEEN dzpro_coupon_from_date AND dzpro_coupon_to_date "))){ if(self::checkCouponUsage($coupon['dzpro_coupon_key'], $coupon['dzpro_coupon_max_usage'])){ return $coupon; } }else{ $this->coupon_message = '"' . $coupon_key . '" is not a valid coupon'; }
		return false;
	}

	/***************************************/
	/*********** CHECK COUPON USED *********/
	/***************************************/
	protected function checkCouponUsage($coupon_key = null, $max_count = 0){
		if(!have($coupon_key)){ return false; }
		if(false !== ($count = mysql_query_get_row(" SELECT COUNT(*) AS count FROM dzpro_coupon_log WHERE dzpro_coupon_key = '" . mysql_real_escape_string($coupon_key) . "' "))){ if((int)$count['count'] <= $max_count){ return true; } }
		return false;
	}

	/***************************************/
	/*********** ADD COUPON TO STACK *******/
	/***************************************/
	public function setCoupon($coupon = null){
		if(!have($coupon)){ return false; }
		switch(true){
			
			//allow concurrent and coupon doesn't already exist
			case(isset($coupon['dzpro_coupon_concurrent']) and $coupon['dzpro_coupon_concurrent'] == '1' and !isset($_SESSION['coupon_stack'][$coupon['dzpro_coupon_key']])):
				$_SESSION['coupon_stack'][$coupon['dzpro_coupon_key']] = $coupon;
			break;
			
			//allow concurrent and coupon does already exist
			case(isset($coupon['dzpro_coupon_concurrent']) and $coupon['dzpro_coupon_concurrent'] == '1' and isset($_SESSION['coupon_stack'][$coupon['dzpro_coupon_key']])):
				$this->coupon_message = 'coupon already applied';
			break;
			
			//do not allow concurrent		
			case(isset($coupon['dzpro_coupon_concurrent']) and $coupon['dzpro_coupon_concurrent'] == '0' and have($_SESSION['coupon_stack'])):
				$this->coupon_message = 'another coupon has already been applied';
			break;
			
			//do not allow concurrent and coupon applied
			case(isset($coupon['dzpro_coupon_concurrent']) and $coupon['dzpro_coupon_concurrent'] == '0' and !have($_SESSION['coupon_stack'])):
				$_SESSION['coupon_stack'][$coupon['dzpro_coupon_key']] = $coupon;
			break;
			
		}
	}

}

?>