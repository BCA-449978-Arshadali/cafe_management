<?php
// admin/toggle_stock.php
// Ek item ka stock status flip karta hai (Available <-> Out of Stock)

include 'includes/auth.php';
include 'includes/db.php';

if(isset($_GET['id'])){
    $id = intval($_GET['id']);

    // Current status fetch karo
    $result = mysqli_query($conn, "SELECT is_available FROM menu WHERE id=$id");
    $row    = mysqli_fetch_assoc($result);

    if($row){
        $new_status = ($row['is_available'] == 1) ? 0 : 1;
        mysqli_query($conn, "UPDATE menu SET is_available=$new_status WHERE id=$id");
    }
}

// Safe redirect - sirf allowed pages pe bhejo
$allowed = ['view_items.php'];
$back = isset($_GET['back']) ? urldecode($_GET['back']) : 'view_items.php';
if(!in_array(basename(parse_url($back, PHP_URL_PATH)), $allowed)){
    $back = 'view_items.php';
}
header("Location: $back");
exit;
?>