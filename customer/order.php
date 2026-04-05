<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = safe_int($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    die("Invalid order.");
}

// Get order details
$stmt = mysqli_prepare($conn, "
    SELECT o.*, rt.table_number, u.name as staff_name,
           (SELECT SUM(quantity) FROM order_items WHERE order_id=?) as total_items
    FROM orders o
    JOIN restaurant_tables rt ON o.table_id = rt.id
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id=?
");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $order_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) {
    die("Order not found.");
}

// Get order items
$items = mysqli_query($conn, "
    SELECT oi.*, p.name, c.category_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    WHERE oi.order_id=$order_id
");

// Get payment info if paid
$payment = null;
if ($order['status'] === 'paid') {
    $payment = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT * FROM payments WHERE order_id=$order_id LIMIT 1"));
}

// Handle payment submission (reuses logic from pos/process_payment.php)
$payment_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    if (!hash_equals($_SESSION['customer_csrf'] ?? '', $_POST['csrf'] ?? '')) {
        die('CSRF check failed');
    }

    $method = trim($_POST['method'] ?? '');
    $allowed_methods = ['Cash', 'Digital', 'UPI'];

    if (!in_array($method, $allowed_methods)) {
        $payment_message = "Invalid payment method.";
    } else {
        // Check order status
        $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM orders WHERE id=$order_id"));
        
        if ($check['status'] === 'paid') {
            $payment_message = "This order is already paid.";
        } else {
            // Record payment (same as pos/process_payment.php)
            $pay_stmt = mysqli_prepare($conn, 
                "INSERT INTO payments (order_id, payment_method, amount, status) VALUES (?, ?, ?, 'paid')");
            mysqli_stmt_bind_param($pay_stmt, "isd", $order_id, $method, $order['total_amount']);
            
            if (mysqli_stmt_execute($pay_stmt)) {
                mysqli_stmt_close($pay_stmt);

                // Update order status
                $upd = mysqli_prepare($conn, "UPDATE orders SET status='paid' WHERE id=?");
                mysqli_stmt_bind_param($upd, "i", $order_id);
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);

                // Free the table
                $tbl = mysqli_prepare($conn, "UPDATE restaurant_tables SET status='free' WHERE id=?");
                mysqli_stmt_bind_param($tbl, "i", $order['table_id']);
                mysqli_stmt_execute($tbl);
                mysqli_stmt_close($tbl);

                // Refresh order info
                $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id=?");
                mysqli_stmt_bind_param($stmt, "i", $order_id);
                mysqli_stmt_execute($stmt);
                $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_stmt_close($stmt);

                $payment_message = "✅ Payment successful! Thank you.";
                $payment = mysqli_fetch_assoc(mysqli_query($conn, 
                    "SELECT * FROM payments WHERE order_id=$order_id LIMIT 1"));
            } else {
                $payment_message = "Payment failed. Please try again.";
            }
        }
    }
}

if (empty($_SESSION['customer_csrf'])) {
    $_SESSION['customer_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['customer_csrf'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Order Details — POS Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--primary:#F97316;--bg:#0A0A0F;--surface:#12121A;--border:rgba(255,255,255,0.07);--text:#F1F1F5;--text2:#9999B3;--text3:#555570;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;padding:28px;}

.container{max-width:800px;margin:0 auto;}
.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--primary);text-decoration:none;margin-bottom:20px;font-weight:700;}
.back-link:hover{text-decoration:underline;}

.panel{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px;margin-bottom:20px;}
.panel h2{font-size:18px;font-weight:800;margin-bottom:16px;}

.order-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--border);}
.order-number{font-size:24px;font-weight:800;}
.order-status{padding:8px 14px;border-radius:8px;font-weight:700;display:inline-block;font-size:13px;}
.status-pending{background:#F59E0B;color:white;}
.status-paid{background:#10B981;color:white;}

.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
.info-item h3{font-size:12px;color:var(--text3);text-transform:uppercase;margin-bottom:6px;font-weight:700;}
.info-item p{font-size:14px;}

.table-wrap{overflow-x:auto;margin-bottom:20px;}
table{width:100%;border-collapse:collapse;}
table th{text-align:left;padding:10px;font-size:12px;color:var(--text3);border-bottom:1px solid var(--border);text-transform:uppercase;font-weight:700;}
table td{padding:12px 10px;border-bottom:1px solid var(--border);font-size:13px;}
table tbody tr:hover{background:rgba(249,115,22,0.03);}

.summary{background:rgba(249,115,22,0.05);border:1px solid var(--border);padding:16px;border-radius:8px;margin-bottom:20px;}
.summary-row{display:flex;justify-content:space-between;padding:8px 0;font-size:14px;}
.summary-row.total{border-top:1px solid var(--border);padding-top:12px;font-size:16px;font-weight:800;color:var(--primary);}

.payment-methods{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;}
.method-btn{padding:16px;background:var(--border);border:2px solid transparent;border-radius:10px;cursor:pointer;text-align:center;font-weight:700;transition:0.15s;font-size:13px;}
.method-btn:hover{background:rgba(249,115,22,0.1);border-color:var(--primary);}
.method-btn input:checked ~ label,.method-btn input[type="radio"]:checked ~ &{background:var(--primary);color:white;border-color:var(--primary);}

.input-group{position:relative;margin-bottom:16px;}
input[type="radio"]{display:none;}
.radio-label{display:block;padding:16px;background:var(--border);border-radius:10px;cursor:pointer;text-align:center;font-weight:700;transition:0.15s;}
input[type="radio"]:checked ~ .radio-label{background:var(--primary);color:white;}

.btn{width:100%;padding:14px;background:var(--primary);color:white;border:none;border-radius:10px;font-weight:800;cursor:pointer;font-size:15px;transition:0.15s;}
.btn:hover:not(:disabled){background:#EA6C0A;}
.btn:disabled{background:#64748b;cursor:not-allowed;}

.msg-success{background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);color:#6EE7B7;padding:14px;border-radius:8px;margin-bottom:16px;}
.msg-error{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#F87171;padding:14px;border-radius:8px;margin-bottom:16px;}

@media (max-width: 600px) {
    .info-grid{grid-template-columns:1fr;}
    .payment-methods{grid-template-columns:1fr;}
}
</style>
</head>
<body>

<div class="container">
    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

    <div class="panel">
        <div class="order-header">
            <div>
                <div class="order-number"><?php echo h($order['order_number']); ?></div>
                <div style="font-size:13px;color:var(--text2);margin-top:4px;">Table <?php echo h($order['table_number']); ?></div>
            </div>
            <div class="order-status status-<?php echo strtolower($order['status']); ?>">
                <?php echo ucfirst($order['status']); ?>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <h3>Order Date</h3>
                <p><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
            </div>
            <div class="info-item">
                <h3>Staff Member</h3>
                <p><?php echo h($order['staff_name'] ?? 'N/A'); ?></p>
            </div>
            <div class="info-item">
                <h3>Items Ordered</h3>
                <p><?php echo $order['total_items']; ?> items</p>
            </div>
            <div class="info-item">
                <h3>Total Amount</h3>
                <p style="font-size:16px;color:var(--primary);font-weight:800;">₹<?php echo number_format($order['total_amount'], 2); ?></p>
            </div>
        </div>
    </div>

    <!-- ITEMS -->
    <div class="panel">
        <h2>📦 Order Items</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = mysqli_fetch_assoc($items)): ?>
                    <tr>
                        <td><strong><?php echo h($item['name']); ?></strong></td>
                        <td><?php echo h($item['category_name']); ?></td>
                        <td style="text-align:center;"><?php echo $item['quantity']; ?></td>
                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                        <td style="font-weight:700;color:var(--primary);">₹<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PAYMENT SECTION -->
    <?php if($order['status'] !== 'paid'): ?>
    <div class="panel">
        <h2>💳 Payment</h2>
        
        <?php if($payment_message): ?>
        <div class="<?php echo strpos($payment_message, '✅') !== false ? 'msg-success' : 'msg-error'; ?>">
            <?php echo h($payment_message); ?>
        </div>
        <?php endif; ?>

        <div class="summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
            <div class="summary-row total">
                <span>Total Amount Due</span>
                <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
            
            <h3 style="font-size:13px;text-transform:uppercase;color:var(--text3);margin-bottom:12px;font-weight:700;">Select Payment Method</h3>

            <div class="payment-methods">
                <label class="input-group">
                    <input type="radio" name="method" value="Cash" required>
                    <div class="radio-label">💵 Cash</div>
                </label>
                <label class="input-group">
                    <input type="radio" name="method" value="Digital" required>
                    <div class="radio-label">💳 Digital</div>
                </label>
                <label class="input-group">
                    <input type="radio" name="method" value="UPI" required>
                    <div class="radio-label">📱 UPI</div>
                </label>
            </div>

            <button type="submit" name="pay" class="btn">Complete Payment →</button>
        </form>
    </div>
    <?php else: ?>
    <div class="panel">
        <h2>✅ Payment Completed</h2>
        <div class="msg-success">
            <strong>Payment successful!</strong><br>
            Method: <?php echo h($payment['payment_method']); ?><br>
            Amount: ₹<?php echo number_format($payment['amount'], 2); ?><br>
            Date: <?php echo date('d M Y, h:i A', strtotime($payment['created_at'])); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>