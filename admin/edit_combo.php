<?php
include 'includes/auth.php';
include 'includes/db.php';

$id = intval($_GET['id'] ?? 0);

// ── Fetch existing combo ─────────────────────────────────────────────────────
$c = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM combos WHERE id=$id"));
if (!$c) { header("Location: view_combos.php"); exit; }

// ── Fetch already-selected items ─────────────────────────────────────────────
$cur_items_res = mysqli_query($conn, "SELECT menu_id FROM combo_items WHERE combo_id=$id");
$cur_items = [];
while ($r = mysqli_fetch_assoc($cur_items_res)) $cur_items[] = $r['menu_id'];

$error = '';
$msg   = '';

// ── Handle update ────────────────────────────────────────────────────────────
if (isset($_POST['update_combo'])) {

    $combo_name  = mysqli_real_escape_string($conn, trim($_POST['combo_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $combo_price = floatval($_POST['combo_price']);
    $items       = $_POST['menu_items'] ?? [];

    if (!preg_match('/[a-zA-Z]/', $combo_name)) {
        $error = "Combo name mein kam se kam ek alphabet hona chahiye!";
    } elseif ($combo_price < 0) {
        $error = "Combo price negative nahi ho sakti!";
    } elseif (count($items) < 2) {
        $error = "Combo mein kam se kam 2 items select karo!";
    } else {

        $image = $c['image'];
        if (!empty($_FILES['image']['name'])) {
            $image = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/$image");
        }

        mysqli_query($conn, "
            UPDATE combos
            SET combo_name='$combo_name', description='$description',
                combo_price='$combo_price', image='$image'
            WHERE id=$id
        ");

        // Replace combo_items
        mysqli_query($conn, "DELETE FROM combo_items WHERE combo_id=$id");
        foreach ($items as $mid) {
            $mid = intval($mid);
            mysqli_query($conn, "INSERT INTO combo_items (combo_id, menu_id) VALUES ($id, $mid)");
        }

        $msg      = "✅ Combo successfully update ho gaya!";
        $c        = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM combos WHERE id=$id"));
        $cur_res2 = mysqli_query($conn, "SELECT menu_id FROM combo_items WHERE combo_id=$id");
        $cur_items = [];
        while ($r2 = mysqli_fetch_assoc($cur_res2)) $cur_items[] = $r2['menu_id'];
    }
}

// ── Fetch all menu items ─────────────────────────────────────────────────────
$menu_result = mysqli_query($conn, "SELECT id, item_name, category, price, small_price FROM menu WHERE is_active=1 ORDER BY category ASC, item_name ASC");
$menu_items  = [];
while ($row = mysqli_fetch_assoc($menu_result)) $menu_items[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Combo</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.combo-wrapper { display:flex; gap:28px; flex-wrap:wrap; }
.combo-left    { flex:1; min-width:280px; }
.combo-right   { flex:1.4; min-width:300px; }

.items-search {
    width:100%; padding:9px 14px; border-radius:10px;
    border:1px solid rgba(255,255,255,0.12);
    background:#1e1e2e; color:#fff; font-size:13px;
    margin-bottom:12px; outline:none; transition:border-color 0.2s;
}
.items-search:focus { border-color:#d4a017; }

.items-grid {
    display:grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap:10px; max-height:400px; overflow-y:auto; padding-right:4px;
}
.items-grid::-webkit-scrollbar { width:5px; }
.items-grid::-webkit-scrollbar-thumb { background:#d4a017; border-radius:4px; }

.item-checkbox-label {
    display:flex; align-items:center; gap:10px;
    padding:10px 12px; border-radius:12px;
    border:1.5px solid rgba(255,255,255,0.08);
    background:#18181f; cursor:pointer;
    transition:0.2s; font-size:13px;
}
.item-checkbox-label:hover { border-color:#d4a01740; background:#1e1e2e; }
.item-checkbox-label.selected { border-color:#d4a017; background:rgba(212,160,23,0.08); }
.item-checkbox-label input[type="checkbox"] { display:none; }

.item-check-icon {
    width:22px; height:22px; border-radius:6px; flex-shrink:0;
    border:2px solid #444; display:flex; align-items:center; justify-content:center;
    font-size:13px; transition:0.2s;
}
.item-checkbox-label.selected .item-check-icon { background:#d4a017; border-color:#d4a017; color:#000; }

.item-info { display:flex; flex-direction:column; line-height:1.35; }
.item-info .i-name  { font-weight:600; color:#eee; }
.item-info .i-cat   { font-size:11px; color:#888; }
.item-info .i-price { font-size:11px; color:#d4a017; margin-top:2px; }

.selected-chips {
    display:flex; flex-wrap:wrap; gap:6px;
    min-height:38px; padding:8px;
    background:#12121a; border-radius:10px;
    border:1px dashed rgba(255,255,255,0.1); margin-top:6px;
}
.chip {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 10px; border-radius:20px;
    background:rgba(212,160,23,0.12); border:1px solid rgba(212,160,23,0.35);
    font-size:12px; color:#d4a017; font-weight:600;
}
.chip .chip-x { cursor:pointer; color:#ff6b6b; margin-left:3px; }

.total-wrap {
    display:flex; align-items:center; justify-content:space-between;
    background:#1a1a2e; padding:12px 16px; border-radius:10px;
    margin-top:10px; font-size:13px;
}
.total-wrap .t-label { color:#aaa; }
.total-wrap .t-val   { font-weight:700; color:#27ae60; font-size:15px; }
.total-wrap .t-disc  { color:#d4a017; font-size:12px; margin-left:6px; }

.cat-group-label {
    font-size:11px; font-weight:700; color:#666; letter-spacing:1px;
    text-transform:uppercase; padding:4px 4px 2px; grid-column:1/-1;
}

.savings-hint {
    background:rgba(39,174,96,0.08); border:1px solid rgba(39,174,96,0.2);
    border-radius:8px; padding:8px 14px; font-size:13px;
    color:#27ae60; margin-top:8px; display:none;
}
</style>
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">
<div class="topbar">
    <h2>✏️ Edit Combo</h2>
    <a href="view_combos.php">
        <button class="add-btn" style="background:#1b1f3b;">📋 View All Combos</button>
    </a>
</div>

<?php if($error): ?>
<div class="error-msg" style="background:rgba(231,76,60,0.1);border:1px solid #e74c3c;color:#e74c3c;padding:12px 16px;border-radius:10px;font-size:14px;margin-bottom:16px;">
    ⚠️ <?php echo $error; ?>
</div>
<?php endif; ?>

<?php if($msg): ?>
<div style="background:rgba(39,174,96,0.1);border:1px solid #27ae60;color:#27ae60;padding:12px 16px;border-radius:10px;font-size:14px;margin-bottom:16px;">
    <?php echo $msg; ?>
</div>
<?php endif; ?>

<div class="form-card">
<form method="POST" enctype="multipart/form-data" onsubmit="return validateCombo()">

<div class="combo-wrapper">

    <!-- ── LEFT ── -->
    <div class="combo-left">
        <h3 style="margin-bottom:16px;font-size:15px;color:#ccc;">📝 Combo Details</h3>

        <div class="form-group">
            <label>Combo Name</label>
            <input type="text" name="combo_name"
                value="<?php echo htmlspecialchars($c['combo_name']); ?>"
                pattern=".*[a-zA-Z].*" required>
        </div>

        <div class="form-group">
            <label>Description <span style="color:#777;font-size:12px;">(optional)</span></label>
            <textarea name="description" rows="3"
                style="width:100%;padding:10px;border-radius:10px;background:#1e1e2e;border:1px solid rgba(255,255,255,0.1);color:#fff;font-family:inherit;resize:vertical;"
            ><?php echo htmlspecialchars($c['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label>Combo Price (₹)</label>
            <input type="number" name="combo_price" id="combo_price"
                min="0" step="0.01"
                value="<?php echo $c['combo_price']; ?>"
                required oninput="updateSavings()">
        </div>

        <div class="savings-hint" id="savings_hint"></div>

        <div class="form-group">
            <label>Current Image</label><br>
            <?php if($c['image']): ?>
            <img src="../assets/images/<?php echo htmlspecialchars($c['image']); ?>"
                 height="90" style="border-radius:8px;margin-top:6px;margin-bottom:8px;object-fit:cover;"
                 onerror="this.style.display='none'">
            <?php else: ?>
            <span style="font-size:13px;color:#888;">Koi image nahi</span>
            <?php endif; ?>
            <br>
            <label>Nai Image Upload Karo <span style="color:#777;font-size:12px;">(optional)</span></label>
            <input type="file" name="image" accept="image/*">
        </div>

        <div class="form-group" style="margin-top:8px;">
            <label>Selected Items Preview</label>
            <div class="selected-chips" id="selected_chips"></div>
        </div>

        <div class="total-wrap" id="total_row" style="display:none;">
            <span class="t-label">Items ka kul daam:</span>
            <span>
                <span class="t-val" id="items_total">₹0</span>
                <span class="t-disc" id="discount_pct"></span>
            </span>
        </div>
    </div>

    <!-- ── RIGHT ── -->
    <div class="combo-right">
        <h3 style="margin-bottom:12px;font-size:15px;color:#ccc;">🛒 Items Select Karo <span style="color:#d4a017;">(min. 2)</span></h3>

        <input type="text" class="items-search" id="itemSearch"
               placeholder="🔍 Item ya category search karo…"
               oninput="filterItems()">

        <div class="items-grid" id="itemsGrid">
        <?php
        $prev_cat = '';
        foreach($menu_items as $mi):
            if ($mi['category'] !== $prev_cat):
                $prev_cat = $mi['category'];
        ?>
            <div class="cat-group-label" data-cat-label="<?php echo strtolower($mi['category']); ?>">
                <?php echo $mi['category']; ?>
            </div>
        <?php endif;
            $is_checked    = in_array($mi['id'], $cur_items);
            $display_price = $mi['small_price'] ? "From ₹{$mi['small_price']}" : "₹{$mi['price']}";
            $item_price    = $mi['small_price'] ? floatval($mi['small_price']) : floatval($mi['price']);
        ?>
            <label class="item-checkbox-label <?php echo $is_checked ? 'selected' : ''; ?>"
                   data-name="<?php echo strtolower($mi['item_name']); ?>"
                   data-cat="<?php echo strtolower($mi['category']); ?>"
                   data-price="<?php echo $item_price; ?>"
                   data-label="<?php echo htmlspecialchars($mi['item_name']); ?>"
                   onclick="toggleItem(this)">
                <input type="checkbox" name="menu_items[]"
                       value="<?php echo $mi['id']; ?>"
                       <?php echo $is_checked ? 'checked' : ''; ?>>
                <span class="item-check-icon">✓</span>
                <span class="item-info">
                    <span class="i-name"><?php echo htmlspecialchars($mi['item_name']); ?></span>
                    <span class="i-cat"><?php echo $mi['category']; ?></span>
                    <span class="i-price"><?php echo $display_price; ?></span>
                </span>
            </label>
        <?php endforeach; ?>
        </div>
    </div>

</div>

<div style="margin-top:28px; border-top:1px solid rgba(255,255,255,0.07); padding-top:20px;">
    <button class="add-btn" name="update_combo" style="min-width:180px;">
        <i class="fa fa-save"></i> Update Combo
    </button>
    <a href="view_combos.php" style="margin-left:16px; color:#aaa; font-size:14px; text-decoration:none;">Cancel</a>
</div>

</form>
</div>
</div>

<script>
const selected = {};

// Pre-populate selected from PHP checked items
window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.item-checkbox-label.selected').forEach(lbl => {
        const id    = lbl.querySelector('input').value;
        const price = parseFloat(lbl.dataset.price) || 0;
        const label = lbl.dataset.label;
        selected[id] = { label, price };
    });
    renderChips();
    updateSavings();
});

function toggleItem(label) {
    const cb    = label.querySelector('input[type="checkbox"]');
    const id    = cb.value;
    const price = parseFloat(label.dataset.price) || 0;
    const lbl   = label.dataset.label;

    if (label.classList.contains('selected')) {
        label.classList.remove('selected');
        cb.checked = false;
        delete selected[id];
    } else {
        label.classList.add('selected');
        cb.checked = true;
        selected[id] = { label: lbl, price };
    }
    renderChips();
    updateSavings();
}

function renderChips() {
    const wrap = document.getElementById('selected_chips');
    const keys = Object.keys(selected);
    if (keys.length === 0) {
        wrap.innerHTML = '<span style="color:#444;font-size:12px;align-self:center;">Koi item select nahi…</span>';
        return;
    }
    wrap.innerHTML = keys.map(id => `
        <span class="chip">
            ${selected[id].label}
            <span class="chip-x" onclick="removeChip('${id}')">✕</span>
        </span>
    `).join('');
}

function removeChip(id) {
    delete selected[id];
    const label = document.querySelector(`input[value="${id}"]`)?.closest('.item-checkbox-label');
    if (label) { label.classList.remove('selected'); label.querySelector('input').checked = false; }
    renderChips();
    updateSavings();
}

function updateSavings() {
    const total    = Object.values(selected).reduce((s, v) => s + v.price, 0);
    const comboVal = parseFloat(document.getElementById('combo_price').value) || 0;
    const hint     = document.getElementById('savings_hint');
    const totalRow = document.getElementById('total_row');

    if (Object.keys(selected).length > 0) {
        totalRow.style.display = 'flex';
        document.getElementById('items_total').textContent = '₹' + total.toFixed(2);
        if (comboVal > 0 && comboVal < total) {
            const saving = (total - comboVal).toFixed(2);
            const pct    = ((total - comboVal) / total * 100).toFixed(0);
            document.getElementById('discount_pct').textContent = `(${pct}% OFF)`;
            hint.style.display = 'block';
            hint.textContent   = `🎉 Customer ko ₹${saving} ki savings milegi!`;
        } else {
            document.getElementById('discount_pct').textContent = '';
            hint.style.display = 'none';
        }
    } else {
        totalRow.style.display = 'none';
        hint.style.display     = 'none';
    }
}

function filterItems() {
    const q = document.getElementById('itemSearch').value.toLowerCase().trim();
    document.querySelectorAll('.item-checkbox-label').forEach(lbl => {
        lbl.style.display = (!q || lbl.dataset.name.includes(q) || lbl.dataset.cat.includes(q)) ? '' : 'none';
    });
    document.querySelectorAll('.cat-group-label').forEach(cl => {
        const items = document.querySelectorAll(`.item-checkbox-label[data-cat="${cl.dataset.catLabel}"]`);
        cl.style.display = [...items].some(i => i.style.display !== 'none') ? '' : 'none';
    });
}

function validateCombo() {
    if (!/[a-zA-Z]/.test(document.querySelector('input[name="combo_name"]').value.trim())) {
        alert('Combo name mein kam se kam ek alphabet hona chahiye!'); return false;
    }
    if (Object.keys(selected).length < 2) {
        alert('Combo mein kam se kam 2 items select karo!'); return false;
    }
    if (parseFloat(document.getElementById('combo_price').value) < 0) {
        alert('Price negative nahi ho sakti!'); return false;
    }
    return true;
}
</script>

</body>
</html>