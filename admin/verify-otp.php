<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['admin_email'])){
    header("Location: login.php"); exit();
}

$email = $_SESSION['admin_email'];
$error = '';

if(isset($_POST['verify'])){
    $otp = $_POST['otp'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM admin_otp WHERE email=? AND otp=? AND expire_at>=NOW()");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        $_SESSION['admin'] = 1;
        $del = $conn->prepare("DELETE FROM admin_otp WHERE email=?");
        $del->bind_param("s", $email); $del->execute();
        header("Location: dashboard.php"); exit;
    } else {
        $error = "Invalid or expired OTP. Please try again.";
    }
}

$parts  = explode("@", $email);
$masked = substr($parts[0], 0, 3) . str_repeat('*', max(0, strlen($parts[0])-3)) . "@" . $parts[1];

// Fetch remaining seconds from DB
$remainingSeconds = 0;
$timeStmt = $conn->prepare("SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), expire_at)) AS remaining FROM admin_otp WHERE email=? ORDER BY expire_at DESC LIMIT 1");
$timeStmt->bind_param("s", $email);
$timeStmt->execute();
$timeResult = $timeStmt->get_result();
if($timeRow = $timeResult->fetch_assoc()){
    $remainingSeconds = (int)$timeRow['remaining'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification — Droppers Café Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body {
    font-family:'Poppins',sans-serif;
    background:#0d0d0d; color:#fff;
    min-height:100vh;
    display:flex; align-items:center; justify-content:center;
    padding:16px; overflow-y:auto;
}
body::before {
    content:''; position:fixed; inset:0; pointer-events:none;
    background:
        radial-gradient(ellipse at 15% 50%, rgba(255,123,0,0.07) 0%, transparent 55%),
        radial-gradient(ellipse at 85% 20%, rgba(255,165,0,0.04) 0%, transparent 50%);
}

#toast {
    position:fixed; top:20px; left:50%; transform:translateX(-50%);
    background:rgba(39,174,96,0.12); border:1px solid rgba(39,174,96,0.4);
    color:#27ae60; padding:10px 22px; border-radius:30px;
    font-size:13px; font-weight:600; z-index:9999;
    display:flex; align-items:center; gap:8px;
    box-shadow:0 8px 24px rgba(0,0,0,0.4);
    transition:opacity 0.4s, transform 0.4s;
}

.card {
    background:#111;
    border:1px solid rgba(255,255,255,0.07);
    border-radius:22px;
    padding:28px 32px 24px;
    width:100%; max-width:420px;
    position:relative; z-index:2;
    box-shadow:0 24px 60px rgba(0,0,0,0.55);
    text-align:center;
}

.card-logo { margin-bottom:16px; }
.card-logo img {
    width:58px; height:58px; border-radius:50%;
    border:2.5px solid #ff7b00; background:#fff; padding:5px;
    animation:glow 3s ease-in-out infinite;
}
@keyframes glow {
    0%,100%{ box-shadow:0 0 14px rgba(255,123,0,0.3); }
    50%    { box-shadow:0 0 30px rgba(255,123,0,0.6); }
}
.card-logo h2 { font-size:17px; font-weight:800; margin-top:8px; }
.card-logo h2 span { color:#ff7b00; }
.admin-badge {
    display:inline-flex; align-items:center; gap:5px;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.3);
    padding:3px 11px; border-radius:20px;
    font-size:10px; color:#ff7b00; font-weight:600; margin-top:5px;
}

.divider { height:1px; background:rgba(255,255,255,0.06); margin:16px 0; }

.icon-circle {
    width:50px; height:50px; border-radius:50%;
    background:rgba(255,123,0,0.1); border:1.5px solid rgba(255,123,0,0.3);
    display:flex; align-items:center; justify-content:center;
    font-size:22px; margin:0 auto 10px;
}
.card h3 { font-size:20px; font-weight:800; margin-bottom:4px; }
.card h3 span { color:#ff7b00; }
.subtitle { font-size:12px; color:#555; margin-bottom:6px; }
.email-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(255,123,0,0.08); border:1px solid rgba(255,123,0,0.2);
    color:#ff7b00; padding:6px 14px; border-radius:30px;
    font-size:12px; margin-bottom:18px;
}

.alert-error {
    display:flex; align-items:center; gap:9px;
    background:rgba(231,76,60,0.08); border:1px solid rgba(231,76,60,0.3);
    color:#e74c3c; padding:10px 14px; border-radius:10px;
    font-size:12px; margin-bottom:14px; text-align:left;
}

.otp-boxes { display:flex; gap:8px; justify-content:center; margin-bottom:6px; }
.otp-box {
    width:50px; height:56px;
    background:#0d0d0d;
    border:1.5px solid rgba(255,255,255,0.08);
    border-radius:12px; color:#fff;
    font-size:24px; font-weight:700; text-align:center;
    font-family:'Poppins',sans-serif; outline:none; transition:0.2s;
}
.otp-box:focus { border-color:#ff7b00; background:#111; box-shadow:0 0 0 3px rgba(255,123,0,0.13); }
.otp-box.filled { border-color:rgba(255,123,0,0.5); color:#ff7b00; }

.timer-wrap { margin:14px 0 2px; }
.timer-ring { width:50px; height:50px; margin:0 auto 6px; position:relative; }
.timer-ring svg { transform:rotate(-90deg); }
.timer-ring circle { fill:none; stroke-width:3.5; }
.timer-bg   { stroke:rgba(255,255,255,0.06); }
.timer-fill { stroke:#ff7b00; stroke-linecap:round; transition:stroke-dashoffset 1s linear; }
.timer-num {
    position:absolute; inset:0;
    display:flex; align-items:center; justify-content:center;
    font-size:11px; font-weight:800; color:#ff7b00;
}
.timer-label { font-size:11px; color:#555; }

.btn-submit {
    width:100%; padding:13px;
    background:linear-gradient(135deg, #ff7b00, #ff9800);
    color:#fff; border:none; border-radius:13px;
    font-size:14px; font-weight:700; cursor:pointer;
    font-family:'Poppins',sans-serif; transition:all 0.25s;
    box-shadow:0 6px 20px rgba(255,123,0,0.3); margin-top:14px;
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-submit:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 10px 28px rgba(255,123,0,0.45); }
.btn-submit:disabled { opacity:0.35; cursor:not-allowed; }

.bottom-links {
    display:flex; align-items:center; justify-content:space-between;
    margin-top:14px; padding-top:14px;
    border-top:1px solid rgba(255,255,255,0.06);
    font-size:12px;
}
.bottom-links a { color:#555; text-decoration:none; display:flex; align-items:center; gap:5px; transition:color 0.2s; }
.bottom-links a:hover { color:#ff7b00; }
.resend-link { color:#ff7b00 !important; font-weight:600; }

#resendExpired {
    text-align:center; margin-top:12px; font-size:12px; color:#555;
    padding:9px 14px; border-radius:10px;
    background:rgba(231,76,60,0.07); border:1px solid rgba(231,76,60,0.2);
}
#resendExpired a { color:#e74c3c; font-weight:600; text-decoration:none; }
</style>
</head>
<body>

<?php if(isset($_GET['resent'])): ?>
<div id="toast"><i class="fa fa-envelope-circle-check"></i> OTP resent successfully!</div>
<?php endif; ?>

<div class="card">
    <div class="card-logo">
        <img src="../assets/images/droppers-logo.png" alt="Logo">
        <h2>Droppers <span>Café</span></h2>
        <div class="admin-badge"><i class="fa fa-shield-halved"></i> Admin Panel</div>
    </div>

    <div class="divider"></div>

    <div class="icon-circle">🔐</div>
    <h3>Enter <span>OTP</span></h3>
    <p class="subtitle">6-digit code sent to your email</p>
    <div class="email-badge"><i class="fa fa-envelope fa-xs"></i> <?= $masked ?></div>

    <?php if($error): ?>
    <div class="alert-error"><i class="fa fa-circle-exclamation"></i> <?= $error ?></div>
    <?php endif; ?>

    <form method="POST" id="otpForm" onsubmit="collectOtp()">
        <div class="otp-boxes">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" id="o1">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" id="o2">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" id="o3">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" id="o4">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" id="o5">
            <input class="otp-box" type="text" maxlength="1" inputmode="numeric" id="o6">
        </div>
        <input type="hidden" name="otp" id="otpInput">

        <div class="timer-wrap">
            <div class="timer-ring">
                <svg width="50" height="50" viewBox="0 0 50 50">
                    <circle class="timer-bg"   cx="25" cy="25" r="19"/>
                    <circle class="timer-fill" id="timerCircle" cx="25" cy="25" r="19"/>
                </svg>
                <div class="timer-num" id="timerNum">05:00</div>
            </div>
            <div class="timer-label" id="timerLabel">OTP valid for 5 minutes</div>
        </div>

        <button type="submit" name="verify" class="btn-submit" id="verifyBtn">
            <i class="fa fa-shield-halved"></i> Verify &amp; Login
        </button>
    </form>

    <div id="resendExpired" style="display:none;">
        ⏱ OTP expired! <a href="resend-otp.php">Resend OTP →</a>
    </div>

    <div class="bottom-links">
        <a href="login.php"><i class="fa fa-arrow-left" style="font-size:10px;"></i> Back to Login</a>
        <a href="resend-otp.php" class="resend-link" id="resendEarly">Resend OTP</a>
    </div>
</div>

<script>
var boxes = ['o1','o2','o3','o4','o5','o6'].map(function(id){ return document.getElementById(id); });
boxes.forEach(function(box, i){
    box.addEventListener('input', function(){
        this.value = this.value.replace(/[^0-9]/g,'');
        this.classList.toggle('filled', this.value !== '');
        if(this.value && i < 5) boxes[i+1].focus();
    });
    box.addEventListener('keydown', function(e){
        if(e.key==='Backspace' && !this.value && i > 0){
            boxes[i-1].focus(); boxes[i-1].classList.remove('filled');
        }
    });
    box.addEventListener('paste', function(e){
        e.preventDefault();
        var d = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        d.split('').forEach(function(ch,j){ if(boxes[j]){ boxes[j].value=ch; boxes[j].classList.add('filled'); } });
        boxes[Math.min(d.length,5)].focus();
    });
});
function collectOtp(){
    document.getElementById('otpInput').value = boxes.map(function(b){ return b.value; }).join('');
}

var total=300, left=<?= $remainingSeconds ?>;
var circle=document.getElementById('timerCircle');
var circ=2*Math.PI*19;
circle.style.strokeDasharray=circ;
circle.style.strokeDashoffset = left <= 0 ? circ : circ*(1-left/total);
function fmt(s){ return String(Math.floor(s/60)).padStart(2,'0')+':'+String(s%60).padStart(2,'0'); }
document.getElementById('timerNum').textContent=fmt(left);

// Already expired on page load
if(left <= 0){
    document.getElementById('timerNum').textContent='00:00';
    document.getElementById('timerLabel').textContent='OTP Expired';
    document.getElementById('timerLabel').style.color='#e74c3c';
    document.getElementById('timerNum').style.color='#e74c3c';
    circle.style.stroke='#e74c3c';
    document.getElementById('verifyBtn').disabled=true;
    document.getElementById('resendExpired').style.display='block';
    document.getElementById('resendEarly').style.display='none';
}

var t = left > 0 ? setInterval(function(){
    left--;
    document.getElementById('timerNum').textContent=fmt(left);
    circle.style.strokeDashoffset=circ*(1-left/total);
    if(left<=30){
        circle.style.stroke='#e74c3c';
        document.getElementById('timerNum').style.color='#e74c3c';
        document.getElementById('timerLabel').textContent='Expiring soon!';
        document.getElementById('timerLabel').style.color='#e74c3c';
    }
    if(left<=0){
        clearInterval(t);
        document.getElementById('timerNum').textContent='00:00';
        document.getElementById('timerLabel').textContent='OTP Expired';
        document.getElementById('verifyBtn').disabled=true;
        document.getElementById('resendExpired').style.display='block';
        document.getElementById('resendEarly').style.display='none';
    }
},1000) : null;

var toast=document.getElementById('toast');
if(toast) setTimeout(function(){ toast.style.opacity='0'; toast.style.transform='translateX(-50%) translateY(-16px)'; },3500);
boxes[0].focus();
</script>
</body>
</html>