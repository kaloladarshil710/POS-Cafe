<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if (!isset($_GET['order_id'])) { header("Location: index.php"); exit(); }

$order_id = intval($_GET['order_id']);
$order_q  = mysqli_query($conn, "SELECT o.*,rt.table_number FROM orders o JOIN restaurant_tables rt ON o.table_id=rt.id WHERE o.id=$order_id");
$order    = mysqli_fetch_assoc($order_q);
if (!$order) { header("Location: index.php"); exit(); }
$payment  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM payments WHERE order_id=$order_id LIMIT 1"));
$items    = mysqli_query($conn,"SELECT * FROM order_items WHERE order_id=$order_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Successful — POS Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{--bg:#F7F4EF;--surface:#FFF;--border:#E8E2D9;--text:#1A1410;--text2:#6B5E52;--text3:#9C8E84;--primary:#C8602A;--green:#2D7D52;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;max-width:540px;width:100%;box-shadow:0 4px 24px rgba(28,20,16,0.08);}

.success-banner{background:rgba(45,125,82,0.06);border:1px solid rgba(45,125,82,0.2);border-radius:16px;padding:28px;text-align:center;margin-bottom:24px;}
.success-icon{width:64px;height:64px;background:rgba(45,125,82,0.1);border:2px solid rgba(45,125,82,0.25);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;}
.success-icon svg{width:28px;height:28px;stroke:#2D7D52;fill:none;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}
.success-banner h1{font-family:'DM Serif Display',serif;font-size:22px;color:var(--green);margin-bottom:4px;}
.success-banner p{font-size:14px;color:var(--text2);}

.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:22px;}
.info-box{background:var(--bg);border:1px solid var(--border);border-radius:11px;padding:13px;}
.info-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);margin-bottom:3px;}
.info-val{font-size:15px;font-weight:600;font-family:'DM Serif Display',serif;}

table{width:100%;border-collapse:collapse;margin-bottom:22px;}
th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);padding:10px 12px;border-bottom:2px solid var(--border);background:#FDFCFA;text-align:left;}
td{padding:12px;border-bottom:1px solid #F2EDE6;font-size:14px;}
tr:last-child td{border-bottom:none;}
.grand-row td{font-weight:600;font-size:15px;color:var(--green);background:rgba(45,125,82,0.04);}

.actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.btn{text-decoration:none;padding:13px;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;text-align:center;display:flex;align-items:center;justify-content:center;gap:8px;transition:all 0.18s;border:none;cursor:pointer;}
.btn-green{background:var(--green);color:white;}
.btn-green:hover{background:#235f3f;transform:translateY(-1px);}
.btn-outline{background:var(--bg);color:var(--text2);border:1px solid var(--border);}
.btn-outline:hover{background:var(--surface);color:var(--text);border-color:#d0c8be;}
.btn svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
</style>
</head>
<body>
<div class="card">
  <div class="success-banner">
    <div class="success-icon">
      <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h1>Payment Received</h1>
    <p>Table cleared. Order complete.</p>
  </div>

  <div class="info-grid">
    <div class="info-box"><div class="info-label">Order</div><div class="info-val"><?php echo htmlspecialchars($order['order_number']); ?></div></div>
    <div class="info-box"><div class="info-label">Table</div><div class="info-val"><?php echo htmlspecialchars($order['table_number']); ?></div></div>
    <div class="info-box"><div class="info-label">Paid Via</div><div class="info-val"><?php echo htmlspecialchars($payment['payment_method'] ?? '—'); ?></div></div>
    <div class="info-box"><div class="info-label">Amount</div><div class="info-val" style="color:var(--green);">&#8377;<?php echo number_format($order['total_amount'],2); ?></div></div>
  </div>

  <table>
    <thead><tr><th>Item</th><th>Qty</th><th>Total</th></tr></thead>
    <tbody>
      <?php while ($item = mysqli_fetch_assoc($items)): ?>
      <tr>
        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
        <td><?php echo $item['quantity']; ?></td>
        <td><strong>&#8377;<?php echo number_format($item['subtotal'],2); ?></strong></td>
      </tr>
      <?php endwhile; ?>
      <tr class="grand-row"><td colspan="2"><strong>Total Paid</strong></td><td><strong>&#8377;<?php echo number_format($order['total_amount'],2); ?></strong></td></tr>
    </tbody>
  </table>

  <div class="actions">
    <a class="btn btn-green" href="index.php">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
      Next Table
    </a>
    <a class="btn btn-outline" href="../admin/reports.php">
      <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      View Reports
    </a>
  </div>
</div>
</body>
</html>
