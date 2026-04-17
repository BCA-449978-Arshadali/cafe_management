<?php
include 'includes/auth.php';
include 'includes/db.php';

// ── DATE FILTER LOGIC ─────────────────────────────────────────────────────────
$range       = $_GET['range'] ?? 'all';
$custom_from = $_GET['from']  ?? '';
$custom_to   = $_GET['to']    ?? '';

switch ($range) {
    case 'today':
        $where       = "WHERE DATE(order_time) = CURDATE()";
        $range_label = 'Today — ' . date('d M Y');
        break;
    case 'week':
        $where       = "WHERE order_time >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
        $range_label = 'Last 7 Days';
        break;
    case 'month':
        $where       = "WHERE YEAR(order_time) = YEAR(NOW()) AND MONTH(order_time) = MONTH(NOW())";
        $range_label = 'This Month — ' . date('F Y');
        break;
    case 'custom':
        $f = mysqli_real_escape_string($conn, $custom_from);
        $t = mysqli_real_escape_string($conn, $custom_to);
        if ($f && $t) {
            $where       = "WHERE DATE(order_time) BETWEEN '$f' AND '$t'";
            $range_label = date('d M Y', strtotime($f)) . ' – ' . date('d M Y', strtotime($t));
        } else {
            $where = ""; $range_label = 'All Time'; $range = 'all';
        }
        break;
    default:
        $where = ""; $range_label = 'All Time'; break;
}

// Helper: chain conditions
function andWhere($where, $extra) {
    return $where ? "$where AND $extra" : "WHERE $extra";
}

// Chart grouping by range
if ($range === 'today') {
    $chart_group = "DATE_FORMAT(order_time,'%h %p') AS label, HOUR(order_time) AS hr";
} elseif ($range === 'week' || $range === 'custom') {
    $chart_group = "DATE_FORMAT(order_time,'%d %b') AS label, DATE(order_time) AS hr";
} else {
    $chart_group = "DATE_FORMAT(order_time,'%b %Y') AS label, YEAR(order_time)*100+MONTH(order_time) AS hr";
}

// ── CSV DOWNLOAD ───────────────────────────────────────────────────────────────
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    $filename = 'droppers_cafe_' . $range . '_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');

    fputcsv($out, ['DROPPERS CAFE — SALES REPORT']);
    fputcsv($out, ['Period',    $range_label]);
    fputcsv($out, ['Generated', date('d M Y, h:i A')]);
    fputcsv($out, []);
    fputcsv($out, ['SUMMARY']);
    fputcsv($out, ['Metric', 'Value']);

    $q = function($sql) use ($conn) { return mysqli_fetch_assoc(mysqli_query($conn, $sql)); };
    fputcsv($out, ['Revenue',          '₹' . number_format($q("SELECT COALESCE(SUM(total_amount),0) AS v FROM orders $where")['v'], 2)]);
    fputcsv($out, ['Total Orders',     $q("SELECT COUNT(*) AS v FROM orders $where")['v']]);
    fputcsv($out, ['Pending Orders',   $q("SELECT COUNT(*) AS v FROM orders " . andWhere($where,"status='pending'"))['v']]);
    fputcsv($out, ['Completed Orders', $q("SELECT COUNT(*) AS v FROM orders " . andWhere($where,"status='completed'"))['v']]);
    fputcsv($out, ['Avg Order Value',  '₹' . number_format($q("SELECT COALESCE(AVG(total_amount),0) AS v FROM orders $where")['v'], 2)]);
    fputcsv($out, []);
    fputcsv($out, ['ORDER DETAILS']);
    fputcsv($out, ['Order ID','Customer','Amount (₹)','Status','Placed At']);

    $all_q = mysqli_query($conn,
        "SELECT id, customer_name, total_amount, status,
                DATE_FORMAT(order_time,'%d %b %Y %H:%i') AS placed_at
         FROM orders $where ORDER BY order_time DESC");
    while ($row = mysqli_fetch_assoc($all_q)) {
        fputcsv($out, [
            '#' . str_pad($row['id'],4,'0',STR_PAD_LEFT),
            $row['customer_name'] ?? 'Guest',
            number_format($row['total_amount'],2),
            ucfirst($row['status'] ?? 'pending'),
            $row['placed_at'],
        ]);
    }
    fclose($out); exit;
}

// ── STATS (filter-aware) ──────────────────────────────────────────────────────
$total_sales    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) AS v FROM orders $where"))['v'];
$total_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS v FROM orders $where"))['v'];
$total_items    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS v FROM menu"))['v'];
$avg_order      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(AVG(total_amount),0) AS v FROM orders $where"))['v'];
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS v FROM orders " . andWhere($where,"status='pending'")))['v'];
$completed_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS v FROM orders " . andWhere($where,"status='completed'")))['v'];

// ── CHART ─────────────────────────────────────────────────────────────────────
$chart_q = mysqli_query($conn,
    "SELECT $chart_group, COALESCE(SUM(total_amount),0) AS total
     FROM orders $where GROUP BY hr ORDER BY hr");
$chart_labels = $chart_values = [];
while ($r = mysqli_fetch_assoc($chart_q)) {
    $chart_labels[] = $r['label'];
    $chart_values[] = (float)$r['total'];
}

// ── STATUS BREAKDOWN ──────────────────────────────────────────────────────────
$status_q = mysqli_query($conn,
    "SELECT COALESCE(status,'pending') AS status, COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS total
     FROM orders $where GROUP BY status ORDER BY cnt DESC");
$statuses = [];
while ($r = mysqli_fetch_assoc($status_q)) $statuses[] = $r;
$max_cnt = $statuses ? max(array_column($statuses,'cnt')) : 1;

// ── RECENT ORDERS ─────────────────────────────────────────────────────────────
$recent_q = mysqli_query($conn,
    "SELECT id, customer_name, total_amount, status,
            DATE_FORMAT(order_time,'%d %b %Y, %h:%i %p') AS placed_at
     FROM orders $where ORDER BY order_time DESC LIMIT 10");

// CSV URL preserving filter
$csv_params = http_build_query(array_filter(['download'=>'csv','range'=>$range,'from'=>$custom_from,'to'=>$custom_to]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report — Droppers Café</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        .rp-wrap { padding: 28px; }

        /* Topbar */
        .rp-topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; gap:14px; flex-wrap:wrap; }
        .rp-heading { display:flex; align-items:center; gap:10px; font-size:1.5rem; font-weight:800; color:#fff; border-left:4px solid #e67e22; padding-left:14px; }
        .rp-heading i { color:#e67e22; font-size:1.2rem; }
        .rp-meta { display:flex; align-items:center; gap:10px; }
        .rp-date-badge { display:flex; align-items:center; gap:8px; background:#1a1d27; border:1px solid #2a2d3e; border-radius:8px; padding:7px 14px; font-size:.82rem; color:#ccc; }
        .rp-date-badge i { color:#3b82f6; }
        .rp-download-btn { display:flex; align-items:center; gap:8px; background:#e67e22; color:#fff; font-size:.82rem; font-weight:700; padding:8px 18px; border-radius:8px; text-decoration:none; transition:background .2s,transform .15s; }
        .rp-download-btn:hover { background:#cf6d17; transform:translateY(-1px); }

        /* Filter Bar */
        .rp-filter-bar { display:flex; align-items:center; gap:10px; background:#1a1d27; border:1px solid #2a2d3e; border-radius:12px; padding:12px 18px; margin-bottom:16px; flex-wrap:wrap; }
        .rp-filter-label { font-size:.72rem; font-weight:700; color:#555; text-transform:uppercase; letter-spacing:.08em; white-space:nowrap; }
        .rp-filter-label i { margin-right:5px; color:#e67e22; }
        .rp-filter-btns { display:flex; gap:6px; flex-wrap:wrap; }
        .rp-filter-btn { padding:7px 16px; border-radius:20px; font-size:.79rem; font-weight:600; cursor:pointer; text-decoration:none; background:#22263a; color:#7b82a0; border:1px solid #2a2d3e; transition:all .18s; }
        .rp-filter-btn:hover { background:#2a2d3e; color:#ccc; }
        .rp-filter-btn.active { background:#e67e22; color:#fff; border-color:#e67e22; }
        .rp-filter-sep { width:1px; height:26px; background:#2a2d3e; margin:0 4px; flex-shrink:0; }
        .rp-custom-form { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
        .rp-custom-form input[type=date] { background:#22263a; border:1px solid #2a2d3e; color:#ccc; border-radius:8px; padding:6px 10px; font-size:.79rem; outline:none; }
        .rp-custom-form input[type=date]:focus { border-color:#e67e22; }
        .rp-custom-form span { color:#555; font-size:.8rem; }
        .rp-custom-apply { padding:6px 14px; background:#e67e22; color:#fff; border:none; border-radius:8px; font-size:.79rem; font-weight:700; cursor:pointer; transition:background .18s; }
        .rp-custom-apply:hover { background:#cf6d17; }

        /* Active range badge */
        .rp-active-range { display:inline-flex; align-items:center; gap:6px; background:rgba(230,126,34,.12); border:1px solid rgba(230,126,34,.3); color:#e67e22; font-size:.75rem; font-weight:600; padding:5px 14px; border-radius:20px; margin-bottom:22px; }

        /* Cards */
        .rp-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:18px; margin-bottom:26px; }
        .rp-card { background:#1a1d27; border-radius:14px; padding:20px; border-top:3px solid transparent; transition:transform .2s,box-shadow .2s; }
        .rp-card:hover { transform:translateY(-3px); box-shadow:0 8px 28px rgba(0,0,0,.5); }
        .rp-card.c-orange{border-color:#e67e22} .rp-card.c-green{border-color:#2ecc71} .rp-card.c-red{border-color:#e74c3c} .rp-card.c-blue{border-color:#3498db} .rp-card.c-teal{border-color:#1abc9c} .rp-card.c-purple{border-color:#9b59b6} .rp-card.c-yellow{border-color:#f1c40f}
        .rp-card-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; margin-bottom:14px; }
        .rp-card.c-orange .rp-card-icon{background:rgba(230,126,34,.2);color:#e67e22} .rp-card.c-green .rp-card-icon{background:rgba(46,204,113,.2);color:#2ecc71} .rp-card.c-red .rp-card-icon{background:rgba(231,76,60,.2);color:#e74c3c} .rp-card.c-blue .rp-card-icon{background:rgba(52,152,219,.2);color:#3498db} .rp-card.c-teal .rp-card-icon{background:rgba(26,188,156,.2);color:#1abc9c} .rp-card.c-purple .rp-card-icon{background:rgba(155,89,182,.2);color:#9b59b6} .rp-card.c-yellow .rp-card-icon{background:rgba(241,196,15,.2);color:#f1c40f}
        .rp-card-label { font-size:.67rem; text-transform:uppercase; letter-spacing:.1em; color:#7b82a0; font-weight:600; margin-bottom:6px; }
        .rp-card-value { font-size:2rem; font-weight:800; color:#fff; line-height:1; }
        .rp-card.c-green .rp-card-value{color:#2ecc71} .rp-card.c-red .rp-card-value{color:#e74c3c}
        .rp-card-sub { font-size:.72rem; color:#555; margin-top:6px; }

        /* Sections */
        .rp-section-title { display:flex; align-items:center; gap:10px; font-size:1rem; font-weight:700; color:#fff; border-left:4px solid #e67e22; padding-left:12px; margin-bottom:18px; }
        .rp-section-badge { font-size:.68rem; background:#22263a; border:1px solid #2a2d3e; padding:3px 10px; border-radius:20px; color:#7b82a0; font-weight:500; margin-left:auto; }
        .rp-row { display:grid; grid-template-columns:2fr 1fr; gap:20px; margin-bottom:26px; }
        @media(max-width:900px){.rp-row{grid-template-columns:1fr}}
        .rp-panel { background:#1a1d27; border-radius:14px; padding:22px; border:1px solid #22263a; }

        /* Status */
        .s-row{margin-bottom:16px} .s-row:last-child{margin-bottom:0}
        .s-meta{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px}
        .s-name{font-size:.82rem;font-weight:600;text-transform:capitalize;color:#ddd}
        .s-count{font-size:.78rem;font-weight:700}
        .s-track{height:6px;background:#22263a;border-radius:10px;overflow:hidden}
        .s-fill{height:100%;border-radius:10px;transition:width .9s ease}
        .s-rev{font-size:.7rem;color:#555;margin-top:4px}

        /* Table */
        .rp-table-panel{background:#1a1d27;border-radius:14px;border:1px solid #22263a;overflow:hidden}
        .rp-table-header{padding:18px 22px;border-bottom:1px solid #22263a}
        table.rt{width:100%;border-collapse:collapse;font-size:.84rem}
        table.rt th{text-align:left;padding:10px 18px;font-size:.67rem;text-transform:uppercase;letter-spacing:.08em;color:#555;font-weight:600;border-bottom:1px solid #22263a}
        table.rt td{padding:13px 18px;border-bottom:1px solid #1e2133;vertical-align:middle;color:#ccc}
        table.rt tr:last-child td{border-bottom:none}
        table.rt tr:hover td{background:rgba(255,255,255,.02)}
        .rt-id{color:#555;font-size:.78rem;font-family:monospace}
        .rt-amount{font-weight:700;color:#2ecc71}
        .rt-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;text-transform:capitalize}
        .rt-badge.completed{background:rgba(46,204,113,.15);color:#2ecc71}
        .rt-badge.pending{background:rgba(230,126,34,.15);color:#e67e22}
        .rt-badge.cancelled{background:rgba(231,76,60,.15);color:#e74c3c}
        .rt-badge.preparing{background:rgba(52,152,219,.15);color:#3498db}
        .rp-empty{text-align:center;padding:40px;color:#444;font-size:.88rem}
        .rp-empty i{font-size:2rem;display:block;margin-bottom:10px;opacity:.5}
    </style>
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">
<div class="rp-wrap">

    <!-- Topbar -->
    <div class="rp-topbar">
        <div class="rp-heading">
            <i class="fa fa-chart-pie"></i> Sales Report
        </div>
        <div class="rp-meta">
            <div class="rp-date-badge">
                <i class="fa fa-calendar-days"></i>
                <?php echo date('l, d M Y'); ?>
            </div>
            <a href="?<?php echo $csv_params; ?>" class="rp-download-btn">
                <i class="fa fa-download"></i> Download CSV
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="rp-filter-bar">
        <span class="rp-filter-label"><i class="fa fa-filter"></i> Filter by</span>

        <div class="rp-filter-btns">
            <a href="?range=today"  class="rp-filter-btn <?php echo $range==='today' ?'active':''; ?>"><i class="fa fa-sun"></i> Today</a>
            <a href="?range=week"   class="rp-filter-btn <?php echo $range==='week'  ?'active':''; ?>"><i class="fa fa-calendar-week"></i> This Week</a>
            <a href="?range=month"  class="rp-filter-btn <?php echo $range==='month' ?'active':''; ?>"><i class="fa fa-calendar"></i> This Month</a>
            <a href="?range=all"    class="rp-filter-btn <?php echo $range==='all'   ?'active':''; ?>"><i class="fa fa-infinity"></i> All Time</a>
        </div>

        <div class="rp-filter-sep"></div>

        <form method="GET" class="rp-custom-form">
            <input type="hidden" name="range" value="custom">
            <input type="date" name="from" value="<?php echo htmlspecialchars($custom_from); ?>" max="<?php echo date('Y-m-d'); ?>">
            <span>to</span>
            <input type="date" name="to"   value="<?php echo htmlspecialchars($custom_to);   ?>" max="<?php echo date('Y-m-d'); ?>">
            <button type="submit" class="rp-custom-apply"><i class="fa fa-arrow-right"></i> Apply</button>
        </form>
    </div>

    <!-- Active range badge -->
    <div class="rp-active-range">
        <i class="fa fa-clock"></i> Showing: <strong>&nbsp;<?php echo htmlspecialchars($range_label); ?></strong>
    </div>

    <!-- Stat Cards -->
    <div class="rp-cards">
        <div class="rp-card c-orange">
            <div class="rp-card-icon"><i class="fa fa-indian-rupee-sign"></i></div>
            <div class="rp-card-label">Revenue</div>
            <div class="rp-card-value">&#8377;<?php echo number_format($total_sales,0); ?></div>
            <div class="rp-card-sub"><?php echo htmlspecialchars($range_label); ?></div>
        </div>
        <div class="rp-card c-blue">
            <div class="rp-card-icon"><i class="fa fa-bag-shopping"></i></div>
            <div class="rp-card-label">Total Orders</div>
            <div class="rp-card-value"><?php echo number_format($total_orders); ?></div>
            <div class="rp-card-sub"><?php echo htmlspecialchars($range_label); ?></div>
        </div>
        <div class="rp-card c-red">
            <div class="rp-card-icon"><i class="fa fa-bell"></i></div>
            <div class="rp-card-label">Pending Orders</div>
            <div class="rp-card-value"><?php echo $pending_orders; ?></div>
            <div class="rp-card-sub">Awaiting action</div>
        </div>
        <div class="rp-card c-teal">
            <div class="rp-card-icon"><i class="fa fa-circle-check"></i></div>
            <div class="rp-card-label">Completed</div>
            <div class="rp-card-value"><?php echo $completed_orders; ?></div>
            <div class="rp-card-sub">Successfully served</div>
        </div>
        <div class="rp-card c-purple">
            <div class="rp-card-icon"><i class="fa fa-chart-line"></i></div>
            <div class="rp-card-label">Avg Order Value</div>
            <div class="rp-card-value">&#8377;<?php echo number_format($avg_order,0); ?></div>
            <div class="rp-card-sub">Per order average</div>
        </div>
        <div class="rp-card c-yellow">
            <div class="rp-card-icon"><i class="fa fa-utensils"></i></div>
            <div class="rp-card-label">Menu Items</div>
            <div class="rp-card-value"><?php echo $total_items; ?></div>
            <div class="rp-card-sub">Active dishes</div>
        </div>
    </div>

    <!-- Chart + Status -->
    <div class="rp-row">
        <div class="rp-panel">
            <div class="rp-section-title">
                Sales Trend
                <span class="rp-section-badge"><?php echo htmlspecialchars($range_label); ?></span>
            </div>
            <?php if ($chart_labels): ?>
                <canvas id="salesChart" height="115"></canvas>
            <?php else: ?>
                <div class="rp-empty"><i class="fa fa-chart-bar"></i>No sales data for this period</div>
            <?php endif; ?>
        </div>

        <div class="rp-panel">
            <div class="rp-section-title">
                Order Status <span class="rp-section-badge">Breakdown</span>
            </div>
            <?php
            $s_colors = ['completed'=>'#2ecc71','pending'=>'#e67e22','cancelled'=>'#e74c3c','preparing'=>'#3498db'];
            if ($statuses): foreach ($statuses as $s):
                $key=$s_colors[strtolower(trim($s['status']))]??'#7b82a0';
                $pct=round($s['cnt']/$max_cnt*100);
            ?>
                <div class="s-row">
                    <div class="s-meta">
                        <span class="s-name"><?php echo htmlspecialchars($s['status']); ?></span>
                        <span class="s-count" style="color:<?php echo $key; ?>"><?php echo $s['cnt']; ?> orders</span>
                    </div>
                    <div class="s-track"><div class="s-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $key; ?>;"></div></div>
                    <div class="s-rev">&#8377;<?php echo number_format($s['total'],0); ?> revenue</div>
                </div>
            <?php endforeach; else: ?>
                <div class="rp-empty" style="padding:24px;"><i class="fa fa-inbox"></i>No data for this period</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="rp-table-panel">
        <div class="rp-table-header">
            <div class="rp-section-title" style="margin-bottom:0;">
                Orders
                <span class="rp-section-badge"><?php echo htmlspecialchars($range_label); ?> — Latest 10</span>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table class="rt">
                <thead>
                    <tr><th>Order ID</th><th>Customer</th><th>Amount</th><th>Status</th><th>Placed At</th></tr>
                </thead>
                <tbody>
                <?php
                $has = false;
                while ($o = mysqli_fetch_assoc($recent_q)):
                    $has = true;
                    $st = strtolower(trim($o['status'] ?? 'pending'));
                ?>
                    <tr>
                        <td><span class="rt-id">#<?php echo str_pad($o['id'],4,'0',STR_PAD_LEFT); ?></span></td>
                        <td><?php echo htmlspecialchars($o['customer_name'] ?? 'Guest'); ?></td>
                        <td><span class="rt-amount">&#8377;<?php echo number_format($o['total_amount'],2); ?></span></td>
                        <td><span class="rt-badge <?php echo $st; ?>"><?php echo ucfirst($st); ?></span></td>
                        <td style="color:#555;font-size:.78rem;"><?php echo $o['placed_at']; ?></td>
                    </tr>
                <?php endwhile; ?>
                <?php if (!$has): ?>
                    <tr><td colspan="5"><div class="rp-empty"><i class="fa fa-receipt"></i>No orders for this period</div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>

<script>
<?php if ($chart_labels): ?>
const ctx = document.getElementById('salesChart').getContext('2d');
const grad = ctx.createLinearGradient(0,0,0,280);
grad.addColorStop(0,'rgba(230,126,34,0.5)');
grad.addColorStop(1,'rgba(230,126,34,0.02)');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label:'Sales (₹)', data:<?php echo json_encode($chart_values); ?>,
            fill:true, backgroundColor:grad, borderColor:'#e67e22', borderWidth:2.5,
            pointBackgroundColor:'#e67e22', pointRadius:5, pointHoverRadius:7, tension:0.4,
        }]
    },
    options: {
        responsive:true,
        plugins: {
            legend:{display:false},
            tooltip:{ backgroundColor:'#1a1d27', borderColor:'#2a2d3e', borderWidth:1,
                      titleColor:'#fff', bodyColor:'#e67e22',
                      callbacks:{label:c=>' ₹'+c.parsed.y.toLocaleString('en-IN')} }
        },
        scales: {
            x:{ticks:{color:'#555',font:{size:11}},grid:{color:'rgba(255,255,255,.04)'}},
            y:{beginAtZero:true, ticks:{color:'#555',font:{size:11},callback:v=>'₹'+(v>=1000?(v/1000).toFixed(0)+'k':v)}, grid:{color:'rgba(255,255,255,.04)'}}
        }
    }
});
<?php endif; ?>

// Date range validation
const fromI = document.querySelector('input[name="from"]');
const toI   = document.querySelector('input[name="to"]');
if (fromI && toI) {
    fromI.addEventListener('change', ()=>{ if(toI.value && fromI.value > toI.value) toI.value = fromI.value; });
    toI.addEventListener('change',   ()=>{ if(fromI.value && toI.value < fromI.value) fromI.value = toI.value; });
}
</script>
</body>
</html>