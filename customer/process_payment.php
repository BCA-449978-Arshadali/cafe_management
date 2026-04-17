<?php
require_once 'includes/auth-check.php';
include '../config.php';

// ✅ Bill email helper - GST aur dynamic delivery charge ke sath (Bug 4 & 5 fixed)
function buildBillEmail($order_id, $customer_name, $items_arr, $order_type, $delivery_charge, $subtotal, $gst, $grand, $payment_method, $address = ''){
    $rows = '';
    foreach($items_arr as $item){
        $rows .= "<tr>
            <td style='padding:10px 14px; border-bottom:1px solid #2a2a2a;'>{$item['name']}</td>
            <td style='padding:10px 14px; border-bottom:1px solid #2a2a2a; text-align:center;'>{$item['qty']}</td>
            <td style='padding:10px 14px; border-bottom:1px solid #2a2a2a; text-align:right;'>₹".($item['price'] * $item['qty'])."</td>
        </tr>";
    }
    $delivery_row = '';
    if($delivery_charge > 0){
        $delivery_row = "<tr>
            <td colspan='2' style='padding:8px 14px; color:#888;'>🛵 Delivery Charge</td>
            <td style='padding:8px 14px; text-align:right; color:#888;'>₹{$delivery_charge}</td>
        </tr>";
    }
    // GST row (Bug 4 fixed - ab GST dikhega)
    $gst_row = "<tr>
        <td colspan='2' style='padding:8px 14px; color:#888;'>🧾 GST (5%)</td>
        <td style='padding:8px 14px; text-align:right; color:#888;'>₹{$gst}</td>
    </tr>";
    $addr_row = '';
    if(!empty($address)){
        $addr_row = "<p style='margin:4px 0; color:#aaa;'>📍 Address: <strong style='color:#fff;'>".htmlspecialchars($address)."</strong></p>";
    }
    $type_icon = ['Dine In'=>'🪑','Takeaway'=>'🛍️','Home Delivery'=>'🛵'];
    $icon = $type_icon[$order_type] ?? '📦';

    return "
    <div style='background:#111; color:#fff; font-family:Arial,sans-serif; padding:30px; max-width:600px; margin:0 auto; border-radius:16px;'>
        <div style='text-align:center; margin-bottom:24px;'>
            <h1 style='color:#ff7b00; margin:0; font-size:26px;'>☕ Droppers Café & Resto</h1>
            <p style='color:#888; margin:6px 0 0;'>Your Order Bill</p>
        </div>
        <div style='background:#1a1a1a; border-radius:12px; padding:20px; margin-bottom:20px;'>
            <p style='margin:4px 0; color:#aaa;'>👤 Name: <strong style='color:#fff;'>".htmlspecialchars($customer_name)."</strong></p>
            <p style='margin:4px 0; color:#aaa;'>{$icon} Order Type: <strong style='color:#fff;'>{$order_type}</strong></p>
            <p style='margin:4px 0; color:#aaa;'>💳 Payment: <strong style='color:#fff;'>{$payment_method}</strong></p>
            <p style='margin:4px 0; color:#aaa;'>🔖 Order ID: <strong style='color:#ff7b00;'>#{$order_id}</strong></p>
            {$addr_row}
        </div>
        <table style='width:100%; border-collapse:collapse; background:#1a1a1a; border-radius:12px; overflow:hidden; margin-bottom:16px;'>
            <tr style='background:#ff7b00;'>
                <th style='padding:12px 14px; text-align:left; font-size:13px;'>Item</th>
                <th style='padding:12px 14px; text-align:center; font-size:13px;'>Qty</th>
                <th style='padding:12px 14px; text-align:right; font-size:13px;'>Amount</th>
            </tr>
            {$rows}
            {$delivery_row}
            {$gst_row}
            <tr style='background:#222;'>
                <td colspan='2' style='padding:12px 14px; font-weight:700; color:#ff7b00; font-size:16px;'>Total</td>
                <td style='padding:12px 14px; text-align:right; font-weight:700; color:#ff7b00; font-size:16px;'>₹{$grand}</td>
            </tr>
        </table>
        <div style='text-align:center; color:#555; font-size:13px; margin-top:20px;'>
            <p>Thank you for dining with us! 🙏</p>
            <p>For queries: +91 7004810081</p>
        </div>
    </div>";
}

if(isset($_POST['razorpay_payment_id'])){
    $payment_id  = mysqli_real_escape_string($conn, $_POST['razorpay_payment_id']);
    $order_type  = mysqli_real_escape_string($conn, $_POST['order_type']);
    $address     = mysqli_real_escape_string($conn, $_POST['delivery_address'] ?? '');
    $total       = intval($_POST['total_amount']);

    $name  = $_SESSION['customer_name']  ?? 'Guest';
    $email = $_SESSION['customer_email'] ?? '';
    $phone = $_SESSION['customer_phone'] ?? '';

    // Cart se items
    $items_arr  = [];
    $items_list = [];
    $subtotal   = 0;
    if(!empty($_SESSION['cart'])){
        foreach($_SESSION['cart'] as $item){
            $items_arr[]  = $item;
            $items_list[] = $item['name']." x".$item['qty'];
            $subtotal    += $item['price'] * $item['qty'];
        }
    }
    $items_str = mysqli_real_escape_string($conn, implode(', ', $items_list));

    $name_e  = mysqli_real_escape_string($conn, $name);
    $phone_e = mysqli_real_escape_string($conn, $phone);

    // Bug 5 fixed: delivery charge POST se lo, default 30
    $delivery_charge = ($order_type == 'Home Delivery') ? max(30, intval($_POST['delivery_charge'] ?? 30)) : 0;

    // Bug 4 fixed: GST calculate karo
    $gst   = round($subtotal * 0.05, 2);
    $grand = $subtotal + $gst + $delivery_charge;

    mysqli_query($conn,
        "INSERT INTO orders(customer_name, customer_phone, items, total_amount, order_type, payment_method, payment_status, delivery_address, razorpay_payment_id)
         VALUES('$name_e','$phone_e','$items_str','$grand','$order_type','Online','Paid','$address','$payment_id')"
    );

    $order_id = mysqli_insert_id($conn);

    // Bill email bhejo
    if(!empty($email)){
        require_once 'includes/mailer.php';
        $bill_html = buildBillEmail($order_id, $name, $items_arr, $order_type, $delivery_charge, $subtotal, $gst, $grand, 'Online Payment (Razorpay)', $address);
        sendMail($email, "Your Order Bill — Droppers Café #$order_id", $bill_html);
    }

    // Session mein save karo
    $_SESSION['last_order_id']        = $order_id;
    $_SESSION['last_order_items']     = $items_arr;
    $_SESSION['last_order_type']      = $order_type;
    $_SESSION['last_order_total']     = $grand;
    $_SESSION['last_order_payment']   = 'Online Payment (Razorpay)';
    $_SESSION['last_order_address']   = $address;
    $_SESSION['last_delivery_charge'] = $delivery_charge;
    $_SESSION['cart'] = [];

    header("Location: order_success.php");
    exit;
}

header("Location: cart.php");
exit;
?>