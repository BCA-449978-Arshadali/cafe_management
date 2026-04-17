<?php
session_start();

// ✅ FIX: Pehle session variables clear karo, phir destroy
session_unset();
session_destroy();

header("Location: login.php");
exit();
?>