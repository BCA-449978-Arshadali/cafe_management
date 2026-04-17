<?php
require_once '../config.php';

if(!isset($_GET['token'])){
    header("Location: forgot-password.php"); exit();
}

$token = $_GET['token'];
$stmt  = $conn->prepare("SELECT * FROM password_resets WHERE token=? AND expire_at >= NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    $expired = true;
} else {
    $expired = false;
    $data    = $result->fetch_assoc();
    $email   = $data['email'];
}

$error   = '';
$success = '';

if(!$expired && isset($_POST['reset'])){
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if(strlen($password) < 6){
        $error = "Password kam se kam 6 characters ka hona chahiye!";
    } elseif($password !== $confirm){
        $error = "Passwords do not match!";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE customers SET password=? WHERE email=?");
        $update->bind_param("ss", $hashed, $email);
        $update->execute();
        $del = $conn->prepare("DELETE FROM password_resets WHERE email=?");
        $del->bind_param("s", $email); $del->execute();
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Droppers Café</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html, body { min-height:100%; }
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
    text-align:center; margin:auto;
}
/* ── Mobile Responsive ── */
@media (max-width:480px) {
    body { padding:16px; align-items:flex-start; }
    .card { padding:28px 18px; border-radius:18px; }
    .card-logo img { width:58px; height:58px; }
    .card-logo h2 { font-size:18px; }
    .icon-circle { width:52px; height:52px; font-size:22px; margin-bottom:14px; }
    .card h3 { font-size:18px; }
    .card-sub { font-size:12px; margin-bottom:20px; }
    .fg input { font-size:13px; padding:11px 40px 11px 38px; }
    .btn-submit, .btn-login { font-size:14px; padding:12px 20px; }
    .success-icon { font-size:44px; }
    .back-link { font-size:12px; }
}
@media (max-width:360px) {
    .card { padding:22px 14px; }
}
.card-logo img {
    width:72px; height:72px; border-radius:50%;
    border:3px solid #ff7b00; background:#fff;
    padding:6px; animation:glow 3s ease-in-out infinite; margin-bottom:12px;
}
@keyframes glow {
    0%,100%{ box-shadow:0 0 16px rgba(255,123,0,0.3); }
    50%    { box-shadow:0 0 36px rgba(255,123,0,0.6); }
}
.card-logo h2 { font-size:22px; font-weight:800; margin-bottom:4px; }
.card-logo h2 span { color:#ff7b00; }
.card-logo p { color:#666; font-size:13px; margin-bottom:28px; }
.icon-circle {
    width:64px; height:64px; border-radius:50%; margin:0 auto 20px;
    background:rgba(255,123,0,0.1); border:2px solid rgba(255,123,0,0.3);
    display:flex; align-items:center; justify-content:center; font-size:26px;
}
.card h3 { font-size:22px; font-weight:800; margin-bottom:8px; }
.card h3 span { color:#ff7b00; }
.card-sub { color:#666; font-size:13px; line-height:1.6; margin-bottom:28px; }

.alert { display:flex; align-items:center; gap:10px; padding:13px 16px; border-radius:12px; font-size:13px; margin-bottom:20px; text-align:left; }
.alert-error   { background:rgba(231,76,60,0.08);  border:1px solid rgba(231,76,60,0.3);  color:#e74c3c; }
.alert-success { background:rgba(39,174,96,0.08);  border:1px solid rgba(39,174,96,0.3);  color:#27ae60; }

.fg { margin-bottom:16px; text-align:left; }
.fg label { display:block; font-size:12px; color:#888; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; }
.input-wrap { position:relative; }
.input-wrap .icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#444; font-size:14px; pointer-events:none; }
.fg input {
    width:100%; padding:13px 44px 13px 42px;
    background:#0d0d0d; border:1px solid rgba(255,255,255,0.08);
    border-radius:12px; color:#fff; font-size:14px;
    font-family:'Poppins',sans-serif; outline:none; transition:0.2s;
}
.fg input:focus { border-color:rgba(255,123,0,0.5); }
.toggle-pw { position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#444; cursor:pointer; font-size:14px; background:none; border:none; transition:0.2s; }
.toggle-pw:hover { color:#ff7b00; }

.pw-strength { margin-top:8px; }
.pw-bar { height:4px; border-radius:4px; background:#1a1a1a; overflow:hidden; margin-bottom:4px; }
.pw-fill { height:100%; border-radius:4px; width:0; transition:width 0.3s, background 0.3s; }
.pw-text { font-size:11px; color:#555; }

.btn-submit {
    width:100%; padding:14px;
    background:linear-gradient(135deg, #ff7b00, #ff9500);
    color:#fff; border:none; border-radius:14px;
    font-size:15px; font-weight:700; cursor:pointer;
    font-family:'Poppins',sans-serif; transition:all 0.3s;
    box-shadow:0 8px 24px rgba(255,123,0,0.3); margin-top:8px;
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-submit:hover { transform:translateY(-2px); box-shadow:0 12px 32px rgba(255,123,0,0.45); }

/* Success state */
.success-icon { font-size:56px; margin-bottom:16px; display:block; animation:bounceIn 0.6s ease; }
@keyframes bounceIn {
    0%  { transform:scale(0); opacity:0; }
    60% { transform:scale(1.2); }
    100%{ transform:scale(1); opacity:1; }
}
.btn-login {
    display:inline-flex; align-items:center; gap:8px;
    background:linear-gradient(135deg, #ff7b00, #ff9500);
    color:#fff; padding:14px 32px; border-radius:14px;
    font-size:15px; font-weight:700; margin-top:20px;
    box-shadow:0 8px 24px rgba(255,123,0,0.3); transition:0.3s;
}
.btn-login:hover { transform:translateY(-2px); box-shadow:0 12px 32px rgba(255,123,0,0.45); color:#fff; }

.back-link { display:flex; align-items:center; justify-content:center; gap:6px; margin-top:18px; font-size:13px; color:#666; }
.back-link a { color:#ff7b00; font-weight:600; }
</style>
</head>
<body>
<div class="card">
    <div class="card-logo">
        <img src="../assets/images/droppers-logo.png" alt="Logo">
        <h2>Droppers <span>Café</span></h2>
        <p>Password Reset</p>
    </div>

    <?php if($expired): ?>
        <div class="icon-circle">⏰</div>
        <h3>Link <span>Expired!</span></h3>
        <p class="card-sub">This reset link has expired or is invalid. Please request a new one.</p>
        <a href="forgot-password.php" class="btn-login"><i class="fa fa-rotate-right"></i> Request New Link</a>

    <?php elseif($success): ?>
        <span class="success-icon">✅</span>
        <h3>Password <span>Updated!</span></h3>
        <p class="card-sub">Your password has been reset successfully. You can now sign in with your new password.</p>
        <a href="login.php" class="btn-login"><i class="fa fa-right-to-bracket"></i> Sign In Now</a>

    <?php else: ?>
        <div class="icon-circle">🔑</div>
        <h3>New <span>Password</span></h3>
        <p class="card-sub">Choose a strong password for your Droppers Café account.</p>

        <?php if($error): ?>
        <div class="alert alert-error"><i class="fa fa-circle-exclamation"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateForm()">
            <div class="fg">
                <label>New Password</label>
                <div class="input-wrap">
                    <i class="icon fa fa-lock"></i>
                    <input type="password" name="password" id="pw" placeholder="Min 6 characters" oninput="checkStrength(this.value)" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('pw','e1')"><i class="fa fa-eye" id="e1"></i></button>
                </div>
                <div class="pw-strength">
                    <div class="pw-bar"><div class="pw-fill" id="pwFill"></div></div>
                    <span class="pw-text" id="pwText">Enter password</span>
                </div>
            </div>
            <div class="fg">
                <label>Confirm Password</label>
                <div class="input-wrap">
                    <i class="icon fa fa-lock"></i>
                    <input type="password" name="confirm" id="cpw" placeholder="Re-enter password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('cpw','e2')"><i class="fa fa-eye" id="e2"></i></button>
                </div>
            </div>
            <button type="submit" name="reset" class="btn-submit">
                <i class="fa fa-shield-halved"></i> Reset Password
            </button>
        </form>

        <div class="back-link">
            <i class="fa fa-arrow-left" style="font-size:12px;"></i>
            <a href="login.php">Back to Login</a>
        </div>
    <?php endif; ?>
</div>

<script>
function togglePw(id, eid){
    var i = document.getElementById(id), e = document.getElementById(eid);
    if(i.type==='password'){ i.type='text'; e.className='fa fa-eye-slash'; }
    else{ i.type='password'; e.className='fa fa-eye'; }
}
function checkStrength(val){
    var fill = document.getElementById('pwFill'), text = document.getElementById('pwText');
    var s = 0;
    if(val.length>=6) s++; if(val.length>=10) s++;
    if(/[A-Z]/.test(val)) s++; if(/[0-9]/.test(val)) s++; if(/[^A-Za-z0-9]/.test(val)) s++;
    var c=['#e74c3c','#e74c3c','#f0a500','#27ae60','#27ae60'];
    var l=['Too short','Weak','Fair','Strong','Very Strong'];
    var p=[20,35,55,75,100];
    var i=Math.max(0,s-1);
    fill.style.width=(val.length?p[i]:0)+'%'; fill.style.background=val.length?c[i]:'';
    text.textContent=val.length?l[i]:'Enter password'; text.style.color=val.length?c[i]:'#555';
}
function validateForm(){
    var pw=document.getElementById('pw').value, cpw=document.getElementById('cpw').value;
    if(pw.length<6){ alert('Password kam se kam 6 characters!'); return false; }
    if(pw!==cpw){ alert('Passwords do not match!'); return false; }
    return true;
}
</script>
</body>
</html>