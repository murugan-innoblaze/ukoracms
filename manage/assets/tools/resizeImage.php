<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../');

//knock over the first domino
require RELATIVE_ASSETS_PATH . 'conf/general.settings.php';

//authenticate session
$Auth = new Auth($db);

$image = (isset($_POST['image']) and is_file(FRONTEND_DOCUMENT_ROOT . $_POST['image'])) ? FRONTEND_DOCUMENT_ROOT . $_POST['image'] : null;
$x1 = isset($_POST['x1']) ? $_POST['x1'] : null;
$y1 = isset($_POST['y1']) ? $_POST['y1'] : null;
$x2 = isset($_POST['x2']) ? $_POST['x2'] : null;
$y2 = isset($_POST['y2']) ? $_POST['y2'] : null;
$newwidth = isset($_POST['width']) ? $_POST['width'] : null; 
$newheight = isset($_POST['height']) ? $_POST['height'] : null;
$new_image_path = substr($image, 0, strlen($image) - 4) . '_cropped.jpg';

if(!empty($image) and ((int)$x2 - (int)$x1) != 0 and ((int)$y2 - (int)$y1) != 0){
	list($cur_width, $cur_height) = @getimagesize($image);
	$source = false;
	$image_type = @exif_imagetype($image);
	if($image_type == IMAGETYPE_JPEG){
		$source = @imagecreatefromjpeg($image);
	}
	if($image_type == IMAGETYPE_GIF){
		$source = @imagecreatefromgif($image);
	}
	if($image_type == IMAGETYPE_PNG){
		$source = @imagecreatefrompng($image);
	}
	if($source){
		$thumb = @imagecreatetruecolor($newwidth, $newheight);
		if(false !== @imagecopyresampled($thumb, $source, 0, 0, $x1, $y1, $newwidth, $newheight, $x2 - $x1, $y2 - $y1)){
			if(is_file($new_image_path)){ @unlink($new_image_path); }
			if(false !== @imagejpeg($thumb, $new_image_path, 100)){
				echo str_replace(FRONTEND_DOCUMENT_ROOT, '', $new_image_path);
			}else{
				echo 'Could not save file.';
			}
		}else{
			echo 'Could not resize.';
		}
		@imagedestroy($source);
		@imagedestroy($thumb);
	}else{
		echo 'Could not create image.';
	}
}else{
 	echo 'Invalid request.';
}

//close database
mysql_close($db);
?>