<?php
include 'includes/auth.php';
include 'includes/db.php';

// Toggle active
if(isset($_GET['toggle'])) {
    $id  = intval($_GET['toggle']);
    $cur = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active FROM offers WHERE id=$id"))['is_active'];
    $new = $cur ? 0 : 1;
    mysqli_query($conn, "UPDATE offers SET is_active=$new WHERE id=$id");
    header("Location: view_offers.php"); exit;
}

// Delete
if(isset($_GET['delete'])) {
    $id  = intval($_GET['delete']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM offers WHERE id=$id"));
    if(!empty($row['image']) && file_exists("../assets/images/".$row['image'])) {
        unlink("../assets/images/".$row['image']);
    }
    mysqli_query($conn, "DELETE FROM offers WHERE id=$id");
    header("Location: view_offers.php"); exit;
}

$offers = mysqli_query($conn, "SELECT * FROM offers ORDER BY created_at DESC");
$total  = mysqli_num_rows($offers);
$active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM offers WHERE is_active=1"))['c'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Offers — Droppers Café Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.stats-row { display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
.stat-mini {
    background:var(--bg2); border:1px solid var(--border);
    border-radius:var(--radius-sm); padding:16px 24px;
    display:flex; align-items:center; gap:12px;
}
.stat-mini i { font-size:22px; color:var(--orange); }
.stat-mini .val { font-size:22px; font-weight:700; }
.stat-mini .lbl { font-size:12px; color:var(--text-muted); }

.offers-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:20px; }
.offer-card {
    background:var(--bg2); border-radius:var(--radius);
    border:1px solid var(--border); overflow:hidden;
    transition:border-color 0.2s;
}
.offer-card:hover { border-color:var(--orange-bd); }
.offer-card.inactive { opacity:0.5; }
.offer-img {
    width:100%; height:160px; object-fit:cover;
    background:var(--bg3); display:block;
}
.offer-img-placeholder {
    width:100%; height:160px;
    background:linear-gradient(135deg, var(--bg3), var(--bg4));
    display:flex; align-items:center; justify-content:center;
    font-size:48px;
}
.offer-body { padding:18px; }
.offer-title { font-size:15px; font-weight:700; margin-bottom:6px; }
.offer-desc  { font-size:13px; color:var(--text-muted); margin-bottom:12px; line-height:1.5; }
.offer-badge {
    display:inline-flex; align-items:center; gap:6px;
    background:var(--orange-bg); border:1px solid var(--orange-bd);
    color:var(--orange); padding:4px 12px; border-radius:20px;
    font-size:13px; font-weight:700; margin-bottom:8px;
}
.offer-code {
    font-size:12px; color:var(--text-muted); margin-bottom:10px;
}
.offer-code span {
    background:var(--bg3); padding:3px 10px; border-radius:6px;
    border:1px solid var(--border); font-family:monospace;
    font-size:13px; color:var(--text); letter-spacing:1px;
}
.offer-dates { font-size:12px; color:var(--text-dim); margin-bottom:14px; }
.offer-actions { display:flex; gap:8px; }
.btn-sm {
    padding:7px 14px; border-radius:8px; font-size:12px;
    font-weight:600; cursor:pointer; border:none;
    font-family:'Poppins',sans-serif; text-decoration:none;
    display:inline-flex; align-items:center; gap:5px; transition:0.2s;
}
.btn-toggle-on  { background:rgba(39,174,96,0.12);  color:#27ae60; border:1px solid rgba(39,174,96,0.3); }
.btn-toggle-off { background:rgba(231,76,60,0.12);  color:#e74c3c; border:1px solid rgba(231,76,60,0.3); }
.btn-delete     { background:rgba(231,76,60,0.12);  color:#e74c3c; border:1px solid rgba(231,76,60,0.3); }
.btn-edit       { background:var(--orange-bg); color:var(--orange); border:1px solid var(--orange-bd); }
.empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }
.empty-state i { font-size:48px; margin-bottom:16px; color:var(--text-dim); }
.status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:4px; }
.dot-green { background:#27ae60; }
.dot-red   { background:#e74c3c; }
</style>
</head>
<body>
<?php include "includes/sidebar.php"; ?>

<div class="main">
    <div class="topbar">
        <h2><i class="fa fa-tags" style="color:var(--orange);font-size:20px;"></i> Manage Offers</h2>
        <div class="topbar-right">
            <a href="add_offer.php" style="background:var(--orange); color:#fff; padding:9px 18px; border-radius:8px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px;">
                <i class="fa fa-plus"></i> Add New Offer
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-mini">
            <i class="fa fa-tags"></i>
            <div><div class="val"><?= $total ?></div><div class="lbl">Total Offers</div></div>
        </div>
        <div class="stat-mini">
            <i class="fa fa-circle-check" style="color:#27ae60;"></i>
            <div><div class="val" style="color:#27ae60;"><?= $active ?></div><div class="lbl">Active</div></div>
        </div>
        <div class="stat-mini">
            <i class="fa fa-circle-xmark" style="color:#e74c3c;"></i>
            <div><div class="val" style="color:#e74c3c;"><?= $total - $active ?></div><div class="lbl">Inactive</div></div>
        </div>
    </div>

    <?php if($total == 0): ?>
    <div class="empty-state">
        <i class="fa fa-tags"></i>
        <p style="font-size:16px; font-weight:600; margin-bottom:8px;">Koi offer nahi hai abhi</p>
        <p style="margin-bottom:20px;">Pehla offer add karo!</p>
        <a href="add_offer.php" style="background:var(--orange); color:#fff; padding:10px 24px; border-radius:8px; font-weight:600;">
            <i class="fa fa-plus"></i> Add Offer
        </a>
    </div>
    <?php else: ?>
    <div class="offers-grid">
        <?php while($offer = mysqli_fetch_assoc($offers)): ?>
        <div class="offer-card <?= $offer['is_active'] ? '' : 'inactive' ?>">

            <?php if(!empty($offer['image']) && file_exists("../assets/images/".$offer['image'])): ?>
                <img src="../assets/images/<?= htmlspecialchars($offer['image']) ?>" class="offer-img" alt="offer">
            <?php else: ?>
                <div class="offer-img-placeholder">🎁</div>
            <?php endif; ?>

            <div class="offer-body">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                    <div class="offer-title"><?= htmlspecialchars($offer['title']) ?></div>
                    <span>
                        <span class="status-dot <?= $offer['is_active'] ? 'dot-green' : 'dot-red' ?>"></span>
                        <span style="font-size:11px; color:var(--text-muted);"><?= $offer['is_active'] ? 'Active' : 'Inactive' ?></span>
                    </span>
                </div>

                <div class="offer-desc"><?= htmlspecialchars($offer['description']) ?></div>

                <div class="offer-badge">
                    <i class="fa fa-tag"></i>
                    <?php if($offer['discount_type'] == 'percent'): ?>
                        <?= $offer['discount_value'] ?>% OFF
                    <?php else: ?>
                        ₹<?= number_format($offer['discount_value'], 0) ?> OFF
                    <?php endif; ?>
                </div>

                <?php if(!empty($offer['promo_code'])): ?>
                <div class="offer-code">
                    🏷️ Promo Code: <span><?= htmlspecialchars($offer['promo_code']) ?></span>
                </div>
                <?php endif; ?>

                <?php if(!empty($offer['valid_from']) || !empty($offer['valid_until'])): ?>
                <div class="offer-dates">
                    📅
                    <?= !empty($offer['valid_from'])  ? date('d M Y', strtotime($offer['valid_from']))  : '—' ?>
                    →
                    <?= !empty($offer['valid_until']) ? date('d M Y', strtotime($offer['valid_until'])) : '—' ?>
                </div>
                <?php endif; ?>

                <div class="offer-actions">
                    <a href="?toggle=<?= $offer['id'] ?>" class="btn-sm <?= $offer['is_active'] ? 'btn-toggle-off' : 'btn-toggle-on' ?>">
                        <i class="fa fa-<?= $offer['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                        <?= $offer['is_active'] ? 'Hide' : 'Show' ?>
                    </a>
                    <a href="edit_offer.php?id=<?= $offer['id'] ?>" class="btn-sm btn-edit">
                        <i class="fa fa-pen"></i> Edit
                    </a>
                    <a href="?delete=<?= $offer['id'] ?>" class="btn-sm btn-delete"
                       onclick="return confirm('Delete karna chahte ho?')">
                        <i class="fa fa-trash"></i> Delete
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>