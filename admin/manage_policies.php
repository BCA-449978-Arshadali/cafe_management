<?php
// admin/manage_policies.php
include 'includes/auth.php';
include 'includes/db.php';

$success = '';
$error   = '';

// ✅ Save policy
if(isset($_POST['save_policy'])){
    $key     = $_POST['policy_key']     ?? '';
    $title   = trim($_POST['title']     ?? '');
    $icon    = trim($_POST['icon']      ?? '📄');
    $content = $_POST['content']        ?? '';

    if(empty($key) || empty($title) || empty($content)){
        $error = 'Title aur content dono required hain!';
    } else {
        $k = mysqli_real_escape_string($conn, $key);
        $t = mysqli_real_escape_string($conn, $title);
        $i = mysqli_real_escape_string($conn, $icon);
        $c = mysqli_real_escape_string($conn, $content);
        mysqli_query($conn,
            "INSERT INTO site_policies (policy_key, title, icon, content)
             VALUES ('$k','$t','$i','$c')
             ON DUPLICATE KEY UPDATE title='$t', icon='$i', content='$c'"
        );
        $success = "✅ " . htmlspecialchars($title) . " saved successfully!";
    }
}

// Fetch all policies
$policies_raw = mysqli_query($conn, "SELECT * FROM site_policies ORDER BY id ASC");
$policies = [];
while($p = mysqli_fetch_assoc($policies_raw)) $policies[$p['policy_key']] = $p;

// Default policy keys (agar DB mein nahi hain toh bhi show karo)
$default_keys = [
    'return_policy'  => ['title' => 'Return Policy',  'icon' => '↩️'],
    'refund_policy'  => ['title' => 'Refund Policy',  'icon' => '💰'],
    'privacy_policy' => ['title' => 'Privacy Policy', 'icon' => '🔒'],
    'disclaimer'     => ['title' => 'Disclaimer',     'icon' => '⚠️'],
];
foreach($default_keys as $key => $def){
    if(!isset($policies[$key])){
        $policies[$key] = [
            'policy_key' => $key,
            'title'      => $def['title'],
            'icon'       => $def['icon'],
            'content'    => '',
            'updated_at' => null,
        ];
    }
}

$edit_key = $_GET['edit'] ?? array_key_first($policies);
$editing  = $policies[$edit_key] ?? reset($policies);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Policies — Droppers Café Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Quill Rich Text Editor -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css">
<style>
.policy-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 20px;
    align-items: start;
}
.policy-sidebar {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    position: sticky;
    top: 80px;
}
.policy-sidebar-header {
    padding: 16px 18px;
    border-bottom: 1px solid var(--border);
    font-size: 12px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.8px;
    color: var(--text-muted);
}
.policy-tab {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    cursor: pointer; transition: 0.15s;
    text-decoration: none;
    color: var(--text);
}
.policy-tab:last-child { border-bottom: none; }
.policy-tab:hover { background: var(--bg3); }
.policy-tab.active { background: var(--orange-bg); border-left: 3px solid var(--orange); }
.policy-tab-icon { font-size: 18px; width: 24px; text-align: center; }
.policy-tab-info { flex: 1; }
.policy-tab-title { font-size: 13px; font-weight: 600; }
.policy-tab-date  { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.policy-tab-status {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--text-dim); flex-shrink: 0;
}
.policy-tab-status.has-content { background: #27ae60; }

/* Editor Card */
.editor-card {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}
.editor-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; flex-wrap: wrap;
}
.editor-card-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 16px; font-weight: 700;
}
.editor-card-body { padding: 24px; }

.form-row { margin-bottom: 18px; }
.form-label {
    display: block; font-size: 12px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.6px;
    color: var(--text-muted); margin-bottom: 7px;
}
.form-input {
    width: 100%; padding: 10px 14px;
    background: var(--bg3);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    color: var(--text); font-size: 14px;
    font-family: 'Poppins', sans-serif;
    outline: none; transition: 0.2s;
}
.form-input:focus { border-color: var(--orange); background: var(--bg4); }

.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 80px;
    gap: 12px;
}

/* Quill editor custom dark theme */
.ql-toolbar.ql-snow {
    background: var(--bg3) !important;
    border: 1.5px solid var(--border) !important;
    border-bottom: none !important;
    border-radius: var(--radius-sm) var(--radius-sm) 0 0 !important;
}
.ql-container.ql-snow {
    background: var(--bg4) !important;
    border: 1.5px solid var(--border) !important;
    border-top: none !important;
    border-radius: 0 0 var(--radius-sm) var(--radius-sm) !important;
    min-height: 320px;
}
.ql-editor { min-height: 320px; font-size: 14px; line-height: 1.7; color: var(--text) !important; }
.ql-editor h2 { color: var(--text) !important; }
.ql-editor h3 { color: var(--text) !important; }
.ql-snow .ql-stroke { stroke: var(--text-muted) !important; }
.ql-snow .ql-fill  { fill:   var(--text-muted) !important; }
.ql-snow .ql-picker { color: var(--text-muted) !important; }
.ql-snow .ql-picker-options { background: var(--bg2) !important; border-color: var(--border) !important; }
.ql-snow.ql-toolbar button:hover .ql-stroke,
.ql-snow.ql-toolbar button.ql-active .ql-stroke { stroke: var(--orange) !important; }
.ql-snow.ql-toolbar button:hover .ql-fill,
.ql-snow.ql-toolbar button.ql-active .ql-fill  { fill:   var(--orange) !important; }

.word-count {
    font-size: 11px; color: var(--text-dim); margin-top: 6px; text-align: right;
}

.btn-save {
    background: var(--orange); color: #fff;
    padding: 11px 28px; border: none; border-radius: var(--radius-sm);
    font-size: 14px; font-weight: 700; cursor: pointer;
    font-family: 'Poppins', sans-serif;
    display: inline-flex; align-items: center; gap: 8px;
    transition: 0.2s;
}
.btn-save:hover { background: #e06900; transform: translateY(-1px); }

.preview-btn {
    background: var(--bg3); color: var(--text-muted);
    padding: 10px 18px; border: 1px solid var(--border); border-radius: var(--radius-sm);
    font-size: 13px; font-weight: 600; cursor: pointer;
    font-family: 'Poppins', sans-serif;
    display: inline-flex; align-items: center; gap: 7px;
    text-decoration: none; transition: 0.2s;
}
.preview-btn:hover { border-color: var(--orange); color: var(--orange); }

.alert-success {
    background: rgba(39,174,96,0.1); border: 1px solid rgba(39,174,96,0.3);
    color: #2ecc71; padding: 12px 16px; border-radius: var(--radius-sm);
    font-size: 13px; font-weight: 600; margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px;
}
.alert-error {
    background: rgba(231,76,60,0.1); border: 1px solid rgba(231,76,60,0.3);
    color: #e74c3c; padding: 12px 16px; border-radius: var(--radius-sm);
    font-size: 13px; font-weight: 600; margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px;
}

.last-updated {
    font-size: 12px; color: var(--text-dim);
    display: flex; align-items: center; gap: 5px;
}

@media(max-width: 768px){
    .policy-layout { grid-template-columns: 1fr; }
    .policy-sidebar { position: static; display: flex; flex-wrap: wrap; }
    .policy-tab { flex: 1 1 45%; border: 1px solid var(--border); border-radius: 8px; margin: 4px; }
}
</style>
</head>
<body>
<?php include "includes/sidebar.php"; ?>

<div class="main">
    <div class="topbar">
        <h2><i class="fa fa-file-lines" style="color:var(--orange);font-size:20px;"></i> Manage Policies</h2>
        <div class="topbar-right">
            <a href="../customer/policy.php?type=<?= $edit_key ?>" target="_blank" class="preview-btn">
                <i class="fa fa-eye"></i> Preview on Site
            </a>
        </div>
    </div>

    <?php if($success): ?>
    <div class="alert-success"><i class="fa fa-circle-check"></i><?= $success ?></div>
    <?php endif; ?>
    <?php if($error): ?>
    <div class="alert-error"><i class="fa fa-circle-exclamation"></i><?= $error ?></div>
    <?php endif; ?>

    <div class="policy-layout">

        <!-- LEFT: Policy Tabs -->
        <div class="policy-sidebar">
            <div class="policy-sidebar-header">📋 All Policies</div>
            <?php foreach($policies as $key => $p): ?>
            <a href="?edit=<?= $key ?>" class="policy-tab <?= $key === $edit_key ? 'active' : '' ?>">
                <span class="policy-tab-icon"><?= $p['icon'] ?></span>
                <div class="policy-tab-info">
                    <div class="policy-tab-title"><?= htmlspecialchars($p['title']) ?></div>
                    <div class="policy-tab-date">
                        <?= $p['updated_at'] ? 'Updated: ' . date('d M Y', strtotime($p['updated_at'])) : 'Not set yet' ?>
                    </div>
                </div>
                <span class="policy-tab-status <?= !empty($p['content']) ? 'has-content' : '' ?>"></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- RIGHT: Editor -->
        <div class="editor-card">
            <div class="editor-card-header">
                <div class="editor-card-title">
                    <span style="font-size:22px;"><?= $editing['icon'] ?></span>
                    <span>Edit: <?= htmlspecialchars($editing['title']) ?></span>
                </div>
                <?php if($editing['updated_at']): ?>
                <div class="last-updated">
                    <i class="fa fa-clock fa-xs"></i>
                    Last updated: <?= date('d M Y, h:i A', strtotime($editing['updated_at'])) ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="editor-card-body">
                <form method="POST" id="policyForm">
                    <input type="hidden" name="save_policy" value="1">
                    <input type="hidden" name="policy_key" value="<?= htmlspecialchars($edit_key) ?>">
                    <input type="hidden" name="content" id="contentHidden">

                    <!-- Title + Icon row -->
                    <div class="form-row form-row-2">
                        <div>
                            <label class="form-label"><i class="fa fa-heading fa-xs"></i> Policy Title</label>
                            <input type="text" name="title" class="form-input"
                                   value="<?= htmlspecialchars($editing['title']) ?>"
                                   placeholder="e.g. Return Policy" required>
                        </div>
                        <div>
                            <label class="form-label">Icon</label>
                            <input type="text" name="icon" class="form-input"
                                   value="<?= htmlspecialchars($editing['icon']) ?>"
                                   placeholder="↩️" maxlength="4">
                        </div>
                    </div>

                    <!-- Rich Text Editor -->
                    <div class="form-row">
                        <label class="form-label"><i class="fa fa-align-left fa-xs"></i> Policy Content</label>
                        <div id="quillEditor"><?= $editing['content'] ?></div>
                        <div class="word-count" id="wordCount">0 words</div>
                    </div>

                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-top:8px;">
                        <button type="submit" class="btn-save">
                            <i class="fa fa-floppy-disk"></i> Save Policy
                        </button>
                        <a href="../customer/policy.php?type=<?= $edit_key ?>" target="_blank" class="preview-btn">
                            <i class="fa fa-arrow-up-right-from-square"></i> Preview
                        </a>
                        <span style="font-size:12px; color:var(--text-dim);">
                            <i class="fa fa-info-circle fa-xs"></i>
                            HTML formatting supported
                        </span>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
// Init Quill
var quill = new Quill('#quillEditor', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ 'header': [2, 3, false] }],
            ['bold', 'italic', 'underline'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link'],
            ['clean']
        ]
    }
});

// Word count
quill.on('text-change', function(){
    var text  = quill.getText().trim();
    var words = text.length > 0 ? text.split(/\s+/).length : 0;
    document.getElementById('wordCount').textContent = words + ' words';
});
quill.root.dispatchEvent(new Event('input'));

// Before submit — copy HTML to hidden input
document.getElementById('policyForm').addEventListener('submit', function(e){
    document.getElementById('contentHidden').value = quill.root.innerHTML;
});
</script>
</body>
</html>