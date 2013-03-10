<?php
define('AMAXON_PIPELINE', 'https://authorize.payments.amazon.com/pba/paypipeline');
//define('AMAXON_PIPELINE', 'https://authorize.payments-sandbox.amazon.com/pba/paypipeline');
define('AMAZON_FPS_PROD_ENDPOINT', 'https://fps.amazonaws.com/');
//define('AMAZON_FPS_PROD_ENDPOINT', 'https://fps.sandbox.amazonaws.com/');
define('AMAZON_AWSACCESSID', '');
define('AMAZON_AWSSECRETKEY', '');
define('AMAXON_SIGNATURE_METHOD', 'HmacSHA256'); // Valid values  are  HmacSHA256 and HmacSHA1.
define('AMAXON_CERTIFICATE_LOCATION', '/assets/certs/amazon-bundle.crt');
?>