<?php
ob_start();
require_once 'includes/auth-check.php';
include '../config.php';

$order_id        = $_SESSION['last_order_id']        ?? null;
$items_arr       = $_SESSION['last_order_items']     ?? [];
$order_type      = $_SESSION['last_order_type']      ?? 'Dine In';
$grand           = $_SESSION['last_order_total']     ?? 0;
$payment_method  = $_SESSION['last_order_payment']   ?? 'Cash on Delivery';
$address         = $_SESSION['last_order_address']   ?? '';
$delivery_charge = $_SESSION['last_delivery_charge'] ?? 0;
$subtotal        = $grand - $delivery_charge;

// If no order_id in session, redirect to menu (prevents blank spinner page)
if(!$order_id){
    header("Location: menu.php");
    exit;
}

// Build combo_name → [item_names] map for bill display
$bill_combo_map = [];
$cr = mysqli_query($conn,
    "SELECT c.combo_name, m.item_name
     FROM combo_items ci
     JOIN combos c ON c.id = ci.combo_id
     JOIN menu   m ON m.id = ci.menu_id
     WHERE c.is_active = 1"
);
if($cr) while($row = mysqli_fetch_assoc($cr))
    $bill_combo_map[$row['combo_name']][] = $row['item_name'];

$type_icon = ['Dine In'=>'🪑','Takeaway'=>'🛍️','Home Delivery'=>'🛵'];
$icon = $type_icon[$order_type] ?? '📦';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful — Droppers Café</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
body { background:#0d0d0d; color:#fff; font-family:'Poppins',sans-serif; overflow-x:hidden; }
a { text-decoration:none; }
.container { max-width:1100px; margin:0 auto; padding:0 24px; }

/* ===== PAGE HERO ===== */
.page-hero {
    background: linear-gradient(135deg, #0a0a0a 0%, #0a120a 50%, #0a0a0a 100%);
    padding: 80px 0 50px;
    position: relative; overflow: hidden;
    border-bottom: 1px solid rgba(255,123,0,0.1);
}
.page-hero::before {
    content:''; position:absolute; inset:0;
    background:
        radial-gradient(ellipse at 20% 50%, rgba(39,174,96,0.07) 0%, transparent 55%),
        radial-gradient(ellipse at 80% 20%, rgba(255,123,0,0.05) 0%, transparent 50%);
    pointer-events:none;
}
.page-hero .container { position:relative; z-index:2; display:flex; align-items:center; gap:24px; flex-wrap:wrap; }
.page-hero-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(39,174,96,0.1); border:1px solid rgba(39,174,96,0.3);
    padding:6px 16px; border-radius:30px;
    font-size:13px; color:#27ae60; font-weight:600; margin-bottom:16px;
}
.page-hero h1 { font-size:clamp(30px,4vw,50px); font-weight:800; margin-bottom:10px; }
.page-hero h1 span { color:#ff7b00; }
.page-hero p { color:#888; font-size:15px; max-width:460px; line-height:1.7; }
.hero-icon-wrap {
    width:100px; height:100px; border-radius:50%;
    background:rgba(39,174,96,0.1); border:2px solid rgba(39,174,96,0.3);
    display:flex; align-items:center; justify-content:center;
    font-size:46px; flex-shrink:0;
    animation:popIn 0.6s cubic-bezier(.22,.68,0,1.3) both;
}
@keyframes popIn {
    from { transform:scale(0); opacity:0; }
    to   { transform:scale(1); opacity:1; }
}

/* ===== STATS ROW ===== */
.stats-row {
    display:grid; grid-template-columns:repeat(4,1fr);
    gap:16px; margin:40px 0;
}
.stat-card {
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:16px; padding:22px; text-align:center; transition:0.3s;
}
.stat-card:hover { border-color:rgba(255,123,0,0.3); transform:translateY(-3px); }
.stat-card .num {
    font-size:26px; font-weight:800;
    background:linear-gradient(135deg,#ff7b00,#ffb347);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    background-clip:text; line-height:1;
}
.stat-card .lbl { font-size:12px; color:#666; margin-top:6px; }

/* ===== MAIN LAYOUT ===== */
.success-section { padding:20px 0 80px; }
.success-layout {
    display:grid; grid-template-columns:1fr 1.1fr;
    gap:28px; align-items:start;
}

/* ===== SHARED CARD ===== */
.card {
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:20px; padding:32px;
}
.card-header { margin-bottom:24px; }
.card-header h2 { font-size:20px; font-weight:800; margin-bottom:4px; }
.card-header h2 span { color:#ff7b00; }
.card-header p { color:#666; font-size:13px; }

/* ===== SUCCESS CARD ===== */
.success-msg {
    text-align:center; padding:10px 0 24px;
    border-bottom:1px solid rgba(255,255,255,0.06);
    margin-bottom:24px;
}
.success-msg h3 { font-size:22px; font-weight:800; color:#27ae60; margin-bottom:8px; }
.success-msg p  { font-size:13px; color:#888; line-height:1.7; }

.order-note {
    background:#0d0d0d; border:1px solid rgba(255,255,255,0.06);
    border-radius:12px; padding:14px 18px;
    margin-bottom:20px;
}
.order-note-row {
    display:flex; align-items:center; gap:10px;
    font-size:13px; color:#888; padding:5px 0;
    border-bottom:1px solid rgba(255,255,255,0.04);
}
.order-note-row:last-child { border-bottom:none; }
.order-note-row strong { color:#fff; }
.order-note-row .tag {
    margin-left:auto;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.25);
    color:#ff7b00; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700;
}

/* Action buttons */
.action-btns { display:flex; flex-direction:column; gap:10px; }
.btn-action {
    display:flex; align-items:center; justify-content:center; gap:8px;
    padding:13px; border-radius:13px;
    font-size:14px; font-weight:700; transition:all 0.3s;
    font-family:'Poppins',sans-serif; border:none; cursor:pointer;
}
.btn-track {
    background:linear-gradient(135deg,#528ff5,#3b5fc0);
    color:#fff; box-shadow:0 6px 20px rgba(82,143,245,0.25);
}
.btn-track:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(82,143,245,0.4); color:#fff; }
.btn-menu {
    background:linear-gradient(135deg,#ff7b00,#ff9500);
    color:#fff; box-shadow:0 6px 20px rgba(255,123,0,0.3);
}
.btn-menu:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(255,123,0,0.45); color:#fff; }
.btn-home {
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08) !important;
    color:#aaa;
}
.btn-home:hover { border-color:rgba(255,123,0,0.3) !important; color:#ff7b00; }

/* Email notice */
.email-notice {
    display:flex; align-items:center; gap:10px;
    background:rgba(39,174,96,0.08); border:1px solid rgba(39,174,96,0.25);
    color:#27ae60; padding:12px 16px; border-radius:12px;
    font-size:13px; margin-bottom:20px;
}

/* ===== BILL CARD ===== */
.bill-header-inner {
    text-align:center; padding-bottom:20px;
    border-bottom:1px solid rgba(255,255,255,0.06);
    margin-bottom:20px;
}
.bill-header-inner h3 { font-size:18px; font-weight:800; color:#ff7b00; margin-bottom:3px; }
.bill-header-inner p  { font-size:12px; color:#555; }

/* Order info grid */
.bill-info {
    background:#0d0d0d; border:1px solid rgba(255,255,255,0.06);
    border-radius:12px; padding:14px 16px; margin-bottom:18px;
}
.bill-info-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:6px 0; font-size:13px; color:#777;
    border-bottom:1px solid rgba(255,255,255,0.04);
}
.bill-info-row:last-child { border-bottom:none; }
.bill-info-row .val { color:#fff; font-weight:600; font-size:13px; }
.bill-info-row .val.orange { color:#ff7b00; }

/* Items table */
.bill-table { width:100%; border-collapse:collapse; margin-bottom:16px; }
.bill-table thead tr {
    background:rgba(255,123,0,0.1);
    border-bottom:1px solid rgba(255,123,0,0.2);
}
.bill-table th {
    padding:10px 12px; font-size:11px;
    color:#ff7b00; font-weight:700;
    text-transform:uppercase; letter-spacing:0.5px;
    text-align:left;
}
.bill-table th:nth-child(2) { text-align:center; }
.bill-table th:last-child   { text-align:right; }
.bill-table td {
    padding:11px 12px; font-size:13px; color:#ccc;
    border-bottom:1px solid rgba(255,255,255,0.04);
}
.bill-table td:nth-child(2) { text-align:center; color:#888; }
.bill-table td:last-child   { text-align:right; color:#fff; font-weight:600; }
.bill-table tbody tr:hover { background:rgba(255,255,255,0.02); }

.subtotal-row td   { color:#777 !important; font-size:12px !important; }
.delivery-row td   { color:#888 !important; font-size:12px !important; }
.total-row {
    background:rgba(255,123,0,0.07);
    border-top:1px solid rgba(255,123,0,0.25) !important;
}
.total-row td { color:#ff7b00 !important; font-weight:800 !important; font-size:15px !important; border-bottom:none !important; }

/* Bill footer */
.bill-footer {
    text-align:center; color:#555; font-size:12px;
    margin-top:16px; padding-top:16px;
    border-top:1px solid rgba(255,255,255,0.06);
}
.bill-footer span { color:#ff7b00; }

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
    0%,100%{ box-shadow:0 4px 20px rgba(214,36,159,0.4); }
    50%     { box-shadow:0 4px 40px rgba(214,36,159,0.7); }
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
    0%,100%{ box-shadow:0 4px 20px rgba(37,211,102,0.4); }
    50%     { box-shadow:0 4px 40px rgba(37,211,102,0.7); }
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
    .success-layout { grid-template-columns:1fr; }
    .stats-row { grid-template-columns:1fr 1fr; }
}
@media(max-width:500px){
    .stats-row { grid-template-columns:1fr 1fr; }
    .card { padding:20px; }
    .page-hero { padding:60px 0 40px; }
    .hero-icon-wrap { width:76px; height:76px; font-size:34px; }
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Page Hero -->
<div class="page-hero">
    <div class="container">
        <div style="flex:1; min-width:220px;">
            <div class="page-hero-badge">
                <i class="fa fa-circle-check"></i> Order Confirmed
            </div>
            <h1>Order <span>Successful!</span></h1>
            <p>Your order has been placed. Our team is preparing it fresh for you!</p>
        </div>
        <div class="hero-icon-wrap">🎉</div>
    </div>
</div>

<!-- Stats -->
<div class="container">
    <div class="stats-row">
        <div class="stat-card">
            <div class="num">#<?= $order_id ?? '--' ?></div>
            <div class="lbl">Order ID</div>
        </div>
        <div class="stat-card">
            <div class="num">₹<?= $grand ?></div>
            <div class="lbl">Total Amount</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= count($items_arr) ?></div>
            <div class="lbl">Items Ordered</div>
        </div>
        <div class="stat-card">
            <div class="num">~25m</div>
            <div class="lbl">Prep Time</div>
        </div>
    </div>
</div>

<div class="success-section">
    <div class="container">
        <div class="success-layout">

            <!-- LEFT: Success Info + Actions -->
            <div>

                <!-- Success Message Card -->
                <div class="card" style="margin-bottom:24px;">
                    <div class="success-msg">
                        <h3><i class="fa fa-circle-check"></i> &nbsp;Order Placed!</h3>
                        <p>Thank you for ordering from Droppers Café. We're making your food with love right now!</p>
                    </div>

                    <div class="order-note">
                        <div class="order-note-row">
                            <i class="fa fa-clock" style="color:#ff7b00; width:16px;"></i>
                            <span>Estimated Time</span>
                            <span class="tag">20–30 min</span>
                        </div>
                        <div class="order-note-row">
                            <i class="fa fa-phone" style="color:#ff7b00; width:16px;"></i>
                            <span>Support</span>
                            <strong>+91 7004810081</strong>
                        </div>
                        <div class="order-note-row">
                            <?= $icon ?>
                            <span>Order Type</span>
                            <strong><?= htmlspecialchars($order_type) ?></strong>
                        </div>
                        <div class="order-note-row">
                            <i class="fa fa-credit-card" style="color:#ff7b00; width:16px;"></i>
                            <span>Payment</span>
                            <strong><?= htmlspecialchars($payment_method) ?></strong>
                        </div>
                    </div>

                    <!-- Email notice -->
                    <?php if(!empty($_SESSION['customer_email'])): ?>
                    <div class="email-notice">
                        <i class="fa fa-envelope"></i>
                        Bill sent to <strong>&nbsp;<?= htmlspecialchars($_SESSION['customer_email']) ?></strong>
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="action-btns">
                        <a href="track_order.php" class="btn-action btn-track">
                            <i class="fa fa-location-dot"></i> Track My Order
                        </a>
                        <a href="menu.php" class="btn-action btn-menu">
                            <i class="fa fa-utensils"></i> Order More
                        </a>
                        <a href="index.php" class="btn-action btn-home">
                            <i class="fa fa-house"></i> Go to Home
                        </a>
                    </div>
                </div>

            </div>

            <!-- RIGHT: Bill -->
            <?php if($order_id): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Your <span>Bill</span></h2>
                    <p>Order receipt — keep this for your reference</p>
                </div>

                <div class="bill-header-inner">
                    <h3> Droppers Café & Resto</h3>
                    <p>Official Order Receipt</p>
                </div>

                <!-- Order Info -->
                <div class="bill-info">
                    <div class="bill-info-row">
                        <span><i class="fa fa-hashtag fa-xs"></i> &nbsp;Order ID</span>
                        <span class="val orange">#<?= $order_id ?></span>
                    </div>
                    <div class="bill-info-row">
                        <span><i class="fa fa-user fa-xs"></i> &nbsp;Name</span>
                        <span class="val"><?= htmlspecialchars($_SESSION['customer_name']) ?></span>
                    </div>
                    <?php if(!empty($_SESSION['customer_phone'])): ?>
                    <div class="bill-info-row">
                        <span><i class="fa fa-phone fa-xs"></i> &nbsp;Phone</span>
                        <span class="val"><?= $_SESSION['customer_phone'] ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="bill-info-row">
                        <span><?= $icon ?> &nbsp;Order Type</span>
                        <span class="val"><?= htmlspecialchars($order_type) ?></span>
                    </div>
                    <div class="bill-info-row">
                        <span><i class="fa fa-credit-card fa-xs"></i> &nbsp;Payment</span>
                        <span class="val"><?= htmlspecialchars($payment_method) ?></span>
                    </div>
                    <?php if(!empty($address)): ?>
                    <div class="bill-info-row">
                        <span><i class="fa fa-location-dot fa-xs"></i> &nbsp;Address</span>
                        <span class="val" style="text-align:right; max-width:55%;"><?= htmlspecialchars($address) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="bill-info-row">
                        <span><i class="fa fa-calendar fa-xs"></i> &nbsp;Date & Time</span>
                        <span class="val"><?= date('d M Y, h:i A') ?></span>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($items_arr as $item):
                        $is_combo      = isset($bill_combo_map[$item['name']]);
                        $combo_content = $is_combo ? $bill_combo_map[$item['name']] : [];
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                <?= htmlspecialchars($item['name']) ?>
                                <?php if($is_combo): ?>
                                <span style="background:#d4a017;color:#000;font-size:9px;font-weight:800;padding:2px 7px;border-radius:20px;letter-spacing:0.4px;">🍱 COMBO</span>
                                <?php endif; ?>
                            </div>
                            <?php if(!empty($combo_content)): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:5px;">
                                <?php foreach($combo_content as $ci): ?>
                                <span style="padding:1px 7px;border-radius:8px;background:rgba(212,160,23,0.08);border:1px solid rgba(212,160,23,0.25);font-size:10px;color:#d4a017;font-weight:600;">
                                    <?= htmlspecialchars($ci) ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><?= $item['qty'] ?></td>
                        <td>₹<?= $item['price'] * $item['qty'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="subtotal-row">
                        <td colspan="2">Subtotal</td>
                        <td>₹<?= $subtotal ?></td>
                    </tr>
                    <?php if($delivery_charge > 0): ?>
                    <tr class="delivery-row">
                        <td colspan="2">🛵 Delivery Charge</td>
                        <td>₹<?= $delivery_charge ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td colspan="2">Grand Total</td>
                        <td>₹<?= $grand ?></td>
                    </tr>
                    </tbody>
                </table>

                <div class="bill-footer">
                    <p>Thank you for choosing Droppers Café! 🙏</p>
                    <p style="margin-top:4px;">Visit again: <span>dropperscafe.in</span></p>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Floating Buttons -->
<a href="https://www.instagram.com/dropperscafe" class="instagram-float" target="_blank" title="Follow on Instagram">
    <i class="fab fa-instagram"></i>
</a>
<a href="https://wa.me/917004810081" class="whatsapp-float" target="_blank" title="Chat on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>
<a href="#" class="back-to-top" id="backToTop">
    <i class="fa fa-arrow-up"></i>
</a>

<script>
// Hide any preloader/spinner as soon as page loads
(function(){
    var pl = document.getElementById('preloader') || document.querySelector('.preloader') || document.querySelector('.page-loader');
    if(pl){ pl.style.display = 'none'; }
})();
window.addEventListener('load', function(){
    var pl = document.getElementById('preloader') || document.querySelector('.preloader') || document.querySelector('.page-loader');
    if(pl){ pl.style.opacity='0'; setTimeout(function(){ pl.style.display='none'; }, 200); }
});

const btt = document.getElementById('backToTop');
window.addEventListener('scroll', () => {
    btt.classList.toggle('show', window.scrollY > 300);
});
</script>
</body>
</html>