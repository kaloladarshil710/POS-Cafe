<?php
session_start();
include("../config/db.php");

// FIXED: Added missing session check
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if (!isset($_GET['order_id'])) { header("Location: index.php"); exit(); }

$order_id = intval($_GET['order_id']);
$order_q  = mysqli_query($conn, "SELECT o.*, rt.table_number FROM orders o JOIN restaurant_tables rt ON o.table_id=rt.id WHERE o.id=$order_id");
$order    = mysqli_fetch_assoc($order_q);
if (!$order) { header("Location: index.php"); exit(); }

$payment  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM payments WHERE order_id=$order_id LIMIT 1"));
$items    = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id=$order_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful — POS Cafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
        :root{--primary:#FF6B35;--text:#0F172A;--muted:#64748B;--border:#E2E8F0;--bg:#F4F5F7;}
        body{font-family:'Sora',sans-serif;background:var(--bg);min-height:100vh;display:flex;justify-content:center;align-items:flex-start;padding:32px 16px;}
        .card{background:white;border:1px solid var(--border);border-radius:28px;padding:36px;max-width:600px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.07);}

        .success-banner{background:linear-gradient(135deg,#10B981,#059669);border-radius:20px;padding:28px;text-align:center;color:white;margin-bottom:28px;}
        .success-icon{font-size:52px;margin-bottom:12px;}
        .success-banner h1{font-size:24px;font-weight:800;margin-bottom:6px;}
        .success-banner p{font-size:14px;opacity:0.85;}

        .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px;}
        .info-box{background:#F8FAFC;border:1px solid var(--border);border-radius:14px;padding:14px;}
        .info-box label{font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:var(--muted);font-weight:700;display:block;margin-bottom:4px;}
        .info-box strong{font-size:16px;font-weight:800;color:var(--text);}

        .receipt-title{font-size:14px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:14px;}
        table{width:100%;border-collapse:collapse;margin-bottom:24px;}
        th{background:#F8FAFC;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:var(--muted);font-weight:700;padding:11px 14px;text-align:left;border-bottom:2px solid var(--border);}
        td{padding:12px 14px;border-bottom:1px solid #F1F5F9;font-size:14px;}
        tr:last-child td{border-bottom:none;}
        .grand{font-size:17px;font-weight:800;background:#FFF8F5;color:var(--primary);}

        .method-badge{display:inline-flex;align-items:center;gap:6px;background:#DBEAFE;color:#1D4ED8;padding:6px 14px;border-radius:999px;font-size:13px;font-weight:700;}

        .actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
        .btn{text-decoration:none;padding:14px;border-radius:14px;font-family:'Sora',sans-serif;font-size:14px;font-weight:700;text-align:center;transition:0.2s;display:flex;align-items:center;justify-content:center;gap:8px;}
        .btn-green{background:linear-gradient(135deg,#10B981,#059669);color:white;}
        .btn-green:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(16,185,129,0.3);}
        .btn-outline{background:#F1F5F9;color:var(--text);border:1px solid var(--border);}
        .btn-outline:hover{background:var(--border);}

        @media(max-width:500px){.info-grid,.actions{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<div class="card">
    <div class="success-banner">
        <div class="success-icon">✅</div>
        <h1>Payment Successful!</h1>
        <p>Table has been freed and is ready for the next customer.</p>
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
            <label>Amount Paid</label>
            <strong style="color:#FF6B35;">₹<?php echo number_format($order['total_amount'], 2); ?></strong>
        </div>
        <div class="info-box">
            <label>Payment Method</label>
            <span class="method-badge">
                <?php
                $icons = ['Cash' => '💵', 'Digital' => '💳', 'UPI' => '📱'];
                $pm = $payment['payment_method'] ?? 'Cash';
                echo ($icons[$pm] ?? '💰') . ' ' . htmlspecialchars($pm);
                ?>
            </span>
        </div>
    </div>

    <div class="receipt-title">Receipt</div>
    <table>
        <thead>
            <tr><th>Item</th><th>Qty</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
            <?php while ($item = mysqli_fetch_assoc($items)): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>₹<?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endwhile; ?>
            <tr>
                <td colspan="2" class="grand">Total Paid</td>
                <td class="grand">₹<?php echo number_format($order['total_amount'], 2); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="actions">
        <a class="btn btn-green" href="index.php">✅ Next Table →</a>
        <a class="btn btn-outline" href="../admin/reports.php">📊 View Reports</a>
    </div>
</div>
</body>
</html>