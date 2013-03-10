<?php
/************************************************************************/
/******************* CHECK IF VISITOR ID IS THERE ***********************/
/************************************************************************/
function getVisitorId(){ assureSession(); if(isset($_SESSION[IDENTITY_COOKIE_KEY]['dzpro_identity_id']) and $_SESSION[IDENTITY_COOKIE_KEY]['dzpro_identity_id'] > 0){ return (int)$_SESSION[IDENTITY_COOKIE_KEY]['dzpro_identity_id']; }else{ return false; } }

/************************************************************************/
/******************* SEE IF A USER HAS BEEN ASSOCIATED ******************/
/************************************************************************/
function userAtMachine(){
	assureSession(); if(activeUserSession()){ return true; } if(false !== ($visitor_id = getVisitorId())){ $result = @mysql_query("SELECT dzpro_user_id FROM dzpro_identity_to_user WHERE dzpro_user_id IS NOT NULL AND dzpro_user_id != 0 AND dzpro_identity_id = " . (int)$visitor_id); if(mysql_num_rows($result) > 0){ return true; }else{ return false; } }else{ return false; }
}

/************************************************************************/
/******************* IS THIS A NEW VISITOR ******************************/
/************************************************************************/
function isNewVisitor(){
	assureSession(); if(!isset($_SESSION[IDENTITY_COOKIE_KEY]) or (isset($_SESSION[IDENTITY_COOKIE_KEY]['dzpro_identity_total_visits']) and $_SESSION[IDENTITY_COOKIE_KEY]['dzpro_identity_total_visits'] < 1)){ return true; }else{ return false; } return null;
}

/************************************************************************/
/******************* IS THIS A NEW VISITOR ******************************/
/************************************************************************/
function getVisitorPageViews(){
	if(isset($_SESSION[IDENTITY_COOKIE_KEY]['dzpro_identity_total_visits'])){ return $_SESSION[IDENTITY_COOKIE_KEY]['dzpro_identity_total_visits']; } return null;
}
?>