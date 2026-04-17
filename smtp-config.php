<?php

// Gmail SMTP Settings

define('SMTP_HOST',       'smtp.gmail.com');
define('SMTP_PORT',       587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME'));
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD'));     // 
define('SMTP_FROM_EMAIL', 'dropperscafe.auth@gmail.com');
define('SMTP_FROM_NAME',  'Droppers Cafe');

?>
