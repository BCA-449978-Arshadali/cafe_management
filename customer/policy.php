<?php
// customer/policy.php
ob_start();
include '../config.php';
require_once 'includes/auth-check.php';

// Policy type URL se lo
$type = trim($_GET['type'] ?? 'return_policy');
$allowed = ['return_policy','refund_policy','privacy_policy','disclaimer'];
if(!in_array($type, $allowed)) $type = 'return_policy';

// DB se fetch karo
$t   = mysqli_real_escape_string($conn, $type);
$row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM site_policies WHERE policy_key='$t' LIMIT 1"
));

// Agar DB mein nahi toh 404 jaisa show
if(!$row || empty($row['content'])){
    $row = [
        'title'      => ucwords(str_replace('_', ' ', $type)),
        'icon'       => '📄',
        'content'    => '<p style="color:#888;">This policy has not been set yet. Please check back later.</p>',
        'updated_at' => null,
    ];
}

// All policies for sidebar nav
$all_policies_res = mysqli_query($conn, "SELECT policy_key, title, icon FROM site_policies ORDER BY id ASC");
$all_policies = [];
while($p = mysqli_fetch_assoc($all_policies_res)) $all_policies[] = $p;
if(empty($all_policies)){
    $all_policies = [
        ['policy_key'=>'return_policy',  'title'=>'Return Policy',  'icon'=>'↩️'],
        ['policy_key'=>'refund_policy',  'title'=>'Refund Policy',  'icon'=>'💰'],
        ['policy_key'=>'privacy_policy', 'title'=>'Privacy Policy', 'icon'=>'🔒'],
        ['policy_key'=>'disclaimer',     'title'=>'Disclaimer',     'icon'=>'⚠️'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($row['title']) ?> — Droppers Café</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
body { background:#0d0d0d; color:#fff; font-family:'Poppins',sans-serif; }
a { text-decoration:none; }
.container { max-width:1100px; margin:0 auto; padding:0 24px; }

/* Hero */
.page-hero {
    background: linear-gradient(135deg,#0a0a0a 0%,#0f0f0a 50%,#0a0a0a 100%);
    padding:70px 0 44px;
    border-bottom:1px solid rgba(255,123,0,0.1);
    position:relative; overflow:hidden;
}
.page-hero::before {
    content:''; position:absolute; inset:0;
    background: radial-gradient(ellipse at 20% 50%,rgba(255,123,0,0.07) 0%,transparent 60%);
}
.page-hero .container { position:relative; z-index:2; }
.breadcrumb {
    display:flex; align-items:center; gap:6px;
    font-size:12px; color:#666; margin-bottom:14px; flex-wrap:wrap;
}
.breadcrumb a { color:#666; transition:0.2s; }
.breadcrumb a:hover { color:#ff7b00; }
.breadcrumb i { font-size:10px; }
.page-hero h1 { font-size:clamp(26px,4vw,44px); font-weight:800; margin-bottom:8px; }
.page-hero h1 span { color:#ff7b00; }
.page-hero-meta { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.meta-badge {
    display:inline-flex; align-items:center; gap:6px;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.2);
    padding:5px 14px; border-radius:30px; font-size:12px; color:#ff7b00; font-weight:600;
}
.meta-updated { font-size:12px; color:#555; }

/* Layout */
.policy-section { padding:32px 0 80px; }
.policy-layout {
    display:grid; grid-template-columns:240px 1fr;
    gap:28px; align-items:start;
}

/* Policy Nav Sidebar */
.policy-nav {
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:16px; overflow:hidden; position:sticky; top:90px;
}
.policy-nav-header {
    padding:14px 18px;
    border-bottom:1px solid rgba(255,255,255,0.06);
    font-size:11px; font-weight:700; text-transform:uppercase;
    letter-spacing:0.8px; color:#555;
}
.policy-nav a {
    display:flex; align-items:center; gap:10px;
    padding:13px 18px;
    border-bottom:1px solid rgba(255,255,255,0.04);
    color:#777; font-size:13px; font-weight:500;
    transition:0.2s;
}
.policy-nav a:last-child { border-bottom:none; }
.policy-nav a:hover { color:#fff; background:rgba(255,255,255,0.03); }
.policy-nav a.active {
    color:#ff7b00; background:rgba(255,123,0,0.08);
    border-left:3px solid #ff7b00;
}
.policy-nav a span.p-icon { font-size:16px; width:20px; text-align:center; }

/* Policy Content Card */
.policy-content-card {
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:20px; padding:40px;
}

/* Prose styling */
.policy-prose { line-height:1.8; }
.policy-prose h2 {
    font-size:22px; font-weight:800; color:#fff;
    margin:0 0 18px; padding-bottom:12px;
    border-bottom:1px solid rgba(255,123,0,0.2);
}
.policy-prose h3 {
    font-size:16px; font-weight:700; color:#fff;
    margin:28px 0 10px;
}
.policy-prose p {
    color:#888; font-size:14px; margin-bottom:14px;
}
.policy-prose ul, .policy-prose ol {
    color:#888; font-size:14px;
    margin:0 0 14px 20px; line-height:2;
}
.policy-prose li::marker { color:#ff7b00; }
.policy-prose strong { color:#ccc; }
.policy-prose a { color:#ff7b00; }

/* Back button */
.back-btn {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08);
    color:#888; padding:9px 18px; border-radius:10px;
    font-size:13px; font-weight:600; transition:0.2s; margin-top:28px;
}
.back-btn:hover { border-color:#ff7b00; color:#ff7b00; }

/* Responsive */
@media(max-width:768px){
    .policy-layout { grid-template-columns:1fr; }
    .policy-nav { position:static; display:flex; flex-wrap:wrap; border-radius:12px; }
    .policy-nav a { flex:1 1 45%; border:none; padding:10px 14px; }
    .policy-nav-header { width:100%; }
    .policy-content-card { padding:24px 18px; }
}
@media(max-width:480px){
    .policy-nav a { flex:1 1 100%; }
    .page-hero { padding:52px 0 30px; }
}
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<!-- Hero -->
<div class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <i class="fa fa-chevron-right"></i>
            <span><?= htmlspecialchars($row['title']) ?></span>
        </div>
        <h1><?= $row['icon'] ?> <span><?= htmlspecialchars($row['title']) ?></span></h1>
        <div class="page-hero-meta">
            <span class="meta-badge"><i class="fa fa-file-lines fa-xs"></i> Legal Document</span>
            <?php if(!empty($row['updated_at'])): ?>
            <span class="meta-updated">
                <i class="fa fa-clock fa-xs"></i>
                Last updated: <?= date('d M Y', strtotime($row['updated_at'])) ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="policy-section">
    <div class="container">
        <div class="policy-layout">

            <!-- Sidebar Nav -->
            <div class="policy-nav">
                <div class="policy-nav-header">📋 All Policies</div>
                <?php foreach($all_policies as $p): ?>
                <a href="policy.php?type=<?= $p['policy_key'] ?>"
                   class="<?= $p['policy_key'] === $type ? 'active' : '' ?>">
                    <span class="p-icon"><?= $p['icon'] ?></span>
                    <?= htmlspecialchars($p['title']) ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Content -->
            <div class="policy-content-card">
                <div class="policy-prose">
                    <?= $row['content'] ?>
                </div>

                <a href="index.php" class="back-btn">
                    <i class="fa fa-arrow-left fa-xs"></i> Back to Home
                </a>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<a href="https://www.instagram.com/dropperscafe" class="instagram-float" target="_blank">
    <i class="fab fa-instagram"></i>
</a>
<a href="https://wa.me/917004810081" class="whatsapp-float" target="_blank">
    <i class="fab fa-whatsapp"></i>
</a>
<a href="#" class="back-to-top" id="backToTop"><i class="fa fa-arrow-up"></i></a>

<script>
const btt = document.getElementById('backToTop');
window.addEventListener('scroll', () => btt.classList.toggle('show', window.scrollY > 300));
</script>
</body>
</html>