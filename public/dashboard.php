<?php
require_once 'common.php';
if (!is_logged_in()) redirect('index.php');
$role = get_role();
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NexusLab — Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@300;400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #020408; --bg2: #060d14; --panel: #0a1520; --panel2: #0f1e2e;
    --border: #0e3a5c; --border2: #1a5a8a;
    --accent: #00d4ff; --accent2: #0088cc; --accent3: #00ff88;
    --warn: #ffb800; --danger: #ff3860;
    --text: #c8dde8; --text2: #6a9ab8; --text3: #3a6a88;
    --glow: 0 0 20px rgba(0,212,255,0.3); --glow2: 0 0 40px rgba(0,212,255,0.15);
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { background:var(--bg); color:var(--text); font-family:'Rajdhani',sans-serif; min-height:100vh; }
  body::before {
    content:''; position:fixed; inset:0;
    background-image: linear-gradient(rgba(0,212,255,0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(0,212,255,0.025) 1px, transparent 1px);
    background-size: 40px 40px; pointer-events:none; z-index:0;
  }

  /* Nav */
  .nav {
    position: sticky; top:0; z-index:100;
    background: rgba(6,13,20,0.95);
    border-bottom: 1px solid var(--border);
    backdrop-filter: blur(10px);
    padding: 0 32px;
    display:flex; align-items:center; justify-content:space-between;
    height: 56px;
  }
  .nav-brand { font-family:'Orbitron',monospace; font-size:16px; font-weight:900; letter-spacing:3px; color:var(--accent); }
  .nav-links { display:flex; gap:4px; }
  .nav-link {
    padding: 6px 14px;
    font-family:'Share Tech Mono',monospace; font-size:11px; letter-spacing:1px;
    color: var(--text3); text-decoration:none; border-radius:3px;
    transition: all 0.15s; border: 1px solid transparent;
  }
  .nav-link:hover, .nav-link.active { color:var(--accent); border-color:var(--border2); background:rgba(0,136,204,0.08); }
  .nav-user { display:flex; align-items:center; gap:12px; }
  .nav-role-badge {
    font-family:'Share Tech Mono',monospace; font-size:10px; letter-spacing:1px;
    padding: 3px 10px; border-radius:3px;
  }
  .role-viewer { border:1px solid rgba(58,106,136,0.5); color:var(--text3); }
  .role-analyst { border:1px solid rgba(255,184,0,0.4); color:var(--warn); }
  .role-operator { border:1px solid rgba(0,212,255,0.4); color:var(--accent); }
  .role-administrator { border:1px solid rgba(0,255,136,0.4); color:var(--accent3); }
  .nav-username { font-family:'Share Tech Mono',monospace; font-size:12px; color:var(--text2); }
  .btn-logout {
    font-family:'Share Tech Mono',monospace; font-size:10px; letter-spacing:1px;
    padding: 5px 12px; background:transparent; border:1px solid rgba(255,56,96,0.3);
    color:var(--danger); border-radius:3px; cursor:pointer; transition:all 0.15s; text-decoration:none;
  }
  .btn-logout:hover { background:rgba(255,56,96,0.1); }

  /* Layout */
  .main { position:relative; z-index:1; padding:32px; max-width:1400px; margin:0 auto; }

  /* Page title */
  .page-title {
    margin-bottom:32px;
    animation: fadeIn 0.5s ease;
  }
  .page-title h1 { font-family:'Orbitron',monospace; font-size:22px; font-weight:700; letter-spacing:4px; color:var(--text); }
  .page-title p { font-family:'Share Tech Mono',monospace; font-size:11px; color:var(--text3); margin-top:6px; letter-spacing:2px; }

  /* Stats grid */
  .stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:16px; margin-bottom:32px; }
  .stat-card {
    background:var(--panel); border:1px solid var(--border); border-radius:6px;
    padding:20px; position:relative; overflow:hidden;
    animation: fadeUp 0.5s ease both;
  }
  .stat-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:2px;
    background:linear-gradient(90deg, transparent, var(--accent), transparent);
    opacity: 0.5;
  }
  .stat-card:nth-child(1) { animation-delay: 0.05s; }
  .stat-card:nth-child(2) { animation-delay: 0.1s; }
  .stat-card:nth-child(3) { animation-delay: 0.15s; }
  .stat-card:nth-child(4) { animation-delay: 0.2s; }

  .stat-label { font-family:'Share Tech Mono',monospace; font-size:10px; color:var(--text3); letter-spacing:2px; text-transform:uppercase; margin-bottom:10px; }
  .stat-value { font-family:'Orbitron',monospace; font-size:28px; font-weight:700; color:var(--accent); }
  .stat-sub { font-family:'Share Tech Mono',monospace; font-size:10px; color:var(--text3); margin-top:6px; }

  /* Feature cards */
  .feature-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px,1fr)); gap:20px; margin-bottom:32px; }
  .feature-card {
    background:var(--panel); border:1px solid var(--border); border-radius:6px;
    padding:24px; cursor:pointer; transition:all 0.2s; text-decoration:none;
    display:block; animation: fadeUp 0.5s ease both;
  }
  .feature-card:hover { border-color:var(--border2); box-shadow:var(--glow2), 0 8px 30px rgba(0,0,0,0.4); transform:translateY(-2px); }
  .feature-card:nth-child(1) { animation-delay: 0.1s; }
  .feature-card:nth-child(2) { animation-delay: 0.2s; }
  .feature-card:nth-child(3) { animation-delay: 0.3s; }

  .feature-icon { margin-bottom:16px; opacity:0.8; }
  .feature-icon svg { width:36px; height:36px; }
  .feature-title { font-family:'Orbitron',monospace; font-size:14px; font-weight:700; color:var(--text); letter-spacing:2px; margin-bottom:8px; }
  .feature-desc { font-family:'Rajdhani',sans-serif; font-size:14px; color:var(--text2); line-height:1.5; }
  .feature-badge {
    display:inline-block; margin-top:14px;
    font-family:'Share Tech Mono',monospace; font-size:10px; letter-spacing:1px;
    padding:3px 10px; border-radius:3px;
  }
  .badge-open { border:1px solid rgba(0,212,255,0.3); color:var(--accent); background:rgba(0,212,255,0.05); }
  .badge-restricted { border:1px solid rgba(255,184,0,0.3); color:var(--warn); background:rgba(255,184,0,0.05); }
  .badge-locked { border:1px solid rgba(255,56,96,0.3); color:var(--danger); background:rgba(255,56,96,0.05); }

  /* Notice */
  .notice {
    background:rgba(0,60,100,0.15); border:1px solid rgba(0,136,204,0.25); border-radius:6px;
    padding:16px 20px; margin-bottom:24px;
    font-family:'Share Tech Mono',monospace; font-size:11px; color:var(--text2); letter-spacing:1px;
    display:flex; align-items:flex-start; gap:12px;
    animation: fadeIn 0.5s ease 0.3s both;
  }

  /* Log terminal */
  .terminal-box {
    background:#010810; border:1px solid var(--border); border-radius:6px;
    padding:20px; font-family:'Share Tech Mono',monospace; font-size:12px;
    line-height:1.7; animation: fadeUp 0.5s ease 0.4s both;
  }
  .terminal-line { color:var(--text3); }
  .terminal-line .ts { color:#1a4060; }
  .terminal-line .event-ok { color:var(--accent3); }
  .terminal-line .event-warn { color:var(--warn); }
  .terminal-line .event-err { color:var(--danger); }

  @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
  @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
</style>
</head>
<body>

<nav class="nav">
  <div class="nav-brand">NEXUSLAB</div>
  <div class="nav-links">
    <a href="dashboard.php" class="nav-link active">DASHBOARD</a>
    <a href="products.php" class="nav-link">PRODUCTS</a>
    <a href="upload.php" class="nav-link">UPLOAD</a>
    <?php if (is_operator()): ?>
    <a href="admin.php" class="nav-link">ADMIN PANEL</a>
    <?php endif; ?>
  </div>
  <div class="nav-user">
    <span class="nav-username"><?= h($username) ?></span>
    <span class="nav-role-badge role-<?= h($role) ?>"><?= strtoupper(h($role)) ?></span>
    <a href="logout.php" class="btn-logout">LOGOUT</a>
  </div>
</nav>

<div class="main">
  <div class="page-title">
    <h1>MISSION CONTROL</h1>
    <p>NEXUSLAB INTELLIGENCE PLATFORM — OPERATIONAL STATUS</p>
  </div>

  <div class="notice">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" style="flex-shrink:0;margin-top:1px"><circle cx="8" cy="8" r="7.5" stroke="#0088cc"/><line x1="8" y1="6" x2="8" y2="11" stroke="#0088cc" stroke-width="1.5"/><circle cx="8" cy="4" r="0.8" fill="#0088cc"/></svg>
    NOTICE: The product catalog module has been updated. Search functionality is available to all authenticated users. Report any anomalies to the security team via the admin panel.
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">PRODUCTS INDEXED</div>
      <div class="stat-value">7</div>
      <div class="stat-sub">4 PUBLIC · 3 RESTRICTED</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">ACCESS LEVEL</div>
      <div class="stat-value" style="font-size:18px;margin-top:4px"><?= strtoupper(h($role)) ?></div>
      <div class="stat-sub">CLEARANCE TIER</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">ACTIVE SESSIONS</div>
      <div class="stat-value">1</div>
      <div class="stat-sub">THIS SESSION · <?= date('Y-m-d') ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">SYSTEM INTEGRITY</div>
      <div class="stat-value" style="color:var(--accent3)">94%</div>
      <div class="stat-sub">NOMINAL OPERATION</div>
    </div>
  </div>

  <div class="feature-grid">
    <a href="products.php" class="feature-card">
      <div class="feature-icon">
        <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="2" y="6" width="32" height="24" rx="2" stroke="#00d4ff" stroke-width="1.5"/>
          <line x1="2" y1="13" x2="34" y2="13" stroke="#00d4ff" stroke-width="1"/>
          <line x1="9" y1="20" x2="27" y2="20" stroke="#00d4ff" stroke-width="1" opacity="0.5"/>
          <line x1="9" y1="24" x2="22" y2="24" stroke="#00d4ff" stroke-width="1" opacity="0.5"/>
          <circle cx="28" cy="22" r="4" stroke="#00d4ff" stroke-width="1.2"/>
          <line x1="31" y1="25" x2="34" y2="28" stroke="#00d4ff" stroke-width="1.5"/>
        </svg>
      </div>
      <div class="feature-title">PRODUCT CATALOG</div>
      <div class="feature-desc">Browse and search the NexusLab product database. Filter by category, price, and availability.</div>
      <span class="feature-badge badge-open">ACCESSIBLE</span>
    </a>

    <a href="upload.php" class="feature-card">
      <div class="feature-icon">
        <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="4" y="4" width="28" height="28" rx="3" stroke="#ffb800" stroke-width="1.5"/>
          <line x1="18" y1="10" x2="18" y2="26" stroke="#ffb800" stroke-width="1.5"/>
          <polyline points="12,16 18,10 24,16" stroke="#ffb800" stroke-width="1.5" fill="none"/>
          <line x1="11" y1="26" x2="25" y2="26" stroke="#ffb800" stroke-width="1" opacity="0.5"/>
        </svg>
      </div>
      <div class="feature-title">FILE UPLOAD</div>
      <div class="feature-desc">Upload diagnostic images and reports to the NexusLab asset repository for analysis.</div>
      <span class="feature-badge badge-restricted">AUTHENTICATED</span>
    </a>

    <a href="<?= is_operator() ? 'admin.php' : '#' ?>" class="feature-card" <?= !is_operator() ? 'onclick="return false;" style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
      <div class="feature-icon">
        <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18 3L32 10V18C32 25.7 25.8 32.8 18 35C10.2 32.8 4 25.7 4 18V10L18 3Z" stroke="#ff3860" stroke-width="1.5"/>
          <circle cx="18" cy="16" r="4" stroke="#ff3860" stroke-width="1.2"/>
          <path d="M10 28C11.5 24.5 14.5 22 18 22C21.5 22 24.5 24.5 26 28" stroke="#ff3860" stroke-width="1.2"/>
        </svg>
      </div>
      <div class="feature-title">ADMIN PANEL</div>
      <div class="feature-desc">Access system administration, user management, and privileged diagnostic tools.</div>
      <span class="feature-badge badge-locked"><?= is_operator() ? 'GRANTED' : 'REQUIRES ELEVATION' ?></span>
    </a>
  </div>

  <div class="terminal-box">
    <div class="terminal-line"><span class="ts">[<?= date('H:i:s') ?>]</span> <span class="event-ok">AUTH_SUCCESS</span> · user=<?= h($username) ?> role=<?= h($role) ?> ip=<?= h($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') ?></div>
    <div class="terminal-line"><span class="ts">[<?= date('H:i:s', time()-12) ?>]</span> <span class="event-ok">SESSION_INIT</span> · session_id=<?= substr(session_id(), 0, 16) ?>...</div>
    <div class="terminal-line"><span class="ts">[<?= date('H:i:s', time()-300) ?>]</span> <span class="event-warn">NOTICE</span> · Product search module loaded — query logging enabled</div>
    <div class="terminal-line"><span class="ts">[<?= date('H:i:s', time()-900) ?>]</span> <span class="event-err">WARN</span> · Anomalous query pattern detected in search endpoint — logged</div>
    <div class="terminal-line"><span class="ts">[<?= date('H:i:s', time()-3600) ?>]</span> <span class="event-ok">SYSTEM</span> · NexusLab platform initialized — all modules nominal</div>
  </div>
</div>
</body>
</html>
