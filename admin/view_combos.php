<?php
include 'includes/auth.php';
include 'includes/db.php';

// ── Auto-create tables ───────────────────────────────────────────────────────
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

// ── Toggle availability ───────────────────────────────────────────────────────
if (isset($_GET['toggle_avail'])) {
    $tid = intval($_GET['toggle_avail']);
    mysqli_query($conn, "UPDATE combos SET is_available = 1 - is_available WHERE id = $tid");
    header("Location: view_combos.php");
    exit;
}

// ── Toggle visibility ─────────────────────────────────────────────────────────
if (isset($_GET['toggle_vis'])) {
    $tid = intval($_GET['toggle_vis']);
    mysqli_query($conn, "UPDATE combos SET is_active = 1 - is_active WHERE id = $tid");
    header("Location: view_combos.php");
    exit;
}

// ── Delete combo ──────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM combo_items WHERE combo_id = $did");
    mysqli_query($conn, "DELETE FROM combos WHERE id = $did");
    header("Location: view_combos.php?deleted=1");
    exit;
}

// ── Fetch combos with item count ──────────────────────────────────────────────
$combos = mysqli_query($conn, "
    SELECT c.*,
           COUNT(ci.id) AS item_count
    FROM   combos c
    LEFT JOIN combo_items ci ON ci.combo_id = c.id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$total = mysqli_num_rows($combos);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Combos</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.combo-card {
    background:#fff;
    border:1px solid rgba(0,0,0,0.09);
    border-radius:16px;
    padding:0;
    overflow:hidden;
    display:flex;
    flex-direction:column;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
    transition:transform 0.2s, box-shadow 0.2s;
}
.combo-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,0.1); }

.combo-img {
    width:100%; height:160px; object-fit:cover;
    background:#f5f5f5;
}
.combo-img-placeholder {
    width:100%; height:160px;
    background:linear-gradient(135deg,#f0e6c8,#e8d5a3);
    display:flex; align-items:center; justify-content:center;
    font-size:52px;
}

.combo-body { padding:16px; flex:1; display:flex; flex-direction:column; gap:8px; }

.combo-name { font-size:16px; font-weight:700; color:#1b1f3b; }
.combo-desc { font-size:13px; color:#777; line-height:1.5; }

.combo-price {
    font-size:20px; font-weight:800; color:#d4a017;
    margin-top:4px;
}

.combo-items-list {
    display:flex; flex-wrap:wrap; gap:5px; margin-top:4px;
}
.combo-item-chip {
    padding:3px 9px; border-radius:12px;
    background:#f0f0f0; font-size:11px; color:#555; font-weight:600;
}

.combo-footer {
    padding:12px 16px;
    border-top:1px solid rgba(0,0,0,0.06);
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
}

/* toggle switch */
.t-switch {
    position:relative; display:inline-block;
    width:40px; height:22px; cursor:pointer; flex-shrink:0;
}
.t-switch input { display:none; }
.t-slider {
    position:absolute; inset:0; border-radius:22px;
    background:#ccc; transition:0.28s;
}
.t-slider:before {
    content:""; position:absolute;
    width:16px; height:16px; left:3px; bottom:3px;
    background:#fff; border-radius:50%;
    transition:0.28s; box-shadow:0 1px 4px rgba(0,0,0,0.25);
}
.oos-sw input:checked     + .t-slider { background:#27ae60; }
.oos-sw input:not(:checked) + .t-slider { background:#e67e22; }
.oos-sw input:checked     + .t-slider:before { transform:translateX(18px); }
.vis-sw input:checked     + .t-slider { background:#27ae60; }
.vis-sw input:not(:checked) + .t-slider { background:#95a5a6; }
.vis-sw input:checked     + .t-slider:before { transform:translateX(18px); }

.s-badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:2px 9px; border-radius:20px;
    font-size:11px; font-weight:700; white-space:nowrap;
}
.sb-instock { background:rgba(39,174,96,0.1);   color:#27ae60; border:1px solid rgba(39,174,96,0.25); }
.sb-oos     { background:rgba(230,126,34,0.1);   color:#e67e22; border:1px solid rgba(230,126,34,0.3); }
.sb-visible { background:rgba(39,174,96,0.1);   color:#27ae60; border:1px solid rgba(39,174,96,0.25); }
.sb-hidden  { background:rgba(127,140,141,0.1); color:#7f8c8d; border:1px solid rgba(127,140,141,0.3); }

.combos-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));
    gap:22px;
    margin-top:20px;
}

.empty-state {
    text-align:center; padding:80px 20px; color:#aaa;
}
.empty-state .e-icon { font-size:60px; margin-bottom:16px; }
.empty-state p { font-size:15px; }
</style>
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">

<div class="topbar">
    <h2>🍱 Manage Combos</h2>
    <a href="add_combo.php">
        <button class="add-btn"><i class="fa fa-plus"></i> Add New Combo</button>
    </a>
</div>

<?php if (isset($_GET['deleted'])): ?>
<div style="background:rgba(39,174,96,0.1);border:1px solid #27ae60;color:#27ae60;padding:12px 16px;border-radius:10px;font-size:14px;margin-bottom:16px;">
    ✅ Combo successfully delete ho gaya!
</div>
<?php endif; ?>

<div style="background:#f9f9f9;border-radius:12px;padding:14px 18px;display:inline-flex;align-items:center;gap:10px;font-size:14px;color:#555;margin-bottom:4px;">
    🍱 Total Combos: <strong style="color:#1b1f3b;"><?php echo $total; ?></strong>
</div>

<?php if ($total == 0): ?>
<div class="empty-state">
    <div class="e-icon">🍱</div>
    <p>Abhi koi combo nahi hai.<br>
       <a href="add_combo.php" style="color:#d4a017;font-weight:700;">+ Pehla combo add karo!</a>
    </p>
</div>

<?php else: ?>
<div class="combos-grid">

<?php
mysqli_data_seek($combos, 0);
while ($c = mysqli_fetch_assoc($combos)):
    $is_avail  = intval($c['is_available']);
    $is_active = intval($c['is_active']);

    // Fetch items of this combo
    $citems = mysqli_query($conn, "
        SELECT m.item_name FROM combo_items ci
        JOIN menu m ON m.id = ci.menu_id
        WHERE ci.combo_id = {$c['id']}
    ");
    $item_names = [];
    while ($ci = mysqli_fetch_assoc($citems)) $item_names[] = $ci['item_name'];
?>

<div class="combo-card">

    <!-- Image -->
    <?php if ($c['image']): ?>
    <img class="combo-img" src="../assets/images/<?php echo htmlspecialchars($c['image']); ?>"
         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
         alt="<?php echo htmlspecialchars($c['combo_name']); ?>">
    <div class="combo-img-placeholder" style="display:none;">🍱</div>
    <?php else: ?>
    <div class="combo-img-placeholder">🍱</div>
    <?php endif; ?>

    <!-- Body -->
    <div class="combo-body">
        <div class="combo-name"><?php echo htmlspecialchars($c['combo_name']); ?></div>

        <?php if ($c['description']): ?>
        <div class="combo-desc"><?php echo htmlspecialchars($c['description']); ?></div>
        <?php endif; ?>

        <div class="combo-price">₹<?php echo number_format($c['combo_price'], 2); ?></div>

        <?php if (!empty($item_names)): ?>
        <div class="combo-items-list">
            <?php foreach ($item_names as $iname): ?>
            <span class="combo-item-chip"><?php echo htmlspecialchars($iname); ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer: toggles + actions -->
    <div class="combo-footer">

        <!-- Available toggle -->
        <a href="?toggle_avail=<?php echo $c['id']; ?>"
           onclick="event.preventDefault(); var url=this.href; showConfirm(<?php echo $is_avail ? "'Out of Stock mark karein?'" : "'In Stock karein?'"; ?>, function(){ window.location.href=url; }, {icon:'📦', okText:'Yes, Confirm'});"
           style="text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
            <span class="t-switch oos-sw">
                <input type="checkbox" <?php echo $is_avail ? 'checked' : ''; ?> tabindex="-1">
                <span class="t-slider"></span>
            </span>
            <span class="s-badge <?php echo $is_avail ? 'sb-instock' : 'sb-oos'; ?>">
                <?php echo $is_avail ? '✅ Available' : '🚫 Unavailable'; ?>
            </span>
        </a>

        <!-- Visible toggle -->
        <a href="?toggle_vis=<?php echo $c['id']; ?>"
           onclick="event.preventDefault(); var url=this.href; showConfirm(<?php echo $is_active ? "'Menu se hide karein?'" : "'Menu par show karein?'"; ?>, function(){ window.location.href=url; }, {icon:'👁', okText:'Yes, Confirm'});"
           style="text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
            <span class="t-switch vis-sw">
                <input type="checkbox" <?php echo $is_active ? 'checked' : ''; ?> tabindex="-1">
                <span class="t-slider"></span>
            </span>
            <span class="s-badge <?php echo $is_active ? 'sb-visible' : 'sb-hidden'; ?>">
                <?php echo $is_active ? '👁 Visible' : '🙈 Hidden'; ?>
            </span>
        </a>

        <div style="margin-left:auto;display:flex;gap:8px;">
            <a class="edit"   href="edit_combo.php?id=<?php echo $c['id']; ?>">Edit</a>
            <a class="delete" href="?delete=<?php echo $c['id']; ?>"
               onclick="event.preventDefault(); var url=this.href; showConfirm('<?php echo addslashes($c["combo_name"]); ?> combo delete karo?', function(){ window.location.href=url; }, {icon:'🗑️', okText:'Yes, Delete', okClass:'gcm-btn-danger'});">Delete</a>
        </div>

    </div>
</div>

<?php endwhile; ?>
</div>
<?php endif; ?>

</div><!-- /.main -->
<?php include "includes/footer.php"; ?>
</body>
</html>