<?php session_start(); include "../config.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Disclaimer - Droppers Café</title>
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

    <h1>⚠️ Disclaimer</h1>
    <p class="updated">Last updated: March 2026</p>

    <div class="highlight">
        Please read this disclaimer carefully before using the Droppers Café website and services.
    </div>

    <h2>General Information</h2>
    <p>The information provided on this website is for general informational purposes only. While we strive to keep the information accurate and up to date, we make no representations or warranties of any kind about the completeness or accuracy of any information.</p>

    <h2>Menu & Pricing</h2>
    <p>Menu items and prices displayed on this website are subject to change without prior notice. Actual prices at the café may vary. Images shown are for representation purposes only and may differ from actual food served.</p>

    <h2>Allergen Information</h2>
    <p>Our food items may contain or come in contact with common allergens including nuts, dairy, eggs, gluten, and seafood. Customers with food allergies are advised to inform our staff before ordering. Droppers Café is not liable for any allergic reactions.</p>

    <h2>Availability</h2>
    <p>Not all menu items may be available at all times. Seasonal items and special items are subject to availability.</p>

    <h2>Online Orders</h2>
    <p>Order delivery times are estimates and may vary depending on distance, weather conditions, and order volume. Droppers Café is not liable for delays caused by factors beyond our control.</p>

    <h2>Third-Party Links</h2>
    <p>This website may contain links to external websites. We have no control over the content of those sites and accept no responsibility for them.</p>

    <h2>Limitation of Liability</h2>
    <p>Droppers Café shall not be liable for any indirect, incidental, or consequential damages arising from the use of our services or website.</p>

    <h2>Contact</h2>
    <p>For any questions regarding this disclaimer:</p>
    <ul>
        <li>📞 <strong style="color:#fff;">+91 7004810081</strong></li>
        <li>📍 Bheldi Road, Amnour, Bihar</li>
    </ul>

</div>
</div>
</body>
</html>
<?php include 'includes/footer.php'; ?>