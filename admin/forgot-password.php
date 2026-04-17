<?php
require_once '../customer/includes/mailer.php';
include 'includes/db.php';

if(isset($_POST['send'])){

    $email = trim($_POST['email'] ?? '');

    // Sirf admin email pe hi link jaaye
    $check = $conn->prepare("SELECT id FROM admin WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows > 0){

        $token = bin2hex(random_bytes(32));

        // Delete old tokens
        $del = $conn->prepare("DELETE FROM password_resets WHERE email=?");
        $del->bind_param("s", $email);
        $del->execute();

        $stmt = $conn->prepare("INSERT INTO password_resets(email,token,expire_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 10 MINUTE))");
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();

        $link = "http://localhost/cafe_management/admin/reset-password.php?token=" . $token;

        $subject = 'Admin Password Reset';
        $body    = "Droppers Cafe Admin Password Reset<br><br>
                    Click the link below to reset your password:<br><br>
                    <a href='$link'>$link</a><br><br>
                    This link will expire in 10 minutes.";

        sendMail($email, $subject, $body);
        echo "<script>alert('Reset link sent to your email');</script>";

    } else {
        // Email not found - but don't reveal this for security
        echo "<script>alert('If this email is registered, you will receive a reset link.');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Forgot Password</title>
<link rel="stylesheet" href="../assets/css/admin-login.css">
</head>
<body>

<div class="admin-login">
<div class="left-side">
    <img src="../assets/images/droppers-logo.png" class="logo">
    <p>Admin Password Reset</p>
    <h1>Droppers Cafe & Resto</h1>
</div>
<div class="divider"></div>
<div class="right-side">
    <h2>Forgot Password</h2>
    <p>Enter admin email to receive reset link</p>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter Admin Email" required>
        <button type="submit" name="send">Send Reset Link</button>
    </form>

    <a href="login.php">Back to Login</a>
</div>
</div>

</body>
</html>