<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$tables         = mysqli_query($conn, "SELECT * FROM restaurant_tables WHERE active='yes' ORDER BY id ASC");
$user_name      = htmlspecialchars($_SESSION['user_name']);
$free_count     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM restaurant_tables WHERE active='yes' AND status='free'"))['t'];
$occupied_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM restaurant_tables WHERE active='yes' AND status='occupied'"))['t'];
$kitchen_active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM orders WHERE status IN ('to_cook','preparing')"))['t'];
$today_sales    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) as total FROM orders WHERE status='paid' AND DATE(created_at)=CURDATE()"))['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POS Terminal — Floor View</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#0A0A0F;
  --surface:#12121A;
  --surface2:#1A1A26;
  --border:rgba(255,255,255,0.07);
  --border2:rgba(255,255,255,0.12);
  --primary:#F97316;
  --primary-dim:rgba(249,115,22,0.15);
  --primary-glow:rgba(249,115,22,0.25);
  --green:#22C55E;
  --green-dim:rgba(34,197,94,0.12);
  --red:#EF4444;
  --red-dim:rgba(239,68,68,0.12);
  --amber:#F59E0B;
  --amber-dim:rgba(245,158,11,0.12);
  --text:#F1F1F5;
  --text2:#9999B3;
  --text3:#555570;
}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column;}

/* ── TOP BAR ── */
.topbar{
  background:var(--surface);
  border-bottom:1px solid var(--border);
  padding:0 28px;
  height:60px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  position:sticky;top:0;z-index:100;
  backdrop-filter:blur(20px);
}
.topbar-left{display:flex;align-items:center;gap:20px;}
.logo{font-size:18px;font-weight:800;letter-spacing:-0.3px;}
.logo span{color:var(--primary);}
.divider-v{width:1px;height:24px;background:var(--border2);}
.session-badge{display:flex;align-items:center;gap:7px;background:var(--green-dim);border:1px solid rgba(34,197,94,0.2);color:var(--green);padding:5px 12px;border-radius:999px;font-size:12px;font-weight:700;}
.session-dot{width:7px;height:7px;background:var(--green);border-radius:50%;animation:blink 2s infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0.3;}}

.topbar-nav{display:flex;gap:4px;}
.nav-btn{text-decoration:none;padding:8px 16px;border-radius:10px;font-size:13px;font-weight:600;color:var(--text2);transition:all 0.15s;display:flex;align-items:center;gap:6px;border:none;background:none;cursor:pointer;}
.nav-btn:hover,.nav-btn.active{background:var(--surface2);color:var(--text);}
.nav-btn.active{color:var(--primary);}
.nav-btn .badge{background:var(--primary);color:white;padding:1px 7px;border-radius:999px;font-size:10px;font-weight:800;}

.topbar-right{display:flex;align-items:center;gap:8px;}
.user-chip{display:flex;align-items:center;gap:8px;padding:6px 12px;background:var(--surface2);border:1px solid var(--border);border-radius:999px;font-size:13px;font-weight:600;}
.avatar{width:26px;height:26px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:white;}
.icon-btn{text-decoration:none;width:36px;height:36px;background:var(--surface2);border:1px solid var(--border);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;transition:0.15s;cursor:pointer;}
.icon-btn:hover{background:var(--border2);}
.logout-btn{color:var(--red);background:var(--red-dim);border-color:rgba(239,68,68,0.2);}
.logout-btn:hover{background:var(--red);color:white;}

/* ── STATS BAR ── */
.stats-bar{
  background:var(--surface);
  border-bottom:1px solid var(--border);
  padding:0 28px;
  height:52px;
  display:flex;
  align-items:center;
  gap:24px;
}
.stat{display:flex;align-items:center;gap:10px;}
.stat-icon{font-size:16px;}
.stat-label{font-size:12px;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;}
.stat-val{font-size:15px;font-weight:800;}
.stat-val.green{color:var(--green);}
.stat-val.red{color:var(--red);}
.stat-val.amber{color:var(--amber);}
.stat-val.orange{color:var(--primary);}
.stats-sep{width:1px;height:24px;background:var(--border);}

/* ── MAIN ── */
.main{flex:1;padding:28px;}
.floor-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;}
.floor-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text3);}
.floor-name{display:flex;align-items:center;gap:8px;background:var(--surface2);border:1px solid var(--border);padding:6px 14px;border-radius:999px;font-size:13px;font-weight:700;}

/* ── TABLE GRID ── */
.table-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;}
.table-card{
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:20px;
  padding:20px;
  text-decoration:none;
  color:var(--text);
  display:block;
  transition:all 0.2s;
  position:relative;
  overflow:hidden;
}
.table-card::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(circle at 50% 0%,var(--primary-dim) 0%,transparent 70%);
  opacity:0;transition:opacity 0.3s;
}
.table-card:hover{border-color:rgba(249,115,22,0.4);transform:translateY(-3px);box-shadow:0 16px 40px rgba(249,115,22,0.12);}
.table-card:hover::before{opacity:1;}
.table-card.occupied{border-color:rgba(239,68,68,0.25);background:rgba(239,68,68,0.03);}
.table-card.occupied::before{background:radial-gradient(circle at 50% 0%,rgba(239,68,68,0.1) 0%,transparent 70%);}
.table-card.occupied:hover{border-color:rgba(239,68,68,0.5);box-shadow:0 16px 40px rgba(239,68,68,0.1);}

.tc-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;}
.tc-num{font-size:28px;font-weight:800;letter-spacing:-1px;}
.tc-status{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;}
.status-free{background:var(--green-dim);color:var(--green);border:1px solid rgba(34,197,94,0.2);}
.status-occupied{background:var(--red-dim);color:var(--red);border:1px solid rgba(239,68,68,0.2);}

.tc-mid{margin-bottom:16px;}
.tc-icon{font-size:36px;margin-bottom:6px;}
.tc-name{font-size:14px;font-weight:700;margin-bottom:3px;}
.tc-seats{font-size:12px;color:var(--text3);display:flex;align-items:center;gap:5px;}

.tc-btn{
  display:flex;align-items:center;justify-content:center;gap:7px;
  background:var(--surface2);border:1px solid var(--border2);
  border-radius:12px;padding:10px;
  font-size:13px;font-weight:700;
  transition:all 0.15s;
}
.table-card:hover .tc-btn{background:var(--primary);border-color:var(--primary);color:white;}
.table-card.occupied:hover .tc-btn{background:var(--red);border-color:var(--red);color:white;}

/* order count badge */
.order-badge{position:absolute;top:14px;right:50px;background:var(--amber);color:#000;width:20px;height:20px;border-radius:50%;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;}

.empty{text-align:center;padding:80px 20px;color:var(--text3);}
.empty-icon{font-size:64px;margin-bottom:16px;opacity:0.4;}
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-left">
    <div class="logo">POS <span>Cafe</span></div>
    <div class="divider-v"></div>
    <div class="session-badge"><div class="session-dot"></div>Session Active</div>
    <nav class="topbar-nav">
      <a class="nav-btn active" href="index.php">🪑 Tables</a>
      <a class="nav-btn" href="register.php">🧾 Register</a>
    </nav>
  </div>
  <div class="topbar-right">
    <a class="nav-btn" href="../kitchen/kitchen.php">👨‍🍳 Kitchen <?php if($kitchen_active>0): ?><span class="badge"><?php echo $kitchen_active; ?></span><?php endif; ?></a>
    <?php if ($_SESSION['user_role'] === 'admin'): ?>
    <a class="icon-btn" href="../admin/dashboard.php" title="Admin Backend">⚙️</a>
    <?php endif; ?>
    <div class="user-chip"><div class="avatar"><?php echo strtoupper(substr($_SESSION['user_name'],0,1)); ?></div><?php echo $user_name; ?></div>
    <a class="icon-btn logout-btn" href="../auth/logout.php" title="Logout">🔓</a>
  </div>
</div>

<div class="stats-bar">
  <div class="stat"><span class="stat-icon">🟢</span><span class="stat-label">Free</span><span class="stat-val green"><?php echo $free_count; ?></span></div>
  <div class="stats-sep"></div>
  <div class="stat"><span class="stat-icon">🔴</span><span class="stat-label">Occupied</span><span class="stat-val red"><?php echo $occupied_count; ?></span></div>
  <div class="stats-sep"></div>
  <div class="stat"><span class="stat-icon">🔥</span><span class="stat-label">In Kitchen</span><span class="stat-val amber"><?php echo $kitchen_active; ?></span></div>
  <div class="stats-sep"></div>
  <div class="stat"><span class="stat-icon">💰</span><span class="stat-label">Today's Sales</span><span class="stat-val orange">₹<?php echo number_format($today_sales,2); ?></span></div>
</div>

<div class="main">
  <div class="floor-header">
    <div class="floor-title">Floor Plan</div>
    <div class="floor-name">🏢 Ground Floor</div>
  </div>

  <?php if (mysqli_num_rows($tables) > 0): ?>
  <div class="table-grid">
    <?php while ($row = mysqli_fetch_assoc($tables)):
      $occ = $row['status'] === 'occupied';
      $num = preg_replace('/[^0-9]/','',$row['table_number']);
    ?>
    <a class="table-card <?php echo $occ?'occupied':''; ?>" href="order.php?table_id=<?php echo $row['id']; ?>">
      <div class="tc-top">
        <div class="tc-num"><?php echo str_pad($num,2,'0',STR_PAD_LEFT); ?></div>
        <div class="tc-status <?php echo $occ?'status-occupied':'status-free'; ?>"><?php echo $occ?'● Occupied':'● Free'; ?></div>
      </div>
      <div class="tc-mid">
        <div class="tc-icon"><?php echo $occ?'🍽️':'🪑'; ?></div>
        <div class="tc-name"><?php echo htmlspecialchars($row['table_number']); ?></div>
        <div class="tc-seats">👥 <?php echo $row['seats']; ?> seats</div>
      </div>
      <div class="tc-btn"><?php echo $occ?'➕ Add / View Order':'🍴 Open Table'; ?> →</div>
    </a>
    <?php endwhile; ?>
  </div>
  <?php else: ?>
  <div class="empty">
    <div class="empty-icon">🪑</div>
    <p style="font-size:16px;font-weight:700;margin-bottom:8px;">No tables set up</p>
    <a href="../admin/tables.php" style="color:var(--primary);text-decoration:none;font-size:14px;">Configure tables in Admin →</a>
  </div>
  <?php endif; ?>
</div>

</body>
</html>
