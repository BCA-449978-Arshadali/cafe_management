<?php
ob_start();
include '../config.php';                 // ✅ Pehle config — BASE_URL define hoga
$_modules_file = dirname(__DIR__) . '/modules.php';
if(file_exists($_modules_file)) include_once $_modules_file; // ✅ modules + feature flags
require_once 'includes/auth-check.php'; // Tab auth check

// ✅ Loyalty Points Helper
$loyalty_file = dirname(__DIR__) . '/includes/loyalty_helper.php';
if(file_exists($loyalty_file)) include $loyalty_file;

// ✅ modules.php se feature flags check karo
$flag_loyalty = function_exists('module_feature') ? module_feature('LOYALTY_POINTS') : true;
$flag_offers  = function_exists('module_feature') ? module_feature('OFFERS_PAGE')    : true;

$loyalty_enabled = $flag_loyalty && function_exists('loyalty_setting') && loyalty_setting($conn, 'points_enabled') == '1';
$customer_phone  = $_SESSION['customer_phone'] ?? '';
$loyalty_balance = ($loyalty_enabled && $customer_phone) ? get_loyalty_balance($conn, $customer_phone) : 0;
$min_redeem      = $loyalty_enabled ? intval(loyalty_setting($conn, 'min_redeem_points', '100')) : 100;
$redeem_rate     = $loyalty_enabled ? intval(loyalty_setting($conn, 'redeem_rate', '100'))        : 100;
// Points jo apply hue hain (session se)
$applied_points  = intval($_SESSION['applied_loyalty_points'] ?? 0);
$loyalty_discount = $applied_points > 0 ? round(($applied_points / $redeem_rate) * 10, 2) : 0;

// Remove item
if(isset($_GET['remove']) && isset($_SESSION['cart'][$_GET['remove']])){
    unset($_SESSION['cart'][$_GET['remove']]);
    header("Location: cart.php");
    exit;
}

// Update qty
if(isset($_POST['update_qty'])){
    foreach($_POST['qty'] as $id => $qty){
        if($qty <= 0) unset($_SESSION['cart'][$id]);
        else $_SESSION['cart'][$id]['qty'] = intval($qty);
    }
    header("Location: cart.php");
    exit;
}

// ✅ Apply Loyalty Points
if(isset($_POST['apply_points'])) {
    $pts = intval($_POST['redeem_points'] ?? 0);
    if($loyalty_enabled && $pts >= $min_redeem && $pts <= $loyalty_balance) {
        $_SESSION['applied_loyalty_points'] = $pts;
    }
    header("Location: cart.php"); exit;
}

// ✅ Remove Loyalty Points
if(isset($_GET['remove_points'])) {
    unset($_SESSION['applied_loyalty_points']);
    header("Location: cart.php"); exit;
}

// ✅ Apply Promo Code
// ✅ Agar features OFF hain toh session bhi clear karo
if(!$flag_loyalty) {
    unset($_SESSION['applied_loyalty_points']);
    $applied_points   = 0;
    $loyalty_discount = 0;
}
if(!$flag_offers) {
    unset($_SESSION['applied_promo']);
    unset($_SESSION['promo_error']);
}

if($flag_offers && isset($_POST['apply_promo'])) {
    $code  = strtoupper(trim($_POST['promo_code'] ?? ''));
    $today = date('Y-m-d');
    if(!empty($code)) {
        $c   = mysqli_real_escape_string($conn, $code);
        $row = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM offers WHERE promo_code='$c' AND is_active=1
             AND (valid_until IS NULL OR valid_until >= '$today')
             AND (valid_from IS NULL OR valid_from <= '$today') LIMIT 1"
        ));
        if($row) {
            $_SESSION['applied_promo'] = [
                'code'           => $row['promo_code'],
                'title'          => $row['title'],
                'discount_type'  => $row['discount_type'],
                'discount_value' => $row['discount_value'],
            ];
            $_SESSION['promo_error'] = '';
            $_SESSION['promo_success_popup'] = true; // ✅ Animation trigger
        } else {
            $_SESSION['applied_promo'] = null;
            $_SESSION['promo_error']   = 'Invalid or expired promo code!';
        }
    }
    header("Location: cart.php"); exit;
}

// ✅ Remove Promo Code
if(isset($_GET['remove_promo'])) {
    unset($_SESSION['applied_promo']);
    unset($_SESSION['promo_error']);
    header("Location: cart.php"); exit;
}

// Promo code values
// Promo values — sirf tab jab OFFERS_PAGE flag ON ho
$applied_promo    = ($flag_offers && isset($_SESSION['applied_promo'])) ? $_SESSION['applied_promo'] : null;
$promo_error      = ($flag_offers && isset($_SESSION['promo_error']))   ? $_SESSION['promo_error']   : '';
$promo_discount   = 0;
if($applied_promo) {
    if($applied_promo['discount_type'] === 'percent') {
        $promo_discount = round(($subtotal + round($subtotal * 0.05, 2)) * ($applied_promo['discount_value'] / 100), 2);
    } else {
        $promo_discount = floatval($applied_promo['discount_value']);
    }
}
unset($_SESSION['promo_error']); // clear after reading
$promo_success_popup = $_SESSION['promo_success_popup'] ?? false;
unset($_SESSION['promo_success_popup']); // ek baar dikhao phir clear

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

// Place Order (COD)
if(isset($_POST['place_order'])){
    $name       = $_SESSION['customer_name']  ?? 'Guest';
    $phone      = $_SESSION['customer_phone'] ?? '';
    $email      = $_SESSION['customer_email'] ?? '';
    $order_type      = $_POST['order_type'] ?? 'Dine In';
    $address         = ($order_type == 'Home Delivery') ? trim($_POST['delivery_address'] ?? '') : '';
    $delivery_charge_post = ($order_type == 'Home Delivery') ? max(30, intval($_POST['delivery_charge'] ?? 30)) : 0;
    $customer_lat    = trim($_POST['customer_lat'] ?? '');
    $customer_lng    = trim($_POST['customer_lng'] ?? '');

    // Append Google Maps link to address if coordinates available
    $maps_link = '';
    if(!empty($customer_lat) && !empty($customer_lng)){
        $maps_link = " [Maps: https://maps.google.com/?q={$customer_lat},{$customer_lng}]";
    }
    $address_with_map = $address . $maps_link;

    // ✅ 9 km distance server-side check
    if($order_type == 'Home Delivery' && !empty($customer_lat) && !empty($customer_lng)){
        $cafe_lat = 25.9330; $cafe_lng = 84.9320;
        $R = 6371;
        $dLat = deg2rad(floatval($customer_lat) - $cafe_lat);
        $dLon = deg2rad(floatval($customer_lng) - $cafe_lng);
        $a = sin($dLat/2)*sin($dLat/2) +
             cos(deg2rad($cafe_lat))*cos(deg2rad(floatval($customer_lat)))*
             sin($dLon/2)*sin($dLon/2);
        $km_dist = $R * 2 * atan2(sqrt($a), sqrt(1-$a));
        if($km_dist > 9){
            $cart_error = "Sorry! We only deliver within 9 km of the café. Your location is " . round($km_dist, 1) . " km away.";
        }
    }

    if($order_type == 'Home Delivery' && empty($address)){
        $cart_error = "Please enter delivery address!";
    } elseif(!empty($_SESSION['cart'])){
        $items_arr = []; $items_list = []; $subtotal = 0;
        foreach($_SESSION['cart'] as $cart_key => $item){
            $items_arr[] = $item;
            $label = $item['name'];
            // Combo? append included items so admin can see contents
            if(strpos($cart_key, 'combo_') === 0){
                $cid  = intval(substr($cart_key, 6));
                $cres = mysqli_query($conn,
                    "SELECT m.item_name FROM combo_items ci
                     JOIN menu m ON m.id = ci.menu_id
                     WHERE ci.combo_id = $cid"
                );
                $cnames = [];
                while($cr = mysqli_fetch_assoc($cres)) $cnames[] = $cr['item_name'];
                if(!empty($cnames)) $label .= ' (' . implode(' + ', $cnames) . ')';
            }
            $items_list[] = $label . " x" . $item['qty'];
            $subtotal    += $item['price'] * $item['qty'];
        }
        $gst             = round($subtotal * 0.05, 2);
        $delivery_charge = $delivery_charge_post;
        $loyalty_disc    = floatval($_POST['loyalty_discount'] ?? 0);
        $promo_disc      = floatval($_POST['promo_discount']   ?? 0);
        // Validate discounts
        $grand_before    = $subtotal + $gst + $delivery_charge;
        $total_disc      = $loyalty_disc + $promo_disc;
        $total_disc      = min($total_disc, $grand_before - 1);
        $grand           = $grand_before - $total_disc;
        $items_str       = implode(', ', $items_list);
        $name_e  = mysqli_real_escape_string($conn, $name);
        $phone_e = mysqli_real_escape_string($conn, $phone);
        $items_e = mysqli_real_escape_string($conn, $items_str);
        $addr_e  = mysqli_real_escape_string($conn, $address_with_map);
        mysqli_query($conn,
            "INSERT INTO orders(customer_name, customer_phone, items, total_amount, order_type, payment_method, payment_status, delivery_address)
             VALUES('$name_e','$phone_e','$items_e','$grand','$order_type','COD','Pending','$addr_e')"
        );
        $order_id = mysqli_insert_id($conn);

        // ✅ Loyalty Points Redeem — agar points apply kiye the
        if($loyalty_enabled && $loyalty_disc > 0 && $applied_points > 0 && !empty($phone)) {
            loyalty_redeem_points($conn, $phone, $applied_points);
        }
        unset($_SESSION['applied_loyalty_points']); // clear after order
        unset($_SESSION['applied_promo']);           // clear promo after order


            require_once 'includes/mailer.php';
            $bill_html = buildBillEmail($order_id, $name, $items_arr, $order_type, $delivery_charge, $subtotal, $gst, $grand, 'Cash on Delivery', $address);
            sendMail($email, "Your Order Bill — Droppers Café #$order_id", $bill_html);
        }
        $_SESSION['last_order_id']        = $order_id;
        $_SESSION['last_order_items']     = $items_arr;
        $_SESSION['last_order_type']      = $order_type;
        $_SESSION['last_order_total']     = $grand;
        $_SESSION['last_order_payment']   = 'Cash on Delivery';
        $_SESSION['last_order_address']   = $address_with_map;
        $_SESSION['last_delivery_charge'] = $delivery_charge;
        $_SESSION['cart'] = [];
        session_write_close();   // ← flush session to disk before redirect
        header("Location: order_success.php");
        exit;
    }
    

// Subtotal + GST
$subtotal = 0; $item_count = 0;
if(!empty($_SESSION['cart'])){
    foreach($_SESSION['cart'] as $item){
        $subtotal   += $item['price'] * $item['qty'];
        $item_count += $item['qty'];
    }
}
$gst   = round($subtotal * 0.05, 2);
$grand = $subtotal + $gst - $loyalty_discount - $promo_discount;

// Fetch included items for every combo in cart
$combo_items_map = [];   // cart_key => [item names]
if(!empty($_SESSION['cart'])){
    foreach($_SESSION['cart'] as $cart_key => $item){
        if(strpos($cart_key, 'combo_') === 0){
            $cid  = intval(substr($cart_key, 6));
            $cres = mysqli_query($conn,
                "SELECT m.item_name FROM combo_items ci
                 JOIN menu m ON m.id = ci.menu_id
                 WHERE ci.combo_id = $cid"
            );
            $cnames = [];
            while($cr = mysqli_fetch_assoc($cres)) $cnames[] = $cr['item_name'];
            $combo_items_map[$cart_key] = $cnames;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Droppers Café</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
body { background:#0d0d0d; color:#fff; font-family:'Poppins',sans-serif; overflow-x:hidden; }
a { text-decoration:none; }
.container { max-width:1100px; margin:0 auto; padding:0 24px; }

/* ===== PAGE HERO ===== */
.page-hero {
    background: linear-gradient(135deg, #0a0a0a 0%, #0f0f0a 50%, #0a0a0a 100%);
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
.page-hero h1 { font-size:clamp(30px,4vw,50px); font-weight:800; margin-bottom:10px; }
.page-hero h1 span { color:#ff7b00; }
.page-hero p { color:#888; font-size:15px; max-width:500px; line-height:1.7; }

/* ===== STATS ROW ===== */
.stats-row {
    display:grid; grid-template-columns:repeat(3,1fr);
    gap:16px; margin:40px 0;
}
.stat-card {
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:16px; padding:22px; text-align:center; transition:0.3s;
}
.stat-card:hover { border-color:rgba(255,123,0,0.3); transform:translateY(-3px); }
.stat-card .num {
    font-size:32px; font-weight:800;
    background:linear-gradient(135deg,#ff7b00,#ffb347);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    background-clip:text; line-height:1;
}
.stat-card .lbl { font-size:13px; color:#666; margin-top:6px; }

/* ===== MAIN SECTION ===== */
.cart-section { padding:20px 0 80px; }
.cart-layout {
    display:grid; grid-template-columns:1.2fr 0.9fr;
    gap:28px; align-items:start;
}

/* ===== SHARED CARD ===== */
.card {
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:20px; padding:32px;
}
.card-header { margin-bottom:24px; }
.card-header h2 { font-size:20px; font-weight:800; margin-bottom:4px; }
.card-header h2 span { color:#ff7b00; }
.card-header p { color:#666; font-size:13px; }

/* ===== CART TABLE ===== */
.cart-table { width:100%; border-collapse:collapse; }
.cart-table thead tr {
    background:rgba(255,123,0,0.1);
    border-bottom:1px solid rgba(255,123,0,0.2);
}
.cart-table th {
    padding:11px 14px; font-size:11px; text-align:left;
    color:#ff7b00; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;
}
.cart-table td {
    padding:14px; font-size:13px;
    border-bottom:1px solid rgba(255,255,255,0.04); vertical-align:middle;
}
.cart-table tbody tr:last-child td { border-bottom:none; }
.cart-table tbody tr { transition:background 0.2s; }
.cart-table tbody tr:hover { background:rgba(255,255,255,0.02); }

.item-thumb {
    width:38px; height:38px; border-radius:10px;
    background:linear-gradient(135deg,#ff6b00,#ffb347);
    display:flex; align-items:center; justify-content:center;
    font-size:18px; flex-shrink:0;
}
.item-info { display:flex; align-items:center; gap:10px; }
.item-name { font-weight:600; font-size:14px; }
.item-price { color:#ff7b00; font-weight:600; }

.qty-wrap { display:flex; align-items:center; gap:6px; }
.qty-btn {
    width:28px; height:28px; border-radius:8px;
    background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08);
    color:#fff; font-size:14px; cursor:pointer;
    display:flex; align-items:center; justify-content:center; transition:0.2s;
}
.qty-btn:hover { background:rgba(255,123,0,0.2); border-color:#ff7b00; color:#ff7b00; }
.qty-input {
    width:42px; padding:5px; background:#0d0d0d;
    border:1px solid rgba(255,255,255,0.08); border-radius:8px;
    color:#fff; font-size:13px; text-align:center; outline:none;
    font-family:'Poppins',sans-serif;
}
.qty-input:focus { border-color:#ff7b00; }

.remove-btn {
    width:30px; height:30px; border-radius:8px;
    background:rgba(231,76,60,0.08); border:1px solid rgba(231,76,60,0.2);
    color:#e74c3c; display:flex; align-items:center; justify-content:center;
    font-size:13px; transition:0.2s; cursor:pointer;
}
.remove-btn:hover { background:#e74c3c; color:#fff; border-color:#e74c3c; }

.cart-footer {
    display:flex; align-items:center; justify-content:space-between;
    margin-top:18px; padding-top:18px;
    border-top:1px solid rgba(255,255,255,0.06);
    flex-wrap:wrap; gap:10px;
}
.btn-secondary {
    display:inline-flex; align-items:center; gap:7px;
    padding:9px 18px; background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:10px; color:#aaa; font-size:13px;
    font-weight:600; transition:0.2s; cursor:pointer;
    font-family:'Poppins',sans-serif;
}
.btn-secondary:hover { border-color:#ff7b00; color:#ff7b00; }

/* ===== CHECKOUT CARD ===== */
.checkout-card { position:sticky; top:100px; }

/* Alert */
.alert {
    display:flex; align-items:flex-start; gap:10px;
    padding:12px 16px; border-radius:12px;
    font-size:13px; margin-bottom:18px; line-height:1.6;
}
.alert-error   { background:rgba(231,76,60,0.08);  border:1px solid rgba(231,76,60,0.3);  color:#e74c3c; }
.alert-success { background:rgba(39,174,96,0.08);  border:1px solid rgba(39,174,96,0.3);  color:#27ae60; }
.alert i { font-size:15px; margin-top:1px; flex-shrink:0; }

/* User info */
.user-info {
    display:flex; align-items:center; gap:12px;
    background:rgba(255,123,0,0.06);
    border:1px solid rgba(255,123,0,0.15);
    border-radius:12px; padding:12px 16px; margin-bottom:18px;
}
.user-avatar {
    width:36px; height:36px; border-radius:50%;
    background:linear-gradient(135deg,#ff6b00,#ffb347);
    display:flex; align-items:center; justify-content:center;
    font-size:15px; font-weight:700; color:#fff; flex-shrink:0;
}
.user-details { font-size:13px; }
.user-details strong { display:block; font-weight:700; color:#fff; }
.user-details span   { color:#888; font-size:12px; }

/* Bill notice */
.bill-notice {
    display:flex; align-items:center; gap:8px;
    background:rgba(39,174,96,0.08); border:1px solid rgba(39,174,96,0.25);
    color:#27ae60; padding:10px 14px; border-radius:10px;
    font-size:12px; margin-bottom:18px;
}

/* Section label */
.sec-label {
    font-size:11px; color:#666; font-weight:700;
    text-transform:uppercase; letter-spacing:0.8px;
    margin-bottom:10px; display:flex; align-items:center; gap:6px;
}

/* Order type */
.order-types { display:flex; gap:10px; margin-bottom:18px; }
.type-card {
    flex:1; background:#0d0d0d;
    border:1.5px solid rgba(255,255,255,0.07);
    border-radius:12px; padding:12px 10px; text-align:center;
    cursor:pointer; transition:0.2s;
}
.type-card:hover { border-color:rgba(255,123,0,0.4); }
.type-card input[type="radio"] { display:none; }
.type-card.selected { border-color:#ff7b00; background:rgba(255,123,0,0.08); }
.type-icon  { font-size:22px; display:block; margin-bottom:5px; }
.type-label { font-size:12px; font-weight:700; color:#fff; }
.type-note  { font-size:10px; color:#666; margin-top:2px; }

/* ── Delivery Address Form ── */
.address-field { display:none; margin-bottom:16px; }

.addr-live-btn {
    width:100%; padding:11px 14px; border-radius:12px; margin-bottom:12px;
    background:linear-gradient(135deg,rgba(82,143,245,0.1),rgba(82,143,245,0.05));
    border:1.5px solid rgba(82,143,245,0.35);
    color:#6fa3f7; font-size:13px; font-weight:700; cursor:pointer;
    font-family:'Poppins',sans-serif; transition:all 0.2s;
    display:flex; align-items:center; justify-content:center; gap:9px;
    position:relative; overflow:hidden;
}
.addr-live-btn::before {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg,rgba(82,143,245,0.15),transparent);
    opacity:0; transition:opacity 0.2s;
}
.addr-live-btn:hover { border-color:#528ff5; color:#93bfff; }
.addr-live-btn:hover::before { opacity:1; }
.addr-live-btn.loading { opacity:0.6; cursor:wait; }
.addr-live-btn.detected {
    border-color:rgba(39,174,96,0.5); color:#2ecc71;
    background:linear-gradient(135deg,rgba(39,174,96,0.08),rgba(39,174,96,0.03));
}

.dist-info {
    background:rgba(39,174,96,0.07); border:1px solid rgba(39,174,96,0.2);
    border-radius:10px; padding:9px 13px; margin-bottom:12px;
    font-size:11px; color:#2ecc71; font-weight:600;
    display:flex; align-items:center; gap:7px;
}

.addr-divider {
    display:flex; align-items:center; gap:10px; margin-bottom:12px;
}
.addr-divider::before, .addr-divider::after {
    content:''; flex:1; height:1px; background:rgba(255,255,255,0.06);
}
.addr-divider span { font-size:10px; color:#444; font-weight:600; letter-spacing:0.6px; white-space:nowrap; }

.addr-grid {
    display:grid; gap:9px;
}
.addr-row-2 { grid-template-columns:1fr 1fr; }
.addr-row-1 { grid-template-columns:1fr; }

.addr-field-wrap { display:flex; flex-direction:column; gap:4px; }
.addr-field-label {
    font-size:10px; color:#666; font-weight:700;
    text-transform:uppercase; letter-spacing:0.6px;
    display:flex; align-items:center; gap:5px;
}
.addr-field-label i { font-size:9px; color:#ff7b00; }

.addr-input {
    width:100%; padding:10px 12px;
    background:rgba(255,255,255,0.03);
    border:1.5px solid rgba(255,255,255,0.07);
    border-radius:10px; color:#fff; font-size:13px;
    font-family:'Poppins',sans-serif; outline:none; transition:all 0.2s;
}
.addr-input::placeholder { color:#363636; font-size:12px; }
.addr-input:focus {
    border-color:rgba(255,123,0,0.5);
    background:rgba(255,123,0,0.03);
    box-shadow:0 0 0 3px rgba(255,123,0,0.07);
}
.addr-input.filled {
    border-color:rgba(255,255,255,0.12);
    background:rgba(255,255,255,0.04);
}
.addr-input.error { border-color:rgba(231,76,60,0.6) !important; }

/* ── Map Pin Picker ── */
.map-container {
    border-radius:12px; overflow:hidden;
    border:1.5px solid rgba(255,123,0,0.25);
    margin-bottom:10px; position:relative;
}
#deliveryMap {
    width:100%; height:260px; background:#1a1a1a;
}
/* Leaflet tooltip custom */
.cafe-tooltip {
    background:#1a1a1a !important;
    border:1px solid rgba(255,123,0,0.35) !important;
    color:#fff !important; font-size:12px !important;
    border-radius:8px !important; padding:6px 10px !important;
    box-shadow:0 4px 16px rgba(0,0,0,0.5) !important;
    font-family:'Poppins',sans-serif !important;
}
.cafe-tooltip::before { border-top-color: rgba(255,123,0,0.35) !important; }
/* Map locate me button */
.map-locate-btn {
    position:absolute; bottom:10px; right:10px; z-index:999;
    background:linear-gradient(135deg,#ff7b00,#ff9500);
    color:#fff; border:none; border-radius:10px;
    padding:7px 12px; font-size:11px; font-weight:700;
    cursor:pointer; font-family:'Poppins',sans-serif;
    display:flex; align-items:center; gap:5px;
    box-shadow:0 3px 10px rgba(255,123,0,0.4);
}
.map-locate-btn:hover { transform:scale(1.04); }
.map-hint {
    position:absolute; bottom:8px; left:50%; transform:translateX(-50%);
    background:rgba(0,0,0,0.75); color:#fff;
    font-size:10px; font-weight:600; padding:4px 12px;
    border-radius:20px; pointer-events:none; white-space:nowrap;
    border:1px solid rgba(255,123,0,0.3); z-index:999;
}
.map-pin-status {
    display:flex; align-items:center; gap:7px;
    background:rgba(255,123,0,0.07); border:1px solid rgba(255,123,0,0.2);
    border-radius:10px; padding:8px 12px; margin-bottom:10px;
    font-size:11px; color:#ff9b3a; font-weight:600; display:none;
}
.map-pin-status i { color:#ff7b00; }

/* pincode verify badge */
.pincode-wrap { position:relative; }
.pincode-wrap .addr-input { padding-right:72px; }
.pin-status {
    position:absolute; right:10px; top:50%; transform:translateY(-50%);
    font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px;
    display:none;
}
.pin-status.valid   { display:block; background:rgba(39,174,96,0.15); color:#2ecc71; border:1px solid rgba(39,174,96,0.3); }
.pin-status.invalid { display:block; background:rgba(231,76,60,0.1);  color:#e74c3c; border:1px solid rgba(231,76,60,0.25); }

/* Payment */
.payment-options { display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
.payment-card {
    flex:1; min-width:130px; background:#0d0d0d;
    border:1.5px solid rgba(255,255,255,0.07);
    border-radius:12px; padding:12px 14px;
    cursor:pointer; transition:0.2s;
    display:flex; align-items:center; gap:10px;
}
.payment-card:hover { border-color:rgba(255,123,0,0.4); }
.payment-card.selected { border-color:#ff7b00; background:rgba(255,123,0,0.08); }
.payment-card input[type="radio"] { display:none; }
.payment-icon { font-size:20px; flex-shrink:0; }
.payment-info strong { font-size:13px; color:#fff; display:block; }
.payment-info span   { font-size:11px; color:#777; }

/* Order summary */
.order-summary {
    background:#0d0d0d; border:1px solid rgba(255,255,255,0.06);
    border-radius:12px; padding:14px 16px; margin-bottom:16px;
}
.summary-row {
    display:flex; justify-content:space-between;
    padding:6px 0; font-size:13px; color:#888;
    border-bottom:1px solid rgba(255,255,255,0.04);
}
.summary-row:last-child {
    border-bottom:none; color:#ff7b00;
    font-weight:700; font-size:15px; margin-top:4px;
}
.delivery-charge { font-size:12px !important; }

/* Buttons */
.btn-submit {
    width:100%; padding:14px;
    background:linear-gradient(135deg, #ff7b00, #ff9500);
    color:#fff; border:none; border-radius:14px;
    font-size:15px; font-weight:700; cursor:pointer;
    font-family:'Poppins',sans-serif;
    transition:all 0.3s; box-shadow:0 8px 24px rgba(255,123,0,0.3);
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-submit:hover { transform:translateY(-2px); box-shadow:0 12px 30px rgba(255,123,0,0.45); }

.btn-razorpay {
    width:100%; padding:14px;
    background:linear-gradient(135deg, #528ff5, #3b5fc0);
    color:#fff; border:none; border-radius:14px;
    font-size:15px; font-weight:700; cursor:pointer;
    font-family:'Poppins',sans-serif;
    transition:all 0.3s; box-shadow:0 8px 24px rgba(82,143,245,0.25);
    display:none; align-items:center; justify-content:center; gap:8px;
}
.btn-razorpay:hover { transform:translateY(-2px); box-shadow:0 12px 30px rgba(82,143,245,0.4); }

/* ===== EMPTY CART ===== */
.empty-wrap { padding:60px 0 80px; }
.empty-cart {
    text-align:center; padding:60px 30px;
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:20px;
}
.empty-cart .empty-icon { font-size:56px; margin-bottom:16px; display:block; }
.empty-cart h2 { font-size:22px; font-weight:700; color:#444; margin-bottom:8px; }
.empty-cart p  { color:#555; font-size:14px; margin-bottom:24px; }
.btn-menu {
    display:inline-flex; align-items:center; gap:8px;
    padding:12px 28px; background:linear-gradient(135deg,#ff7b00,#ff9500);
    color:#fff; border-radius:12px; font-weight:700; font-size:14px;
    box-shadow:0 6px 20px rgba(255,123,0,0.3); transition:0.3s;
}
.btn-menu:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(255,123,0,0.45); }

/* ===== FLOATING BUTTONS ===== */
.instagram-float {
    position:fixed; bottom:144px; right:28px; z-index:999;
    width:50px; height:50px; border-radius:50%;
    background:radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);
    color:#fff; display:flex; align-items:center; justify-content:center;
    font-size:24px; box-shadow:0 4px 20px rgba(214,36,159,0.4);
    animation:pulse-ig 2s ease-in-out infinite; transition:0.3s;
}
.instagram-float:hover { transform:scale(1.1); color:#fff; }
@keyframes pulse-ig {
    0%,100% { box-shadow:0 4px 20px rgba(214,36,159,0.4); }
    50%      { box-shadow:0 4px 40px rgba(214,36,159,0.7); }
}
.whatsapp-float {
    position:fixed; bottom:84px; right:28px; z-index:999;
    width:50px; height:50px; border-radius:50%;
    background:#25D366; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:24px; box-shadow:0 4px 20px rgba(37,211,102,0.4);
    animation:pulse-wa 2s ease-in-out infinite; transition:0.3s;
}
.whatsapp-float:hover { transform:scale(1.1); color:#fff; }
@keyframes pulse-wa {
    0%,100% { box-shadow:0 4px 20px rgba(37,211,102,0.4); }
    50%      { box-shadow:0 4px 40px rgba(37,211,102,0.7); }
}
.back-to-top {
    position:fixed; bottom:28px; right:28px; z-index:999;
    width:44px; height:44px; border-radius:12px;
    background:#ff7b00; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:18px; box-shadow:0 4px 16px rgba(255,123,0,0.4);
    opacity:0; pointer-events:none; transition:0.3s;
}
.back-to-top.show { opacity:1; pointer-events:auto; }
.back-to-top:hover { background:#e06900; transform:translateY(-3px); color:#fff; }

/* ===== RESPONSIVE ===== */
@media(max-width:900px){
    .cart-layout { grid-template-columns:1fr; }
    .checkout-card { position:static; }
    .stats-row { grid-template-columns:repeat(3,1fr); gap:10px; }
    .page-hero { padding:60px 0 36px; }
    .page-hero h1 { font-size:28px; }
    #deliveryMap { height:220px; }
}
@media(max-width:640px){
    .container { padding:0 14px; }
    .card { padding:18px 14px; border-radius:16px; }
    .card-header h2 { font-size:17px; }
    .page-hero { padding:48px 0 28px; }
    .page-hero h1 { font-size:24px; }
    .page-hero p  { font-size:13px; }
    .stats-row { grid-template-columns:1fr 1fr; gap:10px; margin:24px 0; }
    .stats-row .stat-card:last-child { grid-column:span 2; }
    .stat-card .num { font-size:24px; }
    .cart-table thead { display:none; }
    .cart-table, .cart-table tbody, .cart-table tr, .cart-table td { display:block; width:100%; }
    .cart-table tr {
        border:1px solid rgba(255,255,255,0.06);
        border-radius:14px; padding:12px;
        margin-bottom:10px; position:relative;
        background:rgba(255,255,255,0.01);
    }
    .cart-table tr:last-child { margin-bottom:0; }
    .cart-table td { padding:4px 0; border:none; font-size:13px; }
    .cart-table td:nth-child(2)::before { content:"Price: "; color:#888; font-size:11px; }
    .cart-table td:nth-child(3) { display:flex; align-items:center; gap:8px; }
    .cart-table td:nth-child(3)::before { content:"Qty: "; color:#888; font-size:11px; }
    .cart-table td:nth-child(4)::before { content:"Total: "; color:#ff7b00; font-size:11px; font-weight:700; }
    .cart-table td:last-child { position:absolute; top:12px; right:12px; }
    .order-types { gap:8px; }
    .type-card { padding:10px 6px; border-radius:10px; }
    .type-icon { font-size:18px; }
    .type-label { font-size:11px; }
    .type-note { display:none; }
    .addr-row-2 { grid-template-columns:1fr; }
    .addr-input { font-size:14px; padding:11px 12px; }
    #deliveryMap { height:200px; }
    .map-hint { font-size:9px; }
    .btn-submit, .btn-razorpay { font-size:14px; padding:14px 18px; border-radius:14px; }
    .btn-secondary { font-size:12px; padding:8px 14px; }
    .payment-options { gap:8px; }
    .payment-card { padding:10px 12px; }
    .payment-info strong { font-size:13px; }
    .payment-info span   { font-size:11px; }
    .summary-row { font-size:13px; }
    .whatsapp-float  { bottom:76px; right:14px; width:44px; height:44px; font-size:20px; }
    .instagram-float { bottom:130px; right:14px; width:44px; height:44px; font-size:20px; }
    .back-to-top     { bottom:20px; right:14px; width:38px; height:38px; font-size:15px; }
    .user-info { padding:10px 12px; }
    .order-summary { padding:14px; }
}
@media(max-width:360px){
    .card { padding:14px 10px; }
    .page-hero h1 { font-size:20px; }
    .stats-row { grid-template-columns:repeat(3,1fr); }
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Page Hero -->
<div class="page-hero">
    <div class="container">
        <div class="page-hero-badge">
            <i class="fa fa-cart-shopping"></i> Your Cart
        </div>
        <h1>Review Your <span>Order</span></h1>
        <p>Check your items, choose how you'd like to receive them, and place your order!</p>
    </div>
</div>

<?php if(!empty($_SESSION['cart'])): ?>

<!-- Stats Row -->
<div class="container">
    <div class="stats-row">
        <div class="stat-card">
            <div class="num"><?= $item_count ?></div>
            <div class="lbl">Items in Cart</div>
        </div>
        <div class="stat-card">
            <div class="num">₹<?= $subtotal ?></div>
            <div class="lbl">Subtotal (incl. offers)</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= count($_SESSION['cart']) ?></div>
            <div class="lbl">Unique Items</div>
        </div>
    </div>
</div>

<div class="cart-section">
    <div class="container">
        <div class="cart-layout">

            <!-- LEFT: Cart Items -->
            <div class="card">
                <div class="card-header">
                    <h2>Cart <span>Items</span></h2>
                    <p>Review and update your selected items</p>
                </div>

                <?php if(isset($cart_error)): ?>
                <div class="alert alert-error"><i class="fa fa-circle-exclamation"></i><span><?= $cart_error ?></span></div>
                <?php endif; ?>

                <?php if(!empty($_SESSION['reorder_msg'])): ?>
                <?php $rm = $_SESSION['reorder_msg']; unset($_SESSION['reorder_msg']); ?>
                <div class="alert alert-success" style="margin-bottom:14px;">
                    <i class="fa fa-rotate-right"></i>
                    <span>
                        <strong>Reorder from #<?= $rm['order_id'] ?></strong> —
                        <?= $rm['added'] ?> item(s) added to cart<?= $rm['skipped'] > 0 ? ', '.$rm['skipped'].' unavailable item(s) skipped' : '' ?>.
                    </span>
                </div>
                <?php endif; ?>

                <form method="POST" id="cartForm">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($_SESSION['cart'] as $id => $item):
                        $total     = $item['price'] * $item['qty'];
                        $is_combo  = strpos($id, 'combo_') === 0;
                        $thumb_bg  = $is_combo
                            ? 'background:linear-gradient(135deg,#d4a017,#b8860b);'
                            : 'background:linear-gradient(135deg,#ff6b00,#ffb347);';
                        $initial   = $is_combo ? '🍱' : strtoupper(substr($item['name'], 0, 1));
                        $contents  = $combo_items_map[$id] ?? [];
                    ?>
                    <tr>
                        <td>
                            <div class="item-info" style="align-items:flex-start;">
                                <div class="item-thumb" style="<?= $thumb_bg ?> font-size:16px; margin-top:3px; flex-shrink:0;">
                                    <?= $initial ?>
                                </div>
                                <div>
                                    <!-- Name + badge row -->
                                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                        <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                                        <?php if($is_combo): ?>
                                        <span style="background:#d4a017;color:#000;font-size:9px;font-weight:800;padding:2px 8px;border-radius:20px;letter-spacing:0.4px;white-space:nowrap;">🍱 COMBO</span>
                                        <?php else: ?>
                                        <span style="background:rgba(39,174,96,0.12);color:#2ecc71;border:1px solid rgba(39,174,96,0.3);font-size:9px;font-weight:800;padding:2px 7px;border-radius:20px;white-space:nowrap;">🏷️ 5% OFF</span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Combo contents chips -->
                                    <?php if(!empty($contents)): ?>
                                    <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:5px;">
                                        <?php foreach($contents as $ci): ?>
                                        <span style="padding:1px 7px;border-radius:10px;background:rgba(212,160,23,0.1);border:1px solid rgba(212,160,23,0.25);font-size:10px;color:#d4a017;font-weight:600;">
                                            <?= htmlspecialchars($ci) ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><span class="item-price" style="<?= $is_combo ? 'color:#d4a017;' : '' ?>">₹<?= $item['price'] ?></span></td>
                        <td>
                            <div class="qty-wrap">
                                <button type="button" class="qty-btn" onclick="changeQty('qty_<?= $id ?>',-1)">−</button>
                                <input type="number" class="qty-input" name="qty[<?= $id ?>]" id="qty_<?= $id ?>" value="<?= $item['qty'] ?>" min="1">
                                <button type="button" class="qty-btn" onclick="changeQty('qty_<?= $id ?>',1)">+</button>
                            </div>
                        </td>
                        <td><strong>₹<span id="tot_<?= $id ?>"><?= $total ?></span></strong></td>
                        <td>
                            <a href="cart.php?remove=<?= $id ?>" class="remove-btn" title="Remove">
                                <i class="fa fa-xmark"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="cart-footer">
                    <a href="menu.php" class="btn-secondary">
                        <i class="fa fa-arrow-left fa-xs"></i> Continue Shopping
                    </a>
                    <button type="submit" name="update_qty" class="btn-secondary">
                        <i class="fa fa-rotate-right fa-xs"></i> Update Cart
                    </button>
                </div>
                </form>
            </div>

            <!-- RIGHT: Checkout -->
            <div class="card checkout-card">
                <div class="card-header">
                    <h2>Place <span>Order</span></h2>
                    <p>Complete your order details below</p>
                </div>

                <!-- User Info -->
                <div class="user-info">
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['customer_name'] ?? 'G', 0, 1)) ?></div>
                    <div class="user-details">
                        <strong><?= htmlspecialchars($_SESSION['customer_name'] ?? 'Guest') ?></strong>
                        <span>
                            <?php if(!empty($_SESSION['customer_phone'])): ?>📞 <?= $_SESSION['customer_phone'] ?><?php endif; ?>
                            <?php if(!empty($_SESSION['customer_email'])): ?> &nbsp;·&nbsp; 📧 <?= $_SESSION['customer_email'] ?><?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- Email bill notice -->
                <?php if(!empty($_SESSION['customer_email'])): ?>
                <div class="bill-notice">
                    <i class="fa fa-envelope fa-xs"></i>
                    Bill will be emailed to <strong>&nbsp;<?= $_SESSION['customer_email'] ?></strong>
                </div>
                <?php endif; ?>

                <!-- Order Type -->
                <div class="sec-label"><i class="fa fa-utensils fa-xs"></i> Order Type</div>
                <div class="order-types">
                    <label class="type-card selected" onclick="selectType(this,'Dine In')">
                        <input type="radio" name="order_type_display" value="Dine In" checked>
                        <span class="type-icon">🪑</span>
                        <div class="type-label">Dine In</div>
                        <div class="type-note">At café</div>
                    </label>
                    <label class="type-card" onclick="selectType(this,'Takeaway')">
                        <input type="radio" name="order_type_display" value="Takeaway">
                        <span class="type-icon">🛍️</span>
                        <div class="type-label">Takeaway</div>
                        <div class="type-note">Pick up</div>
                    </label>
                    <label class="type-card" onclick="selectType(this,'Home Delivery')">
                        <input type="radio" name="order_type_display" value="Home Delivery">
                        <span class="type-icon">🛵</span>
                        <div class="type-label">Delivery</div>
                        <div class="type-note">₹9/km</div>
                    </label>
                </div>

                <!-- Delivery Address -->
                <div class="address-field" id="addressField">

                    <!-- Live Location -->
                    <button type="button" class="addr-live-btn" id="liveLocBtn" onclick="getLocation()">
                        <i class="fa fa-crosshairs"></i>
                        <span id="liveLocText">📍 Use My Live Location</span>
                    </button>

                    <!-- Distance Info -->
                    <div class="dist-info" id="distInfo" style="display:none;">
                        <i class="fa fa-route fa-xs"></i>
                        <span id="distText">Calculating...</span>
                    </div>

                    <!-- Map Pin Picker -->
                    <div class="map-container" id="mapContainer" style="display:none; position:relative;">
                        <div id="deliveryMap"></div>
                        <div class="map-hint">🏠 Drag pin or tap map to set location</div>
                        <button type="button" class="map-locate-btn" onclick="getLocation()">
                            <i class="fa fa-crosshairs"></i> My Location
                        </button>
                    </div>
                    <div class="map-pin-status" id="mapPinStatus">
                        <i class="fa fa-map-pin fa-xs"></i>
                        <span id="mapPinText">Pin set — address fields updated</span>
                    </div>

                    <div class="addr-divider"><span>OR ENTER MANUALLY</span></div>

                    <!-- House No + Floor -->
                    <div class="addr-grid addr-row-2" style="margin-bottom:9px;">
                        <div class="addr-field-wrap">
                            <label class="addr-field-label"><i class="fa fa-house"></i> House / Flat No.</label>
                            <input type="text" id="addrHouse" class="addr-input"
                                   placeholder="e.g. 42B, Flat 3"
                                   oninput="toggleFilled(this); buildAddress()">
                        </div>
                        <div class="addr-field-wrap">
                            <label class="addr-field-label"><i class="fa fa-layer-group"></i> Floor / Wing</label>
                            <input type="text" id="addrFloor" class="addr-input"
                                   placeholder="e.g. 2nd Floor"
                                   oninput="toggleFilled(this); buildAddress()">
                        </div>
                    </div>

                    <!-- Street / Colony -->
                    <div class="addr-grid addr-row-1" style="margin-bottom:9px;">
                        <div class="addr-field-wrap">
                            <label class="addr-field-label"><i class="fa fa-road"></i> Street / Colony / Area <span style="color:#e74c3c">*</span></label>
                            <input type="text" id="addrStreet" class="addr-input"
                                   placeholder="e.g. Bheldi Road, Amnour"
                                   oninput="toggleFilled(this); buildAddress()">
                        </div>
                    </div>

                    <!-- Landmark -->
                    <div class="addr-grid addr-row-1" style="margin-bottom:9px;">
                        <div class="addr-field-wrap">
                            <label class="addr-field-label"><i class="fa fa-map-pin"></i> Landmark</label>
                            <input type="text" id="addrLandmark" class="addr-input"
                                   placeholder="e.g. Near XYZ School, Opp. Bank"
                                   oninput="toggleFilled(this); buildAddress()">
                        </div>
                    </div>

                    <!-- City + Pincode -->
                    <div class="addr-grid addr-row-2">
                        <div class="addr-field-wrap">
                            <label class="addr-field-label"><i class="fa fa-city"></i> City / Town <span style="color:#e74c3c">*</span></label>
                            <input type="text" id="addrCity" class="addr-input"
                                   placeholder="e.g. Saran"
                                   oninput="toggleFilled(this); buildAddress()">
                        </div>
                        <div class="addr-field-wrap">
                            <label class="addr-field-label"><i class="fa fa-hashtag"></i> Pincode <span style="color:#e74c3c">*</span></label>
                            <div class="pincode-wrap">
                                <input type="text" id="addrPin" class="addr-input"
                                       placeholder="6-digit" maxlength="6"
                                       oninput="toggleFilled(this); validatePin(this); buildAddress()">
                                <span class="pin-status" id="pinStatus"></span>
                            </div>
                        </div>
                    </div>

                    <!-- hidden combined address fed to form -->
                    <input type="hidden" id="addressInput" value="">
                </div>

                <!-- Payment Method -->
                <div class="sec-label"><i class="fa fa-credit-card fa-xs"></i> Payment Method</div>
                <div class="payment-options">
                    <label class="payment-card selected" onclick="selectPayment(this,'COD')">
                        <input type="radio" name="payment_display" value="COD" checked>
                        <span class="payment-icon">💵</span>
                        <div class="payment-info">
                            <strong>Cash on Delivery</strong>
                            <span>Pay when received</span>
                        </div>
                    </label>
                    <label class="payment-card" onclick="selectPayment(this,'Razorpay')">
                        <input type="radio" name="payment_display" value="Razorpay">
                        <span class="payment-icon">📱</span>
                        <div class="payment-info">
                            <strong>Online Pay</strong>
                            <span>UPI / Card</span>
                        </div>
                    </label>
                </div>

                <!-- Order Summary -->
                <div class="sec-label"><i class="fa fa-receipt fa-xs"></i> Order Summary</div>
                <div class="order-summary">
                    <?php foreach($_SESSION['cart'] as $item): ?>
                    <div class="summary-row">
                        <span><?= htmlspecialchars($item['name']) ?> × <?= $item['qty'] ?></span>
                        <span>₹<?= $item['price'] * $item['qty'] ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="summary-row" style="color:#888; font-size:12px;">
                        <span>Subtotal</span>
                        <span id="subtotalAmt">₹<?= $subtotal ?></span>
                    </div>
                    <div class="summary-row" style="color:#27ae60; font-size:12px;">
                        <span>🧾 GST (5%)</span>
                        <span id="gstAmt">₹<?= $gst ?></span>
                    </div>
                    <div class="summary-row delivery-charge" id="deliveryRow" style="display:none;">
                        <span>🛵 Delivery Charge <small id="distLabel" style="color:#666;font-size:11px;"></small></span>
                        <span id="deliveryAmt">₹0</span>
                    </div>

                    <?php if($flag_offers): ?>
                    <!-- ✅ PROMO CODE SECTION — sirf jab OFFERS_PAGE = true -->
                    <?php if($applied_promo): ?>
                    <div class="summary-row" style="color:#27ae60; background:rgba(39,174,96,0.06); border-radius:8px; padding:10px 12px; border:1px solid rgba(39,174,96,0.2); margin:4px 0;">
                        <span style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                            🎁 <strong><?= htmlspecialchars($applied_promo['code']) ?></strong>
                            <span style="color:#888; font-size:11px;">(<?= htmlspecialchars($applied_promo['title']) ?>)</span>
                            <a href="cart.php?remove_promo=1" style="color:#e74c3c; font-size:11px;">[Remove]</a>
                        </span>
                        <span style="font-weight:700; white-space:nowrap;">−₹<?= number_format($promo_discount, 2) ?></span>
                    </div>
                    <?php else: ?>
                    <div style="margin:8px 0;">
                        <form method="POST" style="display:flex; gap:8px; align-items:stretch;">
                            <input type="text" name="promo_code"
                                   placeholder="Enter promo code"
                                   style="flex:1; padding:9px 12px; background:#1a1a1a; border:1px solid <?= $promo_error ? '#e74c3c' : 'rgba(255,255,255,0.1)' ?>; border-radius:8px; color:#fff; font-size:13px; font-family:'Poppins',sans-serif; text-transform:uppercase; letter-spacing:1px;"
                                   maxlength="30">
                            <button type="submit" name="apply_promo"
                                    style="background:#1a1a1a; color:#ff7b00; border:1px solid rgba(255,123,0,0.4); padding:9px 14px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; white-space:nowrap; font-family:'Poppins',sans-serif;">
                                Apply
                            </button>
                        </form>
                        <?php if($promo_error): ?>
                        <div style="color:#e74c3c; font-size:11px; margin-top:5px;">❌ <?= htmlspecialchars($promo_error) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; // flag_offers ?>

                    <?php if($flag_loyalty && $loyalty_enabled && $loyalty_balance >= $min_redeem): ?>
                    <!-- ✅ LOYALTY POINTS SECTION — sirf jab LOYALTY_POINTS = true -->
                    <?php if($applied_points > 0): ?>
                    <!-- Points Applied -->
                    <div class="summary-row" style="color:#27ae60; background:rgba(39,174,96,0.06); border-radius:8px; padding:10px 12px; border:1px solid rgba(39,174,96,0.2); margin:4px 0;">
                        <span style="display:flex; align-items:center; gap:6px;">
                            🏆 <span><?= $applied_points ?> pts redeemed</span>
                            <a href="cart.php?remove_points=1" style="color:#e74c3c; font-size:11px; margin-left:6px;">[Remove]</a>
                        </span>
                        <span style="font-weight:700;">−₹<?= $loyalty_discount ?></span>
                    </div>
                    <?php else: ?>
                    <!-- Apply Points Box -->
                    <div style="background:rgba(255,123,0,0.06); border:1px solid rgba(255,123,0,0.2); border-radius:10px; padding:12px; margin:8px 0;">
                        <div style="font-size:12px; font-weight:700; color:#ff7b00; margin-bottom:8px;">
                            🏆 You have <?= number_format($loyalty_balance) ?> Points (₹<?= points_to_rupees($conn, $loyalty_balance) ?> value)
                        </div>
                        <form method="POST" style="display:flex; gap:8px; align-items:center;">
                            <input type="number" name="redeem_points"
                                   min="<?= $min_redeem ?>" max="<?= $loyalty_balance ?>"
                                   step="<?= $min_redeem ?>"
                                   value="<?= min($loyalty_balance, $min_redeem * floor($loyalty_balance / $min_redeem)) ?>"
                                   style="width:90px; padding:7px 10px; background:#1a1a1a; border:1px solid rgba(255,123,0,0.3); border-radius:8px; color:#fff; font-size:13px; font-family:'Poppins',sans-serif;">
                            <button type="submit" name="apply_points"
                                    style="background:#ff7b00; color:#fff; border:none; padding:7px 14px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; white-space:nowrap; font-family:'Poppins',sans-serif;">
                                Apply Points
                            </button>
                        </form>
                        <div style="font-size:11px; color:#666; margin-top:6px;">Min <?= $min_redeem ?> pts = ₹<?= points_to_rupees($conn, $min_redeem) ?> off</div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <div class="summary-row">
                        <span>Total</span>
                        <span id="grandTotal">₹<?= $grand ?></span>
                    </div>
                </div>

                <!-- COD Form -->
                <form method="POST" id="codForm">
                    <input type="hidden" name="order_type"       id="orderTypeInput"    value="Dine In">
                    <input type="hidden" name="delivery_address" id="addressHidden"     value="">
                    <input type="hidden" name="delivery_charge"  id="deliveryChargeHidden" value="0">
                    <input type="hidden" name="customer_lat"     id="customerLat"       value="">
                    <input type="hidden" name="customer_lng"     id="customerLng"       value="">
                    <input type="hidden" name="loyalty_discount" value="<?= $loyalty_discount ?>">
                    <input type="hidden" name="promo_discount"   value="<?= $promo_discount ?>">
                    <button type="submit" name="place_order" class="btn-submit" id="codBtn">
                        <i class="fa fa-check-circle"></i> Place Order &nbsp;—&nbsp; <span id="btnTotal">₹<?= $grand ?></span>
                    </button>
                </form>

                <!-- Razorpay Button -->
                <button class="btn-razorpay" id="razorpayBtn" onclick="payWithRazorpay()">
                    <i class="fa fa-mobile-screen"></i> Pay Online &nbsp;—&nbsp; <span id="btnTotalRzp">₹<?= $grand ?></span>
                </button>
            </div>

        </div>
    </div>
</div>

<?php else: ?>
<div class="container empty-wrap">
    <div class="empty-cart">
        <span class="empty-icon">🛒</span>
        <h2>Your cart is empty!</h2>
        <p>Add some delicious items from our menu</p>
        <a href="menu.php" class="btn-menu"><i class="fa fa-utensils"></i> Browse Menu</a>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<!-- Floating Buttons -->
<a href="https://www.instagram.com/dropperscafe" class="instagram-float" target="_blank" title="Follow on Instagram">
    <i class="fab fa-instagram"></i>
</a>
<a href="https://wa.me/917004810081" class="whatsapp-float" target="_blank" title="Chat on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>
<a href="#" class="back-to-top" id="backToTop">
    <i class="fa fa-arrow-up"></i>
</a>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let loyaltyDiscount = <?= $loyalty_discount ?>;             // ✅ Loyalty discount
let promoDiscount   = <?= $promo_discount ?>;               // ✅ Promo code discount
let baseTotal      = <?= $grand ?>;   // subtotal + 5% GST - loyalty discount
let subtotalBase   = <?= $subtotal ?>;
let gstBase        = <?= $gst ?>;
let finalTotal     = <?= $grand ?>;
let orderType      = 'Dine In';
let payMethod      = 'COD';
let deliveryCharge = 0;

// ===== Cafe ki location (Bheldi Road, Amnour, Bihar) =====
const CAFE_LAT = 25.9330;
const CAFE_LNG = 84.9320;
const RATE_PER_KM = 9; // ₹9 per km

// Haversine formula — do coordinates ke beech distance (km)
function haversine(lat1, lon1, lat2, lon2){
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function calcDeliveryCharge(km){
    // Minimum ₹30, phir ₹9/km (rounded up)
    return Math.max(30, Math.ceil(km) * RATE_PER_KM);
}

// ===== LEAFLET MAP =====
let deliveryMap = null;
let deliveryMarker = null;

let cafeMarker = null;
let deliveryZone = null;
let distanceLine = null;
let distanceLabel = null;

function initMap(lat, lng){
    const mapEl = document.getElementById('deliveryMap');
    if(!mapEl) return;
    document.getElementById('mapContainer').style.display = 'block';

    if(deliveryMap){
        deliveryMap.setView([lat, lng], 14);
        if(deliveryMarker) deliveryMarker.setLatLng([lat, lng]);
        updateDistanceLine(lat, lng);
        return;
    }

    // ✅ Dark tile layer (CartoDB)
    deliveryMap = L.map('deliveryMap', { zoomControl:false, attributionControl:false }).setView([lat, lng], 13);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 19
    }).addTo(deliveryMap);

    // ✅ Custom zoom control — top right
    L.control.zoom({ position: 'topright' }).addTo(deliveryMap);

    // ✅ 9km delivery zone circle
    deliveryZone = L.circle([CAFE_LAT, CAFE_LNG], {
        radius: 9000,
        color: '#ff7b00',
        weight: 2,
        opacity: 0.7,
        fillColor: '#ff7b00',
        fillOpacity: 0.06,
        dashArray: '6 6'
    }).addTo(deliveryMap);

    // ✅ Café marker
    const cafeIcon = L.divIcon({
        html: `<div style="
            background:linear-gradient(135deg,#ff7b00,#ffb347);
            border:3px solid #fff;
            border-radius:50%;
            width:36px;height:36px;
            display:flex;align-items:center;justify-content:center;
            font-size:18px;
            box-shadow:0 0 0 6px rgba(255,123,0,0.2),0 4px 14px rgba(255,123,0,0.5);
        ">☕</div>`,
        iconSize:[36,36], iconAnchor:[18,18], className:''
    });
    cafeMarker = L.marker([CAFE_LAT, CAFE_LNG], { icon: cafeIcon, title: 'Droppers Café' })
        .addTo(deliveryMap)
        .bindTooltip('<b>☕ Droppers Café</b><br><span style="font-size:11px;color:#aaa;">Your order ships from here</span>', {
            permanent: false, direction: 'top', className: 'cafe-tooltip'
        });

    // ✅ Customer pin icon
    const pinIcon = L.divIcon({
        html: `<div style="
            width:38px;height:38px;border-radius:50% 50% 50% 0;
            background:linear-gradient(135deg,#528ff5,#7fb3ff);
            border:3px solid #fff;
            box-shadow:0 3px 14px rgba(82,143,245,0.6);
            transform:rotate(-45deg);
            display:flex;align-items:center;justify-content:center;
        "><span style="transform:rotate(45deg);font-size:15px;">🏠</span></div>`,
        iconSize:[38,38], iconAnchor:[19,38], className:''
    });

    deliveryMarker = L.marker([lat, lng], { icon: pinIcon, draggable: true }).addTo(deliveryMap)
        .bindTooltip('Your delivery location<br><small>Drag to adjust</small>', {
            direction: 'top', className: 'cafe-tooltip'
        });

    // ✅ Distance line
    distanceLine = L.polyline([[CAFE_LAT, CAFE_LNG], [lat, lng]], {
        color: '#ff7b00', weight: 2, opacity: 0.5, dashArray: '5 8'
    }).addTo(deliveryMap);

    updateDistanceLine(lat, lng);

    // ✅ Fit map to show both markers
    const bounds = L.latLngBounds([[CAFE_LAT, CAFE_LNG], [lat, lng]]);
    deliveryMap.fitBounds(bounds, { padding: [40, 40] });

    // On drag
    deliveryMarker.on('dragend', function(){
        const pos = deliveryMarker.getLatLng();
        reverseGeocodeAndFill(pos.lat, pos.lng);
        updateDeliveryCharge(pos.lat, pos.lng);
        updateDistanceLine(pos.lat, pos.lng);
    });

    // On map click
    deliveryMap.on('click', function(e){
        deliveryMarker.setLatLng(e.latlng);
        reverseGeocodeAndFill(e.latlng.lat, e.latlng.lng);
        updateDeliveryCharge(e.latlng.lat, e.latlng.lng);
        updateDistanceLine(e.latlng.lat, e.latlng.lng);
    });
}

// ✅ Update distance line + midpoint label
function updateDistanceLine(lat, lng){
    if(!distanceLine) return;
    distanceLine.setLatLngs([[CAFE_LAT, CAFE_LNG], [lat, lng]]);
    const km = haversine(CAFE_LAT, CAFE_LNG, lat, lng);
    const within = km <= MAX_DELIVERY_KM;
    distanceLine.setStyle({
        color: within ? '#ff7b00' : '#e74c3c',
        opacity: 0.6
    });
    // Update zone circle color
    if(deliveryZone) deliveryZone.setStyle({
        color: within ? '#ff7b00' : '#e74c3c',
        fillColor: within ? '#ff7b00' : '#e74c3c'
    });
}

const MAX_DELIVERY_KM = 9; // ✅ Maximum delivery distance

function updateDeliveryCharge(lat, lng){
    const km = haversine(CAFE_LAT, CAFE_LNG, lat, lng);
    const distInfoEl = document.getElementById('distInfo');
    const codBtn     = document.getElementById('codBtn');
    const rzpBtn     = document.getElementById('razorpayBtn');

    // ✅ 9 km se zyada — delivery not available
    if(km > MAX_DELIVERY_KM){
        distInfoEl.style.display      = 'flex';
        distInfoEl.style.background   = 'rgba(231,76,60,0.08)';
        distInfoEl.style.borderColor  = 'rgba(231,76,60,0.4)';
        distInfoEl.style.color        = '#e74c3c';
        document.getElementById('distText').innerText =
            `⚠️ Distance: ${km.toFixed(1)} km — Sorry! We only deliver within ${MAX_DELIVERY_KM} km of the café.`;
        document.getElementById('distLabel').innerText = `(${km.toFixed(1)} km — Out of range)`;
        // Buttons disable karo
        codBtn.disabled = true; codBtn.style.opacity = '0.4'; codBtn.style.cursor = 'not-allowed';
        rzpBtn.disabled = true; rzpBtn.style.opacity = '0.4'; rzpBtn.style.cursor = 'not-allowed';
        // Delivery charge reset
        document.getElementById('deliveryAmt').innerText = '—';
        document.getElementById('deliveryChargeHidden').value = 0;
        document.getElementById('customerLat').value = '';
        document.getElementById('customerLng').value = '';
        return;
    }

    // ✅ Within range — reset styles & enable buttons
    distInfoEl.style.background  = '';
    distInfoEl.style.borderColor = '';
    distInfoEl.style.color       = '';
    codBtn.disabled = false; codBtn.style.opacity = '1'; codBtn.style.cursor = 'pointer';
    rzpBtn.disabled = false; rzpBtn.style.opacity = '1'; rzpBtn.style.cursor = 'pointer';

    const charge = calcDeliveryCharge(km);
    deliveryCharge = charge;
    finalTotal = baseTotal + deliveryCharge - loyaltyDiscount;
    distInfoEl.style.display = 'flex';
    document.getElementById('distText').innerText = `Distance: ${km.toFixed(1)} km  ·  Delivery Charge: ₹${charge}`;
    document.getElementById('distLabel').innerText = `(${km.toFixed(1)} km)`;
    document.getElementById('deliveryAmt').innerText = '₹' + charge;
    document.getElementById('deliveryChargeHidden').value = charge;
    updateTotals();
    // Store coords for sending to admin
    document.getElementById('customerLat').value = lat.toFixed(7);
    document.getElementById('customerLng').value = lng.toFixed(7);
}

function reverseGeocodeAndFill(lat, lng){
    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
    .then(r => r.json())
    .then(data => {
        const addr = data.address || {};

        const house    = addr.house_number || addr.building || '';
        const road     = addr.road || addr.neighbourhood || addr.suburb || addr.quarter || '';
        const landmark = addr.amenity || addr.shop || addr.tourism ||
                         addr.leisure || addr.historic || addr.office ||
                         addr.neighbourhood || addr.hamlet || '';
        const city     = addr.city || addr.town || addr.village || addr.county || addr.state_district || '';
        const pin      = addr.postcode || '';

        function fill(id, val){
            if(!val) return;
            const el = document.getElementById(id);
            if(el){ el.value = val; toggleFilled(el); }
        }
        fill('addrHouse',    house);
        fill('addrStreet',   road);
        fill('addrLandmark', landmark);
        fill('addrCity',     city);

        if(pin){
            const pinEl = document.getElementById('addrPin');
            pinEl.value = pin;
            toggleFilled(pinEl);
            validatePin(pinEl);
        }
        buildAddress();

        // Show pin confirmed badge
        const ps = document.getElementById('mapPinStatus');
        ps.style.display = 'flex';
        document.getElementById('mapPinText').textContent =
            `📍 Pin set at: ${lat.toFixed(4)}, ${lng.toFixed(4)} — fields updated`;
    }).catch(() => {
        document.getElementById('addrStreet').value = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        toggleFilled(document.getElementById('addrStreet'));
        buildAddress();
    });
}

function getLocation(){
    const btn = document.getElementById('liveLocBtn');
    if(!navigator.geolocation){
        alert('Aapka browser geolocation support nahi karta!');
        return;
    }
    btn.classList.add('loading');
    document.getElementById('liveLocText').textContent = 'Getting location...';

    navigator.geolocation.getCurrentPosition(
        function(pos){
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            // Init / move map to GPS location
            initMap(lat, lng);

            // Reverse geocode and fill all fields
            reverseGeocodeAndFill(lat, lng);

            // Update delivery charge based on GPS position
            updateDeliveryCharge(lat, lng);

            const btn = document.getElementById('liveLocBtn');
            btn.classList.remove('loading');
            btn.classList.add('detected');
            document.getElementById('liveLocText').textContent = '✓ Location Detected';
        },
        function(err){
            const btn = document.getElementById('liveLocBtn');
            btn.classList.remove('loading');
            document.getElementById('liveLocText').textContent = '📍 Use My Live Location';

            // Even if GPS denied, show map centered on café so user can pan & pick
            alert('GPS denied. Aap map pe pin drag karke apni location set kar sakte hain!');
            initMap(CAFE_LAT, CAFE_LNG);
        }
    );
}

// +/- qty buttons
function changeQty(inputId, delta){
    const inp = document.getElementById(inputId);
    let val = Math.max(1, parseInt(inp.value) + delta);
    inp.value = val;
}

// Build combined address from structured fields
function buildAddress(){
    const house    = document.getElementById('addrHouse')?.value.trim()    || '';
    const floor    = document.getElementById('addrFloor')?.value.trim()    || '';
    const street   = document.getElementById('addrStreet')?.value.trim()   || '';
    const landmark = document.getElementById('addrLandmark')?.value.trim() || '';
    const city     = document.getElementById('addrCity')?.value.trim()     || '';
    const pin      = document.getElementById('addrPin')?.value.trim()      || '';

    const parts = [house, floor, street, landmark ? 'Near ' + landmark : '', city, pin].filter(Boolean);
    document.getElementById('addressInput').value = parts.join(', ');
}

// filled class toggle
function toggleFilled(el){ el.classList.toggle('filled', el.value.trim().length > 0); }

// Pincode validation (6 digits)
function validatePin(el){
    const val = el.value.trim();
    const st  = document.getElementById('pinStatus');
    if(val.length === 0){ st.className = 'pin-status'; return; }
    if(/^\d{6}$/.test(val)){ st.className = 'pin-status valid';   st.textContent = '✓'; }
    else                   { st.className = 'pin-status invalid'; st.textContent = '✗'; }
}

function selectType(el, type){
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    orderType = type;
    document.getElementById('orderTypeInput').value = type;
    const addr = document.getElementById('addressField');
    const row  = document.getElementById('deliveryRow');
    if(type === 'Home Delivery'){
        addr.style.display = 'block';
        row.style.display  = 'flex';
        // Default ₹30 jab tak distance calculate na ho
        if(deliveryCharge === 0) deliveryCharge = 30;
        document.getElementById('deliveryAmt').innerText = '₹' + deliveryCharge;
        document.getElementById('deliveryChargeHidden').value = deliveryCharge;
        finalTotal = baseTotal + deliveryCharge - loyaltyDiscount;
        // Show map centred on café if not yet initialised
        setTimeout(() => {
            if(!deliveryMap) initMap(CAFE_LAT, CAFE_LNG);
            else deliveryMap.invalidateSize();
        }, 100);
    } else {
        addr.style.display = 'none';
        row.style.display  = 'none';
        deliveryCharge = 0;
        document.getElementById('deliveryChargeHidden').value = 0;
        finalTotal = baseTotal - loyaltyDiscount;
    }
    updateTotals();
}

function selectPayment(el, method){
    document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    payMethod = method;
    const codBtn = document.getElementById('codBtn');
    const rzpBtn = document.getElementById('razorpayBtn');
    if(method === 'COD'){
        codBtn.style.display = 'flex';
        rzpBtn.style.display = 'none';
    } else {
        codBtn.style.display = 'none';
        rzpBtn.style.display = 'flex';
    }
}

function updateTotals(){
    document.getElementById('grandTotal').innerText  = '₹' + finalTotal;
    document.getElementById('btnTotal').innerText    = '₹' + finalTotal;
    document.getElementById('btnTotalRzp').innerText = '₹' + finalTotal;
}

// ✅ Address fields basic validation
function validateAddressFields(){
    const street = document.getElementById('addrStreet')?.value.trim() || '';
    const city   = document.getElementById('addrCity')?.value.trim()   || '';
    const pin    = document.getElementById('addrPin')?.value.trim()    || '';
    if(!street){ document.getElementById('addrStreet').classList.add('error'); document.getElementById('addrStreet').focus(); alert('Please enter your street / area!'); return false; }
    if(!city)  { document.getElementById('addrCity').classList.add('error');   document.getElementById('addrCity').focus();   alert('Please enter your city!'); return false; }
    if(!/^\d{6}$/.test(pin)){ document.getElementById('addrPin').classList.add('error'); document.getElementById('addrPin').focus(); alert('Please enter a valid 6-digit pincode!'); return false; }
    return true;
}

// ✅ Distance verify — GPS ho ya manual address, dono check karo
async function verifyDistanceAndProceed(proceedCallback){
    const lat = document.getElementById('customerLat').value.trim();
    const lng = document.getElementById('customerLng').value.trim();
    const distInfoEl = document.getElementById('distInfo');

    // Case 1: Coordinates already hain (GPS/map used) — seedha check karo
    if(lat && lng){
        const km = haversine(CAFE_LAT, CAFE_LNG, parseFloat(lat), parseFloat(lng));
        if(km > MAX_DELIVERY_KM){
            alert(`Sorry! We only deliver within ${MAX_DELIVERY_KM} km of the café.\nYour location is ${km.toFixed(1)} km away.`);
            return;
        }
        proceedCallback();
        return;
    }

    // Case 2: Manual address — Nominatim se geocode karo
    const street = document.getElementById('addrStreet')?.value.trim() || '';
    const city   = document.getElementById('addrCity')?.value.trim()   || '';
    const pin    = document.getElementById('addrPin')?.value.trim()    || '';
    const query  = encodeURIComponent(`${street}, ${city}, ${pin}, India`);

    distInfoEl.style.display    = 'flex';
    distInfoEl.style.background = 'rgba(82,143,245,0.07)';
    distInfoEl.style.borderColor= 'rgba(82,143,245,0.3)';
    distInfoEl.style.color      = '#6fa3f7';
    document.getElementById('distText').innerText = '🔍 Verifying your address location...';

    try {
        const res  = await fetch(`https://nominatim.openstreetmap.org/search?q=${query}&format=json&limit=1`);
        const data = await res.json();

        if(!data || data.length === 0){
            // Geocode fail — map use karne ko kaho
            distInfoEl.style.background  = 'rgba(231,76,60,0.08)';
            distInfoEl.style.borderColor = 'rgba(231,76,60,0.4)';
            distInfoEl.style.color       = '#e74c3c';
            document.getElementById('distText').innerText = '⚠️ Could not verify address. Please use "Use My Live Location" or drag the map pin.';
            alert('We could not verify your address location.\nPlease use the "📍 Use My Live Location" button or drag the map pin to your exact location.');
            return;
        }

        const gLat = parseFloat(data[0].lat);
        const gLng = parseFloat(data[0].lon);
        const km   = haversine(CAFE_LAT, CAFE_LNG, gLat, gLng);

        // Coordinates set karo aur map update karo
        document.getElementById('customerLat').value = gLat.toFixed(7);
        document.getElementById('customerLng').value = gLng.toFixed(7);
        if(typeof initMap === 'function') initMap(gLat, gLng);
        updateDeliveryCharge(gLat, gLng); // this will block if > 9km

        if(km > MAX_DELIVERY_KM){
            alert(`Sorry! We only deliver within ${MAX_DELIVERY_KM} km of the café.\nYour address is approximately ${km.toFixed(1)} km away.`);
            return;
        }
        proceedCallback();

    } catch(err){
        distInfoEl.style.color = '#e74c3c';
        distInfoEl.style.background  = 'rgba(231,76,60,0.08)';
        distInfoEl.style.borderColor = 'rgba(231,76,60,0.4)';
        document.getElementById('distText').innerText = '⚠️ Address verification failed. Please use live location.';
        alert('Address verification failed.\nPlease use the "📍 Use My Live Location" button to confirm your location.');
    }
}

// ✅ COD Form Submit
document.getElementById('codForm').addEventListener('submit', function(e){
    if(orderType === 'Home Delivery'){
        e.preventDefault(); // hamesha rokho pehle
        if(!validateAddressFields()) return;
        buildAddress();
        document.getElementById('addressHidden').value = document.getElementById('addressInput').value;
        const form = this;
        verifyDistanceAndProceed(function(){ form.submit(); });
        return;
    }
    document.getElementById('addressHidden').value = document.getElementById('addressInput').value;
});

function payWithRazorpay(){
    if(orderType === 'Home Delivery'){
        const street = document.getElementById('addrStreet')?.value.trim() || '';
        const city   = document.getElementById('addrCity')?.value.trim()   || '';
        const pin    = document.getElementById('addrPin')?.value.trim()    || '';
        if(!street || !city || !/^\d{6}$/.test(pin)){
            alert('Please fill Street, City and valid Pincode before paying!');
            return;
        }
        buildAddress();
        verifyDistanceAndProceed(function(){ _doRazorpayPayment(); });
        return;
    }
    _doRazorpayPayment();
}

function _doRazorpayPayment(){
    const address = document.getElementById('addressInput').value;
    var options = {
        key: 'YOUR_RAZORPAY_KEY',
        amount: finalTotal * 100,
        currency: 'INR',
        name: 'Droppers Café',
        description: 'Food Order Payment',
        image: '' + window._BASE_URL + '/assets/images/droppers-logo.png',
        handler: function(response){
            var form = document.createElement('form');
            form.method = 'POST'; form.action = 'process_payment.php';
            var fields = {
                'razorpay_payment_id': response.razorpay_payment_id,
                'order_type': orderType,
                'delivery_address': address,
                'delivery_charge': deliveryCharge,
                'total_amount': finalTotal,
                'customer_lat': document.getElementById('customerLat').value,
                'customer_lng': document.getElementById('customerLng').value
            };
            for(var key in fields){
                var input = document.createElement('input');
                input.type='hidden'; input.name=key; input.value=fields[key];
                form.appendChild(input);
            }
            document.body.appendChild(form); form.submit();
        },
        prefill: {
            name:    '<?= htmlspecialchars($_SESSION['customer_name'] ?? '') ?>',
            contact: '<?= $_SESSION['customer_phone'] ?? '' ?>'
        },
        theme: { color: '#ff7b00' }
    };
    new Razorpay(options).open();
}

// Back to top
const btt = document.getElementById('backToTop');
window.addEventListener('scroll', () => {
    btt.classList.toggle('show', window.scrollY > 300);
});
</script>

<?php
$_promo_anim_on = (function_exists('module_feature') ? module_feature('PROMO_ANIMATION') : true);
if($_promo_anim_on && $promo_success_popup): ?>
<!-- ✅ PROMO SUCCESS POPUP -->
<div id="promoPopup" style="
    position:fixed; inset:0; z-index:99999;
    display:flex; align-items:center; justify-content:center;
    background:rgba(0,0,0,0.7); backdrop-filter:blur(6px);
    animation:fadeInBg 0.3s ease;
">
    <div style="
        background:linear-gradient(135deg,#1a1a1a,#111);
        border:1px solid rgba(255,123,0,0.4);
        border-radius:24px; padding:40px 48px;
        text-align:center; max-width:360px; width:90%;
        animation:popIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
        box-shadow:0 0 60px rgba(255,123,0,0.25);
        position:relative;
    ">
        <!-- Confetti circles -->
        <div class="confetti-wrap" id="confettiWrap"></div>

        <div style="font-size:64px; margin-bottom:12px; animation:bounce 0.6s ease 0.3s both;">🎉</div>
        <h2 style="color:#ff7b00; font-size:22px; font-weight:800; margin-bottom:8px;">Promo Applied!</h2>
        <p style="color:#aaa; font-size:14px; margin-bottom:6px;">
            Code <strong style="color:#fff; letter-spacing:2px;">
            <?= htmlspecialchars($applied_promo['code'] ?? '') ?></strong> applied successfully!
        </p>
        <p style="color:#27ae60; font-size:20px; font-weight:800; margin:14px 0;">
            🎁 You saved ₹<?= number_format($promo_discount, 2) ?>!
        </p>
        <div style="font-size:13px; color:#555;"><?= htmlspecialchars($applied_promo['title'] ?? '') ?></div>
        <button onclick="closePromoPopup()" style="
            margin-top:24px; background:linear-gradient(135deg,#ff7b00,#ff9500);
            color:#fff; border:none; padding:12px 36px;
            border-radius:50px; font-size:15px; font-weight:700;
            cursor:pointer; font-family:'Poppins',sans-serif;
            box-shadow:0 8px 24px rgba(255,123,0,0.35);
            transition:transform 0.2s;
        " onmouseover="this.style.transform='scale(1.05)'"
           onmouseout="this.style.transform='scale(1)'">
            🛒 Continue Shopping
        </button>
    </div>
</div>

<style>
@keyframes fadeInBg { from{opacity:0} to{opacity:1} }
@keyframes popIn {
    from{transform:scale(0.5) translateY(40px); opacity:0}
    to  {transform:scale(1)   translateY(0);    opacity:1}
}
@keyframes bounce {
    0%,100%{transform:translateY(0)}
    40%    {transform:translateY(-20px)}
    60%    {transform:translateY(-10px)}
}
@keyframes confettiFly {
    0%  {transform:translateY(0) rotate(0deg);   opacity:1}
    100%{transform:translateY(-120px) rotate(720deg); opacity:0}
}
.confetti-wrap { position:absolute; inset:0; pointer-events:none; overflow:hidden; border-radius:24px; }
.confetti-dot  { position:absolute; width:10px; height:10px; border-radius:50%; animation:confettiFly 1s ease forwards; }
</style>

<script>
// Confetti generate karo
(function(){
    var colors = ['#ff7b00','#ff9500','#27ae60','#3498db','#e74c3c','#f0a500','#fff'];
    var wrap = document.getElementById('confettiWrap');
    for(var i=0; i<22; i++){
        var dot = document.createElement('div');
        dot.className = 'confetti-dot';
        dot.style.cssText = [
            'left:'  + Math.random()*100 + '%',
            'top:'   + (60 + Math.random()*40) + '%',
            'background:' + colors[Math.floor(Math.random()*colors.length)],
            'animation-delay:' + (Math.random()*0.4) + 's',
            'animation-duration:' + (0.7 + Math.random()*0.6) + 's',
            'width:'  + (6 + Math.random()*8) + 'px',
            'height:' + (6 + Math.random()*8) + 'px',
        ].join(';');
        wrap.appendChild(dot);
    }
})();

function closePromoPopup(){
    var popup = document.getElementById('promoPopup');
    popup.style.animation = 'fadeInBg 0.25s ease reverse forwards';
    setTimeout(function(){ popup.remove(); }, 250);
}

// 4 second baad auto close
setTimeout(closePromoPopup, 4000);
</script>
<?php endif; ?>

</body>
</html>