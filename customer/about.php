<?php
session_start();
include "../config.php";

$msg_success = false;
$msg_error   = '';

if (isset($_POST['send_msg'])) {

    require_once __DIR__ . '/includes/mailer.php';

    $name    = htmlspecialchars(trim($_POST['msg_name']));
    $phone   = htmlspecialchars(trim($_POST['msg_phone']));
    $message = htmlspecialchars(trim($_POST['msg_text']));

    $subject = 'New Website Message from ' . $name;

    $body = "
        <div style='font-family:Arial,sans-serif; max-width:500px; margin:0 auto; background:#f9f9f9; padding:24px; border-radius:10px;'>
            <h2 style='color:#ff7b00; margin-top:0;'>&#128140; New Website Message</h2>
            <table style='width:100%; border-collapse:collapse;'>
                <tr>
                    <td style='padding:10px; background:#fff; border:1px solid #eee; font-weight:bold; width:30%;'>&#128100; Name</td>
                    <td style='padding:10px; background:#fff; border:1px solid #eee;'>$name</td>
                </tr>
                <tr>
                    <td style='padding:10px; background:#fff; border:1px solid #eee; font-weight:bold;'>&#128222; Phone</td>
                    <td style='padding:10px; background:#fff; border:1px solid #eee;'>$phone</td>
                </tr>
                <tr>
                    <td style='padding:10px; background:#fff; border:1px solid #eee; font-weight:bold;'>&#128172; Message</td>
                    <td style='padding:10px; background:#fff; border:1px solid #eee;'>$message</td>
                </tr>
            </table>
            <p style='color:#888; font-size:12px; margin-top:16px;'>Sent from Droppers Cafe website contact form.</p>
        </div>
    ";

    $sent = sendMail('dropperscafe.auth@gmail.com', $subject, $body);

    if ($sent) {
        $msg_success = true;
    } else {
        $msg_error = 'Message send nahi hua. smtp-config.php check karein.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About & Contact - Droppers Café</title>
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body { background:#111; color:#fff; font-family:'Poppins',sans-serif; margin:0; }
.about-wrapper { max-width:900px; width:100%; margin:40px auto; padding:0 20px 60px; box-sizing:border-box; }

/* Hero */
.about-hero {
    background: linear-gradient(135deg, rgba(255,107,0,0.15), rgba(255,140,26,0.05));
    border: 1px solid rgba(255,123,0,0.2);
    border-radius:16px; padding:40px; text-align:center; margin-bottom:24px;
}
.about-hero h1 { font-size:32px; color:#ff7b00; margin:0 0 10px; }
.about-hero p  { font-size:16px; color:#aaa; line-height:1.8; max-width:600px; margin:0 auto; }

/* Grid */
.about-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px; margin-bottom:24px; }

@media (max-width: 700px) {
    .about-wrapper { margin:20px auto; padding:0 14px 40px; }
    .about-grid { grid-template-columns:1fr !important; }
    .about-hero { padding:24px 16px; }
    .about-hero h1 { font-size:24px; }
    .map-card, .contact-form-card, .info-card { padding:20px; }
}

.info-card {
    background:#1a1a1a; border-radius:14px;
    padding:28px; border:1px solid #2a2a2a;
    min-width:0; overflow:hidden;
}
.info-card h2 { font-size:18px; color:#ff7b00; margin:0 0 16px; padding-left:12px; border-left:3px solid #ff7b00; }
.info-card p  { font-size:14px; color:#aaa; line-height:1.8; margin:0 0 10px; }

.contact-item {
    display:flex; align-items:center; gap:12px;
    padding:12px 0; border-bottom:1px solid #222;
    font-size:14px;
}
.contact-item:last-child { border-bottom:none; }
.contact-icon { font-size:20px; width:30px; text-align:center; }
.contact-info strong { color:#fff; display:block; font-size:13px; }
.contact-info span   { color:#888; font-size:13px; }
.contact-info a      { color:#ff7b00; text-decoration:none; font-size:13px; }

/* Hours */
.hours-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #222; font-size:14px; }
.hours-row:last-child { border-bottom:none; }
.hours-row span:first-child { color:#aaa; }
.hours-row span:last-child  { color:#fff; font-weight:600; }
.open-badge { background:#d4edda; color:#155724; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }

/* Map */
.map-card {
    background:#1a1a1a; border-radius:14px;
    padding:28px; border:1px solid #2a2a2a;
    margin-bottom:24px;
}
.map-card h2 { font-size:18px; color:#ff7b00; margin:0 0 16px; padding-left:12px; border-left:3px solid #ff7b00; }

/* Contact Form */
.contact-form-card {
    background:#1a1a1a; border-radius:14px;
    padding:28px; border:1px solid #2a2a2a;
}
.contact-form-card h2 { font-size:18px; color:#ff7b00; margin:0 0 20px; padding-left:12px; border-left:3px solid #ff7b00; }
.contact-form-card input,
.contact-form-card textarea {
    width:100%; padding:12px 16px;
    background:#222; border:1px solid #444;
    border-radius:10px; color:#fff;
    font-size:14px; margin-bottom:14px;
    box-sizing:border-box; font-family:'Poppins',sans-serif;
}
.contact-form-card input:focus,
.contact-form-card textarea:focus { outline:none; border-color:#ff7b00; }
.contact-form-card textarea { height:100px; resize:none; }
.btn-send { width:100%; padding:13px; background:linear-gradient(135deg,#ff6b00,#ff8c1a); color:#fff; border:none; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer; font-family:'Poppins',sans-serif; }
.btn-send:hover { box-shadow:0 8px 24px rgba(255,107,0,0.4); }

/* Social */
.social-links { display:flex; gap:12px; flex-wrap:wrap; margin-top:16px; }
.social-btn {
    padding:10px 20px; border-radius:10px;
    text-decoration:none; font-size:14px; font-weight:600;
    display:flex; align-items:center; gap:8px;
}
.whatsapp-btn { background:#25D366; color:#fff; }
.whatsapp-btn:hover { background:#1ebe5e; }

/* Alerts */
.alert-success { background:rgba(39,174,96,0.1); border:1px solid #27ae60; color:#27ae60; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px; }
.alert-error   { background:rgba(231,76,60,0.1);  border:1px solid #e74c3c;  color:#e74c3c;  padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px; }
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="about-wrapper">

    <!-- Hero -->
    <div class="about-hero">
        <h1>🍽️ Droppers Café & Resto</h1>
        <p>A cozy café nestled in the heart of Amnour, Bihar — serving delicious food with warmth and love since our founding. From pizzas to momos, cakes to Chinese — we've got it all!</p>
    </div>

    <!-- Grid: Contact Info + Hours -->
    <div class="about-grid">

        <!-- Contact Info -->
        <div class="info-card">
            <h2>📞 Contact Us</h2>

            <div class="contact-item">
                <span class="contact-icon">📞</span>
                <div class="contact-info">
                    <strong>Phone</strong>
                    <a href="tel:+917004810081">+91 7004810081</a>
                </div>
            </div>

            <div class="contact-item">
                <span class="contact-icon">💬</span>
                <div class="contact-info">
                    <strong>WhatsApp</strong>
                    <a href="https://wa.me/917004810081" target="_blank">wa.me/917004810081</a>
                </div>
            </div>

            <div class="contact-item">
                <span class="contact-icon">📍</span>
                <div class="contact-info">
                    <strong>Address</strong>
                    <span>Bheldi Road, Amnour, Bihar</span>
                </div>
            </div>

            <div class="contact-item">
                <span class="contact-icon">✉️</span>
                <div class="contact-info">
                    <strong>Email</strong>
                    <a href="mailto:dropperscafe.auth@gmail.com">dropperscafe.auth@gmail.com</a>
                </div>
            </div>

            <div class="social-links">
                <a href="https://wa.me/917004810081" target="_blank" class="social-btn whatsapp-btn">
                    💬 WhatsApp
                </a>
            </div>
        </div>

        <!-- Hours -->
        <div class="info-card">
            <h2>🕐 Opening Hours</h2>

            <?php
            $days = [
                'Monday'    => ['10:00 AM', '11:00 PM'],
                'Tuesday'   => ['10:00 AM', '11:00 PM'],
                'Wednesday' => ['10:00 AM', '11:00 PM'],
                'Thursday'  => ['10:00 AM', '11:00 PM'],
                'Friday'    => ['10:00 AM', '11:00 PM'],
                'Saturday'  => ['10:00 AM', '11:30 PM'],
                'Sunday'    => ['10:00 AM', '11:30 PM'],
            ];
            $today = date('l');
            foreach($days as $d => $hours):
                $isToday = ($d == $today);
            ?>
            <div class="hours-row" style="<?= $isToday ? 'color:#ff7b00;' : '' ?>">
                <span><?= $d ?> <?= $isToday ? '<span class="open-badge">Today</span>' : '' ?></span>
                <span><?= $hours[0] ?> – <?= $hours[1] ?></span>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- Map -->
    <div class="map-card">
        <h2>📍 Find Us on Map</h2>
        <div style="overflow:hidden; padding-bottom:40%; position:relative; height:0; border-radius:10px;">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3586.917607304979!2d84.9256401!3d25.970742400000002!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3992cb55d2eb821d%3A0xbe19d4929a2613a3!2sDroppers%20Cafe%20%26%20Resto!5e0!3m2!1sen!2sin!4v1769311847759!5m2!1sen!2sin"
                style="border:0; position:absolute; top:0; left:0; width:100%; height:100%; border-radius:10px;"
                allowfullscreen="" loading="lazy">
            </iframe>
        </div>
        <div style="margin-top:16px; text-align:center;">
            <a href="https://maps.app.goo.gl/Bn7osyqgJzsY7SJH6" target="_blank"
               style="display:inline-block; background:#ff5722; color:#fff; padding:10px 24px; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px;">
               📌 Open in Google Maps
            </a>
        </div>
    </div>

    <!-- Contact Form -->
    <div class="contact-form-card">
        <h2>✉️ Send us a Message</h2>

        <?php if ($msg_success): ?>
            <div class="alert-success">&#10003; Message sent! We'll get back to you soon.</div>
        <?php elseif ($msg_error): ?>
            <div class="alert-error">&#10060; <?= $msg_error ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="msg_name"  placeholder="Your Name" required
                   value="<?= isset($_POST['msg_name'])  && !$msg_success ? htmlspecialchars($_POST['msg_name'])  : '' ?>">
            <input type="tel"  name="msg_phone" placeholder="Phone Number" required
                   value="<?= isset($_POST['msg_phone']) && !$msg_success ? htmlspecialchars($_POST['msg_phone']) : '' ?>">
            <textarea name="msg_text" placeholder="Your message..." required><?= isset($_POST['msg_text']) && !$msg_success ? htmlspecialchars($_POST['msg_text']) : '' ?></textarea>
            <button type="submit" name="send_msg" class="btn-send">Send Message</button>
        </form>
    </div>

</div>
</body>
</html>
<?php include 'includes/footer.php'; ?>