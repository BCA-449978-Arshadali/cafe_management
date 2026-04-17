<?php
ob_start();
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ── AJAX Add to Cart — no page reload ────────────────────────────────────────
if (isset($_POST['add_to_cart']) && isset($_POST['ajax'])) {
    include "../config.php";
    $raw_id = trim($_POST['id'] ?? '');
    $name   = $_POST['name'] ?? '';
    $price  = floatval($_POST['price'] ?? 0);
    $added  = false;

    if (strpos($raw_id, 'combo_') === 0) {
        $combo_id = intval(substr($raw_id, 6));
        $chk = mysqli_fetch_assoc(
            mysqli_query($conn, "SELECT is_available FROM combos WHERE id=$combo_id AND is_active=1 LIMIT 1")
        );
        if ($chk && $chk['is_available'] == 1) {
            $key = 'combo_' . $combo_id;
            if (isset($_SESSION['cart'][$key])) $_SESSION['cart'][$key]['qty']++;
            else $_SESSION['cart'][$key] = ['name'=>$name,'price'=>$price,'qty'=>1];
            $added = true;
        }
    } else {
        $id  = intval($raw_id);
        $chk = mysqli_fetch_assoc(
            mysqli_query($conn, "SELECT is_available FROM menu WHERE id=$id LIMIT 1")
        );
        if ($chk && $chk['is_available'] == 1) {
            if (isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id]['qty']++;
            else $_SESSION['cart'][$id] = ['name'=>$name,'price'=>$price,'qty'=>1];
            $added = true;
        }
    }
    $cart_count = array_sum(array_column($_SESSION['cart'], 'qty'));
    header('Content-Type: application/json');
    echo json_encode(['success'=>$added, 'cart_count'=>$cart_count, 'name'=>$name]);
    exit;
}

// Add to cart — supports regular menu items AND combos (id = "combo_X")
if (isset($_POST['add_to_cart'])) {
    $raw_id = trim($_POST['id'] ?? '');
    $name   = $_POST['name'] ?? '';
    $price  = isset($_POST['price']) ? floatval($_POST['price']) : 0;

    include "../config.php";

    if (strpos($raw_id, 'combo_') === 0) {
        // ── Combo item ──────────────────────────────────────────────
        $combo_id = intval(substr($raw_id, 6));
        $chk = mysqli_fetch_assoc(
            mysqli_query($conn, "SELECT is_available FROM combos WHERE id=$combo_id AND is_active=1 LIMIT 1")
        );
        if ($chk && $chk['is_available'] == 1) {
            $key = 'combo_' . $combo_id;
            if (isset($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key]['qty']++;
            } else {
                $_SESSION['cart'][$key] = ['name' => $name, 'price' => $price, 'qty' => 1];
            }
        }
    } else {
        // ── Regular menu item ────────────────────────────────────────
        $id  = intval($raw_id);
        $chk = mysqli_fetch_assoc(
            mysqli_query($conn, "SELECT is_available FROM menu WHERE id=$id LIMIT 1")
        );
        if ($chk && $chk['is_available'] == 1) {
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['qty']++;
            } else {
                $_SESSION['cart'][$id] = ['name' => $name, 'price' => $price, 'qty' => 1];
            }
        }
    }
}

if(!isset($conn)) include "../config.php";

// Auto-add is_active column if missing
$_col = mysqli_query($conn, "SHOW COLUMNS FROM menu LIKE 'is_active'");
if(mysqli_num_rows($_col) == 0){
    mysqli_query($conn, "ALTER TABLE menu ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
}

$categories = ['Pizza','Burger','Sandwich','Shakes','Coffee','Cakes','Dessert',
               'Hot Dog','Muffin','Donut','Drinks','Rolls','Rice','Noodles',
               'Soup','Bread','Chinese','Momos'];

$category_icons = [
    'Pizza'    =>'🍕','Burger'   =>'🍔','Sandwich' =>'🥪',
    'Shakes'   =>'🥤','Coffee'   =>'☕','Cakes'    =>'🎂',
    'Dessert'  =>'🍰','Hot Dog'  =>'🌭','Muffin'   =>'🧁',
    'Donut'    =>'🍩','Drinks'   =>'🥤','Rolls'    =>'🌯',
    'Rice'     =>'🍚','Noodles'  =>'🍜','Soup'     =>'🥣',
    'Bread'    =>'🫓','Chinese'  =>'🍱','Momos'    =>'🥟'
];

// ── Combo tables — auto-create if not exist ──────────────────────────
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS combos (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        combo_name    VARCHAR(200)   NOT NULL,
        description   TEXT,
        combo_price   DECIMAL(10,2)  NOT NULL,
        image         VARCHAR(255)   DEFAULT '',
        is_available  TINYINT(1)     NOT NULL DEFAULT 1,
        is_active     TINYINT(1)     NOT NULL DEFAULT 1,
        created_at    TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS combo_items (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        combo_id   INT NOT NULL,
        menu_id    INT NOT NULL,
        FOREIGN KEY (combo_id) REFERENCES combos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Fetch active combos
$combos_result = mysqli_query($conn, "
    SELECT * FROM combos
    WHERE is_active = 1
    ORDER BY created_at DESC
");
$combos_list = [];
while ($cr = mysqli_fetch_assoc($combos_result)) $combos_list[] = $cr;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Droppers Café</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/customer.css">

<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
body { background:#0d0d0d; color:#fff; font-family:'Poppins',sans-serif; overflow-x:hidden; }
a { text-decoration:none; }
.container { max-width:1280px; margin:0 auto; padding:0 24px; }

/* ===== PAGE HERO ===== */
.page-hero {
    background: linear-gradient(135deg, #0a0a0a 0%, #0f1f0f 50%, #0a0a0a 100%);
    padding: 80px 0 50px;
    position:relative; overflow:hidden;
    border-bottom:1px solid rgba(255,123,0,0.1);
}
.page-hero::before {
    content:''; position:absolute; inset:0;
    background: radial-gradient(ellipse at 20% 50%, rgba(255,123,0,0.07) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(255,200,0,0.04) 0%, transparent 50%);
}
.page-hero .inner { position:relative; z-index:2; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:24px; }
.page-hero-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.3);
    padding:6px 16px; border-radius:30px;
    font-size:13px; color:#ff7b00; font-weight:600; margin-bottom:16px;
}
.page-hero h1 { font-size:clamp(32px,4vw,52px); font-weight:800; color:#fff; margin-bottom:10px; }
.page-hero h1 span { color:#ff7b00; }
.page-hero p { color:#888; font-size:15px; line-height:1.7; max-width:460px; }

/* Search Bar */
.search-wrap { flex-shrink:0; }
.search-box {
    display:flex; align-items:center; gap:12px;
    background:#1a1a1a; border:1px solid rgba(255,255,255,0.08);
    border-radius:14px; padding:12px 18px;
    width:300px; transition:0.2s;
}
.search-box:focus-within { border-color:rgba(255,123,0,0.5); }
.search-box i { color:#555; font-size:15px; }
.search-box input {
    background:none; border:none; outline:none;
    color:#fff; font-size:14px; font-family:'Poppins',sans-serif;
    width:100%;
}
.search-box input::placeholder { color:#444; }

/* ===== STICKY CATEGORY BAR ===== */
.cat-bar-wrap {
    position:sticky; top:70px; z-index:100;
    background:rgba(10,10,10,0.96);
    backdrop-filter:blur(20px);
    -webkit-backdrop-filter:blur(20px);
    border-bottom:1px solid rgba(255,255,255,0.05);
    padding:10px 0 8px;
    box-shadow:0 8px 32px rgba(0,0,0,0.6);
}
/* Fade edges for scroll hint */
.cat-bar-wrap::before,
.cat-bar-wrap::after {
    content:''; position:absolute; top:0; bottom:0; width:40px; z-index:2; pointer-events:none;
}
.cat-bar-wrap::before { left:0;  background:linear-gradient(to right, rgba(10,10,10,0.95), transparent); }
.cat-bar-wrap::after  { right:0; background:linear-gradient(to left,  rgba(10,10,10,0.95), transparent); }

.cat-bar {
    display:flex; gap:6px; overflow-x:auto;
    padding:4px 40px 4px; scrollbar-width:none;
    cursor:grab; user-select:none; scroll-behavior:smooth;
}
.cat-bar:active { cursor:grabbing; }
.cat-bar::-webkit-scrollbar { display:none; }

.cat-pill {
    display:inline-flex; align-items:center; gap:5px;
    padding:7px 14px; border-radius:30px; white-space:nowrap;
    font-size:12px; font-weight:600; cursor:pointer; flex-shrink:0;
    border:1.5px solid rgba(255,255,255,0.07);
    background:rgba(255,255,255,0.03); color:#555;
    transition:all 0.22s ease; letter-spacing:0.2px;
}
.cat-pill:hover {
    color:#ddd; border-color:rgba(255,123,0,0.35);
    background:rgba(255,123,0,0.05);
    transform:translateY(-1px);
}
.cat-pill.active {
    background:rgba(255,123,0,0.14); color:#ff7b00;
    border-color:rgba(255,123,0,0.6);
    box-shadow:0 0 12px rgba(255,123,0,0.2);
}

/* ── Combos pill — special gold highlight ── */
.cat-pill.combo-pill {
    background:linear-gradient(135deg,rgba(212,160,23,0.15),rgba(184,134,11,0.08));
    border-color:rgba(212,160,23,0.5);
    color:#d4a017;
    box-shadow:0 0 10px rgba(212,160,23,0.15);
    font-weight:700;
}
.cat-pill.combo-pill:hover {
    background:linear-gradient(135deg,rgba(212,160,23,0.25),rgba(184,134,11,0.15));
    border-color:#d4a017;
    color:#f0bc1e;
    box-shadow:0 0 18px rgba(212,160,23,0.3);
    transform:translateY(-2px);
}
.cat-pill.combo-pill.active {
    background:linear-gradient(135deg,#d4a017,#b8860b);
    color:#000; border-color:#d4a017;
    box-shadow:0 4px 20px rgba(212,160,23,0.45);
}
/* Pulsing dot on combo pill */
.combo-dot {
    width:6px; height:6px; border-radius:50%;
    background:#d4a017; display:inline-block;
    animation:comboPulse 1.8s ease-in-out infinite;
}
.cat-pill.combo-pill.active .combo-dot { background:#000; animation:none; }
@keyframes comboPulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:0.4; transform:scale(0.7); }
}

/* ===== MENU SECTION ===== */
.menu-section { padding:60px 0 100px; }

.cat-section { margin-bottom:56px; }
.cat-section-header {
    display:flex; align-items:center; gap:14px;
    margin-bottom:24px; padding-bottom:16px;
    border-bottom:1px solid rgba(255,255,255,0.06);
}
.cat-icon {
    width:52px; height:52px; border-radius:14px; flex-shrink:0;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.2);
    display:flex; align-items:center; justify-content:center; font-size:24px;
}
.cat-section-header h2 { font-size:22px; font-weight:800; }
.cat-section-header h2 span { color:#ff7b00; }
.cat-count {
    margin-left:auto; font-size:12px; color:#555;
    background:#1a1a1a; border:1px solid rgba(255,255,255,0.06);
    padding:4px 12px; border-radius:20px;
}

/* ===== MENU GRID ===== */
.menu-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(230px, 1fr));
    gap:22px;
}

/* ===== MENU CARD ===== */
.menu-card {
    background:#131313;
    border:1px solid rgba(255,255,255,0.07);
    border-radius:20px; overflow:hidden;
    display:flex; flex-direction:column;
    position:relative;
    transition:transform 0.3s cubic-bezier(.22,.68,0,1.2), box-shadow 0.3s ease, border-color 0.3s ease;
    box-shadow: 0 2px 12px rgba(0,0,0,0.3);
}
.menu-card:hover {
    transform:translateY(-7px);
    border-color:rgba(255,123,0,0.4);
    box-shadow:0 20px 50px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,123,0,0.1);
}
.menu-card.out-of-stock { opacity:0.6; }
.menu-card.out-of-stock:hover { transform:none; border-color:rgba(255,255,255,0.07); box-shadow:0 2px 12px rgba(0,0,0,0.3); }

/* Image */
.card-img-wrap { position:relative; overflow:hidden; flex-shrink:0; }
.card-img-wrap img {
    width:100%; height:190px; object-fit:cover;
    display:block; transition:transform 0.5s cubic-bezier(.22,.68,0,1.2);
}
.menu-card:hover:not(.out-of-stock) .card-img-wrap img { transform:scale(1.1); }
.menu-card.out-of-stock .card-img-wrap img { filter:grayscale(60%); }

/* gradient fade at bottom of image */
.card-img-wrap::after {
    content:'';
    position:absolute; bottom:0; left:0; right:0; height:70px;
    background:linear-gradient(to top, #131313 0%, transparent 100%);
    pointer-events:none;
}

/* Floating badges on image */
.oos-ribbon {
    position:absolute; top:12px; left:12px; z-index:2;
    background:rgba(20,20,20,0.85); color:#e74c3c;
    border:1px solid rgba(231,76,60,0.4);
    padding:4px 10px; border-radius:20px;
    font-size:11px; font-weight:700; letter-spacing:0.3px;
    backdrop-filter:blur(6px);
}
.off-float-badge {
    position:absolute; top:12px; right:12px; z-index:2;
    background:linear-gradient(135deg,#27ae60,#2ecc71);
    color:#fff; padding:4px 10px; border-radius:20px;
    font-size:10px; font-weight:800; letter-spacing:0.4px;
    box-shadow:0 4px 12px rgba(39,174,96,0.45);
}
.veg-badge {
    position:absolute; bottom:16px; right:12px; z-index:3;
    width:20px; height:20px; border-radius:4px;
    border:2px solid #27ae60; background:rgba(13,13,13,0.85);
    display:flex; align-items:center; justify-content:center;
    backdrop-filter:blur(4px);
}
.veg-badge::after {
    content:''; width:9px; height:9px;
    border-radius:50%; background:#27ae60;
}

/* Card Body */
.card-body {
    padding:14px 16px 16px;
    flex:1; display:flex; flex-direction:column; gap:10px;
}
.card-top { display:flex; align-items:flex-start; justify-content:space-between; gap:6px; }
.card-category {
    font-size:9px; color:#ff7b00; font-weight:700;
    text-transform:uppercase; letter-spacing:1.2px;
    background:rgba(255,123,0,0.08);
    border:1px solid rgba(255,123,0,0.18);
    padding:2px 8px; border-radius:20px;
    display:inline-flex; align-items:center; gap:4px;
    white-space:nowrap;
}
.card-name {
    font-size:14px; font-weight:700; line-height:1.45;
    color:#f0f0f0; flex:1;
}

/* ─── Size Picker (redesigned) ─── */
.sp-header {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:8px;
}
.sp-label {
    font-size:10px; color:#666; font-weight:600;
    text-transform:uppercase; letter-spacing:0.8px;
}
.sp-discount {
    font-size:10px; color:#27ae60; font-weight:700;
    display:flex; align-items:center; gap:3px;
}

.size-picker {
    display:flex; gap:6px; margin-bottom:2px;
}
.size-pill {
    flex:1; cursor:pointer;
    display:flex; flex-direction:column; align-items:center;
    padding:8px 5px 7px;
    background:rgba(255,255,255,0.03);
    border:1.5px solid rgba(255,255,255,0.07);
    border-radius:12px; transition:all 0.2s ease;
    position:relative; overflow:hidden; user-select:none;
}
.size-pill::before {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg,rgba(255,123,0,0.15),rgba(255,149,0,0.05));
    opacity:0; transition:opacity 0.2s;
}
.size-pill:hover { border-color:rgba(255,123,0,0.35); }
.size-pill:hover::before { opacity:0.6; }
.size-pill.active {
    border-color:#ff7b00;
    background:rgba(255,123,0,0.08);
    box-shadow:0 0 0 2.5px rgba(255,123,0,0.18), inset 0 0 12px rgba(255,123,0,0.06);
}
.size-pill.active::before { opacity:1; }
.size-pill input[type="radio"] { display:none; }

.sz-name {
    font-size:9px; font-weight:700; color:#666;
    text-transform:uppercase; letter-spacing:0.6px;
    margin-bottom:4px; transition:color 0.2s;
    line-height:1;
}
.size-pill.active .sz-name { color:#ff9500; }
.size-pill:hover .sz-name { color:#aaa; }

.sz-new {
    font-size:13px; font-weight:800; color:#e8e8e8;
    line-height:1.1; transition:color 0.2s;
}
.size-pill.active .sz-new { color:#ff7b00; }

.sz-old {
    font-size:9px; color:#444; font-weight:500;
    text-decoration:line-through; text-decoration-color:rgba(231,76,60,0.7);
    margin-top:2px; line-height:1;
}
.size-pill.active .sz-old { color:#666; }

/* Active dot indicator */
.sz-dot {
    width:5px; height:5px; border-radius:50%;
    background:#ff7b00; margin-top:5px;
    opacity:0; transform:scale(0);
    transition:all 0.2s cubic-bezier(.34,1.56,.64,1);
}
.size-pill.active .sz-dot { opacity:1; transform:scale(1); }
.size-price-hidden { display:none; }

/* Price (non-size items) */
.card-price-wrap {
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
}
.card-price-new {
    font-size:20px; font-weight:800; color:#ff7b00;
    line-height:1;
}
.card-price-old {
    font-size:13px; color:#555; font-weight:500;
    text-decoration:line-through; text-decoration-color:rgba(231,76,60,0.7);
}
.off-badge {
    display:inline-flex; align-items:center; gap:3px;
    background:rgba(39,174,96,0.12); border:1px solid rgba(39,174,96,0.3);
    color:#2ecc71; font-size:9px; font-weight:800;
    padding:2px 7px; border-radius:20px; letter-spacing:0.3px;
}

/* Add to Cart Button */
.add-btn {
    width:100%; padding:11px 14px; margin-top:auto;
    background:linear-gradient(135deg, #ff7b00 0%, #ff9d00 100%);
    color:#fff; border:none; border-radius:12px;
    font-size:13px; font-weight:700; cursor:pointer;
    font-family:'Poppins',sans-serif;
    display:flex; align-items:center; justify-content:center; gap:7px;
    box-shadow:0 4px 16px rgba(255,123,0,0.25);
    transition:all 0.25s cubic-bezier(.22,.68,0,1.2);
    position:relative; overflow:hidden;
}
.add-btn::after {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg, rgba(255,255,255,0.12), transparent);
    opacity:0; transition:opacity 0.2s;
}
.add-btn:hover:not(:disabled) {
    transform:translateY(-2px);
    box-shadow:0 8px 24px rgba(255,123,0,0.45);
}
.add-btn:hover:not(:disabled)::after { opacity:1; }
.add-btn:active:not(:disabled) { transform:scale(0.97); }
.add-btn:disabled {
    background:rgba(255,255,255,0.04); color:#3a3a3a;
    border:1px solid rgba(255,255,255,0.05); box-shadow:none; cursor:not-allowed;
}

/* ===== NO RESULTS ===== */
.no-results {
    text-align:center; padding:80px 20px; color:#444; display:none;
}
.no-results i { font-size:48px; margin-bottom:14px; display:block; }
.no-results p { font-size:16px; }


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

/* Tablet — 768px */
@media(max-width:768px){
    .container { padding:0 16px; }

    /* Hero */
    .page-hero { padding:70px 0 32px; }
    .page-hero .inner { flex-direction:column; gap:16px; }
    .page-hero h1 { font-size:28px; }
    .page-hero p  { font-size:13px; }
    .search-wrap  { width:100%; }
    .search-box   { width:100%; }

    /* Category bar */
    .cat-bar { padding:0 16px 4px; gap:6px; }
    .cat-pill { padding:7px 13px; font-size:12px; }

    /* Grid */
    .menu-grid { grid-template-columns:repeat(auto-fill, minmax(155px,1fr)); gap:14px; }
    .card-img-wrap img { height:150px; }

    /* Card body */
    .card-body { padding:11px 12px 13px; gap:8px; }
    .card-name  { font-size:13px; }

    /* Size pills */
    .size-picker { gap:4px; }
    .size-pill { padding:7px 4px 6px; border-radius:10px; }
    .sz-name { font-size:8px; }
    .sz-new  { font-size:12px; }
    .sz-old  { font-size:8px; }

    /* Add button */
    .add-btn { padding:10px 10px; font-size:12px; }

    /* Floating buttons */
    .instagram-float { bottom:130px; right:16px; width:44px; height:44px; font-size:20px; }
    .whatsapp-float  { bottom:76px;  right:16px; width:44px; height:44px; font-size:20px; }
    .back-to-top     { bottom:24px;  right:16px; width:40px; height:40px; font-size:16px; }

    /* Section header */
    .cat-section-header { gap:10px; margin-bottom:16px; padding-bottom:12px; }
    .cat-icon { width:42px; height:42px; font-size:20px; border-radius:12px; }
    .cat-section-header h2 { font-size:18px; }
    .cat-count { font-size:11px; padding:3px 10px; }

    .menu-section { padding:36px 0 80px; }
    .cat-section  { margin-bottom:40px; }
}

/* Mobile — 480px */
@media(max-width:480px){
    /* Grid: 2 columns */
    .menu-grid { grid-template-columns:1fr 1fr; gap:11px; }

    /* Hero tighter */
    .page-hero { padding:64px 0 26px; }
    .page-hero h1 { font-size:24px; }
    .page-hero-badge { font-size:11px; padding:5px 13px; }

    /* Card image shorter */
    .card-img-wrap img { height:130px; }

    /* Card body compact */
    .card-body { padding:9px 10px 11px; gap:7px; }
    .card-name  { font-size:12px; line-height:1.4; }
    .card-category { font-size:8px; padding:2px 6px; }

    /* Price */
    .card-price-new { font-size:16px; }
    .card-price-old { font-size:11px; }
    .off-badge      { font-size:8px; padding:2px 5px; }
    .off-float-badge { font-size:9px; padding:3px 7px; top:8px; right:8px; }
    .oos-ribbon     { font-size:9px; padding:3px 7px; top:8px; left:8px; }
    .veg-badge      { width:16px; height:16px; bottom:10px; right:8px; }
    .veg-badge::after { width:7px; height:7px; }

    /* Size picker tiny */
    .sp-label   { font-size:9px; }
    .sp-discount { font-size:9px; }
    .size-picker { gap:3px; }
    .size-pill  { padding:6px 3px 5px; border-radius:9px; }
    .sz-name    { font-size:7px; letter-spacing:0; margin-bottom:3px; }
    .sz-new     { font-size:11px; }
    .sz-old     { font-size:7px; margin-top:1px; }
    .sz-dot     { width:4px; height:4px; margin-top:3px; }

    /* Add button */
    .add-btn { padding:9px 8px; font-size:11px; gap:5px; border-radius:10px; }

    /* Section header */
    .cat-section-header h2 { font-size:16px; }
    .cat-icon  { width:36px; height:36px; font-size:17px; border-radius:10px; }
    .cat-count { display:none; }

    .menu-section { padding:28px 0 70px; }
    .cat-section  { margin-bottom:32px; }

    /* Search box */
    .search-box { padding:10px 14px; border-radius:12px; }
    .search-box input { font-size:13px; }
}

/* Very small — 360px */
@media(max-width:360px){
    .menu-grid { grid-template-columns:1fr 1fr; gap:9px; }
    .card-img-wrap img { height:115px; }
    .card-body  { padding:8px 8px 10px; }
    .card-name  { font-size:11px; }
    .add-btn    { font-size:10px; padding:8px 6px; }
    .sz-new     { font-size:10px; }
    .container  { padding:0 12px; }
}
/* ===== CART TOAST ===== */
.cart-toast {
    position:fixed; bottom:90px; left:50%; transform:translateX(-50%) translateY(20px);
    background:#1a1a1a; border:1px solid rgba(255,123,0,0.4);
    color:#fff; padding:12px 20px; border-radius:14px;
    font-size:13px; font-weight:600; z-index:9999;
    display:flex; align-items:center; gap:10px;
    box-shadow:0 8px 32px rgba(0,0,0,0.5);
    opacity:0; pointer-events:none;
    transition:opacity 0.3s ease, transform 0.3s ease;
    white-space:nowrap; max-width:90vw;
}
.cart-toast.show {
    opacity:1; transform:translateX(-50%) translateY(0);
}
.cart-toast .t-icon { font-size:18px; }
.cart-toast .t-name { color:#ff7b00; max-width:160px; overflow:hidden; text-overflow:ellipsis; }
.cart-toast .t-count {
    margin-left:auto; background:rgba(255,123,0,0.15);
    border:1px solid rgba(255,123,0,0.3);
    color:#ff7b00; padding:2px 8px; border-radius:20px; font-size:11px;
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Cart Toast Notification -->
<div class="cart-toast" id="cartToast">
    <span class="t-icon">🛒</span>
    <span><span class="t-name" id="toastName"></span> added!</span>
    <span class="t-count" id="toastCount">0</span>
</div>

<!-- Page Hero -->
<div class="page-hero">
    <div class="container">
        <div class="inner">
            <div>
                <div class="page-hero-badge">
                    <i class="fa fa-utensils"></i> Full Menu
                </div>
                <h1>Our <span>Delicious</span> Menu</h1>
                <p>Fresh ingredients, bold flavours — from street-style bites to creamy desserts, all under one roof.</p>
            </div>
            <div class="search-wrap">
                <div class="search-box">
                    <i class="fa fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search dishes..." oninput="filterMenu(this.value)">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sticky Category Bar -->
<div class="cat-bar-wrap">
    <div class="cat-bar" id="catBar">
        <?php foreach($categories as $cat):
            $icon = $category_icons[$cat] ?? '🍽️';
            $id   = strtolower(str_replace(' ','-',$cat));
            // Check if category has items
            $cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM menu WHERE category='".mysqli_real_escape_string($conn,$cat)."' AND is_active=1"))['c'];
            if($cnt == 0) continue;
        ?>
        <button class="cat-pill" data-target="<?php echo $id; ?>" onclick="scrollToSection('<?php echo $id; ?>', this)">
            <?php echo $icon; ?> <?php echo $cat; ?>
        </button>
        <?php endforeach; ?>
        <button class="cat-pill combo-pill" data-target="Combos" onclick="scrollToSection('Combos', this)">
            🍱 Combos <span class="combo-dot"></span>
        </button>
    </div>
</div>

<!-- Menu Sections -->
<section class="menu-section">
    <div class="container">

        <div class="no-results" id="noResults">
            <i class="fa fa-face-sad-tear"></i>
            <p>Koi item nahi mila "<span id="searchTerm"></span>" ke liye</p>
        </div>

        <?php foreach($categories as $cat):
            $icon       = $category_icons[$cat] ?? '🍽️';
            $section_id = strtolower(str_replace(' ','-',$cat));
            $cat_e      = mysqli_real_escape_string($conn, $cat);

            $result = mysqli_query($conn, "SELECT * FROM menu WHERE category='$cat_e' AND is_active=1 ORDER BY is_available DESC, item_name ASC");
            $items = [];
            while($row = mysqli_fetch_assoc($result)) $items[] = $row;
            if(count($items) == 0) continue;
        ?>

        <div class="cat-section" id="section-<?php echo $section_id; ?>">
            <div class="cat-section-header">
                <div class="cat-icon"><?php echo $icon; ?></div>
                <h2><?php echo $cat; ?> <span>Menu</span></h2>
                <span class="cat-count"><?php echo count($items); ?> items</span>
            </div>

            <div class="menu-grid">
            <?php foreach($items as $item):
                $available = isset($item['is_available']) ? intval($item['is_available']) : 1;
                $oos_class = $available ? '' : 'out-of-stock';
                $item_name = htmlspecialchars($item['item_name']);
            ?>
                <div class="menu-card <?php echo $oos_class; ?>" data-name="<?php echo strtolower($item['item_name']); ?>" data-cat="<?php echo strtolower($cat); ?>">

                    <div class="card-img-wrap">
                        <img src="../assets/images/<?php echo $item['image']; ?>"
                             alt="<?php echo $item_name; ?>"
                             onerror="this.src='../assets/images/logo.png'">
                        <?php if(!$available): ?>
                        <div class="oos-ribbon">🚫 Out of Stock</div>
                        <?php endif; ?>
                        <div class="off-float-badge">🏷️ 5% OFF</div>
                    </div>

                    <div class="card-body">
                        <div>
                            <div class="card-category"><?php echo $icon; ?> <?php echo $cat; ?></div>
                            <div class="card-name"><?php echo $item_name; ?></div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="id"   value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="name" value="<?php echo $item_name; ?>">

                            <?php if($cat == 'Pizza' && $item['small_price']): ?>
                                <div class="sp-header">
                                    <span class="sp-label"><i class="fa fa-ruler-horizontal fa-xs"></i> Choose Size</span>
                                    <span class="sp-discount"><i class="fa fa-tag fa-xs"></i> 5% OFF</span>
                                </div>
                                <?php
                                $sizes_pizza = [];
                                if($item['small_price'])  $sizes_pizza[] = ['label'=>'S',  'full'=>'Small',  'orig'=>$item['small_price'],  'disc'=>round($item['small_price']*0.95,2)];
                                if($item['medium_price']) $sizes_pizza[] = ['label'=>'M',  'full'=>'Medium', 'orig'=>$item['medium_price'], 'disc'=>round($item['medium_price']*0.95,2)];
                                if($item['large_price'])  $sizes_pizza[] = ['label'=>'L',  'full'=>'Large',  'orig'=>$item['large_price'],  'disc'=>round($item['large_price']*0.95,2)];
                                $pid = 'sz_'.$item['id'];
                                ?>
                                <div class="size-picker">
                                <?php foreach($sizes_pizza as $si => $sz): ?>
                                    <label class="size-pill <?= $si===0?'active':'' ?>"
                                           onclick="pickSize(this,'<?= $pid ?>','<?= $sz['disc'] ?>')">
                                        <input type="radio" name="__sz_<?= $pid ?>" <?= $si===0?'checked':'' ?>>
                                        <span class="sz-name"><?= $sz['label'] ?></span>
                                        <span class="sz-new">₹<?= $sz['disc'] ?></span>
                                        <span class="sz-old">₹<?= $sz['orig'] ?></span>
                                        <span class="sz-dot"></span>
                                    </label>
                                <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="price" id="<?= $pid ?>" value="<?= $sizes_pizza[0]['disc'] ?>" class="size-price-hidden">

                            <?php elseif($cat == 'Cakes' && $item['small_price']): ?>
                                <div class="sp-header">
                                    <span class="sp-label"><i class="fa fa-weight-scale fa-xs"></i> Choose Size</span>
                                    <span class="sp-discount"><i class="fa fa-tag fa-xs"></i> 5% OFF</span>
                                </div>
                                <?php
                                $sizes_cake = [];
                                if($item['small_price'])  $sizes_cake[] = ['label'=>'500g', 'orig'=>$item['small_price'],  'disc'=>round($item['small_price']*0.95,2)];
                                if($item['medium_price']) $sizes_cake[] = ['label'=>'1 kg', 'orig'=>$item['medium_price'], 'disc'=>round($item['medium_price']*0.95,2)];
                                $cid = 'sz_'.$item['id'];
                                ?>
                                <div class="size-picker">
                                <?php foreach($sizes_cake as $si => $sz): ?>
                                    <label class="size-pill <?= $si===0?'active':'' ?>"
                                           onclick="pickSize(this,'<?= $cid ?>','<?= $sz['disc'] ?>')">
                                        <input type="radio" name="__sz_<?= $cid ?>" <?= $si===0?'checked':'' ?>>
                                        <span class="sz-name"><?= $sz['label'] ?></span>
                                        <span class="sz-new">₹<?= $sz['disc'] ?></span>
                                        <span class="sz-old">₹<?= $sz['orig'] ?></span>
                                        <span class="sz-dot"></span>
                                    </label>
                                <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="price" id="<?= $cid ?>" value="<?= $sizes_cake[0]['disc'] ?>" class="size-price-hidden">

                            <?php elseif(in_array($cat,['Shakes','Coffee']) && $item['small_price']): ?>
                                <div class="sp-header">
                                    <span class="sp-label"><i class="fa fa-mug-hot fa-xs"></i> Choose Style</span>
                                    <span class="sp-discount"><i class="fa fa-tag fa-xs"></i> 5% OFF</span>
                                </div>
                                <?php
                                $sizes_drink = [];
                                if($item['small_price'])  $sizes_drink[] = ['label'=>'REG', 'orig'=>$item['small_price'],  'disc'=>round($item['small_price']*0.95,2)];
                                if($item['medium_price']) $sizes_drink[] = ['label'=>'+ICE', 'orig'=>$item['medium_price'], 'disc'=>round($item['medium_price']*0.95,2)];
                                $did = 'sz_'.$item['id'];
                                ?>
                                <div class="size-picker">
                                <?php foreach($sizes_drink as $si => $sz): ?>
                                    <label class="size-pill <?= $si===0?'active':'' ?>"
                                           onclick="pickSize(this,'<?= $did ?>','<?= $sz['disc'] ?>')">
                                        <input type="radio" name="__sz_<?= $did ?>" <?= $si===0?'checked':'' ?>>
                                        <span class="sz-name"><?= $sz['label'] ?></span>
                                        <span class="sz-new">₹<?= $sz['disc'] ?></span>
                                        <span class="sz-old">₹<?= $sz['orig'] ?></span>
                                        <span class="sz-dot"></span>
                                    </label>
                                <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="price" id="<?= $did ?>" value="<?= $sizes_drink[0]['disc'] ?>" class="size-price-hidden">

                            <?php else:
                                $disc_price = round($item['price'] * 0.95, 2);
                            ?>
                                <div class="card-price-wrap">
                                    <span class="card-price-new">₹<?= $disc_price ?></span>
                                    <span class="card-price-old">₹<?= $item['price'] ?></span>
                                    <span class="off-badge"><i class="fa fa-tag fa-xs"></i> 5% OFF</span>
                                </div>
                                <input type="hidden" name="price" value="<?= $disc_price ?>">
                            <?php endif; ?>

                            <button type="submit" name="add_to_cart" class="add-btn"
                                <?php echo !$available ? 'disabled' : ''; ?>>
                                <?php if($available): ?>
                                    <i class="fa fa-cart-plus"></i> Add to Cart
                                <?php else: ?>
                                    <i class="fa fa-ban"></i> Out of Stock
                                <?php endif; ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>

        <?php endforeach; ?>

        <!-- ===== COMBO SECTION — always visible ===== -->
        <div class="cat-section" id="section-Combos">
            <div class="cat-section-header">
                <div class="cat-icon">🍱</div>
                <h2>Special <span>Combos</span></h2>
                <span class="cat-count"><?php echo count($combos_list); ?> combos</span>
            </div>

            <?php if(empty($combos_list)): ?>
            <!-- Empty state -->
            <div style="text-align:center;padding:60px 20px;background:#131313;border:1px solid rgba(255,255,255,0.06);border-radius:20px;">
                <div style="font-size:60px;margin-bottom:16px;">🍱</div>
                <h3 style="color:#fff;font-size:18px;font-weight:700;margin-bottom:8px;">Combos Coming Soon!</h3>
                <p style="color:#555;font-size:13px;line-height:1.7;">Hamare special combo deals jald hi available honge.<br>Tab tak baaki menu enjoy karein! 🔥</p>
            </div>

            <?php else: ?>
            <div class="menu-grid">
            <?php foreach($combos_list as $combo):
                $available = intval($combo['is_available']);

                // Fetch included items for this combo
                $citems_r = mysqli_query($conn,
                    "SELECT m.item_name FROM combo_items ci
                     JOIN menu m ON m.id = ci.menu_id
                     WHERE ci.combo_id = " . intval($combo['id'])
                );
                $combo_item_names = [];
                while ($ci = mysqli_fetch_assoc($citems_r)) $combo_item_names[] = $ci['item_name'];
            ?>
            <div class="menu-card <?php echo !$available ? 'out-of-stock' : ''; ?>"
                 data-name="<?php echo strtolower(htmlspecialchars($combo['combo_name'])); ?>"
                 data-cat="combos">

                <!-- COMBO badge -->
                <div style="position:absolute;top:10px;left:10px;z-index:3;">
                    <span style="background:#d4a017;color:#000;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;letter-spacing:0.5px;">
                        🍱 COMBO
                    </span>
                </div>

                <?php
                $combo_orig = floatval($combo['combo_price']);
                $combo_disc = round($combo_orig * 0.95, 2);
                ?>

                <?php if(!$available): ?>
                <div style="position:absolute;top:10px;right:10px;z-index:3;">
                    <span class="oos-ribbon">🚫 Unavailable</span>
                </div>
                <?php else: ?>
                <div class="off-float-badge">🏷️ 5% OFF</div>
                <?php endif; ?>

                <!-- Image -->
                <div class="card-img-wrap">
                    <?php if($combo['image']): ?>
                    <img src="../assets/images/<?php echo htmlspecialchars($combo['image']); ?>"
                         alt="<?php echo htmlspecialchars($combo['combo_name']); ?>"
                         style="<?php echo !$available ? 'filter:grayscale(70%);opacity:0.6;' : ''; ?>"
                         onerror="this.parentNode.innerHTML='<div style=\'width:100%;height:190px;background:linear-gradient(135deg,#1a1a0a,#2a2005);display:flex;align-items:center;justify-content:center;font-size:56px;\'>🍱</div>'">
                    <?php else: ?>
                    <div style="width:100%;height:190px;background:linear-gradient(135deg,#1a1a0a,#2a2005);display:flex;align-items:center;justify-content:center;font-size:56px;">🍱</div>
                    <?php endif; ?>
                </div>

                <!-- Card body -->
                <div class="card-body">
                    <div>
                        <div class="card-category">🍱 Combo</div>
                        <div class="card-name"><?php echo htmlspecialchars($combo['combo_name']); ?></div>
                    </div>

                    <?php if($combo['description']): ?>
                    <p style="font-size:12px;color:#666;line-height:1.5;margin:0;">
                        <?php echo htmlspecialchars($combo['description']); ?>
                    </p>
                    <?php endif; ?>

                    <!-- Included items chips -->
                    <?php if(!empty($combo_item_names)): ?>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                        <?php foreach($combo_item_names as $cin): ?>
                        <span style="padding:2px 8px;border-radius:10px;background:rgba(255,123,0,0.08);border:1px solid rgba(255,123,0,0.2);font-size:10px;color:#ff7b00;font-weight:600;">
                            <?php echo htmlspecialchars($cin); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Price -->
                    <div class="card-price-wrap">
                        <span class="card-price-new" style="color:#d4a017;">₹<?php echo $combo_disc; ?></span>
                        <span class="card-price-old">₹<?php echo $combo_orig; ?></span>
                        <span class="off-badge"><i class="fa fa-tag fa-xs"></i> 5% OFF</span>
                    </div>

                    <!-- Add to cart form -->
                    <form method="POST">
                        <input type="hidden" name="id"    value="combo_<?php echo $combo['id']; ?>">
                        <input type="hidden" name="name"  value="<?php echo htmlspecialchars($combo['combo_name']); ?>">
                        <input type="hidden" name="price" value="<?php echo $combo_disc; ?>">
                        <button type="submit" name="add_to_cart"
                                class="add-btn"
                                <?php echo !$available ? 'disabled' : ''; ?>
                                style="<?php echo $available ? 'background:linear-gradient(135deg,#d4a017,#b8860b);' : ''; ?>">
                            <?php if($available): ?>
                                <i class="fa fa-cart-plus"></i> Add Combo to Cart
                            <?php else: ?>
                                <i class="fa fa-ban"></i> Unavailable
                            <?php endif; ?>
                        </button>
                    </form>
                </div>

            </div><!-- /.menu-card -->
            <?php endforeach; ?>
            </div><!-- /.menu-grid -->
            <?php endif; ?>

        </div><!-- /#section-Combos -->

    </div>
</section>

<?php include 'includes/footer.php'; ?>

<!-- Instagram Float -->
<a href="https://www.instagram.com/dropperscafe" class="instagram-float" target="_blank" title="Follow on Instagram">
    <i class="fab fa-instagram"></i>
</a>
<!-- WhatsApp Float -->
<a href="https://wa.me/917004810081" class="whatsapp-float" target="_blank" title="Chat on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>
<!-- Back to Top -->
<a href="#" class="back-to-top" id="backToTop">
    <i class="fa fa-arrow-up"></i>
</a>

<script>
// Mouse drag scroll for category bar
const catBar = document.getElementById('catBar');
let isDown = false, startX, scrollLeft;
catBar.addEventListener('mousedown', e => {
    isDown = true;
    startX = e.pageX - catBar.offsetLeft;
    scrollLeft = catBar.scrollLeft;
});
catBar.addEventListener('mouseleave', () => isDown = false);
catBar.addEventListener('mouseup',    () => isDown = false);
catBar.addEventListener('mousemove', e => {
    if(!isDown) return;
    e.preventDefault();
    const x    = e.pageX - catBar.offsetLeft;
    const walk = (x - startX) * 1.5;
    catBar.scrollLeft = scrollLeft - walk;
});

// Dynamic navbar offset for sticky category bar
function fixCatBarOffset(){
    const navbar = document.querySelector('.top-navbar') || document.querySelector('nav') || document.querySelector('header');
    const catBar = document.querySelector('.cat-bar-wrap');
    if(navbar && catBar){
        catBar.style.top = navbar.offsetHeight + 'px';
    }
}
fixCatBarOffset();
window.addEventListener('resize', fixCatBarOffset);

// Scroll to category section
function scrollToSection(id, btn){
    const el = document.getElementById('section-' + id);
    if(el){
        const offset = 80;
        const top = el.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({ top, behavior:'smooth' });
    }
    document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
    if(btn) btn.classList.add('active');
}

// Active pill on scroll
const sections = document.querySelectorAll('.cat-section');
const pills    = document.querySelectorAll('.cat-pill');
window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(sec => {
        if(window.scrollY >= sec.offsetTop - 120) current = sec.id.replace('section-','');
    });
    pills.forEach(p => {
        p.classList.toggle('active', p.dataset.target === current);
    });
    document.getElementById('backToTop').classList.toggle('show', window.scrollY > 300);
});

// Search / Filter
function filterMenu(q){
    q = q.toLowerCase().trim();
    document.getElementById('searchTerm').textContent = q;
    let anyVisible = false;

    document.querySelectorAll('.cat-section').forEach(sec => {
        let secVisible = false;
        sec.querySelectorAll('.menu-card').forEach(card => {
            const name = card.dataset.name || '';
            const cat  = card.dataset.cat  || '';
            const show = !q || name.includes(q) || cat.includes(q);
            card.style.display = show ? '' : 'none';
            if(show) { secVisible = true; anyVisible = true; }
        });
        sec.style.display = secVisible ? '' : 'none';
    });

    document.getElementById('noResults').style.display = anyVisible ? 'none' : 'block';
}

// Size pill selector
function pickSize(pill, inputId, price){
    const picker = pill.closest('.size-picker');
    picker.querySelectorAll('.size-pill').forEach(p => p.classList.remove('active'));
    pill.classList.add('active');
    document.getElementById(inputId).value = price;
}

// Activate first pill on load
if(pills.length) pills[0].classList.add('active');

// ── AJAX Add to Cart — no page reload ────────────────────────────────────────
let toastTimer = null;

function showToast(name, count) {
    const toast = document.getElementById('cartToast');
    const tName = document.getElementById('toastName');
    const tCount = document.getElementById('toastCount');
    tName.textContent = name.length > 22 ? name.slice(0,22)+'…' : name;
    tCount.textContent = count + ' in cart';
    toast.classList.add('show');
    if(toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2500);
}

function updateNavCartCount(count) {
    // Update any cart badge in navbar (works with various badge selectors)
    const badges = document.querySelectorAll('.cart-count, .cart-badge, #cartCount, [data-cart-count]');
    badges.forEach(b => { b.textContent = count; b.style.display = count > 0 ? '' : 'none'; });
}

// Intercept all add-to-cart form submits
document.addEventListener('submit', function(e) {
    const form = e.target;
    const btn  = form.querySelector('[name="add_to_cart"]');
    if (!btn) return;          // not an add-to-cart form — ignore

    e.preventDefault();        // ← stop page reload

    const data = new FormData(form);
    data.append('ajax', '1');
    data.append('add_to_cart', '1');

    // Button feedback
    const origHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

    fetch('menu.php', { method:'POST', body:data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                // ✅ Added — green flash
                btn.innerHTML = '<i class="fa fa-check"></i> Added!';
                btn.style.background = 'linear-gradient(135deg,#27ae60,#2ecc71)';
                showToast(res.name, res.cart_count);
                updateNavCartCount(res.cart_count);
                setTimeout(() => {
                    btn.innerHTML = origHTML;
                    btn.style.background = '';
                    btn.disabled = false;
                }, 1400);
            } else {
                // ❌ Failed
                btn.innerHTML = '<i class="fa fa-ban"></i> Unavailable';
                btn.style.background = 'rgba(231,76,60,0.3)';
                setTimeout(() => {
                    btn.innerHTML = origHTML;
                    btn.style.background = '';
                    btn.disabled = false;
                }, 1400);
            }
        })
        .catch(() => {
            btn.innerHTML = origHTML;
            btn.style.background = '';
            btn.disabled = false;
        });
});
</script>

</body>
</html>