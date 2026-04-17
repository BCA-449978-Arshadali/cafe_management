<?php
session_start();
require_once '../config.php';
require_once 'includes/otp-functions.php';

if(!isset($_SESSION['verify_email'])){
    header("Location: login.php"); exit();
}

$email = $_SESSION['verify_email'];
$error = '';

if(isset($_POST['verify'])){
    $otp = $_POST['otp'] ?? '';

    if(verifyOTP($email, $otp, $conn)){
        $stmt = $conn->prepare("UPDATE customers SET is_verified=1 WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        deleteOldOTP($email, $conn);
        unset($_SESSION['verify_email']);
        header("Location: login.php?verified=1"); exit();
    } else {
        $error = "Invalid or expired OTP. Please try again.";
    }
}

$parts  = explode("@", $email);
$masked = substr($parts[0], 0, 3) . str_repeat('*', max(0, strlen($parts[0])-3)) . "@" . $parts[1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Droppers Café</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

body {
    font-family:'Poppins',sans-serif;
    background:#0d0d0d;
    color:#fff;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:16px;
    position:relative;
    overflow-x:hidden;
}
body::before {
    content:''; position:fixed; inset:0;
    background:
        radial-gradient(ellipse at 20% 50%, rgba(255,123,0,0.07) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 20%, rgba(255,200,0,0.04) 0%, transparent 50%);
    pointer-events:none;
}

/* ── Card ── */
.card {
    background:#111;
    border:1px solid rgba(255,255,255,0.07);
    border-radius:24px;
    padding:40px 36px;
    width:100%;
    max-width:440px;
    position:relative;
    z-index:2;
    box-shadow:0 24px 64px rgba(0,0,0,0.5);
    text-align:center;
}

/* ── Logo ── */
.card-logo img {
    width:68px; height:68px;
    border-radius:50%;
    border:3px solid #ff7b00;
    background:#fff;
    padding:5px;
    animation:glow 3s ease-in-out infinite;
    margin-bottom:10px;
}
@keyframes glow {
    0%,100%{ box-shadow:0 0 16px rgba(255,123,0,0.3); }
    50%    { box-shadow:0 0 36px rgba(255,123,0,0.6); }
}
.card-logo h2 { font-size:20px; font-weight:800; margin-bottom:2px; }
.card-logo h2 span { color:#ff7b00; }
.card-logo p  { color:#555; font-size:12px; margin-bottom:22px; }

/* ── Icon circle ── */
.icon-circle {
    width:58px; height:58px;
    border-radius:50%; margin:0 auto 16px;
    background:rgba(255,123,0,0.1);
    border:2px solid rgba(255,123,0,0.3);
    display:flex; align-items:center; justify-content:center;
    font-size:24px;
}
.card h3 { font-size:20px; font-weight:800; margin-bottom:6px; }
.card h3 span { color:#ff7b00; }

/* ── Email badge ── */
.email-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(255,123,0,0.08);
    border:1px solid rgba(255,123,0,0.2);
    padding:7px 14px; border-radius:30px;
    font-size:12px; color:#ff7b00;
    margin:10px 0 24px;
    word-break:break-all;
    max-width:100%;
}

/* ── Alert ── */
.alert {
    display:flex; align-items:center; gap:10px;
    padding:12px 14px; border-radius:12px;
    font-size:13px; margin-bottom:18px; text-align:left;
}
.alert-error {
    background:rgba(231,76,60,0.08);
    border:1px solid rgba(231,76,60,0.3);
    color:#e74c3c;
}

/* ── OTP Boxes ── */
.otp-boxes {
    display:flex;
    gap:8px;
    justify-content:center;
    margin-bottom:8px;
}
.otp-box {
    width:48px; height:56px;
    background:#0d0d0d;
    border:2px solid rgba(255,255,255,0.08);
    border-radius:14px;
    color:#fff;
    font-size:22px; font-weight:700;
    text-align:center;
    font-family:'Poppins',sans-serif;
    outline:none;
    transition:border-color 0.2s, box-shadow 0.2s;
    -webkit-appearance:none;
}
.otp-box:focus {
    border-color:#ff7b00;
    background:#0f0f0f;
    box-shadow:0 0 0 3px rgba(255,123,0,0.15);
}
.otp-box.filled { border-color:rgba(255,123,0,0.5); }

/* ── Timer ── */
.timer-wrap { margin:16px 0 4px; }
.timer-ring {
    width:54px; height:54px;
    margin:0 auto 6px;
    position:relative;
}
.timer-ring svg { transform:rotate(-90deg); }
.timer-ring circle { fill:none; stroke-width:4; }
.timer-bg   { stroke:rgba(255,255,255,0.06); }
.timer-fill {
    stroke:#ff7b00; stroke-linecap:round;
    transition:stroke-dashoffset 1s linear;
    stroke-dasharray:138; stroke-dashoffset:0;
}
.timer-num {
    position:absolute; inset:0;
    display:flex; align-items:center; justify-content:center;
    font-size:15px; font-weight:700; color:#ff7b00;
}
.timer-label { font-size:11px; color:#555; }

/* ── Submit button ── */
.btn-submit {
    width:100%; padding:14px;
    background:linear-gradient(135deg, #ff7b00, #ff9500);
    color:#fff; border:none; border-radius:14px;
    font-size:15px; font-weight:700;
    cursor:pointer;
    font-family:'Poppins',sans-serif;
    transition:all 0.3s;
    box-shadow:0 8px 24px rgba(255,123,0,0.3);
    margin-top:8px;
    display:flex; align-items:center; justify-content:center; gap:8px;
    -webkit-tap-highlight-color:transparent;
}
.btn-submit:hover  { transform:translateY(-2px); box-shadow:0 12px 32px rgba(255,123,0,0.45); }
.btn-submit:active { transform:translateY(0); }

/* ── Resend & back ── */
.resend-wrap { margin-top:18px; font-size:13px; color:#555; }
.resend-wrap a { color:#ff7b00; font-weight:600; }
.resend-wrap a:hover { text-decoration:underline; }
.back-link {
    display:flex; align-items:center; justify-content:center; gap:6px;
    margin-top:12px; font-size:13px; color:#555;
}
.back-link a { color:#ff7b00; font-weight:600; }

/* ══════════════════════════════
   MOBILE RESPONSIVE
   ══════════════════════════════ */
@media (max-width: 480px) {
    body { padding:12px; align-items:flex-start; padding-top:24px; }

    .card {
        padding:28px 20px;
        border-radius:20px;
    }

    .card-logo img { width:58px; height:58px; }
    .card-logo h2  { font-size:18px; }
    .card h3       { font-size:18px; }

    .email-badge {
        font-size:11px;
        padding:6px 12px;
    }

    /* Smaller OTP boxes on mobile */
    .otp-boxes { gap:6px; }
    .otp-box {
        width:42px; height:50px;
        font-size:20px;
        border-radius:12px;
    }

    .btn-submit { font-size:14px; padding:13px; }
}

@media (max-width: 360px) {
    .otp-boxes { gap:4px; }
    .otp-box {
        width:36px; height:44px;
        font-size:18px;
        border-radius:10px;
    }
}

/* ── OTP Toggle visibility ── */
.toggle-visibility {
    display:inline-flex; align-items:center; gap:6px;
    margin:10px auto 4px;
    cursor:pointer;
    color:#666; font-size:12px; font-weight:500;
    padding:5px 12px; border-radius:20px;
    border:1px solid rgba(255,255,255,0.07);
    background:rgba(255,255,255,0.03);
    transition:all 0.2s;
    -webkit-tap-highlight-color:transparent;
    user-select:none;
}
.toggle-visibility:hover { color:#ff7b00; border-color:rgba(255,123,0,0.3); }
.toggle-visibility i { font-size:13px; }
/* OTP hidden state — show dots */
.otp-box.hidden-otp { -webkit-text-security:disc; text-security:disc; }
</style>
</head>
<body>
<div class="card">

    <!-- Logo -->
    <div class="card-logo">
        <img src="../assets/images/droppers-logo.png" alt="Logo">
        <h2>Droppers <span>Café</span></h2>
        <p>Email Verification</p>
    </div>

    <div class="icon-circle">📧</div>
    <h3>Enter <span>OTP</span></h3>
    <p style="color:#555;font-size:13px;line-height:1.6;margin-bottom:8px;">We sent a 6-digit code to</p>
    <div class="email-badge"><i class="fa fa-envelope"></i> <?php echo htmlspecialchars($masked); ?></div>

    <?php if($error): ?>
    <div class="alert alert-error"><i class="fa fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" id="otpForm" onsubmit="collectOtp()">
        <div class="otp-boxes">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="o1" autocomplete="one-time-code">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="o2">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="o3">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="o4">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="o5">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="o6">
        </div>
        <!-- Show / Hide OTP toggle -->
        <div class="toggle-visibility" onclick="toggleOtpVisibility()" id="toggleBtn">
            <i class="fa fa-eye-slash" id="toggleIcon"></i> <span id="toggleText">Hide OTP</span>
        </div>
        <input type="hidden" name="otp" id="otpInput">

        <!-- Circular Timer -->
        <div class="timer-wrap">
            <div class="timer-ring">
                <svg width="54" height="54" viewBox="0 0 54 54">
                    <circle class="timer-bg"   cx="27" cy="27" r="22"/>
                    <circle class="timer-fill" id="timerCircle" cx="27" cy="27" r="22"/>
                </svg>
                <div class="timer-num" id="timerNum">60</div>
            </div>
            <div class="timer-label" id="timerLabel">OTP expires in 60s</div>
        </div>

        <button type="submit" name="verify" class="btn-submit">
            <i class="fa fa-shield-halved"></i> Verify OTP
        </button>
    </form>

    <div class="resend-wrap" id="resendWrap" style="display:none;">
        OTP expired. <a href="resend-otp.php">Resend OTP →</a>
    </div>
    <div class="resend-wrap" id="resendEarly">
        Didn't receive it? <a href="resend-otp.php">Resend OTP</a>
    </div>

    <div class="back-link">
        <i class="fa fa-arrow-left" style="font-size:12px;"></i>
        <a href="login.php">Back to Login</a>
    </div>
</div>

<script>
var boxes = ['o1','o2','o3','o4','o5','o6'].map(function(id){ return document.getElementById(id); });

boxes.forEach(function(box, i){
    box.addEventListener('input', function(){
        this.value = this.value.replace(/[^0-9]/g, '');
        if(this.value && i < 5) boxes[i+1].focus();
        this.classList.toggle('filled', this.value !== '');
    });
    box.addEventListener('keydown', function(e){
        if(e.key === 'Backspace' && !this.value && i > 0) boxes[i-1].focus();
    });
    box.addEventListener('paste', function(e){
        e.preventDefault();
        var data = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        data.split('').forEach(function(ch, j){
            if(boxes[j]){ boxes[j].value = ch; boxes[j].classList.add('filled'); }
        });
        var next = Math.min(data.length, 5);
        if(boxes[next]) boxes[next].focus();
    });
});

function collectOtp(){
    document.getElementById('otpInput').value = boxes.map(function(b){ return b.value; }).join('');
}

// ── Persistent Timer (survives refresh) ──
var total = 60;
var circle = document.getElementById('timerCircle');
var circumference = 138;
var TIMER_KEY = 'otp_start_<?php echo md5($email); ?>';

// Get or set start time in sessionStorage
var startTime = sessionStorage.getItem(TIMER_KEY);
if(!startTime){
    startTime = Date.now();
    sessionStorage.setItem(TIMER_KEY, startTime);
} else {
    startTime = parseInt(startTime);
}

function getLeft(){
    var elapsed = Math.floor((Date.now() - startTime) / 1000);
    return Math.max(0, total - elapsed);
}

function updateTimer(){
    var left = getLeft();
    document.getElementById('timerNum').textContent   = left;
    document.getElementById('timerLabel').textContent = left > 0 ? 'OTP expires in ' + left + 's' : 'OTP Expired';
    circle.style.strokeDashoffset = circumference * (1 - left / total);

    if(left <= 10){
        circle.style.stroke = '#e74c3c';
        document.getElementById('timerNum').style.color = '#e74c3c';
    }
    if(left <= 0){
        clearInterval(timerInterval);
        document.getElementById('timerLabel').style.color    = '#e74c3c';
        document.getElementById('resendWrap').style.display  = 'block';
        document.getElementById('resendEarly').style.display = 'none';
        // Clear so resend starts fresh
        sessionStorage.removeItem(TIMER_KEY);
    }
}

updateTimer(); // Immediate render, no flicker
var timerInterval = setInterval(updateTimer, 1000);

// ── OTP Show / Hide Toggle ──
var otpVisible = true;
function toggleOtpVisibility(){
    otpVisible = !otpVisible;
    boxes.forEach(function(b){
        b.classList.toggle('hidden-otp', !otpVisible);
    });
    document.getElementById('toggleIcon').className = otpVisible ? 'fa fa-eye-slash' : 'fa fa-eye';
    document.getElementById('toggleText').textContent = otpVisible ? 'Hide OTP' : 'Show OTP';
}

boxes[0].focus();
</script>
</body>
</html>