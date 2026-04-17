<?php
// admin/toggle_visible.php
// Toggles is_active (show/hide item completely from customer menu)

include 'includes/auth.php';
include 'includes/db.php';

// Auto-add is_active column if missing
$_col = mysqli_query($conn, "SHOW COLUMNS FROM menu LIKE 'is_active'");
if(mysqli_num_rows($_col) == 0){
    mysqli_query($conn, "ALTER TABLE menu ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
}

$id   = intval($_GET['id']  ?? 0);
$back = $_GET['back'] ?? 'view_items.php';

if($id > 0){
    $res = mysqli_query($conn, "SELECT is_active FROM menu WHERE id=$id LIMIT 1");
    $row = mysqli_fetch_assoc($res);

    if($row){
        $new_val = ($row['is_active'] == 1) ? 0 : 1;
        mysqli_query($conn, "UPDATE menu SET is_active=$new_val WHERE id=$id");
    }
}

// Safe redirect
$allowed = ['view_items.php'];
$back_clean = basename(parse_url($back, PHP_URL_PATH));
if(!in_array($back_clean, $allowed)){
    $back = 'view_items.php';
}
header("Location: $back");
exit;