<?php
session_start();
header("Content-type: image/png");
$phrases = array(
	"Baze",
	"Tone",
	"Daki",
	"Scop",
	"Zazo",
	"Lkjs",
	"Uilk",
	"Bear",
	"Opeo",
	"Zoek",
	"Uiol",
	"Ijke",
	"Basl",
	"Eria",
	"Oiel",
	"Ogal",
	"Bedk",
	"Yari",
	"Oter",
	"Iols",
	"Ckld",
	"Pooi",
	"Pout",
	"Uiuj",
	"Rils",
	"Qjsj",
	"Mnmc",
	"Ioip",
	"Opoe",
	"Oppe",
	"Qhjd",
	"Iuem",
	"Ukuo",
	"Oili",
	"Seon",
	"Isle",
	"Ophi"
);
$_SESSION['captcha'] = $phrases[rand(0,count($phrases)-1)];
$im = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'] . "/assets/captcha/captchabg.png");
$color = imagecolorallocate($im, 10, 10, 10);
imagettftext($im, 34, 0, 34, 40, $color, $_SERVER['DOCUMENT_ROOT'] . "/assets/captcha/PulseSansSerif.ttf", $_SESSION['captcha']);
imagepng($im);
imagedestroy($im);
?>