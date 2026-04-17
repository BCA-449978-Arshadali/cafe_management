<?php
include 'includes/auth.php';
include 'includes/db.php';

$id = intval($_GET['id']);
$data = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM menu WHERE id=$id"));

if(isset($_POST['update']))
{
    $name     = trim($_POST['name']);
    $category = trim($_POST['category']);

    $price  = $_POST['price'] ?? NULL;
    $small  = $_POST['small_price'] ?? NULL;
    $medium = $_POST['medium_price'] ?? NULL;
    $large  = $_POST['large_price'] ?? NULL;

    // Category ke hisaab se prices set karo
    $sp = NULL; $mp = NULL; $lp = NULL;

    if($category == 'Pizza'){
        $sp = $small; $mp = $medium; $lp = $large; $price = NULL;
    } elseif($category == 'Cakes'){
        $sp = $small; $mp = $medium; $price = NULL;
    } elseif($category == 'Shakes'){
        $sp = $small; $mp = $medium; $price = NULL;
    }

    // ✅ FIX: Image validation — extension + MIME type check
    if($_FILES['image']['name'] != "" && $_FILES['image']['error'] == 0){

        $ext          = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExt   = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);

        if(!in_array($ext, $allowedExt) || !in_array($mime, $allowedMimes)){
            $error = "Sirf JPG, PNG, WEBP images allowed hain!";
        } elseif($_FILES['image']['size'] > 3 * 1024 * 1024){
            $error = "Image size 3MB se zyada nahi honi chahiye!";
        } else {
            // ✅ FIX: Unique filename — overwrite issue nahi hoga
            $image = uniqid('item_') . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/" . $image);

            // ✅ FIX: Prepared statement — SQL injection safe
            $stmt = $conn->prepare("UPDATE menu SET
                item_name=?, price=?, small_price=?, medium_price=?, large_price=?,
                category=?, image=? WHERE id=?");
            $stmt->bind_param("sssssssi", $name, $price, $sp, $mp, $lp, $category, $image, $id);
            $stmt->execute();
            $msg = "Item Updated Successfully!";
        }

    } else {
        // ✅ FIX: Prepared statement — image nahi badla
        $stmt = $conn->prepare("UPDATE menu SET
            item_name=?, price=?, small_price=?, medium_price=?, large_price=?,
            category=? WHERE id=?");
        $stmt->bind_param("ssssssi", $name, $price, $sp, $mp, $lp, $category, $id);
        $stmt->execute();
        $msg = "Item Updated Successfully!";
    }

    // Refresh data
    $data = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM menu WHERE id=$id"));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Item</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">
    <div class="topbar"><h2>Edit Menu Item</h2></div>

    <div class="form-card">
        <form method="POST" enctype="multipart/form-data">

            <?php if(isset($error)){ echo "<p class='error' style='color:red;margin-bottom:12px;'>⚠️ $error</p>"; } ?>

            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($data['item_name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Category</label>
                <select name="category" id="category" onchange="handleCategory()">
                    <?php
                    $categories = ['Pizza','Burger','Sandwich','Shakes','Coffee','Cakes','Dessert','Hot Dog','Muffin','Donut','Drinks','Rolls','Rice','Noodles','Soup','Bread','Chinese','Momos'];
                    foreach($categories as $cat){
                        $sel = ($data['category'] == $cat) ? 'selected' : '';
                        echo "<option $sel>$cat</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group" id="normal_price">
                <label>Price (₹)</label>
                <input type="number" name="price" value="<?php echo $data['price']; ?>">
            </div>

            <div id="pizza_options" style="display:none;">
                <div class="form-group">
                    <label>Small Price (₹)</label>
                    <input type="number" name="small_price" value="<?php echo $data['small_price']; ?>">
                </div>
                <div class="form-group">
                    <label>Medium Price (₹)</label>
                    <input type="number" name="medium_price" value="<?php echo $data['medium_price']; ?>">
                </div>
                <div class="form-group">
                    <label>Large Price (₹)</label>
                    <input type="number" name="large_price" value="<?php echo $data['large_price']; ?>">
                </div>
            </div>

            <div id="cake_options" style="display:none;">
                <div class="form-group">
                    <label>500g Price (₹)</label>
                    <input type="number" name="small_price" value="<?php echo $data['small_price']; ?>">
                </div>
                <div class="form-group">
                    <label>1kg Price (₹)</label>
                    <input type="number" name="medium_price" value="<?php echo $data['medium_price']; ?>">
                </div>
            </div>

            <div id="shake_options" style="display:none;">
                <div class="form-group">
                    <label>Without Ice Cream (₹)</label>
                    <input type="number" name="small_price" value="<?php echo $data['small_price']; ?>">
                </div>
                <div class="form-group">
                    <label>With Ice Cream (₹)</label>
                    <input type="number" name="medium_price" value="<?php echo $data['medium_price']; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Current Image</label><br>
                <img src="../assets/images/<?php echo htmlspecialchars($data['image']); ?>" height="100" style="border-radius:8px; margin-top:8px;"><br><br>
                <label>Upload New Image (optional — JPG, PNG, WEBP | Max 3MB)</label>
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
            </div>

            <button class="add-btn" name="update">Update Item</button>
        </form>

        <?php if(isset($msg)){ echo "<p class='success'>✅ $msg</p>"; } ?>
    </div>
</div>

<script>
function handleCategory() {
    var cat = document.getElementById('category').value;
    document.getElementById('normal_price').style.display  = 'none';
    document.getElementById('pizza_options').style.display = 'none';
    document.getElementById('cake_options').style.display  = 'none';
    document.getElementById('shake_options').style.display = 'none';

    if(cat === 'Pizza')       document.getElementById('pizza_options').style.display = 'block';
    else if(cat === 'Cakes')  document.getElementById('cake_options').style.display  = 'block';
    else if(cat === 'Shakes') document.getElementById('shake_options').style.display = 'block';
    else                      document.getElementById('normal_price').style.display  = 'block';
}
window.onload = handleCategory;
</script>
</body>
</html>