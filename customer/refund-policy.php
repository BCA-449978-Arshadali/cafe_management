<?php session_start(); include "../config.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Refund Policy - Droppers Café</title>
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
.refund-table { width:100%; border-collapse:collapse; margin:16px 0; }
.refund-table th { background:#ff7b00; color:#fff; padding:12px 16px; font-size:14px; text-align:left; }
.refund-table td { padding:12px 16px; font-size:14px; color:#aaa; border-bottom:1px solid #2a2a2a; }
.refund-table tr:last-child td { border-bottom:none; }
.badge-yes { background:#d4edda; color:#155724; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
.badge-no  { background:#f8d7da; color:#721c24; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="policy-wrapper">
<div class="policy-card">

    <h1>💰 Refund Policy</h1>
    <p class="updated">Last updated: March 2026</p>

    <div class="highlight">
        We strive to ensure complete satisfaction. If something goes wrong, we will make it right.
    </div>

    <h2>Refund Eligibility</h2>
    <table class="refund-table">
        <tr>
            <th>Situation</th>
            <th>Refund</th>
        </tr>
        <tr>
            <td>Wrong item delivered</td>
            <td><span class="badge-yes">✓ Full Refund</span></td>
        </tr>
        <tr>
            <td>Order cancelled before preparation</td>
            <td><span class="badge-yes">✓ Full Refund</span></td>
        </tr>
        <tr>
            <td>Food quality issue (with proof)</td>
            <td><span class="badge-yes">✓ Full Refund</span></td>
        </tr>
        <tr>
            <td>Missing items in order</td>
            <td><span class="badge-yes">✓ Partial Refund</span></td>
        </tr>
        <tr>
            <td>Order already delivered correctly</td>
            <td><span class="badge-no">✗ No Refund</span></td>
        </tr>
        <tr>
            <td>Change of mind after preparation</td>
            <td><span class="badge-no">✗ No Refund</span></td>
        </tr>
    </table>

    <h2>Online Payment Refunds (Razorpay)</h2>
    <p>For orders paid online via UPI/Card/Net Banking:</p>
    <ul>
        <li>Refund will be initiated within <strong style="color:#fff;">2 business days</strong> of approval</li>
        <li>Amount will be credited to your original payment source within <strong style="color:#fff;">5-7 business days</strong></li>
        <li>Bank processing time may vary</li>
    </ul>

    <h2>Cash on Delivery Refunds</h2>
    <p>For COD orders, refunds will be processed via bank transfer or cash at our café location within 24 hours of approval.</p>

    <h2>How to Request a Refund</h2>
    <p>Contact us within <strong style="color:#fff;">1 hour</strong> of receiving your order:</p>
    <ul>
        <li>📞 <strong style="color:#fff;">+91 7004810081</strong></li>
        <li>💬 WhatsApp: <a href="https://wa.me/917004810081" style="color:#ff7b00;">wa.me/917004810081</a></li>
    </ul>
    <p>Please provide your Order ID and a photo/description of the issue.</p>

</div>
</div>
</body>
</html>
<?php include 'includes/footer.php'; ?>