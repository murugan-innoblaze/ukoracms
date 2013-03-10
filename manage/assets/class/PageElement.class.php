<?php

class PageElement extends FormBasics {

	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
	
		//extend from parent
		parent::__construct($db, $table_name, $parameters, $sticky_fields);

		//set intelligence candidates
		$this->intelligence_candidates = array(); self::setIntelligenceCandidates();

		//build widgets array
		$this->widget_incrementor = 1; $this->widget_array = array(); self::buildWidgetArray();
		
		//initiate Widgets object
		if(have($this->widget_array)){
			$this->Widgets = new Widgets($db);
			$this->Widgets->setWidgets($this->widget_array);
		}

		//load widget from ajax call
		if(isset($this->Widgets) and isset($_GET['ajax']) and $_GET['ajax'] == 'pageElementsWidgetContent' and isset($_POST['widget_id']) and is_numeric($_POST['widget_id'])){ $this->Widgets->printWidgetFromAjaxCall(); exit(0); }
		
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
		/******************************** PRINT ELEMENT STATS *****************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/	
		self::printElementStats();
	
	}
	
	/*************************************************************/
	/*********************** GET ITELLIGENCE CANDIDATES **********/
	/*************************************************************/	
	protected function setIntelligenceCandidates(){
		if(isset($this->selected_row['dzpro_page_element_template']) and is_file(FRONTEND_DOCUMENT_ROOT . FRONTEND_ELEMENTS_PATH . $this->selected_row['dzpro_page_element_template']) and isset($this->selected_row['dzpro_page_variant_element_template']) and is_file(FRONTEND_DOCUMENT_ROOT . FRONTEND_ELEMENTS_PATH . $this->selected_row['dzpro_page_variant_element_template'])){ $this->intelligence_candidates = mysql_query_on_key(" SELECT * FROM dzpro_intelligence_data LEFT JOIN dzpro_page_element_to_intelligence_data USING ( dzpro_intelligence_data_id ) WHERE dzpro_page_element_id = '" . mysql_real_escape_string($this->primary_value) . "' ", 'dzpro_intelligence_data_id'); }
		return have($this->intelligence_candidates);
	}

	/*************************************************************/
	/*********************** BUILD WIDGET ARRAY ******************/
	/*************************************************************/	
	protected function buildWidgetArray(){
		if(have($this->intelligence_candidates)){
			foreach($this->intelligence_candidates as $intelligence_row){
		
				//even numbers
				$this->widget_array[$this->widget_incrementor] = array(
					'even' => true,
					'dzpro_widget_id' => $this->widget_incrementor,
					'dzpro_widget_name' => $intelligence_row['dzpro_intelligence_data_name'] . ' group A',
					'dzpro_widget_type' => 'line chart',
					'dzpro_widget_description' => $intelligence_row['dzpro_intelligence_data_name'] . ' A-B Comparison',
					'dzpro_intelligence_data_id' => (int)$intelligence_row['dzpro_intelligence_data_id'],
					'dzpro_widget_interval' => 'hours',
					'dzpro_widget_limit' => 142,
					'dzpro_widget_variations_limit' => 12
				);
				
				//increment widget
				$this->widget_incrementor++;
				
				//odd numbers
				$this->widget_array[$this->widget_incrementor] = array(
					'even' => false,
					'dzpro_widget_id' => $this->widget_incrementor,
					'dzpro_widget_name' => $intelligence_row['dzpro_intelligence_data_name'] . ' group B',
					'dzpro_widget_type' => 'line chart',
					'dzpro_widget_description' => $intelligence_row['dzpro_intelligence_data_name'] . ' A-B Comparison',
					'dzpro_intelligence_data_id' => (int)$intelligence_row['dzpro_intelligence_data_id'],
					'dzpro_widget_interval' => 'hours',
					'dzpro_widget_limit' => 142,
					'dzpro_widget_variations_limit' => 12
				);
				
				//increment widget
				$this->widget_incrementor++;
				
			}
		}
		return (have($this->widget_array));	
	}

	/*************************************************************/
	/*********************** BUILD STATS BLOCK *******************/
	/*************************************************************/	
	protected function printElementStats(){
		if(isset($this->Widgets) and have($this->widget_array)){
			
			//print js
			$this->Widgets->printWidgetHeadBlock();
			
			//print the widgets
			$this->Widgets->printWidgetsForTag('pageElementsWidgetContent');

		}
	}
	
}

?>