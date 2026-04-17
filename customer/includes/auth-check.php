<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['customer_id'])) {
    header("Location: " . BASE_URL . "/customer/login.php");
    exit();
}
?>