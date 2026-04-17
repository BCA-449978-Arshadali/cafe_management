<?php
require_once 'includes/auth-check.php';
include '../config.php';

$order_id = intval($_GET['id'] ?? 0);
if(!$order_id){ header("Location: profile.php"); exit; }

// Fetch order — verify it belongs to this customer
$phone = $_SESSION['customer_phone'] ?? '';
$cname = $_SESSION['customer_name']  ?? '';
$stmt  = $conn->prepare("SELECT * FROM orders WHERE id=? AND (customer_phone=? OR customer_name=?)");
$stmt->bind_param("iss", $order_id, $phone, $cname);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if(!$order){ header("Location: profile.php"); exit; }

// ── Parse items string ─────────────────────────────────────────────────────
// Format: "Item Name x2, Combo Name (A + B + C) x1, ..."
$items_str = $order['items'];
$added     = 0;
$skipped   = 0;

// Split by ", " but NOT inside parentheses
$parts = preg_split('/,\s*(?![^(]*\))/', $items_str);

foreach($parts as $part){
    $part = trim($part);
    if(empty($part)) continue;

    // Extract qty — everything after last " x" followed by digits
    if(!preg_match('/^(.*?)\s+x(\d+)$/i', $part, $m)) continue;
    $raw_name = trim($m[1]);
    $qty      = max(1, intval($m[2]));

    // Strip combo contents in parentheses to get clean combo name
    $clean_name = preg_replace('/\s*\(.*?\)\s*/', '', $raw_name);
    $clean_name = trim($clean_name);

    // ── Try combos first ──────────────────────────────────────────────────
    $cs = $conn->prepare(
        "SELECT id, combo_name, combo_price FROM combos
         WHERE combo_name = ? AND is_active=1 AND is_available=1 LIMIT 1"
    );
    $cs->bind_param("s", $clean_name);
    $cs->execute();
    $combo = $cs->get_result()->fetch_assoc();

    if($combo){
        $disc_price = round($combo['combo_price'] * 0.95, 2);
        $key = 'combo_' . $combo['id'];
        if(isset($_SESSION['cart'][$key])){
            $_SESSION['cart'][$key]['qty'] += $qty;
        } else {
            $_SESSION['cart'][$key] = [
                'name'  => $combo['combo_name'],
                'price' => $disc_price,
                'qty'   => $qty
            ];
        }
        $added++;
        continue;
    }

    // ── Try regular menu items ────────────────────────────────────────────
    // Also try with size suffix stripped (e.g. "Burger S" → "Burger")
    $ms = $conn->prepare(
        "SELECT id, item_name, price, small_price FROM menu
         WHERE item_name = ? AND is_active=1 AND is_available=1 LIMIT 1"
    );
    $ms->bind_param("s", $clean_name);
    $ms->execute();
    $menu_item = $ms->get_result()->fetch_assoc();

    // Fallback: LIKE match (handles minor name differences)
    if(!$menu_item){
        $like = '%' . $clean_name . '%';
        $ms2  = $conn->prepare(
            "SELECT id, item_name, price, small_price FROM menu
             WHERE item_name LIKE ? AND is_active=1 AND is_available=1 LIMIT 1"
        );
        $ms2->bind_param("s", $like);
        $ms2->execute();
        $menu_item = $ms2->get_result()->fetch_assoc();
    }

    if($menu_item){
        $base_price = $menu_item['small_price'] ? floatval($menu_item['small_price']) : floatval($menu_item['price']);
        $disc_price = round($base_price * 0.95, 2);
        $id = $menu_item['id'];
        if(isset($_SESSION['cart'][$id])){
            $_SESSION['cart'][$id]['qty'] += $qty;
        } else {
            $_SESSION['cart'][$id] = [
                'name'  => $menu_item['item_name'],
                'price' => $disc_price,
                'qty'   => $qty
            ];
        }
        $added++;
    } else {
        $skipped++;
    }
}

// Store flash message for cart page
$_SESSION['reorder_msg'] = [
    'added'   => $added,
    'skipped' => $skipped,
    'order_id'=> $order_id
];

header("Location: cart.php");
exit;