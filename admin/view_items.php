<?php
include 'includes/auth.php';
include 'includes/db.php';

// Auto-add is_active column if missing
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM menu LIKE 'is_active'");
if(mysqli_num_rows($col_check) == 0){
    mysqli_query($conn, "ALTER TABLE menu ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
}

$filter = isset($_GET['category']) ? $_GET['category'] : 'All';
$show   = isset($_GET['show'])     ? $_GET['show']     : 'all';

$categories = ['All','Pizza','Burger','Sandwich','Shakes','Coffee','Cakes','Dessert',
               'Hot Dog','Muffin','Donut','Drinks','Rolls','Rice','Noodles','Soup',
               'Bread','Chinese','Momos'];

$where_parts = [];
$params      = [];
$types       = '';

if($filter != 'All'){
    $where_parts[] = "category = ?";
    $params[]      = $filter;
    $types        .= 's';
}
if($show == 'out_of_stock'){
    $where_parts[] = "is_available = 0";
} elseif($show == 'hidden'){
    $where_parts[] = "is_active = 0";
}

$where = count($where_parts) ? "WHERE " . implode(" AND ", $where_parts) : "";
$sql   = "SELECT * FROM menu $where ORDER BY category ASC, item_name ASC";

if(count($params)){
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $query = $stmt->get_result();
} else {
    $query = mysqli_query($conn, $sql);
}

$oos_count    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM menu WHERE is_available = 0"))['c'];
$hidden_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM menu WHERE is_active = 0"))['c'];
$total_count  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM menu"))['c'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Menu Items</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.filter-bar { margin-bottom:12px; display:flex; flex-wrap:wrap; gap:8px; }
.filter-btn {
    padding:6px 14px; border-radius:20px; border:1px solid #ddd;
    background:#f5f5f5; cursor:pointer; font-size:13px;
    text-decoration:none; color:#333; transition:0.2s;
}
.filter-btn:hover,.filter-btn.active { background:#d4a017; color:white; border-color:#d4a017; }

.show-bar { margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap; }
.show-btn {
    padding:7px 18px; border-radius:20px; font-size:13px; font-weight:600;
    text-decoration:none; border:2px solid #ddd; color:#555; transition:0.2s;
}
.show-btn.a-all    { background:#1b1f3b; color:#fff; border-color:#1b1f3b; }
.show-btn.a-oos    { background:#e67e22; color:#fff; border-color:#e67e22; }
.show-btn.a-hidden { background:#7f8c8d; color:#fff; border-color:#7f8c8d; }
.show-btn:hover { opacity:0.85; }
.tab-badge { display:inline-block; padding:1px 7px; border-radius:10px; font-size:11px; font-weight:700; margin-left:4px; }

.category-badge { background:#1b1f3b; color:white; padding:3px 10px; border-radius:12px; font-size:12px; display:inline-block; }

/* Row tints */
.oos-row td    { background:rgba(230,126,34,0.06) !important; }
.hidden-row td { background:rgba(127,140,141,0.06) !important; }

/* Item inline tags */
.row-tag {
    display:inline-flex; align-items:center; gap:3px;
    padding:2px 7px; border-radius:8px;
    font-size:10px; font-weight:700; margin-left:7px; vertical-align:middle;
}
.tag-oos    { background:rgba(230,126,34,0.12); color:#e67e22; border:1px solid rgba(230,126,34,0.3); }
.tag-hidden { background:rgba(127,140,141,0.12); color:#7f8c8d; border:1px solid rgba(127,140,141,0.3); }

/* Two-toggle cell */
.toggles-cell { display:flex; flex-direction:column; gap:7px; min-width:200px; }
.toggle-row   { display:flex; align-items:center; gap:8px; }

.toggle-link  { display:inline-flex; align-items:center; text-decoration:none; flex-shrink:0; }

.t-switch {
    position:relative; display:inline-block;
    width:44px; height:24px; cursor:pointer; flex-shrink:0;
}
.t-switch input { display:none; }
.t-slider {
    position:absolute; inset:0; border-radius:24px;
    background:#ccc; transition:0.28s;
}
.t-slider:before {
    content:""; position:absolute;
    width:18px; height:18px; left:3px; bottom:3px;
    background:#fff; border-radius:50%;
    transition:0.28s; box-shadow:0 1px 4px rgba(0,0,0,0.25);
}
/* OOS toggle: green = in stock, orange = out of stock */
.oos-sw input:checked     + .t-slider { background:#27ae60; }
.oos-sw input:not(:checked) + .t-slider { background:#e67e22; }
.oos-sw input:checked     + .t-slider:before { transform:translateX(20px); }
/* Visibility toggle: green = visible, grey = hidden */
.vis-sw input:checked     + .t-slider { background:#27ae60; }
.vis-sw input:not(:checked) + .t-slider { background:#95a5a6; }
.vis-sw input:checked     + .t-slider:before { transform:translateX(20px); }

.s-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:20px;
    font-size:11px; font-weight:700; white-space:nowrap;
}
.sb-instock  { background:rgba(39,174,96,0.1);   color:#27ae60; border:1px solid rgba(39,174,96,0.25); }
.sb-oos      { background:rgba(230,126,34,0.1);   color:#e67e22; border:1px solid rgba(230,126,34,0.3); }
.sb-visible  { background:rgba(39,174,96,0.1);   color:#27ae60; border:1px solid rgba(39,174,96,0.25); }
.sb-hidden   { background:rgba(127,140,141,0.1); color:#7f8c8d; border:1px solid rgba(127,140,141,0.3); }

.toggle-sep { height:1px; background:rgba(0,0,0,0.08); margin:1px 0; }

/* ===== RESPONSIVE ===== */
@media(max-width:900px){
    /* Table scrollable on tablet */
    .main { padding:16px; }
    .table { display:block; overflow-x:auto; -webkit-overflow-scrolling:touch; white-space:nowrap; }
}

@media(max-width:768px){
    /* Topbar stack */
    .topbar { flex-direction:column; align-items:flex-start; gap:10px; }

    /* Show bar wraps */
    .show-bar { gap:8px; }
    .show-btn  { padding:6px 12px; font-size:12px; }

    /* Filter pills smaller */
    .filter-bar { gap:6px; }
    .filter-btn { padding:5px 11px; font-size:12px; }

    /* Table font smaller */
    .table th, .table td { padding:10px 10px; font-size:12px; }

    /* Image thumb smaller */
    .table img { height:48px !important; width:48px !important; }

    /* Toggles stack stays column — already ok */
    .toggles-cell { min-width:160px; gap:6px; }
    .s-badge { font-size:10px; padding:2px 8px; }
    .t-switch { width:38px; height:21px; }
    .t-slider:before { width:15px; height:15px; }
    .oos-sw input:checked + .t-slider:before,
    .vis-sw input:checked + .t-slider:before { transform:translateX(17px); }

    /* Action buttons stacked */
    .table .edit, .table .delete { display:block; margin:2px 0; text-align:center; font-size:11px; }

    /* Row tags smaller */
    .row-tag { font-size:9px; padding:1px 6px; margin-left:4px; }
}

@media(max-width:480px){
    .main { padding:12px 10px; }

    /* Hide less important columns on very small screens */
    .table th:nth-child(3),
    .table td:nth-child(3) { display:none; } /* hide Price col */

    .show-btn { padding:6px 10px; font-size:11px; }
    .filter-btn { padding:4px 9px; font-size:11px; }
    .tab-badge { padding:1px 5px; font-size:10px; }

    .category-badge { font-size:10px; padding:2px 7px; }
    .toggles-cell { min-width:140px; }
    .s-badge { display:none; } /* just show toggle on tiny screen */
}
</style>
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">

    <div class="topbar">
        <h2>Menu Items</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <?php if($oos_count > 0): ?>
            <div style="background:rgba(230,126,34,0.1); color:#e67e22; border:1px solid rgba(230,126,34,0.3); padding:7px 14px; border-radius:8px; font-size:13px; font-weight:600;">
                🚫 <?php echo $oos_count; ?> Out of Stock
            </div>
            <?php endif; ?>
            <?php if($hidden_count > 0): ?>
            <div style="background:rgba(127,140,141,0.1); color:#7f8c8d; border:1px solid rgba(127,140,141,0.3); padding:7px 14px; border-radius:8px; font-size:13px; font-weight:600;">
                🙈 <?php echo $hidden_count; ?> Hidden from Menu
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Show Tabs -->
    <div class="show-bar">
        <a href="?category=<?php echo urlencode($filter); ?>&show=all"
           class="show-btn <?php echo $show=='all' ? 'a-all' : ''; ?>">
            📋 All Items
            <span class="tab-badge" style="background:#1b1f3b;color:#fff;"><?php echo $total_count; ?></span>
        </a>
        <a href="?category=<?php echo urlencode($filter); ?>&show=out_of_stock"
           class="show-btn <?php echo $show=='out_of_stock' ? 'a-oos' : ''; ?>">
            🚫 Out of Stock
            <?php if($oos_count > 0) echo "<span class='tab-badge' style='background:#e67e22;color:#fff;'>$oos_count</span>"; ?>
        </a>
        <a href="?category=<?php echo urlencode($filter); ?>&show=hidden"
           class="show-btn <?php echo $show=='hidden' ? 'a-hidden' : ''; ?>">
            🙈 Hidden from Menu
            <?php if($hidden_count > 0) echo "<span class='tab-badge' style='background:#7f8c8d;color:#fff;'>$hidden_count</span>"; ?>
        </a>
    </div>

    <!-- Category Filter -->
    <div class="filter-bar">
        <?php foreach($categories as $cat):
            $active = ($filter == $cat) ? 'active' : '';
        ?>
        <a href="view_items.php?category=<?php echo urlencode($cat); ?>&show=<?php echo $show; ?>"
           class="filter-btn <?php echo $active; ?>">
            <?php echo $cat; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th>Image</th>
            <th>Item Name</th>
            <th>Price (₹)</th>
            <th>Category</th>
            <th style="min-width:220px;">
                🚫 Out of Stock &nbsp;|&nbsp; 🙈 Hide from Menu
            </th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>

        <?php
        $rows = [];
        while($row = mysqli_fetch_assoc($query)) $rows[] = $row;

        if(count($rows) == 0): ?>
        <tr>
            <td colspan="6" style="text-align:center;padding:40px;color:#aaa;">
                <?php
                if($show=='out_of_stock') echo '✅ Koi bhi item out of stock nahi!';
                elseif($show=='hidden')   echo '✅ Koi bhi item hidden nahi!';
                else                      echo '📭 Koi item nahi mila.';
                ?>
            </td>
        </tr>
        <?php else: foreach($rows as $row):
            $is_available = isset($row['is_available']) ? intval($row['is_available']) : 1;
            $is_active    = isset($row['is_active'])    ? intval($row['is_active'])    : 1;
            $back_url     = urlencode("view_items.php?category={$filter}&show={$show}");

            // Row class — hidden takes priority
            $row_class = !$is_active ? 'hidden-row' : (!$is_available ? 'oos-row' : '');

            $oos_msg = $is_available
                ? 'Mark this item as Out of Stock?\n(It will still appear on menu but Add to Cart will be disabled)'
                : 'Mark this item as In Stock again?';
            $vis_msg = $is_active
                ? 'Completely HIDE this item from menu?\n(Customers will not be able to see it at all)'
                : 'Show this item on menu again?';
        ?>
        <tr class="<?php echo $row_class; ?>">

            <!-- Image -->
            <td style="width:72px;">
                <img src="../assets/images/<?php echo htmlspecialchars($row['image']); ?>"
                     height="56" width="56"
                     style="border-radius:10px; object-fit:cover;
                            <?php echo !$is_active ? 'filter:grayscale(100%);opacity:0.35;'
                                     : (!$is_available ? 'filter:grayscale(50%);opacity:0.65;' : ''); ?>"
                     onerror="this.src='../assets/images/logo.png'">
            </td>

            <!-- Name + inline tags -->
            <td style="font-weight:600;">
                <?php echo htmlspecialchars($row['item_name']); ?>
                <?php if(!$is_available && $is_active): ?>
                    <span class="row-tag tag-oos">🚫 OOS</span>
                <?php endif; ?>
                <?php if(!$is_active): ?>
                    <span class="row-tag tag-hidden">🙈 Hidden</span>
                <?php endif; ?>
            </td>

            <!-- Price -->
            <td style="font-size:13px; white-space:nowrap;">
                <?php
                if($row['category']=='Pizza' && $row['small_price'])
                    echo "S:₹{$row['small_price']} M:₹{$row['medium_price']} L:₹{$row['large_price']}";
                elseif($row['category']=='Cakes' && $row['small_price'])
                    echo "500g:₹{$row['small_price']} | 1kg:₹{$row['medium_price']}";
                elseif(in_array($row['category'],['Shakes','Coffee']) && $row['small_price'])
                    echo "Reg:₹{$row['small_price']} | +IC:₹{$row['medium_price']}";
                else
                    echo "₹{$row['price']}";
                ?>
            </td>

            <!-- Category -->
            <td><span class="category-badge"><?php echo $row['category']; ?></span></td>

            <!-- TWO TOGGLES -->
            <td>
                <div class="toggles-cell">

                    <!-- Toggle 1 : Out of Stock -->
                    <div class="toggle-row">
                        <a class="toggle-link"
                           href="toggle_stock.php?id=<?php echo $row['id']; ?>&back=<?php echo $back_url; ?>"
                           onclick="event.preventDefault(); var url=this.href; showConfirm('<?php echo addslashes($oos_msg); ?>', function(){ window.location.href=url; }, {icon:'📦', okText:'Yes, Confirm'});">
                            <span class="t-switch oos-sw">
                                <input type="checkbox" <?php echo $is_available ? 'checked' : ''; ?> tabindex="-1">
                                <span class="t-slider"></span>
                            </span>
                        </a>
                        <span class="s-badge <?php echo $is_available ? 'sb-instock' : 'sb-oos'; ?>">
                            <?php echo $is_available ? '✅ In Stock' : '🚫 Out of Stock'; ?>
                        </span>
                    </div>

                    <div class="toggle-sep"></div>

                    <!-- Toggle 2 : Hide / Show on Menu -->
                    <div class="toggle-row">
                        <a class="toggle-link"
                           href="toggle_visible.php?id=<?php echo $row['id']; ?>&back=<?php echo $back_url; ?>"
                           onclick="event.preventDefault(); var url=this.href; showConfirm('<?php echo addslashes($vis_msg); ?>', function(){ window.location.href=url; }, {icon:'👁', okText:'Yes, Confirm'});">
                            <span class="t-switch vis-sw">
                                <input type="checkbox" <?php echo $is_active ? 'checked' : ''; ?> tabindex="-1">
                                <span class="t-slider"></span>
                            </span>
                        </a>
                        <span class="s-badge <?php echo $is_active ? 'sb-visible' : 'sb-hidden'; ?>">
                            <?php echo $is_active ? '👁 On Menu' : '🙈 Hidden'; ?>
                        </span>
                    </div>

                </div>
            </td>

            <!-- Actions -->
            <td style="white-space:nowrap;">
                <a class="edit"   href="edit_item.php?id=<?php echo $row['id']; ?>">Edit</a>
                <a class="delete" href="delete_item.php?id=<?php echo $row['id']; ?>"
                   onclick="event.preventDefault(); var url=this.href; showConfirm('Are you sure you want to delete this item? This cannot be undone.', function(){ window.location.href=url; }, {icon:'🗑️', okText:'Yes, Delete', okClass:'gcm-btn-danger'});">Delete</a>
            </td>

        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

</div>
<?php include "includes/footer.php"; ?>
</body>
</html>