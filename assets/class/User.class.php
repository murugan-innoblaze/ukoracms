<?php
class User { 
	
	/***************************************************************************************************************/
	/***************************************** BUILD CLASS *********************************************************/
	/***************************************************************************************************************/
	function __construct($db){
		/*************************************/
		/******** database connection ********/
		/*************************************/
		$this->db = $db;
		/*************************************/
		/******** failed mssg ****************/
		/*************************************/	
		$this->failed_mssg = null;
		/*************************************/
		/******** start a session ************/
		/*************************************/	
		assureSession();
		/*************************************/
		/******** logout user ****************/
		/*************************************/		
		if(isset($_GET['logout'])){ self::doLogout(); }
	}
	
	/***************************************************************************************************************/
	/***************************************** INTRODUCT NEW USER **************************************************/
	/***************************************************************************************************************/
	public function introduceNewUser($username = null, $email = null, $social_uids = array('facebook_uid' => null, 'google_uid' => null, 'twitter_uid' => null), $password = null){
		if(have($_POST['receive_notifications'])){ saveSubscriber($email, $username); }
		if(false === ($_SESSION['front-end-user'] = self::findExistingUser($username, $email, $social_uids, $password))){ 
			if(false !== ($_SESSION['front-end-user'] = self::insertNewUser($username, $email, $social_uids, $password))){ 
				self::sendWelcomeUserEmail(); 
				$_SESSION['account_just_created'] = true; 
				return true; 
			} 
		}
		if(activeUserSession()){ return true; }
		return false;
	}

	/***************************************************************************************************************/
	/***************************************** SEND WELCOME USER EMAIL *********************************************/
	/***************************************************************************************************************/	
	protected function sendWelcomeUserEmail(){
		$subject = 'Welcome to ' . SITE_NAME . '.';
		$body = '
		<div style="background-color: #f3f3f3; padding: 20px 0; text-align: center; line-height: 150%;">
			<div style="padding: 20px; font-family: Verdana; font-size: 12px; width: 560px; margin: 0 auto; text-align: left;">
				<table cellspacing="0" cellpadding="0" style="width: 100%;">
					<tbody>
						<tr>
							<td style="vertical-align: middle; padding: 0 30px 0 0;">
								<a href="http://' . HOST_NAME . '" title="' . SITE_NAME . '"><img src="http://' . HOST_NAME . '/assets/layout/ukora-logo-large.png" alt="' . SITE_NAME . '" /></a>
							</td>
							<td style="vertical-align: middle">
								<p><strong>Hi ' . getUserName() . ',</strong></p>
								<p>Welcome to ' . SITE_NAME . '! Ukora is a web-development company based in Milwaukee, WI. Here we are always looking forward to the next challenge.</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>';
		return addEmailToOutbox(getUserName(), getUserEmail(), $subject, $body);
	}
	
	/***************************************************************************************************************/
	/***************************************** CAPTURE CONNECT FIELDS **********************************************/
	/***************************************************************************************************************/	
	public function captureConnectFields($reason = null){
		self::clearConnectFields();
		if(have($reason) and !have($_SESSION['connect_failed_reason'])){ $_SESSION['connect_failed_reason'] = $reason; } //the first reason take presedence
		if(have($_POST)){ foreach($_POST as $field_key => $field_value){ if(!preg_match('/password/', $field_key) and !preg_match('/pw/', $field_key) and !preg_match('/captcha/', $field_key)){ $_SESSION['connect_capture'][$field_key] = $field_value; } } return true; }
		return false;
	}

	/***************************************************************************************************************/
	/***************************************** CAPTURE CONNECT FIELDS ONLY IF NO REASON IS CAUGHT ******************/
	/***************************************************************************************************************/	
	public function captureConnectFieldsFailover($reason = null){
		if(have($reason) and !have($_SESSION['connect_failed_reason'])){ $_SESSION['connect_failed_reason'] = $reason; } //the first reason take presedence
		if(have($_POST) and !have($_SESSION['connect_capture'])){ foreach($_POST as $field_key => $field_value){ if(!preg_match('/password/', $field_key) and !preg_match('/pw/', $field_key) and !preg_match('/captcha/', $field_key)){ $_SESSION['connect_capture'][$field_key] = $field_value; } } return true; }
		return false;
	}

	/***************************************************************************************************************/
	/***************************************** CLEAR CONNECT FIELDS ************************************************/
	/***************************************************************************************************************/
	public function clearConnectFields(){
		if(have($_SESSION['connect_capture'])){ $_SESSION['connect_capture'] = array(); unset($_SESSION['connect_capture']); }
		if(have($_SESSION['connect_failed_reason'])){ $_SESSION['connect_failed_reason'] = array(); unset($_SESSION['connect_failed_reason']); }
	}

	/***************************************************************************************************************/
	/***************************************** LOGIN USER **********************************************************/
	/***************************************************************************************************************/	
	public function loginUser($email = null, $password = null){
		if(empty($email) or empty($password)){ return false; }
		$result = @mysql_query("SELECT dzpro_user_id, dzpro_user_name, dzpro_user_email, dzpro_user_facebook_uid, dzpro_user_google_uid, dzpro_user_twitter_uid FROM dzpro_users WHERE dzpro_user_email = '" . mysql_real_escape_string($email) . "' AND dzpro_user_password = '" . mysql_real_escape_string(saltString($password)) . "' AND dzpro_user_password IS NOT NULL AND dzpro_user_email IS NOT NULL"); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ if(have($_POST['receive_notifications'])){ saveSubscriber($row['dzpro_user_email'], $row['dzpro_user_name']); } $_SESSION['front-end-user'] = $row; } mysql_free_result($result); self::clearConnectFields(); return true; }else{ self::captureConnectFields('Login could not be validated. If you don\'t already have an account with us just click \'Sign Up\' below to create an account.'); return false; }
	}
	
	/***************************************************************************************************************/
	/***************************************** FIND OR UPDATE USER *************************************************/
	/***************************************************************************************************************/
	private function findExistingUser($username = null, $email = null, $social_uids = array('facebook_uid' => null, 'google_uid' => null, 'twitter_uid' => null), $password = null){
		$update_sql = "
						UPDATE 
							dzpro_users
						SET 
							dzpro_user_last_modified = NOW()
							";
		if(!empty($username)){ $update_sql .= ",dzpro_user_name = '" . mysql_real_escape_string($username) . "'"; }
		if(isset($social_uids['facebook_uid']) and !empty($social_uids['facebook_uid'])){ $update_sql .= ",dzpro_user_facebook_uid = '" . mysql_real_escape_string($social_uids['facebook_uid']) . "'"; }
		if(isset($social_uids['google_uid']) and !empty($social_uids['google_uid'])){ $update_sql .= ",dzpro_user_google_uid = '" . mysql_real_escape_string($social_uids['google_uid']) . "'"; }
		if(isset($social_uids['twitter_uid']) and !empty($social_uids['twitter_uid'])){ $update_sql .= ",dzpro_user_twitter_uid = '" . mysql_real_escape_string($social_uids['twitter_uid']) . "'"; }
		if(isset($email) and !empty($email)){ $update_sql .= ",dzpro_user_email = '" . mysql_real_escape_string($email) . "'"; }
		$update_sql .= "
						WHERE 
						";
		if(isset($email) and !empty($email) and isset($password) and !empty($password)){
			$update_sql .= "
							(	
								dzpro_user_email IS NOT NULL
							AND
								dzpro_user_email = '" . mysql_real_escape_string($email) . "'
							AND 
								dzpro_user_password IS NOT NULL 
							AND 
								(
									dzpro_user_password = '" . mysql_real_escape_string(saltString($password)) . "'
								OR 
									(
										dzpro_user_password_prepend IS NOT NULL
									AND
										dzpro_user_password = MD5(CONCAT(dzpro_user_password_prepend, '" . mysql_real_escape_string($password) . "'))
									)
								)
							)
							";		
		}elseif(isset($email) and !empty($email) and empty($password) and ((isset($social_uids['facebook_uid']) and !empty($social_uids['facebook_uid'])) or (isset($social_uids['google_uid']) and !empty($social_uids['google_uid'])))){
			$update_sql .= "
							(	
								dzpro_user_email IS NOT NULL
							AND
								dzpro_user_email = '" . mysql_real_escape_string($email) . "'
							)
							";
		}elseif(isset($social_uids['twitter_uid']) and !empty($social_uids['twitter_uid'])){
			$update_sql .= " 
						 	(
								dzpro_user_twitter_uid IS NOT NULL
							AND 
								dzpro_user_twitter_uid = '" . mysql_real_escape_string($social_uids['twitter_uid']) . "'
							)
							";
		}
		$update_sql .= "
						LIMIT 
							1
						";
		mysql_query($update_sql);
		if(mysql_affected_rows() > 0){
			$sql = "
						SELECT 
							dzpro_user_id,
							dzpro_user_name,
							dzpro_user_email,
							dzpro_user_facebook_uid,
							dzpro_user_google_uid,
							dzpro_user_twitter_uid
						FROM 
							dzpro_users
						WHERE
							";
			if(isset($email) and !empty($email)){
				$sql .= "
								(	
									dzpro_user_email IS NOT NULL
								AND
									dzpro_user_email = '" . mysql_real_escape_string($email) . "'
								)
								";
			}elseif(isset($social_uids['twitter_uid']) and !empty($social_uids['twitter_uid'])){
				$sql .= " 
							 	(
									dzpro_user_twitter_uid IS NOT NULL
								AND 
									dzpro_user_twitter_uid = '" . mysql_real_escape_string($social_uids['twitter_uid']) . "'
								)
								";
			}
			$sql .= "
						LIMIT 
							1
					";
			$result = mysql_query($sql);
			if(mysql_num_rows($result) > 0){
				$return = array();
				while($row = mysql_fetch_assoc($result)){
					$return = $row;
				}
				return $return;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	/***************************************************************************************************************/
	/***************************************** INSERT NEW USER *****************************************************/
	/***************************************************************************************************************/
	private function insertNewUser($username = null, $email = null, $social_uids = array('facebook_uid' => null, 'google_uid' => null, 'twitter_uid' => null), $password = null){
		$got_social_id = false; foreach($social_uids as $social_uid){ if(!empty($social_uid)){ $got_social_id = true; } } if(false === $got_social_id and empty($password)){ return false; }
		if(null != self::getUserByEmail($email)){ $_SESSION['connect_capture_to_login'] = true; self::captureConnectFields('A user with email: ' . $email . ' already exists. Please login instead?'); return false; } if(!have($username)){ return false; }
		$insert_sql = "
						INSERT INTO 
							dzpro_users
						(
							dzpro_user_name,
							dzpro_user_email,
						";
		if(!empty($password)){ $insert_sql .= "dzpro_user_password,"; }
		$insert_sql .= "
							dzpro_user_facebook_uid,
							dzpro_user_google_uid,
							dzpro_user_twitter_uid,
							dzpro_user_date_added,
							dzpro_user_last_modified	
						) VALUES (	
							'" . mysql_real_escape_string($username) . "',
							'" . mysql_real_escape_string($email) . "',
							";
		if(!empty($password)){ $insert_sql .= "'" . mysql_real_escape_string(saltString($password)) . "',"; }
		$insert_sql .= "
							'" . mysql_real_escape_string($social_uids['facebook_uid']) . "',
							'" . mysql_real_escape_string($social_uids['google_uid']) . "',
							'" . mysql_real_escape_string($social_uids['twitter_uid']) . "',
							NOW(),
							NOW()
						)
					";
		mysql_query($insert_sql);
		if(mysql_insert_id() > 0){
			$user_array['dzpro_user_id'] = mysql_insert_id();
			$user_array['dzpro_user_name'] = $username;
			$user_array['dzpro_user_email'] = $email;
			$user_array['dzpro_user_facebook_uid'] = $social_uids['facebook_uid'];
			$user_array['dzpro_user_google_uid'] = $social_uids['google_uid'];
			$user_array['dzpro_user_twitter_uid'] = $social_uids['twitter_uid'];
			return $user_array;
		}else{
			return false;
		}
	}

	/***************************************************************************************************************/
	/***************************************** UPDATE USER TO AFFILIATED *******************************************/
	/***************************************************************************************************************/	
	public function setAffiliated($bool = false){
		if(!is_bool($bool)){ return false; } $affiliation_set = ($bool) ? 1 : 0; $_SESSION['front-end-user']['dzpro_user_affiliated'] = $bool;	
		@mysql_query("UPDATE dzpro_users SET dzpro_user_affiliated = " . (int)$affiliation_set . " WHERE dzpro_user_id = " . (int)$_SESSION['front-end-user']['dzpro_user_id']);
		return true;
	}

	/***************************************************************************************************************/
	/***************************************** GET USER BY EMAIL ***************************************************/
	/***************************************************************************************************************/		
	private function getUserByEmail($email = null){
		if(empty($email)){ return null; }
		$result = @mysql_query("SELECT * FROM dzpro_users WHERE dzpro_user_email = '" . mysql_real_escape_string($email) . "'"); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ return $row; } mysql_free_result($result); }
		return null;
	}
	
	/***************************************************************************************************************/
	/***************************************** SEND RESET EMAIL ****************************************************/
	/***************************************************************************************************************/	
	public function sendResetLink($email){
		if(empty($email)){ return null; }
		$user = self::getUserByEmail($email);
		if(isset($user['dzpro_user_email'])){
			$lostPasswordEmailHtml = '<div style="padding: 30px; margin: 15px; border: 1px solid black;"><h1>Reset Password</h1><p>Please click on the link below to reset your password.</p><p><a href="http://' . HOST_NAME . '/reset/' . md5($user['dzpro_user_email'] . SITE_SALT . $user['dzpro_user_password']) . '/" title="Reset Password Link">reset password link</a></p><p>If the above link doesn\'t work just follow the following url to reset your password.</p><p>http://' . HOST_NAME . '/reset/' . md5($user['dzpro_user_email'] . SITE_SALT . $user['dzpro_user_password']) . '/</p></div>';
			if(false !== addEmailToOutbox($user['dzpro_user_name'], $user['dzpro_user_email'], 'Reset Password', $lostPasswordEmailHtml)){ return 'send'; }
		}
		return null;
	}

	/***************************************************************************************************************/
	/***************************************** PASSWORD RESET VALIDATE *********************************************/
	/***************************************************************************************************************/
	public function validatePwResetRequest($reset_key){
		if(empty($reset_key)){ return null; }
		$result = @mysql_query("SELECT * FROM dzpro_users WHERE MD5(CONCAT(dzpro_user_email, '" . mysql_real_escape_string(SITE_SALT) . "', dzpro_user_password)) = '" . mysql_real_escape_string($reset_key) . "'"); if(mysql_num_rows($result) > 0){ return true; }
		return false;
	}

	/***************************************************************************************************************/
	/***************************************** UPDATE PASSWORD *****************************************************/
	/***************************************************************************************************************/	
	public function updateUserPassword($reset_key, $password){
		@mysql_query("UPDATE dzpro_users SET dzpro_user_password = '" . mysql_real_escape_string(saltString($password)) . "' WHERE MD5(CONCAT(dzpro_user_email, '" . mysql_real_escape_string(SITE_SALT) . "', dzpro_user_password)) = '" . mysql_real_escape_string($reset_key) . "'");
		if(mysql_affected_rows() > 0){ return true; }
		return false;
	}

	/***************************************************************************************************************/
	/***************************************** PERFORM LOGOUT ******************************************************/
	/***************************************************************************************************************/	
	private function doLogout(){
		$Navigation = new Navigation($this->db);
		$go_here_after_logout = $Navigation->returnLastPageUrl();
		self::clearConnectFields();
		$_SESSION = array();
		foreach($_COOKIE as $cookie_key => $cookie_value){
			if($cookie_key != IDENTITY_COOKIE_KEY){
				setcookie($cookie_key, null, time() - 3600, '/');
			}
		}
		header("Location: " . $go_here_after_logout);
		exit(0);
	}
	
}
?>