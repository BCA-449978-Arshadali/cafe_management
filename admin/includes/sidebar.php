<?php
// =============================================
//  admin/includes/sidebar.php
//  ✅ Ab modules.php se auto-generate hota hai
//  Kuch bhi change karna ho? modules.php kholo!
// =============================================

// modules.php load karo (agar pehle nahi hua)
$modules_path = dirname(dirname(__DIR__)) . '/modules.php';
if(file_exists($modules_path) && !isset($ADMIN_NAV)){
    include $modules_path;
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="sidebar" id="sidebar">

    <div class="logo-box">
        <img src="../assets/images/droppers-logo.png" alt="Logo">
        <div>
            <span><?= $SITE['name'] ?? 'Droppers Café' ?></span>
            <small>Admin Panel</small>
        </div>
    </div>

    <div class="sidebar-links">
        <?php render_admin_nav($conn); ?>
    </div>

    <div class="sidebar-bottom">
        <a href="logout.php">
            <i class="fa fa-right-from-bracket"></i> Logout
        </a>
    </div>

</div>