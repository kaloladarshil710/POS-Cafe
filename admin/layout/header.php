<?php
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if ($_SESSION['user_role']!=='admin') { header("Location: ../pos/index.php"); exit(); }

$current_page = basename($_SERVER['PHP_SELF']);
$user_name    = htmlspecialchars($_SESSION['user_name']);
$nav_items = [
  ['file'=>'dashboard.php', 'icon'=>'🏠', 'label'=>'Dashboard'],
  ['file'=>'products.php',  'icon'=>'🍔', 'label'=>'Products'],
  ['file'=>'categories.php','icon'=>'🏷️', 'label'=>'Categories'],
  ['file'=>'tables.php',    'icon'=>'🪑', 'label'=>'Tables'],
  ['file'=>'payments.php',  'icon'=>'💳', 'label'=>'Payments'],
  ['file'=>'users.php',     'icon'=>'👥', 'label'=>'Users'],
  ['file'=>'reports.php',   'icon'=>'📊', 'label'=>'Reports'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POS Cafe — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --primary:#F97316;--primary-dark:#EA6C0A;--primary-dim:rgba(249,115,22,0.1);
  --sidebar:#0C0C14;--sidebar-border:rgba(255,255,255,0.07);
  --bg:#F0F2F5;--surface:#FFF;--border:#E4E7EC;
  --text:#101828;--text2:#667085;--text3:#98A2B3;
  --success:#12B76A;--danger:#EF4444;--warning:#F59E0B;
}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);}
.wrapper{display:flex;min-height:100vh;}

/* sidebar */
.sidebar{width:240px;background:var(--sidebar);color:white;padding:20px 14px;position:fixed;top:0;left:0;bottom:0;display:flex;flex-direction:column;border-right:1px solid var(--sidebar-border);z-index:100;}
.sidebar-logo{font-size:20px;font-weight:800;padding:8px 10px 20px;border-bottom:1px solid var(--sidebar-border);margin-bottom:18px;letter-spacing:-0.3px;}
.sidebar-logo span{color:var(--primary);}

.profile-box{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);padding:12px 14px;border-radius:14px;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.profile-avatar{width:34px;height:34px;background:var(--primary);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;flex-shrink:0;}
.profile-name{font-size:13px;font-weight:700;}
.profile-role{font-size:11px;color:#888;margin-top:1px;}

.nav-section{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#444;padding:0 10px;margin:14px 0 6px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:11px;font-size:13px;font-weight:600;color:#888;text-decoration:none;transition:0.15s;margin-bottom:2px;}
.nav-item:hover{background:rgba(255,255,255,0.06);color:#ddd;}
.nav-item.active{background:var(--primary-dim);color:var(--primary);border:1px solid rgba(249,115,22,0.15);}
.nav-icon{font-size:16px;width:20px;text-align:center;flex-shrink:0;}

.sidebar-footer{margin-top:auto;padding-top:16px;border-top:1px solid var(--sidebar-border);}
.sidebar-footer a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:11px;font-size:13px;font-weight:600;color:#888;text-decoration:none;transition:0.15s;}
.sidebar-footer a:hover{background:rgba(239,68,68,0.1);color:#F87171;}

/* main */
.main-area{margin-left:240px;flex:1;display:flex;flex-direction:column;min-height:100vh;}
.top-header{background:var(--surface);border-bottom:1px solid var(--border);padding:0 28px;height:58px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:0 1px 3px rgba(0,0,0,0.06);}
.page-title{font-size:17px;font-weight:800;}
.header-actions{display:flex;gap:8px;}
.header-btn{text-decoration:none;padding:8px 14px;border-radius:10px;font-size:13px;font-weight:700;border:1px solid var(--border);background:var(--bg);color:var(--text);transition:0.15s;display:flex;align-items:center;gap:6px;}
.header-btn:hover{background:var(--border);}
.header-btn.primary{background:var(--primary);border-color:var(--primary);color:white;}
.header-btn.primary:hover{background:var(--primary-dark);}

.content{padding:24px 28px;flex:1;}

/* cards & panels */
.card{background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,0.04);}
.card h3{font-size:13px;color:var(--text2);font-weight:600;margin-bottom:6px;}
.card p{font-size:26px;font-weight:800;color:var(--text);}
.card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:20px;}

.panel{background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:22px;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,0.04);}
.panel h3{font-size:16px;font-weight:800;margin-bottom:16px;}
.panel h3+p{font-size:14px;color:var(--text2);margin-bottom:16px;margin-top:-10px;}

/* form elements */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.form-grid input,.form-grid select,.form-grid textarea,textarea,input[type=text],input[type=number],input[type=email],select{
  width:100%;padding:10px 14px;background:#F8FAFC;border:1px solid var(--border);border-radius:10px;
  font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;color:var(--text);transition:0.15s;
}
.form-grid input:focus,.form-grid select:focus,.form-grid textarea:focus,textarea:focus,input:focus,select:focus{
  outline:none;border-color:var(--primary);background:white;box-shadow:0 0 0 3px rgba(249,115,22,0.1);
}
textarea{min-height:90px;resize:vertical;}

.btn-primary{background:var(--primary);color:white;border:none;border-radius:11px;padding:10px 18px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;cursor:pointer;transition:0.15s;}
.btn-primary:hover{background:var(--primary-dark);}

/* table */
.table-wrap{overflow-x:auto;}
.table-wrap table{width:100%;border-collapse:collapse;}
.table-wrap th{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);padding:11px 14px;text-align:left;border-bottom:2px solid var(--border);background:#F8FAFC;white-space:nowrap;}
.table-wrap td{padding:12px 14px;border-bottom:1px solid #F2F4F7;font-size:14px;vertical-align:middle;}
.table-wrap tr:last-child td{border-bottom:none;}
.table-wrap tr:hover td{background:#FAFBFC;}

.action-btn{text-decoration:none;padding:5px 12px;border-radius:8px;font-size:12px;font-weight:700;transition:0.15s;border:none;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;}
.delete-btn{background:rgba(239,68,68,0.1);color:var(--danger);border:1px solid rgba(239,68,68,0.2);}
.delete-btn:hover{background:var(--danger);color:white;}
.edit-btn{background:rgba(249,115,22,0.1);color:var(--primary);border:1px solid rgba(249,115,22,0.2);}
.edit-btn:hover{background:var(--primary);color:white;}

.msg-success{background:rgba(18,183,106,0.1);color:#059669;border:1px solid rgba(18,183,106,0.25);border-radius:10px;padding:11px 14px;font-size:14px;font-weight:600;margin-bottom:16px;}
.msg-error{background:rgba(239,68,68,0.1);color:var(--danger);border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:11px 14px;font-size:14px;font-weight:600;margin-bottom:16px;}

/* status badges */
.badge{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;}
.badge-active{background:rgba(18,183,106,0.12);color:var(--success);}
.badge-inactive{background:rgba(239,68,68,0.1);color:var(--danger);}
</style>
</head>
<body>
<div class="wrapper">
<div class="sidebar">
  <div class="sidebar-logo">POS <span>Cafe</span></div>
  <div class="profile-box">
    <div class="profile-avatar"><?php echo strtoupper(substr($user_name,0,1)); ?></div>
    <div><div class="profile-name"><?php echo $user_name; ?></div><div class="profile-role">Administrator</div></div>
  </div>

  <div class="nav-section">Main Menu</div>
  <?php foreach ($nav_items as $n): ?>
  <a class="nav-item <?php echo $current_page===$n['file']?'active':''; ?>" href="<?php echo $n['file']; ?>">
    <span class="nav-icon"><?php echo $n['icon']; ?></span><?php echo $n['label']; ?>
  </a>
  <?php endforeach; ?>

  <div class="nav-section" style="margin-top:16px;">POS</div>
  <a class="nav-item" href="../pos/index.php"><span class="nav-icon">🚀</span>POS Terminal</a>
  <a class="nav-item" href="../kitchen/kitchen.php"><span class="nav-icon">👨‍🍳</span>Kitchen Display</a>

  <div class="sidebar-footer">
    <a href="../auth/logout.php">🔓 Logout</a>
  </div>
</div>

<div class="main-area">
<div class="top-header">
  <div class="page-title">
    <?php
    $titles=['dashboard.php'=>'Dashboard','products.php'=>'Products','categories.php'=>'Categories','tables.php'=>'Tables','payments.php'=>'Payment Methods','users.php'=>'Staff & Users','reports.php'=>'Reports'];
    echo $titles[$current_page] ?? 'Admin';
    ?>
  </div>
  <div class="header-actions">
    <a class="header-btn" href="../pos/index.php">🚀 POS Terminal</a>
    <a class="header-btn" href="../kitchen/kitchen.php">👨‍🍳 Kitchen</a>
  </div>
</div>
<div class="content">
<style>
/* ── Pagination ── */
.pagination{display:flex;align-items:center;justify-content:space-between;padding:14px 0 2px;flex-wrap:wrap;gap:10px;}
.pg-info{font-size:13px;color:var(--text2);font-weight:600;}
.pg-btns{display:flex;gap:4px;flex-wrap:wrap;}
.pg-btn{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 10px;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;color:var(--text2);background:var(--bg);border:1px solid var(--border);transition:0.15s;}
.pg-btn:hover{background:var(--border);color:var(--text);}
.pg-btn.pg-active{background:var(--primary);color:white;border-color:var(--primary);}
.pg-ellipsis{display:inline-flex;align-items:center;justify-content:center;width:28px;height:34px;color:var(--text3);font-size:13px;}

/* ── Sort links ── */
.sort-link{text-decoration:none;color:inherit;white-space:nowrap;display:inline-flex;align-items:center;gap:4px;}
.sort-link:hover{color:var(--primary);}
th.th-active{background:#EEF0FA;color:var(--primary);}
th.th-active .sort-link{color:var(--primary);}

/* ── Table header bar ── */
.table-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;}
.table-count{font-size:12px;font-weight:700;background:var(--primary-dim);color:var(--primary);padding:4px 12px;border-radius:999px;}

/* ── Extended badges ── */
.badge-role-admin{background:rgba(249,115,22,0.1);color:var(--primary);}
.badge-role-staff{background:rgba(59,130,246,0.1);color:#3B82F6;}
.action-group{display:flex;gap:6px;flex-wrap:wrap;align-items:center;}
.self-label{font-size:12px;color:var(--text3);font-style:italic;}
.empty-table{text-align:center;padding:28px;color:var(--text3);font-size:14px;}
</style>
