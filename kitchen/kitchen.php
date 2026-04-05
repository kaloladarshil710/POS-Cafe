<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

// ─── Fetch & group orders by (table_id + status) ───────────────────────────
function fetchGrouped($conn, $status, $limit = null) {
 $limitClause = $limit ? "LIMIT $limit" : "";
 $order = ($status === 'completed') ? 'DESC' : 'ASC';
 $q = mysqli_query($conn,
 "SELECT o.*, rt.table_number
 FROM orders o
 JOIN restaurant_tables rt ON o.table_id = rt.id
 WHERE o.status = '$status'
 ORDER BY o.created_at $order $limitClause"
 );

 $groups = []; // keyed by table_id
 while ($row = mysqli_fetch_assoc($q)) {
 $tid = $row['table_id'];
 if (!isset($groups[$tid])) {
 $groups[$tid] = [
 'table_id' => $tid,
 'table_number' => $row['table_number'],
 'status' => $status,
 'earliest_at' => $row['created_at'],
 'total_amount' => 0,
 'order_ids' => [],
 'order_numbers'=> [],
 'items' => [],
 ];
 }
 $groups[$tid]['total_amount'] += $row['total_amount'];
 $groups[$tid]['order_ids'][] = $row['id'];
 $groups[$tid]['order_numbers'][]= $row['order_number'];
 // keep earliest timestamp for age calc
 if (strtotime($row['created_at']) < strtotime($groups[$tid]['earliest_at'])) {
 $groups[$tid]['earliest_at'] = $row['created_at'];
 }
 // pull items for this individual order
 $items_q = mysqli_query($conn,
 "SELECT * FROM order_items WHERE order_id = {$row['id']} ORDER BY id ASC"
 );
 while ($item = mysqli_fetch_assoc($items_q)) {
 $groups[$tid]['items'][] = $item;
 }
 }
 return array_values($groups);
}

$to_cook_groups = fetchGrouped($conn, 'to_cook');
$preparing_groups = fetchGrouped($conn, 'preparing');
$completed_groups = fetchGrouped($conn, 'completed', 8);

$cnt_cook = count($to_cook_groups);
$cnt_prep = count($preparing_groups);
$cnt_done = count($completed_groups);

// ─── Render a merged ticket ─────────────────────────────────────────────────
function renderMergedTicket($group) {
 $status = $group['status'];
 $mins = round((time() - strtotime($group['earliest_at'])) / 60);
 $urgent = ($mins >= 10 && $status === 'to_cook');
 $firstOid = $group['order_ids'][0];
 $tableKey = strtolower($group['table_number']);
 $orderNums = implode(', #', $group['order_numbers']);
 $allIds = implode(',', $group['order_ids']); // for bulk status update
 ?>
<div class="ticket <?php echo $urgent ? 'urgent' : ''; ?>" data-table="<?php echo htmlspecialchars($tableKey); ?>">

 <div class="ticket-head">
 <div>
 <div class="ticket-num">#<?php echo htmlspecialchars($orderNums); ?></div>
 <div class="ticket-table"><?php echo htmlspecialchars($group['table_number']); ?></div>
 </div>
 <div style="text-align:right;">
 <div class="ticket-time"><?php echo date('h:i A', strtotime($group['earliest_at'])); ?></div>
 <div class="ticket-age <?php echo $urgent ? 'age-urgent' : ''; ?>"><?php echo $mins; ?>m ago</div>
 </div>
 </div>

 <div class="items-list">
 <?php foreach ($group['items'] as $item):
 $done = ($item['item_status'] === 'prepared'); ?>
 <a class="item-row <?php echo $done ? 'item-done' : ''; ?>"
 href="toggle_item_status.php?item_id=<?php echo $item['id']; ?>&return=<?php echo $firstOid; ?>">
 <span class="item-qty"><?php echo $item['quantity']; ?>×</span>
 <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
 <span class="item-check"><?php echo $done ? '&check;' : '&circ;'; ?></span>
 </a>
 <?php endforeach; ?>
 </div>

 <div class="ticket-foot">
 <span class="ticket-total">₹<?php echo number_format($group['total_amount'], 2); ?></span>

 <?php if ($status === 'completed'): ?>
 <!-- Pass all order IDs so payment page can handle all of them -->
 <a class="t-btn t-btn-pay"
 href="../pos/payment.php?order_ids=<?php echo urlencode($allIds); ?>">Pay</a>
 <span class="badge-ready"> Ready</span>
 <?php else: ?>
 <!-- update_order_status.php must accept order_ids (comma list) -->
 <a class="t-btn t-btn-next"
 href="update_order_status.php?order_ids=<?php echo urlencode($allIds); ?>">
 <?php echo ($status === 'to_cook') ? 'Start' : 'Done'; ?>
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
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
 --bg:#060A14;
 --surface:rgba(255,255,255,0.04);
 --border:rgba(255,255,255,0.08);
 --text:#F1F1F5;--text2:#9999B3;--text3:#555570;
 --orange:#C8602A;--amber:#F59E0B;--green:#22C55E;--red:#EF4444;
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column;}

.topbar{background:rgba(255,255,255,0.03);border-bottom:1px solid var(--border);padding:0 24px;height:58px;display:flex;align-items:center;justify-content:space-between;backdrop-filter:blur(14px);flex-shrink:0;}
.logo{font-size:17px;font-weight:800;}.logo span{color:var(--orange);}
.topbar-right{display:flex;gap:8px;align-items:center;}
.t-btn-nav{text-decoration:none;padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;border:1px solid var(--border);background:var(--surface);color:var(--text2);transition:0.15s;}
.t-btn-nav:hover{background:rgba(255,255,255,0.08);color:var(--text);}
.pulse-dot{width:8px;height:8px;background:var(--green);border-radius:50%;animation:pulse 2s infinite;flex-shrink:0;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.3;}}
.live-badge{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--green);font-weight:700;}

.search-wrap{padding:12px 24px;border-bottom:1px solid var(--border);background:rgba(255,255,255,0.02);}
.search-wrap input{width:100%;max-width:360px;padding:12px 16px;border-radius:12px;border:1px solid var(--border);background:rgba(255,255,255,0.05);color:var(--text);font-size:14px;outline:none;}
.search-wrap input::placeholder{color:var(--text3);}

.board{display:grid;grid-template-columns:repeat(3,1fr);flex:1;gap:0;}
.col{border-right:1px solid var(--border);padding:18px 16px;overflow-y:auto;min-height:0;}
.col:last-child{border-right:none;}
.col-cook{background:rgba(200,96,42,0.03);}
.col-prep{background:rgba(245,158,11,0.03);}
.col-done{background:rgba(34,197,94,0.03);}

.col-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border);}
.col-title{font-size:14px;font-weight:800;display:flex;align-items:center;gap:8px;}
.col-count{padding:3px 10px;border-radius:999px;font-size:12px;font-weight:800;}
.cnt-cook{background:rgba(200,96,42,0.2);color:var(--orange);}
.cnt-prep{background:rgba(245,158,11,0.2);color:var(--amber);}
.cnt-done{background:rgba(34,197,94,0.2);color:var(--green);}

.ticket{background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:16px;padding:16px;margin-bottom:12px;transition:0.2s;}
.ticket:hover{background:rgba(255,255,255,0.08);}
.ticket.urgent{border-color:rgba(239,68,68,0.4);background:rgba(239,68,68,0.05);}
.ticket-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;}
.ticket-num{font-size:12px;font-weight:800;color:var(--orange);word-break:break-all;}
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
.t-btn{text-decoration:none;padding:7px 14px;border-radius:9px;font-size:12px;font-weight:700;transition:0.15s;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;}
.t-btn-next{background:rgba(200,96,42,0.15);color:var(--orange);border:1px solid rgba(200,96,42,0.25);}
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
 <a class="t-btn-nav" href="../pos/index.php"> POS Terminal</a>
 <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
 <a class="t-btn-nav" href="../admin/dashboard.php"> Admin</a>
 <?php endif; ?>
 </div>
</div>

<div class="search-wrap">
 <input type="text" id="tableSearch" placeholder="Search by Table No (e.g. Table 3)" onkeyup="filterOrders()">
</div>

<div class="board">
 <!-- TO COOK -->
 <div class="col col-cook">
 <div class="col-head">
 <div class="col-title">To Cook</div>
 <span class="col-count cnt-cook"><?php echo $cnt_cook; ?></span>
 </div>
 <?php if ($cnt_cook === 0): ?>
 <div class="empty-col"><div class="empty-col-icon"></div><p>No orders waiting</p></div>
 <?php else: foreach ($to_cook_groups as $g) renderMergedTicket($g); endif; ?>
 </div>

 <!-- PREPARING -->
 <div class="col col-prep">
 <div class="col-head">
 <div class="col-title">Preparing</div>
 <span class="col-count cnt-prep"><?php echo $cnt_prep; ?></span>
 </div>
 <?php if ($cnt_prep === 0): ?>
 <div class="empty-col"><div class="empty-col-icon">⏳</div><p>Nothing in progress</p></div>
 <?php else: foreach ($preparing_groups as $g) renderMergedTicket($g); endif; ?>
 </div>

 <!-- COMPLETED -->
 <div class="col col-done">
 <div class="col-head">
 <div class="col-title"> Ready</div>
 <span class="col-count cnt-done"><?php echo $cnt_done; ?></span>
 </div>
 <?php if ($cnt_done === 0): ?>
 <div class="empty-col"><div class="empty-col-icon"></div><p>No completed orders</p></div>
 <?php else: foreach ($completed_groups as $g) renderMergedTicket($g); endif; ?>
 </div>
</div>

<script>
function filterOrders() {
 const input = document.getElementById("tableSearch").value.toLowerCase();
 document.querySelectorAll(".ticket").forEach(card => {
 const table = card.getAttribute("data-table") || "";
 card.style.display = table.includes(input) ? "" : "none";
 });
}
</script>
</body>
</html>