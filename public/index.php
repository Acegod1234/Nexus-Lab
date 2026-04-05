<?php
require_once 'common.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $db = get_db();
        $hashed = hash('sha256', $password);
        // Intentionally using string interpolation for SQLi demonstration on products page
        $stmt = $db->prepare("SELECT id, username, role FROM users WHERE username = :u AND password = :p");
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $stmt->bindValue(':p', $hashed, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            redirect('dashboard.php');
        } else {
            $error = 'Invalid credentials. Access denied.';
        }
    } else {
        $error = 'All fields required.';
    }
}

if (is_logged_in()) redirect('dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NexusLab — Secure Intelligence Platform</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@300;400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #020408;
    --bg2: #060d14;
    --panel: #0a1520;
    --panel2: #0f1e2e;
    --border: #0e3a5c;
    --border2: #1a5a8a;
    --accent: #00d4ff;
    --accent2: #0088cc;
    --accent3: #00ff88;
    --danger: #ff3860;
    --text: #c8dde8;
    --text2: #6a9ab8;
    --text3: #3a6a88;
    --glow: 0 0 20px rgba(0,212,255,0.3);
    --glow2: 0 0 40px rgba(0,212,255,0.15);
  }

  * { margin:0; padding:0; box-sizing:border-box; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Rajdhani', sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* Animated grid background */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
      linear-gradient(rgba(0,212,255,0.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(0,212,255,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    animation: gridPulse 8s ease-in-out infinite;
    pointer-events: none;
    z-index: 0;
  }

  body::after {
    content: '';
    position: fixed;
    inset: 0;
    background: radial-gradient(ellipse 80% 60% at 50% 50%, rgba(0,60,100,0.2) 0%, transparent 70%);
    pointer-events: none;
    z-index: 0;
  }

  @keyframes gridPulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 1; }
  }

  .page-wrap {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 480px;
    padding: 20px;
  }

  /* Logo */
  .logo-section {
    text-align: center;
    margin-bottom: 40px;
    animation: fadeDown 0.8s ease forwards;
  }

  .logo-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 16px;
    position: relative;
  }

  .logo-icon svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 0 12px var(--accent));
  }

  .logo-title {
    font-family: 'Orbitron', monospace;
    font-size: 28px;
    font-weight: 900;
    letter-spacing: 4px;
    background: linear-gradient(135deg, var(--accent) 0%, #ffffff 50%, var(--accent2) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .logo-sub {
    font-family: 'Share Tech Mono', monospace;
    font-size: 11px;
    color: var(--text3);
    letter-spacing: 3px;
    margin-top: 6px;
    text-transform: uppercase;
  }

  /* Status bar */
  .status-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 24px;
    padding: 8px 14px;
    background: rgba(0,60,30,0.3);
    border: 1px solid rgba(0,255,136,0.2);
    border-radius: 4px;
    font-family: 'Share Tech Mono', monospace;
    font-size: 11px;
    color: var(--accent3);
    animation: fadeDown 0.8s ease 0.1s both;
  }

  .status-dot {
    width: 6px;
    height: 6px;
    background: var(--accent3);
    border-radius: 50%;
    animation: blink 1.5s ease-in-out infinite;
    flex-shrink: 0;
  }

  @keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.2; }
  }

  /* Login panel */
  .login-panel {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 0 60px rgba(0,0,0,0.8), var(--glow2);
    animation: fadeUp 0.8s ease 0.2s both;
  }

  .panel-header {
    padding: 20px 28px 16px;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(180deg, var(--panel2) 0%, var(--panel) 100%);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .panel-header-dots {
    display: flex;
    gap: 6px;
  }

  .panel-header-dots span {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: block;
  }

  .panel-header-dots span:nth-child(1) { background: #ff5f57; }
  .panel-header-dots span:nth-child(2) { background: #febc2e; }
  .panel-header-dots span:nth-child(3) { background: #28c840; }

  .panel-header-title {
    font-family: 'Share Tech Mono', monospace;
    font-size: 12px;
    color: var(--text3);
    letter-spacing: 1px;
    margin-left: 8px;
  }

  .panel-body {
    padding: 32px 28px;
  }

  .section-label {
    font-family: 'Share Tech Mono', monospace;
    font-size: 10px;
    letter-spacing: 3px;
    color: var(--accent);
    text-transform: uppercase;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, var(--border2), transparent);
  }

  .form-group {
    margin-bottom: 20px;
  }

  .form-label {
    display: block;
    font-family: 'Share Tech Mono', monospace;
    font-size: 10px;
    color: var(--text3);
    letter-spacing: 2px;
    margin-bottom: 8px;
    text-transform: uppercase;
  }

  .form-input {
    width: 100%;
    background: rgba(0,10,20,0.8);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 12px 16px;
    color: var(--text);
    font-family: 'Share Tech Mono', monospace;
    font-size: 14px;
    outline: none;
    transition: all 0.2s ease;
  }

  .form-input:focus {
    border-color: var(--accent2);
    box-shadow: 0 0 0 2px rgba(0,136,204,0.15), inset 0 0 10px rgba(0,60,100,0.2);
  }

  .form-input::placeholder {
    color: var(--text3);
    opacity: 0.6;
  }

  .error-msg {
    background: rgba(255,56,96,0.1);
    border: 1px solid rgba(255,56,96,0.3);
    border-radius: 4px;
    padding: 10px 14px;
    font-family: 'Share Tech Mono', monospace;
    font-size: 12px;
    color: var(--danger);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .btn-login {
    width: 100%;
    padding: 13px;
    background: linear-gradient(135deg, var(--accent2) 0%, #005a8a 100%);
    border: 1px solid var(--accent2);
    border-radius: 4px;
    color: #fff;
    font-family: 'Orbitron', monospace;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 3px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-transform: uppercase;
    position: relative;
    overflow: hidden;
  }

  .btn-login::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 100%);
    opacity: 0;
    transition: opacity 0.2s;
  }

  .btn-login:hover {
    box-shadow: var(--glow), 0 4px 20px rgba(0,0,0,0.5);
    transform: translateY(-1px);
  }

  .btn-login:hover::before { opacity: 1; }
  .btn-login:active { transform: translateY(0); }

  .panel-footer {
    padding: 16px 28px;
    border-top: 1px solid var(--border);
    background: rgba(0,5,10,0.4);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .footer-hint {
    font-family: 'Share Tech Mono', monospace;
    font-size: 10px;
    color: var(--text3);
    letter-spacing: 1px;
  }

  .clearance-badge {
    font-family: 'Share Tech Mono', monospace;
    font-size: 10px;
    color: var(--accent3);
    border: 1px solid rgba(0,255,136,0.3);
    padding: 3px 8px;
    border-radius: 3px;
    letter-spacing: 1px;
  }

  /* Corner decorations */
  .corner-deco {
    position: fixed;
    z-index: 1;
    opacity: 0.4;
  }

  .corner-deco.tl { top: 20px; left: 20px; }
  .corner-deco.tr { top: 20px; right: 20px; }
  .corner-deco.bl { bottom: 20px; left: 20px; }
  .corner-deco.br { bottom: 20px; right: 20px; }

  .corner-deco svg { width: 40px; height: 40px; }

  /* Scan line */
  .scanline {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--accent), transparent);
    opacity: 0.3;
    animation: scan 4s linear infinite;
    z-index: 2;
    pointer-events: none;
  }

  @keyframes scan {
    0% { top: 0; }
    100% { top: 100vh; }
  }

  @keyframes fadeDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .warning-strip {
    margin-top: 20px;
    padding: 10px 14px;
    border: 1px solid rgba(255,184,0,0.2);
    background: rgba(255,184,0,0.04);
    border-radius: 4px;
    font-family: 'Share Tech Mono', monospace;
    font-size: 10px;
    color: rgba(255,184,0,0.6);
    letter-spacing: 1px;
    text-align: center;
    animation: fadeUp 0.8s ease 0.4s both;
  }
</style>
</head>
<body>

<div class="scanline"></div>

<!-- Corner decorations -->
<div class="corner-deco tl">
  <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M2 38 L2 2 L38 2" stroke="#00d4ff" stroke-width="2"/>
    <path d="M2 2 L12 2 M2 2 L2 12" stroke="#00d4ff" stroke-width="1" opacity="0.5"/>
  </svg>
</div>
<div class="corner-deco tr">
  <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M38 38 L38 2 L2 2" stroke="#00d4ff" stroke-width="2"/>
  </svg>
</div>
<div class="corner-deco bl">
  <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M2 2 L2 38 L38 38" stroke="#00d4ff" stroke-width="2"/>
  </svg>
</div>
<div class="corner-deco br">
  <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M38 2 L38 38 L2 38" stroke="#00d4ff" stroke-width="2"/>
  </svg>
</div>

<div class="page-wrap">
  <div class="logo-section">
    <div class="logo-icon">
      <svg viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
        <polygon points="36,4 68,20 68,52 36,68 4,52 4,20" stroke="#00d4ff" stroke-width="1.5" fill="rgba(0,60,100,0.2)"/>
        <polygon points="36,14 58,26 58,50 36,62 14,50 14,26" stroke="#00d4ff" stroke-width="0.8" fill="rgba(0,80,130,0.15)" opacity="0.6"/>
        <circle cx="36" cy="36" r="10" stroke="#00d4ff" stroke-width="1.5" fill="rgba(0,100,160,0.3)"/>
        <circle cx="36" cy="36" r="4" fill="#00d4ff" opacity="0.8"/>
        <line x1="36" y1="4" x2="36" y2="26" stroke="#00d4ff" stroke-width="0.8" opacity="0.4"/>
        <line x1="36" y1="46" x2="36" y2="68" stroke="#00d4ff" stroke-width="0.8" opacity="0.4"/>
        <line x1="4" y1="20" x2="26" y2="30" stroke="#00d4ff" stroke-width="0.8" opacity="0.4"/>
        <line x1="46" y1="42" x2="68" y2="52" stroke="#00d4ff" stroke-width="0.8" opacity="0.4"/>
      </svg>
    </div>
    <div class="logo-title">NEXUSLAB</div>
    <div class="logo-sub">Secure Intelligence Platform v4.2.1</div>
  </div>

  <div class="status-bar">
    <span class="status-dot"></span>
    SYSTEM ONLINE — AUTHENTICATION REQUIRED — AUTHORIZED PERSONNEL ONLY
  </div>

  <div class="login-panel">
    <div class="panel-header">
      <div class="panel-header-dots">
        <span></span><span></span><span></span>
      </div>
      <div class="panel-header-title">AUTH_TERMINAL — SESSION_INIT</div>
    </div>

    <div class="panel-body">
      <div class="section-label">IDENTITY VERIFICATION</div>

      <?php if ($error): ?>
      <div class="error-msg">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6.5" stroke="#ff3860"/><line x1="7" y1="4" x2="7" y2="8" stroke="#ff3860" stroke-width="1.5"/><circle cx="7" cy="10" r="0.75" fill="#ff3860"/></svg>
        <?= h($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="index.php" autocomplete="off">
        <div class="form-group">
          <label class="form-label" for="username">OPERATOR ID</label>
          <input class="form-input" type="text" id="username" name="username"
                 placeholder="enter operator id" autocomplete="off"
                 value="<?= h($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="password">ACCESS KEY</label>
          <input class="form-input" type="password" id="password" name="password"
                 placeholder="••••••••••••" autocomplete="off">
        </div>

        <button type="submit" class="btn-login">AUTHENTICATE</button>
      </form>
    </div>

    <div class="panel-footer">
      <span class="footer-hint">NX-PLATFORM © 2024</span>
      <span class="clearance-badge">CLEARANCE: PUBLIC</span>
    </div>
  </div>

  <div class="warning-strip">
    ⚠ UNAUTHORIZED ACCESS IS MONITORED AND PROSECUTED — ALL SESSIONS ARE LOGGED
  </div>
</div>

</body>
</html>
