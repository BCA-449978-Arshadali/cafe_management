<?php
session_start();
include "../config.php";
require "../customer/includes/mailer.php";

if (!isset($_SESSION['admin_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['admin_email'];

$otp = rand(100000,999999);
$expires = date("Y-m-d H:i:s", strtotime("+5 minutes"));

$stmt = $conn->prepare("INSERT INTO admin_otp (email, otp, expire_at) VALUES (?, ?, ?)");
$stmt->bind_param("sss",$email,$otp,$expires);
$stmt->execute();

sendMail($email, "Admin Login OTP", "Your new OTP is: $otp");

header("Location: verify-otp.php?resent=1");
exit();
?>