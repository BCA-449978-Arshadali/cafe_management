<?php
session_start();
include "../config.php";

// Sirf active aur valid offers fetch karo
$today   = date('Y-m-d');
$offers  = mysqli_query($conn, "
    SELECT * FROM offers
    WHERE is_active = 1
      AND (valid_until IS NULL OR valid_until >= '$today')
    ORDER BY created_at DESC
");
$total = mysqli_num_rows($offers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offers & Deals — Droppers Café</title>
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { background:#111; color:#fff; font-family:'Poppins',sans-serif; margin:0; }

/* Hero */
.offers-hero {
    background: linear-gradient(135deg, rgba(255,107,0,0.18), rgba(255,140,26,0.06));
    border-bottom: 1px solid rgba(255,123,0,0.15);
    padding: 48px 20px 40px;
    text-align: center;
}
.offers-hero .badge {
    display: inline-block;
    background: rgba(255,123,0,0.15);
    border: 1px solid rgba(255,123,0,0.3);
    color: #ff7b00; padding: 6px 18px;
    border-radius: 20px; font-size: 13px; font-weight: 600;
    margin-bottom: 16px;
}
.offers-hero h1 {
    font-size: clamp(28px, 5vw, 44px);
    font-weight: 800; margin: 0 0 12px;
}
.offers-hero h1 span { color: #ff7b00; }
.offers-hero p { color: #aaa; font-size: 15px; max-width: 500px; margin: 0 auto; }

/* Grid */
.offers-section { max-width: 1100px; margin: 0 auto; padding: 48px 20px 80px; }
.offers-count { font-size: 13px; color: #666; margin-bottom: 24px; }
.offers-count span { color: #ff7b00; font-weight: 700; }

.offers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}

.offer-card {
    background: linear-gradient(135deg, #1a1a1a, #161616);
    border: 1px solid #2a2a2a;
    border-radius: 16px; overflow: hidden;
    transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
    position: relative;
}
.offer-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255,123,0,0.4);
    box-shadow: 0 12px 40px rgba(255,123,0,0.12);
}

/* Expiry ribbon */
.offer-ribbon {
    position: absolute; top: 12px; right: 12px;
    background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);
    padding: 4px 12px; border-radius: 20px;
    font-size: 11px; color: #aaa; border: 1px solid rgba(255,255,255,0.1);
}
.offer-ribbon.expiring { color: #e74c3c; border-color: rgba(231,76,60,0.3); }

.offer-img {
    width: 100%; height: 180px;
    object-fit: cover; display: block;
}
.offer-img-placeholder {
    width: 100%; height: 180px;
    background: linear-gradient(135deg, #1e1e1e, #252525);
    display: flex; align-items: center; justify-content: center;
    font-size: 60px;
}

.offer-body { padding: 20px; }

.offer-discount {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,123,0,0.12);
    border: 1px solid rgba(255,123,0,0.3);
    color: #ff7b00; padding: 5px 14px;
    border-radius: 20px; font-size: 14px; font-weight: 700;
    margin-bottom: 12px;
}

.offer-title {
    font-size: 17px; font-weight: 700;
    margin-bottom: 8px; line-height: 1.3;
}
.offer-desc {
    font-size: 13px; color: #888;
    line-height: 1.6; margin-bottom: 16px;
}

.offer-code-wrap {
    background: #111; border: 1px dashed rgba(255,123,0,0.4);
    border-radius: 10px; padding: 12px 16px;
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px; gap: 10px;
}
.offer-code-wrap .label { font-size: 11px; color: #666; margin-bottom: 2px; }
.offer-code-wrap .code {
    font-family: monospace; font-size: 17px;
    font-weight: 700; color: #ff7b00; letter-spacing: 2px;
}
.btn-copy {
    background: rgba(255,123,0,0.12);
    border: 1px solid rgba(255,123,0,0.3);
    color: #ff7b00; padding: 7px 14px;
    border-radius: 8px; font-size: 12px; font-weight: 600;
    cursor: pointer; transition: 0.2s; white-space: nowrap;
    font-family: 'Poppins', sans-serif;
}
.btn-copy:hover { background: rgba(255,123,0,0.25); }
.btn-copy.copied { background: rgba(39,174,96,0.15); border-color: rgba(39,174,96,0.4); color: #27ae60; }

.offer-validity {
    font-size: 12px; color: #555;
    display: flex; align-items: center; gap: 6px;
}

.btn-order {
    display: block; width: 100%;
    background: #ff7b00; color: #fff;
    padding: 12px; border-radius: 10px;
    text-align: center; font-weight: 700;
    font-size: 14px; text-decoration: none;
    margin-top: 14px; transition: 0.2s;
}
.btn-order:hover { background: #ff9500; }

/* Empty state */
.empty-state {
    text-align: center; padding: 80px 20px;
    grid-column: 1 / -1;
}
.empty-state i { font-size: 56px; color: #333; margin-bottom: 16px; display: block; }
.empty-state h3 { font-size: 18px; color: #555; margin-bottom: 8px; }
.empty-state p  { color: #444; font-size: 14px; }

/* Toast */
.toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(60px);
    background: #27ae60; color: #fff;
    padding: 12px 24px; border-radius: 10px;
    font-size: 14px; font-weight: 600;
    transition: transform 0.3s; z-index: 9999;
    display: flex; align-items: center; gap: 8px;
}
.toast.show { transform: translateX(-50%) translateY(0); }

@media (max-width: 600px) {
    .offers-hero { padding: 32px 16px 28px; }
    .offers-section { padding: 32px 16px 60px; }
    .offer-img, .offer-img-placeholder { height: 150px; }
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Hero -->
<div class="offers-hero">
    <div class="badge">🎁 Latest Deals</div>
    <h1>Exclusive <span>Offers</span> for You</h1>
    <p>Grab the best deals at Droppers Café — save more, enjoy more!</p>
</div>

<!-- Offers -->
<div class="offers-section">
    <div class="offers-count">
        <span><?= $total ?></span> active offer<?= $total != 1 ? 's' : '' ?> available
    </div>

    <div class="offers-grid">
        <?php if($total == 0): ?>
        <div class="empty-state">
            <i class="fa fa-tags"></i>
            <h3>No offers available right now</h3>
            <p>Check back soon — exciting deals are coming! 🤞</p>
        </div>
        <?php else: while($offer = mysqli_fetch_assoc($offers)):
            // Check expiry
            $expiring_soon = false;
            $days_left     = null;
            if(!empty($offer['valid_until'])){
                $days_left    = (strtotime($offer['valid_until']) - strtotime($today)) / 86400;
                $expiring_soon = $days_left <= 3;
            }
        ?>
        <div class="offer-card">

            <!-- Expiry Ribbon -->
            <?php if(!empty($offer['valid_until'])): ?>
            <div class="offer-ribbon <?= $expiring_soon ? 'expiring' : '' ?>">
                <?php if($expiring_soon): ?>
                    ⚠️ <?= ceil($days_left) ?> day<?= ceil($days_left) != 1 ? 's' : '' ?> left!
                <?php else: ?>
                    Valid till <?= date('d M', strtotime($offer['valid_until'])) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Image -->
            <?php if(!empty($offer['image']) && file_exists("../assets/images/".$offer['image'])): ?>
                <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($offer['image']) ?>" class="offer-img" alt="offer">
            <?php else: ?>
                <div class="offer-img-placeholder">🎁</div>
            <?php endif; ?>

            <div class="offer-body">
                <!-- Discount Badge -->
                <div class="offer-discount">
                    <i class="fa fa-tag"></i>
                    <?php if($offer['discount_type'] == 'percent'): ?>
                        <?= intval($offer['discount_value']) ?>% OFF
                    <?php else: ?>
                        ₹<?= number_format($offer['discount_value'], 0) ?> OFF
                    <?php endif; ?>
                </div>

                <div class="offer-title"><?= htmlspecialchars($offer['title']) ?></div>
                <div class="offer-desc"><?= nl2br(htmlspecialchars($offer['description'])) ?></div>

                <!-- Promo Code -->
                <?php if(!empty($offer['promo_code'])): ?>
                <div class="offer-code-wrap">
                    <div>
                        <div class="label">🏷️ Promo Code</div>
                        <div class="code"><?= htmlspecialchars($offer['promo_code']) ?></div>
                    </div>
                    <button class="btn-copy" onclick="copyCode('<?= htmlspecialchars($offer['promo_code']) ?>', this)">
                        <i class="fa fa-copy"></i> Copy
                    </button>
                </div>
                <?php endif; ?>

                <!-- Validity -->
                <?php if(!empty($offer['valid_from']) || !empty($offer['valid_until'])): ?>
                <div class="offer-validity">
                    <i class="fa fa-calendar" style="color:#ff7b00;"></i>
                    <?= !empty($offer['valid_from'])  ? date('d M Y', strtotime($offer['valid_from']))  : 'Now' ?>
                    →
                    <?= !empty($offer['valid_until']) ? date('d M Y', strtotime($offer['valid_until'])) : 'No Expiry' ?>
                </div>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>/customer/menu.php" class="btn-order">
                    🛒 Order Now & Save!
                </a>
            </div>
        </div>
        <?php endwhile; endif; ?>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast">
    <i class="fa fa-check-circle"></i> <span id="toastMsg">Code copied!</span>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function copyCode(code, btn) {
    navigator.clipboard.writeText(code).then(function() {
        btn.innerHTML = '<i class="fa fa-check"></i> Copied!';
        btn.classList.add('copied');
        showToast('✅ "' + code + '" copied to clipboard!');
        setTimeout(function() {
            btn.innerHTML = '<i class="fa fa-copy"></i> Copy';
            btn.classList.remove('copied');
        }, 2500);
    });
}

function showToast(msg) {
    var toast = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    toast.classList.add('show');
    setTimeout(function() { toast.classList.remove('show'); }, 3000);
}
</script>
</body>
</html>