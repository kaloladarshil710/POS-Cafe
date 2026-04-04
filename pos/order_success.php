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
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{--bg:#F0F2F5;--surface:#FFF;--border:#E4E7EC;--text:#101828;--text2:#667085;--text3:#98A2B3;--primary:#F97316;--primary-dark:#EA6C0A;--amber:#F59E0B;--green:#12B76A;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:24px;padding:32px;max-width:620px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,0.06);}

.success-top{text-align:center;padding-bottom:24px;border-bottom:1px solid var(--border);margin-bottom:24px;}
.check-circle{width:72px;height:72px;background:linear-gradient(135deg,#12B76A,#0DA863);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 16px;}
.success-top h1{font-size:22px;font-weight:800;color:#12B76A;margin-bottom:6px;}
.success-top p{font-size:14px;color:var(--text2);}

.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:24px;}
.info-box{background:#F8FAFC;border:1px solid var(--border);border-radius:14px;padding:14px;}
.info-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);margin-bottom:4px;}
.info-val{font-size:16px;font-weight:800;}
.info-val.orange{color:var(--primary);}

table{width:100%;border-collapse:collapse;margin-bottom:24px;}
th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);padding:10px 12px;border-bottom:2px solid var(--border);background:#F8FAFC;text-align:left;}
td{padding:12px;border-bottom:1px solid #F2F4F7;font-size:14px;}
.total-row td{font-weight:800;font-size:15px;background:#FFF8F5;color:var(--primary);}

.actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.actions.single-action{grid-template-columns:1fr;}
.btn{text-decoration:none;padding:14px 16px;border-radius:14px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;text-align:center;display:flex;align-items:center;justify-content:center;gap:8px;transition:0.18s;border:none;cursor:pointer;}
.btn-amber{background:linear-gradient(135deg,#F59E0B,#D97706);color:white;}
.btn-amber:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(245,158,11,0.3);}
.btn-primary{background:linear-gradient(135deg,#F97316,#EA6C0A);color:white;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(249,115,22,0.3);}
.btn-outline{background:var(--bg);color:var(--text);border:1px solid var(--border);}
.btn-outline:hover{background:var(--border);}
</style>
</head>
<body>
<div class="card">
  <div class="success-top">
    <div class="check-circle">✅</div>
    <h1>Order Placed!</h1>
    <p>Your order is saved. Send it to the kitchen now.</p>
  </div>

  <div class="info-grid">
    <div class="info-box"><div class="info-label">Order Number</div><div class="info-val"><?php echo htmlspecialchars($order['order_number']); ?></div></div>
    <div class="info-box"><div class="info-label">Table</div><div class="info-val"><?php echo htmlspecialchars($order['table_number']); ?></div></div>
    <div class="info-box"><div class="info-label">Status</div><div class="info-val" style="color:#F59E0B;">⏳ Pending</div></div>
    <div class="info-box"><div class="info-label">Total</div><div class="info-val orange">₹<?php echo number_format($order['total_amount'],2); ?></div></div>
  </div>

  <table>
    <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
    <tbody>
      <?php while ($item = mysqli_fetch_assoc($items)): ?>
      <tr>
        <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
        <td><?php echo $item['quantity']; ?></td>
        <td>₹<?php echo number_format($item['price'],2); ?></td>
        <td><strong>₹<?php echo number_format($item['subtotal'],2); ?></strong></td>
      </tr>
      <?php endwhile; ?>
      <tr class="total-row"><td colspan="3">Total</td><td>₹<?php echo number_format($order['total_amount'],2); ?></td></tr>
    </tbody>
  </table>

 <div class="actions single-action">
  <a class="btn btn-amber" href="../kitchen/send_to_kitchen.php?order_id=<?php echo $order['id']; ?>">👨‍🍳 Send to Kitchen</a>
</div>
  <div style="margin-top:12px;">
    <a class="btn btn-outline" href="index.php">← Back to Tables</a>
  </div>
</div>
</body>
</html>
