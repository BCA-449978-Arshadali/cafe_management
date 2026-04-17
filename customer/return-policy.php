<?php session_start(); include "../config.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Return Policy - Droppers Café</title>
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

    <h1>↩️ Return Policy</h1>
    <p class="updated">Last updated: March 2026</p>

    <div class="highlight">
        🍽️ As a food service business, Droppers Café follows a strict no-return policy on food items once prepared and delivered.
    </div>

    <h2>Food Items</h2>
    <p>Due to the perishable nature of food and hygiene reasons, we do not accept returns on any food items once they have been prepared or delivered.</p>

    <h2>When We Accept Returns / Replacements</h2>
    <ul>
        <li>Wrong item delivered — different from what you ordered</li>
        <li>Food quality issue — spoiled, contaminated, or inedible</li>
        <li>Missing items in your order</li>
        <li>Damaged packaging affecting food quality</li>
    </ul>

    <h2>How to Raise a Complaint</h2>
    <p>If you face any issue with your order, please contact us within <strong style="color:#fff;">1 hour</strong> of receiving your order:</p>
    <ul>
        <li>📞 Call: <strong style="color:#fff;">+91 7004810081</strong></li>
        <li>💬 WhatsApp: <a href="https://wa.me/917004810081" style="color:#ff7b00;">wa.me/917004810081</a></li>
        <li>📍 Visit: Bheldi Road, Amnour, Bihar</li>
    </ul>

    <h2>Resolution Timeline</h2>
    <p>All complaints will be reviewed and resolved within <strong style="color:#fff;">24 hours</strong>. We may ask for a photo of the issue for verification.</p>

    <h2>Refund on Returns</h2>
    <p>If your return/replacement request is approved, the refund will be processed within 5-7 business days to your original payment method.</p>

</div>
</div>
</body>
</html>
<?php include 'includes/footer.php'; ?>