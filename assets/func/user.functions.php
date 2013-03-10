<?php
/************************************************************************/
/******************* SEE IF WE HAVE AN ACTIVE USER **********************/
/************************************************************************/
function activeUserSession(){ if(isset($_SESSION['front-end-user']['dzpro_user_id'])){ return true; }else{ return false; } }

/************************************************************************/
/******************* SAVE USER DATA *************************************/
/************************************************************************/
function saveUserData($data_name, $meta_value, $user_id = false){
	assureSession(); if(!activeUserSession() and $user_id === false){ return false; }
	$result = @mysql_query("SELECT dzpro_user_data_id FROM dzpro_user_data WHERE dzpro_user_data_name = '" . mysql_real_escape_string($data_name) . "' LIMIT 1"); 
	if(mysql_num_rows($result) > 0){ $row = mysql_fetch_row($result); mysql_free_result($result); }
	if(isset($row[0]) and $row[0] > 0){ 
		return saveUserMeta($meta_value, (int)$row[0], $user_id);
	}else{
		@mysql_query("INSERT INTO dzpro_user_data (dzpro_user_data_name, dzpro_user_data_date_added) VALUES ('" . mysql_real_escape_string($data_name) . "', NOW())"); if(mysql_insert_id() > 0){ return saveUserMeta($meta_value, mysql_insert_id()); }else{ return false; }
	}
}

/************************************************************************/
/******************* SAVE USER META *************************************/
/************************************************************************/
function saveUserMeta($meta_value = null, $data_id = null, $user_id = false){
	assureSession(); if(!activeUserSession() and $user_id === false){ return false; } if(empty($meta_value)){ return false; } if(empty($data_id)){ return false; }
	$for_this_user_id = ($user_id !== false and is_numeric($user_id)) ? $user_id : (int)$_SESSION['front-end-user']['dzpro_user_id'];	
	$result = @mysql_query("SELECT dzpro_user_meta_id FROM dzpro_user_meta WHERE dzpro_user_meta_value = '" . mysql_real_escape_string($meta_value) . "' AND dzpro_user_id = " . (int)$for_this_user_id . " AND dzpro_user_data_id = " . (int)$data_id . " LIMIT 1"); if(mysql_num_rows($result) > 0){ $row = mysql_fetch_row($result); mysql_free_result($result); }
	if(isset($row[0]) and $row[0] > 0){ 
		@mysql_query(" UPDATE dzpro_user_meta SET dzpro_user_meta_date_added = NOW() WHERE dzpro_user_meta_id = '" . mysql_real_escape_string($row[0]) . "' ");
		return $row[0]; 
	}else{ 
		@mysql_query("INSERT INTO dzpro_user_meta (dzpro_user_id, dzpro_user_data_id, dzpro_user_meta_value, dzpro_user_meta_date_added) VALUES (" . (int)$for_this_user_id . ", " . (int)$data_id . ", '" . mysql_real_escape_string($meta_value) . "', NOW())"); 
		if(mysql_insert_id() > 0){ return mysql_insert_id(); }else{ return false; } 
	}
}

/************************************************************************/
/******************* GET USER META **************************************/
/************************************************************************/
function getUserData($data_key, $all = false, $user_id = false){
	if(!activeUserSession() and $user_id === false){ return false; } if(!is_bool($all)){ return false; } if(!is_bool($user_id) and !is_numeric($user_id)){ return false; }
	$for_this_user_id = ($user_id !== false and is_numeric($user_id)) ? $user_id : (int)$_SESSION['front-end-user']['dzpro_user_id'];
	$return = array(); $sql = "SELECT dzpro_user_meta_value FROM dzpro_user_meta LEFT JOIN dzpro_user_data USING ( dzpro_user_data_id ) WHERE dzpro_user_id = " . $for_this_user_id . " AND dzpro_user_data_name = '" . mysql_real_escape_string($data_key) . "'"; if(false === $all){ $sql .= " ORDER BY dzpro_user_meta_date_added DESC LIMIT 1"; } $result = @mysql_query($sql); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $return[] = $row['dzpro_user_meta_value']; } mysql_free_result($result); }
	if($all === true){ return array_values($return); }else{ $return = array_pop($return); return $return; }
}

/************************************************************************/
/******************* GET PROFILE PICTURE ********************************/
/************************************************************************/
function getProfilePicture($user_id = false){
	$for_this_user_id = ($user_id !== false and is_numeric($user_id)) ? (int)$user_id : (int)$_SESSION['front-end-user']['dzpro_user_id'];
	switch(true){
		case(null !== ($profile_pic = getUserData('facebook_profile_pic', false, $for_this_user_id))): return $profile_pic; break;
		case(null !== ($profile_pic = getUserData('linkedin_profile_pic', false, $for_this_user_id))):	return $profile_pic; break;
		default: return '/assets/img/avatar.jpg'; break;
	}
}

/************************************************************************/
/******************* GET USER HEADLINE **********************************/
/************************************************************************/
function getUserHeadline($user_id = false){
	$for_this_user_id = ($user_id !== false and is_numeric($user_id)) ? (int)$user_id : (int)$_SESSION['front-end-user']['dzpro_user_id'];
	switch(true){
		case(null !== ($return_value = getUserData('linkedin_headline', false, $for_this_user_id))): return $return_value; break;
		case(null !== ($return_value = getUserData('linkedin_current_company_name', false, $for_this_user_id))): return $return_value; break;
		case(null !== ($return_value = getUserData('facebook_current_company_name', false, $for_this_user_id))): return $return_value; break;
		case(null !== ($return_value = getUserData('linkedin_school_name', false, $for_this_user_id))): return $return_value; break;
		default: return 'Ideabooth User'; break;
	}
}

/************************************************************************/
/******************* GET USER EMAIL *************************************/
/************************************************************************/
function getUserEmail($user_id = false){
	if(!activeUserSession() and $user_id === false){ return false; }
	if(!is_bool($user_id) and !is_numeric($user_id)){ return false; }
	$for_this_user_id = ($user_id !== false and is_numeric($user_id)) ? (int)$user_id : (int)$_SESSION['front-end-user']['dzpro_user_id'];
	switch(true){
		case(isset($_SESSION['front-end-user']['dzpro_user_email']) and !empty($_SESSION['front-end-user']['dzpro_user_email']) and false === $user_id): return $_SESSION['front-end-user']['dzpro_user_email']; break;
		case(null !== ($return_value = getUserData('google_email', false, $for_this_user_id))): return $return_value; break;
		case(null !== ($return_value = getUserData('facebook_email', false, $for_this_user_id))): return $return_value; break;
		default: return 'no email available'; break;
	}
}

/************************************************************************/
/******************* SEE IF USER IS AFFILIATED **************************/
/************************************************************************/
function validateMetaExistence($meta_value = null){
	if(!activeUserSession()){ return false; } if(empty($meta_value)){ return false; }
	if(is_numeric($meta_value)){
		$result = @mysql_query("SELECT dzpro_user_data_id FROM dzpro_user_meta WHERE dzpro_user_meta_value = '" . mysql_real_escape_string($meta_value) . "' AND dzpro_user_id = " . (int)$_SESSION['front-end-user']['dzpro_user_id']); if(mysql_num_rows($result) > 0){ mysql_free_result($result); return true; }else{ return false; }
	}else{
		$result = @mysql_query("SELECT dzpro_user_data_id FROM dzpro_user_meta WHERE dzpro_user_meta_value LIKE '%" . mysql_real_escape_string($meta_value) . "' OR '" . mysql_real_escape_string($meta_value) . "%' AND dzpro_user_id = " . (int)$_SESSION['front-end-user']['dzpro_user_id']); if(mysql_num_rows($result) > 0){ mysql_free_result($result); return true; }else{ return false; }
	}
}

/************************************************************************/
/******************* SEE IF USER IS AFFILIATED **************************/
/************************************************************************/
function seeIfUserIsAffiliated(){
	$result = @mysql_query("SELECT allowed_group_value FROM allowed_groups"); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ if(validateMetaExistence($row['allowed_group_value'])){ return true; } } mysql_free_result($result); }
	return false; 
}

/************************************************************************/
/******************* GET USER NAME **************************************/
/************************************************************************/
function getUserName($user_id = false){
	if(!is_numeric($user_id) and !is_bool($user_id)){ return null; }
	if(!$user_id and activeUserSession()){ return $_SESSION['front-end-user']['dzpro_user_name']; }
	if(is_numeric($user_id)){ $result = @mysql_query("SELECT dzpro_user_name FROM dzpro_users WHERE dzpro_user_id = " . (int)$user_id); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ return $row['dzpro_user_name']; } mysql_free_result($result); } }
	return 'Unknown';
}

/************************************************************************/
/******************* IS USER AFFILIATED *********************************/
/************************************************************************/
function isUserAffiliated($user_id = false){
	if(!is_numeric($user_id) and !is_bool($user_id)){ return null; }
	if(!$user_id and activeUserSession()){ return (isset($_SESSION['front-end-user']['dzpro_user_affiliated']) and $_SESSION['front-end-user']['dzpro_user_affiliated'] == '1') ? true : false; }
	if(is_numeric($user_id)){ $result = @mysql_query("SELECT dzpro_user_affiliated FROM dzpro_users WHERE dzpro_user_id = " . (int)$user_id); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ return ($row['dzpro_user_affiliated'] == '1') ? true : false; } mysql_free_result($result); } }
	return false;
}

/************************************************************************/
/******************* GET PROFILE URL ************************************/
/************************************************************************/
function getProfileUrl($user_id = null){
	if(!is_numeric($user_id) and !is_bool($user_id)){ return null; }
	if(!$user_id and activeUserSession()){ return 'http://' . HOST_NAME . '/u/' . prepareStringForUrl($_SESSION['front-end-user']['dzpro_user_name']) . '-' . convertNumber($_SESSION['front-end-user']['dzpro_user_id']) . '.htm'; }
	if(is_numeric($user_id)){ $result = @mysql_query("SELECT dzpro_user_name FROM dzpro_users WHERE dzpro_user_id = " . (int)$user_id); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ return 'http://' . HOST_NAME . '/u/' . prepareStringForUrl($row['dzpro_user_name']) . '-' . convertNumber($user_id) . '.htm'; } } }
	return null;
}

/************************************************************************/
/******************* GET USER ID ****************************************/
/************************************************************************/
function getUserId(){
	if(isset($_SESSION['front-end-user']['dzpro_user_id'])){ return $_SESSION['front-end-user']['dzpro_user_id']; }
	return false;
}

/************************************************************************/
/******************* VALIDATE ADMIN *************************************/
/************************************************************************/
function validateAdmin($user = null, $password = null){
	if(empty($user)){ return false; }
	if(empty($password)){ return false; }
	$result = mysql_query(" SELECT * FROM dzpro_admins WHERE dzpro_admin_username = '" . mysql_real_escape_string($user) . "' AND dzpro_admin_password = '" . mysql_real_escape_string(saltString($password)) . "' "); if(mysql_num_rows($result) > 0){ return true; }
	return false;
}

/************************************************************************/
/******************* VALIDATE ADMIN *************************************/
/************************************************************************/
function gotoConnectPage(){
	insertIntelligenceStack();
	if(defined('FRAME_CONNECT_PATH')){ header('Location: ' . FRAME_CONNECT_PATH); exit(0); }
	header('Location: ' . CONNECT_PAGE_PATH); exit(0);
}

/************************************************************************/
/******************* GET USER ADDRESSES *********************************/
/************************************************************************/
function getUserAddresses(){
	if(!activeUserSession()){ return false; }
	$return = mysql_query_on_key(" SELECT * FROM dzpro_user_shipping_options WHERE dzpro_user_id = '" . mysql_real_escape_string(getUserId()) . "' ", 'dzpro_user_shipping_option_id');
	$return[0] = 'please pick a shipping address'; ksort($return);
	return $return;
}
?>