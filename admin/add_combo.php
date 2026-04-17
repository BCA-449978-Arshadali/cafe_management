<?php
include 'includes/auth.php';
include 'includes/db.php';

// ── Auto-create combos table if not exists ──────────────────────────────────
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

$error = '';
$msg   = '';

// ── Handle form submit ───────────────────────────────────────────────────────
if (isset($_POST['add_combo'])) {

    $combo_name  = mysqli_real_escape_string($conn, trim($_POST['combo_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $combo_price = floatval($_POST['combo_price']);
    $items       = $_POST['menu_items'] ?? [];   // array of menu item IDs

    // ── Validations ─────────────────────────────────────────────────────────
    if (!preg_match('/[a-zA-Z]/', $combo_name)) {
        $error = "Combo name must contain at least one letter!";
    } elseif ($combo_price < 0) {
        $error = "Combo price cannot be negative!";
    } elseif (count($items) < 2) {
        $error = "Please select at least 2 items for the combo!";
    } else {

        // ── Image upload ─────────────────────────────────────────────────────
        $image = '';
        if (!empty($_FILES['image']['name'])) {
            $image = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/$image");
        }

        // ── Insert combo ─────────────────────────────────────────────────────
        $ins = mysqli_query($conn, "
            INSERT INTO combos (combo_name, description, combo_price, image)
            VALUES ('$combo_name', '$description', '$combo_price', '$image')
        ");

        if ($ins) {
            $combo_id = mysqli_insert_id($conn);

            // ── Insert combo_items ────────────────────────────────────────────
            foreach ($items as $mid) {
                $mid = intval($mid);
                mysqli_query($conn, "INSERT INTO combo_items (combo_id, menu_id) VALUES ($combo_id, $mid)");
            }

            $msg = "✅ Combo \"$combo_name\" added successfully!";
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

// ── Fetch all menu items for checkbox list ───────────────────────────────────
$menu_result = mysqli_query($conn, "SELECT id, item_name, category, price, small_price FROM menu WHERE is_active=1 ORDER BY category ASC, item_name ASC");
$menu_items  = [];
while ($row = mysqli_fetch_assoc($menu_result)) {
    $menu_items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Combo</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ── Combo-specific styles ── */
.combo-wrapper { display:flex; gap:28px; flex-wrap:wrap; }
.combo-left    { flex:1; min-width:280px; }
.combo-right   { flex:1.4; min-width:300px; }

.items-search {
    width:100%; padding:9px 14px; border-radius:10px;
    border:1px solid rgba(255,255,255,0.12);
    background:#1e1e2e; color:#fff; font-size:13px;
    margin-bottom:12px; outline:none;
    transition:border-color 0.2s;
}
.items-search:focus { border-color:#d4a017; }

.items-grid {
    display:grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap:10px; max-height:400px; overflow-y:auto;
    padding-right:4px;
}
.items-grid::-webkit-scrollbar { width:5px; }
.items-grid::-webkit-scrollbar-track { background:transparent; }
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
.item-checkbox-label.selected .item-check-icon {
    background:#d4a017; border-color:#d4a017; color:#000;
}

.item-info { display:flex; flex-direction:column; line-height:1.35; }
.item-info .i-name { font-weight:600; color:#eee; }
.item-info .i-cat  { font-size:11px; color:#888; }
.item-info .i-price{ font-size:11px; color:#d4a017; margin-top:2px; }

/* Selected summary chips */
.selected-chips {
    display:flex; flex-wrap:wrap; gap:6px;
    min-height:38px; padding:8px;
    background:#12121a; border-radius:10px;
    border:1px dashed rgba(255,255,255,0.1);
    margin-top:6px;
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
    text-transform:uppercase; padding:4px 4px 2px;
    grid-column:1/-1;
}

/* Saving area price hint */
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
    <h2>🍱 Add Combo Item</h2>
    <a href="view_combos.php" style="text-decoration:none;">
        <button class="add-btn" style="background:#1b1f3b;">📋 View All Combos</button>
    </a>
</div>

<?php if($error): ?>
<div class="error-msg" style="background:rgba(231,76,60,0.1);border:1px solid #e74c3c;color:#e74c3c;padding:12px 16px;border-radius:10px;font-size:14px;margin-bottom:16px;">
    ⚠️ <?php echo $error; ?>
</div>
<?php endif; ?>

<?php if($msg): ?>
<div class="error-msg" style="background:rgba(39,174,96,0.1);border:1px solid #27ae60;color:#27ae60;padding:12px 16px;border-radius:10px;font-size:14px;margin-bottom:16px;">
    <?php echo $msg; ?>
</div>
<?php endif; ?>

<div class="form-card">
<form method="POST" enctype="multipart/form-data" onsubmit="return validateCombo()">

<div class="combo-wrapper">

    <!-- ── LEFT: Combo Details ── -->
    <div class="combo-left">
        <h3 style="margin-bottom:16px;font-size:15px;color:#ccc;">📝 Combo Details</h3>

        <div class="form-group">
            <label>Combo Name</label>
            <input type="text" name="combo_name" placeholder="e.g. Family Feast, Burger+Shake Deal"
                value="<?php echo isset($_POST['combo_name']) ? htmlspecialchars($_POST['combo_name']) : ''; ?>"
                pattern=".*[a-zA-Z].*"
                title="Combo name must contain at least one letter!"
                required>
        </div>

        <div class="form-group">
            <label>Description <span style="color:#777;font-size:12px;">(optional)</span></label>
            <textarea name="description" rows="3"
                placeholder="e.g. 2 Burgers + 2 Shakes + Fries at a special price!"
                style="width:100%;padding:10px;border-radius:10px;background:#1e1e2e;border:1px solid rgba(255,255,255,0.1);color:#fff;font-family:inherit;resize:vertical;"
            ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label>Combo Price (₹) <span style="color:#d4a017;font-size:12px;">— keep it lower than items total</span></label>
            <input type="number" name="combo_price" id="combo_price" min="0" step="0.01"
                placeholder="e.g. 299"
                value="<?php echo isset($_POST['combo_price']) ? htmlspecialchars($_POST['combo_price']) : ''; ?>"
                required oninput="updateSavings()">
        </div>

        <!-- Savings hint -->
        <div class="savings-hint" id="savings_hint"></div>

        <div class="form-group">
            <label>Combo Image <span style="color:#777;font-size:12px;">(optional)</span></label>
            <input type="file" name="image" accept="image/*">
        </div>

        <!-- Selected summary -->
        <div class="form-group" style="margin-top:8px;">
            <label>Selected Items Preview</label>
            <div class="selected-chips" id="selected_chips">
                <span style="color:#444;font-size:12px;align-self:center;">No items selected yet…</span>
            </div>
        </div>

        <!-- Total of selected items -->
        <div class="total-wrap" id="total_row" style="display:none;">
            <span class="t-label">Total items price:</span>
            <span>
                <span class="t-val" id="items_total">₹0</span>
                <span class="t-disc" id="discount_pct"></span>
            </span>
        </div>
    </div>

    <!-- ── RIGHT: Item Picker ── -->
    <div class="combo-right">
        <h3 style="margin-bottom:12px;font-size:15px;color:#ccc;">🛒 Select Items <span style="color:#d4a017;">(min. 2)</span></h3>

        <input type="text" class="items-search" id="itemSearch" placeholder="🔍 Search item or category…" oninput="filterItems()">

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
        <?php endif; ?>

            <?php
            $display_price = $mi['small_price'] ? "From ₹{$mi['small_price']}" : "₹{$mi['price']}";
            $item_price    = $mi['small_price'] ? floatval($mi['small_price']) : floatval($mi['price']);
            ?>
            <label class="item-checkbox-label"
                   data-name="<?php echo strtolower($mi['item_name']); ?>"
                   data-cat="<?php echo strtolower($mi['category']); ?>"
                   data-price="<?php echo $item_price; ?>"
                   data-label="<?php echo htmlspecialchars($mi['item_name']); ?>"
                   onclick="toggleItem(event, this)">
                <input type="checkbox" name="menu_items[]" value="<?php echo $mi['id']; ?>">
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

</div><!-- /.combo-wrapper -->

<div style="margin-top:28px; border-top:1px solid rgba(255,255,255,0.07); padding-top:20px;">
    <button class="add-btn" name="add_combo" style="min-width:180px;">
        <i class="fa fa-plus"></i> Add Combo
    </button>
    <a href="view_combos.php" style="margin-left:16px; color:#aaa; font-size:14px; text-decoration:none;">Cancel</a>
</div>

</form>
</div><!-- /.form-card -->
</div><!-- /.main -->

<script>
/* ── State ─────────────────────────────────────────────────────────────────── */
const selected = {};   // { id: { label, price } }

/* ── Toggle item selection ─────────────────────────────────────────────────── */
function toggleItem(e, label) {
    e.preventDefault();   // ← KEY FIX: stops browser from natively toggling the checkbox a second time

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

/* ── Render selected chips ──────────────────────────────────────────────────── */
function renderChips() {
    const wrap = document.getElementById('selected_chips');
    const keys = Object.keys(selected);

    if (keys.length === 0) {
        wrap.innerHTML = '<span style="color:#444;font-size:12px;align-self:center;">No items selected yet…</span>';
        return;
    }

    wrap.innerHTML = keys.map(id => `
        <span class="chip">
            ${selected[id].label}
            <span class="chip-x" onclick="removeChip('${id}')">✕</span>
        </span>
    `).join('');
}

/* ── Remove chip (and uncheck label) ─────────────────────────────────────── */
function removeChip(id) {
    delete selected[id];
    const label = document.querySelector(`input[value="${id}"]`)?.closest('.item-checkbox-label');
    if (label) {
        label.classList.remove('selected');
        label.querySelector('input').checked = false;
    }
    renderChips();
    updateSavings();
}

/* ── Update savings hint ────────────────────────────────────────────────────── */
function updateSavings() {
    const total    = Object.values(selected).reduce((s, v) => s + v.price, 0);
    const comboVal = parseFloat(document.getElementById('combo_price').value) || 0;
    const totalRow = document.getElementById('total_row');
    const hint     = document.getElementById('savings_hint');

    if (Object.keys(selected).length > 0) {
        totalRow.style.display = 'flex';
        document.getElementById('items_total').textContent = '₹' + total.toFixed(2);

        if (comboVal > 0 && comboVal < total) {
            const saving = (total - comboVal).toFixed(2);
            const pct    = ((total - comboVal) / total * 100).toFixed(0);
            document.getElementById('discount_pct').textContent = `(${pct}% OFF)`;
            hint.style.display = 'block';
            hint.textContent   = `🎉 Customer saves ₹${saving} on this combo!`;
        } else {
            document.getElementById('discount_pct').textContent = '';
            hint.style.display = 'none';
        }
    } else {
        totalRow.style.display = 'none';
        hint.style.display     = 'none';
    }
}

/* ── Search/filter items ────────────────────────────────────────────────────── */
function filterItems() {
    const q = document.getElementById('itemSearch').value.toLowerCase().trim();
    document.querySelectorAll('.item-checkbox-label').forEach(lbl => {
        const match = !q || lbl.dataset.name.includes(q) || lbl.dataset.cat.includes(q);
        lbl.style.display = match ? '' : 'none';
    });
    document.querySelectorAll('.cat-group-label').forEach(cl => {
        const cat   = cl.dataset.catLabel;
        const items = document.querySelectorAll(`.item-checkbox-label[data-cat="${cat}"]`);
        const any   = [...items].some(i => i.style.display !== 'none');
        cl.style.display = any ? '' : 'none';
    });
}

/* ── Client-side validate ─────────────────────────────────────────────────── */
function validateCombo() {
    const name = document.querySelector('input[name="combo_name"]').value.trim();
    if (!/[a-zA-Z]/.test(name)) {
        alert('Combo name must contain at least one letter!');
        return false;
    }
    if (Object.keys(selected).length < 2) {
        alert('Please select at least 2 items for the combo!');
        return false;
    }
    const price = parseFloat(document.getElementById('combo_price').value);
    if (price < 0) {
        alert('Combo price cannot be negative!');
        return false;
    }
    return true;
}
</script>

</body>
</html>