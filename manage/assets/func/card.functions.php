<?php
/***************************************************************************/
/****************** GET CARD TYPE FROM NUMBER ******************************/
/***************************************************************************/
function getCardType($cc_number = null){
	if(!have($cc_number)){ return false; }
	switch(true){
		case(preg_match('/^4\d{3}-?\d{4}-?\d{4}-?\d{4}$/', $cc_number)): return 'Visa'; break;
		case(preg_match('/^5[1-5]\d{2}-?\d{4}-?\d{4}-?\d{4}$/', $cc_number)): return 'MasterCard'; break;
		case(preg_match('/^6011-?\d{4}-?\d{4}-?\d{4}$/', $cc_number)): return 'Discover'; break;
		case(preg_match('/^3[4,7]\d{13}$/', $cc_number)): return 'AmericanExpress'; break;
		case(preg_match('/^3[0,6,8]\d{12}$/', $cc_number)): return 'Diners'; break;
		default: return 'Unknown'; break;
	}
	return false;
}
?>