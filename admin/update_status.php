<?php
include 'includes/auth.php';
include 'includes/db.php';

// Loyalty helper load karo
$loyalty_helper = dirname(__DIR__) . '/includes/loyalty_helper.php';
if(file_exists($loyalty_helper)) include $loyalty_helper;

$id     = intval($_GET['id']);
$status = $_GET['status'];

if(in_array($status, ['Pending', 'Preparing', 'Completed'])) {

    // Pehle check karo kya pehle se Completed nahi tha
    $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status, customer_phone, customer_name, total_amount FROM orders WHERE id=$id LIMIT 1"));

    mysqli_query($conn, "UPDATE orders SET status='$status' WHERE id=$id");

    // ✅ Loyalty Points: Sirf jab Completed hoga tab points milenge (ek baar)
    if($status === 'Completed' && $old && $old['status'] !== 'Completed') {
        if(function_exists('loyalty_earn_points') && !empty($old['customer_phone'])) {
            loyalty_earn_points(
                $conn,
                $id,
                $old['customer_phone'],
                $old['customer_name'] ?? 'Customer',
                floatval($old['total_amount'])
            );
        }
    }
}

// ✅ Open redirect fix — sirf allowed pages
$allowed = ['view_orders.php', 'dashboard.php', 'reports.php'];
$ref     = $_GET['ref'] ?? 'view_orders.php';
$ref     = in_array(basename($ref), $allowed) ? $ref : 'view_orders.php';

header("Location: " . $ref);
exit;
?>