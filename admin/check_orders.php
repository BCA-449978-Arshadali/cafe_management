<?php

include 'includes/auth.php';
include 'includes/db.php';

$result = mysqli_query($conn,"
SELECT COUNT(*) as total 
FROM orders 
WHERE status='Pending'
");

$row = mysqli_fetch_assoc($result);

echo $row['total'];

?>