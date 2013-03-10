<?php

class Album extends Form {

	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
		
		//run form constructor
		parent::__construct($db, $table_name, $parameters, $sticky_fields);
		
		//if upload field are there we'll load the js and css etc
		$this->need_upload = true;
		
		//we need sortable stuff
		$this->need_sortable = true;
		
		//set the albums items table
		$this->album_items_table = 'dzpro_album_items';
		
		//load the row
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'requestItemRow' and isset($_POST['item_id']) and isset($_POST['album_id'])){ self::loadItemRow($_POST['item_id'], $_POST['album_id']); exit(0); }
		
		//reorder photos
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'reorderAlbumItemsSubmit' and isset($_POST['orderString_photos'])){ self::reorderItemRows(json_decode(stripslashes($_POST['orderString_photos']))); exit(0); }
		
		//remove album item
		if(isset($_GET['ajax']) and $_GET['ajax'] == 'removeAlbumItem' and isset($_POST['item_id'])){ echo self::deleteAlbumItem($_POST['item_id']); exit(0); }
		
	}

	/*************************************************************/
	/*********************** REMOVE ALBUM ITEM *******************/
	/*************************************************************/
	protected function deleteAlbumItem($item_id = null){
		@mysql_query(" DELETE FROM dzpro_album_items WHERE dzpro_album_item_id = " . (int)$item_id); if(mysql_affected_rows() > 0){ return 'deleted'; }
		return null;
	}

	/*************************************************************/
	/*********************** REORDER ALBUM ITEMS *****************/
	/*************************************************************/		
	protected function reorderItemRows($array){
		if(!empty($array)){ foreach($array as $order_int => $primary_value){ $sql = " UPDATE dzpro_album_items SET dzpro_album_item_orderfield = " . (int)$order_int . " WHERE dzpro_album_item_id = " . (int)$primary_value . " LIMIT 1 "; @mysql_query($sql, $this->db); } return true; }
		return false;
	}

	/*************************************************************/
	/*********************** PRINT ALBUM ITEM ********************/
	/*************************************************************/		
	protected function loadItemRow($item_id = null, $album_id = null){
		$album_id = !have($album_id) ? $this->primary_value : $album_id;
		$extra_sql = have($item_id) ? " AND dzpro_album_item_id = " . (int)$item_id : null;
		$result = @mysql_query(" SELECT * FROM dzpro_album_items WHERE dzpro_album_id = " . (int)$album_id . $extra_sql . " ORDER BY dzpro_album_item_orderfield ASC, dzpro_album_item_date_added DESC "); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ 
			?>
			<li id="list_album_item_record_<?=(int)$row['dzpro_album_item_id']?>" class="record_listing camera">
				<img src="http://<?=HOST_NAME . $row['dzpro_album_item_thumb_image']?>" alt="<?=prepareTag($row['dzpro_album_item_name'])?>" style="position: absolute; top: 5px; left: 240px; height: 47px; border-radius: 5px; border: 1px solid #333333;" />
				<a class="delete_icon" href="#delete" onclick="javascript:deleteAlbumItem(<?=(int)$row['dzpro_album_item_id']?>);return false;" title="Delete this picture"><!-- block --></a>
				<span class="date"><strong>Apr 1</strong> 8:57pm</span>
				<strong class="title" title="<?=prepareTag($row['dzpro_album_item_name'])?>"><?=prepareStringHtml(limitString($row['dzpro_album_item_name'], LISTING_NAME_STR_LENGTH))?></strong>

				<strong class="sub" title="<?=prepareTag($row['dzpro_album_item_description'])?>"><?=prepareStringHtml(limitString($row['dzpro_album_item_description'], LISTING_DESCRIPTION_STR_LENGTH))?></strong>
				<p><?=prepareStringHtml(limitString($row['dzpro_album_item_thumb_image'], LISTING_NAME_STR_LENGTH))?></p>
				<div class="sort_element sort"><!-- block - sorting handle --></div>
			</li>
			<?php		
		} mysql_free_result($result); }
	}

	/*************************************************************/
	/*********************** PRINT ALBUM UPLOAD UI ***************/
	/*************************************************************/	
	protected function printAlbumInterface(){
		$required_field_seperator_path = (isset($this->required_value) and !empty($this->required_value)) ? md5($this->required_value) . '/' : '';
		$album_data = array('album_key' => $this->primary_key, 'album_value' => $this->primary_value, $this->primary_key => $this->primary_value);
		?>
		<div class="form_area" method="post" style="margin-top: -25px;">
			<script type="text/javascript">
				<!--
					function deleteAlbumItem(item_id){
						if(confirm('Are you sure you want to delete this picture?')){
							$.ajax({
								url : '<?=$_SERVER['PHP_SELF']?>?ajax=removeAlbumItem',
								type : 'POST',
								data : 'item_id='+encodeURIComponent(item_id),
								success : function(response){
									if(response != undefined && response.length > 2){ $('#list_album_item_record_' + item_id).fadeOut(200); }
								}
							});
						}
					}
					function addedImageToAlbum(response){
						var item_id = response;	if(item_id != undefined && item_id.length > 0){ insertPhotoRow(item_id); }
					}
					function insertPhotoRow(item_id){
						$.ajax({
							url : '<?=$_SERVER['PHP_SELF']?>?ajax=requestItemRow',
							type : 'POST',
							data : 'item_id='+encodeURIComponent(item_id)+'&album_id='+encodeURIComponent(<?=(int)$this->primary_value?>),
							success : function(the_row){
								if(the_row != undefined && the_row.length > 20){ $('#form_listing_parent_photos').prepend(the_row); }
							}
						});
					}
					$().ready(function(){
					  	$('#image_upload_album_items').uploadify({
					    	'uploader' : '<?=ASSETS_PATH?>/upl/uploadify.swf',
					    	'script' : '<?=ASSETS_PATH?>/upl/uploadToAlbum.php',
					    	'cancelImg' : '<?=ASSETS_PATH?>/upl/cancel.png',
					    	'folder' : '<?=UPLOADS_PATH?>/<?=$required_field_seperator_path . $this->album_items_table?>/<?=date('Y-m-d-H-i-s')?>',
					    	'buttonImg' : '<?=ASSETS_PATH?>/img/manager/upload_image_button.png',
					    	'wmode' : 'transparent',
					    	'fileExt' : '*.jpg;*.gif;*.png',
							'fileDesc' : 'Image Files',  
							'multi' : true,
							'auto' : true,
							'queueID' : 'custom-album-queue',
							'scriptData' : <?=json_encode($album_data)?>,
							'onComplete' : function(event, ID, fileObj, response, data){ addedImageToAlbum(response); $('#files_left').text(data.fileCount); },
							'onAllComplete' : function(){ $('#album_upload_process').fadeOut(200); },
							'onOpen' : function(){ $('#files_left').text($('#image_upload_album_items').uploadifySettings('queueSize')); $('#album_upload_process').fadeIn(200); },
							'onProgress' : function(event,ID,fileObj,data){
								$('#upload_percentage').text(data.percentage);
								$('#upload_speed').text(data.speed);
							}
					  	});
					});				
				//-->
			</script>
			<div class="input_iframe">
				<div class="table_name">
					Album Images
				</div>
				<div class="bucket_top_nav">
					<div style="position: absolute; top: 4px; right: 0px; cursor: pointer;">
						<input type="file" id="image_upload_album_items" title="New Images" />
					</div>
					<div id="album_upload_process" style="position: absolute; top: 5px; right: 120px; height: 22px; width: 200px; padding-right: 34px; text-align: right; background: url('/assets/img/manager/ajax-loader-blue-small.gif') center right no-repeat transparent; color: white; font-weight: normal; font-size: 11px; padding-top: 10px; display: none;">uploading <span id="upload_percentage">0</span>% (<span id="upload_speed">0</span>Kbs) <span id="files_left">0</span> files left</div>
				</div><!-- end bucket_top_nav -->		
				<div id="custom-album-queue" style="background-color: white; max-height: 120px; overflow: hidden; border-bottom: 1px solid #e0e0e0; display: none;"></div>
				<div id="album-items-ui-target" style="background-color: white; min-height: 300px;">
					<script type="text/javascript">
						<!-- 
							$().ready(function(){
								$('#form_listing_parent_photos').sortable({
									axis : 'y',
									handle : '.sort',
									containment : '#form_listing_parent_photos',
									placeholder : 'ui-state-highlight',
									update : function(){
										var orderArray_photos = [];
										var orderCounter_photos = 0;
										$('#form_listing_parent_photos .record_listing').each(function(){
											orderArray_photos[orderCounter_photos] = $(this).attr('id').substr(23);
											orderCounter_photos += 1;
										});
										var submitOrderString_photos = JSON.stringify(orderArray_photos);
										if(orderCounter_photos > 1){
											$.ajax({
												url : '<?=$_SERVER['PHP_SELF']?>?ajax=reorderAlbumItemsSubmit',
												type : 'post',
												data : 'orderString_photos=' + submitOrderString_photos,
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
					<ul class="listing_parent" id="form_listing_parent_photos">
						<?php self::loadItemRow(); ?>
					</ul><!-- end listing_parent -->
				</div><!-- end .the_content_box -->
			</div><!-- end .input_row -->
			<script type="text/javascript">
				<!-- 
				//-->
			</script>
		</div>
		<?php
	}

	/*************************************************************/
	/*********************** BUILD ALBUM BLOCK *******************/
	/*************************************************************/
	public function buildAlbumBlock(){
	
		//lets build the database editor
		parent::buildFormBlock();
		
		//if we have a valid album - lets show the page ui stuff	
		if(have($this->primary_value)){ self::printAlbumInterface(); } 
	
	}
	
}

?>