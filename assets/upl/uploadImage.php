<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../');

//knock over the first domino
require RELATIVE_ASSETS_PATH . 'conf/general.settings.php';

//If uploaded file
if(!empty($_FILES)){
	$fileTypes = str_replace('*.','',$_REQUEST['fileext']);
	$fileTypes = str_replace(';','|',$fileTypes);
	$typesArray = split('\|',$fileTypes);
	$fileParts = pathinfo($_FILES['Filedata']['name']);

	$tempFile = $_FILES['Filedata']['tmp_name'];
	$targetPath = DOCUMENT_ROOT . $_REQUEST['folder'] . '/';
	$targetFile =  str_replace('//','/',$targetPath) . cleanString($_FILES['Filedata']['name']) . '.' . strtolower($fileParts['extension']);
	
	if(in_array(strtolower($fileParts['extension']), $typesArray)){
		list($width, $height) = @getimagesize($tempFile);
		if($height > MAX_IMAGE_HEIGHT){
			$source = false;
			$image_type = @exif_imagetype($tempFile);
			if($image_type == IMAGETYPE_JPEG){
				$source = @imagecreatefromjpeg($tempFile);
			}
			if($image_type == IMAGETYPE_GIF){
				$source = @imagecreatefromgif($tempFile);
			}
			if($image_type == IMAGETYPE_PNG){
				$source = @imagecreatefrompng($tempFile);
			}
			if($source){
				$newwidth = number_format((MAX_IMAGE_HEIGHT / $height) * $width, 0);
				$newheight = number_format(MAX_IMAGE_HEIGHT, 0);
				$thumb = @imagecreatetruecolor($newwidth, $newheight);
				@imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
				if(!is_dir($targetPath)){
					mkdir(str_replace('//', '/', $targetPath), 0755, true);
				}
				if(false !== @imagejpeg($thumb, $targetFile)){
					echo str_replace(DOCUMENT_ROOT, '', $targetFile);
				}else{
					echo 'Could not save file.';
				}
				@imagedestroy($source);
				@imagedestroy($thumb);
			}else{
				echo 'Could not create image.';
			}
		}else{
			if(!is_dir($targetPath)){
				mkdir(str_replace('//', '/', $targetPath), 0755, true);
			}
			move_uploaded_file($tempFile, $targetFile);
			echo str_replace(DOCUMENT_ROOT, '', $targetFile);		
		}
	}else{
	 	echo 'Invalid file type.';
	}
}
?>