<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$to_cook_q   = mysqli_query($conn,"SELECT o.*,rt.table_number FROM orders o JOIN restaurant_tables rt ON o.table_id=rt.id WHERE o.status='to_cook' ORDER BY o.created_at ASC");
$preparing_q = mysqli_query($conn,"SELECT o.*,rt.table_number FROM orders o JOIN restaurant_tables rt ON o.table_id=rt.id WHERE o.status='preparing' ORDER BY o.created_at ASC");
$completed_q = mysqli_query($conn,"SELECT o.*,rt.table_number FROM orders o JOIN restaurant_tables rt ON o.table_id=rt.id WHERE o.status='completed' ORDER BY o.created_at DESC LIMIT 8");

$cnt_cook = mysqli_num_rows($to_cook_q);
$cnt_prep = mysqli_num_rows($preparing_q);
$cnt_done = mysqli_num_rows($completed_q);

function renderTicket($conn,$order){
  $oid=$order['id']; $status=$order['status'];
  $items=mysqli_query($conn,"SELECT * FROM order_items WHERE order_id=$oid ORDER BY id ASC");
  $mins=round((time()-strtotime($order['created_at']))/60);
  $urgent=($mins>=10 && $status==='to_cook');
  $col_map=['to_cook'=>'col-cook','preparing'=>'col-prep','completed'=>'col-done'];
  ?>
  <div class="ticket <?php echo $urgent?'urgent':''; ?>">
    <div class="ticket-head">
      <div>
        <div class="ticket-num">#<?php echo htmlspecialchars($order['order_number']); ?></div>
        <div class="ticket-table">🪑 <?php echo htmlspecialchars($order['table_number']); ?></div>
      </div>
      <div style="text-align:right;">
        <div class="ticket-time"><?php echo date('h:i A',strtotime($order['created_at'])); ?></div>
        <div class="ticket-age <?php echo $urgent?'age-urgent':''; ?>"><?php echo $mins; ?>m ago</div>
      </div>
    </div>

    <div class="items-list">
      <?php while($item=mysqli_fetch_assoc($items)): $done=$item['item_status']==='prepared'; ?>
      <a class="item-row <?php echo $done?'item-done':''; ?>"
         href="toggle_item_status.php?item_id=<?php echo $item['id']; ?>&return=<?php echo $oid; ?>">
        <span class="item-qty"><?php echo $item['quantity']; ?>×</span>
        <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
        <span class="item-check"><?php echo $done?'✓':'○'; ?></span>
      </a>
      <?php endwhile; ?>
    </div>

    <div class="ticket-foot">
      <span class="ticket-total">₹<?php echo number_format($order['total_amount'],2); ?></span>
      <?php if($status==='completed'): ?>
        <a class="t-btn t-btn-pay" href="../pos/payment.php?order_id=<?php echo $oid; ?>">💳 Pay</a>
        <span class="badge-ready">✅ Ready</span>
      <?php else: ?>
        <a class="t-btn t-btn-next" href="update_order_status.php?order_id=<?php echo $oid; ?>">
          <?php echo $status==='to_cook'?'🍳 Start':'✅ Done'; ?>
        </a>
      <?php endif; ?>
    </div>
  </div>
  <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="refresh" content="15">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kitchen Display — POS Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#060A14;
  --surface:rgba(255,255,255,0.04);
  --border:rgba(255,255,255,0.08);
  --text:#F1F1F5;--text2:#9999B3;--text3:#555570;
  --orange:#F97316;--amber:#F59E0B;--green:#22C55E;--red:#EF4444;
}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column;}

.topbar{background:rgba(255,255,255,0.03);border-bottom:1px solid var(--border);padding:0 24px;height:58px;display:flex;align-items:center;justify-content:space-between;backdrop-filter:blur(14px);flex-shrink:0;}
.logo{font-size:17px;font-weight:800;}.logo span{color:var(--orange);}
.topbar-right{display:flex;gap:8px;align-items:center;}
.t-btn-nav{text-decoration:none;padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;border:1px solid var(--border);background:var(--surface);color:var(--text2);transition:0.15s;}
.t-btn-nav:hover{background:rgba(255,255,255,0.08);color:var(--text);}
.pulse-dot{width:8px;height:8px;background:var(--green);border-radius:50%;animation:pulse 2s infinite;flex-shrink:0;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.3;}}
.live-badge{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--green);font-weight:700;}

.refresh-bar{background:rgba(255,255,255,0.015);border-bottom:1px solid var(--border);padding:8px 24px;font-size:12px;color:var(--text3);display:flex;align-items:center;gap:10px;flex-shrink:0;}

/* columns */
.board{display:grid;grid-template-columns:repeat(3,1fr);flex:1;gap:0;}
.col{border-right:1px solid var(--border);padding:18px 16px;overflow-y:auto;min-height:0;}
.col:last-child{border-right:none;}
.col-cook{background:rgba(249,115,22,0.03);}
.col-prep{background:rgba(245,158,11,0.03);}
.col-done{background:rgba(34,197,94,0.03);}

.col-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border);}
.col-title{font-size:14px;font-weight:800;display:flex;align-items:center;gap:8px;}
.col-count{padding:3px 10px;border-radius:999px;font-size:12px;font-weight:800;}
.cnt-cook{background:rgba(249,115,22,0.2);color:var(--orange);}
.cnt-prep{background:rgba(245,158,11,0.2);color:var(--amber);}
.cnt-done{background:rgba(34,197,94,0.2);color:var(--green);}

/* ticket */
.ticket{background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:16px;padding:16px;margin-bottom:12px;transition:0.2s;}
.ticket:hover{background:rgba(255,255,255,0.08);}
.ticket.urgent{border-color:rgba(239,68,68,0.4);background:rgba(239,68,68,0.05);}
.ticket-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;}
.ticket-num{font-size:13px;font-weight:800;color:var(--orange);}
.ticket-table{font-size:12px;color:var(--text3);margin-top:2px;}
.ticket-time{font-size:12px;color:var(--text2);font-weight:600;}
.ticket-age{font-size:11px;color:var(--text3);margin-top:2px;}
.ticket-age.age-urgent{color:var(--red);font-weight:700;}

.items-list{display:flex;flex-direction:column;gap:6px;margin-bottom:14px;}
.item-row{display:flex;align-items:center;gap:10px;padding:9px 12px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);border-radius:10px;text-decoration:none;color:var(--text);transition:0.15s;}
.item-row:hover{background:rgba(255,255,255,0.08);}
.item-row.item-done{opacity:0.45;}
.item-row.item-done .item-name{text-decoration:line-through;color:var(--text3);}
.item-qty{font-size:15px;font-weight:800;color:var(--amber);min-width:24px;}
.item-name{flex:1;font-size:13px;font-weight:600;}
.item-check{font-size:14px;color:var(--green);}
.item-row:not(.item-done) .item-check{color:var(--text3);}

.ticket-foot{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.ticket-total{font-size:14px;font-weight:800;color:var(--text2);flex:1;}
.t-btn{text-decoration:none;padding:7px 14px;border-radius:9px;font-size:12px;font-weight:700;transition:0.15s;border:none;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;}
.t-btn-next{background:rgba(249,115,22,0.15);color:var(--orange);border:1px solid rgba(249,115,22,0.25);}
.t-btn-next:hover{background:var(--orange);color:white;}
.t-btn-pay{background:rgba(34,197,94,0.12);color:var(--green);border:1px solid rgba(34,197,94,0.2);}
.t-btn-pay:hover{background:var(--green);color:white;}
.badge-ready{background:rgba(34,197,94,0.1);color:var(--green);padding:5px 10px;border-radius:8px;font-size:11px;font-weight:700;border:1px solid rgba(34,197,94,0.2);}

.empty-col{text-align:center;padding:40px 10px;color:var(--text3);}
.empty-col-icon{font-size:40px;margin-bottom:10px;opacity:0.3;}
.empty-col p{font-size:13px;}
</style>
</head>
<body>

<div class="topbar">
  <div style="display:flex;align-items:center;gap:16px;">
    <div class="logo">POS <span>Cafe</span> — Kitchen</div>
    <div class="live-badge"><div class="pulse-dot"></div>Live · Auto-refreshes</div>
  </div>
  <div class="topbar-right">
    <a class="t-btn-nav" href="../pos/index.php">🪑 POS Terminal</a>
    <div class="topbar-right">
  <a class="t-btn-nav" href="../pos/index.php">🪑 POS Terminal</a>

  <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <a class="t-btn-nav" href="../admin/dashboard.php">⚙️ Admin</a>
  <?php endif; ?>
</div>
  </div>
</div>

<div class="refresh-bar">
  🕐 <?php echo date('D d M, h:i:s A'); ?> &nbsp;·&nbsp; Page refreshes every 15 seconds
</div>

<div class="board">
  <!-- TO COOK -->
  <div class="col col-cook">
    <div class="col-head">
      <div class="col-title">🔥 To Cook</div>
      <span class="col-count cnt-cook"><?php echo $cnt_cook; ?></span>
    </div>
    <?php if ($cnt_cook===0): ?>
    <div class="empty-col"><div class="empty-col-icon">🍳</div><p>No orders waiting</p></div>
    <?php else: while($o=mysqli_fetch_assoc($to_cook_q)) renderTicket($conn,$o); endif; ?>
  </div>

  <!-- PREPARING -->
  <div class="col col-prep">
    <div class="col-head">
      <div class="col-title">👨‍🍳 Preparing</div>
      <span class="col-count cnt-prep"><?php echo $cnt_prep; ?></span>
    </div>
    <?php if ($cnt_prep===0): ?>
    <div class="empty-col"><div class="empty-col-icon">⏳</div><p>Nothing in progress</p></div>
    <?php else: while($o=mysqli_fetch_assoc($preparing_q)) renderTicket($conn,$o); endif; ?>
  </div>

  <!-- COMPLETED -->
  <div class="col col-done">
    <div class="col-head">
      <div class="col-title">✅ Ready</div>
      <span class="col-count cnt-done"><?php echo $cnt_done; ?></span>
    </div>
    <?php if ($cnt_done===0): ?>
    <div class="empty-col"><div class="empty-col-icon">✅</div><p>No completed orders</p></div>
    <?php else: while($o=mysqli_fetch_assoc($completed_q)) renderTicket($conn,$o); endif; ?>
  </div>
</div>

</body>
</html>
