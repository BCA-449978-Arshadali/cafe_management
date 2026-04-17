<?php
require_once 'includes/auth-check.php';
include '../config.php';

// ✅ Profile pic column ensure
$conn->query("ALTER TABLE customers ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT NULL");

// ✅ Profile picture upload
if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0){
    $ext     = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if(in_array($ext, $allowed) && $_FILES['profile_pic']['size'] < 3*1024*1024){
        $dir = '../assets/uploads/profiles/';
        if(!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = 'profile_' . $_SESSION['customer_id'] . '.' . $ext;
        if(move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dir . $filename)){
            $pic_path = 'assets/uploads/profiles/' . $filename;
            $pstmt = $conn->prepare("UPDATE customers SET profile_pic=? WHERE id=?");
            $pstmt->bind_param("si", $pic_path, $_SESSION['customer_id']);
            $pstmt->execute();
            // ✅ SESSION UPDATE — navbar me turant reflect hoga
            $_SESSION['customer_pic'] = BASE_URL . '/' . $pic_path;
            header("Location: profile.php");
            exit;
        }
    }
}

// Update profile
if(isset($_POST['update'])){
    $name   = trim($_POST['name']);
    $mobile = trim($_POST['mobile']);
    $pw_error = '';

    if(!empty($_POST['new_password'])){

        // ✅ FIX: Pehle old password verify karo
        $old_password = $_POST['old_password'] ?? '';

        if(empty($old_password)){
            $pw_error = "Password change ke liye pehle purana password daalo!";
        } else {
            // DB se current password fetch karo
            $chk = $conn->prepare("SELECT password FROM customers WHERE id=?");
            $chk->bind_param("i", $_SESSION['customer_id']);
            $chk->execute();
            $chk_row = $chk->get_result()->fetch_assoc();

            if(!password_verify($old_password, $chk_row['password'])){
                $pw_error = "Purana password galat hai!";
            } elseif(strlen($_POST['new_password']) < 6){
                $pw_error = "Naya password kam se kam 6 characters ka hona chahiye!";
            }
        }

        if(empty($pw_error)){
            $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE customers SET name=?, mobile=?, password=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $mobile, $new_pass, $_SESSION['customer_id']);
            $stmt->execute();
            $_SESSION['customer_name']  = $name;
            $_SESSION['customer_phone'] = $mobile;
            $success = "Profile aur password successfully update ho gaya!";
        } else {
            $error_pw = $pw_error;
        }

    } else {
        $stmt = $conn->prepare("UPDATE customers SET name=?, mobile=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $mobile, $_SESSION['customer_id']);
        $stmt->execute();
        $_SESSION['customer_name']  = $name;
        $_SESSION['customer_phone'] = $mobile;
        $success = "Profile updated successfully!";
    }
}

// Fetch customer data
$stmt = $conn->prepare("SELECT * FROM customers WHERE id=?");
$stmt->bind_param("i", $_SESSION['customer_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ✅ Session me pic sync karo (agar session me nahi hai lekin DB me hai)
if(empty($_SESSION['customer_pic']) && !empty($user['profile_pic'])){
    $_SESSION['customer_pic'] = BASE_URL . '/' . $user['profile_pic'];
}

// My Table Bookings
$cust_id = $_SESSION['customer_id'];
$bk_stmt = $conn->prepare("SELECT * FROM bookings WHERE customer_id=? ORDER BY date DESC, time DESC LIMIT 10");
$bk_stmt->bind_param("i", $cust_id);
$bk_stmt->execute();
$my_bookings = $bk_stmt->get_result();

// Order history
$name_e  = mysqli_real_escape_string($conn, $user['name']);
$phone_e = mysqli_real_escape_string($conn, $user['mobile'] ?? '');
$orders  = mysqli_query($conn,
    "SELECT * FROM orders
     WHERE customer_name='$name_e'
     OR (customer_phone='$phone_e' AND customer_phone != '')
     ORDER BY id DESC LIMIT 10"
);

$bk_rows    = $my_bookings->fetch_all(MYSQLI_ASSOC);
$order_rows = mysqli_fetch_all($orders, MYSQLI_ASSOC);
$name_parts  = explode(' ', trim($user['name']));
$initials    = strtoupper(substr($name_parts[0], 0, 1));
if(count($name_parts) > 1) $initials .= strtoupper(substr(end($name_parts), 0, 1));
$total_spent = array_sum(array_column($order_rows, 'total_amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile – Droppers Café</title>
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #0d0d0d; color: #fff; font-family: 'Poppins', sans-serif; min-height: 100vh; }
a { text-decoration: none; }

/* ── Page ── */
.profile-page { max-width: 1080px; margin: 0 auto; padding: 32px 20px 80px; }

/* ── Hero Banner ── */
.profile-hero {
    position: relative;
    background: linear-gradient(135deg, #111 0%, #0f1f0f 50%, #111 100%);
    border: 1px solid rgba(255,123,0,0.12);
    border-radius: 24px; padding: 40px 36px; margin-bottom: 24px;
    overflow: hidden; display: flex; align-items: center;
    justify-content: space-between; gap: 24px; flex-wrap: wrap;
}
.hero-left {
    display: flex; align-items: center; gap: 20px;
    justify-content: flex-start; flex-shrink: 0;
    position: relative; z-index: 1;
}
.profile-hero::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse at 10% 50%, rgba(255,123,0,0.08) 0%, transparent 55%),
                radial-gradient(ellipse at 90% 10%, rgba(255,200,0,0.04) 0%, transparent 45%);
    pointer-events: none;
}
.hero-avatar {
    width: 96px; height: 96px;
    background: linear-gradient(135deg, #ff7b00, #ff9500);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 32px; font-weight: 800; color: #fff; flex-shrink: 0;
    box-shadow: 0 0 0 4px rgba(255,123,0,0.2), 0 0 32px rgba(255,123,0,0.3);
    animation: pulse-av 3s ease-in-out infinite;
    cursor: pointer; position: relative; overflow: hidden; letter-spacing: 1px;
}
.hero-avatar img { width:100%; height:100%; object-fit:cover; border-radius:50%; }
.avatar-overlay {
    position:absolute; inset:0; background:rgba(0,0,0,0.55);
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    opacity:0; transition:0.25s; border-radius:50%; font-size:16px; color:#fff; gap:2px;
}
.avatar-overlay span { font-size:9px; font-weight:700; letter-spacing:0.5px; }
.hero-avatar:hover .avatar-overlay { opacity:1; }
.hero-info { min-width:0; display:flex; flex-direction:column; justify-content:center; align-items:flex-start; }
@keyframes pulse-av {
    0%,100% { box-shadow: 0 0 0 4px rgba(255,123,0,0.2), 0 0 24px rgba(255,123,0,0.25); }
    50%      { box-shadow: 0 0 0 4px rgba(255,123,0,0.35), 0 0 44px rgba(255,123,0,0.4); }
}
.hero-info { min-width: 0; }
.hero-info h1 { font-size: 26px; font-weight: 800; margin-bottom: 4px; }
.hero-info .hero-email { color: #555; font-size: 13px; margin-bottom: 14px; }
.hero-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.hbadge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 30px; font-size: 11px; font-weight: 600;
    background: rgba(255,123,0,0.08); border: 1px solid rgba(255,123,0,0.2); color: #ff7b00;
}
.hbadge.green { background: rgba(39,174,96,0.08); border-color: rgba(39,174,96,0.25); color: #27ae60; }
.hbadge.muted { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); color: #666; }
.hero-stats {
    display: flex; background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07); border-radius: 14px;
    overflow: hidden; flex-shrink: 0; position: relative; z-index: 1;
    align-self: center;
}
.hstat { padding: 18px 26px; text-align: center; border-right: 1px solid rgba(255,255,255,0.07); }
.hstat:last-child { border-right: none; }
.hstat-n { font-size: 22px; font-weight: 800; color: #ff7b00; line-height: 1; }
.hstat-l { font-size: 11px; color: #555; margin-top: 4px; }

/* ── Grid ── */
.profile-grid { display: grid; grid-template-columns: 310px 1fr; gap: 20px; }

/* ── Card ── */
.pcard {
    background: #111; border: 1px solid rgba(255,255,255,0.07);
    border-radius: 20px; padding: 26px;
}
.pcard-title {
    font-size: 14px; font-weight: 700; color: #ff7b00;
    margin-bottom: 20px; padding-bottom: 14px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    display: flex; align-items: center; gap: 8px;
}

/* ── Info Rows ── */
.info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 11px 0; border-bottom: 1px solid rgba(255,255,255,0.05);
    font-size: 13px; gap: 12px;
}
.info-row:last-child { border-bottom: none; }
.info-lbl { color: #555; display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.info-lbl i { width: 14px; color: #ff7b00; font-size: 12px; }
.info-val { color: #ddd; font-weight: 600; text-align: right; word-break: break-all; }

/* ── Form ── */
.fg { margin-bottom: 14px; }
.fg label { display: block; font-size: 11px; color: #555; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
.iw { position: relative; }
.iw .fi { position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
    color: #333; font-size: 12px; pointer-events: none; }
.fg input {
    width: 100%; padding: 11px 14px 11px 38px;
    background: #0d0d0d; border: 1px solid rgba(255,255,255,0.08);
    border-radius: 11px; color: #fff; font-size: 13px;
    font-family: 'Poppins', sans-serif; outline: none; transition: 0.2s;
}
.fg input:focus { border-color: rgba(255,123,0,0.45); }
.fg input:disabled { opacity: 0.3; cursor: not-allowed; }
.tpw {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: #444; cursor: pointer; font-size: 13px; transition: 0.2s;
}
.tpw:hover { color: #ff7b00; }
.btn-save {
    width: 100%; padding: 12px;
    background: linear-gradient(135deg, #ff7b00, #ff9500);
    color: #fff; border: none; border-radius: 11px;
    font-size: 14px; font-weight: 700; cursor: pointer;
    font-family: 'Poppins', sans-serif; transition: all 0.3s;
    box-shadow: 0 6px 20px rgba(255,123,0,0.2);
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(255,123,0,0.38); }
.alert-ok {
    background: rgba(39,174,96,0.08); border: 1px solid rgba(39,174,96,0.28);
    color: #27ae60; padding: 10px 14px; border-radius: 10px;
    font-size: 13px; margin-bottom: 14px;
    display: flex; align-items: center; gap: 8px;
}

/* ── Tabs ── */
.tab-bar {
    display: flex; gap: 0;
    background: #0d0d0d; border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px; padding: 5px; margin-bottom: 16px;
}
.tbtn {
    flex: 1; padding: 9px 14px; border: none;
    background: none; color: #555; font-size: 13px; font-weight: 600;
    font-family: 'Poppins', sans-serif; cursor: pointer;
    border-radius: 9px; transition: all 0.22s;
    display: flex; align-items: center; justify-content: center; gap: 7px;
}
.tbtn.active { background: rgba(255,123,0,0.12); color: #ff7b00; }
.tcnt {
    background: rgba(255,123,0,0.18); color: #ff7b00;
    font-size: 10px; padding: 2px 7px; border-radius: 20px; font-weight: 700;
}
.tab-panel { display: none; }
.tab-panel.active { display: block; }

/* ── History Rows ── */
.hrow {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05);
    gap: 12px; flex-wrap: wrap;
}
.hrow:last-child { border-bottom: none; }
.hrow-id   { font-size: 13px; font-weight: 700; color: #ddd; margin-bottom: 3px; }
.hrow-sub  { font-size: 12px; color: #444; line-height: 1.6; }
.hrow-tag  { font-size: 11px; color: #ff7b00; margin-top: 2px; }
.hrow-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; flex-shrink: 0; }
.price { color: #ff7b00; font-weight: 800; font-size: 15px; }

/* Badges */
.bdg { padding: 4px 11px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.bdg-pend { background: rgba(240,165,0,0.1);  border: 1px solid rgba(240,165,0,0.25);  color: #f0a500; }
.bdg-prep { background: rgba(52,152,219,0.1); border: 1px solid rgba(52,152,219,0.25); color: #3498db; }
.bdg-done { background: rgba(39,174,96,0.1);  border: 1px solid rgba(39,174,96,0.25);  color: #27ae60; }
.bdg-canc { background: rgba(231,76,60,0.1);  border: 1px solid rgba(231,76,60,0.25);  color: #e74c3c; }

.track-btn {
    background: rgba(255,123,0,0.1); color: #ff7b00;
    padding: 4px 12px; border-radius: 8px; font-size: 11px;
    font-weight: 700; border: 1px solid rgba(255,123,0,0.2); transition: 0.2s;
}
.track-btn:hover { background: rgba(255,123,0,0.2); color: #ff7b00; }

.reorder-btn {
    background: rgba(39,174,96,0.1); color: #27ae60;
    padding: 4px 12px; border-radius: 8px; font-size: 11px;
    font-weight: 700; border: 1px solid rgba(39,174,96,0.25); transition: 0.2s;
    display: inline-flex; align-items: center; gap: 4px;
}
.reorder-btn:hover { background: rgba(39,174,96,0.2); color: #27ae60; }

.empty { text-align: center; padding: 36px 20px; }
.empty i { font-size: 34px; color: #1e1e1e; margin-bottom: 10px; display: block; }
.empty p { color: #333; font-size: 13px; }
.empty a { color: #ff7b00; font-weight: 600; }

/* ── Responsive ── */
@media (max-width: 820px) {
    .profile-grid { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .profile-hero { padding: 24px 18px; gap: 18px; }
    .hero-stats { width: 100%; }
    .hstat { flex: 1; padding: 14px 8px; }
    .profile-page { padding: 18px 14px 60px; }
    .pcard { padding: 20px 16px; }
    .hero-info h1 { font-size: 20px; }
    .hero-avatar { width: 76px; height: 76px; font-size: 30px; }
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="profile-page">

    <!-- Hero -->
    <div class="profile-hero">
        <div class="hero-left">
        <!-- Avatar — click to change photo -->
        <form method="POST" enctype="multipart/form-data" id="picForm">
            <div class="hero-avatar" onclick="document.getElementById('picInput').click()" title="Click to change photo">
                <?php if(!empty($user['profile_pic']) && file_exists('../' . $user['profile_pic'])): ?>
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($user['profile_pic']) ?>?v=<?= time() ?>" alt="Profile">
                <?php else: ?>
                    <?= $initials ?>
                <?php endif; ?>
                <div class="avatar-overlay">
                    <i class="fa fa-camera"></i>
                    <span>Change</span>
                </div>
            </div>
            <input type="file" id="picInput" name="profile_pic" accept="image/*" style="display:none"
                onchange="document.getElementById('picForm').submit()">
        </form>
        <div class="hero-info">
            <h1><?= htmlspecialchars($user['name']) ?></h1>
            <p class="hero-email"><?= htmlspecialchars($user['email']) ?></p>
            <div class="hero-badges">
                <span class="hbadge"><i class="fa fa-star"></i> Member</span>
                <?php if($user['is_verified']): ?>
                <span class="hbadge green"><i class="fa fa-circle-check"></i> Verified</span>
                <?php else: ?>
                <span class="hbadge muted"><i class="fa fa-circle-xmark"></i> Not Verified</span>
                <?php endif; ?>
                <?php if($user['mobile']): ?>
                <span class="hbadge muted"><i class="fa fa-phone"></i> <?= htmlspecialchars($user['mobile']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        </div><!-- /hero-left -->
        <div class="hero-stats">
            <div class="hstat">
                <div class="hstat-n"><?= count($order_rows) ?></div>
                <div class="hstat-l">Orders</div>
            </div>
            <div class="hstat">
                <div class="hstat-n"><?= count($bk_rows) ?></div>
                <div class="hstat-l">Bookings</div>
            </div>
            <div class="hstat">
                <div class="hstat-n">₹<?= number_format($total_spent) ?></div>
                <div class="hstat-l">Spent</div>
            </div>
        </div>
    </div>

    <!-- Grid -->
    <div class="profile-grid">

        <!-- Left Column -->
        <div style="display:flex; flex-direction:column; gap:20px;">

            <!-- Info -->
            <div class="pcard">
                <div class="pcard-title"><i class="fa fa-id-card"></i> Account Info</div>
                <div class="info-row">
                    <span class="info-lbl"><i class="fa fa-envelope"></i> Email</span>
                    <span class="info-val"><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-lbl"><i class="fa fa-phone"></i> Mobile</span>
                    <span class="info-val"><?= $user['mobile'] ? htmlspecialchars($user['mobile']) : '—' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-lbl"><i class="fa fa-shield-halved"></i> Status</span>
                    <span class="info-val" style="color:<?= $user['is_verified'] ? '#27ae60' : '#e74c3c' ?>">
                        <?= $user['is_verified'] ? '✓ Verified' : '✗ Unverified' ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-lbl"><i class="fa fa-bag-shopping"></i> Orders</span>
                    <span class="info-val"><?= count($order_rows) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-lbl"><i class="fa fa-chair"></i> Bookings</span>
                    <span class="info-val"><?= count($bk_rows) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-lbl"><i class="fa fa-indian-rupee-sign"></i> Total Spent</span>
                    <span class="info-val" style="color:#ff7b00;">₹<?= number_format($total_spent) ?></span>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="pcard">
                <div class="pcard-title"><i class="fa fa-pen-to-square"></i> Edit Profile</div>

                <?php if(isset($success)): ?>
                <div class="alert-ok"><i class="fa fa-circle-check"></i> <?= $success ?></div>
                <?php endif; ?>

                <?php if(isset($error_pw)): ?>
                <div style="background:rgba(231,76,60,0.08);border:1px solid rgba(231,76,60,0.3);color:#e74c3c;padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                    <i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error_pw) ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="fg">
                        <label>Full Name</label>
                        <div class="iw">
                            <i class="fi fa fa-user"></i>
                            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                    </div>
                    <div class="fg">
                        <label>Mobile Number</label>
                        <div class="iw">
                            <i class="fi fa fa-phone"></i>
                            <input type="tel" name="mobile" value="<?= htmlspecialchars($user['mobile'] ?? '') ?>" placeholder="+91 XXXXX XXXXX">
                        </div>
                    </div>
                    <div class="fg">
                        <label>Email <span style="font-size:10px;color:#333;">(cannot change)</span></label>
                        <div class="iw">
                            <i class="fi fa fa-envelope"></i>
                            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        </div>
                    </div>

                    <!-- ✅ FIX: Old password field — password change ke liye zaroori -->
                    <div class="fg" id="oldPwField" style="display:none;">
                        <label>Current Password <span style="font-size:10px;color:#e74c3c;">*required</span></label>
                        <div class="iw">
                            <i class="fi fa fa-lock"></i>
                            <input type="password" name="old_password" id="oldPw" placeholder=" old password">
                            <button type="button" class="tpw" onclick="toggleOldPw()"><i class="fa fa-eye" id="oldPwEye"></i></button>
                        </div>
                    </div>

                    <div class="fg">
                        <label>New Password <span style="font-size:10px;color:#333;">(blank = keep same)</span></label>
                        <div class="iw">
                            <i class="fi fa fa-lock"></i>
                            <input type="password" name="new_password" id="newPw" placeholder="Min 6 characters" oninput="toggleOldPwField(this.value)">
                            <button type="button" class="tpw" onclick="togglePw()"><i class="fa fa-eye" id="pwEye"></i></button>
                        </div>
                    </div>
                    <button type="submit" name="update" class="btn-save">
                        <i class="fa fa-floppy-disk"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Tabs -->
        <div class="pcard">
            <div class="tab-bar">
                <button class="tbtn active" onclick="switchTab('orders',this)">
                    <i class="fa fa-bag-shopping"></i> Orders <span class="tcnt"><?= count($order_rows) ?></span>
                </button>
                <button class="tbtn" onclick="switchTab('bookings',this)">
                    <i class="fa fa-chair"></i> Bookings <span class="tcnt"><?= count($bk_rows) ?></span>
                </button>
            </div>

            <!-- Orders -->
            <div class="tab-panel active" id="tab-orders">
                <?php if(empty($order_rows)): ?>
                <div class="empty"><i class="fa fa-bag-shopping"></i><p>No orders yet! <a href="menu.php">Browse menu →</a></p></div>
                <?php else: foreach($order_rows as $o):
                    $st = $o['status'];
                    $bdg = match(strtolower($st)) { 'pending'=>'bdg-pend','preparing'=>'bdg-prep','completed'=>'bdg-done','cancelled'=>'bdg-canc', default=>'bdg-pend' };
                    $ico = match(strtolower($st)) { 'pending'=>'⏳','preparing'=>'🍳','completed'=>'✅','cancelled'=>'❌', default=>'⏳' };
                ?>
                <div class="hrow">
                    <div>
                        <div class="hrow-id">#<?= $o['id'] ?> &nbsp;·&nbsp; <?= date('d M Y', strtotime($o['order_time'])) ?></div>
                        <div class="hrow-sub"><?= htmlspecialchars(mb_strimwidth($o['items'], 0, 55, '…')) ?></div>
                        <div class="hrow-sub"><?= date('h:i A', strtotime($o['order_time'])) ?></div>
                        <?php if(!empty($o['order_type'])): ?>
                        <div class="hrow-tag"><i class="fa fa-tag"></i> <?= htmlspecialchars($o['order_type']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="hrow-right">
                        <span class="price">₹<?= $o['total_amount'] ?></span>
                        <span class="bdg <?= $bdg ?>"><?= $ico ?> <?= $st ?></span>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
                            <a href="track_order.php?id=<?= $o['id'] ?>" class="track-btn">
                                <i class="fa fa-location-dot"></i> Track
                            </a>
                            <a href="reorder.php?id=<?= $o['id'] ?>" class="reorder-btn"
                                onclick="event.preventDefault(); showConfirm('Add all items from Order #<?= $o['id'] ?> to your cart?', function(){ window.location.href='reorder.php?id=<?= $o['id'] ?>'; }, {icon:'🛒', okText:'Yes, Add to Cart'});"                                <i class="fa fa-rotate-right"></i> Reorder
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- Bookings -->
            <div class="tab-panel" id="tab-bookings">
                <?php if(empty($bk_rows)): ?>
                <div class="empty"><i class="fa fa-chair"></i><p>No bookings yet! <a href="book_table.php">Book a table →</a></p></div>
                <?php else: foreach($bk_rows as $bk):
                    $bst = $bk['status'] ?? 'Pending';
                    $dur = $bk['duration'] ?? 60;
                    $end_t = date('h:i A', strtotime($bk['time']) + ($dur*60));
                    $bbdg = match($bst) { 'Confirmed'=>'bdg-done','Cancelled'=>'bdg-canc', default=>'bdg-pend' };
                    $bico = match($bst) { 'Confirmed'=>'✅','Cancelled'=>'❌', default=>'⏳' };
                ?>
                <div class="hrow">
                    <div>
                        <div class="hrow-id">🪑 Table <?= $bk['table_no'] ?> &nbsp;·&nbsp; <?= date('d M Y', strtotime($bk['date'])) ?></div>
                        <div class="hrow-sub"><i class="fa fa-clock"></i> <?= date('h:i A', strtotime($bk['time'])) ?> – <?= $end_t ?></div>
                        <div class="hrow-sub"><?= $dur ?> min<?php if(!empty($bk['guests'])): ?> &nbsp;·&nbsp; <?= $bk['guests'] ?> guest(s)<?php endif; ?></div>
                    </div>
                    <div class="hrow-right">
                        <span class="bdg <?= $bbdg ?>"><?= $bico ?> <?= $bst ?></span>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tbtn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
function togglePw() {
    var i = document.getElementById('newPw'), e = document.getElementById('pwEye');
    i.type = i.type === 'password' ? 'text' : 'password';
    e.className = i.type === 'text' ? 'fa fa-eye-slash' : 'fa fa-eye';
}
// ✅ FIX: Old password field — new password type karne par show hoga
function toggleOldPwField(val) {
    var field = document.getElementById('oldPwField');
    var input = document.getElementById('oldPw');
    if(val.length > 0){
        field.style.display = 'block';
        input.required = true;
    } else {
        field.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}
function toggleOldPw() {
    var i = document.getElementById('oldPw'), e = document.getElementById('oldPwEye');
    i.type = i.type === 'password' ? 'text' : 'password';
    e.className = i.type === 'text' ? 'fa fa-eye-slash' : 'fa fa-eye';
}
</script>
</body>
</html>