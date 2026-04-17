<?php

date_default_timezone_set('Asia/Kolkata');
$conn = mysqli_connect("localhost", "root", "", "cafe");

if(!$conn){
    die("Database connection failed!");
}

// =============================================
//  BASE URL
//  Local XAMPP pe: '/cafe_management'
//  Hostinger pe:   ''  (public_html seedha)
// =============================================
if(!defined('BASE_URL')){
    define('BASE_URL', '/cafe_management');
}

// =============================================
//  FEATURE FLAGS
//  true  = feature ON
//  false = feature OFF
// =============================================
if(!defined('FEATURE_ONLINE_PAYMENT'))    define('FEATURE_ONLINE_PAYMENT',    true);
if(!defined('FEATURE_TABLE_BOOKING'))     define('FEATURE_TABLE_BOOKING',     true);
if(!defined('FEATURE_ORDER_TRACKING'))    define('FEATURE_ORDER_TRACKING',    true);
if(!defined('FEATURE_FEEDBACK'))          define('FEATURE_FEEDBACK',          true);
if(!defined('FEATURE_EMAIL_BILL'))        define('FEATURE_EMAIL_BILL',        true);
if(!defined('FEATURE_WHATSAPP_ORDER'))    define('FEATURE_WHATSAPP_ORDER',    true);
if(!defined('FEATURE_LOYALTY_POINTS'))    define('FEATURE_LOYALTY_POINTS',    false);
if(!defined('FEATURE_OFFERS_PAGE'))       define('FEATURE_OFFERS_PAGE',       false);
if(!defined('FEATURE_REVIEWS'))           define('FEATURE_REVIEWS',           false);
if(!defined('FEATURE_WALLET'))            define('FEATURE_WALLET',            false);
if(!defined('FEATURE_REFERRAL'))          define('FEATURE_REFERRAL',          false);

// Use: if(feature('LOYALTY_POINTS')) { ... }
if(!function_exists('feature')){
    function feature(string $name): bool {
        $const = 'FEATURE_' . strtoupper($name);
        return defined($const) && constant($const) === true;
    }
}
?>