<?php

class Page extends Form {

	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
		
		//run form constructor
		parent::__construct($db, $table_name, $parameters, $sticky_fields);
		
		//build templates array
		$this->templates = self::getTemplates();
		
		//build elements array
		$this->elements = self::getElements();		
		
		//we are reordering elements
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'reorderElementsSubmit' and isset($_POST['orderString_elements_' . $this->table]) and !empty($_POST['orderString_elements_' . $this->table])){ self::reorderPageElements(json_decode(stripslashes($_POST['orderString_elements_' . $this->table]))); exit(0); }
		
		//we are reloading elements list
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'reloadElementsList' and isset($_POST['reloadElementsAreaName']) and !empty($_POST['reloadElementsAreaName'])){
self::loadElementsForArea($_POST['reloadElementsAreaName']); exit(0); }
						
		//reloadContentArea
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'reloadContentArea' and isset($_POST['reloadContentAreaName']) and !empty($_POST['reloadContentAreaName'])){
self::loadContentForArea($_POST['reloadContentAreaName']); exit(0); }

		//saveContentArea
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'saveContentArea' and isset($_POST['contentArea']) and !empty($_POST['contentArea'])){
setContentsFor($this->primary_value, $_POST['contentArea'], $_POST['theContent']); exit(0); }

		//Delete Element
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'removeElement' and isset($_POST['ElementId']) and !empty($_POST['ElementId'])){ echo self::removeElementAssociation($_POST['ElementId']); exit(0); }
	
		//Insert Element
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'insertElement' and isset($_POST['ElementId']) and !empty($_POST['ElementId']) and isset($_POST['ElementName']) and !empty($_POST['ElementName'])){ echo self::insertElementAssociation($_POST['ElementId'], $_POST['ElementName']); exit(0); }
		
		//Save Page Settings
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'savePageSettings' and isset($_POST['theSettings']) and !empty($_POST['theSettings'])){
self::savePageSettings(json_decode(stripslashes($_POST['theSettings']))); exit(0); }

		//Insert Id
		if(isset($this->new_insert_id) and !empty($this->new_insert_id) and isset($parameters['default_elements'])){ self::insertDefaultElements($parameters['default_elements']); }
	
	}

	/*************************************************************/
	/*********************** GET ALL ELEMENTS ********************/
	/*************************************************************/		
	private function getElements(){
		if($ehandle = opendir(FRONTEND_DOCUMENT_ROOT . FRONTEND_ELEMENTS_PATH)){ $elements = array(); $elements = array(); while(false !== ($efile = readdir($ehandle))){ if(strpos($efile, 'element') > 0){ $elements[FRONTEND_ELEMENTS_PATH . $efile] = array('element_file' => $efile, 'element_name' => str_ireplace(array('element.', '.php'), array('', ''), $efile)); } } return $elements; } return false;
	}

	/*************************************************************/
	/*********************** INSERT DEFAULT ELEMENTS *************/
	/*************************************************************/	
	private function insertDefaultElements($default_elements = null){
		if(empty($default_elements)){ return false; }
		$mysql_string = " INSERT INTO dzpro_page_element_to_page ( dzpro_page_id, dzpro_page_element_id, dzpro_page_element_map_area, dzpro_page_element_map_date_added, dzpro_page_element_map_orderfield ) VALUES ";
		foreach($default_elements as $orderfield => $element_array){ if(isset($element_array['element_id']) and isset($element_array['element_area'])){ $mysql_string .= " ( " . (int)$this->new_insert_id . ", " . (int)$element_array['element_id'] . ", '" . mysql_real_escape_string($element_array['element_area']) . "', NOW(), " . (int)$orderfield . " ), "; } }	
		$mysql_string = substr($mysql_string, 0, strlen($mysql_string) - 2);
		if(@mysql_query($mysql_string)){ return true; }
		return false;
	}

	/*************************************************************/
	/*********************** SAVE PAGE SETTINGS ******************/
	/*************************************************************/	
	private function savePageSettings($settings_object){
		if(empty($settings_object)){ return null; }
		foreach($settings_object as $setting_name => $setting_value){
			@mysql_query("UPDATE dzpro_page_constants SET dzpro_page_constant_value = '" . mysql_real_escape_string(trim($setting_value)) . "', dzpro_page_constant_date_added = NOW() WHERE dzpro_page_constant_name = '" . mysql_real_escape_string(trim($setting_name)) . "' AND dzpro_page_id = " . (int)$this->primary_value); if(mysql_affected_rows() == 0){ @mysql_query("INSERT INTO dzpro_page_constants (dzpro_page_id, dzpro_page_constant_name, dzpro_page_constant_value, dzpro_page_constant_date_added) VALUES (" . (int)$this->primary_value . ", '" . mysql_real_escape_string(trim($setting_name)) . "', '" . mysql_real_escape_string(trim($setting_value)) . "', NOW())");
			}
		}
	}

	/*************************************************************/
	/*********************** INSERT ELEMENT **********************/
	/*************************************************************/
	private function insertElementAssociation($element_id = 0, $area = null){
		if(!($element_id > 0) or empty($area)){ return null; }
		@mysql_query("INSERT INTO dzpro_page_element_to_page ( dzpro_page_element_map_area, dzpro_page_element_id, dzpro_page_id, dzpro_page_element_map_date_added ) VALUES ( '" . mysql_real_escape_string($area) . "', " . (int)$element_id . ", " . (int)$this->primary_value . ", NOW())");
		if(mysql_insert_id() > 0){ return 'true'; }
		return null;
	}

	/*************************************************************/
	/*********************** REORDER ELEMENTS ********************/
	/*************************************************************/	
	private function reorderPageElements($array = array()){
		if(empty($array)){ return null; }
		foreach($array as $order_int => $element_key){ @mysql_query("UPDATE dzpro_page_element_to_page SET dzpro_page_element_map_orderfield = " . (int)$order_int . " WHERE dzpro_page_element_map_id = " . (int)$element_key); }
		return true;
	}

	/*************************************************************/
	/*********************** REMOVE ELEMENT ID *******************/
	/*************************************************************/		
	private function removeElementAssociation($item_id = null){
		if(empty($item_id)){ return false; }
		@mysql_query("DELETE FROM dzpro_page_element_to_page WHERE dzpro_page_element_map_id = " . (int)$item_id);
		if(mysql_affected_rows() > 0){ return 'true'; }
		return null;
	}

	/*************************************************************/
	/*********************** GET TEMPLATE NAME *******************/
	/*************************************************************/	
	public function getTemplateName($template_path){
		$template_contents = getFileContents($template_path);
		$matches = array(); preg_match_all('/\/\* Template name:([a-z0-9\s\-\_\.\,]+)\*\//msi', $template_contents, $matches);
		return isset($matches[1][0]) ? trim($matches[1][0]) : null;
	}

	/*************************************************************/
	/*********************** GET ELEMENT LOCATIONS ***************/
	/*************************************************************/
	public function getElementLocations($template_path){
		$template_contents = getFileContents($template_path);
		$matches = array(); preg_match_all('/loadPageElements\(\'([a-z0-9\s\-\_\.,]+)\'\)/msi', $template_contents, $matches);
		if(isset($matches[1]) and !empty($matches[1])){ $return = array(); foreach($matches[1] as $area_name){ $return[] = trim($area_name); } return $return; }
		return null;
	}

	/*************************************************************/
	/*********************** GET CONTENT LOCATIONS ***************/
	/*************************************************************/	
	public function getContentLocations($template_path){
		$template_contents = getFileContents($template_path);
		$matches = array(); preg_match_all('/loadPageContent\(\'([a-z0-9\s\-\_\.,]+)\'\)/msi', $template_contents, $matches);
		if(isset($matches[1]) and !empty($matches[1])){ $return = array(); foreach($matches[1] as $area_name){ $return[] = trim($area_name); } return $return; }
		return null;
	}
	
	/*************************************************************/
	/*********************** GET TEMPLATE CONSTANTS **************/
	/*************************************************************/
	protected function getTemplateConstants($template_path){
		$template_contents = getFileContents($template_path);
		$matches = array(); preg_match_all('/\/\* Template constants:([a-z0-9\s\-\_\.\,]+)\*\//msi', $template_contents, $matches);
		$return = array(); if(isset($matches[1][0])){ $constants = explode(',', trim($matches[1][0])); foreach($constants as $constant){ $return[] = trim($constant); } }
		return $return;
	}

	/*************************************************************/
	/*********************** GET TEMPLATES ***********************/
	/*************************************************************/
	public function getTemplates(){
		if($template_dir_handle = opendir(FRONTEND_DOCUMENT_ROOT . FRONTEND_TEMPLATES_PATH)){
			$return = array();
			while(false !== ($template_file = readdir($template_dir_handle))){
				if(stripos($template_file, '.template') > 0){ 
					$template_file_name = self::getTemplateName(FRONTEND_DOCUMENT_ROOT . FRONTEND_TEMPLATES_PATH . $template_file);
					if(empty($template_file_name)){ $template_file_name = ucfirst(str_ireplace(array('.template', '.php'), array('', ''), $template_file)); }
					$return[FRONTEND_TEMPLATES_PATH . $template_file]['template_name'] = self::getTemplateName(FRONTEND_DOCUMENT_ROOT . FRONTEND_TEMPLATES_PATH . $template_file);
					$return[FRONTEND_TEMPLATES_PATH . $template_file]['template_file'] = $template_file; 
					$return[FRONTEND_TEMPLATES_PATH . $template_file]['element_areas'] = self::getElementLocations(FRONTEND_DOCUMENT_ROOT . FRONTEND_TEMPLATES_PATH . $template_file);
					$return[FRONTEND_TEMPLATES_PATH . $template_file]['content_areas'] = self::getContentLocations(FRONTEND_DOCUMENT_ROOT . FRONTEND_TEMPLATES_PATH . $template_file);
					$return[FRONTEND_TEMPLATES_PATH . $template_file]['constants'] = self::getTemplateConstants(FRONTEND_DOCUMENT_ROOT . FRONTEND_TEMPLATES_PATH . $template_file);
				}
			}
			closedir($template_dir_handle);
			return $return;
		}
		return null;
	}

	/*************************************************************/
	/*********************** PRINT CONTENT AREA TARGET  **********/
	/*************************************************************/
	public function printContentTargets(){
			if(!isset($this->templates[$this->page_template]['content_areas']) or empty($this->templates[$this->page_template]['element_areas'])){ return null; }
		?>
						<script type="text/javascript" src="<?=ASSETS_PATH?>/mce/tiny_mce.js"></script>
						<script type="text/javascript">
							<!-- 
								$().ready(function(){
									$('select[name=content-location]').change(function(){
										var selectedArea = $(this).val();
										try{ tinyMCE.execCommand('mceRemoveControl', true, 'textarea-for-content'); }catch(error){ showMessage(error);	}
										$.ajax({
											url : '<?=$_SERVER['PHP_SELF']?>?ajax=reloadContentArea&record_id=<?=(int)$this->primary_value?>',
											type : 'post',
											data : 'reloadContentAreaName=' + selectedArea,
											success : function(html){
												showMessage('content reloaded');
												$('#page-contends-ui-target').attr('title', selectedArea).html(html);
												setContentHtmlBrowser();
											}
										});
									});
									setContentHtmlBrowser();
									$('#save-content-button').live('click', function(){
										try{ var saveContentHtml = tinyMCE.get('textarea-for-content').getContent(); saveContent(saveContentHtml);
										}catch(error){ 
											showMessage(error);
										}
									});
								});
								function setContentHtmlBrowser(){
									tinyMCE.init({
										mode : "textareas",
										theme : "advanced",
										editor_selector : "html_editor_page_content",
										plugins : "pagebreak,style,paste,directionality,visualchars,nonbreaking,xhtmlxtras,template",
										theme_advanced_buttons1 : "pagebreak,styleprops,removeformat,hr,|,pastetext,pasteword,bullist,numlist,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull",
										theme_advanced_buttons2 : "formatselect,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,cleanup,forecolor,backcolor,code",
										theme_advanced_buttons3 : "",
										theme_advanced_buttons4 : "",
										theme_advanced_toolbar_location : "top",
										theme_advanced_toolbar_align : "left"
									});
								}
								function saveContent(saveContentHtml){
									var elementArea = $('#page-contends-ui-target').attr('title');
									$.ajax({
										url: '<?=$_SERVER['PHP_SELF']?>?ajax=saveContentArea&record_id=<?=(int)$this->primary_value?>',
										type: 'post',
										data: 'contentArea='+encodeURIComponent(elementArea)+'&theContent='+encodeURIComponent(saveContentHtml),
										success: function(){ showMessage('content saved'); },
										error: function(error){	showMessage(error); }
									});
								}
							//-->
						</script>
						<div class="input_iframe" id="input_row_iframe_elements_<?=$this->table?>">
							<div class="table_name">
								<table cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td style="width: 170px;">
												Select Content Area
											</td>
											<td class="input">
												<div class="inner_holder">
													<?php if(isset($this->templates[$this->page_template]['content_areas']) and !empty($this->templates[$this->page_template]['element_areas'])){ ?>
													<select name="content-location">
														<?php
															foreach($this->templates[$this->page_template]['content_areas'] as $content_area_name){
																if(!isset($first_content_area)){ $selected = ' selected="selected" '; $first_content_area = $content_area_name; }else{ $selected = ''; }
														?>		
														<option value="<?=$content_area_name?>" <?=$selected?>><?=$content_area_name?></option>
														<?php } ?>
													</select>
													<?php } ?>
												</div><!-- end inner_holder -->
											</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div id="page-contends-ui-target" style="background-color: #dee3e9; padding: 12px;" title="<?=$first_content_area?>">
								<?=self::loadContentForArea($first_content_area)?>
							</div><!-- end .the_content_box -->
						</div><!-- end .input_row -->

		<?php
	}

	/*************************************************************/
	/*********************** LOAD CONTENT AREA *******************/
	/*************************************************************/	
	private function loadContentForArea($content_area){
		?>
						<div class="input_row inner_shadow" id="input_row_page_content">
							<table cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td class="label">
											<?=$content_area?>
										</td>
										<td class="textarea">
											<div class="inner_holder">
												<textarea title="Please enter something." id="textarea-for-content" name="page_content" style="height: 300px; padding: 0;" class="html_editor_page_content"><?=getContentsFor($this->primary_value, $content_area)?></textarea>
											</div><!-- .inner_holder -->
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="button_row">
							<table cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td style="text-align: left;">
											<input type="reset" value="Reset Form" />	
										</td>
										<td style="text-align: right;">
											<input type="submit" name="form_submit" id="save-content-button" value="Save" class="save_button" onclick="javascript:return false;" />
										</td>
									</tr>
								</tbody>
							</table>
						</div>
		<?php
	}
	
	/*************************************************************/
	/*********************** PRINT ELEMENTS TARGET  **************/
	/*************************************************************/
	public function printElementTargets(){
		?>
						<script type="text/javascript">
							<!-- 
								function reloadElementsListNow(){
									var selectedArea = $('select[name=element-location] option:selected').val();
									$.ajax({
										url : '<?=$_SERVER['PHP_SELF']?>?ajax=reloadElementsList&record_id=<?=(int)$this->primary_value?>',
										type : 'post',
										data : 'reloadElementsAreaName='+encodeURIComponent(selectedArea),
										success : function(html){ showMessage('elements reloaded'); $('#page-elements-ui-target').html(html); }
									});									
								}
								$().ready(function(){ $('select[name=element-location]').change(reloadElementsListNow); });
							//-->
						</script>
						<div class="input_iframe" id="input_row_iframe_elements_<?=$this->table?>">
							<div class="table_name">
								<table cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td style="width: 170px;">
												Select Element Area
											</td>
											<td class="input">
												<div class="inner_holder">
													<select name="element-location">
														<?php
															if(isset($this->templates[$this->page_template]['element_areas']) and !empty($this->templates[$this->page_template]['element_areas'])){
																foreach($this->templates[$this->page_template]['element_areas'] as $elements_area_name){
																	if(!isset($first_element_area)){
																		$selected = ' selected="selected" ';
																		$first_element_area = $elements_area_name;
																	}else{
																		$selected = '';
																	}
														?>		
														<option value="<?=$elements_area_name?>" <?=$selected?>><?=$elements_area_name?></option>
														<?php
																}
															}
															
														?>
													</select>
												</div><!-- end inner_holder -->
											</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="bucket_top_nav">
								<a href="#add-element" id="new-element-button" title="New Element">
									+
								</a>
							</div><!-- end bucket_top_nav -->		
							<script type="text/javascript">
								<!-- 
									$().ready(function(){
										$('.delete_icon').live('click', function(){
											var ElementId = $(this).parent().attr('id').substr(20);
											if(confirm('Are you sure you want to delete this page element from this page?')){
												$.ajax({
													url : '<?=$_SERVER['PHP_SELF']?>?ajax=removeElement',
													type : 'post',
													data : 'ElementId='+encodeURIComponent(ElementId),
													success : function(mssg){
														if(mssg == 'true'){
															showMessage('element disassociated');
															$('#list_element_record_' + ElementId).hide();
														}
													}
												});
											}
										});
										$('#new-element-button').live('click', function(){
											$('#new-record-pick-element').hide();
											$('ul#form_listing_parent_elements').prepend('<li id="new-record-pick-element" class="element"><table cellpadding="0" cellspacing="0" style="height: auto; margin-top: 13px;"><tbody><tr><td style="width: 170px; color: #5c7493;">Select New Element: </td><td class="input" style="width: 290px;"><div class="inner_holder"><select><?php if(false !== ($elements = getAllPageElements())){ foreach($elements as $element_id => $element_array){ ?><option value="<?=$element_id?>"><?=$element_array['dzpro_page_element_name']?></option><?php } } ?></select></div></td><td style="text-align: left;"><button id="save-new-element" onclick="javascript: return false;">Insert Element</button>&nbsp;&nbsp;<button id="cancel-new-element" onclick="javascript: return false;">Cancel</button></td></tr></tbody></table></li>');
										});
										$('#save-new-element').live('click', function(){
											var ElementId = $('#new-record-pick-element option:selected').val();
											var PageArea = $('select[name=element-location] option:selected').val();
											$.ajax({
												url : '<?=$_SERVER['PHP_SELF']?>?ajax=insertElement&record_id=<?=(int)$this->primary_value?>',
												type : 'post',
												data : 'ElementId=' + ElementId + '&ElementName=' + PageArea,
												success : function(mssg){ reloadElementsListNow(); }
											});
										});
										$('#cancel-new-element').live('click', function(){
											$('#new-record-pick-element').hide();
										});
									});
								//-->
							</script>
							<div id="page-elements-ui-target" style="background-color: white; min-height: 200px;">
								<?=self::loadElementsForArea($first_element_area)?>
							</div><!-- end .the_content_box -->
						</div><!-- end .input_row -->
		<?php
	}

	/*************************************************************/
	/*********************** LOAD ELEMENTS FOR *******************/
	/*************************************************************/
	public function loadElementsForArea($area){
		if(!($this->primary_value > 0)){ return null; }
		?>
						<script type="text/javascript">
							<!--
								$().ready(function(){
									$('#form_listing_parent_elements').sortable({
										containment : '#form_listing_parent_elements',
										axis : 'y',
										handle : '.sort_element',
										placeholder : 'ui-state-highlight',
										update : function(){
											var orderArray_<?=$this->table?> = [];
											var orderCounter_<?=$this->table?> = 0;
											$('#form_listing_parent_elements .record_listing').each(function(){
												orderArray_<?=$this->table?>[orderCounter_<?=$this->table?>] = $(this).attr('id').substr(20);
												orderCounter_<?=$this->table?> += 1;
											});
											var submitorderString_elements_<?=$this->table?> = JSON.stringify(orderArray_<?=$this->table?>);
											if(orderCounter_<?=$this->table?> > 1){
												$.ajax({
													url : '<?=$_SERVER['PHP_SELF']?>?ajax=reorderElementsSubmit',
													type : 'post',
													data : 'orderString_elements_<?=$this->table?>='+encodeURIComponent(submitorderString_elements_<?=$this->table?>),
													success : function(mssg){
														showMessage('order updated');
														reloadElementsListNow();
													}
												});
											}
										}
									});
								});
							//-->
						</script>
						<ul class="listing_parent" id="form_listing_parent_elements">
		<?php $result = @mysql_query("SELECT * FROM dzpro_page_element_to_page LEFT JOIN dzpro_page_elements USING ( dzpro_page_element_id ) WHERE dzpro_page_element_map_area = '" . mysql_real_escape_string($area) . "' AND dzpro_page_id = " . (int)$this->primary_value . " GROUP BY dzpro_page_element_map_id ORDER BY dzpro_page_element_map_orderfield ASC"); if(mysql_num_rows($result) > 0){  while($row = mysql_fetch_assoc($result)){ ?>
							<li id="list_element_record_<?=(int)$row['dzpro_page_element_map_id']?>" class="record_listing element">
								<a class="delete_icon" href="#delete" title="Delete this record"><!-- block --></a>
								<span class="date"><strong><?=date('M j', strtotime($row['dzpro_page_element_map_date_added']))?></strong> <?=date('g:ia', strtotime($row['dzpro_page_element_map_date_added']))?></span>
								<strong class="title" title="<?=prepareTag($row['dzpro_page_element_name'])?>"><?=limitString(prepareStringHtml($row['dzpro_page_element_name']), LISTING_NAME_STR_LENGTH)?></strong>
								<strong class="sub" title="<?=prepareTag($row['dzpro_page_element_description'])?>"><?=limitString(prepareStringHtml($row['dzpro_page_element_description']), LISTING_DESCRIPTION_STR_LENGTH)?></strong>
								<p><?=limitString(prepareStringHtml($row['dzpro_page_element_template']),LISTING_NAME_STR_LENGTH)?></p>
								<div class="sort_element sort"><!-- block - sorting handle --></div>
							</li>
		<?php } mysql_free_result($result); } ?>
						</ul><!-- end listing_parent -->
		<?php
		
	}

	/*************************************************************/
	/*********************** GET CONSTANT VALUE ******************/
	/*************************************************************/	
	protected function getConstantValue($constant_name){
		$result = @mysql_query("SELECT * FROM dzpro_page_constants WHERE dzpro_page_id = " . (int)$this->primary_value . " AND dzpro_page_constant_name = '" . mysql_real_escape_string($constant_name) . "' ORDER BY dzpro_page_constant_date_added DESC LIMIT 1"); if(mysql_num_rows($result) > 0){ $return = null; while($row = mysql_fetch_assoc($result)){ $return = $row['dzpro_page_constant_value']; } mysql_free_result($result); return $return; } return null;
	}
	
	/*************************************************************/
	/*********************** PRINT CONSTANTS *********************/
	/*************************************************************/	
	protected function printConstantsTarget(){
		if(!isset($this->templates[$this->page_template]['constants']) or empty($this->templates[$this->page_template]['constants'])){ return null; }
		?>
						<script type="text/javascript">
							<!-- 
								$().ready(function(){
									$('#save-settings-button').click(function(){
										var settingsObject = {};
										$('#page_settings_area input[type=text]').each(function(){
											if($(this).val().length > 0){
												settingsObject[$(this).attr('name')] = $(this).val();
											}
										});
										var jsonLint = JSON.stringify(settingsObject);
										$.ajax({
											url: '<?=$_SERVER['PHP_SELF']?>?ajax=savePageSettings&record_id=<?=(int)$this->primary_value?>',
											type: 'post',
											data: 'theSettings='+encodeURIComponent(jsonLint),
											success: function(mssg){ showMessage('settings saved'); },
											error: function(error){	showMessage(error); } 
										});
									})
								});
							//-->
						</script>
						<div class="input_iframe" id="input_row_iframe_constants_<?=$this->table?>">
							<div class="table_name">
								<table cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td style="width: 170px;">
												Set page settings
											</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div style="background-color: #dee3e9; padding: 12px;" id="page_settings_area">
		<?php
			foreach($this->templates[$this->page_template]['constants'] as $constant_name){
		?>
								<div class="input_row inner_shadow" id="input_row_<?=$constant_name?>">
									<table cellpadding="0" cellspacing="0">
										<tbody>
											<tr>
												<td class="label">
													<?=$constant_name?>
												</td>
												<td class="input">
													<div class="inner_holder">
														<input name="<?=$constant_name?>" title="Enter value." type="text" value="<?=self::getConstantValue($constant_name)?>" />
													</div><!-- .inner_holder -->
												</td>
											</tr>
										</tbody>
									</table>
								</div>		
		<?php
			}
		?>
								<div class="button_row">
									<table cellpadding="0" cellspacing="0">
										<tbody>
											<tr>
												<td style="text-align: left;">
													<input type="reset" value="Reset Form" />	
												</td>
												<td style="text-align: right;">
													<input type="submit" name="form_submit" id="save-settings-button" value="Save" class="save_button" onclick="javascript:return false;" />
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
		<?php	
	}

	/*************************************************************/
	/*********************** PRINT TEMPLATE INTERFACE ************/
	/*************************************************************/
	public function printTemplateInterface(){
		?>
			<div class="form_area" method="post" style="margin-top: -25px;">
				<?=self::printElementTargets()?>
				<?=self::printContentTargets()?>
				<?=self::printConstantsTarget()?>
			</div>
		<?php
	}
		
	/*************************************************************/
	/*********************** BUILD FORM BLOCK ********************/
	/*************************************************************/
	public function buildPageBlock(){
	
		//lets build the database editor
		parent::buildFormBlock();
		
		//if we have a valid template - lets show the page ui stuff	
		if(isset($this->page_template) and is_file(FRONTEND_DOCUMENT_ROOT . $this->page_template)){ self::printTemplateInterface(); } 
	
	}
	
}

?>