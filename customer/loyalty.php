<?php
session_start();
include "../config.php";
include "../includes/loyalty_helper.php";

// Login check
if(!isset($_SESSION['customer_id'])) {
    header("Location: " . BASE_URL . "/customer/login.php");
    exit;
}

$phone   = $_SESSION['customer_phone'] ?? '';
$name    = $_SESSION['customer_name']  ?? 'Customer';
$balance = $phone ? get_loyalty_balance($conn, $phone) : 0;
$enabled = loyalty_setting($conn, 'points_enabled');

// Fetch customer's loyalty record
$lrow = $phone ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM loyalty_points WHERE customer_phone='".mysqli_real_escape_string($conn,$phone)."' LIMIT 1")) : null;
$total_earned   = $lrow ? intval($lrow['total_points'])    : 0;
$total_redeemed = $lrow ? intval($lrow['redeemed_points']) : 0;

// Settings
$rate    = intval(loyalty_setting($conn, 'redeem_rate', '100'));
$min_pts = intval(loyalty_setting($conn, 'min_redeem_points', '100'));
$per10   = intval(loyalty_setting($conn, 'points_per_10rs', '1'));

// Rupee value of balance
$rupee_val = points_to_rupees($conn, $balance);

// Recent transactions
$txs = $phone ? mysqli_query($conn, "SELECT * FROM loyalty_transactions WHERE customer_phone='".mysqli_real_escape_string($conn,$phone)."' ORDER BY created_at DESC LIMIT 20") : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Loyalty Points — Droppers Café</title>
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { background:#111; color:#fff; font-family:'Poppins',sans-serif; margin:0; }
.page-wrap { max-width:900px; margin:0 auto; padding:40px 20px 80px; }

/* Hero Card */
.points-hero {
    background: linear-gradient(135deg, #1a1200, #1f1500);
    border: 1px solid rgba(255,123,0,0.3);
    border-radius: 20px; padding: 36px 32px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 24px; margin-bottom: 28px; flex-wrap: wrap;
    position: relative; overflow: hidden;
}
.points-hero::before {
    content: '🏆';
    position: absolute; right: 32px; top: 50%;
    transform: translateY(-50%);
    font-size: 120px; opacity: 0.06; pointer-events: none;
}
.hero-left h2 { font-size: 15px; color: #ff9500; font-weight: 600; margin: 0 0 4px; text-transform: uppercase; letter-spacing: 1px; }
.hero-left .pts-number { font-size: 56px; font-weight: 800; color: #ff7b00; line-height: 1; margin: 0 0 6px; }
.hero-left .pts-label  { font-size: 14px; color: #888; }
.hero-right { text-align: right; }
.rupee-val { font-size: 24px; font-weight: 700; color: #27ae60; }
.rupee-label { font-size: 12px; color: #666; margin-top: 2px; }

/* Stats row */
.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
.stat-box {
    background: #1a1a1a; border: 1px solid #2a2a2a;
    border-radius: 14px; padding: 20px; text-align: center;
}
.stat-box .val { font-size: 26px; font-weight: 800; color: #ff7b00; }
.stat-box .lbl { font-size: 12px; color: #666; margin-top: 4px; }

/* How it works */
.how-card {
    background: #1a1a1a; border: 1px solid #2a2a2a;
    border-radius: 16px; padding: 24px; margin-bottom: 28px;
}
.how-card h3 { font-size: 15px; font-weight: 700; margin: 0 0 18px; color: #ff7b00; }
.how-steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; }
.how-step {
    background: #111; border: 1px solid #2a2a2a;
    border-radius: 12px; padding: 18px; text-align: center;
}
.how-step .icon { font-size: 32px; margin-bottom: 10px; }
.how-step .title { font-size: 13px; font-weight: 700; margin-bottom: 6px; }
.how-step .desc  { font-size: 12px; color: #666; line-height: 1.5; }

/* Transactions */
.tx-card {
    background: #1a1a1a; border: 1px solid #2a2a2a;
    border-radius: 16px; overflow: hidden; margin-bottom: 28px;
}
.tx-card h3 { font-size: 15px; font-weight: 700; padding: 18px 20px; border-bottom: 1px solid #2a2a2a; margin: 0; }
.tx-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid #1e1e1e; gap: 12px;
}
.tx-item:last-child { border-bottom: none; }
.tx-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
}
.tx-earn   .tx-icon { background: rgba(39,174,96,0.12); }
.tx-redeem .tx-icon { background: rgba(231,76,60,0.12); }
.tx-bonus  .tx-icon { background: rgba(52,152,219,0.12); }
.tx-meta { flex: 1; }
.tx-meta .tx-title { font-size: 13px; font-weight: 600; }
.tx-meta .tx-date  { font-size: 11px; color: #555; margin-top: 2px; }
.tx-pts { font-size: 16px; font-weight: 800; }
.tx-earn .tx-pts   { color: #27ae60; }
.tx-redeem .tx-pts { color: #e74c3c; }
.tx-bonus .tx-pts  { color: #3498db; }

.empty-tx { text-align: center; padding: 40px 20px; color: #555; }
.empty-tx i { font-size: 40px; display: block; margin-bottom: 12px; }

/* CTA Button */
.btn-order {
    display: inline-flex; align-items: center; gap: 8px;
    background: #ff7b00; color: #fff;
    padding: 13px 28px; border-radius: 12px;
    font-weight: 700; font-size: 15px;
    text-decoration: none; transition: 0.2s;
}
.btn-order:hover { background: #ff9500; }

.disabled-banner {
    background: rgba(231,76,60,0.1); border: 1px solid rgba(231,76,60,0.3);
    border-radius: 14px; padding: 20px 24px;
    text-align: center; color: #e74c3c; font-weight: 600;
    margin-bottom: 28px;
}

@media (max-width: 600px) {
    .page-wrap { padding: 24px 14px 60px; }
    .points-hero { padding: 24px 20px; flex-direction: column; }
    .hero-right { text-align: left; }
    .stats-row { grid-template-columns: 1fr 1fr; }
    .hero-left .pts-number { font-size: 44px; }
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="page-wrap">

    <?php if($enabled != '1'): ?>
    <div class="disabled-banner">
        <i class="fa fa-pause-circle" style="font-size:28px; display:block; margin-bottom:8px;"></i>
        Loyalty Points system is currently paused. Check back soon!
    </div>
    <?php else: ?>

    <!-- Points Hero -->
    <div class="points-hero">
        <div class="hero-left">
            <h2>🏆 Your Points Balance</h2>
            <div class="pts-number"><?= number_format($balance) ?></div>
            <div class="pts-label">Available Points</div>
        </div>
        <div class="hero-right">
            <div class="rupee-val">₹<?= number_format($rupee_val, 2) ?></div>
            <div class="rupee-label">Redemption Value</div>
            <div style="margin-top:12px; font-size:12px; color:#555;">Min. <?= $min_pts ?> pts to redeem</div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="val"><?= number_format($total_earned) ?></div>
            <div class="lbl">Total Earned</div>
        </div>
        <div class="stat-box">
            <div class="val" style="color:#e74c3c;"><?= number_format($total_redeemed) ?></div>
            <div class="lbl">Total Redeemed</div>
        </div>
        <div class="stat-box">
            <div class="val" style="color:#27ae60;"><?= $per10 ?> pt</div>
            <div class="lbl">Per ₹10 spent</div>
        </div>
    </div>

    <!-- How it works -->
    <div class="how-card">
        <h3><i class="fa fa-circle-question"></i> How It Works</h3>
        <div class="how-steps">
            <div class="how-step">
                <div class="icon">🛒</div>
                <div class="title">Order Food</div>
                <div class="desc">Place any order at Droppers Café</div>
            </div>
            <div class="how-step">
                <div class="icon">⭐</div>
                <div class="title">Earn Points</div>
                <div class="desc">Get <?= $per10 ?> point for every ₹10 you spend</div>
            </div>
            <div class="how-step">
                <div class="icon">🎁</div>
                <div class="title">Redeem</div>
                <div class="desc">Every <?= $rate ?> points = ₹10 off on your next order</div>
            </div>
            <div class="how-step">
                <div class="icon">🍕</div>
                <div class="title">Enjoy!</div>
                <div class="desc">Save more on every visit to the café</div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div style="text-align:center; margin-bottom:32px;">
        <a href="<?= BASE_URL ?>/customer/menu.php" class="btn-order">
            🛒 Order Now & Earn More Points
        </a>
        <?php if($balance >= $min_pts): ?>
        <p style="margin-top:12px; font-size:13px; color:#27ae60;">
            🎉 You have enough points to redeem! Apply at checkout.
        </p>
        <?php else: ?>
        <p style="margin-top:12px; font-size:13px; color:#555;">
            Earn <?= max(0, $min_pts - $balance) ?> more points to start redeeming!
        </p>
        <?php endif; ?>
    </div>

    <!-- Transaction History -->
    <div class="tx-card">
        <h3>📋 Points History</h3>
        <?php if(!$txs || mysqli_num_rows($txs) == 0): ?>
        <div class="empty-tx">
            <i class="fa fa-clock-rotate-left"></i>
            <p>No transactions yet — place your first order to earn points!</p>
        </div>
        <?php else: while($tx = mysqli_fetch_assoc($txs)):
            $is_earn   = in_array($tx['type'], ['earn', 'bonus']);
            $tx_class  = 'tx-' . $tx['type'];
            $icons     = ['earn'=>'🛒', 'redeem'=>'🎁', 'bonus'=>'⭐', 'deduct'=>'➖'];
            $icon      = $icons[$tx['type']] ?? '📍';
            $sign      = $is_earn ? '+' : '−';
        ?>
        <div class="tx-item <?= $tx_class ?>">
            <div class="tx-icon"><?= $icon ?></div>
            <div class="tx-meta">
                <div class="tx-title"><?= htmlspecialchars($tx['description'] ?? ucfirst($tx['type'])) ?></div>
                <div class="tx-date"><?= date('d M Y, h:i A', strtotime($tx['created_at'])) ?></div>
            </div>
            <div class="tx-pts"><?= $sign ?><?= number_format($tx['points']) ?> pts</div>
        </div>
        <?php endwhile; endif; ?>
    </div>

    <?php endif; // points_enabled ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>