<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch all active tables
$stmt = mysqli_prepare($conn, "SELECT * FROM restaurant_tables WHERE active='yes' ORDER BY table_number ASC");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tables = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Check for unpaid orders
    $tid = $row['id'];
    $unpaid_check = mysqli_query($conn, "SELECT SUM(total_amount) as pending FROM orders WHERE table_id=$tid AND status NOT IN ('paid')");
    $row['pending_amount'] = mysqli_fetch_assoc($unpaid_check)['pending'] ?? 0;
    
    // Calculate occupied time
    if ($row['status'] === 'occupied' && $row['occupied_since']) {
        $elapsed = time() - strtotime($row['occupied_since']);
        $h = floor($elapsed / 3600);
        $m = floor(($elapsed % 3600) / 60);
        $row['occupied_time'] = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    } else {
        $row['occupied_time'] = 'Free';
    }
    
    $tables[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Floor Plan — POS Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
*,*::before,*::after {margin:0;padding:0;box-sizing:border-box;}
:root {
  --bg:#F0F2F5; --surface:#FFF; --surface2:#F8FAFC;
  --border:#E4E7EC; --text:#101828; --text2:#667085;
  --primary:#F97316; --primary-dark:#EA6C0A; --primary-dim:rgba(249,115,22,0.08);
  --green:#12B76A; --red:#EF4444; --amber:#F59E0B;
}
body {font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);}

.topbar {background:var(--surface);border-bottom:1px solid var(--border);height:56px;padding:0 24px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 3px rgba(0,0,0,0.06);position:sticky;top:0;z-index:50;}
.back-btn {display:flex;align-items:center;gap:6px;text-decoration:none;background:var(--surface2);border:1px solid var(--border);padding:7px 14px;border-radius:10px;font-size:13px;font-weight:700;color:var(--text);transition:0.15s;}
.back-btn:hover {background:var(--border);}
.topbar-title {font-size:20px;font-weight:800;}
.breadcrumb {display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text2);}

.page {max-width:1400px;margin:0 auto;padding:28px 24px;}
.card-title {font-size:18px;font-weight:800;margin-bottom:24px;display:flex;align-items:center;gap:8px;}
.stats-bar {display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:32px;}
.stat-card {background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:20px;text-align:center;transition:0.2s;}
.stat-card:hover {box-shadow:0 8px 25px rgba(0,0,0,0.08);transform:translateY(-4px);}
.stat-icon {font-size:32px;margin-bottom:8px;}
.stat-free .stat-icon {color:var(--green);}
.stat-occupied .stat-icon {color:var(--amber);}
.stat-label {font-size:12px;color:var(--text2);text-transform:uppercase;letter-spacing:0.5px;font-weight:700;}
.stat-value {font-size:28px;font-weight:800;margin:4px 0;}

.floor-grid {display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;}
.table-card {background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:24px;transition:0.2s;cursor:pointer;overflow:hidden;position:relative;}
.table-card:hover {box-shadow:0 12px 40px rgba(0,0,0,0.1);transform:translateY(-6px);}
.table-card.occupied {border-color:var(--amber);}
.table-card.free {border-color:var(--green);}

.table-header {display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;}
.table-number {font-size:28px;font-weight:800;color:var(--text);}
.table-status {display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:12px;font-size:13px;font-weight:700;}
.status-free {background:var(--green);color:white;}
.status-occupied {background:var(--amber);color:#7C2D12;}

.table-details {display:flex;flex-direction:column;gap:6px;color:var(--text2);font-size:14px;}
.time-badge {display:inline-flex;align-items:center;gap:4px;background:var(--primary-dim);color:var(--primary);padding:4px 10px;border-radius:8px;font-size:12px;font-weight:600;}

.table-actions {margin-top:20px;display:flex;flex-direction:column;gap:10px;}
.btn {padding:12px 20px;border:none;border-radius:14px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;cursor:pointer;transition:0.18s;text-decoration:none;display:inline-flex;align-items:center;gap:8px;justify-content:center;}
.btn-pay {background:var(--primary);color:white;width:100%;}
.btn-pay:hover {background:var(--primary-dark);}
.btn-qr {background:var(--surface2);border:1px solid var(--border);color:var(--text);width:100%;}
.btn-qr:hover {background:var(--border);}

@media (max-width:768px) {
  .floor-grid {grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;}
  .page {padding:20px 16px;}
}

.empty-state {text-align:center;padding:60px 20px;color:var(--text2);}
.empty-state i {font-size:48px;margin-bottom:16px;opacity:0.5;}
</style>
</head>
<body>

<div class="topbar">
  <a class="back-btn" href="index.php"><i class="bi bi-arrow-left"></i> Floor Plan</a>
  <div class="topbar-title">🪑 Floor Plan</div>
  <div class="breadcrumb">
    <span>POS</span><span>›</span>
    <span class="active">Tables Overview</span>
  </div>
</div>

<div class="page">

  <?php 
  $free_count = 0; $occupied_count = 0; $pending_total = 0;
  foreach ($tables as $t) {
      if ($t['status'] === 'free') $free_count++;
      else $occupied_count++;
      if ($t['pending_amount'] > 0) $pending_total += $t['pending_amount'];
  }
  ?>

  <div class="stats-bar">
    <div class="stat-card stat-free">
      <div class="stat-icon">🟢</div>
      <div class="stat-label">Free Tables</div>
      <div class="stat-value"><?php echo $free_count; ?></div>
    </div>
    <div class="stat-card stat-occupied">
      <div class="stat-icon">🔴</div>
      <div class="stat-label">Occupied</div>
      <div class="stat-value"><?php echo $occupied_count; ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">₹</div>
      <div class="stat-label">Pending Bills</div>
      <div class="stat-value"><?php echo number_format($pending_total, 0); ?></div>
    </div>
  </div>

  <h1 class="card-title"><i class="bi bi-grid-3x3-gap"></i> Active Tables (<?php echo count($tables); ?>)</h1>

  <?php if (empty($tables)): ?>
    <div class="empty-state">
      <i class="bi bi-table"></i>
      <h3>No Active Tables</h3>
      <p>Add tables from <a href="../admin/tables.php">Admin → Tables</a></p>
    </div>
  <?php else: ?>
    <div class="floor-grid">
      <?php foreach ($tables as $table): ?>
        <a href="<?php echo $table['pending_amount'] > 0 ? 'table_bill.php?table_id=' . $table['id'] : '#'; ?>" 
           class="table-card <?php echo $table['status']; ?>" 
           title="Table <?php echo $table['table_number']; ?>">
          
          <div class="table-header">
            <div class="table-number"><?php echo htmlspecialchars($table['table_number']); ?></div>
            <div class="table-status status-<?php echo $table['status']; ?>">
              <?php echo $table['status'] === 'free' ? '🟢 Free' : '🔴 Occupied'; ?>
            </div>
          </div>

          <div class="table-details">
            <div><i class="bi bi-people"></i> <?php echo $table['seats']; ?> seats</div>
            <div class="time-badge">
              <i class="bi bi-clock"></i> 
              <?php echo $table['occupied_time']; ?>
            </div>
            <?php if ($table['pending_amount'] > 0): ?>
              <div><i class="bi bi-currency-rupee"></i> ₹<?php echo number_format($table['pending_amount'], 0); ?></div>
            <?php endif; ?>
          </div>

          <div class="table-actions">
            <?php if ($table['pending_amount'] > 0): ?>
              <div class="btn btn-pay">
                <i class="bi bi-credit-card"></i> Pay ₹<?php echo number_format($table['pending_amount'], 0); ?>
              </div>
            <?php else: ?>
              <div class="btn btn-qr">
                <i class="bi bi-qr-code-scan"></i> Customer QR Ready
              </div>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

</body>
</html>

