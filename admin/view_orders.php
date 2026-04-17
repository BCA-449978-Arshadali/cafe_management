<?php
include 'includes/auth.php';
include 'includes/db.php';

// Filter
$filter = $_GET['filter'] ?? 'today';

if($filter == 'today'){
    $query = mysqli_query($conn, "SELECT * FROM orders WHERE DATE(order_time) = CURDATE() ORDER BY id DESC");
} elseif($filter == 'pending'){
    $query = mysqli_query($conn, "SELECT * FROM orders WHERE status='Pending' ORDER BY id DESC");
} elseif($filter == 'preparing'){
    $query = mysqli_query($conn, "SELECT * FROM orders WHERE status='Preparing' ORDER BY id DESC");
} elseif($filter == 'completed'){
    $query = mysqli_query($conn, "SELECT * FROM orders WHERE status='Completed' ORDER BY id DESC");
} else {
    $query = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC");
}

// Stats
$r1 = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders");
$total = mysqli_fetch_assoc($r1)['c'];

$r2 = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE DATE(order_time) = CURDATE()");
$today = mysqli_fetch_assoc($r2)['c'];

$r3 = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='Pending'");
$pending_count = mysqli_fetch_assoc($r3)['c'];

$r4 = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='Preparing'");
$preparing_count = mysqli_fetch_assoc($r4)['c'];

$r5 = mysqli_query($conn, "SELECT SUM(total_amount) as r FROM orders WHERE DATE(order_time) = CURDATE()");
$revenue = mysqli_fetch_assoc($r5)['r'] ?? 0;

// Helper: extract Google Maps link from address string
function extractMapsLink($address){
    if(preg_match('/\[Maps:\s*(https?:\/\/[^\]]+)\]/', $address, $m)){
        return $m[1];
    }
    return '';
}
// Helper: clean address (remove maps link part for display)
function cleanAddress($address){
    return trim(preg_replace('/\s*\[Maps:.*?\]/', '', $address));
}
?>
<!DOCTYPE html>
<html>
<head>
<title>View Orders — Droppers Café Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ── Stats ── */
.stats-row { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
.stat-card {
    flex:1; min-width:130px; background:#fff; border-radius:12px;
    padding:16px 20px; box-shadow:0 2px 8px rgba(0,0,0,0.07);
    border-left:4px solid #d4a017;
}
.stat-card.green  { border-left-color:#27ae60; }
.stat-card.orange { border-left-color:#e67e22; }
.stat-card.red    { border-left-color:#e74c3c; }
.stat-card.blue   { border-left-color:#2980b9; }
.stat-card h4 { margin:0 0 6px; font-size:12px; color:#888; font-weight:500; text-transform:uppercase; }
.stat-card p  { margin:0; font-size:24px; font-weight:700; color:#1b1f3b; }

/* ── Filter Tabs ── */
.filter-tabs { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
.tab {
    padding:8px 18px; border-radius:20px;
    text-decoration:none; font-size:13px; font-weight:500;
    background:#f0f0f0; color:#555; transition:0.2s;
}
.tab:hover { background:#ddd; }
.tab.active { background:#1b1f3b; color:#fff; }
.tab-badge {
    display:inline-block; background:#e74c3c; color:#fff;
    padding:1px 7px; border-radius:10px; font-size:11px; font-weight:700; margin-left:4px;
}
.no-data { text-align:center; padding:40px; color:#aaa; font-size:15px; }
.today-badge { background:#fff3cd; color:#856404; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; margin-left:5px; }

/* ── Order Type Badges ── */
.otype {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;
    white-space:nowrap;
}
.otype-dine     { background:#e8f5e9; color:#2e7d32; }
.otype-takeaway { background:#fff8e1; color:#f57c00; }
.otype-delivery { background:#e3f2fd; color:#1565c0; }

/* ── Payment Badges ── */
.pay-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;
}
.pay-cod    { background:#f3e5f5; color:#6a1b9a; }
.pay-online { background:#e0f2f1; color:#00695c; }

/* ── Modal ── */
.modal {
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,0.55); backdrop-filter:blur(3px);
    align-items:center; justify-content:center;
}
.modal.open { display:flex; }

.modal-box {
    background:#fff; border-radius:20px; width:100%; max-width:520px;
    max-height:90vh; overflow-y:auto;
    box-shadow:0 20px 60px rgba(0,0,0,0.25);
    animation:popIn 0.22s ease;
}
@keyframes popIn {
    from { transform:scale(0.92); opacity:0; }
    to   { transform:scale(1);    opacity:1; }
}

/* Modal header */
.modal-header {
    padding:20px 24px 16px;
    border-bottom:1px solid #f0f0f0;
    display:flex; align-items:center; justify-content:space-between;
}
.modal-header h3 { margin:0; font-size:18px; color:#1b1f3b; }
.modal-close {
    width:32px; height:32px; border-radius:50%;
    background:#f5f5f5; border:none; cursor:pointer;
    font-size:18px; color:#666; display:flex; align-items:center; justify-content:center;
    transition:0.2s;
}
.modal-close:hover { background:#e74c3c; color:#fff; }

/* Modal body */
.modal-body { padding:20px 24px; }

.m-section {
    background:#f8f9fc; border-radius:12px; padding:14px 16px; margin-bottom:14px;
}
.m-section-title {
    font-size:10px; font-weight:700; text-transform:uppercase;
    letter-spacing:0.8px; color:#aaa; margin-bottom:10px;
    display:flex; align-items:center; gap:6px;
}
.m-row {
    display:flex; align-items:flex-start; gap:10px;
    padding:6px 0; border-bottom:1px solid rgba(0,0,0,0.05);
    font-size:13px;
}
.m-row:last-child { border-bottom:none; }
.m-label { color:#888; min-width:110px; font-weight:500; flex-shrink:0; }
.m-val   { color:#1b1f3b; font-weight:600; }

/* Items list inside modal */
.item-chip {
    display:inline-block; background:#fff; border:1px solid #e0e0e0;
    border-radius:8px; padding:3px 10px; font-size:12px; color:#333;
    margin:2px;
}

/* Maps button */
.maps-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:7px 16px; border-radius:10px; font-size:12px; font-weight:700;
    background:linear-gradient(135deg,#1a73e8,#1557b0);
    color:#fff; text-decoration:none; margin-top:8px; transition:0.2s;
}
.maps-btn:hover { opacity:0.88; transform:translateY(-1px); }

/* WhatsApp button */
.wa-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:7px 16px; border-radius:10px; font-size:12px; font-weight:700;
    background:linear-gradient(135deg,#25D366,#128C7E);
    color:#fff; text-decoration:none; margin-top:8px; margin-left:8px; transition:0.2s;
}
.wa-btn:hover { opacity:0.88; transform:translateY(-1px); }
</style>
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">

<div class="topbar">
    <h2>Customer Orders</h2>
    <div style="font-size:13px; color:#888;">📅 Today: <?php echo date('d M Y, l'); ?></div>
</div>

<!-- Stats Cards -->
<div class="stats-row">
    <div class="stat-card">
        <h4><i class="fa fa-shopping-bag"></i> Total Orders</h4>
        <p><?php echo $total; ?></p>
    </div>
    <div class="stat-card green">
        <h4><i class="fa fa-sun"></i> Today's Orders</h4>
        <p><?php echo $today; ?></p>
    </div>
    <div class="stat-card red">
        <h4><i class="fa fa-bell"></i> Pending</h4>
        <p><?php echo $pending_count; ?></p>
    </div>
    <div class="stat-card orange">
        <h4><i class="fa fa-fire"></i> Preparing</h4>
        <p><?php echo $preparing_count; ?></p>
    </div>
    <div class="stat-card blue">
        <h4><i class="fa fa-indian-rupee-sign"></i> Today's Revenue</h4>
        <p>₹<?php echo number_format($revenue, 2); ?></p>
    </div>
</div>

<!-- Filter Tabs -->
<div class="filter-tabs">
    <a href="?filter=today"     class="tab <?php echo $filter=='today'     ? 'active':''; ?>">
        📅 Today <?php if($today > 0) echo "<span class='tab-badge'>$today</span>"; ?>
    </a>
    <a href="?filter=pending"   class="tab <?php echo $filter=='pending'   ? 'active':''; ?>">
        🔔 Pending <?php if($pending_count > 0) echo "<span class='tab-badge'>$pending_count</span>"; ?>
    </a>
    <a href="?filter=preparing" class="tab <?php echo $filter=='preparing' ? 'active':''; ?>">
        🔥 Preparing <?php if($preparing_count > 0) echo "<span class='tab-badge'>$preparing_count</span>"; ?>
    </a>
    <a href="?filter=completed" class="tab <?php echo $filter=='completed' ? 'active':''; ?>">✅ Completed</a>
    <a href="?filter=all"       class="tab <?php echo $filter=='all'       ? 'active':''; ?>">📋 All Orders</a>
</div>

<!-- Orders Table -->
<div class="orders-table">
<table>
<tr>
    <th>ID</th>
    <th>Customer</th>
    <th>Phone</th>
    <th>Order Type</th>
    <th>Payment</th>
    <th>Total</th>
    <th>Status</th>
    <th>Details</th>
    <th>Action</th>
    <th>Time</th>
</tr>

<?php
$count = 0;
while($row = mysqli_fetch_assoc($query)):
    $count++;
    $isToday   = (date('Y-m-d', strtotime($row['order_time'])) == date('Y-m-d'));
    $otype     = $row['order_type']     ?? 'Dine In';
    $payment   = $row['payment_method'] ?? 'COD';
    $address   = $row['delivery_address'] ?? '';
    $mapsLink  = extractMapsLink($address);
    $cleanAddr = cleanAddress($address);

    // Order type badge class + icon
    $otypeClass = 'otype-dine';
    $otypeIcon  = '🪑';
    if($otype == 'Takeaway')      { $otypeClass = 'otype-takeaway'; $otypeIcon = '🛍️'; }
    if($otype == 'Home Delivery') { $otypeClass = 'otype-delivery'; $otypeIcon = '🛵'; }

    // Payment badge class
    $payClass = ($payment == 'COD') ? 'pay-cod' : 'pay-online';
    $payIcon  = ($payment == 'COD') ? '💵' : '📱';
    $payLabel = ($payment == 'COD') ? 'Cash on Delivery' : $payment;
?>
<tr>
    <td><?php echo $row['id']; ?></td>

    <td>
        <?php echo htmlspecialchars($row['customer_name']); ?>
        <?php if($isToday && $filter != 'today') echo "<span class='today-badge'>Today</span>"; ?>
    </td>

    <td><?php echo htmlspecialchars($row['customer_phone']); ?></td>

    <td>
        <span class="otype <?php echo $otypeClass; ?>">
            <?php echo $otypeIcon; ?> <?php echo $otype; ?>
        </span>
    </td>

    <td>
        <span class="pay-badge <?php echo $payClass; ?>">
            <?php echo $payIcon; ?> <?php echo ($payment == 'COD') ? 'COD' : 'Online'; ?>
        </span>
    </td>

    <td><strong>₹<?php echo number_format($row['total_amount'], 2); ?></strong></td>

    <td>
        <span class="status <?php echo $row['status']; ?>"><?php echo $row['status']; ?></span>
    </td>

    <td>
        <button class="viewBtn"
            data-id="<?php echo $row['id']; ?>"
            data-customer="<?php echo htmlspecialchars($row['customer_name'], ENT_QUOTES); ?>"
            data-phone="<?php echo htmlspecialchars($row['customer_phone'], ENT_QUOTES); ?>"
            data-items="<?php echo htmlspecialchars($row['items'], ENT_QUOTES); ?>"
            data-total="<?php echo $row['total_amount']; ?>"
            data-status="<?php echo $row['status']; ?>"
            data-otype="<?php echo htmlspecialchars($otype, ENT_QUOTES); ?>"
            data-payment="<?php echo htmlspecialchars($payLabel, ENT_QUOTES); ?>"
            data-address="<?php echo htmlspecialchars($cleanAddr, ENT_QUOTES); ?>"
            data-maps="<?php echo htmlspecialchars($mapsLink, ENT_QUOTES); ?>"
            data-time="<?php echo $row['order_time']; ?>">
            <i class="fa fa-eye"></i> View
        </button>
    </td>

    <td>
        <?php
        if($row['status'] == 'Pending'){
            echo "<a class='btn' href='update_status.php?id={$row['id']}&status=Preparing&ref=".urlencode("view_orders.php?filter=$filter")."'>🔥 Preparing</a>
                  <a class='btn success' href='update_status.php?id={$row['id']}&status=Completed&ref=".urlencode("view_orders.php?filter=$filter")."'>✅ Done</a>";
        } elseif($row['status'] == 'Preparing'){
            echo "<a class='btn success' href='update_status.php?id={$row['id']}&status=Completed&ref=".urlencode("view_orders.php?filter=$filter")."'>✅ Done</a>";
        } else {
            echo "<span style='color:#aaa;font-size:12px;'>— Done —</span>";
        }
        ?>
    </td>

    <td><?php echo date('d M, h:i A', strtotime($row['order_time'])); ?></td>
</tr>
<?php endwhile; ?>

<?php if($count == 0):
    $msgs = [
        'today'     => '📭 No orders today',
        'pending'   => '📭 No pending orders',
        'preparing' => '📭 No orders being prepared',
        'completed' => '📭 No completed orders yet',
    ];
    $msg = $msgs[$filter] ?? 'No orders found';
    echo "<tr><td colspan='10' class='no-data'>$msg</td></tr>";
endif; ?>
</table>
</div>

<!-- ═══════════════════ ORDER DETAILS MODAL ═══════════════════ -->
<div id="orderModal" class="modal">
<div class="modal-box">

    <!-- Header -->
    <div class="modal-header">
        <h3>🧾 Order Details &nbsp;<span style="font-size:14px;color:#888;" id="mOrderId"></span></h3>
        <button class="modal-close" id="closeModal">&times;</button>
    </div>

    <!-- Body -->
    <div class="modal-body">

        <!-- Customer Info -->
        <div class="m-section">
            <div class="m-section-title"><i class="fa fa-user fa-xs"></i> Customer Info</div>
            <div class="m-row">
                <span class="m-label">👤 Name</span>
                <span class="m-val" id="mCustomer"></span>
            </div>
            <div class="m-row">
                <span class="m-label">📞 Phone</span>
                <span class="m-val" id="mPhone"></span>
            </div>
        </div>

        <!-- Order Info -->
        <div class="m-section">
            <div class="m-section-title"><i class="fa fa-receipt fa-xs"></i> Order Info</div>
            <div class="m-row">
                <span class="m-label">🪑 Order Type</span>
                <span class="m-val" id="mOtype"></span>
            </div>
            <div class="m-row">
                <span class="m-label">💳 Payment</span>
                <span class="m-val" id="mPayment"></span>
            </div>
            <div class="m-row">
                <span class="m-label">💰 Total</span>
                <span class="m-val" id="mTotal"></span>
            </div>
            <div class="m-row">
                <span class="m-label">📊 Status</span>
                <span class="m-val" id="mStatus"></span>
            </div>
            <div class="m-row">
                <span class="m-label">🕐 Time</span>
                <span class="m-val" id="mTime"></span>
            </div>
        </div>

        <!-- Items -->
        <div class="m-section">
            <div class="m-section-title"><i class="fa fa-utensils fa-xs"></i> Items Ordered</div>
            <div id="mItems" style="padding-top:4px;"></div>
        </div>

        <!-- Delivery Address (only for Home Delivery) -->
        <div class="m-section" id="mAddressSection" style="display:none;">
            <div class="m-section-title"><i class="fa fa-map-pin fa-xs"></i> Delivery Address</div>
            <div class="m-row">
                <span class="m-label">📍 Address</span>
                <span class="m-val" id="mAddress"></span>
            </div>
            <div id="mMapsRow" style="display:none; margin-top:6px;">
                <a id="mMapsBtn" href="#" target="_blank" class="maps-btn">
                    <i class="fa fa-map-location-dot"></i> Open in Google Maps
                </a>
                <a id="mWaBtn" href="#" target="_blank" class="wa-btn">
                    <i class="fab fa-whatsapp"></i> Share on WhatsApp
                </a>
            </div>
        </div>

    </div><!-- /modal-body -->
</div>
</div>

</div><!-- /main -->

<script>
const modal    = document.getElementById('orderModal');
const closeBtn = document.getElementById('closeModal');

document.querySelectorAll('.viewBtn').forEach(btn => {
    btn.onclick = function(){
        const d = this.dataset;

        // Populate fields
        document.getElementById('mOrderId').innerText   = '#' + d.id;
        document.getElementById('mCustomer').innerText  = d.customer;
        document.getElementById('mPhone').innerText     = d.phone;
        document.getElementById('mTotal').innerText     = '₹' + parseFloat(d.total).toFixed(2);
        document.getElementById('mPayment').innerText   = d.payment;
        document.getElementById('mTime').innerText      = d.time;

        // Order type with icon
        const typeIcons = { 'Dine In':'🪑', 'Takeaway':'🛍️', 'Home Delivery':'🛵' };
        document.getElementById('mOtype').innerText = (typeIcons[d.otype] || '📦') + ' ' + d.otype;

        // Status badge
        document.getElementById('mStatus').innerHTML =
            `<span class="status ${d.status}">${d.status}</span>`;

        // Items — render as chips
        const items = d.items.split(',').map(s => s.trim()).filter(Boolean);
        document.getElementById('mItems').innerHTML =
            items.map(i => `<span class="item-chip">🍽️ ${i}</span>`).join('');

        // Delivery address section
        const addrSection = document.getElementById('mAddressSection');
        const mapsRow     = document.getElementById('mMapsRow');

        if(d.otype === 'Home Delivery'){
            addrSection.style.display = 'block';
            document.getElementById('mAddress').innerText = d.address || '— not provided —';

            if(d.maps){
                mapsRow.style.display = 'block';
                document.getElementById('mMapsBtn').href = d.maps;
                // WhatsApp link with location + customer name
                const waText = encodeURIComponent(
                    `📦 *Droppers Café — Delivery Order*\n👤 Customer: ${d.customer}\n📞 Phone: ${d.phone}\n📍 Location: ${d.maps}\n📋 Items: ${d.items}\n💰 Total: ₹${parseFloat(d.total).toFixed(2)}`
                );
                document.getElementById('mWaBtn').href = `https://wa.me/?text=${waText}`;
            } else {
                mapsRow.style.display = 'none';
            }
        } else {
            addrSection.style.display = 'none';
        }

        modal.classList.add('open');
    };
});

closeBtn.onclick = () => modal.classList.remove('open');
window.onclick   = e => { if(e.target === modal) modal.classList.remove('open'); };
</script>

</body>
</html>