<?php
session_start();
include "../config.php";

// ✅ Login check
if(!isset($_SESSION['customer_id'])){
    header("Location: " . BASE_URL . "/customer/login.php");
    exit();
}

$cust_id    = $_SESSION['customer_id'];
$cust_name  = $_SESSION['customer_name']  ?? '';
$cust_email = $_SESSION['customer_email'] ?? '';
$cust_phone = $_SESSION['customer_phone'] ?? '';

$sel_date  = $_POST['date']  ?? date('Y-m-d');
$sel_table = intval($_POST['table'] ?? 1);

// User ki latest booking ka real status check karo
$latest_booking = null;
$bq = $conn->prepare("SELECT table_no, date, time, status FROM bookings WHERE customer_id=? ORDER BY id DESC LIMIT 1");
$bq->bind_param("i", $cust_id);
$bq->execute();
$latest_booking = $bq->get_result()->fetch_assoc();

function getOccupiedSlots($conn, $table, $date){
    $occupied = [];
    $query = $conn->prepare("
        SELECT time, duration FROM bookings 
        WHERE table_no=? AND date=? AND status='Confirmed'
    ");
    $query->bind_param("is", $table, $date);
    $query->execute();
    $result = $query->get_result();
    while($row = $result->fetch_assoc()){
        // ✅ Date bhi include karo warna future dates pe timestamp match fail hota hai
        $start = strtotime($date . ' ' . $row['time']);
        $end   = $start + ($row['duration'] * 60);
        $occupied[] = ['start' => $start, 'end' => $end];
    }
    return $occupied;
}

function hasOverlap($occupied, $new_start, $new_duration){
    $new_end = $new_start + ($new_duration * 60);
    foreach($occupied as $slot){
        if($new_start < $slot['end'] && $new_end > $slot['start']){
            return true;
        }
    }
    return false;
}

// ✅ Ensure customer_id column exists in bookings
$col_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'customer_id'");
if($col_check->num_rows == 0){
    $conn->query("ALTER TABLE bookings ADD COLUMN customer_id INT DEFAULT NULL AFTER id");
}
// ✅ Ensure email column exists
$col_check2 = $conn->query("SHOW COLUMNS FROM bookings LIKE 'email'");
if($col_check2->num_rows == 0){
    $conn->query("ALTER TABLE bookings ADD COLUMN email VARCHAR(255) DEFAULT '' AFTER phone");
}

$occupied = getOccupiedSlots($conn, $sel_table, $sel_date);

if(isset($_POST['book'])){
    $name      = trim($_POST['name'] ?? $cust_name);
    $phone     = trim($_POST['phone'] ?? $cust_phone);
    $table     = intval($_POST['table']);
    $date      = $_POST['date'];
    $time      = $_POST['time'];
    $duration  = intval($_POST['duration'] ?? 60);
    $new_start = strtotime("$date $time");

    if(!preg_match('/^[a-zA-Z\s]+$/', $name)){
        $error = "Name mein sirf alphabets allowed hain!";
    } elseif(!preg_match('/^[0-9]{10}$/', $phone)){
        $error = "Phone number exactly 10 digits ka hona chahiye!";
    } elseif($date < date('Y-m-d')){
        $error = "Please select today or a future date!";
    } elseif(empty($time)){
        $error = "Please select a time!";
    } elseif($date == date('Y-m-d') && strtotime("$date $time") <= time()){
        $error = "Aaj ka past time select nahi kar sakte! Future time slot choose karein.";
    } elseif(hasOverlap($occupied, $new_start, $duration)){
        $error = "Table $table is already booked during this time slot! Please choose a different time or duration.";
    } else {
        $name_e  = mysqli_real_escape_string($conn, $name);
        $phone_e = mysqli_real_escape_string($conn, $phone);

        $stmt = $conn->prepare("INSERT INTO bookings(customer_id, name, phone, email, table_no, date, time, duration, status) VALUES(?,?,?,?,?,?,?,?,'Pending')");
        $stmt->bind_param("issssssi", $cust_id, $name_e, $phone_e, $cust_email, $table, $date, $time, $duration);
        $stmt->execute();

        $end_time  = date('h:i A', strtotime($time) + $duration * 60);
        $start_fmt = date('h:i A', strtotime($time));
        $date_fmt  = date('d M Y', strtotime($date));
        $msg = "Booking request sent for Table $table on $date_fmt from $start_fmt to $end_time. Waiting for admin confirmation!";

        if(!empty($cust_email)){
            require_once __DIR__ . '/includes/mailer.php';
            $subject = "Booking Request Received - Droppers Café";
            $body = "
                <div style='font-family:Arial,sans-serif; max-width:520px; margin:0 auto; background:#f9f9f9; padding:24px; border-radius:10px;'>
                    <h2 style='color:#ff7b00; margin-top:0;'>🪑 Table Booking Request Received!</h2>
                    <p style='color:#555;'>Hi <strong>$name</strong>, we have received your booking request. Admin will confirm it shortly.</p>
                    <table style='width:100%; border-collapse:collapse; margin-top:16px;'>
                        <tr><td style='padding:10px; background:#fff; border:1px solid #eee; font-weight:bold; width:35%;'>📅 Date</td><td style='padding:10px; background:#fff; border:1px solid #eee;'>$date_fmt</td></tr>
                        <tr><td style='padding:10px; background:#fff; border:1px solid #eee; font-weight:bold;'>⏰ Time</td><td style='padding:10px; background:#fff; border:1px solid #eee;'>$start_fmt – $end_time</td></tr>
                        <tr><td style='padding:10px; background:#fff; border:1px solid #eee; font-weight:bold;'>🪑 Table</td><td style='padding:10px; background:#fff; border:1px solid #eee;'>Table $table</td></tr>
                        <tr><td style='padding:10px; background:#fff; border:1px solid #eee; font-weight:bold;'>📞 Phone</td><td style='padding:10px; background:#fff; border:1px solid #eee;'>$phone</td></tr>
                    </table>
                    <p style='margin-top:20px; color:#888; font-size:13px;'>Status: <strong style='color:#856404;'>⏳ Pending — Waiting for admin approval</strong></p>
                    <p style='color:#888; font-size:12px;'>📍 Droppers Café & Resto, Bheldi Road, Amnour, Bihar<br>📞 +91 70048 10081</p>
                </div>
            ";
            sendMail($cust_email, $subject, $body);
        }

        $occupied = getOccupiedSlots($conn, $sel_table, $sel_date);
    }
}

$all_slots = [];
for($h = 10; $h <= 22; $h++){
    $all_slots[] = sprintf('%02d:00', $h);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Table - Droppers Café</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/customer.css">

<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
body { background:#0d0d0d; color:#fff; font-family:'Poppins',sans-serif; overflow-x:hidden; }
a { text-decoration:none; }

/* ===== PAGE HERO ===== */
.page-hero {
    background: linear-gradient(135deg, #0a0a0a 0%, #0f1f0f 50%, #0a0a0a 100%);
    padding: 80px 0 50px;
    position: relative;
    overflow: hidden;
    border-bottom: 1px solid rgba(255,123,0,0.1);
}
.page-hero::before {
    content:'';
    position:absolute; inset:0;
    background: radial-gradient(ellipse at 20% 50%, rgba(255,123,0,0.07) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(255,200,0,0.04) 0%, transparent 50%);
}
.page-hero .container { position:relative; z-index:2; max-width:1200px; margin:0 auto; padding:0 24px; }
.page-hero-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.3);
    padding:6px 16px; border-radius:30px;
    font-size:13px; color:#ff7b00; font-weight:600;
    margin-bottom:16px;
}
.page-hero h1 {
    font-size: clamp(32px, 4vw, 52px);
    font-weight:800; line-height:1.15; color:#fff; margin-bottom:12px;
}
.page-hero h1 span { color:#ff7b00; }
.page-hero p { color:#888; font-size:15px; max-width:500px; line-height:1.7; }

/* ===== CONTAINER ===== */
.container { max-width:1200px; margin:0 auto; padding:0 24px; }

/* ===== MAIN LAYOUT ===== */
.booking-section { padding:60px 0 80px; }
.booking-layout {
    display:grid;
    grid-template-columns:1fr 1.1fr;
    gap:32px;
    align-items:start;
}

/* ===== LEFT SIDE INFO CARD ===== */
.info-card {
    background:#111;
    border:1px solid rgba(255,255,255,0.06);
    border-radius:20px;
    overflow:hidden;
    position:sticky;
    top:100px;
}
.info-map iframe {
    width:100%; height:240px; border:none; display:block;
}
.info-body { padding:28px; }
.info-body h3 { font-size:20px; font-weight:700; margin-bottom:6px; }
.info-body h3 span { color:#ff7b00; }
.info-body > p { color:#666; font-size:13px; margin-bottom:24px; line-height:1.6; }

.info-item {
    display:flex; align-items:flex-start; gap:14px;
    padding:14px 0;
    border-bottom:1px solid rgba(255,255,255,0.05);
}
.info-item:last-of-type { border-bottom:none; }
.info-item-icon {
    width:40px; height:40px; border-radius:12px; flex-shrink:0;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.2);
    display:flex; align-items:center; justify-content:center; font-size:17px;
}
.info-item-text p { font-size:11px; color:#555; margin-bottom:2px; text-transform:uppercase; letter-spacing:0.5px; }
.info-item-text strong { font-size:13px; color:#ccc; line-height:1.5; display:block; }

.table-legend { margin-top:20px; }
.table-legend p { font-size:11px; color:#555; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:10px; font-weight:600; }
.legend-grid { display:flex; flex-wrap:wrap; gap:8px; }
.legend-dot {
    display:flex; align-items:center; gap:6px;
    font-size:12px; color:#777;
}
.dot {
    width:10px; height:10px; border-radius:50%; flex-shrink:0;
}
.dot-green  { background:#27ae60; }
.dot-red    { background:#e74c3c; }
.dot-yellow { background:#ffc107; }
.dot-gray   { background:#444; }
.dot-orange { background:#ff7b00; }

/* ===== RIGHT SIDE FORM CARD ===== */
.form-card {
    background:#111;
    border:1px solid rgba(255,255,255,0.06);
    border-radius:20px;
    padding:36px;
}
.form-card-header { margin-bottom:28px; }
.form-card-header h2 { font-size:22px; font-weight:800; margin-bottom:4px; }
.form-card-header h2 span { color:#ff7b00; }
.form-card-header p { color:#666; font-size:13px; }

/* Alerts */
.alert {
    display:flex; align-items:flex-start; gap:10px;
    padding:14px 16px; border-radius:12px;
    font-size:13px; margin-bottom:20px; line-height:1.6;
}
.alert-warning { background:rgba(255,193,7,0.08); border:1px solid rgba(255,193,7,0.3); color:#cda50a; }
.alert-error   { background:rgba(231,76,60,0.08);  border:1px solid rgba(231,76,60,0.3);  color:#e74c3c; }
.alert-success { background:rgba(39,174,96,0.08);  border:1px solid rgba(39,174,96,0.3);  color:#27ae60; }
.alert i { font-size:16px; margin-top:1px; flex-shrink:0; }

/* Form groups */
.fg { margin-bottom:18px; }
.fg label {
    display:block; font-size:12px; color:#888;
    font-weight:600; text-transform:uppercase;
    letter-spacing:0.5px; margin-bottom:8px;
}
.fg input,
.fg select {
    width:100%; padding:12px 16px;
    background:#0d0d0d; border:1px solid rgba(255,255,255,0.08);
    border-radius:12px; color:#fff; font-size:14px;
    font-family:'Poppins',sans-serif; outline:none; transition:0.2s;
}
.fg input:focus,
.fg select:focus { border-color:rgba(255,123,0,0.5); background:#0f0f0f; }
.fg select option { background:#1a1a1a; }

.form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

/* Email note */
.email-note {
    background:rgba(39,174,96,0.06); border:1px solid rgba(39,174,96,0.2);
    border-radius:10px; padding:10px 14px;
    font-size:12px; color:#666; margin-bottom:18px;
}
.email-note strong { color:#27ae60; }

/* Section divider */
.form-divider {
    font-size:11px; color:#444; text-transform:uppercase;
    letter-spacing:1px; font-weight:600;
    margin:24px 0 16px; display:flex; align-items:center; gap:12px;
}
.form-divider::before,
.form-divider::after {
    content:''; flex:1;
    height:1px; background:rgba(255,255,255,0.06);
}

/* Duration buttons */
.dur-grid { display:flex; gap:8px; flex-wrap:wrap; }
.dur-btn {
    padding:9px 18px; border-radius:10px; font-size:13px; font-weight:600;
    cursor:pointer; border:2px solid rgba(255,255,255,0.08);
    background:rgba(255,255,255,0.03); color:#666;
    transition:0.2s; font-family:'Poppins',sans-serif;
}
.dur-btn:hover { border-color:rgba(255,123,0,0.4); color:#ccc; }
.dur-btn.active { background:rgba(255,123,0,0.15); color:#ff7b00; border-color:#ff7b00; }

/* Slot grid */
.slot-grid { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
.slot-btn {
    padding:9px 14px; border-radius:10px; font-size:12px; font-weight:600;
    cursor:pointer; border:2px solid #27ae60;
    background:rgba(39,174,96,0.08); color:#27ae60;
    transition:0.2s; font-family:'Poppins',sans-serif;
}
.slot-btn:hover:not(.booked):not(.pending-slot):not(.past-slot) {
    background:#27ae60; color:#fff;
}
.slot-btn.selected  { background:#ff7b00 !important; color:#fff !important; border-color:#ff7b00 !important; }
.slot-btn.booked    { background:rgba(231,76,60,0.08); color:#e74c3c; border-color:rgba(231,76,60,0.4); cursor:not-allowed; }
.slot-btn.pending-slot { background:rgba(255,193,7,0.08); color:#cda50a; border-color:rgba(255,193,7,0.4); cursor:not-allowed; }
.slot-btn.past-slot { background:rgba(255,255,255,0.03); color:#444; border-color:rgba(255,255,255,0.06); cursor:not-allowed; }

/* Submit button */
.btn-submit {
    width:100%; padding:15px;
    background:linear-gradient(135deg, #ff7b00, #ff9500);
    color:#fff; border:none; border-radius:14px;
    font-size:15px; font-weight:700; cursor:pointer;
    font-family:'Poppins',sans-serif; margin-top:24px;
    transition:all 0.3s; box-shadow:0 8px 24px rgba(255,123,0,0.3);
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-submit:hover { transform:translateY(-2px); box-shadow:0 12px 30px rgba(255,123,0,0.45); }

/* ===== FLOATING BUTTONS ===== */
.instagram-float {
    position:fixed; bottom:144px; right:28px; z-index:999;
    width:50px; height:50px; border-radius:50%;
    background:radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);
    color:#fff; display:flex; align-items:center; justify-content:center;
    font-size:24px; box-shadow:0 4px 20px rgba(214,36,159,0.4);
    animation:pulse-ig 2s ease-in-out infinite; transition:0.3s;
}
.instagram-float:hover { transform:scale(1.1); color:#fff; }
@keyframes pulse-ig {
    0%,100% { box-shadow:0 4px 20px rgba(214,36,159,0.4); }
    50%      { box-shadow:0 4px 40px rgba(214,36,159,0.7); }
}
.whatsapp-float {
    position:fixed; bottom:84px; right:28px; z-index:999;
    width:50px; height:50px; border-radius:50%;
    background:#25D366; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:24px; box-shadow:0 4px 20px rgba(37,211,102,0.4);
    animation:pulse-wa 2s ease-in-out infinite; transition:0.3s;
}
.whatsapp-float:hover { transform:scale(1.1); color:#fff; }
@keyframes pulse-wa {
    0%,100% { box-shadow:0 4px 20px rgba(37,211,102,0.4); }
    50%      { box-shadow:0 4px 40px rgba(37,211,102,0.7); }
}
.back-to-top {
    position:fixed; bottom:28px; right:28px; z-index:999;
    width:44px; height:44px; border-radius:12px;
    background:#ff7b00; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:18px; box-shadow:0 4px 16px rgba(255,123,0,0.4);
    opacity:0; pointer-events:none; transition:0.3s;
}
.back-to-top.show { opacity:1; pointer-events:auto; }
.back-to-top:hover { background:#e06900; transform:translateY(-3px); color:#fff; }

/* ===== RESPONSIVE ===== */
@media(max-width:900px){
    .booking-layout { grid-template-columns:1fr; }
    .info-card { position:static; }
}
@media(max-width:600px){
    .form-row { grid-template-columns:1fr; }
    .form-card { padding:24px; }
    .page-hero { padding:70px 0 40px; }
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Page Hero -->
<div class="page-hero">
    <div class="container">
        <div class="page-hero-badge">
            <i class="fa fa-calendar-check"></i> Reservation
        </div>
        <h1>Book Your <span>Table</span></h1>
        <p>Reserve your favourite spot in advance — no waiting, just arrive and enjoy your meal at Droppers Café.</p>
    </div>
</div>

<!-- Booking Section -->
<section class="booking-section">
    <div class="container">
        <div class="booking-layout">

            <!-- LEFT: Info Card -->
            <div class="info-card">
                <div class="info-map">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3586.917607304979!2d84.9256401!3d25.970742400000002!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3992cb55d2eb821d%3A0xbe19d4929a2613a3!2sDroppers%20Cafe%20%26%20Resto!5e0!3m2!1sen!2sin!4v1769311847759!5m2!1sen!2sin"
                        allowfullscreen loading="lazy">
                    </iframe>
                </div>
                <div class="info-body">
                    <h3>Droppers <span>Café & Resto</span></h3>
                    <p>We'd love to host you! Fill in the form and we'll confirm your seat.</p>

                    <div class="info-item">
                        <div class="info-item-icon">📍</div>
                        <div class="info-item-text">
                            <p>Location</p>
                            <strong>Bheldi Road, Amnour, Saran, Bihar — 841424</strong>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-icon">⏰</div>
                        <div class="info-item-text">
                            <p>Working Hours</p>
                            <strong>Mon – Sat: 10:00 AM – 11:00 PM<br>Sunday: 11:00 AM – 10:00 PM</strong>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-icon">📞</div>
                        <div class="info-item-text">
                            <p>Call / WhatsApp</p>
                            <strong><a href="tel:+917004810081" style="color:#ff7b00;">+91 70048 10081</a></strong>
                        </div>
                    </div>

                    <div class="table-legend">
                        <p>Slot Status Guide</p>
                        <div class="legend-grid">
                            <div class="legend-dot"><span class="dot dot-green"></span> Available</div>
                            <div class="legend-dot"><span class="dot dot-orange"></span> Selected</div>
                            <div class="legend-dot"><span class="dot dot-red"></span> Confirmed</div>
                            <div class="legend-dot"><span class="dot dot-yellow"></span> Pending</div>
                            <div class="legend-dot"><span class="dot dot-gray"></span> Past</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Form Card -->
            <div class="form-card">
                <div class="form-card-header">
                    <h2>Reserve a <span>Table</span></h2>
                    <p>Fill in your details and choose a time slot</p>
                </div>

                <!-- Dynamic Booking Status -->
                <?php if($latest_booking): ?>
                    <?php if($latest_booking['status'] === 'Confirmed'): ?>
                    <div class="alert alert-success">
                        <i class="fa fa-circle-check"></i>
                        <span>Your last booking for <strong>Table <?= $latest_booking['table_no'] ?></strong> on <strong><?= date('d M Y', strtotime($latest_booking['date'])) ?></strong> at <strong><?= date('h:i A', strtotime($latest_booking['time'])) ?></strong> is <strong>Confirmed!</strong> ✅</span>
                    </div>
                    <?php elseif($latest_booking['status'] === 'Pending'): ?>
                    <div class="alert alert-warning">
                        <i class="fa fa-clock"></i>
                        <span>Your booking for <strong>Table <?= $latest_booking['table_no'] ?></strong> on <strong><?= date('d M Y', strtotime($latest_booking['date'])) ?></strong> is <strong>Pending</strong> — admin jaldi confirm karega!</span>
                    </div>
                    <?php elseif($latest_booking['status'] === 'Cancelled'): ?>
                    <div class="alert alert-error">
                        <i class="fa fa-circle-xmark"></i>
                        <span>Your last booking was <strong>Cancelled</strong>. Nai booking ke liye neeche form fill karo.</span>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fa fa-clock"></i>
                    <span>Booking submit hone ke baad <strong>admin review karega</strong> aur confirm karega.</span>
                </div>
                <?php endif; ?>

                <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fa fa-circle-exclamation"></i>
                    <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>

                <?php if(isset($msg)): ?>
                <div class="alert alert-success">
                    <i class="fa fa-circle-check"></i>
                    <span><?php echo $msg; ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" id="bookForm" onsubmit="return validateForm()">

                    <div class="form-row">
                        <div class="fg">
                            <label><i class="fa fa-user"></i> &nbsp;Your Name</label>
                            <input type="text" name="name" id="name" placeholder="Enter your name"
                                value="<?php echo htmlspecialchars($cust_name); ?>"
                                oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                                required>
                        </div>
                        <div class="fg">
                            <label><i class="fa fa-phone"></i> &nbsp;Phone Number</label>
                            <input type="text" name="phone" id="phone" placeholder="10-digit number"
                                value="<?php echo htmlspecialchars($cust_phone); ?>"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                                maxlength="10" required>
                        </div>
                    </div>

                    <?php if(!empty($cust_email)): ?>
                    <div class="email-note">
                        <i class="fa fa-envelope"></i> Confirmation mail jaayegi:
                        <strong><?= htmlspecialchars($cust_email) ?></strong>
                    </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="fg">
                            <label><i class="fa fa-chair"></i> &nbsp;Select Table</label>
                            <select name="table" onchange="document.getElementById('bookForm').submit()">
                                <?php for($t = 1; $t <= 10; $t++): ?>
                                <option value="<?php echo $t; ?>" <?php echo ($sel_table == $t) ? 'selected' : ''; ?>>
                                    Table <?php echo $t; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="fg">
                            <label><i class="fa fa-calendar"></i> &nbsp;Select Date</label>
                            <input type="date" name="date"
                                min="<?php echo date('Y-m-d'); ?>"
                                value="<?php echo htmlspecialchars($sel_date); ?>"
                                required
                                onchange="document.getElementById('bookForm').submit()">
                        </div>
                    </div>

                    <div class="form-divider">Duration</div>
                    <div class="dur-grid">
                        <?php
                        $durations = [60 => '⏱ 1 Hour', 120 => '⏱ 2 Hours', 180 => '⏱ 3 Hours'];
                        $sel_dur   = intval($_POST['duration'] ?? 60);
                        foreach($durations as $mins => $label):
                        ?>
                        <button type="button" class="dur-btn <?php echo ($sel_dur == $mins) ? 'active' : ''; ?>"
                            onclick="setDuration(<?php echo $mins; ?>, this)">
                            <?php echo $label; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="duration" id="durationInput" value="<?php echo $sel_dur; ?>">

                    <div class="form-divider">Available Time Slots</div>

                    <?php
                    $pending_slots = [];
                    $pq = $conn->prepare("SELECT time, duration FROM bookings WHERE table_no=? AND date=? AND status='Pending'");
                    $pq->bind_param("is", $sel_table, $sel_date);
                    $pq->execute();
                    $pq_result = $pq->get_result();
                    while($prow = $pq_result->fetch_assoc()){
                        $ps = strtotime($prow['time']);
                        $pe = $ps + ($prow['duration'] * 60);
                        $pending_slots[] = ['start' => $ps, 'end' => $pe];
                    }
                    ?>

                    <div class="slot-grid">
                    <?php
                    $now      = time();
                    $is_today = ($sel_date == date('Y-m-d'));
                    foreach($all_slots as $slot):
                        $slot_ts  = strtotime("$sel_date $slot");
                        $slot_end = $slot_ts + ($sel_dur * 60);
                        $isPast   = ($is_today && $slot_ts <= $now);
                        $isBooked = !$isPast && hasOverlap($occupied, $slot_ts, $sel_dur);
                        $isPending = false;
                        if(!$isPast){
                            foreach($pending_slots as $ps){
                                if($slot_ts < $ps['end'] && $slot_end > $ps['start']){
                                    $isPending = true; break;
                                }
                            }
                        }
                        $isSelected = (!$isPast && isset($_POST['time']) && date('H:i', strtotime($_POST['time'])) == $slot);
                        if($isPast)         $cls = 'past-slot';
                        elseif($isBooked)   $cls = 'booked';
                        elseif($isPending)  $cls = 'pending-slot';
                        elseif($isSelected) $cls = 'selected';
                        else                $cls = '';
                        $disabled = ($isPast || $isBooked || $isPending) ? 'disabled' : '';
                        if($isPast)        $title = 'Past time';
                        elseif($isBooked)  $title = 'Already confirmed';
                        elseif($isPending) $title = 'Pending approval';
                        else               $title = 'Available';
                    ?>
                    <button type="button"
                        class="slot-btn <?php echo $cls; ?>"
                        <?php echo $disabled; ?>
                        title="<?php echo $title; ?>"
                        onclick="selectSlot('<?php echo $slot; ?>', this)">
                        <?php echo date('h:i A', strtotime($slot)); ?>
                    </button>
                    <?php endforeach; ?>
                    </div>

                    <input type="hidden" name="time" id="timeInput" value="<?php echo isset($_POST['time']) ? $_POST['time'] : ''; ?>">

                    <button name="book" type="submit" class="btn-submit">
                        <i class="fa fa-calendar-check"></i> Send Booking Request
                    </button>

                </form>
            </div>
            <!-- END Form Card -->

        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<!-- Instagram Float -->
<a href="https://www.instagram.com/dropperscafe" class="instagram-float" target="_blank" title="Follow us on Instagram">
    <i class="fab fa-instagram"></i>
</a>
<!-- WhatsApp Float -->
<a href="https://wa.me/917004810081" class="whatsapp-float" target="_blank" title="Chat on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>
<!-- Back to Top -->
<a href="#" class="back-to-top" id="backToTop">
    <i class="fa fa-arrow-up"></i>
</a>

<script>
function validateForm(){
    const name  = document.getElementById('name').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const time  = document.getElementById('timeInput').value;
    if(!/^[a-zA-Z\s]+$/.test(name)){
        alert('Name mein sirf alphabets allowed hain!'); return false;
    }
    if(!/^[0-9]{10}$/.test(phone)){
        alert('Phone number exactly 10 digits ka hona chahiye!'); return false;
    }
    if(!time){
        alert('Please select a time slot!'); return false;
    }
    return true;
}

function selectSlot(time, el){
    document.querySelectorAll('.slot-btn').forEach(b => {
        if(!b.classList.contains('booked') && !b.classList.contains('pending-slot') && !b.classList.contains('past-slot'))
            b.classList.remove('selected');
    });
    el.classList.add('selected');
    document.getElementById('timeInput').value = time;
}

function setDuration(mins, el){
    document.querySelectorAll('.dur-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('durationInput').value = mins;
    document.getElementById('bookForm').submit();
}

// Back to top
const btt = document.getElementById('backToTop');
window.addEventListener('scroll', () => {
    btt.classList.toggle('show', window.scrollY > 300);
});
</script>

</body>
</html>