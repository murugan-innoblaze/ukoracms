<?php
class Templates extends Page {

	/*****************************************/
	/************ CONSTRUCT TEMPLATES ********/
	/*****************************************/
	function __construct($db){
	
		//run form constructor
		parent::__construct($db, $table_name, $parameters, $sticky_fields);
		
		//update file
		if(isset($_POST['template_file_contents']) and !empty($_POST['template_file_contents']) and isset($_GET['template_path']) and is_file(FRONTEND_DOCUMENT_ROOT . FRONTEND_TEMPLATES_PATH . $_GET['template_path'])){ self::updateTemplateFile(); }
	
	}

	/*****************************************/
	/************ UPDATE TEMPLATE FILE *******/
	/*****************************************/		
	protected function updateTemplateFile(){
		if(!isset($_GET['template_path'])){ return false; }
		$template_file_path = FRONTEND_DOCUMENT_ROOT . FRONTEND_TEMPLATES_PATH . $_GET['template_path'];
		if(false !== ($fh = fopen($template_file_path, 'w'))){ fwrite($fh, stripslashes($_POST['template_file_contents'])); fclose($fh); $this->event = 'updated'; return true; }
		$this->event = 'not updated'; return false;
	}

	/*****************************************/
	/************ TEMPLATE LISTINGS **********/
	/*****************************************/	
	public function templateListings(){
		if(empty($this->templates)){ return null; }
	?>
			<ul class="listing_parent">
	<?php	
		foreach($this->templates as $template_path => $template_array){
			$selected = ($template_path == FRONTEND_TEMPLATES_PATH . $_GET['template_path']) ? ' selected ' : '';
	?>
				<li id="list_record_<?=$template_array['template_file']?>" class="record_listing <?=$selected?>">
					<a href="<?=addToGetString(array('action', 'template_path'), array('edit', $template_array['template_file']))?>" title="<?=htmlentities($template_array['template_name'])?>" class="form_link"><!-- block --></a>
					<strong class="title" title="<?=htmlentities($template_array['template_name'])?>"><?=$template_array['template_name']?></strong>
					<strong class="sub" title="<?=htmlentities($template_array['template_file'])?>"><?=$template_array['template_file']?></strong>
					<p></p>
					<img src="<?=ASSETS_PATH?>/img/manager/bucket_right_arrow.png" alt="arrow" class="arrow_img" />
				</li>	
	<?php		
		}
	?>
			</ul>
	<?php
	}

	/**************************************************/
	/************ EXTRA HEAD STUFF - CODEMIRROR *******/
	/**************************************************/
	public function loadCodeMirrorFiles(){
		?>
		    <link rel="stylesheet" href="<?=ASSETS_PATH?>/codem/lib/codemirror.css">
		    <script src="<?=ASSETS_PATH?>/codem/lib/codemirror.js"></script>
		    <script src="<?=ASSETS_PATH?>/codem/mode/xml/xml.js"></script>
		    <link rel="stylesheet" href="<?=ASSETS_PATH?>/codem/mode/xml/xml.css">
		    <script src="<?=ASSETS_PATH?>/codem/mode/javascript/javascript.js"></script>
		    <link rel="stylesheet" href="<?=ASSETS_PATH?>/codem/mode/javascript/javascript.css">
		    <script src="<?=ASSETS_PATH?>/codem/mode/css/css.js"></script>
		    <link rel="stylesheet" href="<?=ASSETS_PATH?>/codem/mode/css/css.css">
		    <script src="<?=ASSETS_PATH?>/codem/mode/clike/clike.js"></script>
		    <link rel="stylesheet" href="<?=ASSETS_PATH?>/codem/mode/clike/clike.css">
		    <script src="<?=ASSETS_PATH?>/codem/mode/php/php.js"></script>
		<?php
	}
	
	/*****************************************/
	/************ TEMPLATE BLOCK *************/
	/*****************************************/
	public function templateBlock(){
		if(!isset($this->templates[FRONTEND_TEMPLATES_PATH . $_GET['template_path']]) or empty($this->templates[FRONTEND_TEMPLATES_PATH . $_GET['template_path']])){ return null; }
	?>
			<div class="form_header">Template: <?=$this->templates[FRONTEND_TEMPLATES_PATH . $_GET['template_path']]['template_name']?> (<?=$_GET['template_path']?>)</div>
			<div style="padding: 12px;">
				<form class="form_area" method="post">
					<div class="input_row" style="-moz-border-radius: 0px; border-radius: 0px; -webkit-border-radius: 0px;">
						<table cellpadding="0" cellspacing="0">
							<tbody>
								<tr>
									<td class="textarea">
										<div class="inner_holder" style="padding: 0px;">
											<script type="text/javascript">
												function resizeCodeMirrorWindow(){ $('.CodeMirror').css({'width' : '200px'}); $('.CodeMirror').css({'height' : $('#template-content-holder').height() + 'px', 'width' : $('#template-content-holder').width() + 'px'}); }
												function setCodeMirrorOnTextarea(areaid){ var editor = CodeMirror.fromTextArea(document.getElementById(areaid), { lineNumbers: true, matchBrackets: true, mode: "application/x-httpd-php", indentUnit: 4, indentWithTabs: true, enterMode: "keep", tabMode: "shift" }); }
												var resizeEditWindowTimeout = '';
												$().ready(function(){
													setCodeMirrorOnTextarea("template_file_textarea_code");
													resizeCodeMirrorWindow(); $(window).resize(function(){ clearTimeout(resizeCodeMirrorWindow); resizeEditWindowTimeout = setTimeout(resizeCodeMirrorWindow, 500); });
												});
											</script>
											<div id="template-content-holder">
												<textarea name="template_file_contents" id="template_file_textarea_code"><?=htmlentities(getFileContents(FRONTEND_DOCUMENT_ROOT . FRONTEND_TEMPLATES_PATH . $_GET['template_path']))?></textarea>
											</div>
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
										<input type="reset" value="Reset Template" />	
									</td>
									<td style="text-align: right;">
										<button class="cancel_button" onclick="javascript:window.location.href='<?=addToGetString(null, null, array('action','record_id'))?>';return false;">Cancel</button>
									</td>
									<td style="text-align: right; width: 164px;">
										<input type="submit" name="form_submit" value="Save Template" class="save_button" onclick="javascript:return confirm('Are you sure you want to overwrite this template file?');" />
									</td>
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