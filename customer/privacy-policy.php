<?php session_start(); include "../config.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Privacy Policy - Droppers Café</title>
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body { background:#111; color:#fff; font-family:'Poppins',sans-serif; margin:0; }
.policy-wrapper { max-width:800px; margin:40px auto; padding:0 20px 60px; }
.policy-card { background:#1a1a1a; border-radius:16px; padding:40px; border:1px solid #2a2a2a; }
.policy-card h1 { font-size:28px; color:#ff7b00; margin:0 0 6px; }
.policy-card .updated { font-size:13px; color:#555; margin-bottom:30px; }
.policy-card h2 { font-size:18px; color:#fff; margin:28px 0 10px; padding-left:12px; border-left:3px solid #ff7b00; }
.policy-card p  { font-size:15px; color:#aaa; line-height:1.8; margin:0 0 14px; }
.policy-card ul { color:#aaa; font-size:15px; line-height:2; padding-left:20px; }
.highlight { background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.2); border-radius:10px; padding:16px 20px; margin:20px 0; color:#ff7b00; font-size:14px; }
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="policy-wrapper">
<div class="policy-card">

    <h1>🔒 Privacy Policy</h1>
    <p class="updated">Last updated: March 2026</p>

    <div class="highlight">
        Your privacy is important to us. This policy explains how Droppers Café collects, uses, and protects your personal information.
    </div>

    <h2>Information We Collect</h2>
    <ul>
        <li><strong style="color:#fff;">Name & Contact:</strong> Name, phone number, email address</li>
        <li><strong style="color:#fff;">Order Data:</strong> Items ordered, delivery address, payment method</li>
        <li><strong style="color:#fff;">Account Data:</strong> Login credentials (password stored encrypted)</li>
        <li><strong style="color:#fff;">Usage Data:</strong> Pages visited, time spent on site</li>
    </ul>

    <h2>How We Use Your Information</h2>
    <ul>
        <li>To process and deliver your orders</li>
        <li>To send order confirmations and updates</li>
        <li>To improve our services and website</li>
        <li>To respond to your queries and complaints</li>
        <li>To send promotional offers (only if you opt-in)</li>
    </ul>

    <h2>Data Storage & Security</h2>
    <p>Your data is stored securely on our servers. Passwords are hashed and never stored in plain text. We use SSL encryption for all data transmission.</p>

    <h2>Payment Information</h2>
    <p>We do not store your card or UPI details. All online payments are processed securely through <strong style="color:#fff;">Razorpay</strong>, which complies with PCI-DSS standards.</p>

    <h2>Sharing Your Information</h2>
    <p>We do <strong style="color:#fff;">NOT</strong> sell or share your personal data with third parties, except:</p>
    <ul>
        <li>Payment gateway (Razorpay) for processing payments</li>
        <li>Delivery partners for home delivery orders</li>
        <li>Legal authorities if required by law</li>
    </ul>

    <h2>Cookies</h2>
    <p>We use session cookies to keep you logged in and remember your cart. No tracking cookies are used.</p>

    <h2>Your Rights</h2>
    <ul>
        <li>Right to access your personal data</li>
        <li>Right to update or correct your information</li>
        <li>Right to request deletion of your account</li>
    </ul>

    <h2>Contact Us</h2>
    <p>For any privacy concerns, contact us at:</p>
    <ul>
        <li>📞 <strong style="color:#fff;">+91 7004810081</strong></li>
        <li>📍 Bheldi Road, Amnour, Bihar</li>
        <li>💬 <a href="https://wa.me/917004810081" style="color:#ff7b00;">WhatsApp us</a></li>
    </ul>

</div>
</div>
</body>
</html>
<?php include 'includes/footer.php'; ?>