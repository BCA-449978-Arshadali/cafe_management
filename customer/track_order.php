<?php
include '../config.php';
require_once 'includes/auth-check.php';

// Order ID from URL or session
$order_id = isset($_GET['id']) ? intval($_GET['id']) : ($_SESSION['last_order_id'] ?? 0);

$order = null;
if($order_id){
    $phone = $_SESSION['customer_phone'] ?? '';
    $cname = $_SESSION['customer_name'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id=? AND (customer_phone=? OR customer_name=?)");
    $stmt->bind_param("iss", $order_id, $phone, $cname);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
}

// Status steps
$steps = ['Pending', 'Preparing', 'Completed'];
$current_step = $order ? array_search($order['status'], $steps) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - Droppers Café</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/customer.css">

<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
body { background:#0d0d0d; color:#fff; font-family:'Poppins',sans-serif; overflow-x:hidden; }
a { text-decoration:none; }
.container { max-width:1200px; margin:0 auto; padding:0 24px; }

/* ===== PAGE HERO ===== */
.page-hero {
    background: linear-gradient(135deg, #0a0a0a 0%, #0f1a0f 50%, #0a0a0a 100%);
    padding: 80px 0 50px;
    position: relative; overflow: hidden;
    border-bottom: 1px solid rgba(255,123,0,0.1);
}
.page-hero::before {
    content:''; position:absolute; inset:0;
    background: radial-gradient(ellipse at 20% 50%, rgba(255,123,0,0.07) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(255,200,0,0.04) 0%, transparent 50%);
}
.page-hero .container { position:relative; z-index:2; }
.page-hero-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.3);
    padding:6px 16px; border-radius:30px;
    font-size:13px; color:#ff7b00; font-weight:600; margin-bottom:16px;
}
.page-hero h1 { font-size:clamp(30px,4vw,50px); font-weight:800; color:#fff; margin-bottom:12px; }
.page-hero h1 span { color:#ff7b00; }
.page-hero p { color:#888; font-size:15px; max-width:500px; line-height:1.7; }

/* ===== STATS ROW ===== */
.stats-row {
    display:grid; grid-template-columns:repeat(3,1fr);
    gap:16px; margin:40px 0;
}
.stat-card {
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:16px; padding:24px; text-align:center;
    transition:0.3s;
}
.stat-card:hover { border-color:rgba(255,123,0,0.3); transform:translateY(-3px); }
.stat-card .num {
    font-size:30px; font-weight:800;
    background:linear-gradient(135deg,#ff7b00,#ffb347);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    background-clip:text; line-height:1;
}
.stat-card .lbl { font-size:12px; color:#666; margin-top:6px; }

/* ===== MAIN SECTION ===== */
.track-section { padding:20px 0 80px; }

/* ===== ORDER DETAILS CARD ===== */
.order-card {
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:20px; padding:32px;
    margin-bottom:24px;
}
.card-header {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:24px; padding-bottom:16px;
    border-bottom:1px solid rgba(255,255,255,0.05);
}
.card-header h2 { font-size:18px; font-weight:800; }
.card-header h2 span { color:#ff7b00; }
.card-header p { color:#666; font-size:13px; margin-top:2px; }

.order-grid {
    display:grid; grid-template-columns:1fr 1fr;
    gap:0;
}
.order-row {
    display:flex; flex-direction:column;
    padding:14px 16px; border-bottom:1px solid rgba(255,255,255,0.04);
}
.order-row:nth-child(odd) { border-right:1px solid rgba(255,255,255,0.04); }
.order-row:nth-last-child(-n+2) { border-bottom:none; }
.order-row .key {
    font-size:11px; color:#555; font-weight:600;
    text-transform:uppercase; letter-spacing:0.5px; margin-bottom:5px;
}
.order-row .val { font-size:14px; color:#fff; font-weight:600; }
.order-row .val i { margin-right:6px; color:#ff7b00; }

/* Badges */
.badge {
    display:inline-block; padding:3px 12px;
    border-radius:20px; font-size:12px; font-weight:600;
}
.badge-orange { background:rgba(255,123,0,0.15); color:#ff7b00; }
.badge-green  { background:rgba(39,174,96,0.12);  color:#27ae60; }
.badge-yellow { background:rgba(255,193,7,0.12);  color:#ffc107; }

/* ===== TRACKER CARD ===== */
.tracker-card {
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:20px; padding:36px 32px;
    margin-bottom:24px;
}
.tracker-card .card-header { margin-bottom:36px; }

/* Progress track */
.tracker {
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    position:relative;
    padding:0 20px;
}
.tracker::before {
    content:'';
    position:absolute;
    top:22px; left:calc(10% + 20px); right:calc(10% + 20px);
    height:3px;
    background:rgba(255,255,255,0.06);
    z-index:0;
}
.tracker-progress {
    position:absolute;
    top:22px; left:calc(10% + 20px);
    height:3px;
    background:linear-gradient(90deg,#ff6b00,#ffb347);
    z-index:1;
    transition:width 0.7s ease;
    border-radius:3px;
}

.step {
    display:flex; flex-direction:column;
    align-items:center; gap:12px;
    position:relative; z-index:2; flex:1;
}
.step-circle {
    width:46px; height:46px;
    border-radius:50%;
    background:#161616;
    border:2px solid rgba(255,255,255,0.08);
    display:flex; align-items:center; justify-content:center;
    font-size:18px; transition:0.4s;
}
.step.done .step-circle {
    background:linear-gradient(135deg,#ff6b00,#ff9500);
    border-color:#ff7b00;
    box-shadow:0 0 20px rgba(255,123,0,0.35);
}
.step.active .step-circle {
    background:transparent;
    border-color:#ff7b00;
    border-width:2px;
    animation:pulse-step 1.5s infinite;
}
@keyframes pulse-step {
    0%,100% { box-shadow:0 0 0 0 rgba(255,123,0,0.4); }
    50%      { box-shadow:0 0 0 10px rgba(255,123,0,0); }
}
.step-label {
    font-size:13px; font-weight:600;
    color:#444; text-align:center;
}
.step.done .step-label,
.step.active .step-label { color:#fff; }
.step-time {
    font-size:11px; color:#444; text-align:center;
    background:rgba(255,255,255,0.04);
    padding:3px 10px; border-radius:20px;
}
.step.done .step-time,
.step.active .step-time { color:#ff9500; background:rgba(255,123,0,0.08); }

/* ===== STATUS MESSAGE ===== */
.status-msg {
    display:flex; align-items:center; gap:14px;
    padding:18px 22px; border-radius:14px;
    font-size:14px; font-weight:600; margin-top:30px;
}
.status-msg i { font-size:22px; flex-shrink:0; }
.status-msg .msg-text { line-height:1.5; }
.status-msg .msg-sub {
    display:block; font-size:12px; font-weight:400;
    color:inherit; opacity:0.75; margin-top:3px;
}
.status-msg.Pending  { background:rgba(255,193,7,0.08);  border:1px solid rgba(255,193,7,0.2);  color:#ffc107; }
.status-msg.Preparing{ background:rgba(255,123,0,0.08);  border:1px solid rgba(255,123,0,0.2);  color:#ff7b00; }
.status-msg.Completed{ background:rgba(39,174,96,0.08);  border:1px solid rgba(39,174,96,0.2);  color:#27ae60; }

/* ===== NOT FOUND CARD ===== */
.not-found-card {
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:20px; padding:60px 32px;
    text-align:center; max-width:500px; margin:40px auto;
}
.not-found-card i { font-size:48px; color:#333; margin-bottom:20px; display:block; }
.not-found-card h2 { font-size:22px; font-weight:800; margin-bottom:10px; }
.not-found-card p  { color:#666; font-size:14px; margin-bottom:28px; }

.search-row {
    display:flex; gap:10px;
}
.search-row input {
    flex:1; background:#161616; border:1px solid rgba(255,255,255,0.08);
    color:#fff; padding:12px 16px; border-radius:10px;
    font-size:14px; font-family:'Poppins',sans-serif;
    outline:none; transition:0.3s;
}
.search-row input:focus { border-color:#ff7b00; }
.search-row button {
    background:linear-gradient(135deg,#ff7b00,#ff9500);
    color:#fff; border:none; padding:12px 20px;
    border-radius:10px; font-size:14px; font-weight:600;
    cursor:pointer; font-family:'Poppins',sans-serif;
    white-space:nowrap; transition:0.3s;
}
.search-row button:hover { opacity:0.9; transform:translateY(-1px); }

/* ===== ACTION BUTTONS ===== */
.action-row {
    display:flex; gap:14px; justify-content:center;
    margin-top:10px; flex-wrap:wrap;
}
.btn-primary {
    background:linear-gradient(135deg,#ff7b00,#ff9500);
    color:#fff; padding:12px 28px; border-radius:10px;
    font-size:14px; font-weight:600;
    display:inline-flex; align-items:center; gap:8px;
    transition:0.3s;
}
.btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(255,123,0,0.3); color:#fff; }
.btn-secondary {
    background:rgba(255,255,255,0.05); color:#fff;
    border:1px solid rgba(255,255,255,0.1);
    padding:12px 28px; border-radius:10px;
    font-size:14px; font-weight:600;
    display:inline-flex; align-items:center; gap:8px;
    transition:0.3s;
}
.btn-secondary:hover { border-color:#ff7b00; color:#ff7b00; transform:translateY(-2px); }

/* ===== REFRESH NOTE ===== */
.refresh-note {
    text-align:center; font-size:12px; color:#444;
    margin:0 0 20px; display:flex; align-items:center;
    justify-content:center; gap:6px;
}
.refresh-note i { color:#ff7b00; }

/* ===== FLOAT BUTTONS ===== */
.instagram-float {
    position:fixed; bottom:90px; right:20px;
    width:46px; height:46px; border-radius:50%;
    background:linear-gradient(135deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:20px; z-index:999;
    box-shadow:0 4px 14px rgba(0,0,0,0.4); transition:0.3s;
}
.instagram-float:hover { transform:scale(1.1); color:#fff; }
.whatsapp-float {
    position:fixed; bottom:36px; right:20px;
    width:46px; height:46px; border-radius:50%;
    background:#25d366;
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:22px; z-index:999;
    box-shadow:0 4px 14px rgba(0,0,0,0.4); transition:0.3s;
}
.whatsapp-float:hover { transform:scale(1.1); color:#fff; }
.back-to-top {
    position:fixed; bottom:144px; right:20px;
    width:40px; height:40px; border-radius:50%;
    background:rgba(255,123,0,0.15); border:1px solid rgba(255,123,0,0.3);
    color:#ff7b00; font-size:15px; z-index:999;
    display:flex; align-items:center; justify-content:center;
    opacity:0; pointer-events:none; transition:0.3s;
}
.back-to-top.show { opacity:1; pointer-events:auto; }
.back-to-top:hover { background:#ff7b00; color:#fff; }
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="page-hero">
    <div class="container">
        <div class="page-hero-badge">
            <i class="fa fa-location-dot"></i> Live Order Tracking
        </div>
        <h1>Track Your <span>Order</span></h1>
        <p>Stay updated on your order status in real-time. We'll keep you posted every step of the way!</p>
    </div>
</div>

<div class="track-section">
    <div class="container">

    <?php if($order): ?>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="num">#<?= $order['id'] ?></div>
                <div class="lbl">Order ID</div>
            </div>
            <div class="stat-card">
                <div class="num">₹<?= number_format($order['total_amount'], 0) ?></div>
                <div class="lbl">Order Total</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= $order['status'] ?></div>
                <div class="lbl">Current Status</div>
            </div>
        </div>

        <!-- Order Details Card -->
        <div class="order-card">
            <div class="card-header">
                <div>
                    <h2>Order <span>#<?= $order['id'] ?></span> Details</h2>
                    <p>Placed on <?= date('d M Y, h:i A', strtotime($order['order_time'])) ?></p>
                </div>
                <span class="badge badge-orange">
                    <i class="fa fa-bag-shopping" style="margin-right:5px;"></i><?= $order['order_type'] ?? 'Dine In' ?>
                </span>
            </div>

            <div class="order-grid">
                <div class="order-row">
                    <span class="key"><i class="fa fa-bowl-food"></i> &nbsp;Items Ordered</span>
                    <span class="val"><?= htmlspecialchars($order['items']) ?></span>
                </div>
                <div class="order-row">
                    <span class="key"><i class="fa fa-indian-rupee-sign"></i> &nbsp;Total Amount</span>
                    <span class="val">₹<?= $order['total_amount'] ?></span>
                </div>
                <div class="order-row">
                    <span class="key"><i class="fa fa-credit-card"></i> &nbsp;Payment Method</span>
                    <span class="val"><?= $order['payment_method'] ?? 'COD' ?></span>
                </div>
                <div class="order-row">
                    <span class="key"><i class="fa fa-circle-check"></i> &nbsp;Payment Status</span>
                    <span class="val">
                        <?php $ps = $order['payment_status'] ?? 'Pending'; ?>
                        <span class="badge <?= $ps == 'Paid' ? 'badge-green' : 'badge-yellow' ?>">
                            <?= $ps ?>
                        </span>
                    </span>
                </div>
                <?php if(!empty($order['delivery_address'])): ?>
                <div class="order-row" style="grid-column:1/-1;">
                    <span class="key"><i class="fa fa-location-dot"></i> &nbsp;Delivery Address</span>
                    <span class="val"><?= htmlspecialchars($order['delivery_address']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tracker Card -->
        <div class="tracker-card">
            <div class="card-header">
                <div>
                    <h2>Order <span>Progress</span></h2>
                    <p>Live status of your order</p>
                </div>
            </div>

            <div class="tracker">
                <!-- Progress bar -->
                <div class="tracker-progress" style="width:<?= $current_step == 0 ? '0' : ($current_step == 1 ? '50%' : '80%') ?>"></div>

                <!-- Step 1: Order Placed -->
                <div class="step done">
                    <div class="step-circle"><i class="fa fa-check"></i></div>
                    <div class="step-label">Order Placed</div>
                    <div class="step-time"><?= date('h:i A', strtotime($order['order_time'])) ?></div>
                </div>

                <!-- Step 2: Preparing -->
                <div class="step <?= $current_step >= 1 ? ($current_step == 1 ? 'active' : 'done') : '' ?>">
                    <div class="step-circle">
                        <?= $current_step > 1 ? '<i class="fa fa-check"></i>' : '🍳' ?>
                    </div>
                    <div class="step-label">Preparing</div>
                    <div class="step-time"><?= $current_step >= 1 ? 'In progress' : 'Waiting...' ?></div>
                </div>

                <!-- Step 3: Ready / Delivered -->
                <div class="step <?= $current_step >= 2 ? 'done' : '' ?>">
                    <div class="step-circle">
                        <?= $current_step >= 2 ? '<i class="fa fa-check"></i>' : '🎉' ?>
                    </div>
                    <div class="step-label">
                        <?= (($order['order_type'] ?? 'Dine In') == 'Home Delivery') ? 'Delivered' : 'Ready' ?>
                    </div>
                    <div class="step-time"><?= $current_step >= 2 ? 'Completed!' : 'Soon...' ?></div>
                </div>
            </div>

            <!-- Status Message -->
            <?php
            $status = $order['status'];
            $is_delivery = ($order['order_type'] ?? '') == 'Home Delivery';
            ?>
            <div class="status-msg <?= $status ?>">
                <?php if($status == 'Pending'): ?>
                    <i class="fa fa-hourglass-half"></i>
                    <div class="msg-text">
                        Your order has been received!
                        <span class="msg-sub">We'll start preparing it shortly. Please hold on.</span>
                    </div>
                <?php elseif($status == 'Preparing'): ?>
                    <i class="fa fa-fire-burner"></i>
                    <div class="msg-text">
                        Your order is being prepared with love!
                        <span class="msg-sub">Our team is working on it. Won't be long!</span>
                    </div>
                <?php else: ?>
                    <i class="fa fa-circle-check"></i>
                    <div class="msg-text">
                        Your order is ready<?= $is_delivery ? ' for delivery' : '' ?>!
                        <span class="msg-sub">
                            <?= $is_delivery ? "Our delivery partner is on the way to you!" : "Please collect your order at the counter." ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Refresh note -->
        <?php if($order['status'] != 'Completed'): ?>
        <p class="refresh-note">
            <i class="fa fa-rotate"></i> Page auto-refreshes every 30 seconds
        </p>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-row">
            <a href="menu.php" class="btn-primary">
                <i class="fa fa-utensils"></i> Order More
            </a>
            <a href="index.php" class="btn-secondary">
                <i class="fa fa-house"></i> Home
            </a>
        </div>

    <?php else: ?>

        <!-- Not Found -->
        <div class="not-found-card">
            <i class="fa fa-magnifying-glass"></i>
            <h2>Order Not Found</h2>
            <p>Enter your order ID below to track your order status.</p>

            <form method="GET">
                <div class="search-row">
                    <input type="number" name="id" placeholder="Enter Order ID" min="1" required>
                    <button type="submit">
                        <i class="fa fa-magnifying-glass"></i> Track Order
                    </button>
                </div>
            </form>
        </div>

    <?php endif; ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Instagram Float -->
<a href="https://www.instagram.com/dropperscafe" class="instagram-float" target="_blank" title="Follow on Instagram">
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
// Auto refresh every 30 seconds if order not completed
<?php if($order && $order['status'] != 'Completed'): ?>
setTimeout(function(){ location.reload(); }, 30000);
<?php endif; ?>

// Back to top
const btt = document.getElementById('backToTop');
window.addEventListener('scroll', () => {
    btt.classList.toggle('show', window.scrollY > 300);
});
</script>

</body>
</html>