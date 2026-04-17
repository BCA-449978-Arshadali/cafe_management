<?php
include 'includes/auth.php';
include 'includes/db.php';

// Last 7 days sales
$sales = []; $labels = [];
for($i=6; $i>=0; $i--){
    $date     = date("Y-m-d", strtotime("-$i days"));
    $labels[] = ($i == 0) ? 'Today' : date('d M', strtotime("-$i days"));
    $r        = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_time)='$date'");
    $row      = mysqli_fetch_assoc($r);
    $sales[]  = $row['total'] ? (float)$row['total'] : 0;
}

// Stats
$menu_total     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM menu"))['c'];
$orders_total   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'];
$new_orders     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='Pending'"))['c'];
$today_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE DATE(order_time)=CURDATE()"))['c'];
$today_rev_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as rev FROM orders WHERE DATE(order_time)=CURDATE()"));
$today_rev      = $today_rev_row['rev'] ? $today_rev_row['rev'] : 0;
$bookings_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings"))['c'];
$today_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE date=CURDATE()"))['c'];
$feedbacks      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM feedback"))['c'];
$customers      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM customers"))['c'];
$total_rev_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as rev FROM orders WHERE status='Completed'"));
$total_rev      = $total_rev_row['rev'] ? $total_rev_row['rev'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Droppers Café Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<audio id="orderSound">
    <source src="../assets/sounds/order-alert.mp3" type="audio/mpeg">
</audio>

<div class="main">

    <!-- Topbar -->
    <div class="topbar">
        <h2><i class="fa fa-gauge-high" style="color:var(--orange);font-size:20px;"></i> Dashboard</h2>
        <div class="topbar-right">
            <div class="topbar-date">📅 <?php echo date('l, d M Y'); ?></div>
            <div class="admin-user">
                <div class="avatar"><i class="fa fa-user"></i></div>
                Admin
            </div>
        </div>
    </div>

    <!-- Alert for pending orders -->
    <?php if($new_orders > 0): ?>
    <div style="background:rgba(231,76,60,0.08); border:1px solid rgba(231,76,60,0.3); border-radius:12px; padding:14px 18px; margin-bottom:24px; display:flex; align-items:center; gap:12px; animation:pulse-alert 2s ease-in-out infinite;">
        <i class="fa fa-bell" style="color:#e74c3c; font-size:18px;"></i>
        <span style="font-weight:600; color:#e74c3c;"><?= $new_orders ?> Pending Order<?= $new_orders > 1 ? 's' : ''; ?> Waiting!</span>
        <a href="view_orders.php?filter=pending" style="margin-left:auto; background:rgba(231,76,60,0.15); color:#e74c3c; padding:6px 14px; border-radius:8px; font-size:13px; font-weight:600; border:1px solid rgba(231,76,60,0.3);">View Now →</a>
    </div>
    <style>@keyframes pulse-alert{ 0%,100%{box-shadow:0 0 0 rgba(231,76,60,0);} 50%{box-shadow:0 0 16px rgba(231,76,60,0.2);} }</style>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="cards">
        <div class="card">
            <i class="fa fa-utensils"></i>
            <h3>Menu Items</h3>
            <p><?= $menu_total ?></p>
        </div>
        <div class="card">
            <i class="fa fa-bag-shopping"></i>
            <h3>Total Orders</h3>
            <p><?= $orders_total ?></p>
        </div>
        <div class="card red-card">
            <i class="fa fa-bell"></i>
            <h3>Pending Orders</h3>
            <p id="newOrders" style="color:var(--red);"><?= $new_orders ?></p>
        </div>
        <div class="card">
            <i class="fa fa-calendar-day"></i>
            <h3>Today's Orders</h3>
            <p><?= $today_orders ?></p>
        </div>
        <div class="card green-card">
            <i class="fa fa-indian-rupee-sign"></i>
            <h3>Today's Revenue</h3>
            <p style="color:var(--green); font-size:22px;">₹<?= number_format($today_rev) ?></p>
        </div>
        <div class="card green-card">
            <i class="fa fa-chart-line"></i>
            <h3>Total Revenue</h3>
            <p style="color:var(--green); font-size:22px;">₹<?= number_format($total_rev) ?></p>
        </div>
        <div class="card blue-card">
            <i class="fa fa-chair"></i>
            <h3>Total Bookings</h3>
            <p><?= $bookings_total ?></p>
        </div>
        <div class="card blue-card">
            <i class="fa fa-calendar-check"></i>
            <h3>Today's Bookings</h3>
            <p><?= $today_bookings ?></p>
        </div>
        <div class="card">
            <i class="fa fa-users"></i>
            <h3>Customers</h3>
            <p><?= $customers ?></p>
        </div>
        <div class="card">
            <i class="fa fa-star"></i>
            <h3>Feedbacks</h3>
            <p><?= $feedbacks ?></p>
        </div>
    </div>

    <!-- Chart -->
    <div class="sales-chart">
        <h3>Last 7 Days Revenue</h3>
        <canvas id="salesChart" height="75"></canvas>
    </div>

    <!-- Recent Orders -->
    <div class="recent-orders">
        <h3>Recent Orders
            <a href="view_orders.php" style="margin-left:auto; font-size:13px; color:var(--orange); font-weight:600;">View All →</a>
        </h3>
        <table>
            <tr>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Time</th>
            </tr>
            <?php
            $result = mysqli_query($conn, "SELECT customer_name,total_amount,order_time,status FROM orders ORDER BY order_time DESC LIMIT 8");
            $count = 0;
            while($row = mysqli_fetch_assoc($result)){
                $count++;
                echo "<tr>
                    <td><strong>{$row['customer_name']}</strong></td>
                    <td style='color:var(--orange); font-weight:700;'>₹{$row['total_amount']}</td>
                    <td><span class='status {$row['status']}'>{$row['status']}</span></td>
                    <td style='color:var(--text-muted); font-size:12px;'>".date('d M, h:i A', strtotime($row['order_time']))."</td>
                </tr>";
            }
            if($count == 0) echo "<tr><td colspan='4' class='no-data'>📭 No orders yet</td></tr>";
            ?>
        </table>
    </div>

</div>

<script>
const salesData   = <?= json_encode($sales); ?>;
const salesLabels = <?= json_encode($labels); ?>;

const ctx = document.getElementById('salesChart');
if(ctx){
    new Chart(ctx, {
        type:'line',
        data:{
            labels: salesLabels,
            datasets:[{
                label:'Revenue (₹)',
                data: salesData,
                borderColor:'#ff7b00',
                backgroundColor:'rgba(255,123,0,0.08)',
                borderWidth:2.5, fill:true, tension:0.4,
                pointBackgroundColor:'#ff7b00',
                pointRadius:5, pointHoverRadius:8,
                pointBorderColor:'#0d0d0d', pointBorderWidth:2,
            }]
        },
        options:{
            responsive:true,
            plugins:{
                legend:{ display:false },
                tooltip:{
                    backgroundColor:'#1a1a1a', borderColor:'rgba(255,123,0,0.3)',
                    borderWidth:1, titleColor:'#fff', bodyColor:'#aaa',
                    callbacks:{ label: ctx => ' ₹' + ctx.parsed.y.toLocaleString() }
                }
            },
            scales:{
                y:{
                    beginAtZero:true,
                    grid:{ color:'rgba(255,255,255,0.04)' },
                    ticks:{ color:'#888', callback: v => '₹'+v }
                },
                x:{
                    grid:{ display:false },
                    ticks:{ color:'#888' }
                }
            }
        }
    });
}

// Live new orders check
let lastCount = <?= $new_orders ?>;
setInterval(function(){
    fetch("check_orders.php")
    .then(r => r.text())
    .then(data => {
        let newCount = parseInt(data);
        let el = document.getElementById("newOrders");
        if(el) el.innerText = newCount;
        if(newCount > lastCount){
            document.getElementById("orderSound").play().catch(()=>{});
        }
        lastCount = newCount;
    }).catch(()=>{});
}, 5000);
</script>

</body>
</html>