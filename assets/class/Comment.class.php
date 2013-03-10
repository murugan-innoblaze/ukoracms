<?php
class Comment {

	function __construct($db, $comments_key){
		/*********************************/
		/******** database connection ****/
		/*********************************/
		$this->db = $db;
		/*********************************/
		/******** start session **********/
		/*********************************/
		if(!sessionStarted()){ session_start(); }
		/*********************************/
		/******** comments key ***********/
		/*********************************/		
		$this->comments_key = isset($comments_key) ? $comments_key : null;
		/*********************************/
		/******** other info *************/
		/*********************************/	
		$this->total_comments = 0;
		/*********************************/
		/******** actions switch *********/
		/*********************************/
		if(isset($_GET['action']) and !empty($this->comments_key)){
			switch(true){
				case($_GET['action'] == 'insert_comment'): self::insertComment(); insertIntelligenceStack(); exit(0); break;
				case($_GET['action'] == 'insert_reply'): self::insertReply(); insertIntelligenceStack(); exit(0); break;
				case($_GET['action'] == 'comment_vote_up'): echo self::voteOnComment(true); insertIntelligenceStack(); exit(0); break;
				case($_GET['action'] == 'comment_vote_down'): echo self::voteOnComment(false); insertIntelligenceStack(); exit(0); break;
			}
		}			
	}

	/*************************************************************/
	/*********************** VOTE ON COMMENT *********************/
	/*************************************************************/	
	private function voteOnComment($up = true){
		if(
			isset($_POST['comment_id']) and 
			isset($_SESSION['front-end-user']['dzpro_user_id'])
		){
			$increment_field = ($up) ? 'dzpro_comment_up' : 'dzpro_comment_down';
			$sql_select = "
					SELECT
						COUNT(*)
					FROM 
						dzpro_comment_concience
					WHERE 
						dzpro_user_id = " . (int)$_SESSION['front-end-user']['dzpro_user_id'] . "
					AND 
						dzpro_comment_id = " . (int)$_POST['comment_id'] . "
					AND
						dzpro_comment_concience_hash = '" . mysql_real_escape_string(md5($increment_field)) . "'
				";
			$result_select = mysql_query($sql_select) or die(mysql_error());
			if(mysql_num_rows($result_select) > 0){
				$row_select = mysql_fetch_row($result_select);
				if(isset($row_select[0]) and (int)$row_select[0] > 0){
					return 'voted';
				}else{
					//perform vote
					$sql_vote = "
									UPDATE 
										dzpro_comments
									SET 
										" . mysql_real_escape_string($increment_field) . " = " . mysql_real_escape_string($increment_field) . " + 1
									WHERE 
										dzpro_comment_id = " . (int)$_POST['comment_id'] . "
								";
					@mysql_query($sql_vote);
					if(@mysql_affected_rows() > 0){
						addToIntelligenceStack('comment vote', $increment_field);
						$sql_votes = "
										SELECT 
											dzpro_comment_up,
											dzpro_comment_down
										FROM 
											dzpro_comments
										WHERE
											dzpro_comment_id = " . (int)$_POST['comment_id'] . "
									";
						$result_votes = mysql_query($sql_votes);
						if(mysql_num_rows($result_votes) > 0){
							$row_votes = mysql_fetch_row($result_votes);
							if(isset($row_votes[0]) and isset($row_votes[1])){
								$sql_insert = "
											INSERT INTO 
												dzpro_comment_concience
											(
												dzpro_user_id,
												dzpro_comment_id,
												dzpro_comment_concience_hash
											) VALUES (
												" . (int)$_SESSION['front-end-user']['dzpro_user_id'] . ",
												" . (int)$_POST['comment_id'] . ",
												'" . mysql_real_escape_string(md5($increment_field)) . "'
											)
										";
								mysql_query($sql_insert) or die(mysql_error());
								return self::buildCountString($row_votes[0], $row_votes[1]);								
							}else{
								return 'error';
							}
							mysql_free_result($result_votes);
						}else{
							return 'error';
						}
					}else{
						return 'error';
					}
				}
			}else{
				return 'error';
			}
		}else{
			return 'error';
		}
	}
	
	/*************************************************************/
	/*********************** GET COMMENT DETAILS *****************/
	/*************************************************************/
	private function getCommentArray($id = false){
		if($id){
			$sql = "
						SELECT 
							*
						FROM 
							dzpro_comments
						LEFT JOIN
							dzpro_users
						USING
							( dzpro_user_id )
						WHERE 
							dzpro_comment_id = " . (int)$id . "
						LIMIT 
							1
					";
			$result = mysql_query($sql) or die(mysql_error());
			if(mysql_num_rows($result) > 0){
				$return_array = array();
				while($row = mysql_fetch_assoc($result)){
					$return_array = $row;
				}
				mysql_free_result($result);
				return $return_array;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	private function getRepliesString($comment_id =  null){
		if(isset($comment_id) and !empty($comment_id)){
			$replies_string = '';
			$sql = "
						SELECT 
							*
						FROM 
							dzpro_comments
						LEFT JOIN
							dzpro_users
						USING
							( dzpro_user_id )
						WHERE 
							dzpro_comment_parent_id = " . (int)$comment_id . "
						ORDER BY 
							dzpro_comment_date_added DESC
					";
			$result = mysql_query($sql) or die(mysql_error());
			if(mysql_num_rows($result) > 0){
				while($row = mysql_fetch_assoc($result)){
					if(false !== ($reply_content = self::createReply($row))){
						$replies_string .= $reply_content; $reply_content = null;
					}
				}
				mysql_free_result($result);
				return $replies_string;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	/*************************************************************/
	/*********************** INSERT COMMENT **********************/
	/*************************************************************/	
	private function insertComment(){
		if(isset($_POST['comment']) and activeUserSession()){
			$sql = "
						INSERT INTO 
							dzpro_comments 
						(
							dzpro_user_id,
							dzpro_comment_key,
							dzpro_comment_text,
							dzpro_comment_date_added
						) VALUES (
							" . (int)$_SESSION['front-end-user']['dzpro_user_id'] . ",
							'" . mysql_real_escape_string($this->comments_key) . "',
							'" . mysql_real_escape_string($_POST['comment']) . "',
							NOW()
						)
					";
			mysql_query($sql) or die(mysql_error());
			if(mysql_insert_id() > 0){
				if(false !== ($comment_array = self::getCommentArray(mysql_insert_id()))){
					addToIntelligenceStack('comment', mysql_insert_id());
					if(false !== ($comment_content = self::createComment($comment_array))){
						echo $comment_content;
					} 
				}
			}else{
				return false;
			}
		}else{
			return false;
		}
	}	

	/*************************************************************/
	/*********************** INSERT REPLY ************************/
	/*************************************************************/	
	private function insertReply(){
		if(isset($_POST['comment']) and activeUserSession() and isset($_POST['comment_parent_id']) and (int)$_POST['comment_parent_id'] > 0){
			$sql = "
						INSERT INTO 
							dzpro_comments 
						(
							dzpro_user_id,
							dzpro_comment_parent_id,
							dzpro_comment_key,
							dzpro_comment_text,
							dzpro_comment_date_added
						) VALUES (
							" . (int)$_SESSION['front-end-user']['dzpro_user_id'] . ",
							" . (int)$_POST['comment_parent_id'] . ",
							'" . mysql_real_escape_string($this->comments_key) . "',
							'" . mysql_real_escape_string($_POST['comment']) . "',
							NOW()
						)
					";
			mysql_query($sql) or die(mysql_error());
			if(mysql_insert_id() > 0){
				if(false !== ($comment_array = self::getCommentArray(mysql_insert_id()))){
					addToIntelligenceStack('comment', mysql_insert_id());
					addToIntelligenceStack('comment reply', mysql_insert_id());
					if(false !== ($comment_content = self::createReply($comment_array))){
						echo $comment_content;
					} 
				}
			}else{
				return false;
			}
		}else{
			return false;
		}
	}	

	/*************************************************************/
	/*********************** PRINT COMMENTS **********************/
	/*************************************************************/	
	public function printCommentsArea(){
		$comments_html = '';
		$sql = "
					SELECT SQL_CALC_FOUND_ROWS
						*
					FROM 
						dzpro_comments
					LEFT JOIN
						dzpro_users
					USING
						( dzpro_user_id )
					WHERE 
						dzpro_comment_key = '" . mysql_real_escape_string($this->comments_key) . "'
					AND 
						dzpro_comment_parent_id = 0
					ORDER BY 
						dzpro_comment_date_added DESC
				";
		if(!isset($_GET['view_all_comments'])){ $sql .= " LIMIT  " . SHOW_COMMENTS_LIMIT; }
		$result = mysql_query($sql) or die(mysql_error());
		$found_rows_sql = "SELECT FOUND_ROWS();";
		$found_rows_result = mysql_query($found_rows_sql) or die(mysql_error());
		if(mysql_num_rows($found_rows_result) > 0){ $found_rows_row = mysql_fetch_row($found_rows_result); $this->total_comments = (int)$found_rows_row[0]; }		
		if(mysql_num_rows($result) > 0){
			$counter = 0;
			while($row = mysql_fetch_assoc($result)){
				if(false !== ($comment_content = self::createComment($row, $class = (floor($counter/2) == ($counter/2)) ? 'even' : 'odd'))){
					$comments_html .= $comment_content; $comment_content = null; $counter++;	
				} 
			}
			mysql_free_result($result);
		}
		self::buildAddContentBlock();
		echo $comments_html;
		self::buildContentFooter();
	}

	/*************************************************************/
	/*********************** PRINT PAGE HEAD BLOCK ***************/
	/*************************************************************/	
	public function printHeadBlock(){
		?>
		<link type="text/css" href="/assets/css/comments.css" rel="stylesheet" media="all" />
		<?php
	}
	
	/*************************************************************/
	/*********************** PRINT ADD CONTENT BLOCK *************/
	/*************************************************************/
	private function buildAddContentBlock(){
		?>
		<div class="comments_block">
			<script type="text/javascript">
				<!--
					
					//Clear comments
					function clearMainCommentBox(){
						$('.text_area_box').val('').removeClass('in_focus');	
					}
					
					$().ready(function(){
					
						//handle text in focus comment
						$('textarea.text_area_box').live('focus', function(){ 
							<?php if(activeUserSession()){ ?>
								$(this).addClass('in_focus');
								$('.reply_text_and_buttons').each(function(){
									$(this).hide().parent().children('.reply_button_block').show().children('textarea.text_area_box_reply').focus();
								});
							<?php }else{ ?>
								$('a.connect').trigger('click');
							<?php } ?> 
						});
						/*
						.blur(function(){ 
							if($(this).val().length == 0){ $(this).removeClass('in_focus'); } 
						});
						*/ 
											
						//Click reply
						$('button.reply').live('click', function(){ 
							<?php if(activeUserSession()){ ?>
							$('.reply_text_and_buttons').each(function(){
								$(this).hide().parent().children('.reply_button_block').show();
							});
							if($('textarea.text_area_box').val().length == 0){ $('textarea.text_area_box').removeClass('in_focus'); }
							$(this).parent().hide().parent().children('.reply_text_and_buttons').show().children('textarea.text_area_box_reply').focus(); 
							<?php }else{ ?>
							//when not connected - prompt connect
							$('a.connect').trigger('click');
							<?php } ?> 
						});
						$('button.cancel_reply').live('click', function(){ 
							$(this).parent().parent().hide().parent().children('.reply_button_block').show(); 
						});
						
						//blur reply textarea
						/*
						$('textarea.text_area_box_reply').blur(function(){
							$(this).parent().hide().parent().children('.reply_button_block').show(); return true;
						});
						*/
						
						//Cancel comment
						$('button.cancel_comment').live('click', clearMainCommentBox);
						
						//Comment hover
						$('.comment').hover(function(){
							$(this).find('.reply_button_block').children('button').show();
						}, function(){
							$(this).find('.reply_button_block').children('button').hide();
						});
						
						//voting vote up
						$('span.vote_up').live('click', function(){
							<?php if(activeUserSession()){ ?>
							var comment_id = $(this).parent().attr('id').substr(14);	
							$.ajax({
								url : '<?=getBaseUrl()?>?action=comment_vote_up',
								type: 'post',
								data: 'comment_id=' + comment_id,
								success: function(vote_html){
									if(vote_html == 'voted'){
										alert('You have already voted.')	
									}else{
										if(vote_html != 'error' && vote_html != undefined){
											$('#comment_tools_' + comment_id).children('.vote_count').html(vote_html);
										}
									}
								}
							});							
							<?php }else{ ?>
							//when not connected - prompt connect
							$('a.connect').trigger('click');
							<?php } ?> 
						});
						
						//vote down
						$('span.vote_down').live('click', function(){
							<?php if(activeUserSession()){ ?>
							var comment_id = $(this).parent().attr('id').substr(14);	
							$.ajax({
								url : '<?=getBaseUrl()?>?action=comment_vote_down',
								type: 'post',
								data: 'comment_id=' + comment_id,
								success: function(vote_html){
									if(vote_html == 'voted'){
										alert('You have already voted.')	
									}else{
										if(vote_html != 'error' && vote_html != undefined){
											$('#comment_tools_' + comment_id).children('.vote_count').html(vote_html);
										}
									}
								}
							});								
							<?php }else{ ?>
							//when not connected - prompt connect
							$('a.connect').trigger('click');
							<?php } ?> 
						});
						
						//clicked post comment
						$('button.add_comment').live('click', function(){
							<?php if(activeUserSession()){ ?>
							var comment = $(this).parent().parent().children('textarea').val();
							$.ajax({
								url : '<?=getBaseUrl()?>?action=insert_comment',
								type: 'post',
								data: 'comment=' + comment,
								success: function(comment_html){
									if(comment_html.length > 0){
										$('.contents_target').prepend(comment_html);
										$('.total_comments_target').html($('.total_comments_target').html() - 0 + 1);
										clearMainCommentBox();
										columizeComments();
									}
								}
							});
							<?php }else{ ?>
							//when not connected - prompt connect
							$('a.connect').trigger('click');
							<?php } ?> 
						});
						
						//clicked post comment
						$('button.post_reply').live('click', function(){
							<?php if(activeUserSession()){ ?>
							var comment = $(this).parent().parent().children('textarea').val();
							var comment_parent_id = $(this).attr('id').substr(11);
							$.ajax({
								url : '<?=getBaseUrl()?>?action=insert_reply',
								type: 'post',
								data: 'comment=' + comment + '&comment_parent_id=' + comment_parent_id,
								success: function(comment_html){
									if(comment_html.length > 0){
										$('#reply_holder_target_' + comment_parent_id).prepend(comment_html).parent().children('.reply_text_and_buttons').children('textarea').val('').parent().hide().parent().children('.reply_button_block').show();
										columizeComments();
									}
								}
							});
							<?php }else{ ?>
							//when not connected - prompt connect
							$('a.connect').trigger('click');
							<?php } ?> 
						});
						
					});
				//-->
			</script>
			<div class="add_comments shadow_light">
				<table class="comments_header">
					<tbody>
						<tr>
							<td style="text-align: left;">All Comments (<span class="total_comments_target"><?=$this->total_comments?></span>)</td>
							<td style="text-align: right;">
								<?php if(!isset($_GET['view_all_comments']) and $this->total_comments > SHOW_COMMENTS_LIMIT){ ?>
									<a href="<?=addToGetString(array('view_all_comments'), array(1))?>" title="See all comments">see all</a>
								<?php } ?>
							</td>
						</tr>
					</tbody>
				</table>
				<textarea class="text_area_box" name="comments_text"></textarea>
				<div class="buttons_block">
					<button class="cancel_comment show_on_hover">Cancel</button>
					<button class="add_comment">Post</button>
				</div>
			</div><!-- .add_comments -->
			<div class="contents_target">
		<?php
	}
	
	/*************************************************************/
	/*********************** PRINT ADD CONTENT BLOCK *************/
	/*************************************************************/
	private function buildContentFooter(){
		?>
			</div><!-- end .contents_target -->
		<?php
		//if($this->total_comments > SHOW_COMMENTS_LIMIT and !isset($_GET['view_all_comments'])){
		?>
			<!--<table class="comments_footer">
				<tbody>
					<tr>
						<td style="text-align: left;">There are <?=$this->total_comments - SHOW_COMMENTS_LIMIT?> more comments</td>
						<td style="text-align: right;"><a href="<?=addToGetString(array('view_all_comments'), array(1))?>" title="See all comments">see all</a></td>
					</tr>
				</tbody>
			</table>-->
		<?php
		//}
		?>
		</div><!-- end .comments_block -->
		<?php
	}	
	
	/*************************************************************/
	/*********************** CREATE COMMENT **********************/
	/*************************************************************/
	private function createComment($comment_array, $class = null){
		if(
			isset($comment_array['dzpro_comment_text']) and
			isset($comment_array['dzpro_comment_date_added']) and
			isset($comment_array['dzpro_user_name'])
		){ 
			$return_string = '
			<div class="comment ' . $class . ' comment_element">
				<div class="comment_mid">
					<div class="comment_in">
						<table cellpadding="0" cellspacing="0">
							<tbody>
								<tr>
									<td>&nbsp;</td>
									<td colspan="2" class="added_by">
										by <strong>' . $comment_array['dzpro_user_name'] . '</strong> <span style="font-style: italic">' . convertToTimeAgo($comment_array['dzpro_comment_date_added']) . '</span> ago
									</td>
								</tr>
								<tr>
									<td class="comment_right">
										<div class="comment_tools">
											<div class="voting_block" id="comment_tools_' . (int)$comment_array['dzpro_comment_id'] . '">
												<span class="vote_up"><!-- block --></span>
												<span class="vote_count">' . self::buildCountString($comment_array['dzpro_comment_up'], $comment_array['dzpro_comment_down']) . '</span>
												<span class="vote_down"><!-- block --></span>
											</div><!-- .voting_block -->
										</div><!-- comment_tools -->
									</td><!-- .comment_right -->
									<td class="comment_middle">
										<p>' . $comment_array['dzpro_comment_text'] . '</p>
									</td><!-- .comment_middle -->
									<td class="comment_left">
										<a href="#profile" title="' . prepareTag($comment_array['dzpro_user_name']) . ' Profile" class="profile_pic">
											<img src="' . getProfilePicture($comment_array['dzpro_user_id']) . '" title="' . prepareTag($comment_array['dzpro_user_name']) . '" />';
			if(isset($comment_array['dzpro_user_affiliated']) and $comment_array['dzpro_user_affiliated'] == 1){
				$return_string .= '
											<div class="ribbon"><!-- ribbon --></div>';
			}
			$return_string .= '
										</a><!-- .profile_pic -->';
			if(isset($comment_array['dzpro_user_affiliated']) and $comment_array['dzpro_user_affiliated'] == 1){
				$return_string .= '
										<span class="subtag">affiliated</span>';
			}else{
				$return_string .= '
										<span class="subtag">guest</span>';
			}
			$return_string .= '
									</td><!-- .comment_left -->
								</tr>
								<tr>
									<td>&nbsp;</td>
									<td colspan="2">
										<div class="reply_button_block">
												<button class="spam show_on_hover">Report Spam</button>
												<!--<button class="reply">Reply</button>-->
										</div><!-- .reply_button_block -->
										<!--<div class="reply_text_and_buttons">
											<textarea class="text_area_box_reply" name="reply_comments_text"></textarea>
											<div class="comment_buttons">
												<button class="cancel_reply show_on_hover">Cancel</button>
												<button class="post_reply" id="comment_id_' . (int)$comment_array['dzpro_comment_id'] . '">Post</button>
											</div>--><!-- .comment_buttons -->
										</div><!-- .reply_text_and_buttons -->
									</td>
								</tr>
							</tbody>
						</table>
					</div><!-- .comment_in -->
				</div><!-- .comment_mid -->
			</div>
			<!--<div class="reply_holder" id="reply_holder_target_' . (int)$comment_array['dzpro_comment_id'] . '">-->';
			//if(false !== ($replies_string = self::getRepliesString((int)$comment_array['dzpro_comment_id']))){
			//	$return_string .= $replies_string; $replies_string = null;
			//}
			$return_string .= '
			<!--</div>--><!-- end .reply_holder -->
			';
			return $return_string;
		}
	}

	/*************************************************************/
	/*********************** CREATE REPLY ************************/
	/*************************************************************/
	private function createReply($comment_array){
		if(
			isset($comment_array['dzpro_comment_text']) and
			isset($comment_array['dzpro_comment_date_added']) and
			isset($comment_array['dzpro_user_name'])
		){
			$return_string = '
			<div class="reply comment_element">
				<table cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td>&nbsp;</td>
							<td colspan="2" class="added_by">
								by <strong>' . $comment_array['dzpro_user_name'] . '</strong> <span style="font-style: italic">' . convertToTimeAgo($comment_array['dzpro_comment_date_added']) . '</span> ago
							</td>
						</tr>
						<tr>
							<td class="comment_left">
								<a href="#profile" title="' . prepareTag($comment_array['dzpro_user_name']) . ' Profile" class="profile_pic">
									<img src="' . getProfilePicture($comment_array['dzpro_user_id']) . '" class="profile_pic" title="' . prepareTag($comment_array['dzpro_user_name']) . '" />
								</a><!-- .profile_pic -->
							</td>
							<td class="comment_middle">
								<p>' . $comment_array['dzpro_comment_text'] . '</p>
							</td>
							<td class="comment_right">
								<div class="comment_tools">
									<div class="voting_block" id="comment_tools_' . (int)$comment_array['dzpro_comment_id'] . '">
										<span class="vote_up"><!-- block --></span>
										<span class="vote_count">' . self::buildCountString($comment_array['dzpro_comment_up'], $comment_array['dzpro_comment_down']) . '</span>
										<span class="vote_down"><!-- block --></span>
									</div><!-- .voting_block -->
								</div><!-- comment_tools -->
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			';
			return $return_string;
		}
	}
	
	private function buildCountString($up = null, $down = null){
		$votes = $up - $down;
		switch(true){
			case($votes > 0): return '<span class="pos">' . (int)$votes . '</span>'; break;
			case($votes == 0): return '<span class="neu">' . (int)$votes . '</span>'; break;
			case($votes < 0): return '<span class="neg">' . (int)$votes . '</span>'; break;
		}
	}
	
}
?>