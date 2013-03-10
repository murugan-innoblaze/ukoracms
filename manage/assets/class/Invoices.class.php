<?php

class Invoices extends FormBasics {

	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
	
		//extend from parent
		parent::__construct($db, $table_name, $parameters, $sticky_fields);

		//client details holder
		$this->client_details = array();

		//get client details
		self::getClientDetails();

		//reload canvas
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'reloadTodoLogCanvas'){ self::printTodoLogEntries(); exit(0); }
		
		//reload canvas
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'reloadInvoiceTotalBlock'){ self::printInvoiceTotalContents(); exit(0); }

		//add todo to invoice
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'addTodoToInvoice'){ echo self::addTodoToInvoice(isset($_POST['todo_id']) ? (int)$_POST['todo_id'] : null); exit(0); }	

	}

	/*************************************************************/
	/*********************** GET CLIENT DETAILS ******************/
	/*************************************************************/		
	protected function getClientDetails(){
		if(!isset($this->selected_row['dzpro_client_id']) or !have($this->selected_row['dzpro_client_id'])){ return null; }
		$this->client_details = mysql_query_get_row(" SELECT * FROM dzpro_clients WHERE dzpro_client_id = '" . mysql_real_escape_string($this->selected_row['dzpro_client_id']) . "' ");
		return $this->client_details;
	}

	/*************************************************************/
	/*********************** GET TODO BY ID **********************/
	/*************************************************************/			
	protected function getTodoById($todo_id = null){
		return mysql_query_get_row(" SELECT * FROM dzpro_project_todo_log LEFT JOIN dzpro_project_todos USING ( dzpro_project_todo_id ) LEFT JOIN dzpro_projects USING ( dzpro_project_id ) WHERE dzpro_project_todo_log_id = '" . mysql_real_escape_string($todo_id) . "' ");
	}

	/*************************************************************/
	/*********************** UPDATE LOG ITEM WITH INVOICE ********/
	/*************************************************************/		
	protected function updateLogItemWithInvoiceId($todo_id = null){
		return mysql_update(" UPDATE dzpro_project_todo_log SET dzpro_invoice_id = '" . mysql_real_escape_string((int)$this->primary_value) . "' WHERE dzpro_project_todo_log_id = '" . mysql_real_escape_string((int)$todo_id) . "' ");
	}
	
	/*************************************************************/
	/*********************** ADD INVOICE ITEM TO INVOICE *********/
	/*************************************************************/			
	protected function addTodoToInvoice($todo_id = null){
		if(isset($this->primary_value) and have($this->primary_value)){ 
			if(false !== ($todo_log_row = self::getTodoById($todo_id))){
				$entry_duration_hours = ceil((strtotime($todo_log_row['dzpro_project_todo_log_end']) - strtotime($todo_log_row['dzpro_project_todo_log_start'])) / 60) / 60;
				if(false !== mysql_insert(" INSERT INTO dzpro_invoice_items ( dzpro_invoice_id, dzpro_invoice_item_label, dzpro_invoice_item_notes, dzpro_invoice_item_quantity, dzpro_invoice_item_price, dzpro_invoice_item_date_added ) VALUES ( '" . mysql_real_escape_string((int)$this->primary_value) . "', '" . mysql_real_escape_string(date('l F jS, Y g:iA', strtotime($todo_log_row['dzpro_project_todo_log_start'])) . ' ' . $todo_log_row['dzpro_project_todo_name']) . "', '" . mysql_real_escape_string($todo_log_row['dzpro_project_todo_log_plan'] . ' ' . $todo_log_row['dzpro_project_todo_log_status']) . "', '" . mysql_real_escape_string((float)$entry_duration_hours) . "', '" . mysql_real_escape_string((float)$this->client_details['dzpro_client_rate']) . "', NOW() ) ")){
					self::updateLogItemWithInvoiceId($todo_id);
					return 'success';
				}			
			}
		}
		return 'failed';
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
		/******************************** FOREIGN TABLE BLOCKS ****************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::printInvoiceTotalBlock();	

		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** FOREIGN TABLE BLOCKS ****************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::printAddTodoFromLogUI();	
		
	}

	/*************************************************************/
	/*********************** PRINT ADD TODO FROM LOG UI **********/
	/*************************************************************/	
	protected function printAddTodoFromLogUI(){
		?>
			<script type="text/javascript">
				<!--
					function reloadInvoiceTotalBlock(){
						$.ajax({
							url : '<?=$_SERVER['REQUEST_URI']?>',
							type : 'post',
							data : 'ajax=reloadInvoiceTotalBlock',
							success : function(html){
								if(html != undefined && html.length > 0){
									$('#invoice_total_contents_canvas').html(html);
								}
							}
						});
					}
					function reloadTodoLogCanvas(){
						$.ajax({
							url : '<?=$_SERVER['REQUEST_URI']?>',
							type : 'post',
							data : 'ajax=reloadTodoLogCanvas',
							success : function(html){
								if(html != undefined && html.length > 0){
									$('#todo_log_canvas').html(html);
								}
							}
						});
					}
					function addTodoItemToInvoice(log_id){
						$.ajax({
							url : '<?=$_SERVER['REQUEST_URI']?>',
							type : 'post',
							data : 'ajax=addTodoToInvoice&todo_id=' + encodeURIComponent(log_id),
							success : function(mssg){
								if(mssg != undefined && mssg == 'success'){
									$('#log_entry_' + log_id).fadeOut();
									document.getElementById('iframe_for_dzpro_invoice_items').src = document.getElementById('iframe_for_dzpro_invoice_items').src;
									reloadTodoLogCanvas();
									reloadInvoiceTotalBlock();
								}
							}
						});
					}
				//-->
			</script>
			<div class="form_area" style="margin-top: -27px;">
			<div style="height: 31px; padding: 13px 29px 0 29px; font-size: 16px; color: black; text-shadow: -1px 1px 1px #ffffff;">
				<strong>Add project todo</strong>
			</div>
			<div id="todo_log_canvas">
				<?=self::printTodoLogEntries()?>
			</div>
		</div>			
		<?php
	}
	
	/*************************************************************/
	/*********************** PRINT TODO LOG ENTRIES **************/
	/*************************************************************/
	public function printTodoLogEntries(){
		?>
			<div class="input_row inner_shadow">
			<?php $log_entries = mysql_query_on_key(" 
					SELECT * FROM dzpro_project_todo_log LEFT JOIN dzpro_admins USING ( dzpro_admin_id ) 
					LEFT JOIN 
						dzpro_project_todos 
					USING 
						( dzpro_project_todo_id ) 
					WHERE 
						dzpro_project_id = '" . mysql_real_escape_string(isset($this->selected_row['dzpro_project_id']) ? (int)$this->selected_row['dzpro_project_id'] : 0) . "' 
					AND 
						dzpro_project_todo_log_end > dzpro_project_todo_log_start
					AND 
						( dzpro_invoice_id = 0 OR dzpro_invoice_id IS NULL )
					ORDER BY 
						dzpro_project_todo_log_start DESC 
				", 'dzpro_project_todo_log_id'); if(have($log_entries)){ $count = 1; foreach($log_entries as $log_id => $log_entry){ $entry_duration = strtotime($log_entry['dzpro_project_todo_log_end']) - strtotime($log_entry['dzpro_project_todo_log_start']); ?>
				<table cellpadding="0" cellspacing="0" id="log_entry_<?=$log_id?>">
					<tbody>
						<tr>
							<td class="label">
								log entry
								<span style="display: block; font-size: 11px; color: #222; padding-top: 5px; text-align: right; font-weight: normal;">
									<?=date('M jS Y, H:i', strtotime($log_entry['dzpro_project_todo_log_start']))?>
								</span>															
							</td>
							<td class="regular_text">
								<div class="inner_holder">
									<h2><?=prepareStringHtmlFlat($log_entry['dzpro_project_todo_name'])?></h2>
									<p><strong><?=prepareStringHtml($log_entry['dzpro_admin_name'])?>:</strong> <?=prepareStringHtmlFlat($log_entry['dzpro_project_todo_log_plan'])?></p>
									<p><strong>Status:</strong> <?=have($log_entry['dzpro_project_todo_log_status']) ? prepareStringHtmlFlat($log_entry['dzpro_project_todo_log_status']) : 'In progress.'?></p>
									<p style="padding: 5px 0;"><span class="highlight alert" style="font-size: 15px;"><?=ceil($entry_duration / 60)?> minute<?=(ceil($entry_duration / 60) > 1) ? 's' : null?></span></p>
								</div><!-- .inner_holder -->
							</td>
							<td style="text-align: right; padding-right: 12px; width: 200px;" class="regular_text">
								<span class="highlight" style="font-size: 20px;">$<?=number_format(($this->client_details['dzpro_client_rate'] * ceil($entry_duration / 60) / 60), 2)?></span>
							</td>
							<td style="text-align: right; padding-right: 12px; padding-top: 12px; padding-bottom: 12px; width: 80px;">
								<input name="form_submit" value="Add" class="save_button" type="submit" onclick="javascript: addTodoItemToInvoice(<?=(int)$log_id?>); return false;" />
							</td>
						</tr>
					</tbody>
				</table>
				<?php if($count != sizeof($log_entries)){ ?><div class="line"><!-- line --></div><?php } ?>									
				<?php $count++; } }else{ ?>
				<table cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td class="plain">
								<?php if(isset($this->selected_row['dzpro_project_id']) and have($this->selected_row['dzpro_project_id'])){ ?>
								no logged entries
								<?php }else{ ?>
								please pick a project first
								<?php } ?>
							</td>
						</tr>
					</tbody>
				</table>			
			<?php } ?>
			</div>
		<?php	
	}	
	
	/*************************************************************/
	/*********************** PRINT INVOICE TOTAL BLOCK ***********/
	/*************************************************************/	
	protected function printInvoiceTotalBlock(){
		?>
			<div class="form_area" style="margin-top: -27px;">
				<div style="height: 31px; padding: 13px 29px 0 29px; font-size: 16px; color: black; text-shadow: -1px 1px 1px #ffffff;">
					<strong>Invoice Details</strong>
				</div>
				<div class="input_row inner_shadow" style="padding: 12px; text-align: right;" id="invoice_total_contents_canvas">
					<?=self::printInvoiceTotalContents()?>
				</div>
			</div>			
		<?php
	}

	/*************************************************************/
	/*********************** PRINT INVOICE TOTAL CONTENTS ********/
	/*************************************************************/	
	protected function printInvoiceTotalContents(){
		$invoice_total = 0; $invoice_details = mysql_query_flat(" SELECT * FROM dzpro_invoice_items WHERE dzpro_invoice_id = '" . mysql_real_escape_string((int)$this->primary_value) . "' "); if(have($invoice_details)){ foreach($invoice_details as $invoice_detail){ $invoice_total += $invoice_detail['dzpro_invoice_item_quantity'] * $invoice_detail['dzpro_invoice_item_price']; } }
		?>
			<h1 class="highlight" style="display: inline-block;">Total: $<?=number_format($invoice_total, 2)?></h1>			
		<?php 
	}
	
}

?>