<?php
include 'includes/auth.php';
include 'includes/db.php';

// Auto-create offers table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `offers` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `title`          VARCHAR(150) NOT NULL,
  `description`    TEXT         NOT NULL,
  `discount_type`  ENUM('percent','flat') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `promo_code`     VARCHAR(50)  DEFAULT NULL,
  `image`          VARCHAR(255) DEFAULT NULL,
  `valid_from`     DATE         DEFAULT NULL,
  `valid_until`    DATE         DEFAULT NULL,
  `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$success = '';
$error   = '';

if(isset($_POST['add_offer'])) {
    $title          = trim($_POST['title'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $discount_type  = $_POST['discount_type'] ?? 'percent';
    $discount_value = floatval($_POST['discount_value'] ?? 0);
    $promo_code     = strtoupper(trim($_POST['promo_code'] ?? ''));
    $valid_from     = $_POST['valid_from'] ?? null;
    $valid_until    = $_POST['valid_until'] ?? null;
    $is_active      = isset($_POST['is_active']) ? 1 : 0;

    if(empty($title)) {
        $error = 'Offer title is required!';
    } elseif($discount_value <= 0) {
        $error = 'Discount value must be greater than 0!';
    } else {
        // Image upload
        $image = null;
        if(!empty($_FILES['image']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if(in_array($ext, $allowed)) {
                $image = 'offer_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/" . $image);
            } else {
                $error = 'Only JPG, PNG, WEBP images are allowed!';
            }
        }

        if(empty($error)) {
            $promo_code  = empty($promo_code) ? null : $promo_code;
            $valid_from  = empty($valid_from)  ? null : $valid_from;
            $valid_until = empty($valid_until) ? null : $valid_until;

            $stmt = $conn->prepare("INSERT INTO offers (title, description, discount_type, discount_value, promo_code, image, valid_from, valid_until, is_active) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssdssssi", $title, $description, $discount_type, $discount_value, $promo_code, $image, $valid_from, $valid_until, $is_active);
            if($stmt->execute()) {
                $success = 'Offer added successfully! 🎉';
            } else {
                $error = 'Something went wrong, please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Offer — Droppers Café Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.form-card {
    background:var(--bg2); border-radius:var(--radius);
    border:1px solid var(--border); padding:32px;
    max-width:700px;
}
.form-group { margin-bottom:20px; }
.form-group label {
    display:block; font-size:13px; font-weight:600;
    color:var(--text-muted); margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;
}
.form-group input, .form-group textarea, .form-group select {
    width:100%; padding:12px 16px;
    background:var(--bg3); border:1px solid var(--border);
    border-radius:var(--radius-sm); color:var(--text);
    font-size:14px; font-family:'Poppins',sans-serif;
    transition:border-color 0.2s;
}
.form-group input:focus, .form-group textarea:focus, .form-group select:focus {
    outline:none; border-color:var(--orange);
}
.form-group textarea { min-height:100px; resize:vertical; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.toggle-wrap {
    display:flex; align-items:center; gap:12px;
    background:var(--bg3); padding:14px 16px;
    border-radius:var(--radius-sm); border:1px solid var(--border);
}
.toggle-wrap input[type=checkbox] { width:18px; height:18px; accent-color:var(--orange); cursor:pointer; }
.btn-submit {
    background:var(--orange); color:#fff;
    border:none; padding:13px 32px;
    border-radius:var(--radius-sm); font-size:15px;
    font-weight:600; cursor:pointer; transition:0.2s;
    font-family:'Poppins',sans-serif; display:inline-flex; align-items:center; gap:8px;
}
.btn-submit:hover { background:var(--orange2); }
.alert { padding:14px 18px; border-radius:var(--radius-sm); margin-bottom:20px; font-size:14px; font-weight:500; }
.alert-success { background:rgba(39,174,96,0.1); border:1px solid rgba(39,174,96,0.3); color:#27ae60; }
.alert-error   { background:rgba(231,76,60,0.1);  border:1px solid rgba(231,76,60,0.3);  color:#e74c3c; }
.image-preview { margin-top:10px; }
.image-preview img { width:120px; height:80px; object-fit:cover; border-radius:8px; border:1px solid var(--border); }
</style>
</head>
<body>
<?php include "includes/sidebar.php"; ?>

<div class="main">
    <div class="topbar">
        <h2><i class="fa fa-tags" style="color:var(--orange);font-size:20px;"></i> Add New Offer</h2>
        <div class="topbar-right">
            <a href="view_offers.php" style="background:var(--bg3); color:var(--text); padding:8px 16px; border-radius:8px; font-size:13px; border:1px solid var(--border);">
                <i class="fa fa-list"></i> View All Offers
            </a>
        </div>
    </div>

    <?php if($success): ?>
    <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if($error): ?>
    <div class="alert alert-error"><i class="fa fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label>Offer Title *</label>
                <input type="text" name="title" placeholder="e.g. Weekend Special, Happy Hours" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Description *</label>
                <textarea name="description" placeholder="Describe the offer — what's included, conditions, etc."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Discount Type *</label>
                    <select name="discount_type" id="dtype" onchange="updateLabel()">
                        <option value="percent" <?= ($_POST['discount_type']??'percent')=='percent'?'selected':'' ?>>Percentage (%) off</option>
                        <option value="flat"    <?= ($_POST['discount_type']??'')=='flat'?'selected':'' ?>>Flat (₹) off</option>
                    </select>
                </div>
                <div class="form-group">
                    <label id="disc-label">Discount Value (%) *</label>
                    <input type="number" name="discount_value" id="dvalue" min="1" step="0.01"
                           placeholder="e.g. 20" required
                           value="<?= htmlspecialchars($_POST['discount_value'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Promo Code <span style="color:var(--text-dim); font-weight:400;">(Optional)</span></label>
                <input type="text" name="promo_code" placeholder="e.g. WELCOME20, WEEKEND50"
                       style="text-transform:uppercase;"
                       value="<?= htmlspecialchars($_POST['promo_code'] ?? '') ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Valid From <span style="color:var(--text-dim); font-weight:400;">(Optional)</span></label>
                    <input type="date" name="valid_from" value="<?= $_POST['valid_from'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Valid Until <span style="color:var(--text-dim); font-weight:400;">(Optional)</span></label>
                    <input type="date" name="valid_until" value="<?= $_POST['valid_until'] ?? '' ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Offer Image <span style="color:var(--text-dim); font-weight:400;">(Optional — JPG/PNG/WEBP)</span></label>
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" onchange="previewImg(this)">
                <div class="image-preview" id="imgPreview"></div>
            </div>

            <div class="form-group">
                <div class="toggle-wrap">
                    <input type="checkbox" name="is_active" id="is_active" checked>
                    <label for="is_active" style="margin:0; text-transform:none; letter-spacing:0; cursor:pointer; font-size:14px;">
                        Activate offer now (visible to customers)
                    </label>
                </div>
            </div>

            <button type="submit" name="add_offer" class="btn-submit">
                <i class="fa fa-plus"></i> Add Offer
            </button>
        </form>
    </div>
</div>

<script>
function updateLabel(){
    var dtype = document.getElementById('dtype').value;
    document.getElementById('disc-label').textContent =
        dtype === 'percent' ? 'Discount Value (%) *' : 'Discount Value (₹) *';
    document.getElementById('dvalue').placeholder =
        dtype === 'percent' ? 'e.g. 20 (means 20% off)' : 'e.g. 50 (means ₹50 off)';
}
function previewImg(input){
    var preview = document.getElementById('imgPreview');
    preview.innerHTML = '';
    if(input.files && input.files[0]){
        var reader = new FileReader();
        reader.onload = function(e){
            preview.innerHTML = '<img src="'+e.target.result+'" alt="preview">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>