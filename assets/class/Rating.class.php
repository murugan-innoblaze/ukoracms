<?php

class Rating extends Page {

	/*******************************************/
	/*********** PAGE CONSTRUCTOR **************/
	/*******************************************/
	function __construct($db, $rating_key = null, $star_count = 5){

		//need sessions
		assureSession();

		//construct parent
		parent::__construct($db);
		
		//star count
		$this->start_count = $star_count;
		
		//set the rating key
		$this->rating_key = $rating_key;
		
		//set or select rating
		$this->rating = self::setOrSelectRating();
	
		//get the rating block
		if(isset($_POST['rating_request']) and $_POST['rating_request'] == 'getRating' and isset($_POST['rating']) and $_POST['rating'] == md5($this->rating_key)){ self::buildRatingBlock(); exit(0); }
		
		//if set rating
		if(isset($_GET['setRating']) and $_GET['setRating'] == md5($this->rating_key) and isset($_GET['value']) and is_numeric($_GET['value'])){ echo self::setRating($_GET['value']); }
		
	}

	/*******************************************/
	/*********** SET RATING ********************/
	/*******************************************/	
	protected function setRating($value){
		if(!have($value)){ return false; } @mysql_query(" UPDATE dzpro_rating_log SET dzpro_rating_log_value = '" . mysql_real_escape_string($value) . "' WHERE dzpro_visitor_id = '" . mysql_real_escape_string(getVisitorId()) . "' AND dzpro_rating_id = '" . mysql_real_escape_string($this->rating['dzpro_rating_id']) . "' ") or handleError(1, mysql_error()); if(mysql_query_got_rows(" SELECT * FROM dzpro_rating_log WHERE dzpro_visitor_id = '" . mysql_real_escape_string(getVisitorId()) . "' AND dzpro_rating_id = '" . mysql_real_escape_string($this->rating['dzpro_rating_id']) . "' ")){ return 'already voted';	}else{ @mysql_query(" UPDATE dzpro_ratings SET dzpro_rating_value = ( ( '" . mysql_real_escape_string($value) . "' + ( dzpro_rating_value * dzpro_rating_count ) ) / ( dzpro_rating_count + 1 ) ), dzpro_rating_count = dzpro_rating_count + 1 WHERE dzpro_rating_id = '" . mysql_real_escape_string($this->rating['dzpro_rating_id']) . "' ") or handleError(1, mysql_error()); @mysql_query(" INSERT INTO dzpro_rating_log ( dzpro_rating_id, dzpro_visitor_id, dzpro_rating_log_value, dzpro_rating_log_date_added ) VALUES ( '" . mysql_real_escape_string($this->rating['dzpro_rating_id']) . "', '" . mysql_real_escape_string(getVisitorId()) . "', '" . mysql_real_escape_string($value) . "', NOW() ) "); if(mysql_insert_id()){ return 'voted'; }else{ handleError(1, 'could not insert dzpro rating into log error:' . mysql_error()); return 'error'; } }
	}

	/*******************************************/
	/*********** SET OR SELECT RATING **********/
	/*******************************************/
	protected function setOrSelectRating(){
		if(!have($this->rating_key)){ return false; }
		$rating = mysql_query_flat(" SELECT * FROM dzpro_ratings WHERE dzpro_rating_key = '" . mysql_real_escape_string($this->rating_key) . "' LIMIT 1 "); if(have($rating[0])){ $this->rating = $rating[0]; return $this->rating; }else{ @mysql_query(" INSERT INTO dzpro_ratings ( dzpro_rating_key, dzpro_rating_date_added ) VALUES ( '" . mysql_real_escape_string($this->rating_key) . "', NOW() ) ") or handleError(1, mysql_error()); $rating = mysql_query_flat(" SELECT * FROM dzpro_ratings WHERE dzpro_rating_key = '" . mysql_real_escape_string($this->rating_key) . "' LIMIT 1 "); if(have($rating)){ $this->rating = $rating[0]; return $this->rating; } } handleError(2, 'could not get or set rating array for key: ' . $this->rating_key); return false;
	}

	/*******************************************/
	/*********** BUILD RATING BLOCK ************/
	/*******************************************/
	public function buildRatingBlock(){
		?>
			<script type="text/javascript">
				<!--
					function setRating<?=md5($this->rating_key)?>(value){ $.ajax({ url : '<?=$_SERVER['REQUEST_URI']?>', type : 'get', data : 'setRating=<?=md5($this->rating_key)?>&value=' + encodeURIComponent(value), success : function(mssg){ if(mssg != undefined && mssg.substr(0, 13) == 'already voted'){ alert('You already voted…'); } loadRatingBlock<?=md5($this->rating_key)?>(); } }); } $().ready(function(){ $('.rating').hover(function(){ $('.rating').removeClass('hover').addClass('forceoff'); var this_rating_id = $(this).attr('id').substr(7); for(i = this_rating_id; i > 0; i--){ $('#rating_' + i).addClass('hover'); } }, function(){ $('.rating').removeClass('hover').removeClass('forceoff'); }); });
				//-->
			</script>
			<table cellpadding="0" cellspacing="0"><tbody><tr>
				<?php for($i = 1; $i <= $this->start_count; $i++){ switch(true){ case(($this->rating['dzpro_rating_value'] / 100) * $this->start_count > ($i - 0.25)): $classes = 'full'; break; case(($this->rating['dzpro_rating_value'] / 100) * $this->start_count > ($i- 0.75)): $classes = 'half'; break; default: $classes = null; break; } ?>
				<td><a href="<?=addToGetString(array('setRating', 'value'), array(md5($this->rating_key), ($i * (100/$this->start_count))))?>" class="rating <?=$classes?>" id="rating_<?=(int)$i?>" onclick="setRating<?=md5($this->rating_key)?>(<?=($i * (100/$this->start_count))?>);return false;" rel="nofollow"><!-- rating tile --></a></td>
				<?php } ?>
			</tr><tr><td colspan="<?=(int)$this->start_count?>" style="padding: 3px; text-align: center; font-weight: bold;"><?=(int)$this->rating['dzpro_rating_count']?> vote<?=($this->rating['dzpro_rating_count'] != 1) ? 's' : null?></td></tr></tbody></table>		
		<?php
	}

	/*******************************************/
	/*********** PRINT RATING BLOCK ************/
	/*******************************************/	
	public function printRatingBlock(){
		if(!have($this->rating_key)){ return false; }
		?>
			<div class="rating_block">
				<script type="text/javascript">
					function loadRatingBlock<?=md5($this->rating_key)?>(){ $.ajax({ url : '<?=$_SERVER['REQUEST_URI']?>', type : 'post', data : 'rating=' + encodeURIComponent('<?=md5($this->rating_key)?>') + '&rating_request=getRating', success : function(html){ $('#rating_target_<?=md5($this->rating_key)?>').html(html); } }); } $().ready(loadRatingBlock<?=md5($this->rating_key)?>);
				</script>
				<div id="rating_target_<?=md5($this->rating_key)?>"><?=self::buildRatingBlock()?></div><!-- end rating_target_<?=md5($this->rating_key)?> -->
			</div><!-- end .rating_block -->
		<?php
	}
	
}

?>