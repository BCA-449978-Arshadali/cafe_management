<?php
    session_start();
    include 'includes/db.php';

    // Allow only admin
    if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
        header("Location: login.php");
        exit;
    }
    <?php
    include('../config.php');
    ?>
    <?php
    // connect DB
    include('../config.php');

    // start session
    session_start();

    // if NOT logged in → redirect to login
    if(!isset($_SESSION['admin'])){
        header("Location: login.php");
        exit();
    }

    // If logged in → redirect to dashboard
    header("Location: dashboard.php");
    exit();
?>
