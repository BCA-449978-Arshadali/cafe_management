<?php
include 'includes/auth.php';
include 'includes/db.php';

// Auto-setup tables
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `loyalty_points` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) DEFAULT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `customer_name` VARCHAR(150) DEFAULT NULL,
  `total_points` INT(11) NOT NULL DEFAULT 0,
  `redeemed_points` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uniq_phone` (`customer_phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `loyalty_transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_phone` VARCHAR(20) NOT NULL,
  `order_id` INT(11) DEFAULT NULL,
  `type` ENUM('earn','redeem','bonus','deduct') NOT NULL DEFAULT 'earn',
  `points` INT(11) NOT NULL DEFAULT 0,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `loyalty_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uniq_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Default settings insert
$defaults = ['points_per_10rs'=>'1','redeem_rate'=>'100','min_redeem_points'=>'100','welcome_bonus'=>'50','points_enabled'=>'1'];
foreach($defaults as $k => $v) {
    mysqli_query($conn, "INSERT IGNORE INTO loyalty_settings (setting_key, setting_value) VALUES ('$k', '$v')");
}

include dirname(__DIR__) . '/includes/loyalty_helper.php';

$success = ''; $error = '';

// ── Save Settings ──────────────────────────────────────────────────────
if(isset($_POST['save_settings'])) {
    $keys = ['points_per_10rs','redeem_rate','min_redeem_points','welcome_bonus','points_enabled'];
    foreach($keys as $k) {
        $v = mysqli_real_escape_string($conn, $_POST[$k] ?? '0');
        mysqli_query($conn, "UPDATE loyalty_settings SET setting_value='$v' WHERE setting_key='$k'");
    }
    $success = 'Settings saved successfully! ✅';
}

// ── Add Bonus Points ───────────────────────────────────────────────────
if(isset($_POST['add_bonus'])) {
    $phone  = trim($_POST['bonus_phone'] ?? '');
    $pts    = intval($_POST['bonus_points'] ?? 0);
    $reason = trim($_POST['bonus_reason'] ?? 'Admin bonus');
    $name   = trim($_POST['bonus_name']   ?? 'Customer');
    if(empty($phone) || $pts <= 0) {
        $error = 'Phone number aur valid points required hain!';
    } else {
        loyalty_add_bonus($conn, $phone, $name, $pts, $reason);
        $success = "$pts bonus points added to $phone! ✅";
    }
}

// ── Load Data ──────────────────────────────────────────────────────────
$search      = trim($_GET['search'] ?? '');
$where       = $search ? "WHERE customer_phone LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR customer_name LIKE '%".mysqli_real_escape_string($conn,$search)."%'" : '';
$customers   = mysqli_query($conn, "SELECT *, (total_points - redeemed_points) as balance FROM loyalty_points $where ORDER BY total_points DESC");
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM loyalty_points"))['c'];
$total_pts   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_points) as s FROM loyalty_points"))['s'] ?? 0;
$total_red   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(redeemed_points) as s FROM loyalty_points"))['s'] ?? 0;

// Settings
$s_enabled  = loyalty_setting($conn, 'points_enabled');
$s_per10    = loyalty_setting($conn, 'points_per_10rs');
$s_rate     = loyalty_setting($conn, 'redeem_rate');
$s_min      = loyalty_setting($conn, 'min_redeem_points');
$s_bonus    = loyalty_setting($conn, 'welcome_bonus');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Loyalty Points — Droppers Café Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.tabs { display:flex; gap:4px; margin-bottom:24px; border-bottom:1px solid var(--border); }
.tab-btn {
    padding:10px 20px; border:none; background:none;
    color:var(--text-muted); font-size:14px; font-weight:600;
    cursor:pointer; border-bottom:2px solid transparent;
    font-family:'Poppins',sans-serif; transition:0.2s;
}
.tab-btn.active { color:var(--orange); border-bottom-color:var(--orange); }
.tab-panel { display:none; }
.tab-panel.active { display:block; }

.stats-row { display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
.stat-card {
    background:var(--bg2); border:1px solid var(--border);
    border-radius:var(--radius); padding:20px 28px;
    display:flex; align-items:center; gap:16px; flex:1; min-width:160px;
}
.stat-card i { font-size:28px; color:var(--orange); }
.stat-card .val { font-size:26px; font-weight:800; }
.stat-card .lbl { font-size:12px; color:var(--text-muted); margin-top:2px; }

.table-wrap { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
table { width:100%; border-collapse:collapse; }
th { background:var(--bg3); padding:12px 16px; text-align:left; font-size:12px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; }
td { padding:12px 16px; border-top:1px solid var(--border); font-size:13px; vertical-align:middle; }
tr:hover td { background:rgba(255,255,255,0.02); }

.badge-pts { background:var(--orange-bg); border:1px solid var(--orange-bd); color:var(--orange); padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; }
.badge-green { background:rgba(39,174,96,0.1); border:1px solid rgba(39,174,96,0.25); color:#27ae60; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; }
.badge-red   { background:rgba(231,76,60,0.1);  border:1px solid rgba(231,76,60,0.25);  color:#e74c3c;  padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; }
.badge-blue  { background:rgba(52,152,219,0.1); border:1px solid rgba(52,152,219,0.25); color:#3498db;  padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; }

.form-card { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius); padding:28px; max-width:600px; margin-bottom:28px; }
.form-card h3 { font-size:15px; font-weight:700; margin-bottom:20px; color:var(--orange); }
.fg { margin-bottom:16px; }
.fg label { display:block; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px; }
.fg input, .fg select { width:100%; padding:11px 14px; background:var(--bg3); border:1px solid var(--border); border-radius:var(--radius-sm); color:var(--text); font-size:13px; font-family:'Poppins',sans-serif; }
.fg input:focus { outline:none; border-color:var(--orange); }
.frow { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.btn-save { background:var(--orange); color:#fff; border:none; padding:11px 26px; border-radius:var(--radius-sm); font-size:14px; font-weight:600; cursor:pointer; font-family:'Poppins',sans-serif; display:inline-flex; align-items:center; gap:6px; }
.btn-save:hover { background:var(--orange2); }

.setting-row { display:flex; align-items:center; justify-content:space-between; padding:14px 0; border-bottom:1px solid var(--border); }
.setting-row:last-child { border-bottom:none; }
.setting-label { font-size:14px; font-weight:600; }
.setting-sub   { font-size:12px; color:var(--text-muted); margin-top:2px; }
.setting-input { width:100px; padding:8px 12px; background:var(--bg3); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:14px; font-weight:700; text-align:center; font-family:'Poppins',sans-serif; }
.setting-input:focus { outline:none; border-color:var(--orange); }

.search-bar { display:flex; gap:10px; margin-bottom:16px; }
.search-bar input { flex:1; padding:10px 14px; background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius-sm); color:var(--text); font-size:13px; font-family:'Poppins',sans-serif; }
.search-bar input:focus { outline:none; border-color:var(--orange); }
.search-bar button { background:var(--orange); color:#fff; border:none; padding:10px 18px; border-radius:var(--radius-sm); cursor:pointer; font-size:13px; }

.alert { padding:13px 18px; border-radius:var(--radius-sm); margin-bottom:20px; font-size:13px; font-weight:600; }
.alert-s { background:rgba(39,174,96,0.1); border:1px solid rgba(39,174,96,0.3); color:#27ae60; }
.alert-e { background:rgba(231,76,60,0.1);  border:1px solid rgba(231,76,60,0.3);  color:#e74c3c; }

.toggle-switch { position:relative; display:inline-block; width:46px; height:24px; }
.toggle-switch input { opacity:0; width:0; height:0; }
.toggle-slider { position:absolute; cursor:pointer; inset:0; background:#444; border-radius:24px; transition:.3s; }
.toggle-slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background:white; border-radius:50%; transition:.3s; }
.toggle-switch input:checked + .toggle-slider { background:var(--orange); }
.toggle-switch input:checked + .toggle-slider:before { transform:translateX(22px); }

.empty-row td { text-align:center; padding:40px; color:var(--text-muted); }
</style>
</head>
<body>
<?php include "includes/sidebar.php"; ?>

<div class="main">
    <div class="topbar">
        <h2><i class="fa fa-trophy" style="color:var(--orange);font-size:20px;"></i> Loyalty Points</h2>
        <div class="topbar-right">
            <div class="topbar-date">📅 <?= date('l, d M Y') ?></div>
        </div>
    </div>

    <?php if($success): ?><div class="alert alert-s"><i class="fa fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if($error):   ?><div class="alert alert-e"><i class="fa fa-times-circle"></i> <?= $error ?></div><?php endif; ?>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('customers', this)"><i class="fa fa-users"></i> Customers</button>
        <button class="tab-btn" onclick="switchTab('bonus', this)"><i class="fa fa-gift"></i> Add Bonus</button>
        <button class="tab-btn" onclick="switchTab('history', this)"><i class="fa fa-clock-rotate-left"></i> History</button>
        <button class="tab-btn" onclick="switchTab('settings', this)"><i class="fa fa-gear"></i> Settings</button>
    </div>

    <!-- ── TAB 1: CUSTOMERS ── -->
    <div class="tab-panel active" id="tab-customers">
        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <i class="fa fa-users"></i>
                <div><div class="val"><?= $total_users ?></div><div class="lbl">Total Members</div></div>
            </div>
            <div class="stat-card">
                <i class="fa fa-star"></i>
                <div><div class="val"><?= number_format($total_pts) ?></div><div class="lbl">Total Points Earned</div></div>
            </div>
            <div class="stat-card">
                <i class="fa fa-gift" style="color:#27ae60;"></i>
                <div><div class="val" style="color:#27ae60;"><?= number_format($total_red) ?></div><div class="lbl">Points Redeemed</div></div>
            </div>
            <div class="stat-card">
                <i class="fa fa-coins" style="color:#f0a500;"></i>
                <div><div class="val" style="color:#f0a500;"><?= number_format($total_pts - $total_red) ?></div><div class="lbl">Active Balance</div></div>
            </div>
        </div>

        <!-- Search -->
        <form method="GET">
            <input type="hidden" name="tab" value="customers">
            <div class="search-bar">
                <input type="text" name="search" placeholder="Search by phone or name..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit"><i class="fa fa-search"></i> Search</button>
                <?php if($search): ?><a href="loyalty.php" style="background:var(--bg3); color:var(--text); border:1px solid var(--border); padding:10px 14px; border-radius:var(--radius-sm); font-size:13px;">✕ Clear</a><?php endif; ?>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Total Earned</th>
                        <th>Redeemed</th>
                        <th>Balance</th>
                        <th>Value</th>
                        <th>Since</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                if(mysqli_num_rows($customers) == 0): ?>
                    <tr class="empty-row"><td colspan="8"><i class="fa fa-trophy" style="font-size:32px; display:block; margin-bottom:10px; color:var(--text-dim);"></i>No loyalty members yet</td></tr>
                <?php else: while($row = mysqli_fetch_assoc($customers)):
                    $balance = max(0, $row['total_points'] - $row['redeemed_points']);
                    $rupee_val = points_to_rupees($conn, $balance);
                ?>
                    <tr>
                        <td style="color:var(--text-dim);"><?= $i++ ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:34px; height:34px; border-radius:50%; background:var(--orange-bg); border:1px solid var(--orange-bd); display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--orange); font-size:14px; flex-shrink:0;">
                                    <?= strtoupper(substr($row['customer_name'] ?? 'C', 0, 1)) ?>
                                </div>
                                <span style="font-weight:600;"><?= htmlspecialchars($row['customer_name'] ?? '—') ?></span>
                            </div>
                        </td>
                        <td style="font-family:monospace; color:var(--text-muted);"><?= htmlspecialchars($row['customer_phone']) ?></td>
                        <td><span class="badge-pts">🏆 <?= number_format($row['total_points']) ?></span></td>
                        <td><span class="badge-red"><?= number_format($row['redeemed_points']) ?></span></td>
                        <td><span class="badge-green" style="font-size:13px; font-weight:800;"><?= number_format($balance) ?> pts</span></td>
                        <td style="color:#27ae60; font-weight:700;">₹<?= number_format($rupee_val, 2) ?></td>
                        <td style="color:var(--text-dim); font-size:12px;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── TAB 2: ADD BONUS ── -->
    <div class="tab-panel" id="tab-bonus">
        <div class="form-card">
            <h3><i class="fa fa-gift"></i> Add Bonus Points to Customer</h3>
            <form method="POST">
                <div class="frow">
                    <div class="fg">
                        <label>Customer Phone *</label>
                        <input type="text" name="bonus_phone" placeholder="+91XXXXXXXXXX" required>
                    </div>
                    <div class="fg">
                        <label>Customer Name</label>
                        <input type="text" name="bonus_name" placeholder="Customer ka naam">
                    </div>
                </div>
                <div class="frow">
                    <div class="fg">
                        <label>Bonus Points *</label>
                        <input type="number" name="bonus_points" min="1" placeholder="e.g. 50" required>
                    </div>
                    <div class="fg">
                        <label>Reason</label>
                        <input type="text" name="bonus_reason" placeholder="e.g. Birthday bonus, Special event">
                    </div>
                </div>
                <button type="submit" name="add_bonus" class="btn-save">
                    <i class="fa fa-plus"></i> Add Bonus Points
                </button>
            </form>
        </div>

        <div style="background:var(--bg2); border:1px solid var(--orange-bd); border-radius:var(--radius); padding:20px; max-width:600px;">
            <h3 style="color:var(--orange); font-size:14px; margin-bottom:12px;"><i class="fa fa-lightbulb"></i> When to give bonus points?</h3>
            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:10px;">
                <li style="color:var(--text-muted); font-size:13px;">🎂 Customer birthday pe</li>
                <li style="color:var(--text-muted); font-size:13px;">🎉 Special events ya festivals pe</li>
                <li style="color:var(--text-muted); font-size:13px;">⭐ Review dene ke baad</li>
                <li style="color:var(--text-muted); font-size:13px;">🤝 Referral bonus</li>
                <li style="color:var(--text-muted); font-size:13px;">💬 Complaint resolve hone par compensation</li>
            </ul>
        </div>
    </div>

    <!-- ── TAB 3: HISTORY ── -->
    <div class="tab-panel" id="tab-history">
        <?php
        $history = mysqli_query($conn, "SELECT * FROM loyalty_transactions ORDER BY created_at DESC LIMIT 100");
        ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th>Points</th>
                        <th>Order #</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(mysqli_num_rows($history) == 0): ?>
                    <tr class="empty-row"><td colspan="6">No transactions yet</td></tr>
                <?php else: while($tx = mysqli_fetch_assoc($history)):
                    $type_badges = [
                        'earn'   => '<span class="badge-green">+ Earned</span>',
                        'redeem' => '<span class="badge-red">− Redeemed</span>',
                        'bonus'  => '<span class="badge-blue">★ Bonus</span>',
                        'deduct' => '<span class="badge-red">− Deducted</span>',
                    ];
                    $sign   = in_array($tx['type'], ['earn','bonus']) ? '+' : '−';
                    $color  = in_array($tx['type'], ['earn','bonus']) ? '#27ae60' : '#e74c3c';
                ?>
                    <tr>
                        <td style="font-size:12px; color:var(--text-muted);"><?= date('d M Y, h:i A', strtotime($tx['created_at'])) ?></td>
                        <td style="font-family:monospace; font-size:12px;"><?= htmlspecialchars($tx['customer_phone']) ?></td>
                        <td><?= $type_badges[$tx['type']] ?? $tx['type'] ?></td>
                        <td style="font-weight:800; color:<?= $color ?>; font-size:15px;"><?= $sign ?><?= $tx['points'] ?></td>
                        <td style="color:var(--text-muted);"><?= $tx['order_id'] ? '#'.$tx['order_id'] : '—' ?></td>
                        <td style="color:var(--text-muted); font-size:12px;"><?= htmlspecialchars($tx['description'] ?? '') ?></td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── TAB 4: SETTINGS ── -->
    <div class="tab-panel" id="tab-settings">
        <div class="form-card" style="max-width:700px;">
            <h3><i class="fa fa-gear"></i> Loyalty System Settings</h3>
            <form method="POST">
                <!-- Enable/Disable -->
                <div class="setting-row">
                    <div>
                        <div class="setting-label">Loyalty System</div>
                        <div class="setting-sub">ON karo toh customers points earn/redeem kar sakte hain</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="points_enabled" value="1" <?= $s_enabled == '1' ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <!-- Points per ₹10 -->
                <div class="setting-row">
                    <div>
                        <div class="setting-label">Points per ₹10 spent</div>
                        <div class="setting-sub">Customer ₹10 spend kare toh kitne points milenge?</div>
                    </div>
                    <input type="number" name="points_per_10rs" class="setting-input" min="1" max="100" value="<?= $s_per10 ?>">
                </div>

                <!-- Redeem Rate -->
                <div class="setting-row">
                    <div>
                        <div class="setting-label">Redeem Rate (Points → ₹)</div>
                        <div class="setting-sub">Kitne points = ₹10 discount? (default: 100 pts = ₹10)</div>
                    </div>
                    <input type="number" name="redeem_rate" class="setting-input" min="1" value="<?= $s_rate ?>">
                </div>

                <!-- Min Redeem -->
                <div class="setting-row">
                    <div>
                        <div class="setting-label">Minimum Redeem Points</div>
                        <div class="setting-sub">Kam se kam kitne points redeem kar sakte hain?</div>
                    </div>
                    <input type="number" name="min_redeem_points" class="setting-input" min="1" value="<?= $s_min ?>">
                </div>

                <!-- Welcome Bonus -->
                <div class="setting-row">
                    <div>
                        <div class="setting-label">Welcome Bonus Points</div>
                        <div class="setting-sub">New customer ke pehle order par bonus points</div>
                    </div>
                    <input type="number" name="welcome_bonus" class="setting-input" min="0" value="<?= $s_bonus ?>">
                </div>

                <div style="margin-top:24px; padding:16px; background:var(--bg3); border-radius:var(--radius-sm); margin-bottom:20px; font-size:13px; color:var(--text-muted); line-height:1.7;">
                    💡 <strong style="color:var(--text);">Current Setup:</strong><br>
                    Customer ₹<?= intval($s_rate * 10) ?> spend kare → <?= intval($s_per10 * $s_rate) ?> points milenge → ₹10 discount milega
                </div>

                <button type="submit" name="save_settings" class="btn-save">
                    <i class="fa fa-save"></i> Save Settings
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
// URL se tab load karo
const urlTab = new URLSearchParams(window.location.search).get('tab');
if(urlTab) {
    const btn = [...document.querySelectorAll('.tab-btn')].find(b => b.getAttribute('onclick').includes("'" + urlTab + "'"));
    if(btn) switchTab(urlTab, btn);
}
</script>
</body>
</html>