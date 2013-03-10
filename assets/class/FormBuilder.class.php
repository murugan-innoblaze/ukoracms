<?php

class FormBuilder extends MapTable {

	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
	
		//extend from parent
		parent::__construct($db, $table_name, $parameters, $sticky_fields);

		//get form stats
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'get_form_stats' and have($_POST['interval']) and have($_POST['limit'])){ echo self::buildFormStatsContent($_POST['interval'], $_POST['limit'], have($_POST['query']) ? $_POST['query'] : null); exit(0); }
	
	}
		
	/*************************************************************/
	/*********************** BUILD HEAD JS BLOCK *****************/
	/*************************************************************/
	public function buildHeadBlock(){
		if(isset($this->need_upload) and $this->need_upload === true){
		?>
		<link href="<?=ASSETS_PATH?>/upl/uploadify.css" type="text/css" rel="stylesheet" />
		<script type="text/javascript" src="<?=ASSETS_PATH?>/upl/swfobject.js"></script>
		<script type="text/javascript" src="<?=ASSETS_PATH?>/upl/jquery.uploadify.v2.1.1.min.js"></script>
		<script type="text/javascript" src="<?=ASSETS_PATH?>/js/imgarea.js"></script>
		<link type="text/css" href="<?=ASSETS_PATH?>/css/imgareadefault.css" rel="stylesheet" media="all" />
		<?php
		} //end if ..need_upload
		?>
		<?php
		if(!empty($this->table)){
		?>
		<script type="text/javascript">
			<!--
				
				$().ready(function(){
					
					/**********************************/
					/****** remove default values *****/
					/**********************************/
					$('input.save_button, input.save_and_new_button', '#form_<?=md5($this->table)?>').live('click', function(){ <?=md5($this->table)?>_prepareSubmit(); });

					/**********************************/
					/****** select box disabled *******/
					/**********************************/					
					$('select', '#form_<?=md5($this->table)?>').each(function(){
						if($(this).attr('disabled')){ $(this).addClass('disabled'); }
					});
					
					/**********************************/
					/****** title val swaps ***********/
					/**********************************/
					$('input[type=text], input[type=password], textarea', '#form_<?=md5($this->table)?>').each(function(){
						if($(this).attr('readonly') == true){ $(this).addClass('disabled'); }
						if($(this).val() == ''){ $(this).val($(this).attr('title')); }else{ if($(this).val() == $(this).attr('title')){ $(this).removeClass('touched'); }else{ $(this).addClass('touched'); } }
					});
					$('input[type=text], input[type=password], textarea', '#form_<?=md5($this->table)?>').focus(function(){
						$('.on', '#form_<?=md5($this->table)?>').removeClass('on'); $(this).parent().addClass('on');
						if($(this).val() == $(this).attr('title')){ $(this).val('').removeClass('touched'); }else{ if($(this).val() == ''){ $(this).removeClass('touched'); }else{ $(this).addClass('touched'); } } }); $('select', '#form_<?=md5($this->table)?>').focus(function(){
					});
					$('input[type=text], input[type=password], textarea', '#form_<?=md5($this->table)?>').blur(function(){
						if($(this).val() == ''){ $(this).val($(this).attr('title')).removeClass('touched'); }else{ if($(this).val() == $(this).attr('title')){ $(this).removeClass('touched'); }else{ $(this).addClass('touched'); } }
					});
					$('select', '#form_<?=md5($this->table)?>').focus(function(){
						$('.on', '#form_<?=md5($this->table)?>').removeClass('on'); $(this).parent().addClass('on');
					});

					/**********************************/
					/****** set focus on click ********/
					/**********************************/
					$('.input_row', '#form_<?=md5($this->table)?>').click(function(){
						$(this).children('input:first, select:first').focus();
					});

					/**********************************/
					/****** remove value button *******/
					/**********************************/
					$('.close_icon', '#form_<?=md5($this->table)?>').click(function(){
						$(this).parent().children('input, textarea').val($(this).parent().children('input, textarea').attr('title'));
						$('#input_row_' + $(this).parent().children('input, textarea').removeClass('touched').attr('name')).removeClass('problem').removeClass('checked');
					});
					
					/**********************************/
					/****** check input trigger *******/
					/**********************************/
					$('input[type=text], input[type=password], textarea', '#form_<?=md5($this->table)?>').keyup(function(){
						var input_name = $(this).attr('name');
						var input_val = $(this).val();
						var input_title = $(this).attr('title');
						var input_status = checkInput<?=md5($this->table)?>(input_name, input_val, input_title);
						handleInputStatus<?=md5($this->table)?>(input_name, input_status);
					});
					$('input[type=text], input[type=password], textarea', '#form_<?=md5($this->table)?>').blur(function(){
						var input_name = $(this).attr('name');
						var input_val = $(this).val();
						var input_title = $(this).attr('title');
						var input_status = checkInput<?=md5($this->table)?>(input_name, input_val, input_title);
						handleInputStatus<?=md5($this->table)?>(input_name, input_status);
					});

					/*************************************************/
					/****** submit form when 'enter' is pressed ******/
					/*************************************************/
					$('input', '#form_<?=md5($this->table)?>').keyup(function(event){ if(event.keyCode == 13){ <?=md5($this->table)?>_prepareSubmit(); } });
					
					/*************************************************/
					/****** reset form when reset button is hit ******/
					/*************************************************/				
					$('input[type=reset]', '#form_<?=md5($this->table)?>').click(function(){ $('#form_<?=md5($this->table)?>').get(0).reset(); });
				
				});

				/**********************************/
				/****** check before submission ***/
				/**********************************/
				function <?=md5($this->table)?>_prepareSubmit(){
					$('input, textarea', '#form_<?=md5($this->table)?>').each(function(){
						if($(this).val() == $(this).attr('title')){
							$(this).val('');
						}
					});
					if(checkFormValidity<?=md5($this->table)?>()){
						$('#form_<?=md5($this->table)?>').submit();				
					}else{
						alert('Some fields are invalid.');
					}
				}

				/**********************************/
				/****** check input values ********/
				/**********************************/
				function addNewRecordRequest(oject){
					$('#form_<?=md5($this->table)?>').prepend('<input type="hidden" name="' + oject.attr('name') + '" value="' + oject.val() + '"" />');
				}

				/**********************************/
				/****** check form validity *******/
				/**********************************/
				function checkFormValidity<?=md5($this->table)?>(){
					
					//assume no problems are found
					var found_problems = false;
					
					//check input and text areas
					$('input[type=text], input[type=password], textarea', '#form_<?=md5($this->table)?>').each(function(){
						var input_name = $(this).attr('name');
						var input_val = $(this).val();
						var input_title = $(this).attr('title');
						var input_status = checkInput<?=md5($this->table)?>(input_name, input_val, input_title);
						handleInputStatus<?=md5($this->table)?>(input_name, input_status);
						if(input_status == 'false'){ found_problems = true; }
					});
					
					//check select boxes
					$('select', '#form_<?=md5($this->table)?>').each(function(){
						var input_name = $(this).attr('name');
						var input_val = $(this).children('option:selected').val();
						var input_title = $(this).attr('title');
						var input_status = checkInput<?=md5($this->table)?>(input_name, input_val, input_title);
						handleInputStatus<?=md5($this->table)?>(input_name, input_status);
						if(input_status == 'false'){ found_problems = true; }
					});
					
					//return boolean
					if(found_problems){ return false; }
					return true;
									
				}
				
				/**********************************/
				/****** handle input status *******/
				/**********************************/								
				function handleInputStatus<?=md5($this->table)?>(input_name, input_status){
					if(input_status == 'false'){
						$('.message_' + input_name + ', input[name=' + input_name + '], textarea[name=' + input_name + '], select', '#form_<?=md5($this->table)?>').addClass('problem').removeClass('checked'); $('#input_row_' + input_name + ' .field_status', '#form_<?=md5($this->table)?>').addClass('bad').removeClass('good');
					}
					if(input_status == 'true'){
						$('.message_' + input_name + ', input[name=' + input_name + '], textarea[name=' + input_name + '], select', '#form_<?=md5($this->table)?>').removeClass('problem').addClass('checked'); $('#input_row_' + input_name + ' .field_status', '#form_<?=md5($this->table)?>').addClass('good').removeClass('bad');
					}
					if(input_status == 'empty'){
						$('.message_' + input_name + ', input[name=' + input_name + '], textarea[name=' + input_name + '], select', '#form_<?=md5($this->table)?>').removeClass('problem').removeClass('checked'); $('#input_row_' + input_name + ' .field_status', '#form_<?=md5($this->table)?>').removeClass('bad').removeClass('good');
					}
				}				
				
				/**********************************/
				/****** check input values ********/
				/**********************************/
				function checkInput<?=md5($this->table)?>(name, value, title){
					switch(name){
						<?php
						foreach($this->fields as $key => $value){
							switch(true){
								
								//star means field can't be empty
								case(isset($value['field_regex']) and $value['field_regex'] == '*'):
						?>
						case '<?=$value['Field']?>':
							if(value == '' || value == title){
								return 'false';		
							}else{
								if(value != ''){
									return 'true';
								}else{
									return 'false';
								}
							}
						break;						
						<?php
								break;
								
								//regular regex
								case(isset($value['field_regex']) and strlen($value['field_regex']) > 2):
						?>
						case '<?=$value['Field']?>':
							if(value == '' || value == title){
								return 'false';		
							}else{
								if(value.match(<?=$value['field_regex']?>)){
									return 'true';
								}else{
									return 'false';
								}
							}
						break;
						<?php
								break;

							} //end switch field_regex
						} //end foreach
						?>
						default:
							return 'neutral';
						break;
					}
				}
			-->
		</script>
		<?php
		}
	}
	
	/*************************************************************/
	/*********************** BUILD EVENT BLOCK *******************/
	/*************************************************************/
	public function showEventBlock(){
		$return = true; //lets assume we want to show the form
		$frame_class = ($this->prepare_for_iframe === true) ? 'iframe' : null;
		if(isset($this->event) and !empty($this->event)){
			switch($this->event){
				case 'updated':
					$message = 'Record has been added';		
					$return = false;
				break;
				case 'not updated':
					$message = 'No changes made';
					$return = false;
				break;
				case 'inserted':
					$message = 'Record has been added';
					$return = false;
				break;
				case 'not inserted':
					$message = 'Record could not be added';
					$return = true;
				break;
				case 'deleted':
					$message = 'Record deleted';
					$return = false;
				break;
				case 'not deleted':
					$message = 'Record not deleted';
					$return = true;	
				break;
				default:
					$message = htmlentities($this->event);
					$return = true;
				break;
			}
		?>
			<script type="text/javascript">
				<!--
					function closeMessageButton(){ clearInterval(intervalVar); $('.form_message').slideUp(200); }
					var intervalVar = '';
					function showMessage(mssg){
						clearInterval(intervalVar);
						$('div.form_message #message_load_target').text(mssg);
						$('div.form_message').show();
						var startTimeMssgClose = 8;
						intervalVar = setInterval(function(){
							startTimeMssgClose = startTimeMssgClose - 1;
							$('.countdown').html('[' + startTimeMssgClose + ']');
							if(startTimeMssgClose == 0){ closeMessageButton(); }
						}, 1000);
					}
					$().ready(function(){
						$('.form_message button').click(closeMessageButton);
						var startTimeMssgClose = 8;
						setInterval(function(){
							startTimeMssgClose = startTimeMssgClose - 1;
							$('.countdown').html('[' + startTimeMssgClose + ']');
							if(startTimeMssgClose == 0){ closeMessageButton(); }
						}, 1000);
					});
				//-->
			</script>
			<div class="form_message">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/notification-icon.gif" alt="Notification Icon" /> <?=$message?></td></tr></tbody></table>
				<button>Close Message <span class="countdown">[8]</span></button>
			</div><!-- .form_message -->			
		<?php
		}else{
		?>
			<script type="text/javascript">
				<!--
					function closeMessageButton(){ clearInterval(intervalVar); $('.form_message').slideUp(200); }
					var intervalVar = '';
					function showMessage(mssg){
						clearInterval(intervalVar);
						$('div.form_message #message_load_target').text(mssg);
						$('div.form_message').show();
						var startTimeMssgClose = 8;
						intervalVar = setInterval(function(){
							startTimeMssgClose = startTimeMssgClose - 1;
							$('.countdown').html('[' + startTimeMssgClose + ']');
							if(startTimeMssgClose == 0){ closeMessageButton(); }
						}, 1000);
					}
					$().ready(function(){ $('.form_message button').click(closeMessageButton); });
				//-->
			</script>
			<div class="form_message" style="display: none;">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/notification-icon.gif" alt="Notification Icon" /><span id="message_load_target"><!-- message loads here --></span></td></tr></tbody></table>
				<button>Close Message <span class="countdown">[8]</span></button>
			</div><!-- .form_message -->		
		<?php
		}
		return $return;
	}

	/*************************************************************/
	/*********************** BUILD FORM TOOLBAR ******************/
	/*************************************************************/	
	public function buildFromToolbar(){
		$filter_out_fields = array('record_search', 'filter_key', 'filter_value');
		if(isset($this->prepare_for_iframe) and $this->prepare_for_iframe){
			$filter_out_fields = array('record_search', 'filter_key', 'filter_value', 'action');
			$need_to_keep_these = array('filter_key', 'filter_value', 'table_name');
			foreach($filter_out_fields as $filter_out_key => $filter_out_field){
				if(in_array($filter_out_field, $need_to_keep_these)){
					unset($filter_out_fields[$filter_out_key]);
				}
			}
		}
		?>
						<?php if(have($this->form_tools)){ ?>
						<script type="text/javascript">
							<!-- 
								$().ready(function(){
									$('li a.form_link', '#bucket').click(function(){
										$('li.selected', '#bucket').removeClass('selected');
										$(this).parents('li').addClass('selected');
									});
									$('#looking_glass').click(function(){ $('#bucket_search_text').focus(); });
									$('.form_tools_icon').click(function(){
										$('#form_tools_load_area_<?=md5($this->table)?>').toggle();
									});
									$('.form_tools_icon').hover(function(){
										$('.arrow_tools', '#form_tools_load_area_<?=md5($this->table)?>').show();
										$('.arrow_tools_down', '#form_tools_load_area_<?=md5($this->table)?>').hide();
									}, function(){
										$('.arrow_tools', '#form_tools_load_area_<?=md5($this->table)?>').hide();
										$('.arrow_tools_down', '#form_tools_load_area_<?=md5($this->table)?>').show();		
									});
								});
							//-->
						</script>
						<div id="form_tools_load_area_<?=md5($this->table)?>" class="form_tools_window">
							<img src="/assets/img/tools-arrow-icon.gif" alt="arrow" class="arrow_tools" />
							<img src="/assets/img/tools-arrow-down-icon.gif" alt="arrow" class="arrow_tools_down" />
							<table cellpadding="0" cellspacing="0">
								<tbody>
									<?php 
										foreach($this->form_tools as $the_tool){ 
											switch($the_tool){
												case('export'):
									?>
									<tr>
										<td>
											<a href="<?=addToGetString(array('export'), array('csv'))?>" title="Export Data" target="_blank" class="export_link">
												<img src="/assets/img/download-csv-icon.png" alt="Export Table Data" /> Export Table Data
											</a>
										</td>
										<td>
											(CSV format, export)
										</td>
									</tr>
									<tr>
										<td colspan="2" style="height: 7px;"><!-- spacer --></td>
									</tr>
									<tr>
										<td>
											<a href="<?=addToGetString(array('export'), array('mysql-dump'))?>" title="Export Data" target="_blank" class="export_link">
												<img src="/assets/img/mysql-dump.png" alt="Export Table Data" /> Do Mysql Dump
											</a>
										</td>
										<td>
											(Dump table data)
										</td>
									</tr>
									<?php			
												break;
											}
									 	} 
									?>
								</tbody>
							</table>
						</div>
						<?php } ?>
						<script type="text/javascript">
							<!-- 
								$().ready(function(){
									$('li a.form_link', '#bucket').click(function(){
										$('li.selected', '#bucket').removeClass('selected');
										$(this).parents('li').addClass('selected');
									});
									$('#looking_glass').click(function(){ $('#bucket_search_text').focus(); });
								});
							//-->
						</script>
						<div class="bucket_top_nav">
							<img src="<?=ASSETS_PATH?>/img/looking-glass.jpg" alt="glass" id="looking_glass" />
							<form method="get" action="<?=getGetString()?>" id="search_form_<?=md5($this->table)?>">
								<?=buildHiddenFieldFromGet($filter_out_fields)?>
								<input type="text" name="record_search" id="bucket_search_text" value="<?=$this->search_query?>" autocomplete="off" />
							</form>
							<a href="<?=addToGetString(null, null, array('record_search', 'viewall', 'action'))?>" title="Clear Results" id="clear_search"><!-- block --></a>
							<a href="<?=addToGetString('action', 'new', array('record_id', 'record_search'))?>" title="New <?=self::getTableName()?>" class="add_record">
								new
							</a>
							<?php if(have($this->form_tools)){ ?><div class="form_tools_icon"><!-- icon <?=md5($this->table)?> --></div><?php } ?>											
							<?php if(have($this->showTotalCountInHeader) and $this->showTotalCountInHeader === true){ ?>
							<div style="position: absolute; top: 13px; right: 59px; color: #333; font-size: 12px; padding: 0px 4px; -moz-border-radius: 7px; border-radius: 7px; -webkit-border-radius: 7px; behavior: url('/assets/css/border-radius.htc'); background: #eee;"><?=self::getTotalRecordCount()?></div>
							<?php } ?>
						</div><!-- end bucket_top_nav -->			
		<?php
	}

	/*************************************************************/
	/*********************** BUILD FORM LIST *********************/
	/*************************************************************/
	public function buildFromListing(){
		$show_records = self::buildFromListingArray(); if(have($show_records)){
				?>
				<?php
				if(isset($this->alt_orderfield) and !empty($this->alt_orderfield) and sizeof($show_records) == $this->table_query_total){
				?>
				<script type="text/javascript">
					<!-- 
						$().ready(function(){
							$('#form_listing_parent_<?=md5($this->table)?>').sortable({
								axis : 'y',
								handle : '.sort',
								containment : '#form_listing_parent_<?=md5($this->table)?>',
								placeholder : 'ui-state-highlight',
								update : function(){
									var orderArray_<?=md5($this->table)?> = [];
									var orderCounter_<?=md5($this->table)?> = 0;
									$('#form_listing_parent_<?=md5($this->table)?> .record_listing').each(function(){
										orderArray_<?=md5($this->table)?>[orderCounter_<?=md5($this->table)?>] = $(this).attr('id').substr(12);
										orderCounter_<?=md5($this->table)?> += 1;
									});
									var submitOrderString_<?=md5($this->table)?> = JSON.stringify(orderArray_<?=md5($this->table)?>);
									if(orderCounter_<?=md5($this->table)?> > 1){
										$.ajax({
											url : '<?=$_SERVER['PHP_SELF'] . addToGetStringAjax(array('ajax'), array('reorderSubmit'))?>',
											type : 'post',
											data : 'orderString_<?=md5($this->table)?>=' + submitOrderString_<?=md5($this->table)?>,
											success : function(mssg){
												showMessage('order updated');
											}
										})
									}
								}
							});
						});
					//-->
				</script>
				<?php
				}
				?>
				<?php 
				if(isset($this->active_field) and !empty($this->active_field)){ 
				?>
				<script type="text/javascript">
					<!--
						$().ready(function(){
							$('.active_indicator_off').live('click', function(){ setRecordActive<?=md5($this->table)?>($(this).attr('id').substr(26), 1); });
							$('.active_indicator_on').live('click', function(){ setRecordActive<?=md5($this->table)?>($(this).attr('id').substr(26), 0); });
						});
						function setRecordActive<?=md5($this->table)?>(record_id, active_value){
							$.ajax({
								url : '<?=$_SERVER['PHP_SELF'] . addToGetStringAjax(array('ajax'), array('changeActive'))?>',
								type : 'post',
								data : 'record_id=' + encodeURIComponent(record_id) + '&active_value=' + encodeURIComponent(active_value),
								dataType : 'json',
								success : function(mssg){
									if(mssg.active != undefined){
										if(mssg.active == 1){
											$('#record_activity_indicator_' + mssg.id).addClass('active_indicator_on').removeClass('active_indicator_off');
										}else{
											$('#record_activity_indicator_' + mssg.id).addClass('active_indicator_off').removeClass('active_indicator_on');
										}
									}
								}
							});
						}
					-->
				</script>
				<?php 
				} 
				?>
				<script type="text/javascript">
					$().ready(function(){
						$('li.record_listing a', '#form_listing_parent_<?=md5($this->table)?>').click(function(){
							$('li.selected', '#form_listing_parent_<?=md5($this->table)?>').removeClass('selected');
							$(this).parents('li').addClass('selected');
						});
					});
				</script>
				<div style="position: relative; overflow-y: auto; height: 476px;">
					<ul class="listing_parent" id="form_listing_parent_<?=md5($this->table)?>">				
			<?php
			if($this->table_start > 0 and !isset($_GET['viewall'])){
				$new_start = (($this->table_start - $this->results_limit) >= 0) ? ($this->table_start - $this->results_limit) : 0;
			?>
						<li class="prev">
							<a href="<?=addToGetString(array('start'), array($new_start))?>" class="prev-link" title="back"><!-- block --></a>
							<?php if($this->table_query_total > sizeof($show_records)){ ?> <a href="<?=addToGetString(array('viewall'), array('1'), array('start', 'action'))?>" class="view_all" title="view all <?=ucwords(self::getTableName())?>">view all <?=(int)$this->table_query_total?> <?=ucwords(self::getTableName())?></a> <?php } ?>
						</li>
			<?php
			}
			foreach($show_records as $row){
				$selected = ((int)$row[$this->primary_key] == $this->primary_value) ? 'selected' : null;
				?>
						<li id="list_record_<?=(int)$row[$this->primary_key]?>" class="record_listing <?=$selected?>">
				<?php
					if(isset($row[$this->active_field]) and strlen($row[$this->active_field]) > 0){
						if($row[$this->active_field] == 0){
				?>
							<div class="acitive_indicator active_indicator_off" id="record_activity_indicator_<?=(int)$row[$this->primary_key]?>"><!-- indicator --></div>
				<?php
						}else{
				?>
							<div class="acitive_indicator active_indicator_on" id="record_activity_indicator_<?=(int)$row[$this->primary_key]?>"><!-- indicator --></div>
				<?php	
						}		
					}
				?>		
							<?php if(!have($this->dontAllowDelete) or self::isSuperUser()){ ?>
								<a class="delete_icon" href="<?=addToGetString(array('action','record_id','record_search'), array('delete',(int)$row[$this->primary_key], $this->search_query))?>" title="Delete this record"><!-- block --></a>
							<?php } ?>
							<a href="<?=addToGetString(array('action','record_id','record_search'), array('edit',(int)$row[$this->primary_key],$this->search_query))?>" title="<?=htmlentities($row[$this->row_name])?>" class="form_link"><!-- block --></a>
							<span class="date"><strong><?=date('M j', strtotime($row[$this->date_added_field]))?></strong> <?=date('g:ia', strtotime($row[$this->date_added_field]))?></span>
							<strong class="title" title="<?=prepareTag(strip_tags($row[$this->row_name]))?>"><?=prepareStringHtml(limitString(strip_tags($row[$this->row_name]), LISTING_NAME_STR_LENGTH))?></strong>
							<strong class="sub" title="<?=prepareTag(strip_tags($row[$this->row_description]))?>"><?=prepareStringHtml(limitString(strip_tags($row[$this->row_description]), LISTING_DESCRIPTION_STR_LENGTH))?></strong>
							<p><?=prepareStringHtml(limitString(strip_tags($row[$this->row_name_alt]), LISTING_NAME_STR_LENGTH))?></p>
				<?php
					if(isset($this->alt_orderfield) and !empty($this->alt_orderfield) and sizeof($show_records) == $this->table_query_total){
				?>
							<div class="sort"><!-- block - sorting handle --></div>
				<?php
					}
				?>
						</li>					
				<?php
			}
			if($this->table_query_total - $this->results_limit > $this->table_start and $this->table_query_total > $this->results_limit and !isset($_GET['viewall'])){
				$new_start = (($this->table_start + $this->results_limit) < $this->table_query_total) ? ($this->table_start + $this->results_limit) : 0;
			?>
						<li class="next">
							<a href="<?=addToGetString(array('start'), array($new_start))?>" class="next-link" title="next"><!-- block --></a>
							<?php if($this->table_query_total > sizeof($show_records)){ ?> <a href="<?=addToGetString(array('viewall'), array('1'), array('start', 'action'))?>" class="view_all" title="view all <?=ucwords(self::getTableName())?>">view all <?=(int)$this->table_query_total?> <?=ucwords(self::getTableName())?></a> <?php } ?>
						</li>
			<?php
			}
			?>
					</ul>
				</div>
			<?php
		}
	}

	/*************************************************************/
	/*********************** BUILD FORM BLOCK ********************/
	/*************************************************************/
	public function buildFormBlock(){
		if(isset($this->alternate_fields[$this->row_name]['post_value']) and !empty($this->alternate_fields[$this->row_name]['post_value'])){
			$record_name = $this->alternate_fields[$this->row_name]['post_value'];
		}else{
			$record_name = 'New Record';
			self::presetFilterKeyValuePair();
		}
		$this_form_identity_string = (isset($_POST['form_time_' . $this->table])) ? $_POST['form_time_' . $this->table] : date('Y-m-d-H-i-s');
		$total_count_string = $this->showTotalCountInHeader ? ' (' . self::getTotalRecordCount() . ')' : null;
		?>
										<div class="form_header">
											<a href="<?=addToGetString(null, null, array('action','record_id'))?>" title="Go Back"><?=(isset($this->primary_value) and $this->primary_value > 0) ? 'back' : 'list'?></a>
											<?=ucwords(self::getTableName()) . $total_count_string?> &raquo; <?=$record_name?>
										</div><!-- end form_header -->
										<div style="position: relative; overflow-y: auto; height: 476px;">
											<form class="form_area" method="post" id="form_<?=md5($this->table)?>">
												<input type="hidden" name="form_time_<?=md5($this->table)?>" value="<?=$this_form_identity_string?>" />		
		<?php
		if(isset($this->primary_value) and $this->primary_value > 0){
		?>
												<input type="hidden" name="this_is_the_primary_value" value="<?=(int)$this->primary_value?>" />
		<?php
		}
		if(isset($_GET['action']) and !empty($_GET['action']) and $_GET['action'] == 'delete'){ //print the delete,cancel block
		?>
												<div class="button_row">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td style="text-align: right;">
																	<button class="cancel_button" onclick="javascript:setTimeout(function(){window.location='<?=addToGetString(null, null, array('action','record_id'))?>';},0);return false;">Cancel</button>
																</td>
																<td style="text-align: right; width: 158px;">
																	<button type="submit" class="delete_button" name="form_submit" value="Delete Record">Delete Record</button>
																</div><!-- end .delete_row -->
															</tr>
														</tbody>
													</table>
												</div>
		<?php
		}
		foreach($this->fields as $key => $value){
		
			//if sticky field .. then skip
			$sticky_keys = array_keys($this->sticky_fields); if(in_array($value['Field'], $sticky_keys)){ continue; }
			
			//should field be shown
			if(!self::shouldFieldBeShown($value)){ 
		?>
												<input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" />
		<?php
				continue;
			}
			
			/**********************************************************************************************/
			/******************************** HIDE OR SHOW CONDITIONAL FIELDS *****************************/ //handles positive .. and negative conditional fields
			/**********************************************************************************************/
			if(isset($value['conditional_fields']) and have($value['conditional_fields'])){
				foreach($value['conditional_fields'] as $key => $conditional_field){
					$pieces = explode('=', $conditional_field); if(have($pieces) and isset($pieces[1])){ $negative_condition = (substr($pieces[0], -1) == '!'); $field_name = str_replace('!', null, $pieces[0]); $field_values = explode('|', $pieces[1]); }
					if(have($field_name) and have($field_values)){ if(!isset($this->conditional_fields[$value['Field']][$field_name][$negative_condition])){ $this->conditional_fields[$value['Field']][$field_name][$negative_condition] = $field_values; }else{ $this->conditional_fields[$value['Field']][$field_name][$negative_condition] = array_merge($this->conditional_fields[$value['Field']][$field_name][$negative_condition], $field_values); } }
				}
			}			
		
			switch(true){
		
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** SELECT A STATE BOX ******************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (isset($value['field_type']) and $value['field_type'] == 'state'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
													<?=printSelectBox(getArray('states'), $value['Field'], $value['post_value'], (!self::canFieldBeEdited($value) ? array('disabled' => 'disabled') : null))?>
													<div class="field_status"><!-- status --></div>
												</div>			
		<?php
				break;
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** SELECT DATE FIELD *******************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case ($value['Type'] == 'date'):
		?>
												<script type="text/javascript">
													<!--
														$().ready(function(){
															$("#datepicker_<?=$value['Field']?>").datepicker();
														});
													//-->
												</script>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<input name="<?=$value['Field']?>" id="datepicker_<?=$value['Field']?>" title="<?=isset($value['error_mssg']) ? $value['error_mssg'] : null?>" type="text" value="<?=(isset($value['post_value']) and !empty($value['post_value'])) ? date('m/d/Y', strtotime($value['post_value'])) : ''?>" autocomplete="off" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
													<div class="field_status"><!-- status --></div>
												</div>			
		<?php		
				break;
			
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** SELECT BOX FROM ENUM ****************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 4) == 'enum'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
													<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
		<?php
					$enum_matches = array();
					@preg_match_all('/\'([^\']+)\'/i', $value['Type'], $enum_matches);
					if(isset($enum_matches[1]) and !empty($enum_matches[1])){
						foreach($enum_matches[1] as $enum_value){
							//select posted value
							$this_selected = (isset($value['post_value']) and !empty($value['post_value']) and $value['post_value'] == $enum_value) ? ' selected="selected" ' : null;
							//select default value
							$this_selected = ((!isset($value['post_value']) or empty($value['post_value'])) and $value['Default'] == $enum_value) ? ' selected="selected" ' : $this_selected;
		?>
														<option value="<?=$enum_value?>" <?=$this_selected?>><?=$enum_value?></option>																	
		<?php
						}
					}
		?>
													</select>
													<div class="field_status"><!-- status --></div>
												</div>				
		<?php
				break;
		
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** TINY INT SWITCH *********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case ($value['Type'] == 'tinyint(1)'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
													<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
														<option value="0" <?php if((isset($value['post_value']) and $value['post_value'] == 0) or (strlen($value['post_value']) == 0 and $value['Default'] == 0)){ echo 'selected="seletected"'; } ?>>off</option>
														<option value="1" <?php if((isset($value['post_value']) and $value['post_value'] == 1) or (strlen($value['post_value']) == 0 and $value['Default'] == 1)){ echo 'selected="seletected"'; } ?>>on</option>
													</select>
													<div class="field_status"><!-- status --></div>
												</div>				
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** PARENT ID TABLE**********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (isset($value['parent_options']) and false !== ($parent_options = self::getParentSelectArray())):
		?>									
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
													<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
														<option value="0"> -- top level -- </option>
		<?php
					foreach($parent_options as $option_key => $option_value){
						$this_selected = (isset($value['post_value']) and $value['post_value'] == $option_key) ? ' selected="selected" ' : '';
		?>
														<option value="<?=$option_key?>" <?=$this_selected?>><?=$option_value?></option>
		<?php
					}		
		?>
													</select>
													<div class="field_status"><!-- status --></div>
												</div>		
		<?php	
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** RELATED TABLE ***********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (isset($value['related_options']) and !empty($value['related_options'])):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
													<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
		<?php
			if($value['Null'] == 'YES'){
		?>
														<option value=""> ----- </option>
		<?php
			}
		?>
		<?php
					foreach($value['related_options'] as $option_key => $option_value){
						$this_selected = (isset($value['post_value']) and $value['post_value'] == $option_key) ? ' selected="selected" ' : '';
		?>
														<option value="<?=$option_key?>" <?=$this_selected?>><?=$option_value['option_name']?></option>
		<?php
					}		
		?>
													</select>
													<div class="field_status"><!-- status --></div>
												</div>		
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** MONTH *******************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Field'], -6) == '_month' and substr($value['Type'], 0, 3) == 'int'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
													<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
														<option value="">Not Applicable</option>
		<?php
						$months = array (1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
						foreach($months as $month_key => $month_string){
								//select posted value
								$this_selected = (isset($value['post_value']) and !empty($value['post_value']) and $value['post_value'] == $month_key) ? ' selected="selected" ' : null;
								//select default value
								$this_selected = ((!isset($value['post_value']) or empty($value['post_value'])) and $value['Default'] == $month_key) ? ' selected="selected" ' : $this_selected;
		?>
														<option value="<?=(int)$month_key?>" <?=$this_selected?>><?=htmlentities($month_string)?></option>		
		<?php				
						}
		?>
													</select>
													<div class="field_status"><!-- status --></div>
												</div>
		<?php
				break;
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** DAYS ********************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Field'], -4) == '_day' and substr($value['Type'], 0, 3) == 'int'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
													<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
														<option value="">Not Applicable</option>
		<?php
						for($the_day = 1;$the_day <= 31;$the_day++){
								//select posted value
								$this_selected = (isset($value['post_value']) and !empty($value['post_value']) and $value['post_value'] == $the_day) ? ' selected="selected" ' : null;
								//select default value
								$this_selected = ((!isset($value['post_value']) or empty($value['post_value'])) and $value['Default'] == $the_day) ? ' selected="selected" ' : $this_selected;
		?>
														<option value="<?=(int)$the_day?>" <?=$this_selected?>><?=htmlentities($the_day)?></option>		
		<?php				
						}
		?>
													</select>
													<div class="field_status"><!-- status --></div>
												</div>
		<?php
				break;
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** TIME ********************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Field'], -5) == '_time' and substr($value['Type'], 0, 4) == 'time'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
													<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
														<option value="">No Specific Time</option>
		<?php
						for($the_hour = 6; $the_hour <= 20; $the_hour++){ $the_hour = $the_hour < 10 ? '0' . $the_hour : $the_hour;
							for($the_minute = 0; $the_minute <= 60 - TIME_MINUTE_INTERVAL; $the_minute += TIME_MINUTE_INTERVAL){ $the_minute = $the_minute < 10 ? '0' . $the_minute : $the_minute;
								$this_hour_minute = $the_hour . ':' . $the_minute . ':00';
								$this_selected = (isset($value['post_value']) and !empty($value['post_value']) and $value['post_value'] == $this_hour_minute) ? ' selected="selected" ' : null;
								$this_selected = ((!isset($value['post_value']) or empty($value['post_value'])) and $value['Default'] == $this_hour_minute) ? ' selected="selected" ' : $this_selected;
								$am_vs_pm = ($the_hour < 12) ? 'AM' : 'PM';
								$time_string = ($the_hour <= 12) ? $the_hour . ':' . $the_minute . $am_vs_pm : $the_hour - 12 . ':' . $the_minute . $am_vs_pm;
		?>
														<option value="<?=$this_hour_minute?>" <?=$this_selected?>><?=$time_string?></option>		
		<?php				
							}
						}
		?>
													</select>
													<div class="field_status"><!-- status --></div>
												</div>
		<?php
				break;					

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** HOURS *******************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Field'], -5) == '_hour' and substr($value['Type'], 0, 3) == 'int'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
													<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
														<option value="">Choose Hour</option>
		<?php
						for($the_hour = 0;$the_hour < 24;$the_hour++){
								$this_selected = (isset($value['post_value']) and !empty($value['post_value']) and $value['post_value'] == $the_hour) ? ' selected="selected" ' : null;
								$this_selected = ((!isset($value['post_value']) or empty($value['post_value'])) and $value['Default'] == $the_hour) ? ' selected="selected" ' : $this_selected;
		?>
														<option value="<?=(int)$the_hour?>" <?=$this_selected?>><?=(int)($the_hour)?>:00</option>		
		<?php				
						}
		?>
													</select>
													<div class="field_status"><!-- status --></div>
												</div>
		<?php
				break;				

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** YEARS *******************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Field'], -5) == '_year' and substr($value['Type'], 0, 3) == 'int'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
													<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
														<option value="">Choose Year</option>
		<?php
						for($the_year = 1970; $the_year < date('Y') + 5;$the_year++){
								$this_selected = (isset($value['post_value']) and !empty($value['post_value']) and $value['post_value'] == $the_year) ? ' selected="selected" ' : null;
								$this_selected = ((!isset($value['post_value']) or empty($value['post_value'])) and $value['Default'] == $the_year) ? ' selected="selected" ' : $this_selected;
		?>
														<option value="<?=(int)$the_year?>" <?=$this_selected?>><?=(int)($the_year)?></option>		
		<?php				
						}
		?>
													</select>
													<div class="field_status"><!-- status --></div>
												</div>
		<?php
				break;	

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** PERCENTAGE **************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Field'], -11) == '_percentage' and substr($value['Type'], 0, 3) == 'int'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<script type="text/javascript">
														$().ready(function(){
															$('input[name=<?=$value['Field']?>]', '#input_row_<?=$value['Field']?>').keyup(function(){
																var this_slider_value_<?=$value['Field']?> = $(this).val().replace(/[^0-9]/, '');
																$('#slider_for_<?=$value['Field']?>', '#input_row_<?=$value['Field']?>').slider({ value: this_slider_value_<?=$value['Field']?> });
															});
															$('#slider_for_<?=$value['Field']?>', '#input_row_<?=$value['Field']?>').slider({ value: <?=(int)$value['post_value']?>, animate: true <?php if(self::canFieldBeEdited($value)){ ?>, change : function(event, ui){ $('input[name=<?=$value['Field']?>]', '#input_row_<?=$value['Field']?>').val(ui.value); } <?php }else{ ?>, disabled: true <?php } ?> });
														});
													</script>
													<input type="text" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" style="position: absolute; top: -4px; left: 0px; font-size: 18px; color: #222222; width: 38px; text-align: center;" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
													<div id="slider_for_<?=$value['Field']?>" style="margin-left: 50px;"><!-- slider --></div>
													<div class="field_status"><!-- status --></div>
												</div>
		<?php
				break;	
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** PRICE FIELD *************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 5) == 'float' and substr($value['Field'], -5) == 'price'):
					$float_info_matches = array();
					if(false !== preg_match('/^float\(([0-9]+)\,([0-9]+)\)$/i', $value['Type'], $float_info_matches)){
						$value['post_value'] = number_format($value['post_value'], $float_info_matches[2]);
						$max_length = (isset($float_info_matches[1]) and !empty($float_info_matches[1])) ? ' maxlength="' . ((int)$float_info_matches[1] + 1) . '" ' : '';	
					}
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<input name="<?=$value['Field']?>" title="<?=isset($value['error_mssg']) ? $value['error_mssg'] : null?>" type="text" value="<?=$value['post_value']?>" autocomplete="off" <?=$max_length?> <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
													<div class="field_status"><!-- status --></div>
												</div>			
		<?php
				break;
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** FLOAT FIELD *************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 5) == 'float'):
					$float_info_matches = array();
					if(false !== preg_match('/^float\(([0-9]+)\,([0-9]+)\)$/i', $value['Type'], $float_info_matches)){
						$value['post_value'] = number_format($value['post_value'], $float_info_matches[2]);
						$max_length = (isset($float_info_matches[1]) and !empty($float_info_matches[1])) ? ' maxlength="' . ((int)$float_info_matches[1] + 1) . '" ' : '';	
					}		
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<input name="<?=$value['Field']?>" title="<?=isset($value['error_mssg']) ? $value['error_mssg'] : null?>" type="text" value="<?=$value['post_value']?>" autocomplete="off" <?=$max_length?> <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
													<div class="field_status"><!-- status --></div>
												</div>			
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** PASSWORD FIELD **********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 7) == 'varchar' and (substr($value['Field'], -9) == '_password' or substr($value['Field'], -3) == '_pw')):
					$matches = array();
					preg_match('/[0-9]+/', $value['Type'], $matches);
					$max_length = (isset($matches[0]) and !empty($matches[0])) ? ' maxlength="' . (int)$matches[0] . '" ' : '';			
			?>									
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<input name="<?=$value['Field']?>" title="<?=isset($value['error_mssg']) ? $value['error_mssg'] : null?>" type="password" value="<?=$value['post_value']?>" <?=$max_length?>  autocomplete="off" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
													<div class="field_status"><!-- status --></div>
												</div>
			<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** COLOR FIELD *************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 7) == 'varchar' and substr($value['Field'], -6) == '_color'):		
			?>									
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<script type="text/javascript" src="<?=ASSETS_PATH?>/farbtastic/farbtastic.js"></script>
													<link rel="stylesheet" href="<?=ASSETS_PATH?>/farbtastic/farbtastic.css" type="text/css" />
													<script type="text/javascript">
														$().ready(function(){
															$('#color_picker_for_<?=$value['Field']?>').farbtastic('input[name=<?=$value['Field']?>]', '#input_row_<?=$value['Field']?>');
															$('input[name=<?=$value['Field']?>]', '#input_row_<?=$value['Field']?>').focus(function(){
																$('#color_picker_for_<?=$value['Field']?>').slideDown();
															});
															$('input[name=<?=$value['Field']?>]', '#input_row_<?=$value['Field']?>').blur(function(){
																$('#color_picker_for_<?=$value['Field']?>').slideUp();
															});
														});
													</script>
													<div id="color_picker_for_<?=$value['Field']?>" style="display: none; margin-bottom: 5px;"><!-- picker --></div>
													<input name="<?=$value['Field']?>" title="<?=isset($value['error_mssg']) ? $value['error_mssg'] : null?>" type="text" value="<?=have($value['post_value']) ? $value['post_value'] : '#ffffff'?>" autocomplete="off" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> style="border-top-left-radius: 5px;border-top-right-radius: 5px;border-bottom-right-radius: 5px;border-bottom-left-radius: 5px;" />
													<div class="field_status"><!-- status --></div>
												</div>
			<?php
				break;
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** VARCHAR FIELD ***********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 7) == 'varchar'):
					$matches = array();
					preg_match('/[0-9]+/', $value['Type'], $matches);
					$max_length = (isset($matches[0]) and !empty($matches[0])) ? ' maxlength="' . (int)$matches[0] . '" ' : '';
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<input name="<?=$value['Field']?>" title="<?=isset($value['error_mssg']) ? $value['error_mssg'] : null?>" type="text" value="<?=$value['post_value']?>" <?=$max_length?>  autocomplete="off" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
													<div class="field_status"><!-- status --></div>
												</div>		
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** INT FIELD ***************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 3) == 'int' and $value['Key'] != 'PRI' and $value['field_type'] != 'orderfield'):
					$matches = array();
					preg_match('/[0-9]+/', $value['Type'], $matches);
					$max_length = (isset($matches[0]) and !empty($matches[0])) ? ' maxlength="' . (int)$matches[0] . '" ' : '';
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<input name="<?=$value['Field']?>" title="<?=isset($value['error_mssg']) ? $value['error_mssg'] : null?>" type="text" value="<?=$value['post_value']?>" <?=$max_length?>  autocomplete="off" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
													<div class="field_status"><!-- status --></div>
												</div>		
		<?php
				break;
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** TEXT AREA FIELD *********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 4) == 'text'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<textarea title="<?=isset($value['error_mssg']) ? $value['error_mssg'] : null?>" name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?>><?=$value['post_value']?></textarea>
													<div class="field_status"><!-- status --></div>
												</div>		
		<?php		
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** TEXT AREA FIELD - MEDIUM TEXT *******************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 10) == 'mediumtext'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<label><?=self::handleFieldLabel($value)?></label>
													<textarea title="<?=isset($value['error_mssg']) ? $value['error_mssg'] : null?>" name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?>><?=$value['post_value']?></textarea>
													<div class="field_status"><!-- status --></div>
												</div>		
		<?php		
				break;
				
			}
		}

		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** CONDITIONAL FIELD CONDITIONS ********************************/
		/**********************************************************************************************/
		/**********************************************************************************************/		
		if(have($this->conditional_fields)){
			?>
												<script type="text/javascript">
													<!--
			<?php foreach($this->conditional_fields as $target_field_name => $conditional_fields){ ?>function handleConditionalFields<?=md5($this->table . $target_field_name)?>(){ if(<?php foreach($conditional_fields as $field_name => $field_condition_array){ if(have($field_condition_array)){ foreach($field_condition_array as $condition_status => $conditions_array){ foreach($conditions_array as $condition_value){ ?><?=($condition_status) ? '!' : null?>( $('input[name=<?=$field_name?>]', '#form_<?=md5($this->table)?>').val() == '<?=$condition_value?>' || $('select[name=<?=$field_name?>] option:selected', '#form_<?=md5($this->table)?>').val() == '<?=$condition_value?>') && <?php } } } } ?> 1 == 1 ){ $('#input_row_<?=$target_field_name?>', '#form_<?=md5($this->table)?>').slideDown(); }else{ $('#input_row_<?=$target_field_name?>', '#form_<?=md5($this->table)?>').hide(); } }<?php } ?>$().ready(function(){ <?php foreach($this->conditional_fields as $target_field_name => $conditional_fields){ ?>	$('select', '#form_<?=md5($this->table)?>').change(function(){ handleConditionalFields<?=md5($this->table . $target_field_name)?>(); }); $('input', '#form_<?=md5($this->table)?>').keyup(function(){ handleConditionalFields<?=md5($this->table . $target_field_name)?>(); }); handleConditionalFields<?=md5($this->table . $target_field_name)?>(); <?php } ?> });
													//-->
												</script>
			<?php
		}		
			
		/**********************************************************************************************/
		/**********************************************************************************************/
		/******************************** ASSOCIATION BLOCKS ******************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/		
		if(isset($this->associative_map_tables) and !empty($this->associative_map_tables)){
		?>
												<script type="text/javascript">
													<!-- 
														$().ready(function(){
															$('td.associations ul li').click(function(event){
																if(event.target.nodeName != 'INPUT'){
																	if(false === $(this).children('input[type=checkbox]').attr('checked')){
																		$(this).children('input[type=checkbox]').attr('checked', true);
																		$(this).addClass('selected');
																	}else{
																		$(this).children('input[type=checkbox]').attr('checked', false);
																		$(this).removeClass('selected');
																	}
																}else{
																	if(false === $(this).children('input[type=checkbox]').attr('checked')){
																		$(this).removeClass('selected');
																	}else{
																		$(this).addClass('selected');
																	}
																}
															});
														});
													//-->
												</script>		
		<?php
			foreach($this->associative_map_tables as $assoc_table_name => $assoc_data_array){
				if(
					isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']) and
					isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field']) and
					isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']) and
					isset($this->associative_map_tables[$assoc_table_name]['assoc_table']['table']) and !empty($this->associative_map_tables[$assoc_table_name]['assoc_table']['table']) and
					isset($this->associative_map_tables[$assoc_table_name]['assoc_table']['native_key']) and !empty($this->associative_map_tables[$assoc_table_name]['assoc_table']['native_key']) and
					isset($this->associative_map_tables[$assoc_table_name]['assoc_table']['foreign_key']) and !empty($this->associative_map_tables[$assoc_table_name]['assoc_table']['foreign_key']) and
					!in_array($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name'], $this->blackListAssociativeTables)
				){
					$sticky_fields_for_assoc = self::filterStickyFieldsForTable($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']);
					if(isset($sticky_fields_for_assoc) and !empty($sticky_fields_for_assoc)){
						$sticky_sql = "";
						foreach($sticky_fields_for_assoc as $sticky_field_name => $sticky_field_value){
							$sticky_sql .= "
									" . mysql_real_escape_string($sticky_field_name) . " = '" . mysql_real_escape_string($sticky_field_value) . "'		
								AND
									";
						}
						$sticky_sql .= "
									1 = 1
									";
					}else{
						$sticky_sql = "
									1 = 1				
									";
					}
					$sql = "
								SELECT 
									" . mysql_real_escape_string($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']) . ",
									" . mysql_real_escape_string($this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field']) . "
								FROM 
									" . mysql_real_escape_string($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']) . "
								WHERE 
									" . $sticky_sql . "				
							";
					if(isset($this->associative_map_tables[$assoc_table_name]['foreign_table']['orderfield']) and !empty($this->associative_map_tables[$assoc_table_name]['foreign_table']['orderfield'])){
						$sql .= "
								ORDER BY 
									" . mysql_real_escape_string($this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field']) . " ASC
							";
					}
					$result = mysql_query($sql, $this->db) or handleError(1, 'sql:' . $sql . ' error:' . mysql_error());
					if(mysql_num_rows($result) > 0){
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$assoc_table_name?>">

																<label>
																	<?=preg_replace('/[^a-z]+/i', ' ', str_ireplace(TABLES_PREPEND ,'', $this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']))?>
																</td>
																<td class="associations">
																	 
																		<ul style="max-height: 240px; overflow-y: auto; margin-right: 40px;">		
		<?php
						while($row = mysql_fetch_assoc($result)){
							if(!isset($_POST) or empty($_POST)){
								$checked_attr = (isset($this->associative_map_tables[$assoc_table_name]['existing_keys']) and in_array($row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']], $this->associative_map_tables[$assoc_table_name]['existing_keys'])) ? ' checked="checked" ' : '';
								$selected_attr = (isset($this->associative_map_tables[$assoc_table_name]['existing_keys']) and in_array($row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']], $this->associative_map_tables[$assoc_table_name]['existing_keys'])) ? ' class="selected" ' : '';
							}else{
								$post_keys = array_keys($_POST);
								$checked_attr = (in_array($assoc_table_name . '_' . (int)$row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']], $post_keys)) ? ' checked="checked" ' : '';
								$selected_attr = (in_array($assoc_table_name . '_' . (int)$row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']], $post_keys)) ? ' class="selected" ' : '';
							}
		?>
																			<li <?=$selected_attr?>>
																				<input type="checkbox" name="<?=$assoc_table_name?>_<?=(int)$row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']]?>" value="true" <?=$checked_attr?> /> <?=htmlentities($row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['show_field']])?>
																			</li>
		<?php
						}
						mysql_free_result($result);
		?>
																		</ul>
																	</div><!-- end .inner_holder -->
																</td><!-- end .associations -->
															</tr>
														</tbody>
													</table>	
												</div><!-- end .input_row -->
		
		<?php
					} //if found rows
				} //if valid associative array
			} //foreach associative candidate
		} //if we have the associative array

		?>
												<div class="button_row">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td style="text-align: left;">
																	<div><input type="reset" value="Reset Form" onclick="javascript:return false;" style="width: auto; overflow: visible;" /></div>
																</td>
																<td style="text-align: right;">
																	<button class="cancel_button" onclick="javascript:window.location.href='<?=addToGetString(null, null, array('action','record_id'))?>';return false;">Cancel</button>
																</td>
																<td style="text-align: right; width: 84px;">
																	<input type="submit" name="form_submit" value="Save" class="save_button" onclick="javascript:return false;" />
																</td>
																<?php
																if(!have($this->primary_value)){
																?>
																<td style="text-align: right; width: 138px;">
																	<input type="submit" name="form_submit_and_new" value="Save &amp; New" class="save_and_new_button" onclick="javascript: addNewRecordRequest($(this)); return false;" />
																</td>
																<?php
																}
																?>
															</tr>
														</tbody>
													</table>
												</div>
											</form>
										</div>
		<?php
	}
	
}
?>