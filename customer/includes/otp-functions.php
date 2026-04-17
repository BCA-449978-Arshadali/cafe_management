<?php
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/../../config.php';

function generateOTP()
{
    return rand(100000, 999999);
}

function saveOTP($email, $otp, $conn)
{
    deleteOldOTP($email, $conn);
    $expire = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    $stmt = $conn->prepare("INSERT INTO email_otp (email, otp, expire_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $otp, $expire);
    $stmt->execute();
}

function sendOTP($email, $otp)
{
    $subject = "Your OTP - Droppers Cafe";
    $body = "
        <h2>Verify Your Account</h2>
        <p>Your OTP is:</p>
        <h1 style='color:#ff7b00;'>$otp</h1>
        <p>This OTP is valid for 5 minutes.</p>
        <p>Do not share this OTP with anyone.</p>
    ";
    return sendMail($email, $subject, $body);
}

function verifyOTP($email, $otp, $conn)
{
    $stmt = $conn->prepare("
        SELECT id FROM email_otp
        WHERE email=?
        AND otp=?
        AND expire_at >= NOW()
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows === 1;
}

function deleteOldOTP($email, $conn)
{
    $stmt = $conn->prepare("DELETE FROM email_otp WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
}