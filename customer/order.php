
<?php 
require_once 'includes/auth-check.php';
include "../config.php";
if(isset($_POST['confirm']))
{
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $qty = $_POST['qty'];
    $itemname = $item['item_name'];
    $price = $item['price'] * $qty;

    $order_text = $itemname . " (Qty: $qty)";

    mysqli_query($conn, 
        "INSERT INTO orders(customer_name, customer_phone, items, total_amount)
         VALUES('$name', '$phone', '$order_text', '$price')");

    header("Location: order_success.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order - Droppers Café</title>
    <link rel="stylesheet" href="../assets/css/customer.css">
</head>
<body>

<div class="nav">
    <h2>☕ Droppers Café</h2>
    <a href="index.php">Home</a>
    <a href="menu.php">Menu</a>
    <a href="book_table.php">Book Table</a>
    <a href="feedback.php">Feedback</a>
</div>

<h1 class="title">Order Your Item</h1>

<div class="order-box">

    <img src="../assets/images/<?php echo $item['image']; ?>" class="order-img">

    <h2><?php echo $item['item_name']; ?></h2>
    <p class="price">Price: ₹<?php echo $item['price']; ?></p>

    <form method="POST">
        
        <label>Your Name</label>
        <input type="text" name="name" required>

        <label>Phone Number</label>
        <input type="text" name="phone" required>

        <label>Quantity</label>
        <input type="number" name="qty" id="qty" value="1" min="1" required>

        <label>Total Price</label>
        <input type="text" id="total" value="<?php echo $item['price']; ?>" readonly>

        <button name="confirm">Confirm Order</button>
    </form>

</div>

<script>
// AUTO PRICE CALCULATION
let price = <?php echo $item['price']; ?>;
let qty = document.getElementById('qty');
let total = document.getElementById('total');

qty.addEventListener('change', () => {
    total.value = price * qty.value;
});
</script>

</body>
</html>
