<?php
session_start();
require_once '../config.php';

$error = '';

if(isset($_POST['login'])){
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM customers WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1){
        $user = $result->fetch_assoc();
        if($user['is_verified'] == 0){
            $error = "Please verify your email first. Check your inbox!";
        } elseif(password_verify($password, $user['password'])){
            $_SESSION['customer_id']    = $user['id'];
            $_SESSION['customer_name']  = $user['name'];
            $_SESSION['customer_email'] = $user['email'];
            $_SESSION['customer_phone'] = $user['mobile'];
            header("Location: " . BASE_URL . "/customer/index.php");
            exit();
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "No account found with this email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Droppers Café</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html, body { height:100%; }
body { font-family:'Poppins',sans-serif; background:#0d0d0d; color:#fff; min-height:100vh; display:flex; }

/* LEFT PANEL */
.left-panel {
    flex:1; display:flex; flex-direction:column;
    align-items:center; justify-content:center; padding:60px 40px;
    background:linear-gradient(135deg, #0a0a0a 0%, #0f1f0f 60%, #0a0a0a 100%);
    position:relative; overflow:hidden;
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
.brand-tagline { color:#666; font-size:13px; margin-bottom:40px; line-height:1.6; }
.feature-list { display:flex; flex-direction:column; gap:14px; text-align:left; }
.feature-item {
    display:flex; align-items:center; gap:14px;
    background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06);
    border-radius:14px; padding:14px 18px;
}
.fi-icon {
    width:40px; height:40px; border-radius:10px; flex-shrink:0;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.2);
    display:flex; align-items:center; justify-content:center; font-size:18px;
}
.feature-item h5 { font-size:14px; font-weight:600; margin-bottom:2px; }
.feature-item p  { font-size:12px; color:#666; }

/* RIGHT PANEL */
.right-panel {
    width:480px; flex-shrink:0; background:#111;
    border-left:1px solid rgba(255,255,255,0.06);
    display:flex; align-items:center; justify-content:center; padding:48px 40px;
}
.form-wrap { width:100%; max-width:380px; }
.form-header { margin-bottom:30px; }
.form-header h2 { font-size:28px; font-weight:800; margin-bottom:6px; }
.form-header h2 span { color:#ff7b00; }
.form-header p { color:#666; font-size:14px; }

.alert-error {
    display:flex; align-items:center; gap:10px;
    background:rgba(231,76,60,0.08); border:1px solid rgba(231,76,60,0.3);
    color:#e74c3c; padding:13px 16px; border-radius:12px;
    font-size:13px; margin-bottom:20px;
}
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
.fg input:focus { border-color:rgba(255,123,0,0.5); background:#0f0f0f; }
.toggle-pw {
    position:absolute; right:14px; top:50%; transform:translateY(-50%);
    color:#444; cursor:pointer; font-size:14px;
    background:none; border:none; transition:0.2s;
}
.toggle-pw:hover { color:#ff7b00; }
.options-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; font-size:13px; }
.remember-wrap { display:flex; align-items:center; gap:8px; color:#666; cursor:pointer; }
.remember-wrap input { accent-color:#ff7b00; width:15px; height:15px; }
.options-row a { color:#ff7b00; }
.options-row a:hover { text-decoration:underline; }
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
.divider { display:flex; align-items:center; gap:12px; color:#333; font-size:12px; margin:22px 0; }
.divider::before, .divider::after { content:''; flex:1; height:1px; background:rgba(255,255,255,0.06); }
.bottom-link { text-align:center; font-size:14px; color:#666; }
.bottom-link a { color:#ff7b00; font-weight:600; }
.bottom-link a:hover { text-decoration:underline; }

@media(max-width:900px){ .left-panel{ display:none; } .right-panel{ width:100%; border-left:none; } }
@media(max-width:480px){ .right-panel{ padding:32px 20px; } }
</style>
</head>
<body>

<div class="left-panel">
    <div class="left-inner">
        <div class="brand-logo"><img src="../assets/images/droppers-logo.png" alt="Logo"></div>
        <h1 class="brand-name">Droppers <span>Café</span></h1>
        <p class="brand-tagline">Good Food. Great Vibes.<br>Bheldi Road, Amnour, Bihar.</p>
        <div class="feature-list">
            <div class="feature-item">
                <div class="fi-icon">🍕</div>
                <div><h5>Order Online</h5><p>Browse 140+ items & order instantly</p></div>
            </div>
            <div class="feature-item">
                <div class="fi-icon">🪑</div>
                <div><h5>Book a Table</h5><p>Reserve your spot in just a few taps</p></div>
            </div>
            <div class="feature-item">
                <div class="fi-icon">📍</div>
                <div><h5>Track Your Order</h5><p>Real-time status on every order</p></div>
            </div>
        </div>
    </div>
</div>

<div class="right-panel">
    <div class="form-wrap">
        <div class="form-header">
            <h2>Welcome <span>Back!</span></h2>
            <p>Sign in to your Droppers Café account</p>
        </div>

        <?php if($error): ?>
        <div class="alert-error"><i class="fa fa-circle-exclamation"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="fg">
                <label>Email Address</label>
                <div class="input-wrap">
                    <i class="icon fa fa-envelope"></i>
                    <input type="email" name="email" placeholder="you@example.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="fg">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="icon fa fa-lock"></i>
                    <input type="password" name="password" id="pw" placeholder="Enter your password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('pw','eyePw')">
                        <i class="fa fa-eye" id="eyePw"></i>
                    </button>
                </div>
            </div>
            <div class="options-row">
                <label class="remember-wrap"><input type="checkbox"> Remember me</label>
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
            <button type="submit" name="login" class="btn-submit">
                <i class="fa fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div class="divider">or</div>
        <div class="bottom-link">Don't have an account? <a href="register.php">Create one free →</a></div>
    </div>
</div>

<script>
function togglePw(inputId, iconId){
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
    if(input.type === 'password'){ input.type='text'; icon.className='fa fa-eye-slash'; }
    else { input.type='password'; icon.className='fa fa-eye'; }
}
</script>
</body>
</html>