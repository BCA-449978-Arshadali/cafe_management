<?php

session_start();
require_once '../config.php';
require_once 'includes/otp-functions.php';

if (!isset($_SESSION['verify_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['verify_email'];

/* Delete old OTP */
deleteOldOTP($email, $conn);

/* Generate new OTP */
$otp = generateOTP();

/* Save new OTP */
saveOTP($email, $otp, $conn);

/* Send OTP */
sendOTP($email, $otp);

echo "<script>alert('New OTP Sent'); window.location='verify-otp.php';</script>";

?>