<?php
// ✅ modules.php load karo — navbar links automatically aayenge
$modules_path = dirname(__DIR__, 2) . '/modules.php';
if(file_exists($modules_path) && !isset($CUSTOMER_NAV)){
    include $modules_path;
}

if(session_status() === PHP_SESSION_NONE) session_start();
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- ===== PAGE LOADER ===== -->
<style>
#dc-loader {
    position:fixed; inset:0; z-index:99999;
    background:#0d0d0d;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    gap:20px;
    transition:opacity 0.5s ease, visibility 0.5s ease;
}
#dc-loader.hide { opacity:0; visibility:hidden; }

.loader-logo {
    width:100px; height:100px; border-radius:50%;
    border:3px solid rgba(255,123,0,0.3); padding:6px;
    animation:logoPulse 1.4s ease-in-out infinite;
}
.loader-logo img {
    width:100%; height:100%; border-radius:50%;
    object-fit:contain; background:#fff; padding:6px;
}
@keyframes logoPulse {
    0%   { opacity:0.3; transform:scale(0.92); box-shadow:0 0 0px rgba(255,123,0,0); }
    50%  { opacity:1;   transform:scale(1.05); box-shadow:0 0 30px rgba(255,123,0,0.4); }
    100% { opacity:0.3; transform:scale(0.92); box-shadow:0 0 0px rgba(255,123,0,0); }
}
.loader-text {
    font-family:'Poppins',sans-serif; font-size:15px; font-weight:600;
    color:#ff7b00; letter-spacing:1px;
    animation:textFade 1.4s ease-in-out infinite;
}
@keyframes textFade { 0%,100%{opacity:0.3;} 50%{opacity:1;} }

.loader-dots { display:flex; gap:8px; }
.loader-dots span {
    width:8px; height:8px; border-radius:50%;
    background:#ff7b00; opacity:0.3;
    animation:dotBounce 1.2s ease-in-out infinite;
}
.loader-dots span:nth-child(2){ animation-delay:0.2s; }
.loader-dots span:nth-child(3){ animation-delay:0.4s; }
@keyframes dotBounce {
    0%,100%{ opacity:0.2; transform:scale(0.8); }
    50%    { opacity:1;   transform:scale(1.2); }
}
</style>

<div id="dc-loader">
    <div class="loader-logo">
        <img src="<?= BASE_URL ?>/assets/images/droppers-logo.png" alt="Droppers Cafe">
    </div>
    <div class="loader-text">Droppers Cafe</div>
    <div class="loader-dots"><span></span><span></span><span></span></div>
</div>

<script>
window.addEventListener('load', function(){
    var loader = document.getElementById('dc-loader');
    setTimeout(function(){
        loader.classList.add('hide');
        setTimeout(function(){ loader.style.display='none'; }, 500);
    }, 800);
});
</script>
<!-- ===== END LOADER ===== -->


<?php
$cart_count = 0;
if(!empty($_SESSION['cart'])){
    foreach($_SESSION['cart'] as $item){
        $cart_count += $item['qty'];
    }
}
$current = basename($_SERVER['PHP_SELF']);

// ✅ Profile pic — session se lo, nahi hai toh DB se fetch karo
if(isset($_SESSION['customer_id']) && empty($_SESSION['customer_pic'])){
    if(!isset($conn)){
        include dirname(__DIR__) . '/../config.php';
    }
    $np = $conn->prepare("SELECT profile_pic FROM customers WHERE id=?");
    $np->bind_param("i", $_SESSION['customer_id']);
    $np->execute();
    $nr = $np->get_result()->fetch_assoc();
    $_SESSION['customer_pic'] = !empty($nr['profile_pic'])
        ? BASE_URL . '/' . $nr['profile_pic']
        : '';
}
$nav_pic = $_SESSION['customer_pic'] ?? '';
?>

<style>
* { box-sizing: border-box; }

.navbar {
    background: rgba(10, 12, 10, 0.45);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    padding: 0 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 70px;
    position: sticky;
    top: 0;
    z-index: 999;
    box-shadow: 0 4px 32px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.07);
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.navbar-brand {
    display: flex; align-items: center; gap: 12px;
    text-decoration: none; flex-shrink: 0;
}
.navbar-brand img {
    width: 44px; height: 44px; border-radius: 50%;
    object-fit: contain; background: #fff;
    padding: 4px; border: 2px solid #ff7b00;
}
.navbar-brand span {
    font-size: 20px; font-weight: 700;
    color: #fff; font-family: 'Poppins', sans-serif;
}
.navbar-links { display: flex; align-items: center; gap: 6px; }
.navbar-links a {
    color: rgba(255,255,255,0.75); text-decoration: none;
    font-size: 14px; font-weight: 500; padding: 8px 16px;
    border-radius: 8px; transition: all 0.2s;
    font-family: 'Poppins', sans-serif; white-space: nowrap;
    position: relative;
}
.navbar-links a::after {
    content: ''; position: absolute; bottom: 3px; left: 50%; right: 50%;
    height: 2px; background: #ff7b00; border-radius: 2px;
    transition: all 0.25s ease;
}
.navbar-links a:hover { background: rgba(255,255,255,0.07); color: #fff; }
.navbar-links a:hover::after, .navbar-links a.active::after { left: 16px; right: 16px; }
.navbar-links a.active { background: rgba(255,123,0,0.1); color: #ff7b00; }

.navbar-right { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }

.cart-btn {
    display: flex; align-items: center; gap: 8px;
    background: rgba(255,123,0,0.1);
    border: 1px solid rgba(255,123,0,0.25);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #ff7b00 !important; padding: 8px 18px !important;
    border-radius: 10px !important; font-weight: 600 !important;
    text-decoration: none; font-size: 14px;
    font-family: 'Poppins', sans-serif; white-space: nowrap;
    transition: all 0.2s;
    box-shadow: 0 2px 12px rgba(255,123,0,0.1), inset 0 1px 0 rgba(255,255,255,0.06);
}
.cart-btn:hover {
    background: rgba(255,123,0,0.2) !important;
    box-shadow: 0 4px 20px rgba(255,123,0,0.2), inset 0 1px 0 rgba(255,255,255,0.1);
    transform: translateY(-1px);
}
.cart-badge {
    background: #ff7b00; color: #fff; border-radius: 50%;
    width: 20px; height: 20px; font-size: 11px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}

.user-avatar {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, #ff6b00, #ff8c1a);
    border-radius: 50%; display: flex; align-items: center;
    justify-content: center; font-size: 14px; font-weight: 700;
    color: #fff; flex-shrink: 0;
    border: 2px solid rgba(255,123,0,0.4);
    overflow: hidden;
    transition: all 0.2s;
    box-shadow: 0 0 0 0 rgba(255,123,0,0);
}
.user-avatar:hover {
    border-color: #ff7b00;
    box-shadow: 0 0 0 3px rgba(255,123,0,0.2);
    transform: scale(1.05);
}
.user-avatar img {
    width: 100%; height: 100%;
    object-fit: cover; border-radius: 50%;
    display: block;
}

.user-name { color: #fff; font-weight: 600; font-size: 14px; white-space: nowrap; }
.logout-btn {
    background: rgba(231,76,60,0.08) !important;
    border: 1px solid rgba(231,76,60,0.2) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #e74c3c !important; padding: 7px 14px !important;
    border-radius: 8px !important; font-size: 13px !important;
    font-weight: 600 !important; text-decoration: none;
    font-family: 'Poppins', sans-serif; white-space: nowrap;
    transition: all 0.2s;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);
}
.logout-btn:hover {
    background: rgba(231,76,60,0.18) !important;
    transform: translateY(-1px);
}
.login-btn {
    background: #ff7b00 !important; color: #fff !important;
    padding: 8px 18px !important; border-radius: 8px !important;
    font-weight: 600 !important; font-size: 14px !important;
    text-decoration: none; white-space: nowrap;
}

/* Hamburger */
.hamburger {
    display: none; flex-direction: column; gap: 5px;
    cursor: pointer; padding: 8px;
    background: rgba(255,255,255,0.06);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-radius: 8px; border: 1px solid rgba(255,255,255,0.12);
    transition: 0.2s;
}
.hamburger:hover { background: rgba(255,255,255,0.1); }
.hamburger span {
    display: block; width: 22px; height: 2px;
    background: #fff; border-radius: 2px; transition: 0.3s;
}
.hamburger.open span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
.hamburger.open span:nth-child(2) { opacity: 0; }
.hamburger.open span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }

/* Mobile Menu */
.mobile-menu {
    display: none;
    position: fixed;
    top: 70px; left: 0; right: 0;
    background: rgba(8, 12, 8, 0.82);
    backdrop-filter: blur(24px) saturate(160%);
    -webkit-backdrop-filter: blur(24px) saturate(160%);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    padding: 8px 20px 16px;
    z-index: 998;
    box-shadow: 0 12px 40px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.06);
    max-height: calc(100vh - 70px);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}
.mobile-menu.open { display: block; }
.mobile-menu a {
    display: flex; align-items: center; gap: 10px;
    color: rgba(255,255,255,0.8);
    text-decoration: none; font-size: 15px; font-weight: 500;
    padding: 13px 8px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    font-family: 'Poppins', sans-serif; transition: 0.2s;
}
.mobile-menu a:last-child { border-bottom: none; }
.mobile-menu a:hover, .mobile-menu a.active { color: #ff7b00; }
.mobile-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 6px 0; }

.mob-avatar {
    width: 30px; height: 30px; border-radius: 50%;
    background: linear-gradient(135deg, #ff6b00, #ff8c1a);
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 13px; color: #fff;
    border: 1px solid rgba(255,123,0,0.5);
    overflow: hidden; flex-shrink: 0;
}
.mob-avatar img { width:100%; height:100%; object-fit:cover; border-radius:50%; }

/* Responsive */
@media (max-width: 768px) {
    .navbar { padding: 0 16px; }
    .navbar-links { display: none; }
    .hamburger { display: flex; }
    .user-name { display: none; }
    .logout-btn { padding: 6px 10px !important; font-size: 12px !important; }
    .cart-btn { padding: 7px 12px !important; font-size: 13px; }
    .navbar-brand span { font-size: 17px; }
}
@media (max-width: 380px) {
    .navbar-brand span { display: none; }
    .navbar-brand img { width: 36px; height: 36px; }
}
</style>

<nav class="navbar">
    <a href="<?= BASE_URL ?>/customer/index.php" class="navbar-brand">
        <img src="<?= BASE_URL ?>/assets/images/droppers-logo.png" alt="Logo">
        <span>Droppers Café</span>
    </a>

    <!-- ✅ Desktop Nav — modules.php se auto generate -->
    <div class="navbar-links">
        <?php render_customer_nav(); ?>
    </div>

    <div class="navbar-right">
        <a href="<?= BASE_URL ?>/customer/cart.php" class="cart-btn">
            🛒 Cart
            <?php if($cart_count > 0): ?>
            <span class="cart-badge"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>

        <?php if(isset($_SESSION['customer_id'])): ?>
            <a href="<?= BASE_URL ?>/customer/profile.php" style="text-decoration:none;display:flex;align-items:center;gap:8px;">
                <div class="user-avatar">
                    <?php if(!empty($nav_pic)): ?>
                        <img src="<?= htmlspecialchars($nav_pic) ?>?v=<?= time() ?>" alt="avatar">
                    <?php else: ?>
                        <?= strtoupper(substr($_SESSION['customer_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <span class="user-name"><?= htmlspecialchars($_SESSION['customer_name']) ?></span>
            </a>
            <a href="<?= BASE_URL ?>/customer/logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/customer/login.php" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:14px;padding:8px 14px;">Login</a>
            <a href="<?= BASE_URL ?>/customer/register.php" class="login-btn" style="text-decoration:none;">Register</a>
        <?php endif; ?>

        <!-- Hamburger -->
        <div class="hamburger" id="hamburger" onclick="toggleMenu()">
            <span></span><span></span><span></span>
        </div>
    </div>
</nav>

<!-- ✅ Mobile Menu — modules.php se auto generate -->
<div class="mobile-menu" id="mobileMenu">
    <?php render_customer_nav(); ?>
    <div class="mobile-divider"></div>
    <?php if(isset($_SESSION['customer_id'])): ?>
    <a href="<?= BASE_URL ?>/customer/profile.php">
        <span class="mob-avatar">
            <?php if(!empty($nav_pic)): ?>
                <img src="<?= htmlspecialchars($nav_pic) ?>?v=<?= time() ?>" alt="avatar">
            <?php else: ?>
                <?= strtoupper(substr($_SESSION['customer_name'], 0, 1)) ?>
            <?php endif; ?>
        </span>
        <?= htmlspecialchars($_SESSION['customer_name']) ?>
    </a>
    <a href="<?= BASE_URL ?>/customer/logout.php" style="color:#e74c3c;">🚪 Logout</a>
    <?php else: ?>
    <a href="<?= BASE_URL ?>/customer/login.php">🔑 Login</a>
    <a href="<?= BASE_URL ?>/customer/register.php">📝 Register</a>
    <?php endif; ?>
</div>

<script>
function toggleMenu(){
    var ham  = document.getElementById('hamburger');
    var menu = document.getElementById('mobileMenu');
    ham.classList.toggle('open');
    menu.classList.toggle('open');

    if(menu.classList.contains('open')){
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

// Link click par menu band karo
document.querySelectorAll('.mobile-menu a').forEach(function(a){
    a.addEventListener('click', function(){
        document.getElementById('hamburger').classList.remove('open');
        document.getElementById('mobileMenu').classList.remove('open');
        document.body.style.overflow = '';
    });
});

// Outside click par band karo
document.addEventListener('click', function(e){
    if(!e.target.closest('.navbar') && !e.target.closest('.mobile-menu')){
        document.getElementById('hamburger').classList.remove('open');
        document.getElementById('mobileMenu').classList.remove('open');
        document.body.style.overflow = '';
    }
});
</script>