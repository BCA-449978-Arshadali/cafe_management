<?php
// =============================================
//  loyalty_helper.php
//  Rakho: cafe_management/includes/loyalty_helper.php
//  Ye file admin aur customer dono use karti hai
// =============================================

/**
 * Setting value fetch karo
 */
function loyalty_setting($conn, $key, $default = '0') {
    $k   = mysqli_real_escape_string($conn, $key);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT setting_value FROM loyalty_settings WHERE setting_key='$k' LIMIT 1"));
    return $row ? $row['setting_value'] : $default;
}

/**
 * Customer ka current balance fetch karo
 */
function get_loyalty_balance($conn, $phone) {
    $p   = mysqli_real_escape_string($conn, $phone);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT total_points, redeemed_points FROM loyalty_points WHERE customer_phone='$p' LIMIT 1"));
    if (!$row) return 0;
    return max(0, intval($row['total_points']) - intval($row['redeemed_points']));
}

/**
 * Order complete hone par points earn karo
 * Call karo jab order status = Completed
 */
function loyalty_earn_points($conn, $order_id, $phone, $name, $amount) {
    if (loyalty_setting($conn, 'points_enabled') != '1') return 0;

    $rate   = max(1, intval(loyalty_setting($conn, 'points_per_10rs', '1')));
    $points = intval(floor($amount / 10) * $rate);
    if ($points <= 0) return 0;

    $p = mysqli_real_escape_string($conn, $phone);
    $n = mysqli_real_escape_string($conn, $name);

    // Upsert loyalty_points
    mysqli_query($conn, "INSERT INTO loyalty_points (customer_phone, customer_name, total_points)
        VALUES ('$p', '$n', $points)
        ON DUPLICATE KEY UPDATE
            customer_name  = IF(customer_name IS NULL OR customer_name='', '$n', customer_name),
            total_points   = total_points + $points,
            updated_at     = NOW()
    ");

    // Transaction record
    $desc = mysqli_real_escape_string($conn, "Earned on Order #$order_id (₹" . number_format($amount, 2) . ")");
    mysqli_query($conn, "INSERT INTO loyalty_transactions (customer_phone, order_id, type, points, description)
        VALUES ('$p', $order_id, 'earn', $points, '$desc')");

    return $points;
}

/**
 * Points redeem karo (checkout pe)
 * Returns: ['success'=>bool, 'discount'=>float, 'message'=>string]
 */
function loyalty_redeem_points($conn, $phone, $points_to_redeem) {
    if (loyalty_setting($conn, 'points_enabled') != '1')
        return ['success' => false, 'discount' => 0, 'message' => 'Loyalty system is disabled.'];

    $min = intval(loyalty_setting($conn, 'min_redeem_points', '100'));
    if ($points_to_redeem < $min)
        return ['success' => false, 'discount' => 0, 'message' => "Minimum $min points needed to redeem."];

    $balance = get_loyalty_balance($conn, $phone);
    if ($points_to_redeem > $balance)
        return ['success' => false, 'discount' => 0, 'message' => "You only have $balance points available."];

    $rate     = intval(loyalty_setting($conn, 'redeem_rate', '100'));  // 100 pts = ₹10
    $discount = round(($points_to_redeem / $rate) * 10, 2);

    $p = mysqli_real_escape_string($conn, $phone);
    mysqli_query($conn, "UPDATE loyalty_points SET redeemed_points = redeemed_points + $points_to_redeem WHERE customer_phone='$p'");

    $desc = mysqli_real_escape_string($conn, "Redeemed $points_to_redeem points for ₹$discount discount");
    mysqli_query($conn, "INSERT INTO loyalty_transactions (customer_phone, type, points, description)
        VALUES ('$p', 'redeem', $points_to_redeem, '$desc')");

    return ['success' => true, 'discount' => $discount, 'message' => "₹$discount discount applied!"];
}

/**
 * Admin: Bonus points manually add karo
 */
function loyalty_add_bonus($conn, $phone, $name, $points, $reason = 'Admin bonus') {
    $p    = mysqli_real_escape_string($conn, $phone);
    $n    = mysqli_real_escape_string($conn, $name);
    $desc = mysqli_real_escape_string($conn, $reason);

    mysqli_query($conn, "INSERT INTO loyalty_points (customer_phone, customer_name, total_points)
        VALUES ('$p', '$n', $points)
        ON DUPLICATE KEY UPDATE
            total_points = total_points + $points,
            updated_at   = NOW()
    ");
    mysqli_query($conn, "INSERT INTO loyalty_transactions (customer_phone, type, points, description)
        VALUES ('$p', 'bonus', $points, '$desc')");
}

/**
 * Points ka rupee value calculate karo
 */
function points_to_rupees($conn, $points) {
    $rate = intval(loyalty_setting($conn, 'redeem_rate', '100'));
    return round(($points / $rate) * 10, 2);
}