<?php
ob_start();
include '../config.php';
require_once 'includes/auth-check.php';
require_once dirname(__DIR__) . '/includes/wallet_helper.php';

$customer_id   = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'] ?? '';

// ── Ensure tables exist ─────────────────────────────────────
wallet_ensure_tables($conn);

// ── Handle Razorpay wallet top-up ──────────────────────────
if(isset($_POST['razorpay_payment_id']) && isset($_POST['topup_amount'])){
    $amount     = floatval($_POST['topup_amount']);
    $payment_id = $_POST['razorpay_payment_id'];
    if($amount >= 10 && $amount <= 10000){
        wallet_credit($conn, $customer_id, $amount, 'Wallet Top-up via Razorpay', $payment_id);
        $_SESSION['wallet_success'] = "₹" . number_format($amount, 2) . " added to your wallet! 🎉";
    }
    header("Location: wallet.php"); exit;
}

// ── Handle Wallet Pay (from cart) ──────────────────────────
if(isset($_POST['pay_via_wallet'])){
    $order_total = floatval($_POST['order_total'] ?? 0);
    $balance     = wallet_balance($conn, $customer_id);
    if($balance >= $order_total && $order_total > 0){
        $success = wallet_debit($conn, $customer_id, $order_total, 'Order Payment', 'ORDER-' . time());
        if($success){
            $_SESSION['wallet_paid']   = true;
            $_SESSION['wallet_amount'] = $order_total;
        }
    }
    header("Location: cart.php"); exit;
}

$balance      = wallet_balance($conn, $customer_id);
$transactions = wallet_transactions($conn, $customer_id, 30);
$success_msg  = $_SESSION['wallet_success'] ?? '';
unset($_SESSION['wallet_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet — Droppers Café</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body { background:#0d0d0d; color:#fff; font-family:'Poppins',sans-serif; min-height:100vh; }
a { text-decoration:none; }
.page { max-width:900px; margin:0 auto; padding:32px 20px 80px; }

/* ── Hero Balance Card ── */
.wallet-hero {
    background:linear-gradient(135deg, #1a0a00 0%, #0f1a0f 40%, #0a0a1a 100%);
    border:1px solid rgba(255,123,0,0.2);
    border-radius:24px; padding:40px 36px;
    margin-bottom:24px; position:relative; overflow:hidden;
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:24px;
}
.wallet-hero::before {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse at 15% 50%, rgba(255,123,0,0.1) 0%, transparent 55%),
               radial-gradient(ellipse at 85% 20%, rgba(100,100,255,0.05) 0%, transparent 50%);
}
.wallet-icon {
    width:80px; height:80px; border-radius:50%;
    background:linear-gradient(135deg,#ff7b00,#ff9500);
    display:flex; align-items:center; justify-content:center;
    font-size:36px; flex-shrink:0;
    box-shadow:0 0 40px rgba(255,123,0,0.4);
    animation:glow 3s ease-in-out infinite; position:relative; z-index:1;
}
@keyframes glow {
    0%,100%{ box-shadow:0 0 30px rgba(255,123,0,0.3); }
    50%    { box-shadow:0 0 55px rgba(255,123,0,0.6); }
}
.wallet-info { position:relative; z-index:1; }
.wallet-label { font-size:13px; color:#666; font-weight:600; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px; }
.wallet-balance { font-size:48px; font-weight:800; color:#ff7b00; line-height:1; }
.wallet-balance span { font-size:24px; color:#ff9500; }
.wallet-sub { font-size:13px; color:#444; margin-top:8px; }

/* ── Add Money Card ── */
.card {
    background:#111; border:1px solid rgba(255,255,255,0.07);
    border-radius:20px; padding:28px; margin-bottom:20px;
}
.card-title {
    font-size:15px; font-weight:700; color:#ff7b00;
    margin-bottom:20px; padding-bottom:14px;
    border-bottom:1px solid rgba(255,255,255,0.06);
    display:flex; align-items:center; gap:8px;
}

/* ── Amount Presets ── */
.preset-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:16px; }
.preset-btn {
    background:#0d0d0d; border:1px solid rgba(255,255,255,0.08);
    border-radius:12px; padding:12px 8px; text-align:center;
    color:#aaa; font-size:14px; font-weight:600;
    cursor:pointer; transition:all 0.2s; font-family:'Poppins',sans-serif;
}
.preset-btn:hover, .preset-btn.active {
    background:rgba(255,123,0,0.1); border-color:rgba(255,123,0,0.4); color:#ff7b00;
}
.input-group { display:flex; gap:10px; align-items:stretch; }
.input-group input {
    flex:1; padding:13px 16px; background:#0d0d0d;
    border:1px solid rgba(255,255,255,0.08); border-radius:12px;
    color:#fff; font-size:16px; font-weight:700;
    font-family:'Poppins',sans-serif; outline:none; transition:0.2s;
}
.input-group input:focus { border-color:rgba(255,123,0,0.5); }
.btn-add {
    background:linear-gradient(135deg,#ff7b00,#ff9500);
    color:#fff; border:none; padding:13px 28px;
    border-radius:12px; font-size:15px; font-weight:700;
    cursor:pointer; font-family:'Poppins',sans-serif;
    transition:all 0.3s; white-space:nowrap;
    display:flex; align-items:center; gap:8px;
    box-shadow:0 6px 20px rgba(255,123,0,0.3);
}
.btn-add:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(255,123,0,0.45); }
.input-hint { font-size:11px; color:#333; margin-top:8px; }

/* ── Transactions ── */
.txn-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:14px 0; border-bottom:1px solid rgba(255,255,255,0.05);
    gap:12px; flex-wrap:wrap;
}
.txn-row:last-child { border-bottom:none; }
.txn-left { display:flex; align-items:center; gap:12px; }
.txn-icon {
    width:42px; height:42px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:18px; flex-shrink:0;
}
.txn-icon.credit { background:rgba(39,174,96,0.1); border:1px solid rgba(39,174,96,0.2); }
.txn-icon.debit  { background:rgba(231,76,60,0.1);  border:1px solid rgba(231,76,60,0.2); }
.txn-desc { font-size:13px; font-weight:600; color:#ddd; }
.txn-time { font-size:11px; color:#444; margin-top:2px; }
.txn-amount { font-size:16px; font-weight:800; white-space:nowrap; }
.txn-amount.credit { color:#27ae60; }
.txn-amount.debit  { color:#e74c3c; }
.txn-bal { font-size:11px; color:#333; text-align:right; margin-top:2px; }
.empty { text-align:center; padding:40px 20px; }
.empty i { font-size:40px; color:#1e1e1e; display:block; margin-bottom:12px; }
.empty p { color:#333; font-size:13px; }

/* ── Alert ── */
.alert-ok {
    background:rgba(39,174,96,0.08); border:1px solid rgba(39,174,96,0.3);
    color:#27ae60; padding:14px 18px; border-radius:14px;
    font-size:14px; font-weight:600; margin-bottom:20px;
    display:flex; align-items:center; gap:10px;
    animation:slideIn 0.4s ease;
}
@keyframes slideIn { from{transform:translateY(-10px);opacity:0} to{transform:translateY(0);opacity:1} }

@media(max-width:600px){
    .wallet-hero { padding:24px 18px; }
    .wallet-balance { font-size:36px; }
    .preset-grid { grid-template-columns:repeat(2,1fr); }
    .page { padding:16px 14px 60px; }
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="page">

    <?php if($success_msg): ?>
    <div class="alert-ok"><i class="fa fa-circle-check"></i> <?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>

    <!-- Hero Balance -->
    <div class="wallet-hero">
        <div style="display:flex; align-items:center; gap:20px; position:relative; z-index:1;">
            <div class="wallet-icon">👛</div>
            <div class="wallet-info">
                <div class="wallet-label">Wallet Balance</div>
                <div class="wallet-balance"><span>₹</span><?= number_format($balance, 2) ?></div>
                <div class="wallet-sub">Available to use on orders</div>
            </div>
        </div>
        <div style="position:relative; z-index:1; display:flex; gap:10px; flex-wrap:wrap;">
            <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:14px; padding:16px 24px; text-align:center;">
                <div style="font-size:20px; font-weight:800; color:#ff7b00;"><?= count($transactions) ?></div>
                <div style="font-size:11px; color:#444; margin-top:2px;">Transactions</div>
            </div>
            <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:14px; padding:16px 24px; text-align:center;">
                <?php
                $total_added = array_sum(array_column(array_filter($transactions, fn($t) => $t['type']==='credit'), 'amount'));
                ?>
                <div style="font-size:20px; font-weight:800; color:#27ae60;">₹<?= number_format($total_added, 0) ?></div>
                <div style="font-size:11px; color:#444; margin-top:2px;">Total Added</div>
            </div>
        </div>
    </div>

    <!-- Add Money -->
    <div class="card">
        <div class="card-title"><i class="fa fa-plus-circle"></i> Add Money to Wallet</div>

        <div class="preset-grid">
            <?php foreach([50, 100, 200, 500, 1000, 2000] as $amt): ?>
            <button class="preset-btn" onclick="setAmount(<?= $amt ?>)">₹<?= $amt ?></button>
            <?php endforeach; ?>
        </div>

        <div class="input-group">
            <input type="number" id="topupAmount" placeholder="Enter amount (₹10 – ₹10,000)"
                   min="10" max="10000" step="1" oninput="clearPreset()">
            <button class="btn-add" onclick="initiateTopup()">
                <i class="fa fa-bolt"></i> Add Money
            </button>
        </div>
        <div class="input-hint">💳 Secure payment via Razorpay — UPI, Cards, Net Banking accepted</div>
    </div>

    <!-- Transaction History -->
    <div class="card">
        <div class="card-title"><i class="fa fa-clock-rotate-left"></i> Transaction History</div>

        <?php if(empty($transactions)): ?>
        <div class="empty">
            <i class="fa fa-wallet"></i>
            <p>No transactions yet. Add money to get started!</p>
        </div>
        <?php else: ?>
        <?php foreach($transactions as $txn):
            $is_credit = $txn['type'] === 'credit';
            $icon      = $is_credit ? '⬆️' : '⬇️';
        ?>
        <div class="txn-row">
            <div class="txn-left">
                <div class="txn-icon <?= $txn['type'] ?>"><?= $icon ?></div>
                <div>
                    <div class="txn-desc"><?= htmlspecialchars($txn['description']) ?></div>
                    <div class="txn-time"><?= date('d M Y, h:i A', strtotime($txn['created_at'])) ?>
                        <?php if($txn['ref_id']): ?>
                        · <span style="color:#333; font-size:10px;"><?= htmlspecialchars($txn['ref_id']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div style="text-align:right;">
                <div class="txn-amount <?= $txn['type'] ?>">
                    <?= $is_credit ? '+' : '-' ?>₹<?= number_format($txn['amount'], 2) ?>
                </div>
                <div class="txn-bal">Balance: ₹<?= number_format($txn['balance_after'], 2) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php include 'includes/footer.php'; ?>

<!-- Razorpay -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var selectedAmount = 0;

function setAmount(amt){
    selectedAmount = amt;
    document.getElementById('topupAmount').value = amt;
    document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
}
function clearPreset(){
    selectedAmount = 0;
    document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
}

function initiateTopup(){
    var amt = parseFloat(document.getElementById('topupAmount').value);
    if(!amt || amt < 10 || amt > 10000){
        alert('Please enter amount between ₹10 and ₹10,000!');
        return;
    }

    var options = {
        key: 'YOUR_RAZORPAY_KEY',   // ← Apna Razorpay key daalo
        amount: Math.round(amt * 100),
        currency: 'INR',
        name: 'Droppers Café',
        description: 'Wallet Top-up',
        image: '<?= BASE_URL ?>/assets/images/droppers-logo.png',
        handler: function(response){
            // Server ko payment confirm karo
            var form = document.createElement('form');
            form.method = 'POST'; form.action = 'wallet.php';
            var fields = {
                'razorpay_payment_id': response.razorpay_payment_id,
                'topup_amount': amt
            };
            for(var k in fields){
                var inp = document.createElement('input');
                inp.type='hidden'; inp.name=k; inp.value=fields[k];
                form.appendChild(inp);
            }
            document.body.appendChild(form); form.submit();
        },
        prefill: {
            name: '<?= htmlspecialchars($customer_name) ?>',
            contact: '<?= $_SESSION['customer_phone'] ?? '' ?>'
        },
        theme: { color: '#ff7b00' }
    };
    new Razorpay(options).open();
}
</script>
</body>
</html>