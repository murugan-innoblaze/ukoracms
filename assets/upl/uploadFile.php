<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//a file is submitted let's check it out and upload if needed
if(!empty($_FILES)){
	$fileTypes  = str_replace('*.','',$_REQUEST['fileext']);
	$fileTypes  = str_replace(';','|',$fileTypes);
	$typesArray = split('\|',$fileTypes);
	$fileParts  = pathinfo($_FILES['Filedata']['name']);

	$tempFile = $_FILES['Filedata']['tmp_name'];
	$targetPath = DOCUMENT_ROOT . $_REQUEST['folder'] . '/';
	$targetFile =  str_replace('//','/',$targetPath) . cleanString($_FILES['Filedata']['name']) . '.' . $fileParts['extension'];
	
	if(in_array($fileParts['extension'],$typesArray)) {
		if(!is_dir($targetPath)){
			mkdir(str_replace('//','/',$targetPath), 0755, true);
		}		
		move_uploaded_file($tempFile,$targetFile);
		echo str_replace(DOCUMENT_ROOT,'',$targetFile);
	}else{
	 	echo 'Invalid file type.';
	}
}
?>