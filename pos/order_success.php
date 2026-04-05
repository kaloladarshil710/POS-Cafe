<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if (!isset($_GET['order_id'])) { header("Location: index.php"); exit(); }

$order_id = intval($_GET['order_id']);
$order_q  = mysqli_query($conn, "SELECT o.*,rt.table_number FROM orders o JOIN restaurant_tables rt ON o.table_id=rt.id WHERE o.id=$order_id");
$order    = mysqli_fetch_assoc($order_q);
if (!$order) { header("Location: index.php"); exit(); }
$items = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id=$order_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Confirmed — POS Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{--bg:#F7F4EF;--surface:#FFF;--border:#E8E2D9;--text:#1A1410;--text2:#6B5E52;--text3:#9C8E84;--primary:#C8602A;--primary-dark:#A84E20;--amber:#B8860B;--green:#2D7D52;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(28,20,16,0.08);}

.success-top{text-align:center;padding-bottom:24px;border-bottom:1px solid var(--border);margin-bottom:24px;}
.check-circle{width:64px;height:64px;background:rgba(45,125,82,0.1);border:2px solid rgba(45,125,82,0.25);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;}
.check-circle svg{width:28px;height:28px;stroke:#2D7D52;fill:none;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}
.success-top h1{font-family:'DM Serif Display',serif;font-size:22px;color:var(--green);margin-bottom:6px;}
.success-top p{font-size:14px;color:var(--text2);}

.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:24px;}
.info-box{background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:14px;}
.info-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);margin-bottom:4px;}
.info-val{font-size:16px;font-weight:600;font-family:'DM Serif Display',serif;}
.info-val.orange{color:var(--primary);}
.info-val.amber{color:var(--amber);}

table{width:100%;border-collapse:collapse;margin-bottom:24px;}
th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);padding:10px 12px;border-bottom:2px solid var(--border);background:#FDFCFA;text-align:left;}
td{padding:12px;border-bottom:1px solid #F2EDE6;font-size:14px;}
tr:last-child td{border-bottom:none;}
.total-row td{font-weight:600;font-size:15px;background:#FDF5F0;color:var(--primary);border-radius:0 0 8px 8px;}

.actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.actions.single-action{grid-template-columns:1fr;}
.btn{text-decoration:none;padding:13px 16px;border-radius:11px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;text-align:center;display:flex;align-items:center;justify-content:center;gap:8px;transition:all 0.18s;border:none;cursor:pointer;}
.btn-amber{background:#B8860B;color:white;}
.btn-amber:hover{background:#9a720a;transform:translateY(-1px);}
.btn-primary{background:var(--primary);color:white;}
.btn-primary:hover{background:var(--primary-dark);transform:translateY(-1px);}
.btn-outline{background:var(--bg);color:var(--text2);border:1px solid var(--border);}
.btn-outline:hover{background:var(--surface);color:var(--text);border-color:#d0c8be;}
.btn svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
</style>
</head>
<body>
<div class="card">
  <div class="success-top">
    <div class="check-circle">
      <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h1>Order Placed</h1>
    <p>Your order has been saved. Send it to the kitchen when ready.</p>
  </div>

  <div class="info-grid">
    <div class="info-box"><div class="info-label">Order Number</div><div class="info-val"><?php echo htmlspecialchars($order['order_number']); ?></div></div>
    <div class="info-box"><div class="info-label">Table</div><div class="info-val"><?php echo htmlspecialchars($order['table_number']); ?></div></div>
    <div class="info-box"><div class="info-label">Status</div><div class="info-val amber">Pending</div></div>
    <div class="info-box"><div class="info-label">Total</div><div class="info-val orange">&#8377;<?php echo number_format($order['total_amount'],2); ?></div></div>
  </div>

  <table>
    <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
    <tbody>
      <?php while ($item = mysqli_fetch_assoc($items)): ?>
      <tr>
        <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
        <td><?php echo $item['quantity']; ?></td>
        <td>&#8377;<?php echo number_format($item['price'],2); ?></td>
        <td><strong>&#8377;<?php echo number_format($item['subtotal'],2); ?></strong></td>
      </tr>
      <?php endwhile; ?>
      <tr class="total-row"><td colspan="3"><strong>Order Total</strong></td><td><strong>&#8377;<?php echo number_format($order['total_amount'],2); ?></strong></td></tr>
    </tbody>
  </table>

  <div class="actions single-action">
    <a class="btn btn-amber" href="../kitchen/send_to_kitchen.php?order_id=<?php echo $order['id']; ?>">
      <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      Send to Kitchen
    </a>
  </div>
  <div style="margin-top:10px;">
    <a class="btn btn-outline" href="index.php">
      <svg viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Back to Tables
    </a>
  </div>
</div>
</body>
</html>
