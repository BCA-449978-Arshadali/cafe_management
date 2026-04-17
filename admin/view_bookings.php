<?php
include 'includes/auth.php';
include 'includes/db.php';

// ✅ Mailer.php use karo (same jo customer pending email ke liye kaam karta hai)
require_once '../customer/includes/mailer.php';

// ============ HELPER FUNCTIONS ============

function getCustomerEmail($conn, $phone){
    $stmt = $conn->prepare("SELECT email FROM customers WHERE mobile=? LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return $r['email'] ?? null;
}

function sendBookingEmail($email, $name, $status, $table, $date, $time, $duration){
    $end_t    = date('h:i A', strtotime($date . ' ' . $time) + ($duration * 60));
    $date_fmt = date('d M Y', strtotime($date));
    $time_fmt = date('h:i A', strtotime($time));

    if($status == 'Confirmed'){
        $subject = '✅ Table Booking Confirmed! - Droppers Cafe';
        $body = "
        <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto;border:1px solid #eee;border-radius:10px;overflow:hidden;'>
            <div style='background:#27ae60;padding:20px;text-align:center;'>
                <h2 style='color:#fff;margin:0;'>✅ Booking Confirmed!</h2>
            </div>
            <div style='padding:24px;'>
                <p>Dear <b>$name</b>,</p>
                <p>Your table booking has been <b style='color:#27ae60;'>confirmed!</b></p>
                <table style='width:100%;border-collapse:collapse;background:#f9f9f9;border-radius:8px;'>
                    <tr><td style='padding:10px;color:#888;'>🪑 Table</td><td style='padding:10px;'><b>Table $table</b></td></tr>
                    <tr><td style='padding:10px;color:#888;'>📅 Date</td><td style='padding:10px;'><b>$date_fmt</b></td></tr>
                    <tr><td style='padding:10px;color:#888;'>⏰ Time</td><td style='padding:10px;'><b>$time_fmt – $end_t</b></td></tr>
                    <tr><td style='padding:10px;color:#888;'>⏱️ Duration</td><td style='padding:10px;'><b>$duration minutes</b></td></tr>
                </table>
                <p style='margin-top:20px;'>We look forward to seeing you! 🍽️</p>
            </div>
            <div style='background:#f5f5f5;padding:16px;text-align:center;font-size:12px;color:#888;'>
                <b>Droppers Cafe &amp; Resto</b> | Bheldi Road, Amnour, Bihar | 📞 +91 7004810081
            </div>
        </div>";
    } else {
        $subject = '❌ Table Booking Cancelled - Droppers Cafe';
        $body = "
        <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto;border:1px solid #eee;border-radius:10px;overflow:hidden;'>
            <div style='background:#e74c3c;padding:20px;text-align:center;'>
                <h2 style='color:#fff;margin:0;'>❌ Booking Cancelled</h2>
            </div>
            <div style='padding:24px;'>
                <p>Dear <b>$name</b>,</p>
                <p>Your booking for <b>Table $table</b> on <b>$date_fmt</b> at <b>$time_fmt</b> has been <b style='color:#e74c3c;'>cancelled</b>.</p>
                <p>Please contact us to rebook at a different time.</p>
                <p>📞 <b>+91 7004810081</b></p>
            </div>
            <div style='background:#f5f5f5;padding:16px;text-align:center;font-size:12px;color:#888;'>
                <b>Droppers Cafe &amp; Resto</b> | Bheldi Road, Amnour, Bihar
            </div>
        </div>";
    }

    // ✅ sendMail() use karo — same mailer.php jo pending email ke liye kaam karta hai
    sendMail($email, $subject, $body);
}

function sendBookingWhatsApp($phone, $name, $status, $table, $date, $time, $duration){
    // ⚠️ Replace with your Twilio credentials
    $twilio_sid   = 'YOUR_TWILIO_ACCOUNT_SID';
    $twilio_token = 'YOUR_TWILIO_AUTH_TOKEN';
    $twilio_from  = 'whatsapp:+14155238886';

    if($twilio_sid == 'YOUR_TWILIO_ACCOUNT_SID') return; // Skip if not configured

    $phone_clean = preg_replace('/[^0-9]/', '', $phone);
    if(strlen($phone_clean) == 10) $phone_clean = '91' . $phone_clean;
    $to = 'whatsapp:+' . $phone_clean;

    $end_t    = date('h:i A', strtotime($time) + ($duration * 60));
    $date_fmt = date('d M Y', strtotime($date));
    $time_fmt = date('h:i A', strtotime($time));

    if($status == 'Confirmed'){
        $msg = "✅ *Booking Confirmed!*\nDear $name,\n\n🪑 Table: $table\n📅 Date: $date_fmt\n⏰ Time: $time_fmt – $end_t\n\nSee you soon! 🍽️\n*Droppers Cafe & Resto*";
    } else {
        $msg = "❌ *Booking Cancelled*\nDear $name,\n\nYour booking for Table $table on $date_fmt at $time_fmt has been cancelled.\n\n📞 +91 7004810081\n*Droppers Cafe & Resto*";
    }

    $url  = "https://api.twilio.com/2010-04-01/Accounts/$twilio_sid/Messages.json";
    $data = ['From' => $twilio_from, 'To' => $to, 'Body' => $msg];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, "$twilio_sid:$twilio_token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}

// ============ STATUS UPDATE ============
if(isset($_GET['status']) && isset($_GET['id'])){
    $id     = intval($_GET['id']);
    $status = $_GET['status'];

    if(in_array($status, ['Confirmed', 'Cancelled', 'Pending'])){
        mysqli_query($conn, "UPDATE bookings SET status='$status' WHERE id='$id'");

        if($status == 'Confirmed' || $status == 'Cancelled'){
            $bk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM bookings WHERE id='$id'"));
            if($bk){
                $dur = $bk['duration'] ?? 60;
                // Email
                // pehle bookings.email check karo, fallback customers table
                $email = !empty($bk['email']) ? $bk['email'] : getCustomerEmail($conn, $bk['phone']);
                if($email) sendBookingEmail($email, $bk['name'], $status, $bk['table_no'], $bk['date'], $bk['time'], $dur);
                // WhatsApp
                sendBookingWhatsApp($bk['phone'], $bk['name'], $status, $bk['table_no'], $bk['date'], $bk['time'], $dur);
            }
        }
    }
    header("Location: view_bookings.php?filter=" . ($_GET['filter'] ?? 'today'));
    exit;
}

// ============ FILTER ============
$filter = $_GET['filter'] ?? 'today';

if($filter == 'today')        $query = mysqli_query($conn, "SELECT * FROM bookings WHERE date = CURDATE() ORDER BY time ASC");
elseif($filter == 'pending')  $query = mysqli_query($conn, "SELECT * FROM bookings WHERE status='Pending' ORDER BY date ASC, time ASC");
elseif($filter == 'upcoming') $query = mysqli_query($conn, "SELECT * FROM bookings WHERE date > CURDATE() ORDER BY date ASC, time ASC");
elseif($filter == 'past')     $query = mysqli_query($conn, "SELECT * FROM bookings WHERE date < CURDATE() ORDER BY date DESC");
else                          $query = mysqli_query($conn, "SELECT * FROM bookings ORDER BY date DESC, time DESC");

// Stats
$r1 = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings"); $total = mysqli_fetch_assoc($r1)['c'];
$r2 = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE date = CURDATE()"); $today_cnt = mysqli_fetch_assoc($r2)['c'];
$r3 = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE date > CURDATE()"); $upcoming = mysqli_fetch_assoc($r3)['c'];
$r4 = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE status='Confirmed' AND date = CURDATE()"); $confirmed = mysqli_fetch_assoc($r4)['c'];
$r5 = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE status='Pending'"); $pending = mysqli_fetch_assoc($r5)['c'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Table Bookings</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.stats-row { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
.stat-card { flex:1; min-width:130px; background:#fff; border-radius:12px; padding:16px 20px; box-shadow:0 2px 8px rgba(0,0,0,0.07); border-left:4px solid #d4a017; }
.stat-card.green  { border-left-color:#27ae60; }
.stat-card.orange { border-left-color:#e67e22; }
.stat-card.blue   { border-left-color:#2980b9; }
.stat-card h4 { margin:0 0 6px; font-size:12px; color:#888; font-weight:500; }
.stat-card p  { margin:0; font-size:24px; font-weight:700; color:#1b1f3b; }

.filter-tabs { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
.tab { padding:8px 18px; border-radius:20px; text-decoration:none; font-size:13px; font-weight:500; background:#f0f0f0; color:#555; transition:0.2s; }
.tab:hover, .tab.active { background:#1b1f3b; color:#fff; }
.tab.pending-tab.active { background:#e67e22; color:#fff; }
.cnt-badge { background:#e74c3c; color:#fff; padding:1px 7px; border-radius:10px; font-size:11px; font-weight:700; margin-left:4px; }

.table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.07); }
.table th { background:#1b1f3b; color:#fff; padding:12px 16px; font-size:13px; text-align:left; }
.table td { padding:11px 16px; font-size:14px; border-bottom:1px solid #f0f0f0; vertical-align:middle; color:#1b1f3b !important; }
.table tr:last-child td { border-bottom:none; }
.table tr:hover td { background:#fafafa; }
.today-row td { background:#fffdf0 !important; color:#1b1f3b !important; }

.status-badge { padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; }
.status-badge.Pending   { background:#fff3cd; color:#856404; }
.status-badge.Confirmed { background:#d4edda; color:#155724; }
.status-badge.Cancelled { background:#f8d7da; color:#721c24; }

.action-btns { display:flex; gap:6px; flex-wrap:wrap; }
.action-btns a { padding:5px 12px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none; transition:0.2s; }
.btn-confirm { background:#d4edda; color:#155724; }
.btn-confirm:hover { background:#27ae60; color:#fff; }
.btn-cancel  { background:#f8d7da; color:#721c24; }
.btn-cancel:hover  { background:#e74c3c; color:#fff; }
.no-data { text-align:center; padding:40px; color:#aaa; font-size:15px; }
.today-badge { background:#fff3cd; color:#856404; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; margin-left:5px; }
</style>
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">
    <div class="topbar">
        <h2>Table Bookings</h2>
        <div style="font-size:13px; color:#888;">📅 Today: <?php echo date('d M Y, l'); ?></div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card"><h4>📋 Total</h4><p><?php echo $total; ?></p></div>
        <div class="stat-card green"><h4>📅 Today</h4><p><?php echo $today_cnt; ?></p></div>
        <div class="stat-card orange"><h4>🔔 Pending</h4><p><?php echo $pending; ?></p></div>
        <div class="stat-card blue"><h4>⏰ Upcoming</h4><p><?php echo $upcoming; ?></p></div>
        <div class="stat-card"><h4>✅ Confirmed Today</h4><p><?php echo $confirmed; ?></p></div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="?filter=today"    class="tab <?php echo $filter=='today'   ?'active':'';?>">📅 Today <?php if($today_cnt>0) echo "<span class='cnt-badge'>$today_cnt</span>"; ?></a>
        <a href="?filter=pending"  class="tab pending-tab <?php echo $filter=='pending' ?'active':'';?>">🔔 Pending <?php if($pending>0) echo "<span class='cnt-badge'>$pending</span>"; ?></a>
        <a href="?filter=upcoming" class="tab <?php echo $filter=='upcoming'?'active':'';?>">⏰ Upcoming</a>
        <a href="?filter=past"     class="tab <?php echo $filter=='past'    ?'active':'';?>">📂 Past</a>
        <a href="?filter=all"      class="tab <?php echo $filter=='all'     ?'active':'';?>">📋 All</a>
    </div>

    <!-- Table -->
    <table class="table">
        <tr>
            <th>#</th><th>Name</th><th>Phone</th><th>Table</th>
            <th>Date</th><th>Time Slot</th><th>Duration</th>
            <th>Status</th><th>Action</th>
        </tr>
        <?php
        $rows = mysqli_fetch_all($query, MYSQLI_ASSOC);
        if(count($rows) == 0):
        ?>
        <tr><td colspan="9" class="no-data">
            <?php
            if($filter=='pending')  echo '📭 No pending bookings';
            elseif($filter=='today') echo '📭 No bookings today';
            elseif($filter=='upcoming') echo '📭 No upcoming bookings';
            else echo '📭 No bookings found';
            ?>
        </td></tr>
        <?php else: foreach($rows as $i => $row):
            $isToday = ($row['date'] == date('Y-m-d'));
            $status  = $row['status'] ?? 'Pending';
            $dur     = $row['duration'] ?? 60;
            $end_t   = date('h:i A', strtotime($row['time']) + ($dur * 60));
        ?>
        <tr class="<?php echo $isToday ? 'today-row' : ''; ?>">
            <td><?php echo $i+1; ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?><?php if($isToday) echo "<span class='today-badge'>Today</span>"; ?></td>
            <td><?php echo htmlspecialchars($row['phone']); ?></td>
            <td>🪑 Table <?php echo $row['table_no']; ?></td>
            <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
            <td><?php echo date('h:i A', strtotime($row['time'])); ?> – <?php echo $end_t; ?></td>
            <td><?php echo $dur; ?> min</td>
            <td><span class="status-badge <?php echo $status; ?>"><?php echo $status; ?></span></td>
            <td>
                <div class="action-btns">
                    <?php if($status == 'Pending'): ?>
                    <a href="?id=<?php echo $row['id']; ?>&status=Confirmed&filter=<?php echo $filter; ?>" class="btn-confirm">✓ Confirm</a>
                    <a href="?id=<?php echo $row['id']; ?>&status=Cancelled&filter=<?php echo $filter; ?>" class="btn-cancel" onclick="event.preventDefault(); var url=this.href; showConfirm('Cancel this booking?', function(){ window.location.href=url; }, {icon:'🗑️', okText:'Yes, Cancel', okClass:'gcm-btn-danger'});">✕ Cancel</a>
                    <?php elseif($status == 'Confirmed'): ?>
                    <a href="?id=<?php echo $row['id']; ?>&status=Cancelled&filter=<?php echo $filter; ?>" class="btn-cancel" onclick="event.preventDefault(); var url=this.href; showConfirm('Cancel this booking?', function(){ window.location.href=url; }, {icon:'🗑️', okText:'Yes, Cancel', okClass:'gcm-btn-danger'});">✕ Cancel</a>
                    <?php else: ?>
                    <span style="color:#aaa;font-size:12px;">—</span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; endif; ?>
    </table>
</div>
<?php include "includes/footer.php"; ?>
</body>
</html>