<?php
include 'includes/auth.php';
include 'includes/db.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Feedback</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">

    <div class="topbar"><h2>Customer Feedback</h2></div>

    <table class="table">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Message</th>
            <th>Rating</th>
        </tr>

        <?php
        $query = mysqli_query($conn, "SELECT * FROM feedback ORDER BY id DESC");
        while($row = mysqli_fetch_array($query))
        {
        ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['message']); ?></td>
            <td>
                <?php 
                    for($i=1; $i<=5; $i++){
                        if($i <= $row['rating']) echo "⭐";
                        else echo "☆";
                    }
                ?>
            </td>
        </tr>

        <?php } ?>

    </table>

</div>

</body>
</html>