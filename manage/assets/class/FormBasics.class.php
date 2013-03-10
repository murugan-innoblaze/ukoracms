<?php

class FormBasics extends MapTable {

	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
	
		//extend from parent
		parent::__construct($db, $table_name, $parameters, $sticky_fields);

		//get form stats
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'get_form_stats' and isset($_GET['key']) and $_GET['key'] == md5($this->table) and have($_POST['interval']) and have($_POST['limit'])){ echo self::buildFormStatsContent($_POST['interval'], $_POST['limit'], have($_POST['query']) ? $_POST['query'] : null, isset($_POST['altdatefield']) ? $_POST['altdatefield'] : null); exit(0); }
	
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
		<link type="text/css" href="<?=ASSETS_PATH?>/css/form.css" rel="stylesheet" media="all" />
		<link type="text/css" href="<?=ASSETS_PATH?>/css/listing.css" rel="stylesheet" media="all" />
		<?php
		if(isset($this->prepare_for_iframe) and $this->prepare_for_iframe === true){
		?>
		<link type="text/css" href="<?=ASSETS_PATH?>/css/styles-ifr.css" rel="stylesheet" media="all" />
		<?php
		} //end if ..in iframe
		?>
		<?php
		if(isset($this->prepareForHtmlEditor) and $this->prepareForHtmlEditor === true){
		?>
		<script type="text/javascript" src="<?=ASSETS_PATH?>/mce/tiny_mce.js"></script>
		<?php
		} //end if ..in iframe
		?>
		<?php
		if(!empty($this->table)){
		?>
		<script type="text/javascript">
			<!--
				
				//lets see if we make edits
				var we_edited_the_form = false;
				var legit_form_submit = false;
				
				$().ready(function(){
					
					/**********************************/
					/****** when leaving the page *****/
					/**********************************/
					$(window).unload(function(){
						if(legit_form_submit) return true;
						if(we_edited_the_form){
							if(confirm('Would you like to save your changes?')){
								<?=$this->table?>_prepareSubmit();
							}else{
								return true;
							}
						}
						return true;
					});
				
					/**********************************/
					/****** remove default values *****/
					/**********************************/
					$('input.save_button, input.save_and_new_button', '#form_<?=$this->table?>').live('click', function(){ legit_form_submit = true; <?=$this->table?>_prepareSubmit(); });

					/**********************************/
					/****** select input on click *****/
					/**********************************/
					$('.input_row', '#form_<?=$this->table?>').click(function(){
						$(this).find('input[type=text]:first, select:first, textarea:first').focus();
					});
					
					/**********************************/
					/****** select box disabled *******/
					/**********************************/					
					$('select', '#form_<?=$this->table?>').each(function(){
						if($(this).attr('disabled')){ $(this).addClass('disabled'); }
					});
					
					/**********************************/
					/****** title val swaps ***********/
					/**********************************/
					$('input[type=text], input[type=password], textarea', '#form_<?=$this->table?>').each(function(){
						if($(this).attr('readonly') == true){ $(this).addClass('disabled'); }
						if($(this).val() == ''){
							$(this).val($(this).attr('title'));
						}else{
							if($(this).val() == $(this).attr('title')){
								$(this).removeClass('touched');
							}else{
								$(this).addClass('touched');
							}
						}
					});
					$('input[type=text], input[type=password], textarea', '#form_<?=$this->table?>').focus(function(){
						if($(this).val() == $(this).attr('title')){
							$(this).val('').removeClass('touched');
						}else{
							if($(this).val() == ''){
								$(this).removeClass('touched');
							}else{
								$(this).addClass('touched');
							}
						}
					});
					$('input[type=text], input[type=password], textarea', '#form_<?=$this->table?>').blur(function(){
						if($(this).val() == ''){
							$(this).val($(this).attr('title')).removeClass('touched');
						}else{
							if($(this).val() == $(this).attr('title')){
								$(this).removeClass('touched');
							}else{
								$(this).addClass('touched');
							}
						}
					});

					/**********************************/
					/****** remove value button *******/
					/**********************************/
					$('.close_icon', '#form_<?=$this->table?>').click(function(){
						$(this).parent().children('input, textarea').val($(this).parent().children('input, textarea').attr('title'));
						$('#input_row_' + $(this).parent().children('input, textarea').removeClass('touched').attr('name')).removeClass('problem').removeClass('checked');
					});
					<?php
						if($this->need_upload === true){
					?>
					$('.close_icon_uploader', '#form_<?=$this->table?>').live('click', function(){
						$(this).parent().children('.file_dialogue_target').html('').hide();
						$(this).parent().children('input[type=hidden]').attr('value', '');
						$('#input_row_' + $(this).parent().children('input, textarea').attr('name')).removeClass('problem').removeClass('checked');
					});
					<?php
						} //need this to handle uploaded close button click
					?>
					
					/**********************************/
					/****** check input trigger *******/
					/**********************************/
					$('input[type=text], input[type=password], textarea', '#form_<?=$this->table?>').keyup(function(){
						we_edited_the_form = true;
						var input_name = $(this).attr('name');
						var input_val = $(this).val();
						var input_title = $(this).attr('title');
						var input_status = <?=$this->table?>_checkInput(input_name, input_val, input_title);
						if(input_status == 'false'){
							$('#input_row_' + input_name).addClass('problem');
						}
						if(input_status == 'true'){
							$('#input_row_' + input_name).removeClass('problem').addClass('checked');
						}
						if(input_status == 'empty'){
							$('#input_row_' + input_name).removeClass('problem').removeClass('checked');
						}
					});
					$('input[type=text], input[type=password], textarea', '#form_<?=$this->table?>').blur(function(){
						var input_name = $(this).attr('name');
						var input_val = $(this).val();
						var input_title = $(this).attr('title');
						var input_status = <?=$this->table?>_checkInput(input_name, input_val, input_title);
						if(input_status == 'false'){
							$('#input_row_' + input_name).addClass('problem');
						}
						if(input_status == 'true'){
							$('#input_row_' + input_name).removeClass('problem').addClass('checked');
						}
						if(input_status == 'empty'){
							$('#input_row_' + input_name).removeClass('problem').removeClass('checked');
						}
					});

					/*************************************************/
					/****** submit form when 'enter' is pressed ******/
					/*************************************************/
					$('input', '#form_<?=$this->table?>').keyup(function(event){ if(event.keyCode == 13){ legit_form_submit = true; <?=$this->table?>_prepareSubmit(); } });
					
					/*************************************************/
					/****** reset form when reset button is hit ******/
					/*************************************************/				
					$('input[type=reset]', '#form_<?=$this->table?>').click(function(){ $('#form_<?=$this->table?>').get(0).reset(); we_edited_the_form = false; });
				
				});

				/**********************************/
				/****** check before submission ***/
				/**********************************/
				function <?=$this->table?>_prepareSubmit(){
					$('input, textarea', '#form_<?=$this->table?>').each(function(){
						if($(this).val() == $(this).attr('title')){
							$(this).val('');
						}
					});
					$('#form_<?=$this->table?>').submit();				
				}

				/**********************************/
				/****** check input values ********/
				/**********************************/
				function addNewRecordRequest(oject){
					$('#form_<?=$this->table?>').prepend('<input type="hidden" name="' + oject.attr('name') + '" value="' + oject.val() + '"" />');
				}
				
				/**********************************/
				/****** check input values ********/
				/**********************************/
				function <?=$this->table?>_checkInput(name, value, title){
					switch(name){
						<?php
						foreach($this->fields as $key => $value){
							switch(true){
								
								//star means field can't be empty
								case(isset($value['field_regex']) and $value['field_regex'] == '*'):
						?>
						case '<?=$value['Field']?>':
							if(value == '' || value == title){
								return 'empty';		
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
								case(isset($value['field_regex']) and strlen($value['field_regex']) > 3):
						?>
						case '<?=$value['Field']?>':
							if(value == '' || value == title){
								return 'empty';		
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
		?>
			<div class="form_message <?=$frame_class?>">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/manager/notification-icon.gif" alt="Notification Icon" /> Record has been updated</td></tr></tbody></table>
				<button>Close Message <span class="countdown">[8]</span></button>
			</div><!-- .form_message -->
		<?php
					if($this->prepare_for_iframe === true){
						$return = false;
					}else{ //not an iframe
						$return = true;		
					}	
				break;
				case 'not updated':
		?>
			<div class="form_message <?=$frame_class?>">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/manager/notification-icon.gif" alt="Notification Icon" /> No changes made</td></tr></tbody></table>
				<button>Close Message <span class="countdown">[8]</span></button>
			</div><!-- .form_message -->
		<?php		
					if($this->prepare_for_iframe === true){
						$return = false;
					}else{ //not an iframe
						$return = true;
					}
				break;
				case 'inserted':
		?>
			<div class="form_message <?=$frame_class?>">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/manager/notification-icon.gif" alt="Notification Icon" /> Record has been added</td></tr></tbody></table>
				<button>Close Message <span class="countdown">[8]</span></button>
			</div><!-- .form_message -->
		<?php		
					$return = false;
				break;
				case 'not inserted':
		?>
			<div class="form_message <?=$frame_class?>">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/manager/notification-icon.gif" alt="Notification Icon" /> Record could not be added</td></tr></tbody></table>
				<button>Close Message <span class="countdown">[8]</span></button>
			</div><!-- .form_message -->
		<?php		
					$return = true;
				break;
				case 'deleted':
		?>
			<div class="form_message <?=$frame_class?>">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/manager/notification-icon.gif" alt="Notification Icon" /> Record deleted</td></tr></tbody></table>
				<button>Close Message <span class="countdown">[8]</span></button>
			</div><!-- .form_message -->
		<?php		
					$return = false;
				break;
				case 'not deleted':
		?>
			<div class="form_message <?=$frame_class?>">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/manager/notification-icon.gif" alt="Notification Icon" /> Record not deleted</td></tr></tbody></table>
				<button>Close Message <span class="countdown">[8]</span></button>
			</div><!-- .form_message -->
		<?php	
					$return = true;	
				break;
				default:
		?>
			<div class="form_message <?=$frame_class?>">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/manager/notification-icon.gif" alt="Notification Icon" /> <?=htmlentities($this->event)?></td></tr></tbody></table>
				<button>Close Message <span class="countdown">[8]</span></button>
			</div><!-- .form_message -->
		<?php		
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
			<div class="form_message <?=$frame_class?>" style="display: none;">
				<table cellpadding="0" cellspacing="0"><tbody><tr><td><img src="<?=ASSETS_PATH?>/img/manager/notification-icon.gif" alt="Notification Icon" /><span id="message_load_target"><!-- message loads here --></span></td></tr></tbody></table>
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
		$filter_out_fields = array('record_search', 'filter_key', 'filter_value', 'start');
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
										$('#form_tools_load_area_<?=$this->table?>').toggle();
									});
									$('.form_tools_icon').hover(function(){
										$('.arrow_tools', '#form_tools_load_area_<?=$this->table?>').show();
										$('.arrow_tools_down', '#form_tools_load_area_<?=$this->table?>').hide();
									}, function(){
										$('.arrow_tools', '#form_tools_load_area_<?=$this->table?>').hide();
										$('.arrow_tools_down', '#form_tools_load_area_<?=$this->table?>').show();		
									});
								});
							//-->
						</script>
						<div id="form_tools_load_area_<?=$this->table?>" class="form_tools_window">
							<img src="/assets/img/manager/tools-arrow-icon.gif" alt="arrow" class="arrow_tools" />
							<img src="/assets/img/manager/tools-arrow-down-icon.gif" alt="arrow" class="arrow_tools_down" />
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
												<img src="/assets/img/manager/download-csv-icon.png" alt="Export Table Data" /> Export Table Data
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
												<img src="/assets/img/manager/mysql-dump.png" alt="Export Table Data" /> Do Mysql Dump
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
							<img src="<?=ASSETS_PATH?>/img/manager/looking-glass.jpg" alt="glass" id="looking_glass" />
							<form method="get" action="<?=getGetString()?>" id="search_form_<?=$this->table?>">
								<?=buildHiddenFieldFromGet($filter_out_fields)?>
								<input type="text" name="record_search" id="bucket_search_text" value="<?=$this->search_query?>" autocomplete="off" />
							</form>
							<a href="<?=addToGetString(null, null, array('record_search', 'viewall'))?>" title="Clear Results" id="clear_search"><!-- block --></a>
							<?php if(!defined('DO_NOT_ALLOW_ADDING_RECORDS')){ ?>
							<a href="<?=addToGetString('action', 'new', array('record_id', 'record_search'))?>" title="New Record">
								+
							</a>
							<?php } ?>
							<?php if(have($this->form_tools)){ ?><div class="form_tools_icon"><!-- icon <?=$this->table?> --></div><?php } ?>											
							<?php if(have($this->showTotalCountInHeader) and $this->showTotalCountInHeader === true){ ?>
							<div style="position: absolute; top: 13px; right: 44px; color: #677bbc; font-size: 12px; padding: 0px 4px; -moz-border-radius: 7px; border-radius: 7px; background: white;"><?=self::getTotalRecordCount()?></div>
							<?php } ?>
						</div><!-- end bucket_top_nav -->			
		<?php
	}

	/*************************************************************/
	/*********************** BUILD FORM LIST *********************/
	/*************************************************************/
	public function buildFromListing(){
		if(have($this->show_records)){
			$frame_class = ($this->prepare_for_iframe === true) ? 'iframe' : null;
				?>
					<?php
					if(isset($this->alt_orderfield) and !empty($this->alt_orderfield) and sizeof($this->show_records) == $this->table_query_total){
					?>
					<script type="text/javascript">
						<!-- 
							$().ready(function(){
								$('#form_listing_parent_<?=$this->table?>').sortable({
									axis : 'y',
									handle : '.sort',
									containment : '#form_listing_parent_<?=$this->table?>',
									placeholder : 'ui-state-highlight',
									update : function(){
										var orderArray_<?=$this->table?> = [];
										var orderCounter_<?=$this->table?> = 0;
										$('#form_listing_parent_<?=$this->table?> .record_listing').each(function(){
											orderArray_<?=$this->table?>[orderCounter_<?=$this->table?>] = $(this).attr('id').substr(12);
											orderCounter_<?=$this->table?> += 1;
										});
										var submitOrderString_<?=$this->table?> = JSON.stringify(orderArray_<?=$this->table?>);
										if(orderCounter_<?=$this->table?> > 1){
											$.ajax({
												url : '<?=$_SERVER['PHP_SELF'] . addToGetStringAjax(array('ajax'), array('reorderSubmit'))?>',
												type : 'post',
												data : 'orderString_<?=$this->table?>=' + submitOrderString_<?=$this->table?>,
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
								$('.active_indicator_off').live('click', function(){ setRecordActive<?=$this->table?>($(this).attr('id').substr(26), 1); });
								$('.active_indicator_on').live('click', function(){ setRecordActive<?=$this->table?>($(this).attr('id').substr(26), 0); });
							});
							function setRecordActive<?=$this->table?>(record_id, active_value){
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
							$('li.record_listing a', '#form_listing_parent_<?=$this->table?>').click(function(){
								$('li.selected', '#form_listing_parent_<?=$this->table?>').removeClass('selected');
								$(this).parents('li').addClass('selected');
							});
						});
					</script>
					<ul class="listing_parent <?=$frame_class?>" id="form_listing_parent_<?=$this->table?>">				
			<?php
			if($this->table_start > 0 and !isset($_GET['viewall'])){
				$new_start = (($this->table_start - $this->results_limit) >= 0) ? ($this->table_start - $this->results_limit) : 0;
			?>
						<li class="prev">
							<a href="<?=addToGetString(array('start'), array($new_start))?>" class="prev-link" title="back"><!-- block --></a>
							<?php if($this->table_query_total > sizeof($this->show_records)){ ?> <a href="<?=addToGetString(array('viewall'), array('1'), array('start'))?>" class="view_all" title="view all records">view all <?=(int)$this->table_query_total?> records</a> <?php } ?>
						</li>
			<?php
			}
			foreach($this->show_records as $row){
				$selected = ((int)$row[$this->primary_key] == $this->primary_value) ? 'selected' : null;
				?>
						<!--<li style="height: auto; padding: 0px; background: none; padding-left: 25px;"><strong>7:00am 8:00am</strong></li>-->
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
					if(isset($this->alt_orderfield) and !empty($this->alt_orderfield) and sizeof($this->show_records) == $this->table_query_total){
				?>
							<div class="sort"><!-- block - sorting handle --></div>
				<?php
					}
				?>
				<?php
					if(!isset($this->prepare_for_iframe) or $this->prepare_for_iframe === false){
				?>	
							<img src="<?=ASSETS_PATH?>/img/manager/bucket_right_arrow.png" alt="arrow" class="arrow_img" />
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
							<?php if($this->table_query_total > sizeof($this->show_records)){ ?> <a href="<?=addToGetString(array('viewall'), array('1'), array('start'))?>" class="view_all" title="view all records">view all <?=(int)$this->table_query_total?> records</a> <?php } ?>
						</li>
			<?php
			}
			?>
					</ul>
			<?php
		}
	}
	
	/*************************************************************/
	/*********************** BUILD FORM STATS ********************/
	/*************************************************************/
	public function buildFormStats($interval = 'days', $limit = 30, $area_styles = null, $query = null, $alternate_timestamp = null){
		if(!have($area_styles)){ $area_styles = 'margin-top: -54px;'; }
		?>
			<div class="form_area" style="<?=$area_styles?>">
				<div class="form_content_block">
					<div class="content_block_header" style="cursor: default;">
						<table cellpadding="0" cellspacing="0">
							<tbody>
								<tr>
									<td style="width: 170px;">
										<?=have($this->table_comments) ? ucwords($this->table_comments) : ucwords(str_replace(array('-', '_'), array(' ', ' '), $this->table))?> Added In The Last <?=(int)$limit?> <?=prepareStringHtml($interval)?>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<?=self::printStatsHeadBlock()?>
					<script type="text/javascript">
						<!--					
							$().ready(function(){
								$.ajax({
									url : '<?=$_SERVER['PHP_SELF'] . addToGetStringAjax(array('ajax', 'key'), array('get_form_stats', md5($this->table)))?>',
									type : 'post',
									data : 'interval=' + encodeURIComponent('<?=strtolower(prepareTag($interval))?>') + '&limit=<?=(int)$limit?>&query=' + encodeURIComponent('<?=encryptString($query, HOST_NAME)?>') + '&altdatefield=' + encodeURIComponent('<?=prepareTag($alternate_timestamp)?>'),
									success : function(mssg){
										$('#<?=$this->table?>_stats_load_target_<?=md5($this->table . $interval . $limit . $query)?>').html(mssg);
									}, 
									error: function(error){ alert('error:'+ error); }
								})
							});
						//-->					
					</script>
					<div id="<?=$this->table?>_stats_load_target_<?=md5($this->table . $interval . $limit . $query)?>" class="widget_target">
						<div class="background-loader-for-widget"><!-- background --></div>
						<!-- stats load here -->
					</div><!-- end <?=$this->table?>_stats_load_target -->
				</div><!-- end .content_block_header -->
			</div><!-- .form_area -->		
		<?php	
	}

	/*****************************************************/
	/***************** WIDGET HEAD BLOCK *****************/
	/*****************************************************/	
	public function printStatsHeadBlock(){
		if(isset($this->printStatsHeadBlockLoaded)){ return null; }
		?>
			<script type="text/javascript" src="http://www.google.com/jsapi"></script>
			<script type="text/javascript">
				<!-- 
					google.load('visualization', '1', { packages:['corechart'] });
				//-->
			</script>
		<?php
		$this->printStatsHeadBlockLoaded = true;
	}
	
	/*************************************************************/
	/*********************** BUILD FORM STATS - CONTENT **********/
	/*************************************************************/	
	public function buildFormStatsContent($interval = 'days', $limit = 30, $query = null, $alt_datefield = null){
		
		//get alternate date string
		if(!have($alt_datefield)){ $alt_datefield = $this->date_added_field; } 
		
		//decrypt query string
		$query = decryptString($query, HOST_NAME);
		?>
			<script type="text/javascript">
				<!--
					<?php
					$form_stats_array = array();
					for($d = $limit; $d >= 0; $d--){	
						switch($interval){
							case 'hours':
								$date_to = date('Y-m-d H:i:s', strtotime('-' . $d . ' hours')); 
								$date_from = date('Y-m-d H:i:s', strtotime('-' . ($d + 1) . ' hours'));
								$date_string = date('ha M jS, Y', strtotime($date_from)); 
							break;
							case 'days':
								$date_to = date('Y-m-d 23:59:59', strtotime('-' . $d . ' days')); 
								$date_from = date('Y-m-d 00:00:00', strtotime('-' . $d . ' days'));
								$date_string = date('M jS, Y', strtotime($date_from)); 			
							break;
							case 'weeks':
								$date_to = date('Y-m-d 23:59:59', strtotime('-' . $d . ' weeks')); 
								$date_from = date('Y-m-d 23:59:59', strtotime('-' . ($d + 1) . ' weeks'));
								$date_string = date('M jS, Y', strtotime($date_from));
							break;
							case 'months':
								$date_to = date('Y-m-d 23:59:59', strtotime('-' . $d . ' months')); 
								$date_from = date('Y-m-d 23:59:59', strtotime('-' . ($d + 1) . ' months'));
								$date_string = date('M, Y', strtotime($date_from)); 
							break;
							case 'years':
								$date_to = date('Y-m-d 23:59:59', strtotime('-' . $d . ' years')); 
								$date_from = date('Y-m-d 23:59:59', strtotime('-' . ($d + 1) . ' years'));
								$date_string = date('Y', strtotime($date_from));
							break;
							case 'all time':
								$date_to = date('Y-m-d 23:59:59');
								$date_from = '1979-01-01 00:00:00';
								$date_string = 'all time';
							break;
						}
						
						//sticky fields sql
						$sticky_fields_sql = null;
						if(have($this->sticky_fields)){ foreach($this->sticky_fields as $sticky_field => $sticky_value){ $sticky_fields_sql .= " AND " . mysql_real_escape_string($sticky_field) . " = '" . mysql_real_escape_string($sticky_value) . "' "; } } 
						
						//active filter sql
						$active_filter_sql = have($this->active_field) ? " AND " . mysql_real_escape_string($this->active_field) . " = 1 " : null; 
						
						//build array
						if(!have($query)){
							$date_count_result = @mysql_query("SELECT COUNT(*) AS hits FROM " . mysql_real_escape_string($this->table) . " WHERE 1 = 1 " . $active_filter_sql . $sticky_fields_sql . " AND " . $alt_datefield . " BETWEEN '" . mysql_real_escape_string($date_from) . "' AND '" . mysql_real_escape_string($date_to) . "'"); $period_hits = 0; $period_label = 'hits'; if(mysql_num_rows($date_count_result) > 0){ while($row_count = mysql_fetch_assoc($date_count_result)){ $period_hits = $row_count['hits']; } mysql_free_result($date_count_result); } $form_stats_array['dates'][$date_string][$period_label] = $period_hits; if(have($form_stats_array['labels'][$period_label])){ $form_stats_array['labels'][$period_label] += $period_hits; }else{ $form_stats_array['labels'][$period_label] = 0; }
						}else{
							$date_count_result = @mysql_query(str_ireplace('|||WHERE-STATEMENTS|||', " WHERE 1 = 1 " . $active_filter_sql . $sticky_fields_sql . " AND " . $alt_datefield . " BETWEEN '" . mysql_real_escape_string($date_from) . "' AND '" . mysql_real_escape_string($date_to) . "'", $query)); $period_hits = 0; $period_label = null; if(mysql_num_rows($date_count_result) > 0){ while($row_count = mysql_fetch_assoc($date_count_result)){ 
								$period_hits = $row_count['hits']; 
								$period_label = $row_count['label'];
								$form_stats_array['dates'][$date_string][$period_label] = $period_hits;
								if(have($form_stats_array['labels'][$period_label])){ $form_stats_array['labels'][$period_label] += $period_hits; }else{ $form_stats_array['labels'][$period_label] = 0; }
							} mysql_free_result($date_count_result); }
						}
						
					}
					?>
					function drawChart<?=md5($this->table . $interval . $limit . $query)?>(){
						var data = new google.visualization.DataTable();					
						data.addRows(<?=sizeof($form_stats_array['dates'])?>);
						data.addColumn('string', 'Date');
						<?php if(have($form_stats_array['labels'])){ foreach($form_stats_array['labels'] as $the_label => $the_count_for_label){ ?>
						data.addColumn('number', '<?=prepareTag($the_label)?>');
						<?php } } ?>
						<?php if(isset($form_stats_array['dates']) and !empty($form_stats_array['dates'])){ $this_index = 0; foreach($form_stats_array['dates'] as $date_string => $date_array){ $subkey = 0; ?>
						data.setValue(<?=(int)$this_index?>, <?=$subkey++?>, '<?=$date_string?>');
						<?php if(have($form_stats_array['labels'])){ foreach($form_stats_array['labels'] as $the_label => $the_count_for_label){ ?>
						<?php $hits_this_period = have($date_array[$the_label]) ? (int)$date_array[$the_label] : 0; ?>
						data.setValue(<?=(int)$this_index?>, <?=$subkey++?>, <?=(int)$hits_this_period?>);
						<?php } } ?>
						<?php $this_index++; } } ?>
						new google.visualization.LineChart(document.getElementById('chart_<?=md5($this->table . $interval . $limit . $query)?>')).draw(data, {width: 600, height: 300});	
					}
					setTimeout(drawChart<?=md5($this->table . $interval . $limit . $query)?>, <?=JAVASCRIPT_DELAY_FOR_WIDGET_LOAD?>);				
				//-->
			</script>
			<div id="chart_<?=md5($this->table . $interval . $limit . $query)?>"><div class="background-loader-for-widget" style="background-color: #ffffff;"><!-- loader --></div><!-- chart loads here --></div>	
		<?php
	}

	/*************************************************************/
	/*********************** PRINT TOP FORM BUTTON ROW ***********/
	/*************************************************************/
	protected function printTopFormButtonRow(){
		if(isset($this->alternate_fields[$this->row_name]['post_value']) and !empty($this->alternate_fields[$this->row_name]['post_value'])){
			$record_name = $this->alternate_fields[$this->row_name]['post_value'];
		}else{
			$record_name = 'New Record';
			self::presetFilterKeyValuePair();
		}
		$this_form_identity_string = (isset($_POST['form_time_' . $this->table])) ? $_POST['form_time_' . $this->table] : date('Y-m-d-H-i-s');
		$frame_class = ($this->prepare_for_iframe === true) ? 'iframe' : null;
		$total_count_string = $this->showTotalCountInHeader ? ' (' . self::getTotalRecordCount() . ')' : null;
		?>
											<div class="form_header <?=$frame_class?>">
												<?=ucwords(self::getTableName()) . $total_count_string?> &raquo; <?=$record_name?> 
												<strong style="float:right;"><?=have($this->primary_value) ? '#' . (int)$this->primary_value : '-'?></strong>
											</div><!-- end form_header -->
											<form class="form_area <?=$frame_class?>" method="post" id="form_<?=$this->table?>">
												<input type="hidden" name="form_time_<?=$this->table?>" value="<?=$this_form_identity_string?>" />		
		<?php
		if(isset($this->primary_value) and $this->primary_value > 0){
		?>
												<input type="hidden" name="this_is_the_primary_value" value="<?=(int)$this->primary_value?>" />
		<?php
		}
		if(isset($_GET['action']) and !empty($_GET['action']) and $_GET['action'] == 'delete'){ //print the delete,cancel block
		?>
												<div class="delete_row">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td style="text-align: right;">
																	<button class="cancel_button" onclick="javascript:window.location.href='<?=addToGetString(null, null, array('action','record_id'))?>';return false;">Cancel</button>
																</td>
																<td style="text-align: right; width: 158px;">
																	<button class="delete_button" name="form_submit" value="Delete Record">Delete Record</button>
																</td>
															</tr>
														</tbody>
													</table>
												</div><!-- end .delete_row -->

		<?php
		}else{ //print the reset,cancel,save block
		?>
												<div class="button_row" style="margin-bottom: 12px;">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td style="text-align: left;">
																	<input type="reset" value="Reset Form" onclick="javascript:return false;" />	
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
												</div><!-- end .button_row -->
		<?php
		}	
	}

	/*************************************************************/
	/*********************** PRINT BOTTOM FORM BUTTON ROW ********/
	/*************************************************************/
	protected function printBottomFormButtonRow(){
		?>
												<div class="button_row">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td style="text-align: left;">
																	<input type="reset" value="Reset Form" onclick="javascript:return false;" />	
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
												<div style="height: 15px;"><!-- spacer --></div>
											</form>					
		<?php
	}

	/*************************************************************/
	/*********************** PRINT ASSOCIATIVE BLOCKS ************/
	/*************************************************************/		
	protected function printAssociativeBlocks(){
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
					if(($assoc_num_rows = mysql_num_rows($result)) > 0){
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$assoc_table_name?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=(false !== ($table_label = self::getTableComments($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']))) ? $table_label : preg_replace('/[^a-z]+/i', ' ', str_ireplace(TABLES_PREPEND ,'', $this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']))?>
																</td>
																<td class="associations">
																	<div class="inner_holder">
						<?php if(have($assoc_num_rows) and $assoc_num_rows > MIN_NUMBER_OF_ASSOC_FOR_HEADER_TOOLS){ ?>	
																		<script type="text/javascript">
																			$().ready(function(){
																				$('#<?=$assoc_table_name?>_search_box').keyup(function(){
																					var this_<?=$assoc_table_name?>_search_value = $(this).val().replace(/[^a-z^0-9^\s]/gi, '');
																					var results_<?=$assoc_table_name?> = false;
																					$('li', '#<?=$assoc_table_name?>_options_list').each(function(){
																						var match_<?=$assoc_table_name?>_regex = new RegExp(this_<?=$assoc_table_name?>_search_value, 'gi');
																						if($(this).text().match(match_<?=$assoc_table_name?>_regex)){
																							results_<?=$assoc_table_name?> = true;
																							$(this).show();
																						}else{
																							$(this).hide();
																						}
																					});
																					if(results_<?=$assoc_table_name?>){
																						$('#no_results_<?=$assoc_table_name?>').hide();
																					}else{
																						$('#no_results_<?=$assoc_table_name?>').show();
																						$('#no_results_<?=$assoc_table_name?>_query_string').text(this_<?=$assoc_table_name?>_search_value);
																					}
																				});
																				$('#<?=$assoc_table_name?>_search_box_clear').click(function(){
																					$('li', '#<?=$assoc_table_name?>_options_list').show();
																					$('#<?=$assoc_table_name?>_search_box').val('');
																					$('#no_results_<?=$assoc_table_name?>').hide();
																				});
																				$('#<?=$assoc_table_name?>_unselect_all').click(function(){
																					$('li:visible input[type=checkbox]', '#<?=$assoc_table_name?>_options_list').each(function(){	
																						$(this).attr('checked', false).parents('li').removeClass('selected');
																					});
																				});
																				$('#<?=$assoc_table_name?>_select_all').click(function(){
																					$('li:visible input[type=checkbox]', '#<?=$assoc_table_name?>_options_list').each(function(){	
																						$(this).attr('checked', true).parents('li').addClass('selected');
																					});
																				});
																				$('#<?=$assoc_table_name?>_select_inverse').click(function(){
																					$('li:visible input[type=checkbox]', '#<?=$assoc_table_name?>_options_list').each(function(){	
																						if(false === $(this).attr('checked')){ $(this).attr('checked', true).parents('li').addClass('selected'); }else{ $(this).attr('checked', false).parents('li').removeClass('selected'); }
																					});
																				});
																				$('#<?=$assoc_table_name?>_undo_selection').click(function(){
																					$('li', '#<?=$assoc_table_name?>_options_list').show();
																					$('#<?=$assoc_table_name?>_search_box').val('');
																					$('#no_results_<?=$assoc_table_name?>').hide();																					
																					$('li input[type=checkbox]', '#<?=$assoc_table_name?>_options_list').each(function(){	
																						$(this).attr('checked', false).parents('li').removeClass('selected');
																					});
																					$('li.first_selected input[type=checkbox]', '#<?=$assoc_table_name?>_options_list').each(function(){
																						$(this).attr('checked', true).parents('li').addClass('selected');
																					});
																				});
																			});
																		</script>
																		<div class="associations_header">
																			<img src="/assets/img/manager/looking-glass.jpg" alt="glass" class="associations_looking_glass" />
																			<input name="<?=$assoc_table_name?>_search_box" id="<?=$assoc_table_name?>_search_box" class="associations_search_box" type="text" />
																			<div class="associations_clear_search" id="<?=$assoc_table_name?>_search_box_clear"><!-- clear search --></div>
																			<div class="uncheck_all_icon" title="Unselect all" id="<?=$assoc_table_name?>_unselect_all"><!-- clickable icon --></div>
																			<div class="check_all_icon" title="Select all" id="<?=$assoc_table_name?>_select_all"><!-- clickable icon --></div>
																			<div class="swap_selection_icon" title="Inverse selection" id="<?=$assoc_table_name?>_select_inverse"><!-- clickable icon --></div>
																			<div class="undo_selection_icon" title="Undo changes" id="<?=$assoc_table_name?>_undo_selection"><!-- clickable icon --></div>
																		</div><!-- end .associations_header -->
																		<div class="no_results" id="no_results_<?=$assoc_table_name?>">
																			No <?=(false !== ($table_label = self::getTableComments($this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']))) ? $table_label : preg_replace('/[^a-z]+/i', ' ', str_ireplace(TABLES_PREPEND ,'', $this->associative_map_tables[$assoc_table_name]['foreign_table']['table_name']))?> with "<span id="no_results_<?=$assoc_table_name?>_query_string" class="query_string"><!-- query string loads here --></span>"
																		</div>
						<?php } //end if need header tools ?>
																		<ul id="<?=$assoc_table_name?>_options_list" style="max-height: 240px; overflow-y: auto; margin-right: 40px;">		
		<?php
						while($row = mysql_fetch_assoc($result)){
							if(!isset($_POST) or empty($_POST)){
								$checked_attr = (isset($this->associative_map_tables[$assoc_table_name]['existing_keys']) and in_array($row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']], $this->associative_map_tables[$assoc_table_name]['existing_keys'])) ? ' checked="checked" ' : '';
								$selected_attr = (isset($this->associative_map_tables[$assoc_table_name]['existing_keys']) and in_array($row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']], $this->associative_map_tables[$assoc_table_name]['existing_keys'])) ? ' class="selected first_selected" ' : '';
							}else{
								$post_keys = array_keys($_POST);
								$checked_attr = (in_array($assoc_table_name . '_' . (int)$row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']], $post_keys)) ? ' checked="checked" ' : '';
								$selected_attr = (in_array($assoc_table_name . '_' . (int)$row[$this->associative_map_tables[$assoc_table_name]['foreign_table']['table_key']], $post_keys)) ? ' class="selected first_selected" ' : '';
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
	}

	/*************************************************************/
	/*********************** CONDITIONAL FIELD JAVACRIPT *********/
	/*************************************************************/	
	protected function printConditionalFieldsJs(){
		if(have($this->conditional_fields)){
			?>
												<script type="text/javascript">
													<!--
			<?php foreach($this->conditional_fields as $target_field_name => $conditional_fields){ ?>function handleConditionalFields<?=md5($this->table . $target_field_name)?>(){ if(<?php foreach($conditional_fields as $field_name => $field_condition_array){ if(have($field_condition_array)){ foreach($field_condition_array as $condition_status => $conditions_array){ foreach($conditions_array as $condition_value){ ?><?=($condition_status) ? '!' : null?>( $('input[name=<?=$field_name?>]', '#form_<?=$this->table?>').val() == '<?=$condition_value?>' || $('select[name=<?=$field_name?>] option:selected', '#form_<?=$this->table?>').val() == '<?=$condition_value?>') && <?php } } } } ?> 1 == 1 ){ $('#input_row_<?=$target_field_name?>', '#form_<?=$this->table?>').slideDown(); }else{ $('#input_row_<?=$target_field_name?>', '#form_<?=$this->table?>').hide(); } }<?php } ?>$().ready(function(){ <?php foreach($this->conditional_fields as $target_field_name => $conditional_fields){ ?>	$('select', '#form_<?=$this->table?>').change(function(){ handleConditionalFields<?=md5($this->table . $target_field_name)?>(); }); $('input', '#form_<?=$this->table?>').keyup(function(){ handleConditionalFields<?=md5($this->table . $target_field_name)?>(); }); handleConditionalFields<?=md5($this->table . $target_field_name)?>(); <?php } ?> });
													//-->
												</script>
			<?php
		}	
	}

	/*************************************************************/
	/*********************** BUILD FOREIGN TABLES BLOCK **********/
	/*************************************************************/		
	protected function printForeignTablesBlock(){		
		?>
											<div class="form_area" style="padding-top: 0px;">
		<?php											
		if($this->show_foreign_table and have($this->foreign_tables_with_primary_key) and have($this->primary_value)){
			foreach($this->foreign_tables_with_primary_key as $table_name => $table_value){
				if(in_array($table_name, $this->whitelistForeignTables)){
		?>
												<script type="text/javascript">
													<!-- 
														$().ready(function(){
															$('#input_row_iframe_<?=$table_name?> .table_name.closed').live('click', function(){
																$(this).removeClass('closed').addClass('opened').parent().children('iframe').show();
															});
															$('#input_row_iframe_<?=$table_name?> .table_name.opened').live('click', function(){
																$(this).removeClass('opened').addClass('closed').parent().children('iframe').hide();
															});
														});
													//-->
												</script>
												<div class="input_iframe" id="input_row_iframe_<?=$table_name?>">
													<div class="table_name closed"><?=(false !== ($table_label = self::getTableComments($table_name))) ? $table_label : ucwords(str_ireplace(array('_', '-', TABLES_PREPEND), array(' ',' ',''), $table_name))?></div>
													<iframe src="<?=ASSETS_PATH?>/ifr/table.php?table_name=<?=$table_name?>&filter_key=<?=$this->primary_key?>&filter_value=<?=$this->primary_value?>" width="100%" style="display:none;" id="iframe_for_<?=$table_name?>">
														<p><strong>Warning: </strong>Your browser does not support iframes.</p>
													</iframe>
												</div><!-- end .input_row -->
		<?php
				} //if whitelisted table
			}
		}
		?>
											</div><!-- end .form_area -->
		<?php	
	}

	/*************************************************************/
	/*********************** BUILD FORM FIELDS *******************/
	/*************************************************************/	
	protected function printFormFields(){
		if(!have($this->fields)){ return false; }
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
				case (isset($value['field_type']) and $value['field_type'] == 'state' and isset($value['states_array']) and !empty($value['states_array'])):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
																		<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
																			<option value=""> --------- </option>
		<?php
					foreach($value['states_array'] as $state_key => $state_value){
						$this_selected = (isset($value['post_value']) and !empty($value['post_value']) and $value['post_value'] == $state_key) ? ' selected="selected" ' : '';
		?>
																			<option value="<?=$state_key?>" <?=$this_selected?>><?=$state_value?></option>
		<?php			
					}
		?>
																		</select>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>			
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** NAME FIELD DO DYNAMIC LOOKUP ********************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (isset($value['field_type']) and $value['field_type'] == 'name' and $this->primary_value == null):
					preg_match('/[0-9]+/', $value['Type'], $matches);
					$max_length = (isset($matches[0]) and !empty($matches[0])) ? ' maxlength="' . (int)$matches[0] . '" ' : '';	
		?>
												<?php if(have($this->showNameSuggest) and $this->showNameSuggest === true){ ?>
												<script type="text/javascript">
													<!--
														$().ready(function(){
		                                    				$('#search_field_<?=$value['Field']?>').attr('autocomplete', 'off');
		                                    				$('.intelli_search_holder', '#input_row_<?=$value['Field']?>').attr('title', $('#search_field_<?=$value['Field']?>').val());
		                                    				var this_res_index_<?=$value['Field']?> = -1;
		                                    				var this_max_index_<?=$value['Field']?> = <?=(INTELLIGENT_RESULTS_LIMIT - 1)?>;
		                                    				$('#search_field_<?=$value['Field']?>').blur(function(){
		                                    					setTimeout(function(){
		                                    						$('.intelli_search_holder', '#input_row_<?=$value['Field']?>').hide();
		                                    					}, 300);
		                                    				});
		                                    				$('#search_field_<?=$value['Field']?>').focus(function(){
		                                    					if($('#search_field_<?=$value['Field']?>').val().length > 0 && $('.intelli_search_holder', '#input_row_<?=$value['Field']?>').html().length > 130){
		                                    						$('.intelli_search_holder', '#input_row_<?=$value['Field']?>').show();
		                                    					}
		                                    				});
		                                    				$('#search_field_<?=$value['Field']?>').keyup(function(event){
		                                       					var doAjaxSearch_<?=$value['Field']?> = true;
		                                    					if(event.keyCode == 40){
			                                    					this_res_index_<?=$value['Field']?> = this_res_index_<?=$value['Field']?> + 1;
		                                    						this_res_index_<?=$value['Field']?> = (this_res_index_<?=$value['Field']?> <= this_max_index_<?=$value['Field']?>) ? this_res_index_<?=$value['Field']?> : 0;
		                                    						this_res_index_<?=$value['Field']?> = (this_res_index_<?=$value['Field']?> < 0) ? this_max_index_<?=$value['Field']?> : this_res_index_<?=$value['Field']?>;
		                                    						$('.intelli_search_results a.selected', '#input_row_<?=$value['Field']?>').removeClass('selected');
		                                    						$('.intelli_search_results a#search_res_<?=$this->table?>_' + this_res_index_<?=$value['Field']?>, '#input_row_<?=$value['Field']?>').addClass('selected');
		                                    						$('#search_field_<?=$value['Field']?>').val($('.intelli_search_results a#search_res_<?=$this->table?>_' + this_res_index_<?=$value['Field']?>, '#input_row_<?=$value['Field']?>').attr('title'));
		                                    						doAjaxSearch_<?=$value['Field']?> = false;
		                                    					}
		                                    					if(event.keyCode == 38){
		                                    						this_res_index_<?=$value['Field']?> = this_res_index_<?=$value['Field']?> - 1;
		                                      						if(this_res_index_<?=$value['Field']?> == -1){
		                                    							$('.intelli_search_results a.selected', '#input_row_<?=$value['Field']?>').removeClass('selected');
		                                    							$('#search_field_<?=$value['Field']?>').focus();
		                                    							$('#search_field_<?=$value['Field']?>').val($('.intelli_search_holder', '#input_row_<?=$value['Field']?>').attr('title'));
		                                    							$('#search_field_<?=$value['Field']?>').val($('#search_field_<?=$value['Field']?>').val());
		                                    						}else{
			                                    						this_res_index_<?=$value['Field']?> = (this_res_index_<?=$value['Field']?> <= this_max_index_<?=$value['Field']?>) ? this_res_index_<?=$value['Field']?> : 0;
			                                    						this_res_index_<?=$value['Field']?> = (this_res_index_<?=$value['Field']?> < 0) ? this_max_index_<?=$value['Field']?> : this_res_index_<?=$value['Field']?>;
			                                    						$('.intelli_search_results a.selected', '#input_row_<?=$value['Field']?>').removeClass('selected');
			                                    						$('.intelli_search_results a#search_res_<?=$this->table?>_' + this_res_index_<?=$value['Field']?>, '#input_row_<?=$value['Field']?>').addClass('selected');
			                                    						$('#search_field_<?=$value['Field']?>').val($('.intelli_search_results a#search_res_<?=$this->table?>_' + this_res_index_<?=$value['Field']?>, '#input_row_<?=$value['Field']?>').attr('title'));
		                                    						}
		                                    						doAjaxSearch_<?=$value['Field']?> = false;
		                                    					}
		                                    					if(event.keyCode == 13 && $('.intelli_search_results a.selected', '#input_row_<?=$value['Field']?>').attr('href') != undefined){
		                                    						window.location.href = $('.intelli_search_results a.selected', '#input_row_<?=$value['Field']?>').attr('href');
		                                    						return false;
		                                    					}
		                                    					if(doAjaxSearch_<?=$value['Field']?>){
			                                    					var search_string_<?=$this->table?> = $(this).val();
			                                    					if(search_string_<?=$this->table?>.length > 0){ 
				                                    					$('.intelli_search_holder', '#input_row_<?=$value['Field']?>').attr('title', search_string_<?=$this->table?>);
				                                    					$.ajax({
				                                    						url : '<?=$_SERVER['PHP_SELF'] . addToGetStringAjax(array('ajax'),array('name_field_search'),array('action'))?>',
				                                    						type : 'POST',
				                                    						data : 'search_string_<?=$this->table?>=' + search_string_<?=$this->table?>,
				                                    						success : function(mssg){
				                                    							if(mssg != ''){
					                                    							$('.intelli_search_holder', '#input_row_<?=$value['Field']?>').show().html(mssg);
					                                    							this_max_index_<?=$value['Field']?> = -1;
					                                    							$('.intelli_search_results a', '#input_row_<?=$value['Field']?>').each(function(){
					                                    								this_max_index_<?=$value['Field']?> = this_max_index_<?=$value['Field']?> + 1;
					                                    							});
					                                    							this_res_index_<?=$value['Field']?> = -1;
				                                    							}else{
				                                    								$('.intelli_search_holder', '#input_row_<?=$value['Field']?>').hide();
				                                    							}
				                                    						}
				                                    					});
																	}else{
																		$('.intelli_search_holder', '#input_row_<?=$value['Field']?>').hide();
																	}
																}
																return true;
		                                    				});
		                                    			});	
													//-->
												</script>
												<?php } ?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=$value['post_value']?>" <?=$max_length?> autocomplete="off" id="search_field_<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
																		<?php if(have($this->showNameSuggest) and $this->showNameSuggest === true){ ?><div class="intelli_search_holder"><!-- search results load here --></div><?php } ?>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** UPLOAD A VIDEO TO THIS FIELD ********************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Field'], -11) == '_video_file'):
					$file_type = '*.fla;*.flv;*.mp4';
					$post_string = (isset($value['post_value']) and is_file(FRONTEND_DOCUMENT_ROOT . $value['post_value'])) ? '<img src="' . ASSETS_PATH. '/img/manager/file_icon.png" alt="file" /> <a href="http://' . HOST_NAME . $value['post_value'] . '" title="Open ' . substr($value['post_value'], strrpos($value['post_value'], '/')) . '" target="_blank">' . substr($value['post_value'], strrpos($value['post_value'], '/')) . '</a> (' . filesize(FRONTEND_DOCUMENT_ROOT . $value['post_value']) . ' Bytes)' : '';
					$required_field_seperator_path = (isset($this->required_value) and !empty($this->required_value)) ? md5($this->required_value) . '/' : '';
		?>
												<script type="text/javascript">
													<!--
														$().ready(function(){
														  	$('#file_upload_<?=$value['Field']?>').uploadify({
														    	'uploader' : '<?=ASSETS_PATH?>/upl/uploadify.swf',
														    	'script' : '<?=ASSETS_PATH?>/upl/uploadFile.php',
														    	'cancelImg' : '<?=ASSETS_PATH?>/upl/cancel.png',
														    	'folder' : '<?=UPLOADS_PATH?>/<?=$required_field_seperator_path . $this->table?>/<?=$value['Field']?>/<?=$this_form_identity_string?>',
														    	'buttonImg' : '<?=ASSETS_PATH?>/img/manager/upload_file_button.png',
														    	'wmode' : 'transparent',
														    	'fileExt' : '<?=$file_type?>',
  																'fileDesc' : 'Document Files',  
  																'multi' : false,
  																'auto' : true,
  																'onComplete' : function(event, ID, fileObj, response, data){
  																	$('#file_upload_target_<?=$value['Field']?>').html('<img src="<?=ASSETS_PATH?>/img/manager/file_icon.png" alt="file" /> <a href="http://<?=HOST_NAME?>' + response + '" title="Open ' + fileObj.name + '" target="_blank">' + fileObj.name + '</a> (' + fileObj.size + ' Bytes)').fadeIn(200);
  																	$('input[type=hidden]', '#input_row_<?=$value['Field']?>').attr('value', response);
  																},
    															'scriptData': { 'PHPSESSID': '<?=session_id()?>' }
														  	});
															<?php
																if(!empty($post_string)){
															?>
															$('#file_upload_target_<?=$value['Field']?>').fadeIn(200);
															<?php	
																}
															?>
														});
													//-->
												</script>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon_uploader"><!-- block --></div>
																		<input name="file_upload_<?=$value['Field']?>" id="file_upload_<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="file" value="<?=$value['post_value']?>" />
																		<input name="<?=$value['Field']?>" value="<?=$value['post_value']?>" type="hidden" />
																		<div class="file_dialogue_target" id="file_upload_target_<?=$value['Field']?>"><?=$post_string?><!-- form element load target --></div>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>	
		<?php
				break;	
				 				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** UPLOAD A FILE TO THIS FIELD *********************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (isset($value['field_type']) and $value['field_type'] == 'file'):
					$file_type = in_array(substr($value['Field'], strpos($value['Field'], '_file') - 3, 3), array('png','txt','doc','pdf','jpg', 'flv', 'mp4', 'gif')) ? '*.' . substr($value['Field'], strpos($value['Field'], '_file') - 3, 3) : '*.doc;*.txt;*.pdf';
					$post_string = (isset($value['post_value']) and is_file(FRONTEND_DOCUMENT_ROOT . $value['post_value'])) ? '<img src="' . ASSETS_PATH. '/img/manager/file_icon.png" alt="file" /> <a href="http://' . HOST_NAME . $value['post_value'] . '" title="Open ' . substr($value['post_value'], strrpos($value['post_value'], '/')) . '" target="_blank">' . substr($value['post_value'], strrpos($value['post_value'], '/')) . '</a> (' . filesize(FRONTEND_DOCUMENT_ROOT . $value['post_value']) . ' Bytes)' : '';
					$required_field_seperator_path = (isset($this->required_value) and !empty($this->required_value)) ? md5($this->required_value) . '/' : '';
		?>
												<script type="text/javascript">
													<!--
														$().ready(function(){
														  	$('#file_upload_<?=$value['Field']?>').uploadify({
														    	'uploader' : '<?=ASSETS_PATH?>/upl/uploadify.swf',
														    	'script' : '<?=ASSETS_PATH?>/upl/uploadFile.php',
														    	'cancelImg' : '<?=ASSETS_PATH?>/upl/cancel.png',
														    	'folder' : '<?=UPLOADS_PATH?>/<?=$required_field_seperator_path . $this->table?>/<?=$value['Field']?>/<?=$this_form_identity_string?>',
														    	'buttonImg' : '<?=ASSETS_PATH?>/img/manager/upload_file_button.png',
														    	'wmode' : 'transparent',
														    	'fileExt' : '<?=$file_type?>',
  																'fileDesc' : 'Document Files',  
  																'multi' : false,
  																'auto' : true,
  																'onComplete' : function(event, ID, fileObj, response, data){
  																	$('#file_upload_target_<?=$value['Field']?>').html('<img src="<?=ASSETS_PATH?>/img/manager/file_icon.png" alt="file" /> <a href="http://<?=HOST_NAME?>' + response + '" title="Open ' + fileObj.name + '" target="_blank">' + fileObj.name + '</a> (' + fileObj.size + ' Bytes)').fadeIn(200);
  																	$('input[type=hidden]', '#input_row_<?=$value['Field']?>').attr('value', response);
  																},
    															'scriptData': { 'PHPSESSID': '<?=session_id()?>' }
														  	});
															<?php
																if(!empty($post_string)){
															?>
															$('#file_upload_target_<?=$value['Field']?>').fadeIn(200);
															<?php	
																}
															?>
														});
													//-->
												</script>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon_uploader"><!-- block --></div>
																		<input name="file_upload_<?=$value['Field']?>" id="file_upload_<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="file" value="<?=$value['post_value']?>" />
																		<input name="<?=$value['Field']?>" value="<?=$value['post_value']?>" type="hidden" />
																		<div class="file_dialogue_target" id="file_upload_target_<?=$value['Field']?>"><?=$post_string?><!-- form element load target --></div>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>	
		<?php
				break;		

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** UPLOAD AN IMAGE TO THIS FIELD *******************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (isset($value['field_type']) and $value['field_type'] == 'image'):
					$post_string = (isset($value['post_value']) and is_file(FRONTEND_DOCUMENT_ROOT . $value['post_value'])) ? '<img src="http://' . HOST_NAME . $value['post_value'] . '" alt="image" id="image_for_' . $value['Field'] . '" style="max-width: 500px;" />' : '';
					$required_field_seperator_path = (isset($this->required_value) and !empty($this->required_value)) ? md5($this->required_value) . '/' : '';
					$with_height_array = array(); preg_match('/([0-9]+)x([0-9]+)/', $value['error_mssg'], $with_height_array);
					if(isset($with_height_array[1]) and isset($with_height_array[2])){
						$force_width = (int)$with_height_array[1];
						$force_height = (int)$with_height_array[2];
					}else{
						$force_width = round(MAX_IMAGE_HEIGHT * 1.5);
						$force_height = MAX_IMAGE_HEIGHT;
					}
		?>
												<script type="text/javascript">
													<!--
														var <?=$value['Field']?>x1 = '';
														var <?=$value['Field']?>y1 = '';
														var <?=$value['Field']?>x2 = '';
														var <?=$value['Field']?>y2 = '';
														var <?=$value['Field']?>image = '';
														var imageAreaSelect<?=$value['Field']?> = '';
														function saveCroppedImage<?=$value['Field']?>(){
															$.ajax({
																url : '<?=ASSETS_PATH?>/tools/resizeImage.php',
																type : 'POST',
																data : 'image=' + <?=$value['Field']?>image + '&x1=' + <?=$value['Field']?>x1 + '&y1=' + <?=$value['Field']?>y1 + '&x2=' + <?=$value['Field']?>x2 + '&y2=' + <?=$value['Field']?>y2 + '&width=<?=$force_width?>&height=<?=$force_height?>',
																success : function(response){
																	imageAreaSelect<?=$value['Field']?>.remove();
																	$('#image_upload_target_<?=$value['Field']?>').html('<img src="http://<?=HOST_NAME?>' + response + '" alt="' + response + '" id="image_for_<?=$value['Field']?>" />').fadeIn(200);
																	$('input[name=<?=$value['Field']?>]', '#input_row_<?=$value['Field']?>').attr('value', response);
																	$('#save_cropped_area<?=$value['Field']?>').hide();
																}
															});
														}
														$().ready(function(){
														  	$('#save_cropped_area<?=$value['Field']?>').live('click', saveCroppedImage<?=$value['Field']?>);
														  	$('#image_upload_<?=$value['Field']?>').uploadify({
														    	'uploader' : '<?=ASSETS_PATH?>/upl/uploadify.swf',
														    	'script' : '<?=ASSETS_PATH?>/upl/uploadImage.php',
														    	'cancelImg' : '<?=ASSETS_PATH?>/upl/cancel.png',
														    	'folder' : '<?=UPLOADS_PATH?>/<?=$required_field_seperator_path . $this->table?>/<?=$value['Field']?>/<?=$this_form_identity_string?>',
														    	'buttonImg' : '<?=ASSETS_PATH?>/img/manager/upload_image_button.png',
														    	'wmode' : 'transparent',
														    	'fileExt' : '*.jpg;*.gif;*.png',
  																'fileDesc' : 'Image Files',  
  																'multi' : false,
  																'auto' : true,
  																'onComplete' : function(event, ID, fileObj, response, data){
    																$('#image_upload_target_<?=$value['Field']?>').html('<img src="http://<?=HOST_NAME?>' + response + '" alt="' + fileObj.name + '" id="image_for_<?=$value['Field']?>" />').fadeIn(200);
    																var imagePath = response;
    																imageAreaSelect<?=$value['Field']?> = $('#image_for_<?=$value['Field']?>').imgAreaSelect({ 
    																	instance: true,
    																	aspectRatio: '<?=$force_width?>:<?=$force_height?>',
    																	handles: true, 
    																	onSelectChange: function(){
    																		$('#save_cropped_area<?=$value['Field']?>').show();
    																	},
    																	onSelectEnd: function(img, selection){ 
    																		<?=$value['Field']?>x1 = selection.x1;
    																		<?=$value['Field']?>y1 = selection.y1;
    																		<?=$value['Field']?>x2 = selection.x2;
    																		<?=$value['Field']?>y2 = selection.y2;
    																		<?=$value['Field']?>image = imagePath; 
    																	} 
    																});
    																$('input[name=<?=$value['Field']?>]', '#input_row_<?=$value['Field']?>').attr('value', response);
    															},
    															'scriptData': { 'PHPSESSID': '<?=session_id()?>' }
														  	});
															<?php
																if(!empty($post_string)){
															?>
															$('#image_upload_target_<?=$value['Field']?>').fadeIn(200);
															<?php	
																}
															?>
														});
													//-->
												</script>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?> (<?=$force_width?>x<?=$force_height?>)
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon_uploader"><!-- block --></div>
																		<input name="image_upload_<?=$value['Field']?>" id="image_upload_<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="file" value="<?=$value['post_value']?>" />
																		<input name="<?=$value['Field']?>" value="<?=$value['post_value']?>" type="hidden" />
																		<div class="file_dialogue_target" id="image_upload_target_<?=$value['Field']?>"><?=$post_string?><!-- form element load target --></div>
																		<div style="display: none; cursor: pointer;" id="save_cropped_area<?=$value['Field']?>"><img src="<?=ASSETS_PATH?>/img/manager/save-cropped-image-file-button.png" alt="Save Cropped Image" /></div>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" id="datepicker_<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=(isset($value['post_value']) and !empty($value['post_value'])) ? date('m/d/Y', strtotime($value['post_value'])) : ''?>" autocomplete="off" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>			
		<?php		
				break;
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** CONTENT TEMPLATE LOOKUP *************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Field'], -14) == '_page_template'):
					if($thandle = opendir(FRONTEND_DOCUMENT_ROOT . FRONTEND_TEMPLATES_PATH)){
						$templates = array(); while(false !== ($tfile = readdir($thandle))){
							if(strpos($tfile, 'template') > 0){ if($fhandle = fopen(FRONTEND_DOCUMENT_ROOT . FRONTEND_TEMPLATES_PATH . $tfile, 'r')){
								$tcontent = fread($fhandle, filesize(FRONTEND_DOCUMENT_ROOT . FRONTEND_TEMPLATES_PATH . $tfile));
								$matches = array(); preg_match('/\/\* Template name:([a-z0-9\s]+)\*\//msi', $tcontent, $matches);
								$template_name = (isset($matches[1]) and !empty($matches[1])) ? $matches[1] : str_replace(array('.php', '.template'), array('', ''), $tfile);
								$templates[FRONTEND_TEMPLATES_PATH . $tfile]['name'] = $template_name;
							} }
						} //while reading templates dir
					
					
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder" style="padding-top: 10px; padding-bottom: 10px;">
																		<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
																		<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
																			<option value="">Not Applicable</option>
		<?php
						if(isset($templates) and !empty($templates)){
							foreach($templates as $template_path => $template_array){
								//select posted value
								$this_selected = (isset($value['post_value']) and !empty($value['post_value']) and $value['post_value'] == $template_path) ? ' selected="selected" ' : null;
								//select default value
								$this_selected = ((!isset($value['post_value']) or empty($value['post_value'])) and $value['Default'] == $template_path) ? ' selected="selected" ' : $this_selected;
								
								//set page template
								$this->page_template = $value['post_value'];
		?>
																			<option value="<?=htmlentities($template_path)?>" <?=$this_selected?>><?=htmlentities($template_array['name'])?></option>		
		<?php				
							}
						}
		?>
																		</select>
																	</div>
																</td>
															</tr>
														</tbody>
													</table>
												</div>			
		<?php	
					} //if templates dir
					
				break;
				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** FORM FIELD VALUE TYPE ***************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Field'], -16) == '_form_field_type'):
					if($fhandle = fopen(FRONTEND_DOCUMENT_ROOT . FRONTEND_FORM_BUILDER_PATH, 'r')){
						$classcontent = fread($fhandle, filesize(FRONTEND_DOCUMENT_ROOT . FRONTEND_FORM_BUILDER_PATH));
						$matches = array(); preg_match_all('/case[\s\t]*\'([a-z0-9]+)\'\:[\s\t]*\/\/form[\s\t]+option/msi', $classcontent, $matches);
						$form_field_options = (isset($matches[1]) and !empty($matches[1])) ? $matches[1] : null;
					} //get field options from form builder class
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder" style="padding-top: 10px; padding-bottom: 10px;">
																		<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
																		<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
		<?php
						if(isset($form_field_options) and !empty($form_field_options)){
							foreach($form_field_options as $form_field_option_key => $form_field_option_value){
								//select posted value
								$this_selected = (isset($value['post_value']) and !empty($value['post_value']) and $value['post_value'] == $form_field_option_value) ? ' selected="selected" ' : null;
								//select default value
								$this_selected = ((!isset($value['post_value']) or empty($value['post_value'])) and $value['Default'] == $form_field_option_value) ? ' selected="selected" ' : $this_selected;
		?>
																			<option value="<?=htmlentities($form_field_option_value)?>" <?=$this_selected?>><?=htmlentities($form_field_option_value)?></option>		
		<?php				
							}
						}
		?>
																		</select>
																	</div>
																</td>
															</tr>
														</tbody>
													</table>
												</div>			
		<?php					
				break;				

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** ELEMENT TEMPLATE LOOKUP *************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Field'], -16) == 'element_template'):
					if($ehandle = opendir(FRONTEND_DOCUMENT_ROOT . FRONTEND_ELEMENTS_PATH)){ $elements = array(); while(false !== ($efile = readdir($ehandle))){ if(strpos($efile, 'element') > 0){ $elements[$efile] = $efile; } }				
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
																		<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
																			<option value="">Not Applicable</option>
		<?php
						if(isset($elements) and !empty($elements)){
							foreach($elements as $element_path => $element_filename){
								//select posted value
								$this_selected = (isset($value['post_value']) and !empty($value['post_value']) and $value['post_value'] == $element_path) ? ' selected="selected" ' : null;
								//select default value
								$this_selected = ((!isset($value['post_value']) or empty($value['post_value'])) and $value['Default'] == $element_path) ? ' selected="selected" ' : $this_selected;
		?>
																			<option value="<?=htmlentities($element_path)?>" <?=$this_selected?>><?=htmlentities($element_filename)?></option>		
		<?php				
							}
						}
		?>
																		</select>
																	</div>
																</td>
															</tr>
														</tbody>
													</table>
												</div>			
		<?php	
					} //if templates dir
					
				break;

				
				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** SELECT BOX FROM ENUM ****************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 4) == 'enum'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
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
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
																		<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
																			<option value="0" <?php if((isset($value['post_value']) and $value['post_value'] == 0) or (strlen($value['post_value']) == 0 and $value['Default'] == 0)){ echo 'selected="seletected"'; } ?>>off</option>
																			<option value="1" <?php if((isset($value['post_value']) and $value['post_value'] == 1) or (strlen($value['post_value']) == 0 and $value['Default'] == 1)){ echo 'selected="seletected"'; } ?>>on</option>
																		</select>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>				
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** PARENT ID TABLE *********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (isset($value['parent_options']) and false !== ($parent_options = self::getParentSelectArray())):
		?>									
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
																		<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
																			<option value="0"> -- top level -- </option>
		<?php
					foreach($parent_options as $option_array){
						$this_selected = (isset($value['post_value']) and $value['post_value'] == $option_array['dzpro_page_id']) ? ' selected="selected" ' : null;
						$this_disabled = (isset($this->primary_value) and $this->primary_value == $option_array['dzpro_page_id']) ? 'disabled style="color: #999;"' : null;
						$tabs = null; for($i = 1; $i <= (int)$option_array['level']; $i++){ $tabs .= '&nbsp;&nbsp;&nbsp;&nbsp;'; }
		?>
																			<option value="<?=$option_array['dzpro_page_id']?>" <?=$this_selected?> <?=$this_disabled?>><?=$tabs . $option_array['dzpro_page_title']?></option>
		<?php
					}		
		?>
																		</select>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
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
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>		
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** CRON PATH ***************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Field'], -10) == '_cron_path'):
					if($chandle = opendir(DOCUMENT_ROOT . CRONJOBS_PATH_PATH)){ $cronjobs = array(); while(false !== ($cfile = readdir($chandle))){ if(strpos($cfile, 'cron') > 0){ $cronjobs[$cfile] = $cfile; } }
					
					
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder" style="padding-top: 10px; padding-bottom: 10px;">
																		<?php if(!self::canFieldBeEdited($value)){ ?><input type="hidden" name="<?=$value['Field']?>" value="<?=$value['post_value']?>" /><?php } ?>
																		<select name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'disabled="disabled"' : null?>>
																			<option value="">Not Applicable</option>
		<?php
						if(isset($cronjobs) and !empty($cronjobs)){
							foreach($cronjobs as $cron_path => $cron_filename){
								//select posted value
								$this_selected = (isset($value['post_value']) and !empty($value['post_value']) and $value['post_value'] == $cron_path) ? ' selected="selected" ' : null;
								//select default value
								$this_selected = ((!isset($value['post_value']) or empty($value['post_value'])) and $value['Default'] == $cron_path) ? ' selected="selected" ' : $this_selected;
		?>
																			<option value="<?=htmlentities($cron_path)?>" <?=$this_selected?>><?=htmlentities($cron_filename)?></option>		
		<?php				
							}
						}
		?>
																		</select>
																	</div>
																</td>
															</tr>
														</tbody>
													</table>
												</div>			
		<?php	
					} //if templates dir
					
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** MONTH *******************************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Field'], -6) == '_month' and substr($value['Type'], 0, 3) == 'int'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder" style="padding-top: 10px; padding-bottom: 10px;">
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
																	</div>
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder" style="padding-top: 10px; padding-bottom: 10px;">
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
																	</div>
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder" style="padding-top: 10px; padding-bottom: 10px;">
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
																	</div>
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder" style="padding-top: 10px; padding-bottom: 10px;">
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
																	</div>
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder" style="padding-top: 10px; padding-bottom: 10px;">
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
																	</div>
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
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
																	</div>
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=$value['post_value']?>" autocomplete="off" <?=$max_length?> <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=$value['post_value']?>" autocomplete="off" <?=$max_length?> <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="password" value="" <?=$max_length?>  autocomplete="off" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
																		<input name="<?=$value['Field']?>_hash" type="hidden" value="<?=$value['db_value']?>" />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input" style="padding: 10px;">
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
																	<div class="inner_holder">
																		<div id="color_picker_for_<?=$value['Field']?>" style="display: none; margin-bottom: 5px;"><!-- picker --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=have($value['post_value']) ? $value['post_value'] : '#ffffff'?>" autocomplete="off" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> style="border-top-left-radius: 5px;border-top-right-radius: 5px;border-bottom-right-radius: 5px;border-bottom-left-radius: 5px;" />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>
			<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** DATETIME FIELD **********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 8) == 'datetime' and substr($value['Field'], -11) != '_date_added'):
		?>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=$value['post_value']?>" autocomplete="off" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=$value['post_value']?>" <?=$max_length?>  autocomplete="off" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="input">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<input name="<?=$value['Field']?>" title="<?=$value['error_mssg']?>" type="text" value="<?=$value['post_value']?>" <?=$max_length?>  autocomplete="off" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?> />
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>		
		<?php
				break;

				/**********************************************************************************************/
				/**********************************************************************************************/
				/******************************** HTML AREA FIELD *********************************************/
				/**********************************************************************************************/
				/**********************************************************************************************/
				case (substr($value['Type'], 0, 4) == 'text' and substr($value['Field'], -4) == 'html'):
		?>
												<script type="text/javascript">
													<!--
														tinyMCE.init({
															mode : "textareas",
															theme : "advanced",
															editor_selector : "html_editor_<?=$value['Field']?>",
															plugins : "pagebreak,style,paste,directionality,visualchars,nonbreaking,xhtmlxtras,template",
															theme_advanced_buttons1 : "pagebreak,styleprops,removeformat,hr,|,pastetext,pasteword,bullist,numlist,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull",
															theme_advanced_buttons2 : "formatselect,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,cleanup,forecolor,backcolor,code",
															theme_advanced_buttons3 : "",
															theme_advanced_buttons4 : "",
															theme_advanced_toolbar_location : "top",
															theme_advanced_toolbar_align : "left",
															theme_advanced_blockformats : "p,h1,h2,h3,blockquote,code"
														});
													//-->
												</script>
												<div class="input_row inner_shadow" id="input_row_<?=$value['Field']?>">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="textarea">
																	<div class="inner_holder">
																		<textarea title="<?=$value['error_mssg']?>" name="<?=$value['Field']?>" style="height: 400px; padding: 0;" class="html_editor_<?=$value['Field']?>"><?=$value['post_value']?></textarea>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="textarea">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<textarea title="<?=$value['error_mssg']?>" name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?>><?=$value['post_value']?></textarea>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
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
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	<?=self::handleFieldLabel($value['field_name'])?>
																</td>
																<td class="textarea">
																	<div class="inner_holder">
																		<div class="close_icon"><!-- block --></div>
																		<textarea title="<?=$value['error_mssg']?>" name="<?=$value['Field']?>" <?=!self::canFieldBeEdited($value) ? 'readonly="true"' : null?>><?=$value['post_value']?></textarea>
																	</div><!-- .inner_holder -->
																</td>
															</tr>
														</tbody>
													</table>
												</div>		
		<?php		
				break;
				
			}
		}
	}
	
}
?>