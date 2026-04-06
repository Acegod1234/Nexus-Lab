<?php
require_once 'common.php';
if (!is_logged_in()) redirect('index.php');

$msg = '';
$msg_type = '';

// Privilege escalation via token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['escalation_token'])) {
    $token = trim($_POST['escalation_token']);
    $db = get_db();

    // Check the token against system_tokens table
    $stmt = $db->prepare("SELECT privilege_level FROM system_tokens WHERE token_value = :t");
    $stmt->bindValue(':t', $token, SQLITE3_TEXT);
    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);

    if ($row) {
        $level = (int)$row['privilege_level'];
        if ($level >= 3) {
            $_SESSION['role'] = 'administrator';
            $msg = 'PRIVILEGE ESCALATION SUCCESSFUL — Role elevated to ADMINISTRATOR';
            $msg_type = 'success';
        } elseif ($level >= 2) {
            $_SESSION['role'] = 'operator';
            $msg = 'Token accepted — Role elevated to OPERATOR. Higher token required for administrator.';
            $msg_type = 'warn';
        } else {
            $msg = 'Token rejected — insufficient privilege level.';
            $msg_type = 'error';
        }
    } else {
        $msg = 'Invalid escalation token.';
        $msg_type = 'error';
    }
}

$role = get_role();

// Flag is NOT served here — players must use LFI to retrieve it
$flag_content = '';

$db = get_db();
$users = [];
$res = $db->query("SELECT id, username, role, email, created_at FROM users");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NexusLab — Admin Panel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@300;400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:#020408; --panel:#0a1520; --panel2:#0f1e2e;
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

  .main { position:relative; z-index:1; padding:32px; max-width:1200px; margin:0 auto; }
  .page-title { margin-bottom:28px; }
  .page-title h1 { font-family:'Orbitron',monospace; font-size:20px; font-weight:700; letter-spacing:4px; }
  .page-title p { font-family:'Share Tech Mono',monospace; font-size:11px; color:var(--text3); margin-top:6px; letter-spacing:2px; }

  .panel { background:var(--panel); border:1px solid var(--border); border-radius:6px; overflow:hidden; margin-bottom:24px; }
  .panel-head { padding:14px 20px; border-bottom:1px solid var(--border); background:var(--panel2); font-family:'Share Tech Mono',monospace; font-size:11px; color:var(--text2); letter-spacing:2px; }
  .panel-body { padding:24px; }

  .access-denied { text-align:center; padding:60px 20px; }
  .access-denied .lock-icon { margin:0 auto 24px; width:64px; height:64px; opacity:0.4; }
  .access-denied h2 { font-family:'Orbitron',monospace; font-size:18px; letter-spacing:3px; color:var(--danger); margin-bottom:10px; }
  .access-denied p { font-family:'Share Tech Mono',monospace; font-size:12px; color:var(--text3); margin-bottom:30px; letter-spacing:1px; }

  .escalate-form { max-width:480px; margin:0 auto; }
  .field-label { font-family:'Share Tech Mono',monospace; font-size:10px; color:var(--text3); letter-spacing:2px; margin-bottom:8px; display:block; }
  .token-input {
    width:100%; background:rgba(0,10,20,0.8); border:1px solid var(--border);
    border-radius:4px; padding:11px 16px; color:var(--text);
    font-family:'Share Tech Mono',monospace; font-size:13px; outline:none;
    transition:all 0.2s; margin-bottom:14px;
  }
  .token-input:focus { border-color:var(--accent2); box-shadow:0 0 0 2px rgba(0,136,204,0.15); }
  .btn-escalate {
    width:100%; padding:11px; background:linear-gradient(135deg,rgba(255,56,96,0.3),rgba(150,0,40,0.3));
    border:1px solid rgba(255,56,96,0.5); border-radius:4px; color:var(--danger);
    font-family:'Share Tech Mono',monospace; font-size:12px; letter-spacing:2px;
    cursor:pointer; transition:all 0.15s;
  }
  .btn-escalate:hover { background:linear-gradient(135deg,rgba(255,56,96,0.4),rgba(150,0,40,0.4)); box-shadow:0 0 16px rgba(255,56,96,0.2); }

  .msg { border-radius:4px; padding:10px 14px; margin-bottom:16px; font-family:'Share Tech Mono',monospace; font-size:12px; }
  .msg.success { background:rgba(0,255,136,0.08); border:1px solid rgba(0,255,136,0.25); color:var(--accent3); }
  .msg.warn { background:rgba(255,184,0,0.08); border:1px solid rgba(255,184,0,0.25); color:var(--warn); }
  .msg.error { background:rgba(255,56,96,0.08); border:1px solid rgba(255,56,96,0.25); color:var(--danger); }

  .flag-container {
    background:#010810; border:2px solid var(--accent3); border-radius:6px;
    padding:24px; text-align:center; margin-bottom:24px;
  }
  .flag-label { font-family:'Share Tech Mono',monospace; font-size:10px; letter-spacing:3px; margin-bottom:14px; }
  .flag-value {
    font-family:'Share Tech Mono',monospace; color:#fff;
    background:rgba(0,255,136,0.08); border:1px solid rgba(0,255,136,0.2);
    padding:14px 20px; border-radius:4px; word-break:break-all; letter-spacing:1px;
  }
  .flag-congrats { font-family:'Orbitron',monospace; font-size:13px; margin-top:14px; letter-spacing:3px; }

  table { width:100%; border-collapse:collapse; }
  th { font-family:'Share Tech Mono',monospace; font-size:10px; letter-spacing:2px; color:var(--text3); padding:10px 16px; text-align:left; border-bottom:1px solid var(--border); background:rgba(0,5,10,0.3); }
  td { padding:12px 16px; border-bottom:1px solid rgba(14,58,92,0.4); font-size:13px; }
  tr:last-child td { border-bottom:none; }
  tr:hover td { background:rgba(0,60,100,0.08); }
</style>
</head>
<body>
<nav class="nav">
  <div class="nav-brand">NEXUSLAB</div>
  <div class="nav-links">
    <a href="dashboard.php" class="nav-link">DASHBOARD</a>
    <a href="products.php" class="nav-link">PRODUCTS</a>
    <a href="upload.php" class="nav-link">UPLOAD</a>
    <a href="admin.php" class="nav-link active">ADMIN PANEL</a>
  </div>
  <div class="nav-user">
    <span class="nav-username"><?= h($_SESSION['username']) ?></span>
    <span class="nav-role-badge role-<?= h($role) ?>"><?= strtoupper(h($role)) ?></span>
    <a href="logout.php" class="btn-logout">LOGOUT</a>
  </div>
</nav>

<div class="main">
  <div class="page-title">
    <h1>ADMIN PANEL</h1>
    <p>SYSTEM ADMINISTRATION — PRIVILEGED ACCESS REQUIRED</p>
  </div>

  <?php if ($msg): ?>
  <div class="msg <?= $msg_type ?>"><?= h($msg) ?></div>
  <?php endif; ?>

  <?php if (!is_operator() && !is_admin()): ?>
  <div class="panel">
    <div class="panel-head">🔒 ACCESS DENIED — PRIVILEGE ESCALATION REQUIRED</div>
    <div class="panel-body">
      <div class="access-denied">
        <div class="lock-icon">
          <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="12" y="28" width="40" height="28" rx="4" stroke="#ff3860" stroke-width="2"/>
            <path d="M20 28V20C20 13.4 25.4 8 32 8C38.6 8 44 13.4 44 20V28" stroke="#ff3860" stroke-width="2"/>
            <circle cx="32" cy="42" r="4" fill="#ff3860" opacity="0.6"/>
            <line x1="32" y1="46" x2="32" y2="50" stroke="#ff3860" stroke-width="2"/>
          </svg>
        </div>
        <h2>INSUFFICIENT CLEARANCE</h2>
        <p>Your current role (<?= strtoupper(h($role)) ?>) does not have access to this area.<br>Submit a valid system escalation token to elevate privileges.</p>
        <form method="POST" action="admin.php" class="escalate-form">
          <label class="field-label" for="token">ESCALATION TOKEN</label>
          <input class="token-input" type="text" id="token" name="escalation_token" placeholder="NX-PRIV-xxxxxxxxxxxx" autocomplete="off">
          <button type="submit" class="btn-escalate">SUBMIT TOKEN</button>
        </form>
      </div>
    </div>
  </div>

  <?php else: ?>
  <?php if (!is_admin()): ?>
  <div class="panel">
    <div class="panel-head">⬆ FURTHER PRIVILEGE ESCALATION</div>
    <div class="panel-body">
      <form method="POST" action="admin.php" style="display:flex;gap:12px;max-width:500px">
        <input class="token-input" type="text" name="escalation_token" placeholder="NX-PRIV-xxxxxxxxxxxx" style="margin-bottom:0;flex:1" autocomplete="off">
        <button type="submit" class="btn-escalate" style="width:auto;padding:11px 20px">SUBMIT</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php if (is_admin()): ?>
  <div class="flag-container" style="border-color:var(--warn);box-shadow:0 0 30px rgba(255,184,0,0.1);">
    <div class="flag-label" style="color:var(--warn);">// ADMINISTRATOR ACCESS GRANTED — CLASSIFIED ASSET LOCATED //</div>
    <div class="flag-value" style="font-size:13px;color:var(--warn);">
      Sensitive asset detected at secure storage path.<br><br>
      Path: <span style="color:#fff">/var/secrets/.flag_db9f2a</span><br><br>
      Direct read access is restricted to system processes only.<br>
      Use available platform tools to retrieve the asset contents.
    </div>
    <div class="flag-congrats" style="color:var(--warn);">ELEVATION COMPLETE — RETRIEVE THE ASSET TO FINISH</div>
  </div>
  <?php endif; ?>

  <div class="panel">
    <div class="panel-head">👥 USER MANAGEMENT</div>
    <div class="panel-body" style="padding:0">
      <table>
        <thead>
          <tr><th>ID</th><th>USERNAME</th><th>ROLE</th><th>EMAIL</th><th>CREATED</th></tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td style="font-family:'Share Tech Mono',monospace;font-size:11px;color:var(--text3)"><?= h((string)$u['id']) ?></td>
            <td><?= h($u['username']) ?></td>
            <td style="color:var(--accent)"><?= h($u['role']) ?></td>
            <td style="color:var(--text2)"><?= h($u['email']) ?></td>
            <td style="font-family:'Share Tech Mono',monospace;font-size:11px;color:var(--text3)"><?= h($u['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php endif; ?>
</div>
</body>
</html>
