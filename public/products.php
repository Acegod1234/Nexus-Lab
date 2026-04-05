<?php
require_once 'common.php';
if (!is_logged_in()) redirect('index.php');

$db = get_db();
$results = [];
$search = '';
$sqli_error = false;
$raw_error = '';

if (isset($_GET['search'])) {
    $search = $_GET['search'];

    // VULNERABLE: Direct string interpolation — UNION-based SQLi
    // Only visible columns are: id, name, category, price, description
    $query = "SELECT id, name, category, price, description FROM products WHERE visible=1 AND (name LIKE '%{$search}%' OR category LIKE '%{$search}%')";

    try {
        $res = $db->query($query);
        if ($res === false) {
            $sqli_error = true;
            $raw_error = $db->lastErrorMsg();
        } else {
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $results[] = $row;
            }
        }
    } catch (Exception $e) {
        $sqli_error = true;
        $raw_error = $e->getMessage();
    }
} else {
    // Default: show all visible products
    $res = $db->query("SELECT id, name, category, price, description FROM products WHERE visible=1");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $results[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NexusLab — Product Catalog</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@300;400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:#020408; --bg2:#060d14; --panel:#0a1520; --panel2:#0f1e2e;
    --border:#0e3a5c; --border2:#1a5a8a;
    --accent:#00d4ff; --accent2:#0088cc; --accent3:#00ff88;
    --warn:#ffb800; --danger:#ff3860;
    --text:#c8dde8; --text2:#6a9ab8; --text3:#3a6a88;
    --glow:0 0 20px rgba(0,212,255,0.3); --glow2:0 0 40px rgba(0,212,255,0.15);
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { background:var(--bg); color:var(--text); font-family:'Rajdhani',sans-serif; min-height:100vh; }
  body::before {
    content:''; position:fixed; inset:0;
    background-image:linear-gradient(rgba(0,212,255,0.025) 1px,transparent 1px),linear-gradient(90deg,rgba(0,212,255,0.025) 1px,transparent 1px);
    background-size:40px 40px; pointer-events:none; z-index:0;
  }
  .nav {
    position:sticky; top:0; z-index:100;
    background:rgba(6,13,20,0.95); border-bottom:1px solid var(--border);
    backdrop-filter:blur(10px); padding:0 32px;
    display:flex; align-items:center; justify-content:space-between; height:56px;
  }
  .nav-brand { font-family:'Orbitron',monospace; font-size:16px; font-weight:900; letter-spacing:3px; color:var(--accent); }
  .nav-links { display:flex; gap:4px; }
  .nav-link { padding:6px 14px; font-family:'Share Tech Mono',monospace; font-size:11px; letter-spacing:1px; color:var(--text3); text-decoration:none; border-radius:3px; transition:all 0.15s; border:1px solid transparent; }
  .nav-link:hover,.nav-link.active { color:var(--accent); border-color:var(--border2); background:rgba(0,136,204,0.08); }
  .nav-user { display:flex; align-items:center; gap:12px; }
  .nav-username { font-family:'Share Tech Mono',monospace; font-size:12px; color:var(--text2); }
  .nav-role-badge { font-family:'Share Tech Mono',monospace; font-size:10px; letter-spacing:1px; padding:3px 10px; border-radius:3px; }
  .role-viewer { border:1px solid rgba(58,106,136,0.5); color:var(--text3); }
  .role-analyst { border:1px solid rgba(255,184,0,0.4); color:var(--warn); }
  .role-operator { border:1px solid rgba(0,212,255,0.4); color:var(--accent); }
  .role-administrator { border:1px solid rgba(0,255,136,0.4); color:var(--accent3); }
  .btn-logout { font-family:'Share Tech Mono',monospace; font-size:10px; letter-spacing:1px; padding:5px 12px; background:transparent; border:1px solid rgba(255,56,96,0.3); color:var(--danger); border-radius:3px; cursor:pointer; transition:all 0.15s; text-decoration:none; }
  .btn-logout:hover { background:rgba(255,56,96,0.1); }

  .main { position:relative; z-index:1; padding:32px; max-width:1400px; margin:0 auto; }
  .page-title { margin-bottom:28px; }
  .page-title h1 { font-family:'Orbitron',monospace; font-size:20px; font-weight:700; letter-spacing:4px; }
  .page-title p { font-family:'Share Tech Mono',monospace; font-size:11px; color:var(--text3); margin-top:6px; letter-spacing:2px; }

  /* Search bar */
  .search-panel {
    background:var(--panel); border:1px solid var(--border); border-radius:6px;
    padding:20px 24px; margin-bottom:24px;
    display:flex; gap:12px; align-items:center;
  }
  .search-input {
    flex:1; background:rgba(0,10,20,0.8); border:1px solid var(--border);
    border-radius:4px; padding:10px 16px; color:var(--text);
    font-family:'Share Tech Mono',monospace; font-size:13px; outline:none;
    transition:all 0.2s;
  }
  .search-input:focus { border-color:var(--accent2); box-shadow:0 0 0 2px rgba(0,136,204,0.15); }
  .search-input::placeholder { color:var(--text3); }
  .btn-search {
    padding:10px 24px; background:linear-gradient(135deg,var(--accent2),#005a8a);
    border:1px solid var(--accent2); border-radius:4px; color:#fff;
    font-family:'Share Tech Mono',monospace; font-size:12px; letter-spacing:2px;
    cursor:pointer; transition:all 0.15s; white-space:nowrap;
  }
  .btn-search:hover { box-shadow:var(--glow); transform:translateY(-1px); }
  .search-hint { font-family:'Share Tech Mono',monospace; font-size:10px; color:var(--text3); margin-top:8px; }

  /* Error display — deliberately shows DB errors for SQLi discovery */
  .db-error {
    background:rgba(255,56,96,0.08); border:1px solid rgba(255,56,96,0.3);
    border-radius:6px; padding:16px 20px; margin-bottom:20px;
    font-family:'Share Tech Mono',monospace; font-size:12px; color:var(--danger);
  }
  .db-error .err-title { font-size:10px; letter-spacing:2px; margin-bottom:8px; opacity:0.7; }
  .db-error .err-body { word-break:break-all; }

  /* Results table */
  .results-panel {
    background:var(--panel); border:1px solid var(--border); border-radius:6px;
    overflow:hidden;
  }
  .results-header {
    padding:14px 24px; border-bottom:1px solid var(--border);
    background:var(--panel2);
    display:flex; align-items:center; justify-content:space-between;
  }
  .results-title { font-family:'Share Tech Mono',monospace; font-size:11px; color:var(--text2); letter-spacing:2px; }
  .results-count { font-family:'Share Tech Mono',monospace; font-size:11px; color:var(--accent); }

  table { width:100%; border-collapse:collapse; }
  th {
    font-family:'Share Tech Mono',monospace; font-size:10px; letter-spacing:2px;
    color:var(--text3); padding:12px 24px; text-align:left;
    border-bottom:1px solid var(--border); background:rgba(0,5,10,0.3);
  }
  td { padding:14px 24px; border-bottom:1px solid rgba(14,58,92,0.4); font-size:14px; vertical-align:middle; }
  tr:last-child td { border-bottom:none; }
  tr:hover td { background:rgba(0,60,100,0.1); }

  .td-id { font-family:'Share Tech Mono',monospace; font-size:11px; color:var(--text3); }
  .td-name { font-weight:600; color:var(--text); }
  .td-category {
    font-family:'Share Tech Mono',monospace; font-size:11px;
    padding:3px 8px; border-radius:3px; border:1px solid var(--border2);
    color:var(--accent); background:rgba(0,136,204,0.08);
    display:inline-block;
  }
  .td-price { font-family:'Share Tech Mono',monospace; font-size:13px; color:var(--accent3); }
  .td-desc { color:var(--text2); font-size:13px; max-width:300px; }

  .no-results { padding:40px; text-align:center; font-family:'Share Tech Mono',monospace; font-size:12px; color:var(--text3); }
</style>
</head>
<body>

<nav class="nav">
  <div class="nav-brand">NEXUSLAB</div>
  <div class="nav-links">
    <a href="dashboard.php" class="nav-link">DASHBOARD</a>
    <a href="products.php" class="nav-link active">PRODUCTS</a>
    <a href="upload.php" class="nav-link">UPLOAD</a>
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
    <h1>PRODUCT CATALOG</h1>
    <p>NEXUSLAB SECURITY SOLUTIONS — SEARCHABLE PRODUCT DATABASE</p>
  </div>

  <div class="search-panel">
    <form method="GET" action="products.php" style="flex:1;display:flex;gap:12px;align-items:flex-start;flex-direction:column">
      <div style="display:flex;gap:12px;width:100%">
        <input class="search-input" type="text" name="search"
               placeholder="search products by name or category..."
               value="<?= h($search) ?>">
        <button type="submit" class="btn-search">QUERY</button>
      </div>
      <div class="search-hint">// search against product name and category fields</div>
    </form>
  </div>

  <?php if ($sqli_error): ?>
  <div class="db-error">
    <div class="err-title">DATABASE ERROR</div>
    <div class="err-body"><?= h($raw_error) ?></div>
  </div>
  <?php endif; ?>

  <div class="results-panel">
    <div class="results-header">
      <span class="results-title">QUERY RESULTS</span>
      <span class="results-count"><?= count($results) ?> RECORD(S)</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>PRODUCT NAME</th>
          <th>CATEGORY</th>
          <th>PRICE (USD)</th>
          <th>DESCRIPTION</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($results)): ?>
        <tr><td colspan="5" class="no-results">// NO RECORDS MATCHED QUERY</td></tr>
        <?php else: foreach ($results as $row): ?>
        <tr>
          <td class="td-id"><?= h((string)$row['id']) ?></td>
          <td class="td-name"><?= h((string)$row['name']) ?></td>
          <td><span class="td-category"><?= h((string)$row['category']) ?></span></td>
          <td class="td-price">$<?= h(number_format((float)$row['price'], 2)) ?></td>
          <td class="td-desc"><?= h((string)$row['description']) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
