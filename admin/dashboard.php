<?php
include("../config/db.php");
include("layout/header.php");

$product_count = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM products"))['t'];
$table_count   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM restaurant_tables WHERE active='yes'"))['t'];
$today_orders  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM orders WHERE status='paid' AND DATE(created_at)=CURDATE()"))['t'];
$today_sales   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(total_amount),0) as t FROM orders WHERE status='paid' AND DATE(created_at)=CURDATE()"))['t'];
$kitchen_active= mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM orders WHERE status IN('to_cook','preparing')"))['t'];
$pending_orders= mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM orders WHERE status='pending'"))['t'];

$recent = mysqli_query($conn,"SELECT o.*,rt.table_number,u.name as staff FROM orders o JOIN restaurant_tables rt ON o.table_id=rt.id LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 8");
?>

<style>
.stat-accent-orange{border-top:3px solid #C8602A;}
.stat-accent-amber{border-top:3px solid #B8860B;}
.stat-accent-green{border-top:3px solid #2D7D52;}
.stat-num{font-family:'DM Serif Display',serif;}
.quick-link{display:flex;align-items:center;gap:12px;padding:13px 16px;border-radius:11px;text-decoration:none;font-size:14px;font-weight:500;transition:all 0.15s;border:1px solid transparent;}
.quick-link.primary-btn{background:var(--primary);color:white;border-color:var(--primary);}
.quick-link.primary-btn:hover{background:var(--primary-dark);}
.quick-link.amber-btn{background:#B8860B;color:white;border-color:#B8860B;}
.quick-link.amber-btn:hover{background:#9a720a;}
.quick-link.purple-btn{background:#6B48A0;color:white;border-color:#6B48A0;}
.quick-link.purple-btn:hover{background:#573d85;}
.quick-link.outline-btn{background:var(--bg);color:var(--text);border-color:var(--border);}
.quick-link.outline-btn:hover{background:var(--surface);border-color:#d0c8be;}
.quick-link svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;}
.badge-pill{display:inline-block;background:rgba(255,255,255,0.2);padding:1px 8px;border-radius:999px;font-size:11px;font-weight:700;margin-left:auto;}
.status-chip{font-size:12px;font-weight:600;padding:3px 9px;border-radius:5px;}
.status-paid{background:rgba(45,125,82,0.1);color:#2D7D52;}
.status-pending{background:rgba(184,134,11,0.1);color:#B8860B;}
.status-to_cook{background:rgba(200,96,42,0.1);color:#C8602A;}
.status-preparing{background:rgba(59,130,246,0.1);color:#2563EB;}
.status-completed{background:rgba(45,125,82,0.08);color:#2D7D52;}
</style>

<div class="card-grid">
  <div class="card stat-accent-orange">
    <div class="card-label">Today's Revenue</div>
    <div class="card-value stat-num" style="color:var(--primary);">&#8377;<?php echo number_format($today_sales,2); ?></div>
    <div class="card-sub"><?php echo $today_orders; ?> orders completed</div>
  </div>
  <div class="card stat-accent-amber">
    <div class="card-label">In Kitchen</div>
    <div class="card-value stat-num" style="color:#B8860B;"><?php echo $kitchen_active; ?></div>
    <div class="card-sub"><?php echo $pending_orders; ?> pending</div>
  </div>
  <div class="card stat-accent-green">
    <div class="card-label">Active Tables</div>
    <div class="card-value stat-num" style="color:#2D7D52;"><?php echo $table_count; ?></div>
    <div class="card-sub"><?php echo $product_count; ?> products on menu</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">
  <div class="panel" style="margin:0;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <div class="panel-title" style="margin:0;">Recent Orders</div>
      <a href="reports.php" style="font-size:13px;color:var(--primary);text-decoration:none;font-weight:600;">View all</a>
    </div>
    <div class="table-wrap">
      <table>
        <tr><th>Order</th><th>Table</th><th>Staff</th><th>Amount</th><th>Status</th><th>Time</th></tr>
        <?php $has=false; while($row=mysqli_fetch_assoc($recent)): $has=true;
          $smap=['pending'=>'pending','to_cook'=>'to_cook','preparing'=>'preparing','completed'=>'completed','paid'=>'paid'];
          $status_class='status-'.($smap[$row['status']]??'pending');
          $status_label=ucwords(str_replace('_',' ',$row['status']));
        ?>
        <tr>
          <td><span style="font-size:12px;font-weight:600;color:var(--text3);"><?php echo htmlspecialchars($row['order_number']); ?></span></td>
          <td style="font-weight:500;"><?php echo htmlspecialchars($row['table_number']); ?></td>
          <td style="color:var(--text2);"><?php echo htmlspecialchars($row['staff']??'—'); ?></td>
          <td><strong style="color:var(--primary);">&#8377;<?php echo number_format($row['total_amount'],2); ?></strong></td>
          <td><span class="status-chip <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
          <td style="font-size:12px;color:var(--text3);"><?php echo date('h:i A',strtotime($row['created_at'])); ?></td>
        </tr>
        <?php endwhile; if(!$has): ?><tr><td colspan="6" style="text-align:center;color:var(--text3);padding:28px;font-size:14px;">No orders yet today.</td></tr><?php endif; ?>
      </table>
    </div>
  </div>

  <div class="panel" style="margin:0;">
    <div class="panel-title">Quick Actions</div>
    <div style="display:flex;flex-direction:column;gap:9px;">
      <a href="../pos/index.php" class="quick-link primary-btn">
        <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        Open POS Terminal
      </a>
      <a href="../kitchen/kitchen.php" class="quick-link amber-btn">
        <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Kitchen Display
        <?php if($kitchen_active>0): ?><span class="badge-pill"><?php echo $kitchen_active; ?></span><?php endif; ?>
      </a>
      <a href="reports.php" class="quick-link purple-btn">
        <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        View Reports
      </a>
      <a href="products.php" class="quick-link outline-btn">
        <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        Manage Products
      </a>
      <a href="tables.php" class="quick-link outline-btn">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
        Manage Tables
      </a>
    </div>
  </div>
</div>

<?php include("layout/footer.php"); ?>
