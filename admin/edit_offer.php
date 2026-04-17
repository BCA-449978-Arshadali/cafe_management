<?php
include 'includes/auth.php';
include 'includes/db.php';

$id    = intval($_GET['id'] ?? 0);
$offer = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM offers WHERE id=$id LIMIT 1"));
if(!$offer) { header("Location: view_offers.php"); exit; }

$success = '';
$error   = '';

if(isset($_POST['edit_offer'])) {
    $title          = trim($_POST['title'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $discount_type  = $_POST['discount_type'] ?? 'percent';
    $discount_value = floatval($_POST['discount_value'] ?? 0);
    $promo_code     = strtoupper(trim($_POST['promo_code'] ?? ''));
    $valid_from     = $_POST['valid_from']  ?: null;
    $valid_until    = $_POST['valid_until'] ?: null;
    $is_active      = isset($_POST['is_active']) ? 1 : 0;

    if(empty($title)) { $error = 'Title is required!'; }
    elseif($discount_value <= 0) { $error = 'Discount value must be greater than 0!'; }
    else {
        $image = $offer['image'];

        if(!empty($_FILES['image']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if(in_array($ext, $allowed)) {
                // Delete old image
                if(!empty($image) && file_exists("../assets/images/".$image)) unlink("../assets/images/".$image);
                $image = 'offer_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/".$image);
            } else {
                $error = 'Only JPG, PNG, WEBP images are allowed!';
            }
        }

        if(empty($error)) {
            $promo_code = empty($promo_code) ? null : $promo_code;
            $stmt = $conn->prepare("UPDATE offers SET title=?, description=?, discount_type=?, discount_value=?, promo_code=?, image=?, valid_from=?, valid_until=?, is_active=? WHERE id=?");
            $stmt->bind_param("sssdssssii", $title, $description, $discount_type, $discount_value, $promo_code, $image, $valid_from, $valid_until, $is_active, $id);
            $stmt->execute();
            $success = 'Offer updated successfully! ✅';
            $offer   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM offers WHERE id=$id LIMIT 1"));
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Offer — Droppers Café Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.form-card { background:var(--bg2); border-radius:var(--radius); border:1px solid var(--border); padding:32px; max-width:700px; }
.form-group { margin-bottom:20px; }
.form-group label { display:block; font-size:13px; font-weight:600; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px; }
.form-group input, .form-group textarea, .form-group select { width:100%; padding:12px 16px; background:var(--bg3); border:1px solid var(--border); border-radius:var(--radius-sm); color:var(--text); font-size:14px; font-family:'Poppins',sans-serif; transition:border-color 0.2s; }
.form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline:none; border-color:var(--orange); }
.form-group textarea { min-height:100px; resize:vertical; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.toggle-wrap { display:flex; align-items:center; gap:12px; background:var(--bg3); padding:14px 16px; border-radius:var(--radius-sm); border:1px solid var(--border); }
.toggle-wrap input[type=checkbox] { width:18px; height:18px; accent-color:var(--orange); cursor:pointer; }
.btn-submit { background:var(--orange); color:#fff; border:none; padding:13px 32px; border-radius:var(--radius-sm); font-size:15px; font-weight:600; cursor:pointer; transition:0.2s; font-family:'Poppins',sans-serif; display:inline-flex; align-items:center; gap:8px; }
.btn-submit:hover { background:var(--orange2); }
.alert { padding:14px 18px; border-radius:var(--radius-sm); margin-bottom:20px; font-size:14px; font-weight:500; }
.alert-success { background:rgba(39,174,96,0.1); border:1px solid rgba(39,174,96,0.3); color:#27ae60; }
.alert-error   { background:rgba(231,76,60,0.1);  border:1px solid rgba(231,76,60,0.3);  color:#e74c3c; }
.current-img { width:120px; height:80px; object-fit:cover; border-radius:8px; border:1px solid var(--border); margin-top:10px; }
</style>
</head>
<body>
<?php include "includes/sidebar.php"; ?>
<div class="main">
    <div class="topbar">
        <h2><i class="fa fa-pen" style="color:var(--orange);font-size:20px;"></i> Edit Offer</h2>
        <div class="topbar-right">
            <a href="view_offers.php" style="background:var(--bg3); color:var(--text); padding:8px 16px; border-radius:8px; font-size:13px; border:1px solid var(--border);">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if($success): ?><div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if($error):   ?><div class="alert alert-error"><i class="fa fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

    <div class="form-card">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Offer Title *</label>
                <input type="text" name="title" required value="<?= htmlspecialchars($offer['title']) ?>">
            </div>
            <div class="form-group">
                <label>Description *</label>
                <textarea name="description"><?= htmlspecialchars($offer['description']) ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Discount Type *</label>
                    <select name="discount_type" id="dtype" onchange="updateLabel()">
                        <option value="percent" <?= $offer['discount_type']=='percent'?'selected':'' ?>>Percentage (%) off</option>
                        <option value="flat"    <?= $offer['discount_type']=='flat'?'selected':'' ?>>Flat (₹) off</option>
                    </select>
                </div>
                <div class="form-group">
                    <label id="disc-label">Discount Value *</label>
                    <input type="number" name="discount_value" id="dvalue" min="1" step="0.01" required value="<?= $offer['discount_value'] ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Promo Code <span style="color:var(--text-dim); font-weight:400;">(Optional)</span></label>
                <input type="text" name="promo_code" style="text-transform:uppercase;" value="<?= htmlspecialchars($offer['promo_code'] ?? '') ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Valid From</label>
                    <input type="date" name="valid_from" value="<?= $offer['valid_from'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Valid Until</label>
                    <input type="date" name="valid_until" value="<?= $offer['valid_until'] ?? '' ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Offer Image <span style="color:var(--text-dim); font-weight:400;">(Upload new or leave blank to keep current)</span></label>
                <?php if(!empty($offer['image']) && file_exists("../assets/images/".$offer['image'])): ?>
                    <img src="../assets/images/<?= htmlspecialchars($offer['image']) ?>" class="current-img" alt="current"><br>
                <?php endif; ?>
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" style="margin-top:10px;">
            </div>
            <div class="form-group">
                <div class="toggle-wrap">
                    <input type="checkbox" name="is_active" id="is_active" <?= $offer['is_active'] ? 'checked' : '' ?>>
                    <label for="is_active" style="margin:0; text-transform:none; letter-spacing:0; cursor:pointer; font-size:14px;">
                        Offer is Active (visible to customers)
                    </label>
                </div>
            </div>
            <button type="submit" name="edit_offer" class="btn-submit">
                <i class="fa fa-save"></i> Save Changes
            </button>
        </form>
    </div>
</div>
<script>
function updateLabel(){
    var dtype = document.getElementById('dtype').value;
    document.getElementById('disc-label').textContent = dtype === 'percent' ? 'Discount Value (%) *' : 'Discount Value (₹) *';
}
updateLabel();
</script>
</body>
</html>