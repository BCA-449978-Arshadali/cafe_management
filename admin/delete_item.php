<?php
include 'includes/auth.php';
include 'includes/db.php';

$id = intval($_GET['id']);

mysqli_query($conn, "DELETE FROM menu WHERE id='$id'");

header("Location: view_items.php");
exit;
?>