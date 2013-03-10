<?php

ini_set('display_errors', 1);

//where are we
define('RELATIVE_ASSETS_PATH', '..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//the text
$text = isset($_GET['text']) ? $_GET['text'] : null;

//the size
$size = isset($_GET['size']) ? $_GET['size'] : 40;

//color
$color = isset($_GET['color']) ? $_GET['color'] : '#2e2f7a';

//pick font
$pick_font = isset($_GET['font']) ? $_GET['font'] : null;

//choose font
switch($pick_font){
	case '1': $font = RELATIVE_ASSETS_PATH . '/title/fonts/AndBasR.ttf'; break;
	case '2': $font = RELATIVE_ASSETS_PATH . '/title/fonts/Impact.ttf'; break;
	case '3': $font = RELATIVE_ASSETS_PATH . '/title/fonts/Helvetica-Condensed-Black-Se.ttf'; break;
	case '4': $font = RELATIVE_ASSETS_PATH . '/title/fonts/DS-DIGIB.TTF'; break;
	case '5': $font = RELATIVE_ASSETS_PATH . '/title/fonts/PermanentMarker.ttf'; break;
	case '6': $font = RELATIVE_ASSETS_PATH . '/title/fonts/TOONISH.ttf'; break;
	case '7': $font = RELATIVE_ASSETS_PATH . '/title/fonts/Written_during_an_exam.ttf'; break;
	case '8': $font = RELATIVE_ASSETS_PATH . '/title/fonts/ThrowMyHandsUpintheAir.ttf'; break;
	case '9': $font = RELATIVE_ASSETS_PATH . '/title/fonts/CapitalsRegular.ttf'; break;
	default: $font = RELATIVE_ASSETS_PATH . '/title/fonts/Century_Gothic_Bold.ttf'; break;
}

//build request string
$request_string = $text . $size . $color . $pick_font; //should be unique per request

//let's try to retrieve this from cache
if(is_file(DOCUMENT_ROOT . '/assets/title/cache/' . md5($request_string) . '.png')){ header('Location: /assets/title/cache/' . md5($request_string) . '.png'); exit(0); }

//over compensate dimensions!
$cropPadding = 3;
$fontRange = 'xgypqXi()';
$bounds = imagettfbbox($size,0,$font,$fontRange);
$height = abs($bounds[1]-$bounds[5])+$cropPadding;
$y = abs($bounds[7]) - 1 + $cropPadding;
$bounds = imagettfbbox($size,0,$font,$text);
$width = abs($bounds[0]-$bounds[2]) + $cropPadding + $cropPadding;
$x = ($bounds[0] * -1) + $cropPadding;

//create transparent image
$image = imagecreatetruecolor($width,$height);
imagesavealpha($image, true);
imagealphablending($image, false);
$background = imagecolorallocatealpha($image, 255, 255, 255, 127);
imagefilledrectangle($image, 0, 0, $width, $height, $background);
imagealphablending($image, true);

//make color
$color = str_split(ltrim($color,'#'),2);
$color = imagecolorallocatealpha($image,hexdec($color[0]),hexdec($color[1]),hexdec($color[2]),0);

//render text to image
imagettftext($image,$size,0,$x,$y,$color,$font,$text.'     '.$fontRange);

//calculate crop
$trim_bottom = 0;
$trim_left = 0;
$trim_right = 0;

//bottom
for($trim_y = $height-1; $trim_y >= 0; $trim_y--){ for($trim_x = 0; $trim_x < $width; $trim_x++){ $alpha = (imagecolorat($image, $trim_x, $trim_y) >> 24) & 0xFF; if($alpha != 127) { break 2; } } $trim_bottom++; }
   
//left
for($trim_x = 0; $trim_x < $width; $trim_x++){ for($trim_y = 0; $trim_y < $height; $trim_y++){ $alpha = (imagecolorat($image, $trim_x, $trim_y) >> 24) & 0xFF; if($alpha != 127) { break 2; } } $trim_left++; }

//right
for($trim_x = $width-1; $trim_x >= 0; $trim_x--){ for($trim_y = 0; $trim_y < $height; $trim_y++){ $alpha = (imagecolorat($image, $trim_x, $trim_y) >> 24) & 0xFF; if($alpha != 127) { break 2; } } $trim_right++; }


	//create new image
	$newWidth = $width - $trim_right;
	
	$newHeight = $height - $trim_bottom;
	
	$newImage = imagecreatetruecolor($newWidth, $newHeight);
	
	imagesavealpha($newImage, true);
	
	imagealphablending($newImage, false);
	
	$background = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
	
	imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $background);
	
	imagealphablending($newImage, true);
	
	$imageWidth = imagesx($newImage);
	
	$imageHeight = imagesy($newImage);
	
	imagecopyresampled($newImage,$image,-$trim_left,0,0,0,$imageWidth,$imageHeight,$imageWidth,$imageHeight);
   
    //swap the new image
    imagedestroy($image);
    $image = $newImage;


//set header
header('Content-type: image/png');

//do something with image
imagepng($image, DOCUMENT_ROOT . '/assets/title/cache/' . md5($request_string) . '.png');

//output the image
imagepng($image);

//free image
imagedestroy($image);
   
?>
