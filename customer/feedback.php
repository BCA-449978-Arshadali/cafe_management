<?php
session_start();
include "../config.php";

if(isset($_POST['send'])){
    $name    = trim($_POST['name'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $rating  = intval($_POST['rating'] ?? 5);

    if(!preg_match('/^[a-zA-Z\s]+$/', $name)){
        $error = "Name mein sirf alphabets allowed hain!";
    } elseif(empty($message)){
        $error = "Please enter your feedback message!";
    } else {
        $stmt = $conn->prepare("INSERT INTO feedback(name, message, rating) VALUES(?,?,?)");
        $stmt->bind_param("ssi", $name, $message, $rating);
        $stmt->execute();
        $msg = "Thank you <strong>$name</strong>! Your feedback has been submitted successfully. 🎉";
    }
}

// Fetch recent feedbacks
$feedbacks = [];
$fb_result = mysqli_query($conn, "SELECT * FROM feedback ORDER BY id DESC LIMIT 6");
while($r = mysqli_fetch_assoc($fb_result)) $feedbacks[] = $r;

// Stats
$total_fb   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM feedback"))['c'];
$avg_rating = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(rating) avg FROM feedback"))['avg'];
$five_star  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM feedback WHERE rating=5"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Droppers Café</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/customer.css">

<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
body { background:#0d0d0d; color:#fff; font-family:'Poppins',sans-serif; overflow-x:hidden; }
a { text-decoration:none; }
.container { max-width:1200px; margin:0 auto; padding:0 24px; }

/* ===== PAGE HERO ===== */
.page-hero {
    background: linear-gradient(135deg, #0a0a0a 0%, #0f1f0f 50%, #0a0a0a 100%);
    padding: 80px 0 50px;
    position: relative; overflow: hidden;
    border-bottom: 1px solid rgba(255,123,0,0.1);
}
.page-hero::before {
    content:''; position:absolute; inset:0;
    background: radial-gradient(ellipse at 20% 50%, rgba(255,123,0,0.07) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(255,200,0,0.04) 0%, transparent 50%);
}
.page-hero .container { position:relative; z-index:2; }
.page-hero-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(255,123,0,0.1); border:1px solid rgba(255,123,0,0.3);
    padding:6px 16px; border-radius:30px;
    font-size:13px; color:#ff7b00; font-weight:600; margin-bottom:16px;
}
.page-hero h1 { font-size:clamp(32px,4vw,52px); font-weight:800; color:#fff; margin-bottom:12px; }
.page-hero h1 span { color:#ff7b00; }
.page-hero p { color:#888; font-size:15px; max-width:500px; line-height:1.7; }

/* ===== STATS ROW ===== */
.stats-row {
    display:grid; grid-template-columns:repeat(3,1fr);
    gap:16px; margin:40px 0;
}
.stat-card {
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:16px; padding:24px; text-align:center;
    transition:0.3s;
}
.stat-card:hover { border-color:rgba(255,123,0,0.3); transform:translateY(-3px); }
.stat-card .num {
    font-size:36px; font-weight:800;
    background:linear-gradient(135deg,#ff7b00,#ffb347);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    background-clip:text; line-height:1;
}
.stat-card .lbl { font-size:13px; color:#666; margin-top:6px; }

/* ===== MAIN LAYOUT ===== */
.feedback-section { padding:20px 0 80px; }
.feedback-layout {
    display:grid; grid-template-columns:1.1fr 1fr;
    gap:32px; align-items:start;
}

/* ===== FORM CARD ===== */
.form-card {
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:20px; padding:36px;
}
.form-card-header { margin-bottom:28px; }
.form-card-header h2 { font-size:22px; font-weight:800; margin-bottom:4px; }
.form-card-header h2 span { color:#ff7b00; }
.form-card-header p { color:#666; font-size:13px; }

/* Alerts */
.alert {
    display:flex; align-items:flex-start; gap:10px;
    padding:14px 16px; border-radius:12px;
    font-size:13px; margin-bottom:20px; line-height:1.6;
}
.alert-error   { background:rgba(231,76,60,0.08);  border:1px solid rgba(231,76,60,0.3);  color:#e74c3c; }
.alert-success { background:rgba(39,174,96,0.08);  border:1px solid rgba(39,174,96,0.3);  color:#27ae60; }
.alert i { font-size:16px; margin-top:1px; flex-shrink:0; }

/* Form groups */
.fg { margin-bottom:18px; }
.fg label {
    display:block; font-size:12px; color:#888;
    font-weight:600; text-transform:uppercase;
    letter-spacing:0.5px; margin-bottom:8px;
}
.fg input, .fg textarea {
    width:100%; padding:12px 16px;
    background:#0d0d0d; border:1px solid rgba(255,255,255,0.08);
    border-radius:12px; color:#fff; font-size:14px;
    font-family:'Poppins',sans-serif; outline:none; transition:0.2s;
    resize:none;
}
.fg input:focus, .fg textarea:focus { border-color:rgba(255,123,0,0.5); background:#0f0f0f; }

/* ===== STAR RATING ===== */
.star-label {
    font-size:12px; color:#888; font-weight:600;
    text-transform:uppercase; letter-spacing:0.5px;
    display:block; margin-bottom:10px;
}
.star-rating { display:flex; gap:6px; margin-bottom:8px; flex-direction:row-reverse; justify-content:flex-end; }
.star-rating input { display:none; }
.star-rating label {
    font-size:34px; color:#333; cursor:pointer;
    transition:color 0.15s, transform 0.15s;
    text-transform:none; letter-spacing:0; font-weight:400; margin:0;
}
.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color:#f0a500;
}
.star-rating label:hover { transform:scale(1.2); }
.star-rating input:checked + label { transform:scale(1.1); }
.rating-text { font-size:13px; color:#555; margin-bottom:18px; min-height:18px; transition:0.2s; }

/* Submit */
.btn-submit {
    width:100%; padding:15px;
    background:linear-gradient(135deg, #ff7b00, #ff9500);
    color:#fff; border:none; border-radius:14px;
    font-size:15px; font-weight:700; cursor:pointer;
    font-family:'Poppins',sans-serif; margin-top:8px;
    transition:all 0.3s; box-shadow:0 8px 24px rgba(255,123,0,0.3);
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-submit:hover { transform:translateY(-2px); box-shadow:0 12px 30px rgba(255,123,0,0.45); }

/* ===== RECENT FEEDBACKS ===== */
.recent-card {
    background:#111; border:1px solid rgba(255,255,255,0.06);
    border-radius:20px; padding:32px;
    position:sticky; top:100px;
}
.recent-card h3 { font-size:18px; font-weight:700; margin-bottom:4px; }
.recent-card h3 span { color:#ff7b00; }
.recent-card > p { color:#666; font-size:13px; margin-bottom:24px; }

.fb-list { display:flex; flex-direction:column; gap:14px; }
.fb-item {
    background:#161616; border:1px solid rgba(255,255,255,0.05);
    border-radius:14px; padding:16px 18px; transition:0.3s;
}
.fb-item:hover { border-color:rgba(255,123,0,0.2); }
.fb-item-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
.fb-avatar {
    width:36px; height:36px; border-radius:50%;
    background:linear-gradient(135deg, #ff6b00, #ffb347);
    display:flex; align-items:center; justify-content:center;
    font-size:15px; font-weight:700; color:#fff; flex-shrink:0;
}
.fb-name { font-size:14px; font-weight:600; margin-left:10px; }
.fb-stars { color:#f0a500; font-size:13px; letter-spacing:1px; }
.fb-msg { font-size:13px; color:#777; line-height:1.6; }

.no-feedback {
    text-align:center; padding:40px 20px; color:#444;
}
.no-feedback i { font-size:36px; margin-bottom:10px; display:block; }

/* ===== FLOATING BUTTONS ===== */
.instagram-float {
    position:fixed; bottom:144px; right:28px; z-index:999;
    width:50px; height:50px; border-radius:50%;
    background:radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);
    color:#fff; display:flex; align-items:center; justify-content:center;
    font-size:24px; box-shadow:0 4px 20px rgba(214,36,159,0.4);
    animation:pulse-ig 2s ease-in-out infinite; transition:0.3s;
}
.instagram-float:hover { transform:scale(1.1); color:#fff; }
@keyframes pulse-ig {
    0%,100% { box-shadow:0 4px 20px rgba(214,36,159,0.4); }
    50%      { box-shadow:0 4px 40px rgba(214,36,159,0.7); }
}
.whatsapp-float {
    position:fixed; bottom:84px; right:28px; z-index:999;
    width:50px; height:50px; border-radius:50%;
    background:#25D366; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:24px; box-shadow:0 4px 20px rgba(37,211,102,0.4);
    animation:pulse-wa 2s ease-in-out infinite; transition:0.3s;
}
.whatsapp-float:hover { transform:scale(1.1); color:#fff; }
@keyframes pulse-wa {
    0%,100% { box-shadow:0 4px 20px rgba(37,211,102,0.4); }
    50%      { box-shadow:0 4px 40px rgba(37,211,102,0.7); }
}
.back-to-top {
    position:fixed; bottom:28px; right:28px; z-index:999;
    width:44px; height:44px; border-radius:12px;
    background:#ff7b00; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:18px; box-shadow:0 4px 16px rgba(255,123,0,0.4);
    opacity:0; pointer-events:none; transition:0.3s;
}
.back-to-top.show { opacity:1; pointer-events:auto; }
.back-to-top:hover { background:#e06900; transform:translateY(-3px); color:#fff; }

/* ===== RESPONSIVE ===== */
@media(max-width:900px){
    .feedback-layout { grid-template-columns:1fr; }
    .recent-card { position:static; }
    .stats-row { grid-template-columns:1fr 1fr 1fr; }
}
@media(max-width:500px){
    .stats-row { grid-template-columns:1fr; }
    .form-card { padding:22px; }
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Page Hero -->
<div class="page-hero">
    <div class="container">
        <div class="page-hero-badge">
            <i class="fa fa-star"></i> Customer Feedback
        </div>
        <h1>Share Your <span>Experience</span></h1>
        <p>Your feedback helps us improve and serve you better. We truly value every word you share with us!</p>
    </div>
</div>

<div class="feedback-section">
    <div class="container">

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="num"><?php echo $total_fb; ?>+</div>
                <div class="lbl">Total Reviews</div>
            </div>
            <div class="stat-card">
                <div class="num"><?php echo $avg_rating ? number_format($avg_rating,1) : '5.0'; ?></div>
                <div class="lbl">Average Rating</div>
            </div>
            <div class="stat-card">
                <div class="num"><?php echo $five_star; ?>+</div>
                <div class="lbl">5-Star Reviews</div>
            </div>
        </div>

        <div class="feedback-layout">

            <!-- LEFT: Form -->
            <div class="form-card">
                <div class="form-card-header">
                    <h2>Write a <span>Review</span></h2>
                    <p>Tell us what you loved (or what we can improve!)</p>
                </div>

                <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fa fa-circle-exclamation"></i>
                    <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>

                <?php if(isset($msg)): ?>
                <div class="alert alert-success">
                    <i class="fa fa-circle-check"></i>
                    <span><?php echo $msg; ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" onsubmit="return validateForm()">

                    <div class="fg">
                        <label><i class="fa fa-user"></i> &nbsp;Your Name</label>
                        <input type="text" name="name" id="name"
                            placeholder="Enter your name"
                            oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : (isset($_SESSION['customer_name']) ? htmlspecialchars($_SESSION['customer_name']) : ''); ?>"
                            required>
                    </div>

                    <div class="fg">
                        <label><i class="fa fa-comment"></i> &nbsp;Your Feedback</label>
                        <textarea name="message" rows="5"
                            placeholder="Share your experience with us..."
                            required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>

                    <!-- Interactive Star Rating -->
                    <span class="star-label"><i class="fa fa-star"></i> &nbsp;Your Rating</span>
                    <div class="star-rating" id="starRating">
                        <input type="radio" name="rating" id="s5" value="5" checked>
                        <label for="s5" title="Excellent">★</label>
                        <input type="radio" name="rating" id="s4" value="4">
                        <label for="s4" title="Good">★</label>
                        <input type="radio" name="rating" id="s3" value="3">
                        <label for="s3" title="Average">★</label>
                        <input type="radio" name="rating" id="s2" value="2">
                        <label for="s2" title="Poor">★</label>
                        <input type="radio" name="rating" id="s1" value="1">
                        <label for="s1" title="Terrible">★</label>
                    </div>
                    <div class="rating-text" id="ratingText">⭐⭐⭐⭐⭐ — Excellent!</div>

                    <button name="send" type="submit" class="btn-submit">
                        <i class="fa fa-paper-plane"></i> Submit Feedback
                    </button>

                </form>
            </div>

            <!-- RIGHT: Recent Feedbacks -->
            <div class="recent-card">
                <h3>Recent <span>Reviews</span></h3>
                <p>What our customers are saying</p>

                <div class="fb-list">
                    <?php if(!empty($feedbacks)): foreach($feedbacks as $fb):
                        $stars   = intval($fb['rating'] ?? 5);
                        $star_html = str_repeat('★', $stars) . str_repeat('☆', 5 - $stars);
                        $initial = strtoupper(substr($fb['name'] ?? 'C', 0, 1));
                    ?>
                    <div class="fb-item">
                        <div class="fb-item-top">
                            <div style="display:flex; align-items:center;">
                                <div class="fb-avatar"><?php echo $initial; ?></div>
                                <span class="fb-name"><?php echo htmlspecialchars($fb['name']); ?></span>
                            </div>
                            <div class="fb-stars"><?php echo $star_html; ?></div>
                        </div>
                        <div class="fb-msg">"<?php echo htmlspecialchars($fb['message'] ?? $fb['feedback'] ?? ''); ?>"</div>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="no-feedback">
                        <i class="fa fa-comment-dots"></i>
                        <p>Koi reviews nahi abhi. Pehle review likhne waale bano! 🎉</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Instagram Float -->
<a href="https://www.instagram.com/dropperscafe" class="instagram-float" target="_blank" title="Follow on Instagram">
    <i class="fab fa-instagram"></i>
</a>
<!-- WhatsApp Float -->
<a href="https://wa.me/917004810081" class="whatsapp-float" target="_blank" title="Chat on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>
<!-- Back to Top -->
<a href="#" class="back-to-top" id="backToTop">
    <i class="fa fa-arrow-up"></i>
</a>

<script>
// Star rating text
const ratingLabels = {
    5: '⭐⭐⭐⭐⭐ — Excellent!',
    4: '⭐⭐⭐⭐ — Good!',
    3: '⭐⭐⭐ — Average',
    2: '⭐⭐ — Poor',
    1: '⭐ — Terrible'
};
document.querySelectorAll('.star-rating input').forEach(input => {
    input.addEventListener('change', function(){
        document.getElementById('ratingText').textContent = ratingLabels[this.value];
    });
});

function validateForm(){
    const name = document.getElementById('name').value.trim();
    if(!/^[a-zA-Z\s]+$/.test(name)){
        alert('Name mein sirf alphabets allowed hain!');
        return false;
    }
    return true;
}

// Back to top
const btt = document.getElementById('backToTop');
window.addEventListener('scroll', () => {
    btt.classList.toggle('show', window.scrollY > 300);
});
</script>

</body>
</html>