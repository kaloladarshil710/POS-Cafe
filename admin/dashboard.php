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

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
  <div class="card" style="border-left:4px solid #F97316;">
    <h3 style="font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:8px;">Today's Revenue</h3>
    <p style="font-size:28px;font-weight:800;color:#F97316;">₹<?php echo number_format($today_sales,2); ?></p>
    <div style="font-size:13px;color:#64748b;margin-top:4px;"><?php echo $today_orders; ?> orders today</div>
  </div>
  <div class="card" style="border-left:4px solid #F59E0B;">
    <h3 style="font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:8px;">In Kitchen</h3>
    <p style="font-size:28px;font-weight:800;color:#F59E0B;"><?php echo $kitchen_active; ?></p>
    <div style="font-size:13px;color:#64748b;margin-top:4px;"><?php echo $pending_orders; ?> pending</div>
  </div>
  <div class="card" style="border-left:4px solid #22C55E;">
    <h3 style="font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:8px;">Active Tables</h3>
    <p style="font-size:28px;font-weight:800;color:#22C55E;"><?php echo $table_count; ?></p>
    <div style="font-size:13px;color:#64748b;margin-top:4px;"><?php echo $product_count; ?> products</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">
  <div class="panel" style="margin:0;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <h3>Recent Orders</h3>
      <a href="reports.php" style="font-size:13px;color:#F97316;text-decoration:none;font-weight:700;">View All →</a>
    </div>
    <div class="table-wrap">
      <table>
        <tr><th>Order</th><th>Table</th><th>Staff</th><th>Amount</th><th>Status</th><th>Time</th></tr>
        <?php $has=false; while($row=mysqli_fetch_assoc($recent)): $has=true;
          $smap=['pending'=>['⏳','#F59E0B'],'to_cook'=>['🔥','#F97316'],'preparing'=>['👨‍🍳','#3B82F6'],'completed'=>['✅','#22C55E'],'paid'=>['💰','#10B981']];
          [$sicon,$scolor]=$smap[$row['status']]??['?','#94a3b8'];
        ?>
        <tr>
          <td><strong style="font-size:12px;"><?php echo htmlspecialchars($row['order_number']); ?></strong></td>
          <td><?php echo htmlspecialchars($row['table_number']); ?></td>
          <td><?php echo htmlspecialchars($row['staff']??'—'); ?></td>
          <td><strong style="color:#F97316;">₹<?php echo number_format($row['total_amount'],2); ?></strong></td>
          <td><span style="color:<?php echo $scolor; ?>;font-size:13px;font-weight:700;"><?php echo $sicon.' '.ucfirst($row['status']); ?></span></td>
          <td style="font-size:12px;color:#94a3b8;"><?php echo date('h:i A',strtotime($row['created_at'])); ?></td>
        </tr>
        <?php endwhile; if(!$has): ?><tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:24px;">No orders yet.</td></tr><?php endif; ?>
      </table>
    </div>
  </div>

  <div class="panel" style="margin:0;">
    <h3 style="margin-bottom:16px;">Quick Actions</h3>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <a href="../pos/index.php" style="display:flex;align-items:center;gap:10px;padding:14px;background:linear-gradient(90deg,#F97316,#EA6C0A);color:white;border-radius:14px;text-decoration:none;font-weight:700;font-size:14px;">
        🚀 Open POS Terminal
      </a>
      <a href="../kitchen/kitchen.php" style="display:flex;align-items:center;gap:10px;padding:14px;background:linear-gradient(90deg,#F59E0B,#D97706);color:white;border-radius:14px;text-decoration:none;font-weight:700;font-size:14px;">
        👨‍🍳 Kitchen Display <?php if($kitchen_active>0): ?><span style="background:rgba(0,0,0,0.2);padding:2px 8px;border-radius:999px;font-size:11px;"><?php echo $kitchen_active; ?></span><?php endif; ?>
      </a>
      <a href="reports.php" style="display:flex;align-items:center;gap:10px;padding:14px;background:linear-gradient(90deg,#8B5CF6,#7C3AED);color:white;border-radius:14px;text-decoration:none;font-weight:700;font-size:14px;">
        📊 View Reports
      </a>
      <a href="products.php" style="display:flex;align-items:center;gap:10px;padding:14px;background:#F8FAFC;border:1px solid #E2E8F0;color:#0F172A;border-radius:14px;text-decoration:none;font-weight:700;font-size:14px;">
        🍔 Manage Products
      </a>
      <a href="tables.php" style="display:flex;align-items:center;gap:10px;padding:14px;background:#F8FAFC;border:1px solid #E2E8F0;color:#0F172A;border-radius:14px;text-decoration:none;font-weight:700;font-size:14px;">
        🪑 Manage Tables
      </a>
    </div>
  </div>
</div>

<?php include("layout/footer.php"); ?>
