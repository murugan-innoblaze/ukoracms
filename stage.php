<?php

ini_set('display_errors', 1);

//where are we
define('RELATIVE_ASSETS_PATH', 'assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//assure session
assureSession();

$field = 4398; $form_id = 123423; $FormConstructor = new FormConstructor($db, array(
	$form_id => array(	
		'dzpro_form_name' => 'Request UKORA CMS download',
		'dzpro_form_description' => '',
		'dzpro_form_submit_string' => '',
		'dzpro_form_action' => 'send email',
		'dzpro_form_success_email_html' => 'Hi [[[name]]]! This worked I guess!',
		'dzpro_form_success_html' => '',
		'fields' => array(
			$field++ => array(
				'dzpro_form_field_type' => 'name',
				'dzpro_form_field_label' => 'Your name',
				'dzpro_form_field_message' => 'Please enter your name',
				'dzpro_form_field_place_holder' => 'First Last',
				'dzpro_form_field_multiple' => '0',
				'dzpro_form_field_payment_options' => '',
				'dzpro_form_field_amount' => '',
				'dzpro_form_field_active' => '1',
				'dzpro_form_field_required' => '1'
			),
			$field++ => array(
				'dzpro_form_field_type' => 'email',
				'dzpro_form_field_label' => 'Your email',
				'dzpro_form_field_message' => 'Please a valid email',
				'dzpro_form_field_place_holder' => 'your@email.com',
				'dzpro_form_field_multiple' => '0',
				'dzpro_form_field_payment_options' => '',
				'dzpro_form_field_amount' => '',
				'dzpro_form_field_active' => '1',
				'dzpro_form_field_required' => '1'
			),
			$field++ => array(
				'dzpro_form_field_type' => 'phone',
				'dzpro_form_field_label' => 'Your phone',
				'dzpro_form_field_message' => 'Please enter your phone number',
				'dzpro_form_field_place_holder' => '123.123.1234',
				'dzpro_form_field_multiple' => '0',
				'dzpro_form_field_payment_options' => '',
				'dzpro_form_field_amount' => '',
				'dzpro_form_field_active' => '1',
				'dzpro_form_field_required' => '1'
			)
		)
	)
));


$FormConstructor->buildForm();

die('stopped');












die();

function Zip($source, $destination){
    if(!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {  
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file) {
            $file = str_replace('\\', '/', realpath($file));

            if (is_dir($file) === true) {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true) {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    else if (is_file($source) === true) {
        $zip->addFromString(basename($source), file_get_contents($source));
    }
	
	$status = $zip->getStatusString();
	return $zip->close();
}

$var = Zip(DOCUMENT_ROOT, './uploads/compressed-site.zip');

var_dump($var);

?>
