<?php
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if ($_SESSION['user_role']!=='admin') { header("Location: ../pos/index.php"); exit(); }

$current_page = basename($_SERVER['PHP_SELF']);
$user_name    = htmlspecialchars($_SESSION['user_name']);

$nav_items = [
  ['file'=>'dashboard.php',  'label'=>'Dashboard',    'icon'=>'<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
  ['file'=>'products.php',   'label'=>'Products',     'icon'=>'<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>'],
  ['file'=>'categories.php', 'label'=>'Categories',   'icon'=>'<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
  ['file'=>'tables.php',     'label'=>'Tables',       'icon'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/>'],
  ['file'=>'table_qr.php','label'=>'Table QR','icon'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 7h3v3H7z"/><path d="M14 7h3v3h-3z"/><path d="M7 14h3v3H7z"/><path d="M14 14h3v3h-3z"/>'],
  ['file'=>'payments.php',   'label'=>'Payments',     'icon'=>'<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
  ['file'=>'users.php',      'label'=>'Users',        'icon'=>'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
  ['file'=>'reports.php',    'label'=>'Reports',      'icon'=>'<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
];

$page_titles = ['dashboard.php'=>'Dashboard','products.php'=>'Products','categories.php'=>'Categories','tables.php'=>'Tables','payments.php'=>'Payments','users.php'=>'Users','reports.php'=>'Reports'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POS Cafe — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --primary:#C8602A;--primary-dark:#A84E20;--primary-light:#F5E9E2;--primary-dim:rgba(200,96,42,0.1);
  --sidebar:#1C1410;--sidebar-border:rgba(255,255,255,0.07);
  --bg:#F7F4EF;--surface:#FFFFFF;--border:#E8E2D9;
  --text:#1A1410;--text2:#6B5E52;--text3:#9C8E84;
  --success:#2D7D52;--danger:#C0392B;--warning:#B8860B;
  --success-bg:rgba(45,125,82,0.1);--danger-bg:rgba(192,57,43,0.08);
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);}
.wrapper{display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{width:232px;background:var(--sidebar);padding:20px 14px;position:fixed;top:0;left:0;bottom:0;display:flex;flex-direction:column;border-right:1px solid var(--sidebar-border);z-index:100;}
.sidebar-logo{display:flex;align-items:center;gap:9px;padding:6px 8px 20px;border-bottom:1px solid var(--sidebar-border);margin-bottom:18px;}
.sidebar-logo-icon{width:32px;height:32px;background:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.sidebar-logo-icon svg{width:16px;height:16px;stroke:white;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.sidebar-logo-text{font-family:'DM Serif Display',serif;font-size:18px;color:white;letter-spacing:0.2px;}

.profile-box{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);padding:10px 12px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.profile-avatar{width:32px;height:32px;background:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:white;flex-shrink:0;font-family:'DM Serif Display',serif;}
.profile-name{font-size:13px;font-weight:600;color:rgba(255,255,255,0.85);line-height:1.2;}
.profile-role{font-size:11px;color:rgba(255,255,255,0.3);margin-top:2px;}

.nav-section{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.2);padding:0 10px;margin:14px 0 6px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 11px;border-radius:9px;font-size:13px;font-weight:500;color:rgba(255,255,255,0.4);text-decoration:none;transition:all 0.15s;margin-bottom:1px;}
.nav-item:hover{background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.75);}
.nav-item.active{background:rgba(200,96,42,0.15);color:var(--primary);border:1px solid rgba(200,96,42,0.2);}
.nav-icon{width:18px;height:18px;flex-shrink:0;stroke:currentColor;fill:none;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round;}

.sidebar-footer{margin-top:auto;padding-top:14px;border-top:1px solid var(--sidebar-border);}
.sidebar-footer a{display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:9px;font-size:13px;font-weight:500;color:rgba(255,255,255,0.35);text-decoration:none;transition:all 0.15s;}
.sidebar-footer a:hover{background:rgba(192,57,43,0.12);color:#e57373;}
.sidebar-footer svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}

/* MAIN */
.main-area{margin-left:232px;flex:1;display:flex;flex-direction:column;min-height:100vh;}
.top-header{background:var(--surface);border-bottom:1px solid var(--border);padding:0 28px;height:56px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:0 1px 3px rgba(0,0,0,0.04);}
.page-title{font-family:'DM Serif Display',serif;font-size:20px;color:var(--text);}
.header-actions{display:flex;gap:8px;}
.header-btn{text-decoration:none;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:500;border:1px solid var(--border);background:var(--bg);color:var(--text2);transition:all 0.15s;display:flex;align-items:center;gap:6px;}
.header-btn:hover{background:var(--surface);color:var(--text);border-color:#d0c8be;}
.header-btn.primary{background:var(--primary);border-color:var(--primary);color:white;}
.header-btn.primary:hover{background:var(--primary-dark);}
.header-btn svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}

.content{padding:24px 28px;flex:1;}

/* CARDS */
.card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,0.04);}
.card-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);margin-bottom:8px;}
.card-value{font-size:28px;font-weight:600;color:var(--text);font-family:'DM Serif Display',serif;}
.card-sub{font-size:13px;color:var(--text3);margin-top:4px;}
.card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:20px;}

.panel{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:22px;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,0.04);}
.panel-title{font-family:'DM Serif Display',serif;font-size:17px;color:var(--text);margin-bottom:16px;}

/* FORMS */
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.4px;color:var(--text2);margin-bottom:7px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
input[type=text],input[type=number],input[type=email],select,textarea{
  width:100%;padding:10px 13px;background:var(--bg);border:1.5px solid var(--border);border-radius:9px;
  font-family:'DM Sans',sans-serif;font-size:14px;color:var(--text);transition:border-color 0.15s,background 0.15s;
}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--primary);background:#fff;box-shadow:0 0 0 3px rgba(200,96,42,0.08);}
textarea{min-height:90px;resize:vertical;}

.btn-primary{background:var(--primary);color:white;border:none;border-radius:9px;padding:10px 18px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;cursor:pointer;transition:background 0.15s;}
.btn-primary:hover{background:var(--primary-dark);}

/* TABLE */
.table-wrap{overflow-x:auto;}
.table-wrap table{width:100%;border-collapse:collapse;}
.table-wrap th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);padding:11px 14px;text-align:left;border-bottom:2px solid var(--border);background:#FDFCFA;white-space:nowrap;}
.table-wrap td{padding:12px 14px;border-bottom:1px solid #F2EDE6;font-size:14px;vertical-align:middle;}
.table-wrap tr:last-child td{border-bottom:none;}
.table-wrap tr:hover td{background:#FDFCFA;}

.action-btn{text-decoration:none;padding:5px 12px;border-radius:7px;font-size:12px;font-weight:600;transition:all 0.15s;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;display:inline-flex;align-items:center;gap:5px;}
.delete-btn{background:var(--danger-bg);color:var(--danger);border:1px solid rgba(192,57,43,0.18);}
.delete-btn:hover{background:var(--danger);color:white;}
.edit-btn{background:var(--primary-dim);color:var(--primary);border:1px solid rgba(200,96,42,0.2);}
.edit-btn:hover{background:var(--primary);color:white;}
.action-btn svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}

.msg-success{background:var(--success-bg);color:var(--success);border:1px solid rgba(45,125,82,0.25);border-radius:9px;padding:11px 14px;font-size:14px;font-weight:500;margin-bottom:16px;}
.msg-error{background:var(--danger-bg);color:var(--danger);border:1px solid rgba(192,57,43,0.2);border-radius:9px;padding:11px 14px;font-size:14px;font-weight:500;margin-bottom:16px;}

.badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:600;letter-spacing:0.2px;}
.badge-active{background:rgba(45,125,82,0.1);color:var(--success);}
.badge-inactive{background:var(--danger-bg);color:var(--danger);}
</style>
</head>
<body>
<div class="wrapper">
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-icon">
      <svg viewBox="0 0 24 24"><path d="M17 8h1a4 4 0 0 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V8z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>
    </div>
    <span class="sidebar-logo-text">POS Cafe</span>
  </div>

  <div class="profile-box">
    <div class="profile-avatar"><?php echo strtoupper(substr($user_name,0,1)); ?></div>
    <div>
      <div class="profile-name"><?php echo $user_name; ?></div>
      <div class="profile-role">Administrator</div>
    </div>
  </div>

  <div class="nav-section">Main Menu</div>
  <?php foreach ($nav_items as $n): ?>
  <a class="nav-item <?php echo $current_page===$n['file']?'active':''; ?>" href="<?php echo $n['file']; ?>">
    <svg class="nav-icon" viewBox="0 0 24 24"><?php echo $n['icon']; ?></svg>
    <?php echo $n['label']; ?>
  </a>
  <?php endforeach; ?>
  
  

  <div class="nav-section" style="margin-top:14px;">Operations</div>
  <a class="nav-item" href="../pos/index.php">
    <svg class="nav-icon" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
    POS Terminal
  </a>
  <a class="nav-item" href="../kitchen/kitchen.php">
    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    Kitchen Display
  </a>

  <div class="sidebar-footer">
    <a href="../auth/logout.php">
      <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Sign Out
    </a>
  </div>
</div>

<div class="main-area">
<div class="top-header">
  <div class="page-title"><?php echo $page_titles[$current_page] ?? 'Admin'; ?></div>
  <div class="header-actions">
    <a class="header-btn" href="../pos/index.php">
      <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
      POS Terminal
    </a>
    <a class="header-btn" href="../kitchen/kitchen.php">
      <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      Kitchen
    </a>
  </div>
</div>
<div class="content">
