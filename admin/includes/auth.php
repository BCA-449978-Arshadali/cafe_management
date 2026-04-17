
<?php
// ✅ Session lifetime: 2 ghante (7200 seconds)
ini_set('session.gc_maxlifetime', 7200);
session_set_cookie_params(7200);

session_start();

// ✅ Login check
if(!isset($_SESSION['admin']) || $_SESSION['admin'] != 1){
    header("Location: login.php");
    exit();
}

// ✅ Activity-based timeout: 30 minute inactivity pe logout
$timeout = 30 * 60; // 30 minutes
if(isset($_SESSION['last_activity'])){
    if(time() - $_SESSION['last_activity'] > $timeout){
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
}
$_SESSION['last_activity'] = time(); // Har page visit pe update
?>