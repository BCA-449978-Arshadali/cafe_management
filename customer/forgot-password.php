<?php
session_start();
require_once '../config.php';
require_once 'includes/mailer.php';

$error = '';
$success = '';

if(isset($_POST['send_link'])){
    $email = trim($_POST['email'] ?? '');

    $check = $conn->prepare("SELECT id FROM customers WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();

    if($check->get_result()->num_rows == 1){
        $token  = bin2hex(random_bytes(50));
        $expire = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        $del = $conn->prepare("DELETE FROM password_resets WHERE email=?");
        $del->bind_param("s", $email);
        $del->execute();

        $stmt = $conn->prepare("INSERT INTO password_resets(email,token,expire_at) VALUES(?,?,?)");
        $stmt->bind_param("sss", $email, $token, $expire);
        $stmt->execute();

        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
        $link     = "$protocol://{$_SERVER['HTTP_HOST']}" . BASE_URL . "/customer/reset-password.php?token=$token";

        $body = "
            <div style='font-family:Arial,sans-serif;max-width:520px;margin:0 auto;background:#f9f9f9;padding:24px;border-radius:10px;'>
                <h2 style='color:#ff7b00;margin-top:0;'>🔒 Reset Your Password</h2>
                <p style='color:#555;'>We received a request to reset your Droppers Café account password.</p>
                <a href='$link' style='display:inline-block;margin:20px 0;padding:14px 28px;background:#ff7b00;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;'>Reset Password →</a>
                <p style='color:#888;font-size:13px;'>This link expires in <strong>15 minutes</strong>. If you didn't request this, ignore this email.</p>
                <p style='color:#aaa;font-size:12px;'>Droppers Café & Resto, Bheldi Road, Amnour, Bihar</p>
            </div>
        ";

        sendMail($email, "Reset Password - Droppers Café", $body);
        $success = "Reset link sent to <strong>" . htmlspecialchars($email) . "</strong>. Check your inbox!";
    } else {
        $error = "If this email is registered, a reset link has been sent.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Droppers Café</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html, body { margin:0; padding:0; }
body { font-family:'Poppins',sans-serif; background:#0d0d0d; color:#fff; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; position:relative; overflow-x:hidden; overflow-y:auto; }
body::before {
    content:''; position:fixed; inset:0;
    background:radial-gradient(ellipse at 20% 50%, rgba(255,123,0,0.07) 0%, transparent 60%),
               radial-gradient(ellipse at 80% 20%, rgba(255,200,0,0.04) 0%, transparent 50%);
    pointer-events:none;
}
.card {
    background:#111; border:1px solid rgba(255,255,255,0.07);
    border-radius:24px; padding:40px 36px;
    width:100%; max-width:440px;
    position:relative; z-index:2;
    box-shadow:0 24px 64px rgba(0,0,0,0.5);
    margin:auto;
}
/* ── Mobile Responsive ── */
@media (max-width:480px) {
    body { padding:16px; align-items:flex-start; }
    .card { padding:28px 18px; border-radius:18px; margin:0; }
    .card-logo img { width:58px; height:58px; }
    .card-logo h2 { font-size:18px; }
    .icon-circle { width:52px; height:52px; font-size:22px; margin-bottom:14px; }
    .card h3 { font-size:18px; }
    .card > p { font-size:12px; margin-bottom:20px; }
    .fg input { font-size:13px; padding:11px 14px 11px 38px; }
    .btn-submit { font-size:14px; padding:12px; }
    .back-link { font-size:13px; }
}
@media (max-width:360px) {
    .card { padding:22px 14px; }
}
.card-logo { text-align:center; margin-bottom:28px; }
.card-logo img {
    width:72px; height:72px; border-radius:50%;
    border:3px solid #ff7b00; background:#fff;
    padding:6px; animation:glow 3s ease-in-out infinite;
}
@keyframes glow {
    0%,100%{ box-shadow:0 0 16px rgba(255,123,0,0.3); }
    50%    { box-shadow:0 0 36px rgba(255,123,0,0.6); }
}
.card-logo h2 { font-size:22px; font-weight:800; margin-top:12px; }
.card-logo h2 span { color:#ff7b00; }
.card-logo p { color:#666; font-size:13px; margin-top:4px; }

.icon-circle {
    width:64px; height:64px; border-radius:50%; margin:0 auto 20px;
    background:rgba(255,123,0,0.1); border:2px solid rgba(255,123,0,0.3);
    display:flex; align-items:center; justify-content:center; font-size:26px;
}
.card h3 { font-size:22px; font-weight:800; text-align:center; margin-bottom:6px; }
.card h3 span { color:#ff7b00; }
.card > p { color:#666; font-size:13px; text-align:center; margin-bottom:28px; line-height:1.6; }

.alert { display:flex; align-items:flex-start; gap:10px; padding:14px 16px; border-radius:12px; font-size:13px; margin-bottom:20px; line-height:1.6; }
.alert-error   { background:rgba(231,76,60,0.08);  border:1px solid rgba(231,76,60,0.3);  color:#e74c3c; }
.alert-success { background:rgba(39,174,96,0.08);  border:1px solid rgba(39,174,96,0.3);  color:#27ae60; }

.fg { margin-bottom:18px; }
.fg label { display:block; font-size:12px; color:#888; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; }
.input-wrap { position:relative; }
.input-wrap .icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#444; font-size:14px; pointer-events:none; }
.fg input {
    width:100%; padding:13px 16px 13px 42px;
    background:#0d0d0d; border:1px solid rgba(255,255,255,0.08);
    border-radius:12px; color:#fff; font-size:14px;
    font-family:'Poppins',sans-serif; outline:none; transition:0.2s;
}
.fg input:focus { border-color:rgba(255,123,0,0.5); }
.btn-submit {
    width:100%; padding:14px;
    background:linear-gradient(135deg, #ff7b00, #ff9500);
    color:#fff; border:none; border-radius:14px;
    font-size:15px; font-weight:700; cursor:pointer;
    font-family:'Poppins',sans-serif; transition:all 0.3s;
    box-shadow:0 8px 24px rgba(255,123,0,0.3);
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-submit:hover { transform:translateY(-2px); box-shadow:0 12px 32px rgba(255,123,0,0.45); }
.back-link { display:flex; align-items:center; justify-content:center; gap:6px; margin-top:20px; font-size:14px; color:#666; }
.back-link a { color:#ff7b00; font-weight:600; }
.back-link a:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="card">
    <div class="card-logo">
        <img src="../assets/images/droppers-logo.png" alt="Logo">
        <h2>Droppers <span>Café</span></h2>
        <p>Password Recovery</p>
    </div>

    <div class="icon-circle">🔒</div>
    <h3>Forgot <span>Password?</span></h3>
    <p>No worries! Enter your registered email and we'll send you a reset link.</p>

    <?php if($error): ?>
    <div class="alert alert-error"><i class="fa fa-circle-exclamation"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if($success): ?>
    <div class="alert alert-success"><i class="fa fa-circle-check"></i> <?php echo $success; ?></div>
    <?php endif; ?>

    <?php if(!$success): ?>
    <form method="POST">
        <div class="fg">
            <label>Email Address</label>
            <div class="input-wrap">
                <i class="icon fa fa-envelope"></i>
                <input type="email" name="email" placeholder="you@example.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
        </div>
        <button type="submit" name="send_link" class="btn-submit">
            <i class="fa fa-paper-plane"></i> Send Reset Link
        </button>
    </form>
    <?php endif; ?>

    <div class="back-link">
        <i class="fa fa-arrow-left" style="font-size:12px;"></i>
        <a href="login.php">Back to Login</a>
    </div>
</div>
</body>
</html>