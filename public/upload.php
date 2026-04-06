<?php
require_once 'common.php';
if (!is_logged_in()) redirect('index.php');
if (!is_admin()) redirect('dashboard.php');

$msg = '';
$msg_type = '';
$uploaded_file = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['asset'])) {
    $file = $_FILES['asset'];
    $original_name = basename($file['name']);
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    // Only allow .png (but we'll include the file — LFI trick)
    if ($ext !== 'png') {
        $msg = 'ERROR: Only PNG image files are accepted by the asset repository.';
        $msg_type = 'error';
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $msg = 'ERROR: File size exceeds 2MB limit.';
        $msg_type = 'error';
    } else {
        // Sanitize name but keep extension
        $safe_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
        $dest = UPLOADS_PATH . $safe_name . '.png';
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $uploaded_file = $safe_name . '.png';
            $msg = 'Asset uploaded successfully: uploads/' . $uploaded_file;
            $msg_type = 'success';
        } else {
            $msg = 'ERROR: Upload failed — storage error.';
            $msg_type = 'error';
        }
    }
}

// LFI — file preview parameter (VULNERABLE)
// Intended to "preview" uploaded diagnostic images, but includes the file as PHP
$preview = '';
$preview_output = '';
if (isset($_GET['preview'])) {
    $preview = $_GET['preview'];
    // Strip path traversal *partially* — only removes leading slashes, not ../
    // This is intentionally weak: strips only direct / at start but allows ../
    $preview_clean = ltrim($preview, '/');
    $preview_path = UPLOADS_PATH . $preview_clean;

    // Simulate "image metadata" fetch — actually PHP-includes the file
    if (file_exists($preview_path)) {
        ob_start();
        include($preview_path); // VULNERABLE: executes PHP in .png files
        $preview_output = ob_get_clean();
    } else {
        $preview_output = '// File not found: ' . $preview_clean;
    }
}

// List existing uploads
$uploads = array_filter(scandir(UPLOADS_PATH) ?: [], fn($f) => str_ends_with($f, '.png'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NexusLab — Asset Upload</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@300;400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:#020408; --bg2:#060d14; --panel:#0a1520; --panel2:#0f1e2e;
    --border:#0e3a5c; --border2:#1a5a8a;
    --accent:#00d4ff; --accent2:#0088cc; --accent3:#00ff88;
    --warn:#ffb800; --danger:#ff3860;
    --text:#c8dde8; --text2:#6a9ab8; --text3:#3a6a88;
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { background:var(--bg); color:var(--text); font-family:'Rajdhani',sans-serif; min-height:100vh; }
  body::before {
    content:''; position:fixed; inset:0;
    background-image:linear-gradient(rgba(0,212,255,0.025) 1px,transparent 1px),linear-gradient(90deg,rgba(0,212,255,0.025) 1px,transparent 1px);
    background-size:40px 40px; pointer-events:none; z-index:0;
  }
  .nav { position:sticky; top:0; z-index:100; background:rgba(6,13,20,0.95); border-bottom:1px solid var(--border); backdrop-filter:blur(10px); padding:0 32px; display:flex; align-items:center; justify-content:space-between; height:56px; }
  .nav-brand { font-family:'Orbitron',monospace; font-size:16px; font-weight:900; letter-spacing:3px; color:var(--accent); }
  .nav-links { display:flex; gap:4px; }
  .nav-link { padding:6px 14px; font-family:'Share Tech Mono',monospace; font-size:11px; letter-spacing:1px; color:var(--text3); text-decoration:none; border-radius:3px; transition:all 0.15s; border:1px solid transparent; }
  .nav-link:hover,.nav-link.active { color:var(--accent); border-color:var(--border2); background:rgba(0,136,204,0.08); }
  .nav-user { display:flex; align-items:center; gap:12px; }
  .nav-username { font-family:'Share Tech Mono',monospace; font-size:12px; color:var(--text2); }
  .nav-role-badge { font-family:'Share Tech Mono',monospace; font-size:10px; letter-spacing:1px; padding:3px 10px; border-radius:3px; }
  .role-viewer{border:1px solid rgba(58,106,136,0.5);color:var(--text3);}
  .role-analyst{border:1px solid rgba(255,184,0,0.4);color:var(--warn);}
  .role-operator{border:1px solid rgba(0,212,255,0.4);color:var(--accent);}
  .role-administrator{border:1px solid rgba(0,255,136,0.4);color:var(--accent3);}
  .btn-logout { font-family:'Share Tech Mono',monospace; font-size:10px; letter-spacing:1px; padding:5px 12px; background:transparent; border:1px solid rgba(255,56,96,0.3); color:var(--danger); border-radius:3px; cursor:pointer; transition:all 0.15s; text-decoration:none; }
  .btn-logout:hover { background:rgba(255,56,96,0.1); }

  .main { position:relative; z-index:1; padding:32px; max-width:1000px; margin:0 auto; }
  .page-title { margin-bottom:28px; }
  .page-title h1 { font-family:'Orbitron',monospace; font-size:20px; font-weight:700; letter-spacing:4px; }
  .page-title p { font-family:'Share Tech Mono',monospace; font-size:11px; color:var(--text3); margin-top:6px; letter-spacing:2px; }

  .two-col { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
  @media(max-width:768px) { .two-col { grid-template-columns:1fr; } }

  .panel {
    background:var(--panel); border:1px solid var(--border); border-radius:6px; overflow:hidden;
  }
  .panel-head {
    padding:14px 20px; border-bottom:1px solid var(--border);
    background:var(--panel2);
    font-family:'Share Tech Mono',monospace; font-size:11px; color:var(--text2); letter-spacing:2px;
    display:flex; align-items:center; gap:8px;
  }
  .panel-body { padding:24px; }

  .drop-zone {
    border:2px dashed var(--border2); border-radius:6px;
    padding:40px 20px; text-align:center; cursor:pointer;
    transition:all 0.2s; margin-bottom:20px;
  }
  .drop-zone:hover { border-color:var(--accent); background:rgba(0,136,204,0.05); }
  .drop-zone input { display:none; }
  .drop-icon { margin:0 auto 14px; width:48px; height:48px; opacity:0.5; }
  .drop-title { font-family:'Share Tech Mono',monospace; font-size:13px; color:var(--text2); margin-bottom:6px; }
  .drop-hint { font-family:'Share Tech Mono',monospace; font-size:10px; color:var(--text3); letter-spacing:1px; }

  .btn-upload {
    width:100%; padding:11px; background:linear-gradient(135deg,#007a30,#004a1c);
    border:1px solid var(--accent3); border-radius:4px; color:var(--accent3);
    font-family:'Share Tech Mono',monospace; font-size:12px; letter-spacing:2px;
    cursor:pointer; transition:all 0.15s;
  }
  .btn-upload:hover { box-shadow:0 0 16px rgba(0,255,136,0.2); }

  .msg {
    border-radius:4px; padding:10px 14px; margin-bottom:16px;
    font-family:'Share Tech Mono',monospace; font-size:12px;
  }
  .msg.success { background:rgba(0,255,136,0.08); border:1px solid rgba(0,255,136,0.25); color:var(--accent3); }
  .msg.error { background:rgba(255,56,96,0.08); border:1px solid rgba(255,56,96,0.25); color:var(--danger); }

  /* Upload list */
  .file-list { list-style:none; }
  .file-item {
    display:flex; align-items:center; justify-content:space-between;
    padding:10px 0; border-bottom:1px solid var(--border);
    font-family:'Share Tech Mono',monospace; font-size:12px;
  }
  .file-item:last-child { border-bottom:none; }
  .file-name { color:var(--text2); }
  .file-preview-link {
    color:var(--accent); text-decoration:none; font-size:11px; letter-spacing:1px;
    padding:3px 8px; border:1px solid var(--border2); border-radius:3px;
    transition:all 0.15s;
  }
  .file-preview-link:hover { background:rgba(0,136,204,0.1); }

  /* Preview box */
  .preview-section { margin-top:24px; }
  .preview-form { display:flex; gap:10px; margin-bottom:16px; }
  .preview-input {
    flex:1; background:rgba(0,10,20,0.8); border:1px solid var(--border);
    border-radius:4px; padding:9px 14px; color:var(--text);
    font-family:'Share Tech Mono',monospace; font-size:12px; outline:none;
    transition:all 0.2s;
  }
  .preview-input:focus { border-color:var(--accent2); }
  .btn-preview {
    padding:9px 18px; background:rgba(0,60,100,0.3); border:1px solid var(--border2);
    border-radius:4px; color:var(--accent); font-family:'Share Tech Mono',monospace;
    font-size:11px; letter-spacing:1px; cursor:pointer; transition:all 0.15s; white-space:nowrap;
  }
  .btn-preview:hover { background:rgba(0,80,130,0.4); }

  .preview-output {
    background:#010810; border:1px solid var(--border); border-radius:4px;
    padding:16px; font-family:'Share Tech Mono',monospace; font-size:12px;
    color:var(--accent3); white-space:pre-wrap; word-break:break-all;
    min-height:60px; line-height:1.7;
  }

  .spec-notice {
    margin-top:16px; padding:12px 16px;
    background:rgba(255,184,0,0.05); border:1px solid rgba(255,184,0,0.15);
    border-radius:4px; font-family:'Share Tech Mono',monospace; font-size:10px;
    color:rgba(255,184,0,0.5); letter-spacing:1px; line-height:1.7;
  }
</style>
</head>
<body>
<nav class="nav">
  <div class="nav-brand">NEXUSLAB</div>
  <div class="nav-links">
    <a href="dashboard.php" class="nav-link">DASHBOARD</a>
    <a href="products.php" class="nav-link">PRODUCTS</a>
    <a href="upload.php" class="nav-link active">UPLOAD</a>
    <?php if (is_operator()): ?>
    <a href="admin.php" class="nav-link">ADMIN PANEL</a>
    <?php endif; ?>
  </div>
  <div class="nav-user">
    <span class="nav-username"><?= h($_SESSION['username']) ?></span>
    <span class="nav-role-badge role-<?= h(get_role()) ?>"><?= strtoupper(h(get_role())) ?></span>
    <a href="logout.php" class="btn-logout">LOGOUT</a>
  </div>
</nav>

<div class="main">
  <div class="page-title">
    <h1>ASSET REPOSITORY</h1>
    <p>UPLOAD DIAGNOSTIC PNG IMAGES — ASSET MANAGEMENT SYSTEM</p>
  </div>

  <div class="two-col">
    <!-- Upload panel -->
    <div class="panel">
      <div class="panel-head">📁 UPLOAD ASSET</div>
      <div class="panel-body">
        <?php if ($msg): ?>
        <div class="msg <?= $msg_type ?>"><?= h($msg) ?></div>
        <?php endif; ?>

        <form method="POST" action="upload.php" enctype="multipart/form-data">
          <label class="drop-zone" for="file-input">
            <input type="file" id="file-input" name="asset" accept=".png" onchange="this.closest('form').querySelector('.drop-title').textContent = this.files[0]?.name || 'click to select file'">
            <div class="drop-icon">
              <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="6" y="6" width="36" height="36" rx="4" stroke="#00d4ff" stroke-width="1.5"/>
                <line x1="24" y1="14" x2="24" y2="34" stroke="#00d4ff" stroke-width="1.5"/>
                <polyline points="16,22 24,14 32,22" stroke="#00d4ff" stroke-width="1.5" fill="none"/>
              </svg>
            </div>
            <div class="drop-title">click to select file</div>
            <div class="drop-hint">ACCEPTED FORMAT: .PNG — MAX SIZE: 2MB</div>
          </label>
          <button type="submit" class="btn-upload">UPLOAD ASSET</button>
        </form>

        <div class="spec-notice">
          NOTE: Uploaded PNG assets are stored in the /uploads/ directory.
          The preview tool renders asset metadata using the filename parameter.
          Only .png extension files are accepted.
        </div>
      </div>
    </div>

    <!-- Files + preview panel -->
    <div class="panel">
      <div class="panel-head">🗂 STORED ASSETS</div>
      <div class="panel-body">
        <?php if (empty($uploads)): ?>
          <p style="font-family:'Share Tech Mono',monospace;font-size:12px;color:var(--text3);">// no assets uploaded yet</p>
        <?php else: ?>
          <ul class="file-list">
            <?php foreach ($uploads as $f): ?>
            <li class="file-item">
              <span class="file-name"><?= h($f) ?></span>
              <a href="upload.php?preview=<?= urlencode($f) ?>" class="file-preview-link">PREVIEW</a>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <div class="preview-section">
          <div style="font-family:'Share Tech Mono',monospace;font-size:10px;color:var(--text3);letter-spacing:2px;margin-bottom:12px;">// ASSET METADATA PREVIEW</div>
          <form method="GET" action="upload.php" class="preview-form">
            <input class="preview-input" type="text" name="preview"
                   placeholder="filename.png"
                   value="<?= h($preview) ?>">
            <button type="submit" class="btn-preview">RENDER</button>
          </form>
          <?php if ($preview !== ''): ?>
          <div class="preview-output"><?= $preview_output !== '' ? h($preview_output) : '// empty output' ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
