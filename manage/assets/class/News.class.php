<?php
class News extends Form {

	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
		
		//run form constructor
		parent::__construct($db, $table_name, $parameters, $sticky_fields);

		//if form submitted ... lets check them and if correct insert the record
		if(self::checkValues() and $this->primary_value == false and !isset($_POST['this_is_the_primary_value']) and !empty($_POST)){ self::publishToSocialMedia(); }

	}

	/*************************************************************/
	/*********************** PUBLISH TO SOCIAL MEDIA *************/
	/*************************************************************/	
	public function publishToSocialMedia(){
		
		/*************************************************************/
		/*********************** PUBLISH TO TWITTER ******************/
		/*************************************************************/	
		if(false !== ($twitter_oauth_token = getSiteData('twitter_oauth_token')) and false !== ($twitter_oauth_token_secret = getSiteData('twitter_oauth_token_secret')) and isset($_POST[$this->row_description]) and !empty($_POST[$this->row_description]) and isset($_POST['publish_to_twitter']) and $_POST['publish_to_twitter'] == 'true'){ 
			$this->twitter_posted = true;
			$TwitterOAuth = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $twitter_oauth_token, $twitter_oauth_token_secret);
			$TwitterOAuth->get('account/verify_credentials');
			$TwitterOAuth->post('statuses/update', array('status' => substr($_POST[$this->row_description], 0, TWITTER_STATUS_LIMIT)));
		}
		
		/*************************************************************/
		/*********************** PUBLISH TO FACEBOOK *****************/
		/*************************************************************/			
		if(false !== getSiteData('facebook_access_token') and false !== getSiteData('facebook_user_id') and isset($_POST[$this->row_description]) and !empty($_POST[$this->row_description]) and isset($_POST['publish_to_facebook']) and $_POST['publish_to_facebook'] == 'true'){ 
			$this->facebook_posted = true;
			$Facebook = new Facebook($this->db);
			$Facebook->streamPublish($_POST[$this->row_description]);
		}
		
		/*************************************************************/
		/*********************** PUBLISH TO LINKEDIN *****************/
		/*************************************************************/		
		if(isset($_SESSION['linkedin']) and !empty($_SESSION['linkedin']) and isset($_POST['publish_to_linkedin']) and $_POST['publish_to_linkedin'] == 'true'){
			$this->linkedin_posted = true;
			$LinkedIn = new LinkedIn(LINKEDIN_API_KEY, LINKEDIN_SECRET_KEY, LINKEDIN_CALLBACK_URL);
			$LinkedIn->request_token = unserialize($_SESSION['linkedin']['requestToken']);
			$LinkedIn->oauth_verifier = $_SESSION['linkedin']['oauth_verifier'];
			$LinkedIn->access_token = unserialize($_SESSION['linkedin']['oauth_access_token']);
			//$xml_response = $LinkedIn->simpleShare($_POST[$this->row_description]);
			//var_dump($xml_response);	
		}
		
	}
	
	/*************************************************************/
	/*********************** BUILD EVENT BLOCK *******************/
	/*************************************************************/
	public function showNewsEventBlock(){
		$return = true; //lets assume we want to show the form
		$frame_class = ($this->prepare_for_iframe === true) ? 'iframe' : null;
		if(isset($this->linkedin_posted)){ $this->event .= ' posted to linkedin '; }
		if(isset($this->facebook_posted)){ $this->event .= ' posted to facebook '; }
		if(isset($this->twitter_posted)){ $this->event .= ' posted to twitter '; }
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
	/*********************** POST TO SOCIAL MEDIA ****************/
	/*************************************************************/	
	protected function postToSocialMediaUi(){
		?>
												<div class="input_row inner_shadow" id="input_row_social_media">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<tr>
																<td class="label">
																	publish to
																</td>
																<td class="associations">
																	<div class="inner_holder">
																		<ul>		
																			<?php if(false !== getSiteData('facebook_access_token') and false !== getSiteData('facebook_user_id')){ ?>
																			<li>
																				<input type="checkbox" name="publish_to_facebook" value="true" /> <img src="/assets/img/manager/icon-facebook.jpg" alt="icon" style="vertical-align: middle;" /> Publish to Facebook fan page
																			</li>
																			<?php }else{ ?>
																			<li>
																				<img src="/assets/img/manager/icon-facebook.jpg" alt="icon" style="vertical-align: middle;" /> Not connected to Facebook
																			</li>
																			<?php } ?>
																			<?php if(false !== ($twitter_oauth_token = getSiteData('twitter_oauth_token')) and false !== ($twitter_oauth_token_secret = getSiteData('twitter_oauth_token_secret'))){ ?>
																			<li>
																				<input type="checkbox" name="publish_to_twitter" value="true" /> <img src="/assets/img/manager/icon-twitter.jpg" alt="icon" style="vertical-align: middle;" /> Publish to Twitter
																			</li>
																			<?php }else{ ?>
																			<li>
																				<img src="/assets/img/manager/icon-twitter.jpg" alt="icon" style="vertical-align: middle;" /> Not connected to Twitter
																			</li>
																			<?php } ?>
																			<?php if(isset($_SESSION['linkedin']) and !empty($_SESSION['linkedin'])){ ?>
																			<li>
																				<input type="checkbox" name="publish_to_linkedin" value="true" /> <img src="/assets/img/manager/icon-linkedin.jpg" alt="icon" style="vertical-align: middle;" /> Publish as LinkedIn status
																			</li>
																			<?php }else{ ?>
																			<li>
																				<img src="/assets/img/manager/icon-linkedin.jpg" alt="icon" style="vertical-align: middle;" /> Not connected to LinkedIn 
																			</li>
																			<?php } ?>
																		</ul>
																	</div><!-- end .inner_holder -->
																</td><!-- end .associations -->
															</tr>
														</tbody>
													</table>	
												</div><!-- end .input_row -->		
		<?php
	}
	
	/*************************************************************/
	/*********************** PUBLISH TO SOCIAL MEDIA *************/
	/*************************************************************/	
	public function printNewsAreaBlock(){
		
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
		/******************************** SOCIAL MEDIA LINKS ******************************************/
		/**********************************************************************************************/
		/**********************************************************************************************/
		self::postToSocialMediaUi();
		
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
	}

}
?>