<?php
include 'includes/auth.php';
include 'includes/db.php';

if(isset($_POST['add']))
{
    $name     = mysqli_real_escape_string($conn, trim($_POST['name']));
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));

    $price  = $_POST['price']          ?? NULL;
    $small  = $_POST['small_price']    ?? NULL;
    $medium = $_POST['medium_price']   ?? NULL;
    $large  = $_POST['large_price']    ?? NULL;
    $half   = $_POST['half_kg_price']  ?? NULL;
    $one    = $_POST['one_kg_price']   ?? NULL;
    $normal = $_POST['normal_price']   ?? NULL;
    $ice    = $_POST['icecream_price'] ?? NULL;

    // ✅ Name: kam se kam ek alphabet hona chahiye
    if(!preg_match('/[a-zA-Z]/', $name)){
        $error = "Item name mein kam se kam ek alphabet hona chahiye!";
    } else {

        // ✅ Price: koi bhi negative nahi honi chahiye
        $allPrices = array_filter([$price, $small, $medium, $large, $half, $one, $normal, $ice], fn($v) => $v !== NULL && $v !== '');
        $hasNegative = false;
        foreach($allPrices as $p){
            if(floatval($p) < 0){ $hasNegative = true; break; }
        }

        if($hasNegative){
            $error = "Price negative nahi ho sakti! Sirf 0 ya usse zyada value daalo.";
        } else {

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
            $allowedMimes = ['image/jpeg','image/png','image/webp'];
            if(in_array($ext, $allowed) && in_array($mime, $allowedMimes)){
                // then upload
            }

            $sp = NULL; $mp = NULL; $lp = NULL;

            if($category == "Pizza"){
                $sp = $small; $mp = $medium; $lp = $large;
            } else if($category == "Cakes"){
                $sp = $half;  $mp = $one;
            } else if($category == "Shakes"){
                $sp = $normal; $mp = $ice;
            }

            $stmt = $conn->prepare("INSERT INTO menu(item_name,price,small_price,medium_price,large_price,category,image) VALUES(?,?,?,?,?,?,?)");
            $stmt->bind_param("sddddss", $name, $price, $sp, $mp, $lp, $category, $image);
            $stmt->execute();

            if($query){
                $msg = "Menu Item Added Successfully!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Menu Item</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main">
    <div class="form-card">

<h2>Add Menu Item</h2>

<?php if(isset($error)): ?>
<div class="error-msg" style="background:rgba(231,76,60,0.1);border:1px solid #e74c3c;color:#e74c3c;padding:12px 16px;border-radius:10px;font-size:14px;margin-bottom:16px;">
    ⚠️ <?php echo $error; ?>
</div>
<?php endif; ?>

<?php if(isset($msg)): ?>
<p class='success'><?php echo $msg; ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">

<div class="form-group">
<label>Item Name</label>
<!-- ✅ pattern: kam se kam ek alphabet -->
<input type="text" name="name" placeholder="Enter item name"
    pattern=".*[a-zA-Z].*"
    title="Item name mein kam se kam ek alphabet hona chahiye!"
    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
    required>
</div>

<!-- Normal Price -->
<div class="form-group" id="normal_price">
<label>Price (₹)</label>
<input type="number" name="price" min="0" step="0.01" placeholder="Enter price">
</div>

<!-- Pizza Options -->
<div id="pizza_options" style="display:none;">
<div class="form-group">
<label>Small Price</label>
<input type="number" name="small_price" min="0" step="0.01" placeholder="Enter price">
</div>
<div class="form-group">
<label>Medium Price</label>
<input type="number" name="medium_price" min="0" step="0.01" placeholder="Enter price">
</div>
<div class="form-group">
<label>Large Price</label>
<input type="number" name="large_price" min="0" step="0.01" placeholder="Enter price">
</div>
</div>

<!-- Cake Options -->
<div id="cake_options" style="display:none;">
<div class="form-group">
<label>500g Price</label>
<input type="number" name="half_kg_price" min="0" step="0.01" placeholder="Enter price">
</div>
<div class="form-group">
<label>1kg Price</label>
<input type="number" name="one_kg_price" min="0" step="0.01" placeholder="Enter price">
</div>
</div>

<!-- Shake Options -->
<div id="shake_options" style="display:none;">
<div class="form-group">
<label>Without Ice Cream</label>
<input type="number" name="normal_price" min="0" step="0.01" placeholder="Enter price">
</div>
<div class="form-group">
<label>With Ice Cream</label>
<input type="number" name="icecream_price" min="0" step="0.01" placeholder="Enter price">
</div>
</div>

<div class="form-group">
<label>Category</label>
<select name="category" id="category" onchange="handleCategory()">
<option>Pizza</option>
<option>Burger</option>
<option>Sandwich</option>
<option>Shakes</option>
<option>Coffee</option>
<option>Cakes</option>
<option>Dessert</option>
<option>Hot Dog</option>
<option>Muffin</option>
<option>Donut</option>
<option>Drinks</option>
<option>Rolls</option>
<option>Rice</option>
<option>Noodles</option>
<option>Soup</option>
<option>Bread</option>
<option>Chinese</option>
<option>Momos</option>
</select>
</div>

<div class="form-group">
<label>Item Image</label>
<input type="file" name="image" required>
</div>

<button class="add-btn" name="add">Add Item</button>

</form>

</div>
</div>

<script>
function handleCategory() {
    var cat = document.getElementById('category').value;
    document.getElementById('normal_price').style.display  = 'none';
    document.getElementById('pizza_options').style.display = 'none';
    document.getElementById('cake_options').style.display  = 'none';
    document.getElementById('shake_options').style.display = 'none';

    if (cat === 'Pizza')       document.getElementById('pizza_options').style.display = 'block';
    else if (cat === 'Cakes')  document.getElementById('cake_options').style.display  = 'block';
    else if (cat === 'Shakes') document.getElementById('shake_options').style.display = 'block';
    else                       document.getElementById('normal_price').style.display  = 'block';
}

function validateForm(){
    // ✅ Name: Item name must contain at least one alphabet
    const name = document.querySelector('input[name="name"]').value.trim();
    if(!/[a-zA-Z]/.test(name)){
        alert('Item name must contain at least one alphabet! (e.g. "7UP", "Combo 1" OK hai, "123" nahi)');
        return false;
    }

    // ✅ Price: The price cannot be negative! Just enter a value of 0 or greater.
    const inputs = document.querySelectorAll('input[type="number"]');
    for(let inp of inputs){
        if(inp.value !== '' && parseFloat(inp.value) < 0){
            alert('The price cannot be negative! Just enter a value of 0 or greater.');
            inp.focus();
            return false;
        }
    }

    return true;
}

window.onload = handleCategory;
</script>

</body>
</html>