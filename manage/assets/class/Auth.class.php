<?php
class Auth {
	
	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $require_login = true, $remember_entry = true){
	
		//database connection
		$this->db = $db;
		
		//start session
		session_start();
		
		//expire login
		$this->expire_login_hours = 8;
		
		//if logout
		if(isset($_GET['logout']) and $_GET['logout'] == 'true'){ self::logoutUser(); }
		
		//if loggin in
		if(isset($_POST['username']) and isset($_POST['password'])){ self::loginAttempt(); }
		
		//look for active session - remember entry url
		if($require_login){ if(false === self::authenticateUser()){ if($remember_entry){ $_SESSION['attempted_entry_point'] = $_SERVER['REQUEST_URI']; } header("Location: /login"); exit(0); } }
					
	}

	/*************************************************************/
	/*********************** AUTHENTICATE USER *******************/
	/*************************************************************/	
	public function authenticateUser(){
		if(!isset($_SESSION['dzpro_admin_id'])){
			if(false !== self::setSessionFromCookie()){
				return true;
			}else{
				return false;
			}
		}else{
			return true;
		}
	}
	
	private function setSessionFromCookie(){
		if(isset($_COOKIE['dz_admin_key'])){
			$sql = "
					SELECT 
						*
					FROM 
						dzpro_admins
					WHERE 
						MD5(CONCAT(dzpro_admin_username, dzpro_admin_password)) = '" . mysql_real_escape_string($_COOKIE['dz_admin_key']) . "'
					LIMIT 
						1	
					";
			$result = mysql_query($sql) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
			if(mysql_num_rows($result) > 0){
				while($row = mysql_fetch_assoc($result)){
					$_SESSION['dzpro_admin_super'] = $row['dzpro_admin_super'];
					$_SESSION['dzpro_admin_id'] = $row['dzpro_admin_id'];
					$_SESSION['dzpro_admin_name'] = $row['dzpro_admin_name'];
					setcookie('dz_admin_key', md5($row['dzpro_admin_username'] . $row['dzpro_admin_password']), time() + ($this->expire_login_hours * 3600), '/', MANAGER_DOMAIN);
				}
				mysql_free_result($result);
				return true;
			}
		}else{
			return false;
		}
	}

	/*************************************************************/
	/*********************** LOGOUT USER *************************/
	/*************************************************************/	
	public function loginAttempt(){
		if(isset($_POST['username']) and isset($_POST['password'])){
			$sql = "
						SELECT 
							*
						FROM 
							dzpro_admins
						WHERE 
							dzpro_admin_username = '" . mysql_real_escape_string($_POST['username']) . "'
						AND 
							dzpro_admin_password = '" . mysql_real_escape_string(saltString($_POST['password'])) . "'
						LIMIT 
							1	
					";
			$result = mysql_query($sql) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
			if(mysql_num_rows($result) > 0){
				while($row = mysql_fetch_assoc($result)){
					$_SESSION['dzpro_admin_super'] = $row['dzpro_admin_super'];
					$_SESSION['dzpro_admin_id'] = $row['dzpro_admin_id'];
					$_SESSION['dzpro_admin_name'] = $row['dzpro_admin_name'];
					setcookie('dz_admin_key', md5($row['dzpro_admin_username'] . $row['dzpro_admin_password']), time() + ($this->expire_login_hours * 3600), '/', MANAGER_DOMAIN);
				}
				mysql_free_result($result);
				if(isset($_SESSION['attempted_entry_point']) and !empty($_SESSION['attempted_entry_point'])){ header("Location: " . $_SESSION['attempted_entry_point']); exit(0); }
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	/*************************************************************/
	/*********************** LOGOUT USER *************************/
	/*************************************************************/	
	public function logoutUser(){
		
		//expire all cookies
		if(isset($_COOKIE) and !empty($_COOKIE)){
			foreach($_COOKIE as $cookie_key => $cookie_value){	
				setcookie($cookie_key, '', time() - 3600, '/', MANAGER_DOMAIN);
				unset($_COOKIE[$cookie_key]);
			}
		}
		
		//empty out cookie array
		$_COOKIE = array();

		//empty session array
		$_SESSION = array();

		//destroy session
		session_destroy();
			
		//return
		if(empty($_SESSION) and empty($_COOKIE)){
			return true;
		}else{
			return false;
		}
		
	}
	
}
?>