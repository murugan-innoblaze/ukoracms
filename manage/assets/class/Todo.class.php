<?php

class Todo extends FormBasics {

	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
	
		//extend from parent
		parent::__construct($db, $table_name, $parameters, $sticky_fields);
		
		//reload canvas
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'reloadTodoLogCanvas'){ self::printTodoLogEntries(); exit(0); }
		
		//insert log entry
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'addLogEntry'){ echo self::insertTodoLogEntry(isset($_POST['theplan']) ? $_POST['theplan'] : null); exit(0); }
		
		//stop the timer
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'stopTheTimer'){ echo self::stopTheTimer(isset($_POST['logId']) ? (int)$_POST['logId'] : null, isset($_POST['status']) ? (string)$_POST['status'] : null); exit(0); }

		//markTodoAllDone
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'markTodoAllDone'){ echo self::markTodoAllDone(isset($_POST['logId']) ? (int)$_POST['logId'] : null); exit(0); }

		//updateThisTodoValue
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'updateThisTodoValue'){ echo self::updateThisTodoValue(isset($_POST['logId']) ? (int)$_POST['logId'] : null, isset($_POST['value']) ? (int)$_POST['value'] : null); exit(0); }

	}

	/*************************************************************/
	/*********************** UPDATE THIS TODO VALUE **************/
	/*************************************************************/
	private function updateThisTodoValue($log_id = null, $value = null){
		if(!self::isSuperUser() or !have($log_id) or !have($value)){ return null; }
		$todo = mysql_query_get_row(" SELECT * FROM dzpro_project_todo_log WHERE dzpro_project_todo_log_id = '" . mysql_real_escape_string($log_id) . "' ");
		if(have($todo) and (false !== mysql_update(" UPDATE dzpro_project_todo_log SET dzpro_project_todo_log_end = '" . mysql_real_escape_string(date('Y-m-d H:i:s', strtotime($todo['dzpro_project_todo_log_start']) + ((int)$value * 60))) . "' WHERE dzpro_project_todo_log_id = '" . mysql_real_escape_string((int)$todo['dzpro_project_todo_log_id']) . "' "))){ return 'success'; }
		return false;
	}

	/*************************************************************/
	/*********************** MARK TODO ALL DONE ******************/
	/*************************************************************/
	private function markTodoAllDone($log_id = null){
		if(false !== ($log_row = mysql_query_get_row(" SELECT * FROM dzpro_project_todos LEFT JOIN dzpro_project_todo_log USING ( dzpro_project_todo_id ) WHERE dzpro_project_todo_log_id = '" . mysql_real_escape_string((int)$log_id) . "' "))){ if(false !== mysql_update(" UPDATE dzpro_project_todos SET dzpro_project_todo_completed = 1 WHERE dzpro_project_todo_id = '" . mysql_real_escape_string((int)$log_row['dzpro_project_todo_id']) . "' ")){ return 'success'; } }
		return 'failed';
	}

	/*************************************************************/
	/*********************** STOP THE TIMER **********************/
	/*************************************************************/
	private function stopTheTimer($log_id = null, $status = null){
		return (false !== mysql_update(" UPDATE dzpro_project_todo_log SET dzpro_project_todo_log_end = NOW(), dzpro_project_todo_log_status = '" . mysql_real_escape_string($status) . "' WHERE dzpro_project_todo_log_id = '" . mysql_real_escape_string((int)$log_id) . "' AND ( dzpro_project_todo_log_end IS NULL OR dzpro_project_todo_log_end = '0000-00-00 00:00:00' ) ")) ? 'success' : 'failed';
	}

	/*************************************************************/
	/*********************** INSERT TODO LOG ENTRY ***************/
	/*************************************************************/
	private function insertTodoLogEntry($entry = null){
		return (false !== mysql_insert(" INSERT INTO dzpro_project_todo_log ( dzpro_project_todo_id, dzpro_project_todo_log_start, dzpro_project_todo_log_plan, dzpro_admin_id, dzpro_project_todo_log_date_added ) VALUES ( '" . mysql_real_escape_string($this->primary_value) . "', '" . mysql_real_escape_string(date('Y-m-d H:i:s')) . "', '" . mysql_real_escape_string($entry) . "', '" . mysql_real_escape_string((int)$_SESSION['dzpro_admin_id']) . "', NOW() ) ")) ? 'success' : 'failed';
	}

	/*************************************************************/
	/*********************** IS THERE AN ACTIVE LOG ITEM *********/
	/*************************************************************/
	private function isThereAnActiveLogItem(){
		return mysql_query_got_rows(" SELECT * FROM dzpro_project_todo_log WHERE dzpro_project_todo_id = '" . mysql_real_escape_string((int)$this->primary_value) . "' AND dzpro_admin_id = '" . mysql_real_escape_string((int)$_SESSION['dzpro_admin_id']) . "' AND ( dzpro_project_todo_log_end IS NULL OR dzpro_project_todo_log_end = '0000-00-00 00:00:00' ) ");
	}

	/*************************************************************/
	/*********************** PRINT TODO LOG ENTRIES **************/
	/*************************************************************/
	public function printTodoLogEntries(){
		?>
			<div class="input_row inner_shadow">
				<?php $log_entries = mysql_query_on_key(" SELECT * FROM dzpro_project_todo_log LEFT JOIN dzpro_admins USING ( dzpro_admin_id ) WHERE dzpro_project_todo_id = '" . mysql_real_escape_string((int)$this->primary_value) . "' ORDER BY  dzpro_project_todo_log_start DESC ", 'dzpro_project_todo_log_id'); if(have($log_entries)){ $count = 1; foreach($log_entries as $log_id => $log_entry){ $entry_duration = strtotime($log_entry['dzpro_project_todo_log_end']) - strtotime($log_entry['dzpro_project_todo_log_start']); ?>
				<table cellpadding="0" cellspacing="0">
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
									<p><strong><?=prepareStringHtml($log_entry['dzpro_admin_name'])?>:</strong> <?=prepareStringHtmlFlat($log_entry['dzpro_project_todo_log_plan'])?></p>
									<p><strong>Status:</strong> <?=have($log_entry['dzpro_project_todo_log_status']) ? prepareStringHtmlFlat($log_entry['dzpro_project_todo_log_status']) : 'In progress.'?></p>
								</div><!-- .inner_holder -->
							</td>
							<?php if($entry_duration > 0){ ?>
							<td style="text-align: right; padding-right: 12px;" class="regular_text">
								<span class="highlight" style="font-size: 15px;"><?=ceil($entry_duration / 60)?> minute<?=(ceil($entry_duration / 60) > 1) ? 's' : null?></span>
								<?php if(self::isSuperUser()){ ?>
								<div class="edit_icon" onclick="javascript: var value = prompt('Enter new value'); if(value.length > 0 && value > 0){ updateThisTodoValue(<?=(int)$log_id?>, value); return true; } return false;" title="Click to change duration"><!-- edit value --></div>
								<?php } ?>
							</td>
							<?php }else{ ?>
							<td style="text-align: right; padding-right: 12px; padding-top: 12px; padding-bottom: 12px;">
								<img src="/assets/img/manager/timer-loader.gif" alt="Timer loader" />&nbsp;&nbsp;
								<?php if($log_entry['dzpro_admin_id'] == $_SESSION['dzpro_admin_id']){ ?>
								<input name="form_submit" value="Stop Timer" class="save_button" type="submit" onclick="javascript: stopTheTimer(<?=(int)$log_id?>, '<?=prepareTag($log_entry['dzpro_project_todo_log_plan'])?>'); return false;" />
								<?php }else{ ?>
								<strong class="highlight alert"><?=prepareStringHtml($log_entry['dzpro_admin_name'])?></strong>
								<?php } ?>
							</td>
							<?php } ?>
						</tr>
					</tbody>
				</table>
				<?php if($count != sizeof($log_entries)){ ?>
				<div class="line"><!-- line --></div>
				<?php } ?>
				<?php $count++; } }else{ ?>
				<table cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td class="plain">
								no logged entries
							</td>
						</tr>
					</tbody>
				</table>
				<?php } ?>
			</div>		
		<?php	
	}

	/*************************************************************/
	/*********************** PRINT START LOG ENTRY ***************/
	/*************************************************************/	
	public function printStartLogEntry(){
		if(!have($this->selected_row)){ return null; }
		?>
			<script type="text/javascript">
				<!--

					/*************************************************************/
					/**************** UPDATE TODO VALUE **************************/
					/*************************************************************/			
					function updateThisTodoValue(log_id, value){
						$.ajax({
							url : '<?=$_SERVER['REQUEST_URI']?>',
							type : 'post', 
							data : 'ajax=updateThisTodoValue&logId=' + encodeURIComponent(log_id) + '&value=' + encodeURIComponent(value),
							success : function(mssg){
								if(mssg != undefined && mssg == 'success'){
									reloadTodoLogCanvas();
								}
							}
						});
					}

					/*************************************************************/
					/**************** STOP THE TIMER *****************************/
					/*************************************************************/
					function stopTheTimer(log_id, theplan){
						var status = prompt('Cool what is the status of ' + theplan);
						if(status != undefined && status != ''){
							$.ajax({
								url : '<?=$_SERVER['REQUEST_URI']?>',
								type : 'post',
								data : 'ajax=stopTheTimer&logId=' + encodeURIComponent(log_id) + '&status=' + encodeURIComponent(status),
								success : function(mssg){
									if(mssg != undefined && mssg == 'success'){
										if(confirm('Are you all done with: ' + theplan + '? If not, that\'s ok. Just click cancel.')){ markTodoAllDone(log_id); }else{ reloadTodoLogCanvas(); }
										$('#start_the_timer_ui_holder').show();
									}else{
										if(confirm('I could not stop this timer. Has the timer already been stopped in another browser window perhaps?')){ window.location=window.location; }
									}
								}
							})
						}else{ 
							alert('Please share your status to stop the timer.');
						}
					}

					/*************************************************************/
					/**************** MARK TODO ALL DONE *************************/
					/*************************************************************/
					function markTodoAllDone(log_id){
						$.ajax({
							url : '<?=$_SERVER['REQUEST_URI']?>',
							type : 'post',
							data : 'ajax=markTodoAllDone&logId=' + encodeURIComponent(log_id),
							success : function(mssg){
								if(mssg != undefined && mssg == 'success'){
									alert('This todo has been marked completed.');
									reloadTodoLogCanvas();
								}
							}				
						});
					}

					/*************************************************************/
					/**************** RELOAD LOG CANVAS **************************/
					/*************************************************************/						
					function reloadTodoLogCanvas(){
						$.ajax({
							url : '<?=$_SERVER['REQUEST_URI']?>',
							type : 'post',
							data : 'ajax=reloadTodoLogCanvas',
							success : function(html){
								if(html != undefined){
									$('#todo_log_canvas').html(html);
								}
							}
						});
					}

					/*************************************************************/
					/**************** HANDLE CLICK ON TIMER START ****************/
					/*************************************************************/	
					function startTheTimeActions(){
						
						/*************************************************************/
						/**************** GET WORKING ON SUBJECT *********************/
						/*************************************************************/						
						var answer = $('#working-on-text-input').val();
						if(answer == '' || answer == undefined){
							answer = prompt('What are you planning on working on?');
							if(answer != '' && answer != undefined){
								$('#working-on-text-input').val(answer);
							}else{
								alert('No need to be secretive.');
								return false;
							}
						}

						/*************************************************************/
						/**************** CONFIRM INTENTION **************************/
						/*************************************************************/							
						if(confirm('Are you ready to start on: ' + answer + '? Don\'t forget to stop the timer when you are done. Thanks!')){
							alert('Great! I started the timer. Just let me know when you are done!');
							$('#working-on-text-input').val('');
							$.ajax({
								url : '<?=$_SERVER['REQUEST_URI']?>',
								type : 'post',
								data : 'ajax=addLogEntry&theplan=' + encodeURIComponent(answer),
								success : function(mssg){
									if(mssg != undefined && mssg == 'success'){
										$('#start_the_timer_ui_holder').hide();
										reloadTodoLogCanvas();
									}
								},
								error : function(error){
									alert('error: ' + error);
								}
							});
						}else{
							alert('Ok, just let me know when you are.');
							return false;
						}
						
					}

					/*************************************************************/
					/**************** ATTACH LISTENERS ***************************/
					/*************************************************************/					
					$().ready(function(){
						$('#start-the-timer').live('click', startTheTimeActions);
						$('#working-on-text-input').live('keyup', function(event){
							if(event.keyCode == 13 && $('#working-on-text-input').val().length > 2){
								$('#working-on-text-input').blur();
								startTheTimeActions();
							}
						});
					});
					
				//-->
			</script>
			<div id="start_the_timer_ui_holder" <?php if(self::isThereAnActiveLogItem()){ ?>style="display: none;"<?php } ?>>
				<div class="form_area" style="margin-top: -27px;">		
					<div style="height: 31px; padding: 13px 29px 0 29px; font-size: 16px; color: black; text-shadow: -1px 1px 1px #ffffff;">
						<strong>Start the timer</strong>
					</div>
					<div class="input_row inner_shadow">
						<table cellpadding="0" cellspacing="0">
							<tbody>
								<tr>
									<td class="label">
										what is the plan?
										<span class="label_sub_text">
											what are you planning on working on?
										</span>																		
									</td>
									<td class="input">
										<div class="inner_holder">
											<input type="text" name="working-on-text" id="working-on-text-input" class="touched" />
										</div><!-- .inner_holder -->
									</td>
									<td style="text-align: right; padding-right: 12px;">
										<input name="form_submit" id="start-the-timer" value="Start Timer" class="save_button" type="submit" />
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="form_area" style="margin-top: -27px;">
				<div style="height: 31px; padding: 13px 29px 0 29px; font-size: 16px; color: black; text-shadow: -1px 1px 1px #ffffff;">
					<strong>Activity log</strong>
				</div>
				<div id="todo_log_canvas">
					<?=self::printTodoLogEntries()?>
				</div><!-- end todo_log_canvas -->	
			</div>		
		<?php
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
		/******************************** START LOG ENTRY UI ******************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::printStartLogEntry();

		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** FOREIGN TABLE BLOCKS ****************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::printForeignTablesBlock();	
	}
	
}

?>