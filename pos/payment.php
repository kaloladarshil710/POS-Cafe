<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if (!isset($_GET['order_id'])) { header("Location: index.php"); exit(); }

$order_id = intval($_GET['order_id']);
$order_q  = mysqli_query($conn, "SELECT o.*, rt.table_number FROM orders o JOIN restaurant_tables rt ON o.table_id=rt.id WHERE o.id=$order_id");
$order    = mysqli_fetch_assoc($order_q);
if (!$order) { header("Location: index.php"); exit(); }

$items = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id=$order_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Placed — POS Cafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
        :root{--primary:#FF6B35;--primary-dark:#E85520;--text:#0F172A;--muted:#64748B;--border:#E2E8F0;--bg:#F4F5F7;}
        body{font-family:'Sora',sans-serif;background:var(--bg);min-height:100vh;display:flex;justify-content:center;align-items:flex-start;padding:32px 16px;}
        .card{background:white;border:1px solid var(--border);border-radius:28px;padding:36px;max-width:680px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.07);}
        .success-header{text-align:center;margin-bottom:32px;padding-bottom:24px;border-bottom:1px solid var(--border);}
        .success-icon{font-size:56px;margin-bottom:16px;}
        .success-header h1{font-size:26px;font-weight:800;color:#059669;margin-bottom:6px;}
        .success-header p{font-size:15px;color:var(--muted);}
        .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:28px;}
        .info-box{background:#F8FAFC;border:1px solid var(--border);border-radius:16px;padding:16px;}
        .info-box label{font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:var(--muted);font-weight:700;display:block;margin-bottom:5px;}
        .info-box strong{font-size:18px;font-weight:800;color:var(--text);}
        .order-table{width:100%;border-collapse:collapse;margin-bottom:24px;}
        .order-table th{background:#F8FAFC;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:var(--muted);font-weight:700;padding:12px 14px;text-align:left;border-bottom:2px solid var(--border);}
        .order-table td{padding:13px 14px;border-bottom:1px solid #F1F5F9;font-size:14px;}
        .order-table tr:last-child td{border-bottom:none;}
        .total-row td{font-weight:800;font-size:16px;color:var(--text);background:#F8FAFC;}
        .actions{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}
        .btn{text-decoration:none;padding:14px 16px;border-radius:14px;font-family:'Sora',sans-serif;font-size:14px;font-weight:700;text-align:center;display:flex;align-items:center;justify-content:center;gap:8px;transition:0.2s;border:none;cursor:pointer;}
        .btn-kitchen{background:linear-gradient(135deg,#F59E0B,#D97706);color:white;}
        .btn-kitchen:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(245,158,11,0.3);}
        .btn-primary{background:linear-gradient(135deg,#FF6B35,#E85520);color:white;}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(255,107,53,0.3);}
        .btn-outline{background:#F1F5F9;color:var(--text);border:1px solid var(--border);}
        .btn-outline:hover{background:var(--border);}
        .badge{display:inline-flex;align-items:center;padding:5px 12px;border-radius:999px;font-size:12px;font-weight:700;}
        .badge-pending{background:#FEF9C3;color:#854D0E;}
        .badge-kitchen{background:#FEF3C7;color:#92400E;}

        @media(max-width:500px){.info-grid{grid-template-columns:1fr;}.actions{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<div class="card">
    <div class="success-header">
        <div class="success-icon">✅</div>
        <h1>Order Placed Successfully!</h1>
        <p>The order has been saved. Send it to the kitchen when ready.</p>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <label>Order Number</label>
            <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
        </div>
        <div class="info-box">
            <label>Table</label>
            <strong><?php echo htmlspecialchars($order['table_number']); ?></strong>
        </div>
        <div class="info-box">
            <label>Status</label>
            <strong><span class="badge badge-pending">⏳ Pending</span></strong>
        </div>
        <div class="info-box">
            <label>Total Amount</label>
            <strong style="color:#FF6B35;">₹<?php echo number_format($order['total_amount'], 2); ?></strong>
        </div>
    </div>

    <table class="order-table">
        <thead>
            <tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
            <?php while ($item = mysqli_fetch_assoc($items)): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                <td><strong>₹<?php echo number_format($item['subtotal'], 2); ?></strong></td>
            </tr>
            <?php endwhile; ?>
            <tr class="total-row">
                <td colspan="3">Total</td>
                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="actions">
        <a class="btn btn-kitchen" href="../kitchen/send_to_kitchen.php?order_id=<?php echo $order['id']; ?>">
            👨‍🍳 Send to Kitchen
        </a>
        <a class="btn btn-primary" href="payment.php?order_id=<?php echo $order['id']; ?>">
            💳 Take Payment
        </a>
        <a class="btn btn-outline" href="index.php">
            ← Back to Tables
        </a>
    </div>
</div>
</body>
</html>