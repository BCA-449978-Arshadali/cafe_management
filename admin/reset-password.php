<?php
include 'includes/db.php';

// Token check
if(!isset($_GET['token'])){
    header("Location: login.php");
    exit;
}

$token = $_GET['token'];

// Verify token valid hai aur expire nahi hua
$stmt = $conn->prepare("SELECT * FROM password_resets WHERE token=? AND expire_at >= NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    echo "<script>alert('Link expired or invalid! Please request a new one.'); window.location='forgot-password.php';</script>";
    exit;
}

$data  = $result->fetch_assoc();
$email = $data['email'];

if(isset($_POST['reset']))
{
    $new_password = $_POST['password'] ?? '';

    if(strlen($new_password) < 6){
        $error = "Password must be at least 6 characters!";
    } else {
        // ✅ Password hash karo pehle!
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $upd = $conn->prepare("UPDATE admin SET password=? WHERE email=?");
        $upd->bind_param("ss", $hashed, $email);
        $upd->execute();

        // Delete used token
        $del = $conn->prepare("DELETE FROM password_resets WHERE token=?");
        $del->bind_param("s", $token);
        $del->execute();

        echo "<script>alert('Password Updated Successfully!'); window.location='login.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Reset Password</title>
<link rel="stylesheet" href="../assets/css/admin-login.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="admin-login">
<div class="left-side">
    <img src="../assets/images/droppers-logo.png" class="logo">
    <p>Admin Password Reset</p>
    <h1>Droppers Cafe & Resto</h1>
</div>
<div class="divider"></div>
<div class="right-side">
    <h2>Reset Password</h2>
    <p>Enter your new password</p>

    <?php if(isset($error)){ ?>
    <div class="error"><?php echo $error; ?></div>
    <?php } ?>

    <form method="POST">
        <input type="password" name="password" placeholder="New Password (min 6 chars)" required>
        <button type="submit" name="reset">Reset Password</button>
    </form>

    <a href="login.php">Back to Login</a>
</div>
</div>

</body>
</html>