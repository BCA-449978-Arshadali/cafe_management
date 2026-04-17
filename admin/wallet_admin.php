<?php
include 'includes/auth.php';
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/wallet_helper.php';

wallet_ensure_tables($conn);

$success = '';
$error   = '';

// ── Manual Credit / Debit ───────────────────────────────────
if(isset($_POST['manual_action'])){
    $cust_id = intval($_POST['customer_id']);
    $amount  = floatval($_POST['amount']);
    $type    = $_POST['action_type']; // credit | debit
    $desc    = trim($_POST['description'] ?? 'Admin adjustment');

    if($cust_id <= 0 || $amount <= 0){
        $error = 'Invalid customer or amount!';
    } else {
        if($type === 'credit'){
            wallet_credit($conn, $cust_id, $amount, $desc, 'ADMIN');
            $success = "₹" . number_format($amount, 2) . " credited successfully!";
        } elseif($type === 'debit'){
            $ok = wallet_debit($conn, $cust_id, $amount, $desc, 'ADMIN');
            $success = $ok ? "₹" . number_format($amount, 2) . " debited successfully!" : '';
            $error   = $ok ? '' : 'Insufficient wallet balance!';
        }
    }
}

// ── Filters ─────────────────────────────────────────────────
$view       = $_GET['view'] ?? 'customers'; // customers | transactions
$search     = trim($_GET['search'] ?? '');
$txn_filter = $_GET['txn_type'] ?? ''; // credit | debit | ''

// ── Customers with wallet balance ───────────────────────────
$cust_where = $search ? "AND (c.name LIKE '%$search%' OR c.email LIKE '%$search%' OR c.mobile LIKE '%$search%')" : '';
$customers  = mysqli_query($conn,
    "SELECT c.id, c.name, c.email, c.mobile,
            COALESCE(w.balance, 0) as balance
     FROM customers c
     LEFT JOIN wallet w ON w.customer_id = c.id
     WHERE c.is_verified=1 $cust_where
     ORDER BY balance DESC"
);

// ── All Transactions ─────────────────────────────────────────
$txn_where = $txn_filter ? "AND wt.type='$txn_filter'" : '';
$txn_search = $search ? "AND (c.name LIKE '%$search%' OR c.email LIKE '%$search%')" : '';
$all_txns   = mysqli_query($conn,
    "SELECT wt.*, c.name as customer_name, c.email, c.mobile
     FROM wallet_transactions wt
     JOIN customers c ON c.id = wt.customer_id
     WHERE 1=1 $txn_where $txn_search
     ORDER BY wt.id DESC LIMIT 100"
);

// ── Summary Stats ────────────────────────────────────────────
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COUNT(DISTINCT customer_id) as total_wallets,
        COALESCE(SUM(balance), 0) as total_balance
     FROM wallet WHERE balance > 0"
));
$total_credited = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(amount),0) as total FROM wallet_transactions WHERE type='credit'"
))['total'];
$total_debited = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(amount),0) as total FROM wallet_transactions WHERE type='debit'"
))['total'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Wallet Management — Droppers Café Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
.stat-card  { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius); padding:20px; }
.stat-val   { font-size:26px; font-weight:800; color:var(--orange); }
.stat-lbl   { font-size:12px; color:var(--text-muted); margin-top:4px; }

.tab-bar { display:flex; gap:4px; background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius-sm); padding:5px; margin-bottom:20px; width:fit-content; }
.tab-btn { padding:9px 20px; border:none; background:none; color:var(--text-muted); font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; border-radius:6px; transition:0.2s; }
.tab-btn.active { background:rgba(255,123,0,0.12); color:var(--orange); }

.table-wrap { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
table { width:100%; border-collapse:collapse; }
th { background:var(--bg3); padding:13px 16px; text-align:left; font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid var(--border); }
td { padding:13px 16px; font-size:13px; border-bottom:1px solid rgba(255,255,255,0.04); color:var(--text); }
tr:last-child td { border-bottom:none; }
tr:hover td { background:rgba(255,255,255,0.02); }

.badge-credit { background:rgba(39,174,96,0.1); border:1px solid rgba(39,174,96,0.2); color:#27ae60; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.badge-debit  { background:rgba(231,76,60,0.1);  border:1px solid rgba(231,76,60,0.2);  color:#e74c3c;  padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }

.amount-credit { color:#27ae60; font-weight:700; }
.amount-debit  { color:#e74c3c; font-weight:700; }

/* Modal */
.modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); z-index:9999; align-items:center; justify-content:center; }
.modal-bg.show { display:flex; }
.modal { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius); padding:32px; width:100%; max-width:440px; animation:popIn 0.3s ease; }
@keyframes popIn { from{transform:scale(0.9);opacity:0} to{transform:scale(1);opacity:1} }
.modal h3 { font-size:18px; font-weight:700; margin-bottom:20px; }
.fg { margin-bottom:16px; }
.fg label { display:block; font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:7px; }
.fg input, .fg select, .fg textarea {
    width:100%; padding:11px 14px;
    background:var(--bg3); border:1px solid var(--border);
    border-radius:var(--radius-sm); color:var(--text);
    font-size:14px; font-family:'Poppins',sans-serif; outline:none;
}
.fg input:focus, .fg select:focus { border-color:var(--orange); }
.modal-actions { display:flex; gap:10px; margin-top:20px; }
.btn-credit { flex:1; background:rgba(39,174,96,0.15); color:#27ae60; border:1px solid rgba(39,174,96,0.3); padding:12px; border-radius:var(--radius-sm); font-size:14px; font-weight:700; cursor:pointer; font-family:'Poppins',sans-serif; transition:0.2s; }
.btn-debit  { flex:1; background:rgba(231,76,60,0.1); color:#e74c3c; border:1px solid rgba(231,76,60,0.25); padding:12px; border-radius:var(--radius-sm); font-size:14px; font-weight:700; cursor:pointer; font-family:'Poppins',sans-serif; transition:0.2s; }
.btn-credit:hover { background:rgba(39,174,96,0.25); }
.btn-debit:hover  { background:rgba(231,76,60,0.2); }
.btn-cancel { background:var(--bg3); color:var(--text-muted); border:1px solid var(--border); padding:12px 20px; border-radius:var(--radius-sm); cursor:pointer; font-family:'Poppins',sans-serif; }

.alert { padding:13px 18px; border-radius:var(--radius-sm); margin-bottom:20px; font-size:14px; font-weight:500; }
.alert-success { background:rgba(39,174,96,0.1); border:1px solid rgba(39,174,96,0.3); color:#27ae60; }
.alert-error   { background:rgba(231,76,60,0.1);  border:1px solid rgba(231,76,60,0.3);  color:#e74c3c; }

.search-bar { display:flex; gap:10px; margin-bottom:16px; }
.search-bar input { flex:1; padding:10px 14px; background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius-sm); color:var(--text); font-size:13px; font-family:'Poppins',sans-serif; outline:none; }
.search-bar input:focus { border-color:var(--orange); }
.search-bar select { padding:10px 14px; background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius-sm); color:var(--text); font-size:13px; font-family:'Poppins',sans-serif; outline:none; }
.btn-manage { background:rgba(255,123,0,0.1); color:var(--orange); border:1px solid rgba(255,123,0,0.25); padding:6px 14px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; font-family:'Poppins',sans-serif; transition:0.2s; }
.btn-manage:hover { background:rgba(255,123,0,0.2); }
@media(max-width:768px){ .stats-grid{grid-template-columns:1fr 1fr;} }
</style>
</head>
<body>
<?php include "includes/sidebar.php"; ?>

<div class="main">
    <div class="topbar">
        <h2><i class="fa fa-wallet" style="color:var(--orange);"></i> Wallet Management</h2>
    </div>

    <?php if($success): ?><div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if($error):   ?><div class="alert alert-error"><i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-val"><?= $stats['total_wallets'] ?></div>
            <div class="stat-lbl"><i class="fa fa-users"></i> Active Wallets</div>
        </div>
        <div class="stat-card">
            <div class="stat-val">₹<?= number_format($stats['total_balance'], 0) ?></div>
            <div class="stat-lbl"><i class="fa fa-wallet"></i> Total Balance</div>
        </div>
        <div class="stat-card">
            <div class="stat-val" style="color:#27ae60;">₹<?= number_format($total_credited, 0) ?></div>
            <div class="stat-lbl"><i class="fa fa-arrow-up"></i> Total Credited</div>
        </div>
        <div class="stat-card">
            <div class="stat-val" style="color:#e74c3c;">₹<?= number_format($total_debited, 0) ?></div>
            <div class="stat-lbl"><i class="fa fa-arrow-down"></i> Total Debited</div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tab-bar">
        <button class="tab-btn <?= $view=='customers'?'active':'' ?>" onclick="switchView('customers')">
            <i class="fa fa-users"></i> Customers
        </button>
        <button class="tab-btn <?= $view=='transactions'?'active':'' ?>" onclick="switchView('transactions')">
            <i class="fa fa-list"></i> All Transactions
        </button>
    </div>

    <!-- Search -->
    <form method="GET" class="search-bar">
        <input type="hidden" name="view" id="viewInput" value="<?= htmlspecialchars($view) ?>">
        <input type="text" name="search" placeholder="Search by name, email or mobile..." value="<?= htmlspecialchars($search) ?>">
        <?php if($view === 'transactions'): ?>
        <select name="txn_type">
            <option value="">All Types</option>
            <option value="credit" <?= $txn_filter=='credit'?'selected':'' ?>>Credit Only</option>
            <option value="debit"  <?= $txn_filter=='debit' ?'selected':'' ?>>Debit Only</option>
        </select>
        <?php endif; ?>
        <button type="submit" style="background:var(--orange); color:#fff; border:none; padding:10px 18px; border-radius:var(--radius-sm); cursor:pointer; font-family:'Poppins',sans-serif; font-weight:600;">
            <i class="fa fa-search"></i> Search
        </button>
    </form>

    <!-- Customers View -->
    <div id="view-customers" style="<?= $view!='customers'?'display:none':'' ?>">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Mobile</th>
                        <th>Wallet Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                while($c = mysqli_fetch_assoc($customers)):
                ?>
                <tr>
                    <td style="color:var(--text-muted);"><?= $no++ ?></td>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($c['name']) ?></div>
                        <div style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($c['email']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($c['mobile']) ?></td>
                    <td>
                        <span style="font-size:16px; font-weight:800; color:<?= $c['balance']>0 ? 'var(--orange)' : 'var(--text-muted)' ?>;">
                            ₹<?= number_format($c['balance'], 2) ?>
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn-manage" onclick="openModal(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>', <?= $c['balance'] ?>)">
                            <i class="fa fa-pen"></i> Manage
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Transactions View -->
    <div id="view-transactions" style="<?= $view!='transactions'?'display:none':'' ?>">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Balance After</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                while($t = mysqli_fetch_assoc($all_txns)):
                    $is_credit = $t['type'] === 'credit';
                ?>
                <tr>
                    <td style="color:var(--text-muted);"><?= $no++ ?></td>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($t['customer_name']) ?></div>
                        <div style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($t['mobile']) ?></div>
                    </td>
                    <td>
                        <span class="badge-<?= $t['type'] ?>"><?= ucfirst($t['type']) ?></span>
                    </td>
                    <td class="amount-<?= $t['type'] ?>">
                        <?= $is_credit ? '+' : '-' ?>₹<?= number_format($t['amount'], 2) ?>
                    </td>
                    <td style="color:var(--text-muted); font-size:12px;">
                        <?= htmlspecialchars($t['description']) ?>
                        <?php if($t['ref_id']): ?>
                        <div style="color:#333; font-size:10px;"><?= htmlspecialchars($t['ref_id']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--orange); font-weight:600;">₹<?= number_format($t['balance_after'], 2) ?></td>
                    <td style="color:var(--text-muted); font-size:12px;"><?= date('d M Y, h:i A', strtotime($t['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Manual Adjust Modal -->
<div class="modal-bg" id="manualModal">
    <div class="modal">
        <h3>💰 Adjust Wallet Balance</h3>
        <form method="POST">
            <input type="hidden" name="manual_action" value="1">
            <input type="hidden" name="customer_id" id="modal_cust_id">
            <input type="hidden" name="action_type" id="modal_action_type" value="credit">

            <div class="fg">
                <label>Customer</label>
                <input type="text" id="modal_cust_name" disabled style="opacity:0.5;">
            </div>
            <div class="fg">
                <label>Current Balance</label>
                <input type="text" id="modal_balance" disabled style="opacity:0.5; color:#ff7b00;">
            </div>
            <div class="fg">
                <label>Amount (₹) *</label>
                <input type="number" name="amount" min="1" max="50000" step="0.01" placeholder="Enter amount" required>
            </div>
            <div class="fg">
                <label>Description</label>
                <input type="text" name="description" placeholder="e.g. Refund for Order #123" value="Admin adjustment">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn-debit" onclick="submitModal('debit')">
                    <i class="fa fa-minus"></i> Debit
                </button>
                <button type="button" class="btn-credit" onclick="submitModal('credit')">
                    <i class="fa fa-plus"></i> Credit
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function switchView(v){
    document.getElementById('viewInput').value = v;
    document.getElementById('view-customers').style.display    = v==='customers'    ? '' : 'none';
    document.getElementById('view-transactions').style.display = v==='transactions' ? '' : 'none';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
}
function openModal(id, name, balance){
    document.getElementById('modal_cust_id').value   = id;
    document.getElementById('modal_cust_name').value = name;
    document.getElementById('modal_balance').value   = '₹' + parseFloat(balance).toFixed(2);
    document.getElementById('manualModal').classList.add('show');
}
function closeModal(){
    document.getElementById('manualModal').classList.remove('show');
}
function setType(t){
    document.getElementById('modal_action_type').value = t;
}
function submitModal(type){
    // Pehle type set karo, phir form submit karo
    document.getElementById('modal_action_type').value = type;
    document.querySelector('#manualModal form').submit();
}
// Close on outside click
document.getElementById('manualModal').addEventListener('click', function(e){
    if(e.target === this) closeModal();
});
</script>
</body>
</html>