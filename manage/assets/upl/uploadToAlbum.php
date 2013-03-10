<?php

//allow more memory 
ini_set('memory_limit', -1);

//where are we
define('RELATIVE_ASSETS_PATH', '../');

//knock over the first domino
require RELATIVE_ASSETS_PATH . 'conf/general.settings.php';

//get the album id
$the_album_id = (have($_POST['album_value']) and is_numeric($_POST['album_value'])) ? (int)$_POST['album_value'] : null;

//get album item fields
function getAlbumItemFields(){
	$image_fields = array(); $result = @mysql_query(" SHOW FULL COLUMNS FROM dzpro_album_items "); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ if(substr($row['Field'], -6) == '_image' and substr($row['Type'], 0, 7) == 'varchar'){ $image_fields[$row['Field']]['Field'] = $row['Field']; $image_fields[$row['Field']]['Type'] = $row['Type']; $comment_pieces = array(); if($comment_pieces = explode('|||', $row['Comment'])){ if(have($comment_pieces[0])){ $image_fields[$row['Field']]['name'] = $comment_pieces[0]; } if(have($comment_pieces[1])){ $image_fields[$row['Field']]['required'] = $comment_pieces[1]; } if(have($comment_pieces[2])){ $image_fields[$row['Field']]['size'] = $comment_pieces[2]; $size_pieces = array(); $size_pieces = explode('x', $image_fields[$row['Field']]['size']); if(have($size_pieces) and sizeof($size_pieces) == 2){ $image_fields[$row['Field']]['width'] = $size_pieces[0]; $image_fields[$row['Field']]['height'] = $size_pieces[1]; } } } } } } return $image_fields;
}

//image copy merge retain transparency
function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct, $trans = NULL){
	$dst_w = imagesx($dst_im); $dst_h = imagesy($dst_im); $src_x = max($src_x, 0); $src_y = max($src_y, 0); $dst_x = max($dst_x, 0); $dst_y = max($dst_y, 0); if($dst_x + $src_w > $dst_w) $src_w = $dst_w - $dst_x; if($dst_y + $src_h > $dst_h) $src_h = $dst_h - $dst_y; for($x_offset = 0; $x_offset < $src_w; $x_offset++) for($y_offset = 0; $y_offset < $src_h; $y_offset++){ $srccolor = imagecolorsforindex($src_im, imagecolorat($src_im, $src_x + $x_offset, $src_y + $y_offset)); $dstcolor = imagecolorsforindex($dst_im, imagecolorat($dst_im, $dst_x + $x_offset, $dst_y + $y_offset)); if(is_null($trans) || ($srccolor !== $trans)){ $src_a = $srccolor['alpha'] * $pct / 100; $src_a = 127 - $src_a; $dst_a = 127 - $dstcolor['alpha']; $dst_r = ($srccolor['red'] * $src_a + $dstcolor['red'] * $dst_a * (127 - $src_a) / 127) / 127; $dst_g = ($srccolor['green'] * $src_a + $dstcolor['green'] * $dst_a * (127 - $src_a) / 127) / 127; $dst_b = ($srccolor['blue'] * $src_a + $dstcolor['blue'] * $dst_a * (127 - $src_a) / 127) / 127; $dst_a = 127 - ($src_a + $dst_a * (127 - $src_a) / 127); $color = imagecolorallocatealpha($dst_im, $dst_r, $dst_g, $dst_b, $dst_a); if(!imagesetpixel($dst_im, $dst_x + $x_offset, $dst_y + $y_offset, $color)) return false; imagecolordeallocate($dst_im, $color); } } return true;
}

//If uploaded file
if(!empty($_FILES)){

	//get file information
	$fileTypes = str_replace('*.','',$_REQUEST['fileext']);
	$fileTypes = str_replace(';','|',$fileTypes);
	$typesArray = split('\|',$fileTypes);
	$fileParts = pathinfo($_FILES['Filedata']['name']);

	//temp tile and target path + target file
	$tempFile = $_FILES['Filedata']['tmp_name'];
	$targetPath = FRONTEND_DOCUMENT_ROOT . $_REQUEST['folder'] . '/';

	//build image handler
	$source = false;
	$image_type = @exif_imagetype($tempFile);
	if($image_type == IMAGETYPE_JPEG){ $source = @imagecreatefromjpeg($tempFile); }
	if($image_type == IMAGETYPE_GIF){ $source = @imagecreatefromgif($tempFile); }
	if($image_type == IMAGETYPE_PNG){ $source = @imagecreatefrompng($tempFile); }
	if($source){

		//get the fields
		$image_fields = getAlbumItemFields();
		
		//check for fields
		if(have($image_fields)){ 
	
			//lets get the original height and width
			list($original_width, $original_height) = @getimagesize($tempFile);
			
			//lets get the aspect ratio
			$original_aspect_ratio = $original_width / $original_height;
	
			//key values pairs
			$key_value_pairs = array();
	
			//resize for all fields
			foreach($image_fields as $ikey => $ifield){

				//target file name
				$targetFile = cleanString($ifield['Field'] . $_FILES['Filedata']['name']) . '.' . strtolower($fileParts['extension']);
			
				//create the holder
				$this_image = @imagecreatetruecolor($ifield['width'], $ifield['height']);
				
				//this image aspect ratio
				$this_aspect_ratio = $ifield['width'] / $ifield['height'];
				
				//build and crop
				if($this_aspect_ratio == $original_aspect_ratio){
					@imagecopyresampled($this_image, $source, 0, 0, 0, 0, $ifield['width'], $ifield['height'], $original_width, $original_height);
				}elseif($this_aspect_ratio > $original_aspect_ratio){ //target picture is taller
					@imagecopyresampled($this_image, $source, 0, 0, 0, ($original_height - ($original_width / $this_aspect_ratio)) / 2, $ifield['width'], $ifield['height'], $original_width, $original_width / $this_aspect_ratio);
				}else{ //target picture is wider
					@imagecopyresampled($this_image, $source, 0, 0, ($original_width - ($original_height * $this_aspect_ratio)) / 2, 0, $ifield['width'], $ifield['height'], $original_height * $this_aspect_ratio, $original_height);
				}
				
				//add the watermark
				if(MINIMUM_IMAGE_HEIGHT_FOR_WATERMARK <= $ifield['height'] and MINIMUM_IMAGE_WIDTH_FOR_WATERMARK <= $ifield['width']){ 
					$watermark = imagecreatefrompng('watermark.png');
					$watermark_width = imagesx($watermark);
					$watermark_height = imagesy($watermark);
					imagecopymerge_alpha($this_image, $watermark, $ifield['width'] - $watermark_width - WATERMARK_IMAGE_PADDING, $ifield['height'] - $watermark_height - WATERMARK_IMAGE_PADDING, 0, 0, $watermark_width, $watermark_height, 100);
				}
				
				//make the directory if needed
				if(!is_dir($targetPath)){ mkdir(str_replace('//', '/', $targetPath), 0755, true); }
				
				//save the field
				if(false !== @imagejpeg($this_image, $targetPath . $targetFile)){ $image_fields[$ikey]['path'] = $targetPath . $targetFile; $key_value_pairs[$ifield['Field']] = $_REQUEST['folder'] . '/' . $targetFile; }
			
			}
			
			if(have($key_value_pairs)){
				$sql = " INSERT INTO dzpro_album_items ( dzpro_album_id, dzpro_album_item_name, dzpro_album_item_date_added, "; foreach($key_value_pairs as $field_name => $field_value){ $sql .= mysql_real_escape_string($field_name) . ","; } $sql = substr($sql, 0, -1);
				$sql .= " ) VALUES ( " . (int)$the_album_id . ", '" . mysql_real_escape_string($_FILES['Filedata']['name']) . "', NOW(), ";
				foreach($key_value_pairs as $field_name => $field_value){ $sql .= "'" . mysql_real_escape_string($field_value) . "',"; } $sql = substr($sql, 0, -1);
				$sql .= " ) ";
				@mysql_query($sql);
				if(mysql_insert_id()){ echo mysql_insert_id(); mysql_close($db); exit(0); }
			}
		
		}
		
	}

}

//something went wrong
echo 'false';

//close database
mysql_close($db);
?>