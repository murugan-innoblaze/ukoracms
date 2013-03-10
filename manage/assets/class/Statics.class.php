<?php
class Statics extends Form { 

	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
			
		//run form constructor
		parent::__construct($db, $table_name, $parameters, $sticky_fields);
			
	}

	/*************************************************************/
	/*********************** PRINT RECORD UI *********************/
	/*************************************************************/
	public function buildStaticsBlock(){
	
		parent::buildFormBlock();
	
	}

}
?>