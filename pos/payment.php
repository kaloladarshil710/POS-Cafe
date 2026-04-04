<?php
// FIXED: payment.php was a copy of order_success.php — completely rewritten
// as the actual payment selection screen (Cash / Digital / UPI QR)
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if (!isset($_GET['order_id'])) { header("Location: index.php"); exit(); }

$order_id = intval($_GET['order_id']);
$order_q  = mysqli_query($conn, "SELECT o.*, rt.table_number FROM orders o JOIN restaurant_tables rt ON o.table_id=rt.id WHERE o.id=$order_id");
$order    = mysqli_fetch_assoc($order_q);
if (!$order) { header("Location: index.php"); exit(); }

// If already paid, redirect to success
if ($order['status'] === 'paid') { header("Location: payment_success.php?order_id=$order_id"); exit(); }

// Get enabled payment methods
$methods_q = mysqli_query($conn, "SELECT * FROM payment_methods WHERE is_enabled='yes' ORDER BY id ASC");
$methods = [];
while ($m = mysqli_fetch_assoc($methods_q)) $methods[] = $m;

// Get UPI ID for QR
$upi_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT upi_id FROM payment_methods WHERE method_name='UPI' AND is_enabled='yes' LIMIT 1"));
$upi_id  = $upi_row ? $upi_row['upi_id'] : '';

$items = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id=$order_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment — <?php echo htmlspecialchars($order['order_number']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
        :root{--primary:#FF6B35;--primary-dark:#E85520;--text:#0F172A;--muted:#64748B;--border:#E2E8F0;--bg:#F4F5F7;}
        body{font-family:'Sora',sans-serif;background:var(--bg);min-height:100vh;display:flex;justify-content:center;align-items:flex-start;padding:32px 16px;}
        .wrap{display:grid;grid-template-columns:1fr 380px;gap:24px;max-width:940px;width:100%;}
        .card{background:white;border:1px solid var(--border);border-radius:24px;padding:28px;box-shadow:0 10px 40px rgba(0,0,0,0.06);}
        h2{font-size:18px;font-weight:800;margin-bottom:18px;}
        .order-meta{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;}
        .meta-box{background:#F8FAFC;border:1px solid var(--border);border-radius:12px;padding:12px 16px;flex:1;min-width:120px;}
        .meta-box label{font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:var(--muted);font-weight:700;display:block;margin-bottom:3px;}
        .meta-box strong{font-size:15px;font-weight:800;}
        table{width:100%;border-collapse:collapse;margin-bottom:16px;}
        th{font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:var(--muted);font-weight:700;padding:10px 12px;text-align:left;border-bottom:2px solid var(--border);background:#F8FAFC;}
        td{padding:11px 12px;border-bottom:1px solid #F1F5F9;font-size:14px;}
        .grand-row td{font-weight:800;font-size:16px;color:var(--primary);background:#FFF8F5;}
        /* Payment methods */
        .method-list{display:flex;flex-direction:column;gap:12px;margin-bottom:20px;}
        .method-btn{display:flex;align-items:center;gap:14px;padding:18px 20px;border:2px solid var(--border);border-radius:16px;cursor:pointer;transition:all 0.2s;background:white;}
        .method-btn:hover{border-color:var(--primary);background:#FFF8F5;}
        .method-btn.selected{border-color:var(--primary);background:#FFF8F5;box-shadow:0 0 0 4px rgba(255,107,53,0.1);}
        .method-icon{font-size:28px;width:44px;text-align:center;}
        .method-info{flex:1;}
        .method-name{font-size:16px;font-weight:700;}
        .method-desc{font-size:13px;color:var(--muted);margin-top:2px;}
        .method-check{width:22px;height:22px;border-radius:50%;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;transition:0.2s;}
        .method-btn.selected .method-check{background:var(--primary);border-color:var(--primary);color:white;font-size:12px;}
        /* Amount display */
        .amount-display{background:linear-gradient(135deg,#FF6B35,#E85520);border-radius:18px;padding:22px;text-align:center;color:white;margin-bottom:20px;}
        .amount-label{font-size:13px;opacity:0.85;margin-bottom:6px;}
        .amount-value{font-size:42px;font-weight:800;}
        /* QR modal */
        .qr-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:999;align-items:center;justify-content:center;}
        .qr-overlay.show{display:flex;}
        .qr-box{background:white;border-radius:28px;padding:36px;max-width:380px;width:90%;text-align:center;}
        .qr-title{font-size:20px;font-weight:800;margin-bottom:4px;}
        .qr-sub{font-size:14px;color:var(--muted);margin-bottom:24px;}
        .qr-img{width:200px;height:200px;border:3px solid var(--border);border-radius:18px;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;font-size:80px;background:#F8FAFC;}
        .qr-upi{font-size:16px;font-weight:700;color:var(--primary);margin-bottom:6px;}
        .qr-amount{font-size:28px;font-weight:800;margin-bottom:24px;}
        .qr-btns{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
        .btn{padding:14px 18px;border-radius:14px;font-family:'Sora',sans-serif;font-size:14px;font-weight:700;cursor:pointer;border:none;transition:0.2s;}
        .btn-confirm{background:linear-gradient(135deg,#10B981,#059669);color:white;}
        .btn-confirm:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(16,185,129,0.3);}
        .btn-cancel{background:#F1F5F9;color:var(--text);}
        .btn-cancel:hover{background:var(--border);}
        .pay-btn{width:100%;padding:16px;background:var(--primary);color:white;border:none;border-radius:14px;font-family:'Sora',sans-serif;font-size:16px;font-weight:800;cursor:pointer;transition:0.2s;}
        .pay-btn:hover:not(:disabled){background:var(--primary-dark);transform:translateY(-2px);box-shadow:0 10px 28px rgba(255,107,53,0.35);}
        .pay-btn:disabled{background:#CBD5E1;color:#94A3B8;cursor:not-allowed;transform:none;}
        .back-link{display:inline-flex;align-items:center;gap:6px;text-decoration:none;color:var(--muted);font-size:13px;font-weight:600;margin-top:14px;}
        .back-link:hover{color:var(--text);}
        @media(max-width:720px){.wrap{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<div class="wrap">
    <!-- Left: Order Summary -->
    <div class="card">
        <h2>📋 Order Summary</h2>
        <div class="order-meta">
            <div class="meta-box">
                <label>Order #</label>
                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
            </div>
            <div class="meta-box">
                <label>Table</label>
                <strong><?php echo htmlspecialchars($order['table_number']); ?></strong>
            </div>
        </div>
        <table>
            <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
            <tbody>
                <?php while ($item = mysqli_fetch_assoc($items)): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                    <td><strong>₹<?php echo number_format($item['subtotal'], 2); ?></strong></td>
                </tr>
                <?php endwhile; ?>
                <tr class="grand-row">
                    <td colspan="3"><strong>Grand Total</strong></td>
                    <td><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                </tr>
            </tbody>
        </table>
        <a class="back-link" href="index.php">← Back to Tables</a>
    </div>

    <!-- Right: Payment -->
    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="amount-display">
                <div class="amount-label">Amount to Collect</div>
                <div class="amount-value">₹<?php echo number_format($order['total_amount'], 2); ?></div>
            </div>

            <h2>💳 Select Payment Method</h2>
            <div class="method-list">
                <?php foreach ($methods as $m): ?>
                <?php
                    $icon = '💵'; $desc = 'Collect cash from customer';
                    if ($m['method_name'] === 'Digital') { $icon = '💳'; $desc = 'Card or bank transfer'; }
                    if ($m['method_name'] === 'UPI') { $icon = '📱'; $desc = 'Scan QR to pay via UPI'; }
                ?>
                <div class="method-btn" id="method-<?php echo $m['id']; ?>"
                     onclick="selectMethod('<?php echo htmlspecialchars($m['method_name']); ?>', <?php echo $m['id']; ?>)">
                    <div class="method-icon"><?php echo $icon; ?></div>
                    <div class="method-info">
                        <div class="method-name"><?php echo htmlspecialchars($m['method_name']); ?></div>
                        <div class="method-desc"><?php echo $desc; ?></div>
                    </div>
                    <div class="method-check" id="check-<?php echo $m['id']; ?>">✓</div>
                </div>
                <?php endforeach; ?>
            </div>

            <button class="pay-btn" id="payBtn" disabled onclick="processPayment()">
                Select a Payment Method
            </button>
        </div>
    </div>
</div>

<!-- UPI QR Modal -->
<div class="qr-overlay" id="qrOverlay">
    <div class="qr-box">
        <div class="qr-title">📱 UPI Payment</div>
        <div class="qr-sub">Scan the QR code to pay</div>
        <div class="qr-img">📲</div>
        <div class="qr-upi"><?php echo htmlspecialchars($upi_id); ?></div>
        <div class="qr-amount">₹<?php echo number_format($order['total_amount'], 2); ?></div>
        <div class="qr-btns">
            <button class="btn btn-cancel" onclick="closeQR()">✕ Cancel</button>
            <form action="process_payment.php" method="POST" style="display:contents;">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <input type="hidden" name="method" value="UPI">
                <button type="submit" class="btn btn-confirm">✅ Confirmed</button>
            </form>
        </div>
    </div>
</div>

<!-- Hidden form for Cash/Digital payment -->
<form action="process_payment.php" method="POST" id="payForm">
    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
    <input type="hidden" name="method" id="payMethodInput" value="">
</form>

<script>
let selectedMethod = '';
function selectMethod(name, id) {
    document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById('method-' + id).classList.add('selected');
    selectedMethod = name;
    const btn = document.getElementById('payBtn');
    btn.disabled = false;
    btn.textContent = 'Pay with ' + name + ' →';
}
function processPayment() {
    if (!selectedMethod) return;
    if (selectedMethod === 'UPI') {
        document.getElementById('qrOverlay').classList.add('show');
    } else {
        document.getElementById('payMethodInput').value = selectedMethod;
        document.getElementById('payForm').submit();
    }
}
function closeQR() {
    document.getElementById('qrOverlay').classList.remove('show');
}
</script>
</body>
</html>
