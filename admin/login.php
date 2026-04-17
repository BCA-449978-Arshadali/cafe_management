<?php
session_start();

require_once '../customer/includes/mailer.php';
include 'includes/db.php';

$error = '';

if(isset($_POST['login'])){
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $data = $result->fetch_assoc();
        $passwordMatch = false;

        if(password_verify($password, $data['password'])){
            $passwordMatch = true;
        } elseif($password === $data['password']){
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE admin SET password=? WHERE username=?");
            $upd->bind_param("ss", $hashed, $username);
            $upd->execute();
            $passwordMatch = true;
        }

        if($passwordMatch){
            $email = $data['email'];
            $otp   = rand(100000, 999999);

            $stmt_del = $conn->prepare("DELETE FROM admin_otp WHERE email=?");
            $stmt_del->bind_param("s", $email);
            $stmt_del->execute();

            $conn->query("INSERT INTO admin_otp(email,otp,expire_at)
            VALUES('$email','$otp',DATE_ADD(NOW(),INTERVAL 5 MINUTE))");

            $_SESSION['admin_email'] = $email;

            $subject = 'Admin Login OTP';
            $body    = "
                <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;background:#f9f9f9;padding:24px;border-radius:10px;'>
                    <h2 style='color:#ff7b00;margin-top:0;'>🔐 Admin Login OTP</h2>
                    <p style='color:#555;'>Your OTP for Droppers Café Admin Panel login:</p>
                    <div style='font-size:36px;font-weight:800;color:#ff7b00;letter-spacing:8px;text-align:center;padding:20px;background:#fff;border-radius:10px;margin:16px 0;border:2px dashed #ff7b00;'>$otp</div>
                    <p style='color:#888;font-size:13px;'>Valid for <strong>5 minutes</strong>. Do not share this OTP with anyone.</p>
                </div>
            ";

            if(!sendMail($email, $subject, $body)){
                $error = "OTP Email Failed. Please check SMTP settings.";
            } else {
                header("Location: verify-otp.php"); exit;
            }
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "Admin account not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Droppers Café</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html, body { height:100%; }
body { font-family:'Poppins',sans-serif; background:#0d0d0d; color:#fff; min-height:100vh; display:flex; }

.left-panel {
    flex:1; display:flex; flex-direction:column;
    align-items:center; justify-content:center; padding:60px 40px;
    background:linear-gradient(135deg, #0a0a0a 0%, #0f1a0a 50%, #0a0a0a 100%);
    position:relative; overflow:hidden;
}
.left-panel::before {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse at 30% 40%, rgba(255,123,0,0.08) 0%, transparent 60%),
               radial-gradient(ellipse at 70% 80%, rgba(255,165,0,0.04) 0%, transparent 50%);
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
.brand-name { font-size:26px; font-weight:800; margin-bottom:4px; }
.brand-name span { color:#ff7b00; }
.brand-badge {
    display:inline-flex; align-items:center; gap:6px;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.3);
    padding:4px 14px; border-radius:20px; font-size:12px;
    color:#ff7b00; font-weight:600; margin:10px 0 36px;
}
.info-list { display:flex; flex-direction:column; gap:12px; text-align:left; }
.info-item {
    display:flex; align-items:center; gap:14px;
    background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06);
    border-radius:12px; padding:14px 16px;
}
.info-icon { width:38px; height:38px; border-radius:10px; flex-shrink:0; background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.2); display:flex; align-items:center; justify-content:center; font-size:17px; }
.info-item h5 { font-size:13px; font-weight:600; margin-bottom:2px; }
.info-item p  { font-size:11px; color:#666; }

.right-panel {
    width:480px; flex-shrink:0; background:#111;
    border-left:1px solid rgba(255,255,255,0.06);
    display:flex; align-items:center; justify-content:center; padding:48px 40px;
}
.form-wrap { width:100%; max-width:380px; }
.form-header { margin-bottom:30px; }
.form-header h2 { font-size:26px; font-weight:800; margin-bottom:6px; }
.form-header h2 span { color:#ff7b00; }
.form-header p { color:#666; font-size:13px; }

.alert-error { display:flex; align-items:center; gap:10px; background:rgba(231,76,60,0.08); border:1px solid rgba(231,76,60,0.3); color:#e74c3c; padding:13px 16px; border-radius:12px; font-size:13px; margin-bottom:20px; }

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
.toggle-pw { position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#444; cursor:pointer; font-size:14px; background:none; border:none; transition:0.2s; }
.toggle-pw:hover { color:#ff7b00; }

.btn-submit {
    width:100%; padding:14px;
    background:linear-gradient(135deg, #ff7b00, #ff9500);
    color:#fff; border:none; border-radius:14px;
    font-size:15px; font-weight:700; cursor:pointer;
    font-family:'Poppins',sans-serif; transition:all 0.3s;
    box-shadow:0 8px 24px rgba(255,123,0,0.3);
    display:flex; align-items:center; justify-content:center; gap:8px; margin-top:4px;
}
.btn-submit:hover { transform:translateY(-2px); box-shadow:0 12px 32px rgba(255,123,0,0.45); }

.form-footer { margin-top:20px; text-align:center; }
.form-footer a { color:#ff7b00; font-size:13px; font-weight:500; }
.form-footer a:hover { text-decoration:underline; }

@media(max-width:900px){ .left-panel{ display:none; } .right-panel{ width:100%; border-left:none; } }
</style>
</head>
<body>

<div class="left-panel">
    <div class="left-inner">
        <div class="brand-logo"><img src="../assets/images/droppers-logo.png" alt="Logo"></div>
        <h1 class="brand-name">Droppers <span>Café</span></h1>
        <div class="brand-badge"><i class="fa fa-shield-halved"></i> Admin Panel</div>
        <div class="info-list">
            <div class="info-item">
                <div class="info-icon">📊</div>
                <div><h5>Dashboard Overview</h5><p>Real-time stats, revenue & orders</p></div>
            </div>
            <div class="info-item">
                <div class="info-icon">🛒</div>
                <div><h5>Order Management</h5><p>View, update & track all orders</p></div>
            </div>
            <div class="info-item">
                <div class="info-icon">🍕</div>
                <div><h5>Menu Control</h5><p>Add, edit, toggle stock items</p></div>
            </div>
            <div class="info-item">
                <div class="info-icon">🪑</div>
                <div><h5>Table Bookings</h5><p>Manage reservations & confirmations</p></div>
            </div>
        </div>
    </div>
</div>

<div class="right-panel">
    <div class="form-wrap">
        <div class="form-header">
            <h2>Admin <span>Login</span></h2>
            <p>Sign in to access the Droppers Café admin panel</p>
        </div>

        <?php if($error): ?>
        <div class="alert-error"><i class="fa fa-circle-exclamation"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="fg">
                <label>Username</label>
                <div class="input-wrap">
                    <i class="icon fa fa-user-shield"></i>
                    <input type="text" name="username" placeholder="Admin username"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="fg">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="icon fa fa-lock"></i>
                    <input type="password" name="password" id="pw" placeholder="Admin password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw()">
                        <i class="fa fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" name="login" class="btn-submit">
                <i class="fa fa-right-to-bracket"></i> Login & Send OTP
            </button>
        </form>

        <div class="form-footer">
            <a href="forgot-password.php"><i class="fa fa-key"></i> Forgot Password?</a>
        </div>
    </div>
</div>

<script>
function togglePw(){
    var input = document.getElementById('pw');
    var icon  = document.getElementById('eyeIcon');
    if(input.type==='password'){ input.type='text'; icon.className='fa fa-eye-slash'; }
    else{ input.type='password'; icon.className='fa fa-eye'; }
}
</script>
</body>
</html>