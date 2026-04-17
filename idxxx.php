<?php
session_start();
include '../config.php';

// Popular items fetch (first 8 from most popular categories)
$popular = [];
$pop_result = mysqli_query($conn, "
    SELECT * FROM menu 
    WHERE is_available = 1 
    AND category IN ('Pizza','Burger','Momos','Rolls','Noodles','Shakes','Sandwich','Chinese')
    ORDER BY RAND() 
    LIMIT 8
");
while($r = mysqli_fetch_assoc($pop_result)) $popular[] = $r;

// Snacks tab
$snacks = [];
$snack_result = mysqli_query($conn, "
    SELECT * FROM menu 
    WHERE is_available = 1 
    AND category IN ('Sandwich','Donut','Muffin','Hot Dog','Dessert','Cakes')
    ORDER BY RAND() 
    LIMIT 8
");
while($r = mysqli_fetch_assoc($snack_result)) $snacks[] = $r;

// Drinks tab
$drinks = [];
$drink_result = mysqli_query($conn, "
    SELECT * FROM menu 
    WHERE is_available = 1 
    AND category IN ('Shakes','Coffee','Drinks','Soup')
    ORDER BY RAND() 
    LIMIT 8
");
while($r = mysqli_fetch_assoc($drink_result)) $drinks[] = $r;

// Feedbacks
$feedbacks = [];
$fb_result = mysqli_query($conn, "SELECT * FROM feedback ORDER BY id DESC LIMIT 6");
while($r = mysqli_fetch_assoc($fb_result)) $feedbacks[] = $r;

// Stats
$menu_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM menu"))['c'];
$order_count  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM orders"))['c'];
$cust_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM customers"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Droppers Café & Resto</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Pacifico&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Animate on Scroll -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- Existing Navbar CSS -->
    <link rel="stylesheet" href="../assets/css/customer.css">

<style>
/* ===================== RESET & BASE ===================== */
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
body { background:#0d0d0d; color:#fff; font-family:'Poppins',sans-serif; overflow-x:hidden; }
a { text-decoration:none; }
img { max-width:100%; }

/* ===================== SECTION COMMON ===================== */
.section { padding:80px 0; }
.section-dark  { background:#0d0d0d; }
.section-dark2 { background:#111; }
.section-dark3 { background:#161616; }
.container { max-width:1200px; margin:0 auto; padding:0 24px; }
.section-label {
    font-size:13px; font-weight:600; letter-spacing:3px;
    color:#ff7b00; text-transform:uppercase; margin-bottom:10px;
    display:block;
}
.section-title {
    font-size:38px; font-weight:800; line-height:1.2;
    color:#fff; margin-bottom:16px;
}
.section-title span { color:#ff7b00; }
.section-sub { color:#888; font-size:15px; max-width:500px; }

/* ===================== LOADING SPINNER ===================== */
#page-loader {
    position:fixed; inset:0; background:#0d0d0d;
    display:flex; align-items:center; justify-content:center;
    z-index:9999; transition:opacity 0.5s;
}
.loader-ring {
    width:56px; height:56px;
    border:4px solid rgba(255,123,0,0.2);
    border-top-color:#ff7b00;
    border-radius:50%;
    animation:spin 0.8s linear infinite;
}
@keyframes spin { to { transform:rotate(360deg); } }

/* ===================== HERO ===================== */
.hero {
    min-height:100vh;
    background: linear-gradient(135deg, #0a0a0a 0%, #0f1f0f 50%, #0a0a0a 100%);
    position:relative; overflow:hidden;
    display:flex; align-items:center;
}
.hero::before {
    content:'';
    position:absolute; inset:0;
    background:
        radial-gradient(ellipse at 20% 50%, rgba(255,123,0,0.08) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 20%, rgba(255,200,0,0.05) 0%, transparent 50%);
}
.hero-content {
    position:relative; z-index:2;
    display:flex; align-items:center; justify-content:space-between;
    gap:40px; flex-wrap:wrap;
    width:100%;
}
.hero-text { flex:1; min-width:300px; }
.hero-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.3);
    padding:6px 16px; border-radius:30px;
    font-size:13px; color:#ff7b00; font-weight:600;
    margin-bottom:24px;
}
.hero-title {
    font-size:clamp(36px, 5vw, 64px);
    font-weight:800; line-height:1.1; margin-bottom:20px;
    color:#fff;
}
.hero-title .highlight {
    background: linear-gradient(135deg, #ff7b00, #ffb347);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    background-clip:text;
}
.hero-title .brand-name {
    font-family:'Pacifico', cursive;
    color:#ff7b00; font-size:0.85em;
    display:block; margin-top:4px;
    -webkit-text-fill-color:#ff7b00;
}
.hero-desc {
    font-size:16px; color:#aaa; line-height:1.7;
    margin-bottom:36px; max-width:520px;
}
.hero-btns { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:40px; }
.btn-primary-hero {
    background:linear-gradient(135deg, #ff7b00, #ff9500);
    color:#fff; padding:14px 32px; border-radius:12px;
    font-size:15px; font-weight:700;
    box-shadow:0 8px 24px rgba(255,123,0,0.35);
    transition:all 0.3s; display:inline-flex; align-items:center; gap:8px;
}
.btn-primary-hero:hover { transform:translateY(-2px); box-shadow:0 12px 32px rgba(255,123,0,0.45); color:#fff; }
.btn-outline-hero {
    background:transparent; color:#fff;
    border:2px solid rgba(255,255,255,0.2);
    padding:14px 32px; border-radius:12px;
    font-size:15px; font-weight:600;
    transition:all 0.3s; display:inline-flex; align-items:center; gap:8px;
}
.btn-outline-hero:hover { border-color:#ff7b00; color:#ff7b00; transform:translateY(-2px); }

.hero-info { display:flex; gap:28px; flex-wrap:wrap; }
.hero-info-item { display:flex; align-items:center; gap:10px; }
.hero-info-icon {
    width:40px; height:40px; border-radius:10px;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.2);
    display:flex; align-items:center; justify-content:center;
    font-size:16px; flex-shrink:0;
}
.hero-info-text p  { font-size:11px; color:#666; margin-bottom:1px; }
.hero-info-text strong { font-size:13px; color:#ccc; }

.hero-image-wrap { flex:1; min-width:280px; text-align:center; position:relative; }
.hero-img-circle {
    width:min(420px, 90vw); height:min(420px, 90vw);
    border-radius:50%; margin:auto;
    background:radial-gradient(circle at 40% 40%, rgba(255,123,0,0.15), transparent 70%);
    border:2px solid rgba(255,123,0,0.15);
    overflow:hidden; position:relative;
    box-shadow:0 0 80px rgba(255,123,0,0.1);
}
.hero-img-circle img { width:100%; height:100%; object-fit:cover; }
.hero-float-badge {
    position:absolute; background:#1a1a1a;
    border:1px solid rgba(255,123,0,0.3);
    border-radius:12px; padding:10px 16px;
    display:flex; align-items:center; gap:10px;
    box-shadow:0 8px 24px rgba(0,0,0,0.5);
    animation:float 3s ease-in-out infinite;
}
@keyframes float {
    0%,100% { transform:translateY(0); }
    50%      { transform:translateY(-8px); }
}
.hero-float-badge.left  { bottom:20px; left:-20px; }
.hero-float-badge.right { top:30px; right:-20px; animation-delay:1.5s; }
.hero-float-badge .icon { font-size:20px; }
.hero-float-badge .text p    { font-size:10px; color:#888; margin:0; }
.hero-float-badge .text strong { font-size:13px; color:#fff; }

/* ===================== STATS BAR ===================== */
.stats-bar {
    background:linear-gradient(135deg, #1a1a1a, #222);
    border-top:1px solid rgba(255,123,0,0.15);
    border-bottom:1px solid rgba(255,123,0,0.15);
    padding:32px 0;
}
.stats-grid { display:flex; justify-content:center; flex-wrap:wrap; gap:0; }
.stat-item {
    flex:1; min-width:150px; text-align:center;
    padding:16px 20px;
    border-right:1px solid rgba(255,255,255,0.06);
}
.stat-item:last-child { border-right:none; }
.stat-number {
    font-size:36px; font-weight:800;
    background:linear-gradient(135deg, #ff7b00, #ffb347);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    background-clip:text; line-height:1;
}
.stat-label { font-size:13px; color:#777; margin-top:4px; }

/* ===================== SERVICES ===================== */
.services-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
    gap:24px; margin-top:48px;
}
.service-card {
    background:#1a1a1a; border:1px solid rgba(255,255,255,0.05);
    border-radius:16px; padding:32px 24px; text-align:center;
    transition:all 0.3s; position:relative; overflow:hidden;
}
.service-card::before {
    content:''; position:absolute;
    inset:0; background:linear-gradient(135deg, rgba(255,123,0,0.06), transparent);
    opacity:0; transition:0.3s;
}
.service-card:hover { border-color:rgba(255,123,0,0.3); transform:translateY(-4px); }
.service-card:hover::before { opacity:1; }
.service-icon {
    width:64px; height:64px; border-radius:16px; margin:0 auto 20px;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.2);
    display:flex; align-items:center; justify-content:center; font-size:28px;
    transition:0.3s;
}
.service-card:hover .service-icon { background:rgba(255,123,0,0.2); transform:scale(1.1); }
.service-card h4 { font-size:17px; font-weight:700; margin-bottom:8px; }
.service-card p  { font-size:13px; color:#777; line-height:1.6; }

/* ===================== ABOUT ===================== */
.about-grid {
    display:flex; align-items:center; gap:60px;
    flex-wrap:wrap; margin-top:20px;
}
.about-images {
    flex:1; min-width:280px;
    display:grid; grid-template-columns:1fr 1fr; gap:14px;
}
.about-images img {
    width:100%; border-radius:14px; object-fit:cover;
    border:2px solid rgba(255,123,0,0.1);
    transition:0.3s;
}
.about-images img:hover { border-color:rgba(255,123,0,0.4); transform:scale(1.02); }
.about-images .tall { height:220px; }
.about-images .short { height:160px; }
.about-images .short-bottom { height:160px; margin-top:60px; }
.about-images .tall-bottom   { height:220px; }
.about-text { flex:1; min-width:280px; }
.about-text p { color:#888; font-size:15px; line-height:1.8; margin-bottom:20px; }
.about-stats { display:flex; gap:24px; flex-wrap:wrap; margin:28px 0; }
.about-stat {
    display:flex; align-items:center; gap:14px;
    background:#1a1a1a; border-left:4px solid #ff7b00;
    padding:14px 20px; border-radius:0 12px 12px 0; flex:1; min-width:150px;
}
.about-stat .num {
    font-size:32px; font-weight:800; color:#ff7b00; line-height:1;
}
.about-stat .lbl { font-size:12px; color:#888; margin-top:2px; }

/* ===================== MENU TABS ===================== */
.menu-tabs { margin-top:40px; }
.tab-nav {
    display:flex; gap:0; border-bottom:1px solid rgba(255,255,255,0.08);
    margin-bottom:36px; flex-wrap:wrap;
}
.tab-btn {
    display:flex; align-items:center; gap:10px;
    padding:14px 28px; cursor:pointer; background:none; border:none;
    border-bottom:3px solid transparent; color:#666;
    font-size:14px; font-weight:600; font-family:'Poppins',sans-serif;
    transition:0.2s; margin-bottom:-1px;
}
.tab-btn:hover { color:#fff; }
.tab-btn.active { color:#ff7b00; border-bottom-color:#ff7b00; }
.tab-btn i { font-size:20px; }

.tab-panel { display:none; }
.tab-panel.active { display:block; }

.menu-list { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:20px; }
.menu-list-item {
    display:flex; align-items:center; gap:16px;
    background:#1a1a1a; border:1px solid rgba(255,255,255,0.05);
    border-radius:14px; padding:14px; transition:0.3s;
}
.menu-list-item:hover {
    border-color:rgba(255,123,0,0.25);
    background:#1e1e1e; transform:translateX(4px);
}
.menu-list-item img {
    width:80px; height:80px; border-radius:10px;
    object-fit:cover; flex-shrink:0;
    border:1px solid rgba(255,123,0,0.1);
}
.menu-item-info { flex:1; min-width:0; }
.menu-item-info h5 {
    font-size:14px; font-weight:700; margin-bottom:4px;
    display:flex; justify-content:space-between; align-items:center;
    border-bottom:1px dashed rgba(255,255,255,0.06); padding-bottom:8px; margin-bottom:6px;
}
.menu-item-info h5 .price { color:#ff7b00; font-weight:700; font-size:14px; white-space:nowrap; }
.menu-item-info small { color:#666; font-size:12px; }
.menu-item-cat {
    background:rgba(255,123,0,0.1); color:#ff7b00;
    padding:2px 8px; border-radius:20px; font-size:10px;
    font-weight:600; margin-left:6px; white-space:nowrap;
}
.menu-more {
    text-align:center; margin-top:32px;
}
.btn-menu-more {
    display:inline-flex; align-items:center; gap:8px;
    background:transparent; border:2px solid rgba(255,123,0,0.3);
    color:#ff7b00; padding:12px 32px; border-radius:10px;
    font-size:15px; font-weight:600; transition:0.3s;
}
.btn-menu-more:hover {
    background:rgba(255,123,0,0.1); border-color:#ff7b00; transform:scale(1.02);
}

/* ===================== BOOK TABLE ===================== */
.booking-wrap {
    display:flex; gap:0; border-radius:20px; overflow:hidden;
    border:1px solid rgba(255,255,255,0.06); margin-top:40px;
    flex-wrap:wrap;
}
.booking-map { flex:1; min-width:280px; min-height:320px; }
.booking-map iframe { width:100%; height:100%; min-height:320px; border:none; display:block; }
.booking-form-wrap {
    flex:1; min-width:280px;
    background:linear-gradient(135deg, #1a1a1a, #222);
    padding:40px 36px;
}
.booking-form-wrap h3 { font-size:26px; font-weight:800; margin-bottom:8px; }
.booking-form-wrap p  { color:#777; font-size:14px; margin-bottom:28px; }
.form-group { margin-bottom:16px; }
.form-group label { display:block; font-size:13px; color:#888; margin-bottom:6px; font-weight:500; }
.form-group input,
.form-group select,
.form-group textarea {
    width:100%; padding:12px 16px;
    background:#0d0d0d; border:1px solid rgba(255,255,255,0.08);
    border-radius:10px; color:#fff; font-size:14px;
    font-family:'Poppins',sans-serif; transition:0.2s;
    outline:none;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color:rgba(255,123,0,0.5); }
.form-row { display:flex; gap:14px; }
.form-row .form-group { flex:1; }
.btn-book {
    width:100%; padding:14px;
    background:linear-gradient(135deg, #ff7b00, #ff9500);
    color:#fff; border:none; border-radius:12px;
    font-size:15px; font-weight:700; cursor:pointer;
    font-family:'Poppins',sans-serif; margin-top:8px;
    transition:all 0.3s; box-shadow:0 8px 24px rgba(255,123,0,0.3);
}
.btn-book:hover { transform:translateY(-2px); box-shadow:0 12px 30px rgba(255,123,0,0.4); }

/* ===================== TESTIMONIALS ===================== */
.testimonials-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));
    gap:20px; margin-top:48px;
}
.testimonial-card {
    background:#1a1a1a; border:1px solid rgba(255,255,255,0.05);
    border-radius:16px; padding:28px 24px;
    transition:0.3s; position:relative;
}
.testimonial-card:hover {
    border-color:rgba(255,123,0,0.2); transform:translateY(-4px);
}
.testimonial-card .quote-icon {
    font-size:28px; color:rgba(255,123,0,0.3); margin-bottom:14px;
}
.testimonial-card p {
    font-size:14px; color:#888; line-height:1.7; margin-bottom:20px;
}
.testimonial-person { display:flex; align-items:center; gap:12px; }
.person-avatar {
    width:44px; height:44px; border-radius:50%;
    background:linear-gradient(135deg, #ff6b00, #ffb347);
    display:flex; align-items:center; justify-content:center;
    font-size:18px; font-weight:700; color:#fff; flex-shrink:0;
}
.person-name { font-size:14px; font-weight:700; }
.person-stars { color:#f0a500; font-size:12px; margin-top:2px; }
.no-feedback {
    text-align:center; padding:60px 20px; color:#555; grid-column:1/-1;
}
.no-feedback i { font-size:40px; margin-bottom:12px; display:block; color:#333; }

/* ===================== FOOTER ===================== */
.footer {
    background:linear-gradient(135deg, #0a0a0a, #111);
    border-top:1px solid rgba(255,123,0,0.1);
    padding:64px 0 0;
}
.footer-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
    gap:40px; margin-bottom:48px;
}
.footer-brand .logo {
    display:flex; align-items:center; gap:12px; margin-bottom:16px;
}
.footer-brand .logo img { width:48px; height:48px; border-radius:50%; border:2px solid #ff7b00; }
.footer-brand .logo span { font-size:20px; font-weight:800; }
.footer-brand p { color:#666; font-size:13px; line-height:1.7; margin-bottom:20px; }
.footer-social { display:flex; gap:10px; }
.footer-social a {
    width:38px; height:38px; border-radius:10px;
    border:1px solid rgba(255,255,255,0.1); color:#666;
    display:flex; align-items:center; justify-content:center;
    font-size:15px; transition:0.2s;
}
.footer-social a:hover { background:#ff7b00; border-color:#ff7b00; color:#fff; }
.footer-col h4 {
    font-size:14px; font-weight:700; text-transform:uppercase;
    letter-spacing:1px; color:#ff7b00; margin-bottom:20px;
}
.footer-col a {
    display:block; color:#666; font-size:13px;
    padding:6px 0; transition:0.2s;
}
.footer-col a:hover { color:#ff7b00; padding-left:6px; }
.footer-contact-item {
    display:flex; gap:10px; align-items:flex-start;
    color:#666; font-size:13px; margin-bottom:12px; line-height:1.5;
}
.footer-contact-item i { color:#ff7b00; margin-top:2px; flex-shrink:0; }
.footer-hours span { color:#666; font-size:13px; }
.footer-hours strong { color:#aaa; }
.footer-bottom {
    border-top:1px solid rgba(255,255,255,0.05);
    padding:20px 0;
    display:flex; justify-content:space-between; align-items:center;
    flex-wrap:wrap; gap:12px;
}
.footer-bottom p   { color:#555; font-size:12px; }
.footer-bottom a   { color:#ff7b00; }
.footer-bottom-links { display:flex; gap:16px; }
.footer-bottom-links a { color:#555; font-size:12px; transition:0.2s; }
.footer-bottom-links a:hover { color:#ff7b00; }

/* ===================== BACK TO TOP ===================== */
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

/* ===================== WHATSAPP FLOAT ===================== */
.whatsapp-float {
    position:fixed; bottom:84px; right:28px; z-index:999;
    width:50px; height:50px; border-radius:50%;
    background:#25D366; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:24px; box-shadow:0 4px 20px rgba(37,211,102,0.4);
    animation:pulse-wa 2s ease-in-out infinite;
    transition:0.3s;
}
.whatsapp-float:hover { transform:scale(1.1); color:#fff; }
@keyframes pulse-wa {
    0%,100% { box-shadow:0 4px 20px rgba(37,211,102,0.4); }
    50%      { box-shadow:0 4px 40px rgba(37,211,102,0.7); }
}

/* ===================== ANIMATE ON SCROLL ===================== */
.aos { opacity:0; transform:translateY(30px); transition:all 0.6s ease; }
.aos.visible { opacity:1; transform:translateY(0); }
.aos-delay-1 { transition-delay:0.1s; }
.aos-delay-2 { transition-delay:0.2s; }
.aos-delay-3 { transition-delay:0.3s; }
.aos-delay-4 { transition-delay:0.4s; }

/* ===================== RESPONSIVE ===================== */
@media(max-width:768px){
    .section { padding:60px 0; }
    .section-title { font-size:28px; }
    .hero-title { font-size:32px; }
    .about-grid { flex-direction:column; }
    .booking-wrap { flex-direction:column; }
    .booking-map iframe { min-height:260px; }
    .hero-float-badge { display:none; }
    .hero-content { justify-content:center; }
    .hero-image-wrap { display:none; }
    .stats-grid { gap:0; }
    .stat-item { min-width:120px; }
}
</style>
</head>
<body>

<!-- Page Loader -->
<div id="page-loader">
    <div class="loader-ring"></div>
</div>

<!-- Navbar -->
<?php include 'includes/navbar.php'; ?>

<!-- ==================== HERO ==================== -->
<section class="hero" id="home">
    <div class="container">
        <div class="hero-content">
            <!-- Left Text -->
            <div class="hero-text animate__animated animate__fadeInLeft">
                <div class="hero-badge">
                    <i class="fa fa-star" style="color:#ffb347;"></i>
                    Bihar ka Favourite Café
                </div>
                <h1 class="hero-title">
                    Taste the Magic of<br>
                    <span class="highlight">Delicious Food</span>
                    <span class="brand-name">Droppers Café & Resto</span>
                </h1>
                <p class="hero-desc">
                    Freshly made meals, warm vibes, and unforgettable flavours.
                    From spicy street-style bites to creamy desserts — all under one roof.
                </p>
                <div class="hero-btns">
                    <a href="menu.php" class="btn-primary-hero">
                        <i class="fa fa-utensils"></i> Explore Menu
                    </a>
                    <a href="book_table.php" class="btn-outline-hero">
                        <i class="fa fa-calendar-check"></i> Book a Table
                    </a>
                </div>
                <div class="hero-info">
                    <div class="hero-info-item">
                        <div class="hero-info-icon">📍</div>
                        <div class="hero-info-text">
                            <p>Location</p>
                            <strong>Bheldi Road, Amnour, Bihar</strong>
                        </div>
                    </div>
                    <div class="hero-info-item">
                        <div class="hero-info-icon">⏰</div>
                        <div class="hero-info-text">
                            <p>Working Hours</p>
                            <strong>10:00 AM – 11:00 PM</strong>
                        </div>
                    </div>
                    <div class="hero-info-item">
                        <div class="hero-info-icon">📞</div>
                        <div class="hero-info-text">
                            <p>Call Us</p>
                            <strong>+91 70048 10081</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Image -->
            <div class="hero-image-wrap animate__animated animate__fadeInRight">
                <div class="hero-img-circle">
                    <img src="../assets/images/menu/pizza/Margherita-pizza.jpg" alt="Delicious Food" onerror="this.src='../assets/images/logo.png'">
                </div>
                <div class="hero-float-badge left">
                    <span class="icon">🍕</span>
                    <div class="text"><p>Fresh &amp; Hot</p><strong>Pizza Ready!</strong></div>
                </div>
                <div class="hero-float-badge right">
                    <span class="icon">⭐</span>
                    <div class="text"><p>Customer Rating</p><strong>4.9 / 5.0</strong></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================== STATS BAR ==================== -->
<div class="stats-bar">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item aos">
                <div class="stat-number"><?php echo $menu_count; ?>+</div>
                <div class="stat-label">Menu Items</div>
            </div>
            <div class="stat-item aos aos-delay-1">
                <div class="stat-number"><?php echo $order_count; ?>+</div>
                <div class="stat-label">Orders Served</div>
            </div>
            <div class="stat-item aos aos-delay-2">
                <div class="stat-number"><?php echo $cust_count; ?>+</div>
                <div class="stat-label">Happy Customers</div>
            </div>
            <div class="stat-item aos aos-delay-3">
                <div class="stat-number">18+</div>
                <div class="stat-label">Categories</div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== SERVICES ==================== -->
<section class="section section-dark2" id="services">
    <div class="container">
        <div class="text-center aos">
            <span class="section-label">What We Offer</span>
            <h2 class="section-title">Why Choose <span>Droppers Café?</span></h2>
        </div>
        <div class="services-grid">
            <div class="service-card aos aos-delay-1">
                <div class="service-icon">🍽️</div>
                <h4>Quality Food</h4>
                <p>Fresh ingredients, hygienically prepared, full of flavour and made with love in every bite.</p>
            </div>
            <div class="service-card aos aos-delay-2">
                <div class="service-icon">🛒</div>
                <h4>Easy Online Order</h4>
                <p>Browse our menu, add to cart, and place your order in just a few taps — quick and easy.</p>
            </div>
            <div class="service-card aos aos-delay-3">
                <div class="service-icon">🪑</div>
                <h4>Table Booking</h4>
                <p>Reserve your favourite table in advance. No waiting, just arrive and enjoy your meal.</p>
            </div>
            <div class="service-card aos aos-delay-4">
                <div class="service-icon">🚀</div>
                <h4>Fast Service</h4>
                <p>We value your time. Quick preparation, prompt service — because you shouldn't have to wait.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==================== ABOUT ==================== -->
<section class="section section-dark3" id="about">
    <div class="container">
        <div class="about-grid">
            <!-- Images -->
            <div class="about-images aos">
                <img class="tall" src="../assets/images/menu/pizza/tandoori-chicken-pizza.jpg" alt="food" onerror="this.src='../assets/images/logo.png'">
                <img class="short-bottom" src="../assets/images/menu/burger/Chicken-Burger.jpg" alt="food" onerror="this.src='../assets/images/logo.png'" style="margin-top:60px;">
                <img class="short" src="../assets/images/menu/momos/chicken-pan-fired-momos.jpg" alt="food" onerror="this.src='../assets/images/logo.png'">
                <img class="tall-bottom" src="../assets/images/menu/noodles/chicken-hakka-noodles.jpg" alt="food" onerror="this.src='../assets/images/logo.png'">
            </div>
            <!-- Text -->
            <div class="about-text aos aos-delay-2">
                <span class="section-label">About Us</span>
                <h2 class="section-title">Welcome to <span>Droppers Café</span></h2>
                <p>Nestled in Bheldi Road, Amnour, Bihar — Droppers Café & Resto is your go-to destination for amazing food and great vibes. We bring together the best of Indian, Chinese, and café-style cuisines under one roof.</p>
                <p>Whether you're coming in for a quick bite, a family meal, or a special celebration — we have something for everyone. Our chefs craft every dish with care, using only the freshest ingredients.</p>
                <div class="about-stats">
                    <div class="about-stat">
                        <div>
                            <div class="num"><?php echo $menu_count; ?>+</div>
                            <div class="lbl">Menu Dishes</div>
                        </div>
                    </div>
                    <div class="about-stat">
                        <div>
                            <div class="num"><?php echo $order_count; ?>+</div>
                            <div class="lbl">Orders Delivered</div>
                        </div>
                    </div>
                </div>
                <a href="about.php" class="btn-primary-hero" style="display:inline-flex; margin-top:8px;">
                    <i class="fa fa-arrow-right"></i> Know More About Us
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ==================== POPULAR MENU ==================== -->
<section class="section section-dark2" id="menu">
    <div class="container">
        <div class="text-center aos">
            <span class="section-label">Food Menu</span>
            <h2 class="section-title">Our <span>Popular Items</span></h2>
            <p class="section-sub" style="margin:0 auto;">Handpicked favourites loved by our customers. Fresh, tasty, and always satisfying!</p>
        </div>

        <div class="menu-tabs">
            <!-- Tab Nav -->
            <div class="tab-nav aos">
                <button class="tab-btn active" onclick="switchTab('meals', this)">
                    <i class="fa fa-hamburger"></i>
                    <div>
                        <div style="font-size:11px;color:#888;font-weight:400;">Most Loved</div>
                        <div>Meals & Snacks</div>
                    </div>
                </button>
                <button class="tab-btn" onclick="switchTab('bites', this)">
                    <i class="fa fa-cookie-bite"></i>
                    <div>
                        <div style="font-size:11px;color:#888;font-weight:400;">Quick</div>
                        <div>Sweet Bites</div>
                    </div>
                </button>
                <button class="tab-btn" onclick="switchTab('drinks', this)">
                    <i class="fa fa-coffee"></i>
                    <div>
                        <div style="font-size:11px;color:#888;font-weight:400;">Refreshing</div>
                        <div>Drinks & Soups</div>
                    </div>
                </button>
            </div>

            <!-- Meals Tab -->
            <div class="tab-panel active" id="tab-meals">
                <div class="menu-list">
                    <?php foreach($popular as $item):
                        $price = $item['small_price'] ? "₹{$item['small_price']}" : "₹{$item['price']}";
                    ?>
                    <div class="menu-list-item aos">
                        <img src="../assets/images/<?php echo $item['image']; ?>"
                             alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                             onerror="this.src='../assets/images/logo.png'">
                        <div class="menu-item-info">
                            <h5>
                                <span><?php echo htmlspecialchars($item['item_name']); ?>
                                    <span class="menu-item-cat"><?php echo $item['category']; ?></span>
                                </span>
                                <span class="price"><?php echo $price; ?></span>
                            </h5>
                            <small>Fresh &amp; Made to Order &nbsp; ⭐ Must Try!</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($popular)): ?>
                    <p style="color:#555; text-align:center; grid-column:1/-1; padding:40px;">No items found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bites Tab -->
            <div class="tab-panel" id="tab-bites">
                <div class="menu-list">
                    <?php foreach($snacks as $item):
                        $price = $item['small_price'] ? "₹{$item['small_price']}" : "₹{$item['price']}";
                    ?>
                    <div class="menu-list-item aos">
                        <img src="../assets/images/<?php echo $item['image']; ?>"
                             alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                             onerror="this.src='../assets/images/logo.png'">
                        <div class="menu-item-info">
                            <h5>
                                <span><?php echo htmlspecialchars($item['item_name']); ?>
                                    <span class="menu-item-cat"><?php echo $item['category']; ?></span>
                                </span>
                                <span class="price"><?php echo $price; ?></span>
                            </h5>
                            <small>Freshly Baked &amp; Sweet 🍰</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Drinks Tab -->
            <div class="tab-panel" id="tab-drinks">
                <div class="menu-list">
                    <?php foreach($drinks as $item):
                        $price = $item['small_price'] ? "₹{$item['small_price']}" : "₹{$item['price']}";
                    ?>
                    <div class="menu-list-item aos">
                        <img src="../assets/images/<?php echo $item['image']; ?>"
                             alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                             onerror="this.src='../assets/images/logo.png'">
                        <div class="menu-item-info">
                            <h5>
                                <span><?php echo htmlspecialchars($item['item_name']); ?>
                                    <span class="menu-item-cat"><?php echo $item['category']; ?></span>
                                </span>
                                <span class="price"><?php echo $price; ?></span>
                            </h5>
                            <small>Cool, Warm &amp; Refreshing ☕</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="menu-more aos">
                <a href="menu.php" class="btn-menu-more">
                    View Full Menu &nbsp; <i class="fa fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ==================== BOOK TABLE ==================== -->
<section class="section section-dark" id="booking">
    <div class="container">
        <div class="text-center aos">
            <span class="section-label">Reservation</span>
            <h2 class="section-title">Book a <span>Table Online</span></h2>
        </div>
        <div class="booking-wrap aos" style="margin-top:40px;">
            <!-- Map -->
            <div class="booking-map">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3586.917607304979!2d84.9256401!3d25.970742400000002!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3992cb55d2eb821d%3A0xbe19d4929a2613a3!2sDroppers%20Cafe%20%26%20Resto!5e0!3m2!1sen!2sin!4v1769311847759!5m2!1sen!2sin"
                    allowfullscreen loading="lazy">
                </iframe>
            </div>
            <!-- Form -->
            <div class="booking-form-wrap">
                <span class="section-label" style="font-size:11px;">Reserve Your Spot</span>
                <h3>Book Your <span style="color:#ff7b00;">Table</span></h3>
                <p>Fill in the details and we'll confirm your booking shortly.</p>
                <a href="book_table.php" style="display:block;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Your Name</label>
                        <input type="text" placeholder="e.g. Rahul Kumar" disabled style="cursor:pointer;">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" placeholder="+91 XXXXX XXXXX" disabled style="cursor:pointer;">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" disabled style="cursor:pointer;">
                    </div>
                    <div class="form-group">
                        <label>Select Table</label>
                        <select disabled style="cursor:pointer;">
                            <option>Table 1 to 10</option>
                        </select>
                    </div>
                </div>
                </a>
                <a href="book_table.php" class="btn-book" style="display:block; text-align:center;">
                    🪑 Go to Table Booking Page
                </a>
                <p style="text-align:center; font-size:12px; color:#555; margin-top:12px;">
                    📞 Or call us: <a href="tel:+917004810081" style="color:#ff7b00;">+91 70048 10081</a>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ==================== TESTIMONIALS ==================== -->
<section class="section section-dark3" id="testimonials">
    <div class="container">
        <div class="text-center aos">
            <span class="section-label">Testimonials</span>
            <h2 class="section-title">What Our <span>Customers Say</span></h2>
        </div>
        <div class="testimonials-grid">
            <?php if(!empty($feedbacks)): foreach($feedbacks as $fb):
                $stars = intval($fb['rating'] ?? 5);
                $star_html = str_repeat('★', $stars) . str_repeat('☆', 5-$stars);
                $initial = strtoupper(substr($fb['name'] ?? 'C', 0, 1));
            ?>
            <div class="testimonial-card aos">
                <div class="quote-icon"><i class="fa fa-quote-left"></i></div>
                <p>"<?php echo htmlspecialchars($fb['message'] ?? $fb['feedback'] ?? ''); ?>"</p>
                <div class="testimonial-person">
                    <div class="person-avatar"><?php echo $initial; ?></div>
                    <div>
                        <div class="person-name"><?php echo htmlspecialchars($fb['name']); ?></div>
                        <div class="person-stars"><?php echo $star_html; ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="no-feedback">
                <i class="fa fa-comment-dots"></i>
                <p>Be the first to share your experience!</p>
                <a href="feedback.php" class="btn-primary-hero" style="margin-top:16px; display:inline-flex;">
                    Leave a Feedback
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php if(!empty($feedbacks)): ?>
        <div class="menu-more" style="margin-top:32px;">
            <a href="feedback.php" class="btn-menu-more">
                Share Your Feedback &nbsp;<i class="fa fa-heart" style="color:#e74c3c;"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ==================== FOOTER ==================== -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand -->
            <div class="footer-brand">
                <div class="logo">
                    <img src="../assets/images/droppers-logo.png" alt="Droppers Café">
                    <span>Droppers Café</span>
                </div>
                <p>Your favourite café in Amnour, Bihar. Fresh food, great vibes, and memories that last forever.</p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="https://wa.me/917004810081" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    <a href="tel:+917004810081"><i class="fa fa-phone"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-col">
                <h4>Quick Links</h4>
                <a href="index.php">🏠 Home</a>
                <a href="menu.php">🍽️ Menu</a>
                <a href="book_table.php">🪑 Book a Table</a>
                <a href="cart.php">🛒 Cart</a>
                <a href="track_order.php">📍 Track Order</a>
                <a href="feedback.php">⭐ Feedback</a>
            </div>

            <!-- Contact -->
            <div class="footer-col">
                <h4>Contact Us</h4>
                <div class="footer-contact-item">
                    <i class="fa fa-map-marker-alt"></i>
                    <span>Bheldi Road, Amnour, Saran, Bihar — 841424</span>
                </div>
                <div class="footer-contact-item">
                    <i class="fa fa-phone-alt"></i>
                    <span><a href="tel:+917004810081" style="color:inherit;">+91 70048 10081</a></span>
                </div>
                <div class="footer-contact-item">
                    <i class="fab fa-whatsapp" style="color:#25D366;"></i>
                    <span><a href="https://wa.me/917004810081" style="color:inherit;" target="_blank">Chat on WhatsApp</a></span>
                </div>
                <div class="footer-contact-item">
                    <i class="fa fa-envelope"></i>
                    <span>dropperscafe.auth@gmail.com</span>
                </div>
            </div>

            <!-- Hours -->
            <div class="footer-col">
                <h4>Opening Hours</h4>
                <div class="footer-hours" style="line-height:2;">
                    <div><strong>Monday – Saturday</strong><br><span>10:00 AM – 11:00 PM</span></div>
                    <br>
                    <div><strong>Sunday</strong><br><span>11:00 AM – 10:00 PM</span></div>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p>© <?php echo date('Y'); ?> <a href="#">Droppers Café & Resto</a>. All rights reserved.</p>
            <div class="footer-bottom-links">
                <a href="privacy-policy.php">Privacy Policy</a>
                <a href="refund-policy.php">Refund Policy</a>
                <a href="disclaimer.php">Disclaimer</a>
            </div>
        </div>
    </div>
</footer>

<!-- WhatsApp Float Button -->
<a href="https://wa.me/917004810081" class="whatsapp-float" target="_blank" title="Order on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>

<!-- Back to Top -->
<a href="#home" class="back-to-top" id="backToTop">
    <i class="fa fa-arrow-up"></i>
</a>

<script>
// ---- Page Loader ----
window.addEventListener('load', () => {
    const loader = document.getElementById('page-loader');
    loader.style.opacity = '0';
    setTimeout(() => loader.style.display = 'none', 500);
});

// ---- Menu Tabs ----
function switchTab(name, btn){
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

// ---- Back to Top ----
const btt = document.getElementById('backToTop');
window.addEventListener('scroll', () => {
    btt.classList.toggle('show', window.scrollY > 300);
});

// ---- Animate on Scroll ----
const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if(e.isIntersecting) {
            e.target.classList.add('visible');
            observer.unobserve(e.target);
        }
    });
}, { threshold: 0.12 });
document.querySelectorAll('.aos').forEach(el => observer.observe(el));
</script>

</body>
</html>