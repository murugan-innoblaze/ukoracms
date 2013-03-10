<?php

class PageForm extends Page {

	/***************************************/
	/*********** PAGE CONSTRUCTOR **********/
	/***************************************/
	function __construct($db){

		//in case we use captcha
		assureSession();

		//construct parent
		parent::__construct($db);

		//get page forms array
		$this->page_forms_raw_array = parent::getPageForms();
		
		//credit card present checker
		$this->credit_card_present = array();
		
		//possible credit card payment information
		$this->credit_card_information = array();
		
		//form status array
		$this->page_form_status = array();
		
		//form array
		$this->form_array = array();
		
		//organize form array
		self::organizeFormArray();
	
		//handle form submit
		if(have($_POST)){ self::buildSubmittedValues(); self::validateFormSubmission(); }
	
	}

	/***************************************************************************/
	/****************** ORGANIZE THE FORM ARRAY ********************************/
	/***************************************************************************/	
	public function organizeFormArray(){
		if(have($this->page_forms_raw_array)){ foreach($this->page_forms_raw_array as $key => $value){ if(have($value)){ foreach($value as $fkey => $fvalue){ if(have($fvalue) and substr($fkey, 0, 17) != 'dzpro_form_field_'){ $this->form_array[$value['dzpro_form_id']][$fkey] = $fvalue; } if(have($fvalue) and substr($fkey, 0, 17) == 'dzpro_form_field_'){ $this->form_array[$value['dzpro_form_id']]['fields'][$key][$fkey] = $fvalue; } } if($value['dzpro_form_field_type'] == 'creditcard'){ $this->credit_card_present[$value['dzpro_form_id']] = self::buildPaymentOptions($value); } } } }
	}

	/***************************************************************************/
	/****************** ORGANIZE PAYMENT OPTIONS *******************************/
	/***************************************************************************/	
	protected function buildPaymentOptions($creditcard_date_type_row = null){
		if(!isset($creditcard_date_type_row['dzpro_form_field_multiple'])){ return false; }
		switch($creditcard_date_type_row['dzpro_form_field_multiple']){
			case 'single':
				return $creditcard_date_type_row['dzpro_form_field_amount'];
			break;
			case 'multiple':
				return $creditcard_date_type_row['dzpro_form_field_amount'];
			break;
			case 'custom options':
				$option_array = array(); $option_array_raw = explode(',', $creditcard_date_type_row['dzpro_form_field_payment_options']); if(have($option_array_raw)){ foreach($option_array_raw as $option_array_raw_part){ $option_array_raw_part_raw = explode('=', $option_array_raw_part); if(have($option_array_raw_part_raw[0]) and have($option_array_raw_part_raw[1]) and is_numeric($option_array_raw_part_raw[1])){ $option_array[$option_array_raw_part_raw[0]] = $option_array_raw_part_raw[1]; } } }	
				return $option_array;
			break;
			default:
				return false;
			break;		
		}
	}

	/***************************************************************************/
	/****************** ORGANIZE THE FORM ARRAY ********************************/
	/***************************************************************************/
	protected function buildSubmittedValues(){
		if(have($this->form_array) and have($_POST)){ foreach($_POST as $pkey => $pvalue){ $form_matches = array(); preg_match('/^form\_([0-9]+)\_/i', $pkey, $form_matches); $form_id = have($form_matches[1]) ? $form_matches[1] : null; $field_matches = array(); preg_match('/^form\_[0-9]+\_field_([0-9]+)/i', $pkey, $field_matches); $field_id = have($field_matches[1]) ? $field_matches[1] : null; if(have($form_id) and have($field_id)){ $this->form_array[$form_id]['fields'][$field_id]['submitted_value'] = $pvalue; } } }
	}
	
	/***************************************************************************/
	/****************** GO FIND THE EMAIL **************************************/
	/***************************************************************************/
	protected function goFindEmailAmongSubmittedValues($form_id = null){
		if(!have($this->form_array[$form_id]['fields'])){ return false; }
		foreach($this->form_array[$form_id]['fields'] as $field_id => $field_array){ if(have($field_array['submitted_value']) and preg_match('/^[^@]+@[^@]+\.[a-z]{2,4}$/i', $field_array['submitted_value'])){ return $field_array['submitted_value']; } }
		return false;
	}

	/***************************************************************************/
	/****************** GO FIND THE NAME ***************************************/
	/***************************************************************************/	
	protected function goFindNameAmongSubmittedValues($form_id = null){
		if(!have($this->form_array[$form_id]['fields'])){ return false; }
		foreach($this->form_array[$form_id]['fields'] as $field_id => $field_array){ if(have($field_array['submitted_value']) and preg_match('/name/i', $field_array['dzpro_form_field_label'])){ return $field_array['submitted_value']; } }
		return false;	
	}

	/***************************************************************************/
	/****************** PREPARE EMAIL BODY *************************************/
	/***************************************************************************/		
	protected function prepareEmailBody($form_id = null, $amount_paid = null){
		if(!have($this->form_array[$form_id]['dzpro_form_success_email_html'])){ return false; }
		if(!have($this->form_array[$form_id]['fields'])){ return false; }
		$return_this_body = $this->form_array[$form_id]['dzpro_form_success_email_html']; foreach($this->form_array[$form_id]['fields'] as $field_id => $field_array){ $return_this_body = str_ireplace('[[[' . $field_array['dzpro_form_field_label'] . ']]]', $field_array['submitted_value'], $return_this_body); }
		if(have($amount_paid)){ $return_this_body = str_ireplace('[[[amount]]]', number_format(str_replace(',', null, $amount_paid), 2), $return_this_body); $return_this_body .= self::buildReceiptForFormPayment($amount_paid); }
		return $return_this_body;
	}

	/***************************************************************************/
	/****************** PREPARE PAYMENT RECEIPT ********************************/
	/***************************************************************************/	
	protected function buildReceiptForFormPayment($amount_paid = null){
		if(!have($amount_paid)){ return null; }
		return '<br /><br /><hr><strong>Payment Receipt</strong><p>Amount: $' . number_format($amount_paid, 2) . '<br /> Date: ' . date('m-d-Y') . '</p><hr /><br /><br />';
	}
	
	/***************************************************************************/
	/****************** SEND EMAIL IF NEEDED ***********************************/
	/***************************************************************************/
	protected function sendEmailUponSubmissionIfNeeded($form_id, $amount_paid = null){
		if(false === ($email_candidate = self::goFindEmailAmongSubmittedValues($form_id))){ return false; }
		if(false === ($recicient_name = self::goFindNameAmongSubmittedValues($form_id))){ return false; }
		if(false === ($email_body = self::prepareEmailBody($form_id, $amount_paid))){ return false; }
		if(!have($this->form_array[$form_id]['dzpro_form_name'])){ echo 'no form name'; return false; }
		addEmailToOutbox($recicient_name, $email_candidate, $this->form_array[$form_id]['dzpro_form_name'], $email_body);
		self::sendEmailToDepartments($form_id, $recicient_name, $amount_paid);
		return true;
	}

	/***************************************************************************/
	/****************** VALIDATE FORM ******************************************/
	/***************************************************************************/
	protected function sendEmailToDepartments($form_id = null, $recicient_name = null, $amount_paid = null){
		if(!have($form_id)){ return false; }
		if(!have($recicient_name)){ return false; }
		if(false === ($email_body = self::buildEmailBodyFromSubmittedValues($form_id, $amount_paid))){ return false; }
		$admins = mysql_query_flat(" SELECT * FROM dzpro_form_to_department LEFT JOIN dzpro_departments USING ( dzpro_department_id ) LEFT JOIN dzpro_admin_to_department USING ( dzpro_department_id ) LEFT JOIN dzpro_admins USING ( dzpro_admin_id ) WHERE dzpro_form_id = " . (int)$form_id . " GROUP BY dzpro_admin_id "); if(have($admins)){ foreach($admins as $admin){ addEmailToOutbox($admin['dzpro_admin_name'], $admin['dzpro_admin_username'], $this->form_array[$form_id]['dzpro_form_name'] . ' submission email [' . $recicient_name . ']', $email_body); } return true; }
		return false;
	}

	/***************************************************************************/
	/****************** BUILD EMAIL BODY FROM SUBMITTED VALUES *****************/
	/***************************************************************************/	
	protected function buildEmailBodyFromSubmittedValues($form_id = null, $amount_paid = null){
		if(!have($this->form_array[$form_id]['fields'])){ return false; }
		$return_body = null; foreach($this->form_array[$form_id]['fields'] as $field_id => $field_array){ $return_body .= '<strong>' . $field_array['dzpro_form_field_label'] . ':</strong> ' . $field_array['submitted_value'] . '<br />'; }
		if(have($amount_paid)){ $return_body .= self::buildReceiptForFormPayment($amount_paid); }
		return $return_body;
	}

	/***************************************************************************/
	/****************** VALIDATE FORM ******************************************/
	/***************************************************************************/	
	protected function validateFormSubmission(){
		if(have($this->form_array)){
			foreach($this->form_array as $form_id => $form_array){
				$all_fieds_checked = false;
				if(have($form_array['fields'])){
					$all_fieds_checked = true;
					foreach($form_array['fields'] as $field_key => $field_value){
						if($field_value['dzpro_form_field_required'] == 'required'){
							switch(true){
								
								/*********************************************************/
								/****************** TEXT FIELD ***************************/
								/*********************************************************/
								case($field_value['dzpro_form_field_type'] == 'text'):
									if(!have($field_value['submitted_value'])){ $all_fieds_checked = false; }
								break;
								
								/*********************************************************/
								/******************* NAME FIELD **************************/
								/*********************************************************/
								case($field_value['dzpro_form_field_type'] == 'name'):
									if(!have($field_value['submitted_value'])){ $all_fieds_checked = false; }
								break;
								
								/*********************************************************/
								/******************* PHONE FIELD *************************/
								/*********************************************************/
								case($field_value['dzpro_form_field_type'] == 'phone'):
									if(!preg_match('/[0-9\-\.\s]{7,15}/', $field_value['submitted_value'])){ $all_fieds_checked = false; }
								break;
								
								/*********************************************************/
								/******************* EMAIL FIELD *************************/
								/*********************************************************/
								case($field_value['dzpro_form_field_type'] == 'email'):
									if(!preg_match('/[^@]+@[^@]+\.[a-z]{2,3}/i', $field_value['submitted_value'])){ $all_fieds_checked = false; }
								break;
								
								/*********************************************************/
								/******************* CITY FIELD **************************/
								/*********************************************************/
								case($field_value['dzpro_form_field_type'] == 'city'):
									if(!have($field_value['submitted_value'])){ $all_fieds_checked = false; }
								break;
								
								/*********************************************************/
								/******************* STATE FIELD *************************/
								/*********************************************************/
								case($field_value['dzpro_form_field_type'] == 'state'):
									if(!have($field_value['submitted_value'])){ $all_fieds_checked = false; }
								break;

								/*********************************************************/
								/******************* COUNTRY FIELD ***********************/
								/*********************************************************/
								case($field_value['dzpro_form_field_type'] == 'country'):
									if(!have($field_value['submitted_value'])){ $all_fieds_checked = false; }
								break;
								
								/*********************************************************/
								/******************* ZIPCODE FIELD ***********************/
								/*********************************************************/
								case($field_value['dzpro_form_field_type'] == 'zipcode'):
									if(!preg_match('/^[0-9]{5}$|^[0-9]{5}[^0-9]{1}[0-9]{4}$/i', $field_value['submitted_value'])){ $all_fieds_checked = false; }
								break;
															
								/*********************************************************/
								/******************* CAPTCHA FIELD ***********************/
								/*********************************************************/
								case($field_value['dzpro_form_field_type'] == 'captcha'):
									if(!isset($_SESSION['captcha']) or (isset($_SESSION['captcha']) and strtolower($field_value['submitted_value']) != strtolower($_SESSION['captcha']))){ $all_fieds_checked = false; }
								break;	
								
							}						
						}
					}
				}
				if($all_fieds_checked){			
					if(false !== self::checkForPaymentRequest($form_id)){
						
						//get the amount for single and multiple payments
						if(have($this->credit_card_present) and is_numeric($this->credit_card_present[$form_id])){ $pay_this_amount = (have($this->credit_card_information[$form_id]['cc_quantity']) ? $this->credit_card_information[$form_id]['cc_quantity'] : 1) * $this->credit_card_present[$form_id]; }
					
						//get the amount for custom options
						if(have($this->credit_card_present)){ foreach($this->credit_card_present[$form_id] as $option_name => $option_amount){ if($this->credit_card_information[$form_id]['cc_option'] == md5($option_name . $option_amount)){ $pay_this_amount = $option_amount; } } }
						
						//set the amount
						if(have($this->credit_card_present) and !have($pay_this_amount) and is_numeric($this->credit_card_present[$form_id])){ $pay_this_amount = $this->credit_card_present[$form_id]; }
						
						//process payment
						$Payment = new Payment($this->db);
						$Payment->setPaymentMethod('authorize.net');
						$payment_response = $Payment->processPaymentRequest(
																				$pay_this_amount, 
																				$this->credit_card_information[$form_id]['cc_number'], 
																				$this->credit_card_information[$form_id]['cc_exp_year'], 
																				$this->credit_card_information[$form_id]['cc_exp_month'], 
																				$this->credit_card_information[$form_id]['cc_exp_cvv'], 
																				$this->credit_card_information[$form_id]['cc_name'], 
																				$this->credit_card_information[$form_id]['cc_address'], 
																				$this->credit_card_information[$form_id]['cc_city'], 
																				$this->credit_card_information[$form_id]['cc_state'], 
																				$this->credit_card_information[$form_id]['cc_zipcode']
																			);
						
						//submit form on success
						if(have($payment_response) and $payment_response['status'] == 'true'){
							self::submitTheForm($form_id, $pay_this_amount);
							addToIntelligenceStack('form submission', 'submitted with payment');
						}else{
							$this->form_array[$form_id]['problem_message'] = have($payment_response['message']) ? $payment_response['message'] : 'Your payment could not be completed.';
							addToIntelligenceStack('form submission', 'payment failed');
						}
						continue;
					}
					addToIntelligenceStack('form submission', 'submission successful');
					self::submitTheForm($form_id);
				}else{
					$this->form_array[$form_id]['problem_message'] = 'Some fields are invalid, please review your submission.';
					addToIntelligenceStack('form submission', 'submission failed');
				}
				continue;
			}
		}
	}

	/***************************************************************************/
	/****************** BUILD PAYMENT INFORMATION ARRAY ************************/
	/***************************************************************************/
	protected function buildPaymentInfoArray(){
		if(have($_POST)){
			foreach($_POST as $pkey => $pvalue){
				$form_id_match = array(); preg_match('/^form_([0-9]+)_/', $pkey, $form_id_match); $form_id = have($form_id_match[1]) ? $form_id_match[1] : null;
				if(have($form_id)){
					if(stripos($pkey, 'credit_card_option') > 0 and have($pvalue)){ $this->credit_card_information[$form_id]['cc_option'] = $pvalue; }
					if(stripos($pkey, 'credit_card_quantity') > 0 and have($pvalue) and $pvalue > 1){ $this->credit_card_information[$form_id]['cc_quantity'] = (int)$pvalue; }
					if(stripos($pkey, 'credit_card_name') > 0 and have($pvalue)){ $this->credit_card_information[$form_id]['cc_name'] = $pvalue; }
					if(stripos($pkey, 'credit_card_address') > 0 and have($pvalue)){ $this->credit_card_information[$form_id]['cc_address'] = $pvalue; }
					if(stripos($pkey, 'credit_card_city') > 0 and have($pvalue)){ $this->credit_card_information[$form_id]['cc_city'] = $pvalue; }
					if(stripos($pkey, 'credit_card_state') > 0 and have($pvalue)){ $this->credit_card_information[$form_id]['cc_state'] = $pvalue; }
					if(stripos($pkey, 'credit_card_zipcode') > 0 and have($pvalue)){ $this->credit_card_information[$form_id]['cc_zipcode'] = $pvalue; }
					if(stripos($pkey, 'credit_card_number') > 0 and have($pvalue)){ $this->credit_card_information[$form_id]['cc_number'] = $pvalue; }
					if(stripos($pkey, 'credit_card_expiration_month') > 0 and have($pvalue)){ $this->credit_card_information[$form_id]['cc_exp_month'] = $pvalue; }
					if(stripos($pkey, 'credit_card_expiration_year') > 0 and have($pvalue)){ $this->credit_card_information[$form_id]['cc_exp_year'] = $pvalue; }
					if(stripos($pkey, 'credit_card_sec_code') > 0 and have($pvalue)){ $this->credit_card_information[$form_id]['cc_exp_cvv'] = $pvalue; }	
				}
			}
		}	
	}
	
	/***************************************************************************/
	/****************** CHECK FOR PAYMENT FORM *********************************/
	/***************************************************************************/	
	protected function checkForPaymentRequest($form_id = null){
		if(!have($form_id)){ return false; }
		self::buildPaymentInfoArray();
		if(isset($this->credit_card_information[$form_id]) and have($this->credit_card_information[$form_id]) and isset($this->credit_card_present[$form_id]) and have($this->credit_card_present[$form_id])){ return true; }
		return false;
	}
	
	/***************************************************************************/
	/****************** SUBMIT THE FORM ****************************************/
	/***************************************************************************/		
	protected function submitTheForm($form_id = null, $amount_paid = null){
		if(!have($form_id)){ return false; }
		self::sendEmailUponSubmissionIfNeeded($form_id, $amount_paid);
		@mysql_query(" INSERT INTO dzpro_submissions ( dzpro_identity_id, dzpro_user_id, dzpro_submission_name, dzpro_submission_description, dzpro_submission_amount, dzpro_submission_date_added ) VALUES ( '" . mysql_real_escape_string(getVisitorId()) . "', '" . mysql_real_escape_string(getUserId()) . "', '" . mysql_real_escape_string($this->form_array[$form_id]['dzpro_form_name']) . "', '" . mysql_real_escape_string($this->form_array[$form_id]['dzpro_form_description']) . "', '" . mysql_real_escape_string($amount_paid) . "', NOW() ) "); $submission_id = mysql_insert_id(); if(have($submission_id)){ $this->page_form_status[$form_id] = true; if(have($this->form_array[$form_id]['fields'])){ foreach($this->form_array[$form_id]['fields'] as $field_key => $field_array){ @mysql_query(" INSERT INTO dzpro_submission_values ( dzpro_submission_id, dzpro_submission_value_name, dzpro_submission_value_value, dzpro_submission_value_type, dzpro_submission_value_date_added ) VALUES ( '" . mysql_real_escape_string($submission_id) . "', '" . mysql_real_escape_string($field_array['dzpro_form_field_label']) . "', '" . mysql_real_escape_string($field_array['submitted_value']) . "', '" . mysql_real_escape_string($field_array['dzpro_form_field_type']) . "', NOW() ) "); } return true; } }
		return false;
	}

	/***************************************************************************/
	/****************** GET JAVASCRIPT BLOCK ***********************************/
	/***************************************************************************/	
	protected function getJavascriptBlock($form_array){
		if(have($form_array['fields'])){
		?>
							<script type="text/javascript">
								<!--
								
								/**********************************/
								/****** check input values ********/
								/**********************************/
								function checkInput<?=(int)$form_array['dzpro_form_id']?>(name, value, title){				
									switch(name){
										<?php foreach($form_array['fields'] as $field_id => $field_array){ ?>
											<?php switch(true){ 
													
													/*************************************************************/
													/*********************** TEXT FIELD **************************/
													/*************************************************************/
													case($field_array['dzpro_form_field_required'] == 'required' and $field_array['dzpro_form_field_type'] == 'text'): 
												?>
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_field_<?=$field_id?>':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value != ''){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;	
												<?php 
													break; 

													/*************************************************************/
													/*********************** NAME FIELD **************************/
													/*************************************************************/
													case($field_array['dzpro_form_field_required'] == 'required' and $field_array['dzpro_form_field_type'] == 'name'): 
												?>
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_field_<?=$field_id?>':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value != ''){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;	
												<?php 
													break; 
													
													/*************************************************************/
													/*********************** CITY FIELD **************************/
													/*************************************************************/
													case($field_array['dzpro_form_field_required'] == 'required' and $field_array['dzpro_form_field_type'] == 'city'): 
												?>
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_field_<?=$field_id?>':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value != ''){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;	
												<?php 
													break; 	
													
													/*************************************************************/
													/*********************** CAPTCHA FIELD ***********************/
													/*************************************************************/
													case($field_array['dzpro_form_field_type'] == 'captcha'): 
												?>
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_field_<?=$field_id?>':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value != ''){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;	
												<?php 
													break; 												

													/*************************************************************/
													/*********************** STATE FIELD *************************/
													/*************************************************************/
													case($field_array['dzpro_form_field_required'] == 'required' and $field_array['dzpro_form_field_type'] == 'state'): 
												?>
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_field_<?=$field_id?>':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value.match(/^[a-z]{2}$/i)){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;	
												<?php 
													break; 

													/*************************************************************/
													/*********************** COUNTRY FIELD ***********************/
													/*************************************************************/
													case($field_array['dzpro_form_field_required'] == 'required' and $field_array['dzpro_form_field_type'] == 'country'): 
												?>
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_field_<?=$field_id?>':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value.match(/^[a-z]{2}$/i)){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;
										
												<?php 
													break; 
													
													/*************************************************************/
													/*********************** EMAIL FIELD *************************/
													/*************************************************************/
													case($field_array['dzpro_form_field_required'] == 'required' and $field_array['dzpro_form_field_type'] == 'email'): 
												?>
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_field_<?=$field_id?>':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value.match(/[^@]+@[^@]+\.[a-z]{2,3}/i)){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;
												<?php 
													break;
													
													/*************************************************************/
													/*********************** PHONE NUMBER FIELD ******************/
													/*************************************************************/
													case($field_array['dzpro_form_field_required'] == 'required' and $field_array['dzpro_form_field_type'] == 'phone'): 
												?>
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_field_<?=$field_id?>':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value.match(/[0-9\-\.\s]{7,15}/)){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;
												<?php 
													break;													

													/*************************************************************/
													/*********************** ZIPCODE FIELD ***********************/
													/*************************************************************/
													case($field_array['dzpro_form_field_required'] == 'required' and $field_array['dzpro_form_field_type'] == 'zipcode'): 
												?>
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_field_<?=$field_id?>':												
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value.match(/^[0-9]{5}$|^[0-9]{5}[^0-9]{1}[0-9]{4}$/i)){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;
												<?php 
													break;	
													
													/*************************************************************/
													/*********************** CREDIT CARD INFO ********************/
													/*************************************************************/
													case($field_array['dzpro_form_field_type'] == 'creditcard'): 
												?>
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_credit_card_name':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value != ''){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_credit_card_address':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value != ''){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_credit_card_city':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value != ''){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_credit_card_state':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value.match(/^[a-z]{2}$/i)){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_credit_card_zipcode':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value.match(/^[0-9]{5}$|^[0-9]{5}[^0-9]{1}[0-9]{4}$/i)){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_credit_card_number':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value.match(/^[0-9]{4}[^0-9]?[0-9]{4}[^0-9]?[0-9]{4}[^0-9]?[0-9]{2,4}$/)){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_credit_card_expiration_month':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value.match(/^[0-9]{1,2}$/)){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_credit_card_expiration_year':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value.match(/^[0-9]{4}$/)){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_credit_card_sec_code':	
											if(value == '' || value == title){
												return 'false';		
											}else{
												if(value.match(/^[0-9]{3,4}$$/)){
													return 'true';
												}else{
													return 'false';
												}
											}
										break;
												<?php 
													
													/*************************************************************/
													/*********************** DEFAULT FIELD ***********************/
													/*************************************************************/ 
													default: 
												?>
										case 'form_<?=(int)$form_array['dzpro_form_id']?>_field_<?=$field_id?>':	
											return 'neutral';
										break;
												<?php 
													break; 
												} 
											?>	
					
										<?php } ?>							
										default:
											return 'neutral';
										break;
									}
								}

								/***************************************/
								/****** prepare form submission ********/
								/***************************************/
								function prepareSubmit<?=(int)$form_array['dzpro_form_id']?>(){
									$('input, textarea', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').each(function(){
										if($(this).val() == $(this).attr('title')){
											$(this).val('');
										}
									});
									if(checkFormValidity<?=(int)$form_array['dzpro_form_id']?>()){
										$.blockUI();
										$('form', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').submit();				
									}else{
										alert('One or more fields are invalid, please review your <?=strtolower(prepareTag($form_array['dzpro_form_name']))?> submission. Invalid fields are outlined in red.');
									}
								}

								/**********************************/
								/****** check form validity *******/
								/**********************************/
								function checkFormValidity<?=(int)$form_array['dzpro_form_id']?>(){
									
									//assume no problems are found
									var found_problems = false;
									
									//check input and text areas
									$('input[type=text], input[type=password], textarea', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').each(function(){
										var input_name = $(this).attr('name');
										var input_val = $(this).val();
										var input_title = $(this).attr('title');
										var input_status = checkInput<?=(int)$form_array['dzpro_form_id']?>(input_name, input_val, input_title);
										handleInputStatus<?=(int)$form_array['dzpro_form_id']?>(input_name, input_status);
										if(input_status == 'false'){ found_problems = true; }
									});
									
									//check select boxes
									$('select', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').each(function(){
										var input_name = $(this).attr('name');
										var input_val = $(this).children('option:selected').val();
										var input_title = $(this).attr('title');
										var input_status = checkInput<?=(int)$form_array['dzpro_form_id']?>(input_name, input_val, input_title);
										handleInputStatus<?=(int)$form_array['dzpro_form_id']?>(input_name, input_status);
										if(input_status == 'false'){ found_problems = true; }
									});
									
									//return boolean
									if(found_problems){ return false; }
									return true;
													
								}
								
								/**********************************/
								/****** handle input status *******/
								/**********************************/								
								function handleInputStatus<?=(int)$form_array['dzpro_form_id']?>(input_name, input_status){
									if(input_status == 'false'){
										$('.message_' + input_name + ', input[name=' + input_name + '], textarea[name=' + input_name + '], select[name=' + input_name + ']', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').addClass('error').removeClass('success').parents('.clearfix').addClass('error').removeClass('success');
									}
									if(input_status == 'true'){
										$('.message_' + input_name + ', input[name=' + input_name + '], textarea[name=' + input_name + '], select[name=' + input_name + ']', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').removeClass('error').addClass('success').parents('.clearfix').removeClass('error').addClass('success');
									}
									if(input_status == 'empty'){
										$('.message_' + input_name + ', input[name=' + input_name + '], textarea[name=' + input_name + '], select[name=' + input_name + ']', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').removeClass('error').removeClass('success').parents('.clearfix').removeClass('error').removeClass('success');
									}
								}

								$().ready(function(){

									/***************************************/
									/****** title val swaps ****************/
									/***************************************/
									$('input[type=text], input[type=password], textarea', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').each(function(){
										if($(this).attr('readonly') == true){ $(this).addClass('disabled'); }
										if($(this).val() == ''){ $(this).val($(this).attr('title')); }else{ if($(this).val() == $(this).attr('title')){ $(this).removeClass('touched').addClass('temp'); }else{ $(this).addClass('touched').removeClass('temp'); } }
									});
									$('input[type=text], input[type=password], textarea', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').focus(function(){
										$('.on').removeClass('on');	$('.id_' + $(this).attr('name')).addClass('on');
										if($(this).val() == $(this).attr('title')){ $(this).val('').removeClass('touched').removeClass('temp'); }else{ if($(this).val() == ''){ $(this).removeClass('touched'); }else{ $(this).addClass('touched'); } }
									});
									$('input[type=text], input[type=password], textarea', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').blur(function(){
										if($(this).val() == ''){ $(this).val($(this).attr('title')).removeClass('touched');	}else{ if($(this).val() == $(this).attr('title')){ $(this).removeClass('touched'); }else{ $(this).addClass('touched'); } }
									});

									/***************************************/
									/****** select focus / blur actions ****/
									/***************************************/
									$('select', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').focus(function(){
										$('.on').removeClass('on'); $('.id_' + $(this).attr('name')).addClass('on');
									});
									$('select', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').blur(function(){
										$('.on').removeClass('on');
									});

									/***************************************/
									/****** focus on field from click ******/
									/***************************************/
									$('.clearfix', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').click(function(event){
										if(event.target.nodeName != 'INPUT' && event.target.nodeName != 'SELECT' && event.target.nodeName != 'OPTION' && event.target.nodeName != 'TEXTAREA'){ 
											$(this).find('select:first, input:first, textarea:first').focus(); 
										}
									});
									
									/**********************************/
									/****** check input trigger *******/
									/**********************************/
									$('input[type=text], input[type=password], textarea', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').keyup(function(){
										var input_name = $(this).attr('name');
										var input_val = $(this).val();
										var input_title = $(this).attr('title');
										var input_status = checkInput<?=(int)$form_array['dzpro_form_id']?>(input_name, input_val, input_title);
										handleInputStatus<?=(int)$form_array['dzpro_form_id']?>(input_name, input_status);
									});
									$('select', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').change(function(){
										var input_name = $(this).attr('name');
										var input_val = $(this).children('option:selected').val();
										var input_title = $(this).attr('title');
										var input_status = checkInput<?=(int)$form_array['dzpro_form_id']?>(input_name, input_val, input_title);
										handleInputStatus<?=(int)$form_array['dzpro_form_id']?>(input_name, input_status);
									});
									$('input[type=text], input[type=password], textarea', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').blur(function(){
										var input_name = $(this).attr('name');
										var input_val = $(this).val();
										var input_title = $(this).attr('title');
										var input_status = checkInput<?=(int)$form_array['dzpro_form_id']?>(input_name, input_val, input_title);
										handleInputStatus<?=(int)$form_array['dzpro_form_id']?>(input_name, input_status);
									});
									$('select', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').blur(function(){
										var input_name = $(this).attr('name');
										var input_val = $(this).children('option:selected').val();
										var input_title = $(this).attr('title');
										var input_status = checkInput<?=(int)$form_array['dzpro_form_id']?>(input_name, input_val, input_title);
										handleInputStatus<?=(int)$form_array['dzpro_form_id']?>(input_name, input_status);
									});
				
									/*************************************************/
									/****** submit form when 'enter' is pressed ******/
									/*************************************************/
									var last_pressed_key = ''; $('input[type=text]', '#page_form_<?=(int)$form_array['dzpro_form_id']?>').keyup(function(event){ if(event.keyCode == 13 && last_pressed_key != 37 && last_pressed_key != 38 && last_pressed_key != 39 && last_pressed_key != 40){ prepareSubmit<?=(int)$form_array['dzpro_form_id']?>(); } last_pressed_key = event.keyCode; });		
																
								});
								//-->
							</script>
		<?php
		}
	}
	
	/***************************************************************************/
	/****************** BUILD THE FORM *****************************************/
	/***************************************************************************/	
	public function buildForm(){
		if(have($this->form_array)){
			foreach($this->form_array as $form_id => $form_array){
				if(isset($this->page_form_status[$form_id]) and $this->page_form_status[$form_id] == true){
					?>
						<div class="show_message radius_3_ie">Submission Successful. Need to <a href="?" title="Start Fresh">start fresh</a>?</div>
						<?=$this->form_array[$form_id]['dzpro_form_success_html']?>
						
					<?php
				}
				if(have($form_array['fields']) and !(isset($this->page_form_status[$form_id]) and $this->page_form_status[$form_id] == true)){
					?>
						<div id="page_form_<?=(int)$form_id?>" class="page_form">
							<?=self::getJavascriptBlock($form_array)?>
					<?php if(isset($this->form_array[$form_id]['problem_message']) and have($this->form_array[$form_id]['problem_message'])){ ?>
							<div class="problem_mssg radius_3_ie">
								<?=$this->form_array[$form_id]['problem_message']?>
							</div>
							<div style="height: 10px;"><!-- spacer --></div>
					<?php } ?>
							<form method="post" action="">
								<fieldset>
									<legend><?=prepareStringHtml($form_array['dzpro_form_name'])?></legend>
					<?php
					foreach($form_array['fields'] as $field_id => $field_array){
						switch($field_array['dzpro_form_field_type']){

							/******************************************************************************************/
							/******************************** TEXT FIELD **********************************************/
							/******************************************************************************************/
							case 'text': //form option
								?>
									<div class="clearfix id_form_<?=(int)$form_id?>_field_<?=$field_id?>" id="id_form_<?=(int)$form_id?>_field_<?=$field_id?>">
										<label for="form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_label'])?> <?=($field_array['dzpro_form_field_required'] == 'required') ? '<span title="This is a required field">*</span>' : null?></label>
										<div class="input">
											<div class="field_status"><!-- block --></div>
											<input name="form_<?=(int)$form_id?>_field_<?=$field_id?>" id="form_<?=(int)$form_id?>_field_<?=$field_id?>" type="text" value="<?=prepareTag(isset($field_array['submitted_value']) ? $field_array['submitted_value'] : null)?>" title="<?=prepareTag($field_array['dzpro_form_field_place_holder'])?>" />
											<span class="help-inline message_column message_form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_message'])?></span>
										</div>
									</div>		
								<?php							
							break;	
							
							/******************************************************************************************/
							/******************************** TEXTAREA ************************************************/
							/******************************************************************************************/
							case 'textarea': //form option
								?>
									<div class="clearfix id_form_<?=(int)$form_id?>_field_<?=$field_id?>" id="id_form_<?=(int)$form_id?>_field_<?=$field_id?>">
										<label for="form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_label'])?> <?=($field_array['dzpro_form_field_required'] == 'required') ? '<span title="This is a required field">*</span>' : null?></label>
										<div class="input">
											<div class="field_status"><!-- block --></div>
											<textarea name="form_<?=(int)$form_id?>_field_<?=$field_id?>" id="form_<?=(int)$form_id?>_field_<?=$field_id?>" rows="3" cols="20"></textarea>
											<span class="help-inline message_column message_form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_message'])?></span>
										</div>
									</div>
								<?php							
							break;
							
							/******************************************************************************************/
							/******************************** NAME FIELD **********************************************/
							/******************************************************************************************/
							case 'name': //form option
								?>
									<div class="clearfix id_form_<?=(int)$form_id?>_field_<?=$field_id?>" id="id_form_<?=(int)$form_id?>_field_<?=$field_id?>">
										<label for="form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_label'])?> <?=($field_array['dzpro_form_field_required'] == 'required') ? '<span title="This is a required field">*</span>' : null?></label>
										<div class="input">
											<div class="field_status"><!-- block --></div>
											<input name="form_<?=(int)$form_id?>_field_<?=$field_id?>" id="form_<?=(int)$form_id?>_field_<?=$field_id?>" type="text" value="<?=prepareTag(isset($field_array['submitted_value']) ? $field_array['submitted_value'] : ((activeUserSession()) ? getUserName() : null))?>" title="<?=prepareStringHtmlFlat($field_array['dzpro_form_field_place_holder'])?>" />
											<span class="help-inline message_column message_form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_message'])?></span>
										</div>
									</div>
								<?php							
							break;

							/******************************************************************************************/
							/******************************** EMAIL FIELD *********************************************/
							/******************************************************************************************/
							case 'email': //form option
								?>
									<div class="clearfix id_form_<?=(int)$form_id?>_field_<?=$field_id?>" id="id_form_<?=(int)$form_id?>_field_<?=$field_id?>">
										<label for="form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_label'])?> <?=($field_array['dzpro_form_field_required'] == 'required') ? '<span title="This is a required field">*</span>' : null?></label>
										<div class="input">
											<div class="field_status"><!-- block --></div>
											<input name="form_<?=(int)$form_id?>_field_<?=$field_id?>" id="form_<?=(int)$form_id?>_field_<?=$field_id?>" type="text" value="<?=prepareTag(isset($field_array['submitted_value']) ? $field_array['submitted_value'] : ((activeUserSession()) ? getUserEmail() : null))?>" title="<?=prepareStringHtmlFlat($field_array['dzpro_form_field_place_holder'])?>" />
											<span class="help-inline message_column message_form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_message'])?></span>
										</div>
									</div>
								<?php							
							break;
							
							/******************************************************************************************/
							/******************************** PASSWORD FIELD ******************************************/
							/******************************************************************************************/
							case 'password': //form option
								?>
									<div class="clearfix id_form_<?=(int)$form_id?>_field_<?=$field_id?>" id="id_form_<?=(int)$form_id?>_field_<?=$field_id?>">
										<label for="form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_label'])?> <?=($field_array['dzpro_form_field_required'] == 'required') ? '<span title="This is a required field">*</span>' : null?></label>
										<div class="input">
											<div class="field_status"><!-- block --></div>
											<input name="form_<?=(int)$form_id?>_field_<?=$field_id?>" id="form_<?=(int)$form_id?>_field_<?=$field_id?>" type="password" value="" />
											<span class="help-inline message_column message_form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_message'])?></span>
										</div>
									</div>
								<?php							
							break;
							
							/******************************************************************************************/
							/******************************** CAPTCHA FIELD *******************************************/
							/******************************************************************************************/
							case 'captcha': //form option
								?>
									<div class="clearfix id_form_<?=(int)$form_id?>_field_<?=$field_id?>" id="id_form_<?=(int)$form_id?>_field_<?=$field_id?>">
										<label for="form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_label'])?> <span title="This is a required field">*</span></label>
										<div class="input">
											<div class="field_status"><!-- block --></div>
											<div class="inline-inputs">
												<table cellpadding="0" cellspacing="0" style="width: auto;">
													<tbody>
														<tr>
															<td style="vertical-align: middle; padding: 0 7px 0 0; border: none;">
																<img src="/assets/captcha/generate.php" alt="captcha" style="height: 26px; -webkit-border-radius: 0 3px 3px 0; -moz-border-radius: 0 3px 3px 0; border-radius: 0 3px 3px 0; border-collapse: collapse; border: 1px solid #eee; border: 1px solid rgba(0, 0, 0, 0.05);" />
															</td>
															<td style="vertical-align: middle; padding: 0; border: none;">
																<input name="form_<?=(int)$form_id?>_field_<?=$field_id?>" id="form_<?=(int)$form_id?>_field_<?=$field_id?>" type="text" value="" title="<?=prepareStringHtmlFlat($field_array['dzpro_form_field_place_holder'])?>" class="small" />
															</td>
														</tr>
													</tbody>
												</table>
											</div>
										</div>
									</div>
								<?php							
							break;
							
							/******************************************************************************************/
							/******************************** PHONE FIELD *********************************************/
							/******************************************************************************************/
							case 'phone': //form option
								?>
									<div class="clearfix id_form_<?=(int)$form_id?>_field_<?=$field_id?>" id="id_form_<?=(int)$form_id?>_field_<?=$field_id?>">
										<label for="form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_label'])?> <?=($field_array['dzpro_form_field_required'] == 'required') ? '<span title="This is a required field">*</span>' : null?></label>
										<div class="input">
											<div class="field_status"><!-- block --></div>
											<input name="form_<?=(int)$form_id?>_field_<?=$field_id?>" id="form_<?=(int)$form_id?>_field_<?=$field_id?>" type="text" value="<?=prepareTag(isset($field_array['submitted_value']) ? $field_array['submitted_value'] : getUserData('phone'))?>" title="<?=prepareStringHtmlFlat($field_array['dzpro_form_field_place_holder'])?>" />
											<span class="help-inline message_column message_form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_message'])?></span>
										</div>
									</div>
								<?php							
							break;
							
							/******************************************************************************************/
							/******************************** ZIPCODE FIELD *******************************************/
							/******************************************************************************************/
							case 'zipcode': //form option
								?>
									<div class="clearfix id_form_<?=(int)$form_id?>_field_<?=$field_id?>" id="id_form_<?=(int)$form_id?>_field_<?=$field_id?>">
										<label for="form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_label'])?> <?=($field_array['dzpro_form_field_required'] == 'required') ? '<span title="This is a required field">*</span>' : null?></label>
										<div class="input">
											<div class="field_status"><!-- block --></div>
											<input name="form_<?=(int)$form_id?>_field_<?=$field_id?>" id="form_<?=(int)$form_id?>_field_<?=$field_id?>" type="text" value="<?=prepareTag(isset($field_array['submitted_value']) ? $field_array['submitted_value'] : getUserData('zip'))?>" title="<?=prepareStringHtmlFlat($field_array['dzpro_form_field_place_holder'])?>" />
											<span class="help-inline message_column message_form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_message'])?></span>
										</div>
									</div>
								<?php							
							break;
							
							/******************************************************************************************/
							/******************************** CITY FIELD **********************************************/
							/******************************************************************************************/
							case 'city': //form option
								?>
									<div class="clearfix id_form_<?=(int)$form_id?>_field_<?=$field_id?>" id="id_form_<?=(int)$form_id?>_field_<?=$field_id?>">
										<label for="form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_label'])?> <?=($field_array['dzpro_form_field_required'] == 'required') ? '<span title="This is a required field">*</span>' : null?></label>
										<div class="input">
											<div class="field_status"><!-- block --></div>
											<input name="form_<?=(int)$form_id?>_field_<?=$field_id?>" id="form_<?=(int)$form_id?>_field_<?=$field_id?>" type="text" value="<?=prepareTag(isset($field_array['submitted_value']) ? $field_array['submitted_value'] : getUserData('city'))?>" title="<?=prepareStringHtmlFlat($field_array['dzpro_form_field_place_holder'])?>" />
											<span class="help-inline message_column message_form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_message'])?></span>
										</div>
									</div>
								<?php							
							break;

							/******************************************************************************************/
							/******************************** STATE FIELD *********************************************/
							/******************************************************************************************/
							case 'state': //form option
								?>
									<div class="clearfix id_form_<?=(int)$form_id?>_field_<?=$field_id?>" id="id_form_<?=(int)$form_id?>_field_<?=$field_id?>">
										<label for="form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_label'])?> <?=($field_array['dzpro_form_field_required'] == 'required') ? '<span title="This is a required field">*</span>' : null?></label>
										<div class="input">
											<div class="field_status"><!-- block --></div>
											<?=printStateSelectBox('form_' . (int)$form_id . '_field_' . (int)$field_id, array(), (activeUserSession() ? getUserData('state') : null))?>
											<span class="help-inline message_column message_form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_message'])?></span>
										</div>
									</div>
								<?php							
							break;

							/******************************************************************************************/
							/******************************** COUNTRY FIELD *******************************************/
							/******************************************************************************************/
							case 'country': //form option
								?>
									<div class="clearfix id_form_<?=(int)$form_id?>_field_<?=$field_id?>" id="id_form_<?=(int)$form_id?>_field_<?=$field_id?>">
										<label for="form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_label'])?> <?=($field_array['dzpro_form_field_required'] == 'required') ? '<span title="This is a required field">*</span>' : null?></label>
										<div class="input">
											<div class="field_status"><!-- block --></div>
											<?=printCountrySelectBox('form_' . (int)$form_id . '_field_' . (int)$field_id, array(), (activeUserSession() ? getUserData('country') : null))?>
											<span class="help-inline message_column message_form_<?=(int)$form_id?>_field_<?=$field_id?>"><?=prepareStringHtmlFlat($field_array['dzpro_form_field_message'])?></span>
										</div>
									</div>
								<?php							
							break;

							/******************************************************************************************/
							/******************************** CREDIT CARD NUMBER **************************************/
							/******************************************************************************************/
							case 'creditcard': //form option
								?>
									<div class="id_form_<?=(int)$form_id?>_credit_card_quantity id_form_<?=(int)$form_id?>_credit_card_name id_form_<?=(int)$form_id?>_credit_card_address id_form_<?=(int)$form_id?>_credit_card_city id_form_<?=(int)$form_id?>_credit_card_state id_form_<?=(int)$form_id?>_credit_card_zipcode id_form_<?=(int)$form_id?>_credit_card_number form_<?=(int)$form_id?>_credit_card_expiration_month form_<?=(int)$form_id?>_credit_card_expiration_year id_form_<?=(int)$form_id?>_credit_card_sec_code">
									<?php if($field_array['dzpro_form_field_multiple'] == 'multiple'){ ?>
										<div class="clearfix" id="id_form_<?=(int)$form_id?>_credit_card_quantity">
											<label for="form_<?=(int)$form_id?>_credit_card_quantity">Choose Quantity</label>
											<div class="input">
												<div class="field_status"><!-- block --></div>
												<?php $payment_options_array = array(); for($n = 1; $n <= 9; $n++){ $payment_options_array[(int)$n] = (int)$n . ' ($' . number_format($n * $field_array['dzpro_form_field_amount'], 2) . ')'; } ?>
												<?=printSelectBox($payment_options_array, 'form_' . (int)$form_id . '_credit_card_quantity')?>
												<span class="help-inline">Choose quantity</span>
											</div>
										</div>
									<?php }elseif($field_array['dzpro_form_field_multiple'] == 'single'){ ?>
										<div class="clearfix" id="id_form_<?=(int)$form_id?>_credit_card_quantity">
											<label for="form_<?=(int)$form_id?>_credit_card_quantity">Amount Due</label>
											<div class="input">
												<span style="font-size: 24px;">$<?=number_format($field_array['dzpro_form_field_amount'], 2)?></span>
											</div>
										</div>
									<?php }elseif($field_array['dzpro_form_field_multiple'] == 'custom options' and have($this->credit_card_present[$form_id]) and is_array($this->credit_card_present[$form_id])){ ?>
										<div class="clearfix" id="id_form_<?=(int)$form_id?>_credit_card_quantity">
											<label for="form_<?=(int)$form_id?>_credit_card_option">Choose Option</label>
											<div class="input">
												<div class="field_status"><!-- block --></div>
												<select name="form_<?=(int)$form_id?>_credit_card_option" id="form_<?=(int)$form_id?>_credit_card_quantity">
													<?php foreach($this->credit_card_present[$form_id] as $option_name => $option_price){ ?>
													<option value="<?=md5($option_name . $option_price)?>"><?=prepareStringHtml($option_name)?> ($<?=number_format($option_price, 2)?>)
													<?php } ?>
												</select>
												<span class="help-inline">Choose quantity</span>
											</div>
										</div>									
									<?php } ?>
										<div class="clearfix" id="id_form_<?=(int)$form_id?>_credit_card_name">
											<label for="form_<?=(int)$form_id?>_credit_card_name">Billing Name *</label>
											<div class="input">
												<div class="field_status"><!-- block --></div>
												<input name="form_<?=(int)$form_id?>_credit_card_name" id="form_<?=(int)$form_id?>_credit_card_name" type="text" title="First Last" value="<?=prepareTag(have($this->credit_card_information[(int)$form_id]['cc_name']) ? $this->credit_card_information[(int)$form_id]['cc_name'] : getUserData('name'))?>" />
												<span class="help-inline message_column message_form_<?=(int)$form_id?>_credit_card_name">Enter your billing name</span>
											</div>
										</div>
										<div class="clearfix" id="id_form_<?=(int)$form_id?>_credit_card_address">
											<label for="form_<?=(int)$form_id?>_credit_card_address">Billing Address *</label>
											<div class="input">
												<div class="field_status"><!-- block --></div>
												<input name="form_<?=(int)$form_id?>_credit_card_address" id="form_<?=(int)$form_id?>_credit_card_address" type="text" title="1234 Anystreet NE" value="<?=prepareTag(have($this->credit_card_information[(int)$form_id]['cc_address']) ? $this->credit_card_information[(int)$form_id]['cc_address'] : getUserData('address'))?>" />
												<span class="help-inline message_column message_form_<?=(int)$form_id?>_credit_card_address">Enter your billing street address</span>
											</div>
										</div>
										<div class="clearfix" id="id_form_<?=(int)$form_id?>_credit_card_city">
											<label for="form_<?=(int)$form_id?>_credit_card_city">Billing City *</label>
											<div class="input">
												<div class="field_status"><!-- block --></div>
												<input name="form_<?=(int)$form_id?>_credit_card_city" id="form_<?=(int)$form_id?>_credit_card_city" type="text" title="Anytown" value="<?=prepareTag(have($this->credit_card_information[(int)$form_id]['cc_city']) ? $this->credit_card_information[(int)$form_id]['cc_city'] : getUserData('city'))?>" />
												<span class="help-inline message_column message_form_<?=(int)$form_id?>_credit_card_city">Enter your billing city</span>
											</div>
										</div>	
										<div class="clearfix" id="id_form_<?=(int)$form_id?>_credit_card_state">
											<label for="form_<?=(int)$form_id?>_credit_card_state">Billing State *</label>
											<div class="input">
												<div class="field_status"><!-- block --></div>
												<?=printStateSelectBox('form_' . (int)$form_id . '_credit_card_state', array(), (activeUserSession() ? getUserData('state') : null))?>
												<span class="help-inline message_column message_form_<?=(int)$form_id?>_credit_card_state">Choose billing state</span>
											</div>
										</div>
										<div class="clearfix" id="id_form_<?=(int)$form_id?>_credit_card_zipcode">
											<label for="form_<?=(int)$form_id?>_credit_card_zipcode">Billing Zipcode *</label>
											<div class="input">
												<div class="field_status"><!-- block --></div>
												<input name="form_<?=(int)$form_id?>_credit_card_zipcode" id="form_<?=(int)$form_id?>_credit_card_zipcode" type="text" title="12345" value="<?=prepareTag(have($this->credit_card_information[(int)$form_id]['cc_zipcode']) ? $this->credit_card_information[(int)$form_id]['cc_zipcode'] : getUserData('zip'))?>" />
												<span class="help-inline message_column message_form_<?=(int)$form_id?>_credit_card_zipcode">Enter your billing zipcode</span>
											</div>
										</div>
										<div class="clearfix" id="id_form_<?=(int)$form_id?>_credit_card_number">
											<label for="form_<?=(int)$form_id?>_credit_card_number">Card Number *</label>
											<div class="input">
												<div class="field_status"><!-- block --></div>
												<input name="form_<?=(int)$form_id?>_credit_card_number" id="form_<?=(int)$form_id?>_credit_card_number" type="text" />
												<span class="help-inline message_column message_form_<?=(int)$form_id?>_credit_card_number">Enter your card number</span>
											</div>
										</div>		
										<div class="clearfix" id="id_form_<?=(int)$form_id?>_credit_card_expiration_year">
											<label for="form_<?=(int)$form_id?>_credit_card_expiration_month">Expiration Date *</label>
											<div class="input">
												<div class="field_status"><!-- block --></div>
												<div class="inline-inputs">
													<select name="form_<?=(int)$form_id?>_credit_card_expiration_month" id="form_<?=(int)$form_id?>_credit_card_expiration_month" style="width: 60px;">
														<option value="">--</option>
														<?php for($m = 1; $m <= 12; $m++){ ?>
														<option value="<?=(int)$m?>" <?php if(have($_POST['form_' . $form_id . '_credit_card_expiration_month']) and $_POST['form_' . $form_id . '_credit_card_expiration_month'] == $m){ ?>selected="selected"<?php } ?>><?=(int)$m?></option>
														<?php } ?>															
													</select>
													<select name="form_<?=(int)$form_id?>_credit_card_expiration_year" id="form_<?=(int)$form_id?>_credit_card_expiration_year" style="width: 80px;">
														<option value="">----</option>
														<?php for($y = date('Y'); $y <= date('Y') + 8; $y++){ ?>
														<option value="<?=(int)$y?>" <?php if(have($_POST['form_' . $form_id . '_credit_card_expiration_year']) and $_POST['form_' . $form_id . '_credit_card_expiration_year'] == $y){ ?>selected="selected"<?php } ?>><?=(int)$y?></option>
														<?php } ?>													
													</select>						
													<span class="help-inline message_column message_form_<?=(int)$form_id?>_credit_card_expiration_year">Enter expiration month and year</span>
												</div>
											</div>
										</div>
										<div class="clearfix" id="id_form_<?=(int)$form_id?>_credit_card_sec_code">
											<label for="form_<?=(int)$form_id?>_credit_card_sec_code">Security Code *</label>
											<div class="input">
												<div class="field_status"><!-- block --></div>
												<input class="mini" name="form_<?=(int)$form_id?>_credit_card_sec_code" id="form_<?=(int)$form_id?>_credit_card_sec_code" type="text" title="" />
												<span class="help-inline message_column message_form_<?=(int)$form_id?>_credit_card_sec_code">Enter code on back</span>
											</div>
										</div>			
									</div>
								<?php							
							break;
							
							/******************************************************************************************/
							/******************************** PDF, DOC UPLOAD FIELD ***********************************/
							/******************************************************************************************/
							case 'file': //form option
								?>
								
								<?php							
							break;																					

							/******************************************************************************************/
							/******************************** JPG, GIF, PNG IMAGE *************************************/
							/******************************************************************************************/
							case 'image': //form option
								?>
								
								<?php							
							break;
														
						}
					}
					?>
									<div class="actions" style="margin-top: 12px;">
										<input type="submit" value="<?=have($form_array['dzpro_form_submit_string']) ? prepareTag($form_array['dzpro_form_submit_string']) : 'Submit Form'?>" class="btn primary" onclick="javascript:prepareSubmit<?=(int)$form_id?>();return false;" />
								
									</div>
								</fieldset>
							</form>
						</div><!-- end page_form_<?=(int)$form_id?> -->
					<?php
				}			
			}
		}
	}
	

}
?>