<?php
session_start();
require_once '../config.php';
require_once 'includes/otp-functions.php';

$error = '';

if(isset($_POST['register'])){
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $mobile   = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // ✅ Mobile normalize — +91 / 91 prefix strip karke sirf last 10 digits rakho
    // Isse "+91 9234366818" aur "9234366818" dono same treat honge
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    if(strlen($mobile) == 12 && substr($mobile, 0, 2) === '91') $mobile = substr($mobile, 2);
    $mobile = substr($mobile, -10);

    if(!preg_match('/^[a-zA-Z\s]+$/', $name)){
        $error = "Name can only contain alphabets!";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Please enter a valid email address.";
    } elseif(!preg_match('/^[0-9]{10}$/', $mobile)){
        $error = "Mobile number must be exactly 10 digits!";
    } elseif(strlen($password) < 6){
        $error = "Password must be at least 6 characters!";
    } elseif($password !== $confirm){
        $error = "Passwords do not match!";
    } else {

        // ✅ Check 1: Email already registered?
        $chk_email = $conn->prepare("SELECT id FROM customers WHERE email=?");
        $chk_email->bind_param("s", $email);
        $chk_email->execute();
        if($chk_email->get_result()->num_rows > 0){
            $error = "This email address is already registered! Please <a href='login.php' style='color:#ff7b00;'>login</a> or use a different email.";
        }

        // ✅ Check 2: Mobile already registered?
        if(empty($error)){
            $chk_mobile = $conn->prepare("SELECT id FROM customers WHERE mobile=?");
            $chk_mobile->bind_param("s", $mobile);
            $chk_mobile->execute();
            if($chk_mobile->get_result()->num_rows > 0){
                $error = "This mobile number is already registered! Please <a href='login.php' style='color:#ff7b00;'>login</a> or use a different number.";
            }
        }

        // ✅ All clear — register karo
        if(empty($error)){
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO customers(name,email,mobile,password) VALUES(?,?,?,?)");
            $stmt->bind_param("ssss", $name, $email, $mobile, $hashed);
            $stmt->execute();

            deleteOldOTP($email, $conn);
            $otp = generateOTP();
            saveOTP($email, $otp, $conn);
            sendOTP($email, $otp);

            $_SESSION['verify_email'] = $email;
            header("Location: verify-otp.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Droppers Café</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html { height:100%; }
body { font-family:'Poppins',sans-serif; background:#0d0d0d; color:#fff; min-height:100vh; display:flex; }

/* LEFT PANEL */
.left-panel {
    flex:1; display:flex; flex-direction:column;
    align-items:center; justify-content:center; padding:60px 40px;
    background:linear-gradient(135deg, #0a0a0a 0%, #0f1f0f 60%, #0a0a0a 100%);
    position:relative; overflow:hidden;
    min-height:100vh;
}
.left-panel::before {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse at 30% 40%, rgba(255,123,0,0.1) 0%, transparent 60%),
               radial-gradient(ellipse at 70% 80%, rgba(255,200,0,0.05) 0%, transparent 50%);
}
.left-inner { position:relative; z-index:2; text-align:center; max-width:400px; }
.brand-logo {
    width:90px; height:90px; border-radius:50%;
    border:3px solid #ff7b00; background:#fff; padding:8px;
    margin:0 auto 20px; animation:glow 3s ease-in-out infinite;
}
.brand-logo img { width:100%; height:100%; object-fit:contain; border-radius:50%; }
@keyframes glow {
    0%,100%{ box-shadow:0 0 20px rgba(255,123,0,0.3); }
    50%    { box-shadow:0 0 50px rgba(255,123,0,0.6); }
}
.brand-name { font-size:28px; font-weight:800; margin-bottom:6px; }
.brand-name span { color:#ff7b00; }
.brand-tagline { color:#666; font-size:13px; margin-bottom:36px; line-height:1.6; }

.steps-list { display:flex; flex-direction:column; gap:0; text-align:left; }
.step-item { display:flex; gap:16px; padding:16px 0; border-bottom:1px solid rgba(255,255,255,0.05); }
.step-item:last-child { border-bottom:none; }
.step-num {
    width:36px; height:36px; border-radius:50%; flex-shrink:0;
    background:rgba(255,123,0,0.1); border:2px solid rgba(255,123,0,0.3);
    display:flex; align-items:center; justify-content:center;
    font-size:13px; font-weight:800; color:#ff7b00;
}
.step-item h5 { font-size:14px; font-weight:600; margin-bottom:2px; }
.step-item p  { font-size:12px; color:#666; }

/* RIGHT PANEL */
.right-panel {
    width:500px; flex-shrink:0; background:#111;
    border-left:1px solid rgba(255,255,255,0.06);
    display:flex; align-items:flex-start; justify-content:center;
    padding:48px 40px; overflow-y:auto;
    min-height:100vh;
}
.form-wrap { width:100%; max-width:400px; padding:10px 0; }
.form-header { margin-bottom:26px; }
.form-header h2 { font-size:26px; font-weight:800; margin-bottom:6px; }
.form-header h2 span { color:#ff7b00; }
.form-header p { color:#666; font-size:13px; }

.alert-error {
    display:flex; align-items:flex-start; gap:10px;
    background:rgba(231,76,60,0.08); border:1px solid rgba(231,76,60,0.3);
    color:#e74c3c; padding:13px 16px; border-radius:12px;
    font-size:13px; margin-bottom:18px; line-height:1.6;
}
.fg { margin-bottom:15px; }
.fg label { display:block; font-size:12px; color:#888; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:7px; }
.input-wrap { position:relative; }
.input-wrap .icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#444; font-size:14px; pointer-events:none; }
.fg input {
    width:100%; padding:12px 16px 12px 42px;
    background:#0d0d0d; border:1px solid rgba(255,255,255,0.08);
    border-radius:12px; color:#fff; font-size:14px;
    font-family:'Poppins',sans-serif; outline:none; transition:0.2s;
}
.fg input:focus { border-color:rgba(255,123,0,0.5); background:#0f0f0f; }
.fg input.valid   { border-color:rgba(39,174,96,0.5); }
.fg input.invalid { border-color:rgba(231,76,60,0.5); }
.toggle-pw {
    position:absolute; right:14px; top:50%; transform:translateY(-50%);
    color:#444; cursor:pointer; font-size:14px;
    background:none; border:none; transition:0.2s;
}
.toggle-pw:hover { color:#ff7b00; }

/* Password strength */
.pw-strength { margin-top:8px; }
.pw-strength-bar { height:4px; border-radius:4px; background:#1a1a1a; overflow:hidden; margin-bottom:4px; }
.pw-strength-fill { height:100%; border-radius:4px; width:0; transition:width 0.3s, background 0.3s; }
.pw-strength-text { font-size:11px; color:#555; }

.form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

.btn-submit {
    width:100%; padding:14px;
    background:linear-gradient(135deg, #ff7b00, #ff9500);
    color:#fff; border:none; border-radius:14px;
    font-size:15px; font-weight:700; cursor:pointer;
    font-family:'Poppins',sans-serif; transition:all 0.3s;
    box-shadow:0 8px 24px rgba(255,123,0,0.3); margin-top:6px;
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-submit:hover { transform:translateY(-2px); box-shadow:0 12px 32px rgba(255,123,0,0.45); }
.divider { display:flex; align-items:center; gap:12px; color:#333; font-size:12px; margin:20px 0; }
.divider::before, .divider::after { content:''; flex:1; height:1px; background:rgba(255,255,255,0.06); }
.bottom-link { text-align:center; font-size:14px; color:#666; }
.bottom-link a { color:#ff7b00; font-weight:600; }
.bottom-link a:hover { text-decoration:underline; }

.terms-note { font-size:11px; color:#444; text-align:center; margin-top:14px; line-height:1.6; }
.terms-note a { color:#666; }

@media(max-width:960px){ .left-panel{ display:none; } .right-panel{ width:100%; border-left:none; } }
@media(max-width:480px){ .right-panel{ padding:28px 20px; } .form-row{ grid-template-columns:1fr; } }
</style>
</head>
<body>

<!-- Left Panel -->
<div class="left-panel">
    <div class="left-inner">
        <div class="brand-logo"><img src="../assets/images/droppers-logo.png" alt="Logo"></div>
        <h1 class="brand-name">Droppers <span>Café</span></h1>
        <p class="brand-tagline">Join thousands of happy customers.<br>Sign up and enjoy the best food in Amnour!</p>

        <div class="steps-list">
            <div class="step-item">
                <div class="step-num">1</div>
                <div><h5>Create Your Account</h5><p>Fill in your details — takes less than a minute</p></div>
            </div>
            <div class="step-item">
                <div class="step-num">2</div>
                <div><h5>Verify Your Email</h5><p>Enter the OTP sent to your inbox</p></div>
            </div>
            <div class="step-item">
                <div class="step-num">3</div>
                <div><h5>Start Ordering!</h5><p>Browse menu, book tables & track orders</p></div>
            </div>
        </div>
    </div>
</div>

<!-- Right Panel -->
<div class="right-panel">
    <div class="form-wrap">
        <div class="form-header">
            <h2>Create <span>Account</span></h2>
            <p>Join the Droppers Café family today!</p>
        </div>

        <?php if($error): ?>
        <div class="alert-error"><i class="fa fa-circle-exclamation" style="margin-top:2px;flex-shrink:0;"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateForm()" id="regForm">

            <div class="fg">
                <label>Full Name</label>
                <div class="input-wrap">
                    <i class="icon fa fa-user"></i>
                    <input type="text" name="name" id="name" placeholder="Your full name"
                        oninput="this.value=this.value.replace(/[^a-zA-Z\s]/g,'')"
                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="fg">
                <label>Email Address</label>
                <div class="input-wrap">
                    <i class="icon fa fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="you@example.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="fg">
                <label>Mobile Number</label>
                <div class="input-wrap">
                    <i class="icon fa fa-phone"></i>
                    <input type="text" name="mobile" id="mobile" placeholder="10-digit number"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
                        maxlength="10"
                        value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="fg">
                    <label>Password</label>
                    <div class="input-wrap">
                        <i class="icon fa fa-lock"></i>
                        <input type="password" name="password" id="pw" placeholder="Min 6 chars"
                            oninput="checkStrength(this.value)" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('pw','eyePw')">
                            <i class="fa fa-eye" id="eyePw"></i>
                        </button>
                    </div>
                    <div class="pw-strength">
                        <div class="pw-strength-bar"><div class="pw-strength-fill" id="pwFill"></div></div>
                        <span class="pw-strength-text" id="pwText">Enter password</span>
                    </div>
                </div>
                <div class="fg">
                    <label>Confirm Password</label>
                    <div class="input-wrap">
                        <i class="icon fa fa-lock"></i>
                        <input type="password" name="confirm_password" id="cpw" placeholder="Re-enter" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('cpw','eyeCpw')">
                            <i class="fa fa-eye" id="eyeCpw"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" name="register" class="btn-submit">
                <i class="fa fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="divider">or</div>
        <div class="bottom-link">Already have an account? <a href="login.php">Sign in →</a></div>
        <p class="terms-note">By registering, you agree to our <a href="privacy-policy.php">Privacy Policy</a> and <a href="disclaimer.php">Terms of Use</a>.</p>
    </div>
</div>

<script>
function togglePw(inputId, iconId){
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
    if(input.type==='password'){ input.type='text'; icon.className='fa fa-eye-slash'; }
    else{ input.type='password'; icon.className='fa fa-eye'; }
}

function checkStrength(val){
    var fill = document.getElementById('pwFill');
    var text = document.getElementById('pwText');
    var strength = 0;
    if(val.length >= 6) strength++;
    if(val.length >= 10) strength++;
    if(/[A-Z]/.test(val)) strength++;
    if(/[0-9]/.test(val)) strength++;
    if(/[^A-Za-z0-9]/.test(val)) strength++;
    var colors = ['#e74c3c','#e74c3c','#f0a500','#27ae60','#27ae60'];
    var labels = ['Too short','Weak','Fair','Strong','Very Strong'];
    var pct    = [20,35,55,75,100];
    var i = Math.max(0, strength - 1);
    fill.style.width      = (val.length ? pct[i] : 0) + '%';
    fill.style.background = val.length ? colors[i] : '';
    text.textContent      = val.length ? labels[i] : 'Enter password';
    text.style.color      = val.length ? colors[i] : '#555';
}

function validateForm(){
    var name   = document.getElementById('name').value.trim();
    var mobile = document.getElementById('mobile').value.trim();
    var pw     = document.getElementById('pw').value;
    var cpw    = document.getElementById('cpw').value;
    if(!/^[a-zA-Z\s]+$/.test(name))  { alert('Name can only contain alphabets!'); return false; }
    if(!/^[0-9]{10}$/.test(mobile))  { alert('Mobile number must be exactly 10 digits!'); return false; }
    if(pw.length < 6)                 { alert('Password must be at least 6 characters!'); return false; }
    if(pw !== cpw)                    { alert('Passwords do not match!'); return false; }
    return true;
}
</script>
</body>
</html>