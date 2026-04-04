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
$pay_icons= ['Cash'=>'💵','Digital'=>'💳','UPI'=>'📱'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Successful — POS Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{--bg:#F0F2F5;--surface:#FFF;--border:#E4E7EC;--text:#101828;--text2:#667085;--text3:#98A2B3;--primary:#F97316;--green:#12B76A;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:24px;padding:32px;max-width:560px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,0.06);}
.success-banner{background:linear-gradient(135deg,#12B76A,#0DA863);border-radius:18px;padding:28px;text-align:center;color:white;margin-bottom:24px;}
.success-banner .icon{font-size:52px;margin-bottom:10px;}
.success-banner h1{font-size:22px;font-weight:800;margin-bottom:4px;}
.success-banner p{font-size:14px;opacity:0.85;}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:22px;}
.info-box{background:#F8FAFC;border:1px solid var(--border);border-radius:13px;padding:13px;}
.info-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);margin-bottom:3px;}
.info-val{font-size:15px;font-weight:800;}
table{width:100%;border-collapse:collapse;margin-bottom:22px;}
th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);padding:10px 12px;border-bottom:2px solid var(--border);background:#F8FAFC;text-align:left;}
td{padding:12px;border-bottom:1px solid #F2F4F7;font-size:14px;}
.grand-row td{font-weight:800;font-size:15px;color:var(--green);background:rgba(18,183,106,0.06);}
.actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.btn{text-decoration:none;padding:14px;border-radius:14px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;text-align:center;display:flex;align-items:center;justify-content:center;gap:8px;transition:0.18s;border:none;cursor:pointer;}
.btn-green{background:linear-gradient(135deg,#12B76A,#0DA863);color:white;}
.btn-green:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(18,183,106,0.3);}
.btn-outline{background:var(--bg);color:var(--text);border:1px solid var(--border);}
.btn-outline:hover{background:var(--border);}
</style>
</head>
<body>
<div class="card">
  <div class="success-banner">
    <div class="icon">🎉</div>
    <h1>Payment Successful!</h1>
    <p>Table cleared. Order complete.</p>
  </div>

  <div class="info-grid">
    <div class="info-box"><div class="info-label">Order</div><div class="info-val"><?php echo htmlspecialchars($order['order_number']); ?></div></div>
    <div class="info-box"><div class="info-label">Table</div><div class="info-val"><?php echo htmlspecialchars($order['table_number']); ?></div></div>
    <div class="info-box"><div class="info-label">Paid Via</div><div class="info-val"><?php $pm=$payment['payment_method']??'—'; echo ($pay_icons[$pm]??'').' '.htmlspecialchars($pm); ?></div></div>
    <div class="info-box"><div class="info-label">Amount</div><div class="info-val" style="color:var(--green);">₹<?php echo number_format($order['total_amount'],2); ?></div></div>
  </div>

  <table>
    <thead><tr><th>Item</th><th>Qty</th><th>Total</th></tr></thead>
    <tbody>
      <?php while ($item = mysqli_fetch_assoc($items)): ?>
      <tr>
        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
        <td><?php echo $item['quantity']; ?></td>
        <td><strong>₹<?php echo number_format($item['subtotal'],2); ?></strong></td>
      </tr>
      <?php endwhile; ?>
      <tr class="grand-row"><td colspan="2"><strong>Total Paid</strong></td><td><strong>₹<?php echo number_format($order['total_amount'],2); ?></strong></td></tr>
    </tbody>
  </table>

  <div class="actions">
    <a class="btn btn-green" href="index.php">🪑 Next Table</a>
    <a class="btn btn-outline" href="../admin/reports.php">📊 View Reports</a>
  </div>
</div>
</body>
</html>
