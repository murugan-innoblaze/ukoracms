<?php

class Project extends FormBasics {

	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
	
		//extend from parent
		parent::__construct($db, $table_name, $parameters, $sticky_fields);
		
		//reload canvas
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'reloadTodoLogCanvas'){ self::printTodoLogEntries(); exit(0); }
		
		//insert log entry
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'addLogEntry'){ echo self::insertTodoLogEntry(isset($_POST['theplan']) ? $_POST['theplan'] : null, isset($_POST['todoId']) ? $_POST['todoId'] : null); exit(0); }
		
		//stop the timer
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'stopTheTimer'){ echo self::stopTheTimer(isset($_POST['logId']) ? (int)$_POST['logId'] : null, isset($_POST['status']) ? (string)$_POST['status'] : null); exit(0); }
		
		//reload todo options
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'reloadTodoOptions'){ self::loadPickTodoOptions(); exit(0); }
		
		//add new todo record
		if(isset($_POST['ajax']) and $_POST['ajax'] == 'addNewTodoRecord'){ echo self::addNewTodoRecord(isset($_POST['name']) ? $_POST['name'] : null, isset($_POST['description']) ? $_POST['description'] : null); exit(0); } 
		
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
	private function insertTodoLogEntry($entry = null, $todo_id = null){
		if(!have($entry)){ return false; }
		if(!have($todo_id)){ return false; }
		if(self::isThereAnActiveLogItem()){ return false; }
		return (false !== mysql_insert(" INSERT INTO dzpro_project_todo_log ( dzpro_project_todo_id, dzpro_project_todo_log_start, dzpro_project_todo_log_plan, dzpro_admin_id, dzpro_project_todo_log_date_added ) VALUES ( '" . mysql_real_escape_string($todo_id) . "', '" . mysql_real_escape_string(date('Y-m-d H:i:s')) . "', '" . mysql_real_escape_string($entry) . "', '" . mysql_real_escape_string((int)$_SESSION['dzpro_admin_id']) . "', NOW() ) ")) ? 'success' : 'failed';
	}

	/*************************************************************/
	/*********************** IS THERE AN ACTIVE LOG ITEM *********/
	/*************************************************************/
	private function isThereAnActiveLogItem(){
		return mysql_query_got_rows(" SELECT * FROM dzpro_project_todo_log LEFT JOIN dzpro_project_todos USING ( dzpro_project_todo_id ) WHERE dzpro_project_id = '" . mysql_real_escape_string((int)$this->primary_value) . "' AND dzpro_admin_id = '" . mysql_real_escape_string((int)$_SESSION['dzpro_admin_id']) . "' AND ( dzpro_project_todo_log_end IS NULL OR dzpro_project_todo_log_end = '0000-00-00 00:00:00' ) ");
	}

	/*************************************************************/
	/*********************** ADD NEW TODO RECORD *****************/
	/*************************************************************/	
	private function addNewTodoRecord($name = null, $description = null){
		if(!have($name)){ return false; }
		return (false !== mysql_insert(" INSERT INTO dzpro_project_todos ( dzpro_project_todo_name, dzpro_project_todo_description, dzpro_project_id, dzpro_project_todo_date_added ) VALUES ( '" . mysql_real_escape_string($name) . "', '" . mysql_real_escape_string($description) . "', '" . mysql_real_escape_string((int)$this->primary_value) . "', NOW() ) ")) ? 'success' : 'failed';
	}

	/*************************************************************/
	/*********************** PRINT TODO LOG ENTRIES **************/
	/*************************************************************/
	public function printTodoLogEntries(){
		?>
			<div class="input_row inner_shadow">
			<?php $log_entries = mysql_query_on_key(" SELECT * FROM dzpro_project_todo_log LEFT JOIN dzpro_admins USING ( dzpro_admin_id ) LEFT JOIN dzpro_project_todos USING ( dzpro_project_todo_id ) WHERE dzpro_project_id = '" . mysql_real_escape_string((int)$this->primary_value) . "' ORDER BY  dzpro_project_todo_log_start DESC ", 'dzpro_project_todo_log_id'); if(have($log_entries)){ $count = 1; foreach($log_entries as $log_id => $log_entry){ $entry_duration = strtotime($log_entry['dzpro_project_todo_log_end']) - strtotime($log_entry['dzpro_project_todo_log_start']); ?>
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
									<h2><?=prepareStringHtmlFlat($log_entry['dzpro_project_todo_name'])?></h2>
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
				<?php if($count != sizeof($log_entries)){ ?><div class="line"><!-- line --></div><?php } ?>									
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
	/*********************** PRINT TODO LOG INTERFACE ************/
	/*************************************************************/	
	public function addTodoInterface(){
		if(!have($this->selected_row)){ return null; }
		?>
			<script type="text/javascript">
				<!--

					/*************************************************************/
					/**************** QUICK ADD TODO ITEM ************************/
					/*************************************************************/
					function quickAddNewTodo(){
						var title = prompt('Ok, let me know what to name this \'todo\'?');
						if(title != undefined && title != ''){
							var description = prompt('Can you give me some more details? (optional)');
							$.ajax({
								url : '<?=$_SERVER['REQUEST_URI']?>',
								type : 'post', 
								data : 'ajax=addNewTodoRecord&name=' + encodeURIComponent(title) + '&description=' + encodeURIComponent(description),
								success : function(mssg){
									if(mssg != undefined && mssg == 'success'){
										reloadTodoOptions();
										alert('Todo: ' + title + ' has been added');
									}
								}
							});
						}else{
							alert('no todo was added');
						}
					}
					
					/*************************************************************/
					/**************** RELOAD TODO OPTOINS ************************/
					/*************************************************************/					
					function reloadTodoOptions(){
						$.ajax({
							url : '<?=$_SERVER['REQUEST_URI']?>',
							type : 'post', 
							data : 'ajax=reloadTodoOptions',
							success : function(html){
								if(html != undefined && html != ''){
									$('#todo_select_box').html(html);
								}
							}
						});
					}
					
					/*************************************************************/
					/**************** STOP THE TIMER *****************************/
					/*************************************************************/					
					$().ready(function(){
					
					});
					
				//-->
			</script>
			<div class="form_area" style="margin-top: -54px;">
				<div style="height: 31px; padding: 13px 29px 0 29px; font-size: 16px; color: black; text-shadow: -1px 1px 1px #ffffff;">
					<strong>Do you need to add a 'todo' to this project?</strong>
				</div>
				<div class="input_row inner_shadow" style="padding: 12px;">
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td style="text-align: left;">
									<input type="submit" onclick="javascript: quickAddNewTodo(); return false;" class="save_button" value="Quick Add Todo" />
								</td>
								<td style="text-align: right;">
									<input type="submit" onclick="javascript:document.location='/calendar/todos.php?action=new'; return false;" class="save_button" value="Add New Todo" />
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>		
		<?php
	}

	/*************************************************************/
	/*********************** PRINT TODO OPTIONS ******************/
	/*************************************************************/		
	protected function loadPickTodoOptions(){
		$todos = mysql_query_on_key(" SELECT * FROM dzpro_project_todos WHERE dzpro_project_id = '" . mysql_real_escape_string($this->primary_value) . "' AND dzpro_project_todo_completed = 0 ", 'dzpro_project_todo_id');
		if(!have($todos)){ 
			?><option value="">no todo's added yet</option><?php
		}else{
			?><option value="">-- pick todo --</option><?php
			foreach($todos as $todo_id => $todo){
			?><option value="<?=(int)$todo_id?>"><?=$todo['dzpro_project_todo_name']?></option><?php
			}
		}
		return true;
	}

	/*************************************************************/
	/*********************** PRINT TODO LOG INTERFACE ************/
	/*************************************************************/	
	public function printTodoInterface(){
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
				/**************** MARK TODO ALL DONE *************************/
				/*************************************************************/
				function markTodoAllDone(log_id){
					$.ajax({
						url : '<?=$_SERVER['REQUEST_URI']?>',
						type : 'post',
						data : 'ajax=markTodoAllDone&logId=' + encodeURIComponent(log_id),
						success : function(mssg){
							if(mssg != undefined && mssg == 'success'){
								reloadTodoOptions();
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
									reloadTodoLogCanvas();
									$('#start_the_timer_ui_holder').show();
									if(confirm('Are you all done with: ' + theplan + '? If not, that\'s ok. Just click cancel.')){ markTodoAllDone(log_id); }
								}else{
									if(confirm('I could not stop this timer. Has the timer already been stopped in another browser window perhaps?')){ window.location=window.location; }
								}
							}
						});
					}else{ 
						alert('Please share your status to stop the timer.');
					}
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
					/**************** GET THE SELECTED TODO **********************/
					/*************************************************************/	
					var todo_id = $('#todo_select_box option:selected').val();
					if(todo_id == undefined || todo_id == ''){ alert('Oops! Please pick a \'todo\''); return false; }
					
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
							data : 'ajax=addLogEntry&todoId=' + encodeURIComponent(todo_id) + '&theplan=' + encodeURIComponent(answer),
							success : function(mssg){
								if(mssg != undefined && mssg == 'success'){
									$('#start_the_timer_ui_holder').hide();
									reloadTodoLogCanvas();
								}else{
									alert('You already have a timer running');
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
									please pick																
								</td>
								<td class="input">
									<div class="inner_holder">
										<select name="todo_select_box" id="todo_select_box">
											<?=self::loadPickTodoOptions()?>
										</select>
									</div><!-- .inner_holder -->
								</td>
							</tr>
						</tbody>
					</table>
					<div class="line"><!-- line --></div>
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
		</div><!-- end start_the_timer_ui_holder -->
		<div class="form_area" style="margin-top: -27px;">
			<div style="height: 31px; padding: 13px 29px 0 29px; font-size: 16px; color: black; text-shadow: -1px 1px 1px #ffffff;">
				<strong>Activity log</strong>
			</div>
			<div id="todo_log_canvas">
				<?=self::printTodoLogEntries()?>
			</div>
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
		/******************************** ADD TODO BUTTON *********************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::addTodoInterface();

		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** PRINT TODO LOG INTERFACE ************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::printTodoInterface();

		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** FOREIGN TABLE BLOCKS ****************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::printForeignTablesBlock();	
	}
	
}

?>