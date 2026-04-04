<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['customer_session_id'])) {
    header('Location: scan.php');
    exit;
}

$order_id = safe_int($_GET['order_id'] ?? 0);
if (!$order_id) {
    header('Location: menu.php');
    exit;
}

// Fetch order details
$stmt = mysqli_prepare($conn, '
    SELECT o.*, rt.table_number, cs.customer_name, cs.mobile 
    FROM orders o 
    JOIN restaurant_tables rt ON o.table_id = rt.id 
    JOIN customer_sessions cs ON o.session_id = cs.id 
    WHERE o.id = ? AND o.session_id = ?
');
mysqli_stmt_bind_param($stmt, 'is', $order_id, $_SESSION['customer_session_id']);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    $_SESSION['error'] = 'Order not found';
    header('Location: menu.php');
    exit;
}

// Fetch payment methods
$methods = mysqli_query($conn, 'SELECT * FROM payment_methods WHERE is_active = 1');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - POS Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3 class="mb-0 fw-bold"><i class="bi bi-credit-card"></i> Payment</h3>
                        <p class="mb-0 opacity-75">Order #<?php echo htmlspecialchars($order['order_number']); ?></p>
                    </div>
                    <div class="card-body p-5">
                        <!-- Order Summary -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6><i class="bi bi-table"></i> Table <?php echo htmlspecialchars($order['table_number']); ?></h6>
                            </div>
                            <div class="col-md-6 text-end">
                                <h5 class="fw-bold text-primary">₹<?php echo number_format($order['total_amount'], 2); ?></h5>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="row g-3 mb-4">
                            <?php while ($method = mysqli_fetch_assoc($methods)): ?>
                            <div class="col-md-6">
                                <button class="btn btn-outline-primary w-100 p-4 rounded-3 border-2 h-100 position-relative payment-method" 
                                        data-method="<?php echo $method['id']; ?>" data-upi="<?php echo htmlspecialchars($method['upi_id'] ?? ''); ?>">
                                    <?php if ($method['name'] === 'Cash'): ?>
                                        <i class="bi bi-cash-stack fs-1 mb-2 d-block text-success"></i>
                                    <?php elseif ($method['name'] === 'UPI'): ?>
                                        <i class="bi bi-phone fs-1 mb-2 d-block text-primary"></i>
                                    <?php else: ?>
                                        <i class="bi bi-card-heading fs-1 mb-2 d-block text-info"></i>
                                    <?php endif; ?>
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($method['name']); ?></h6>
                                    <?php if ($method['upi_id']): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($method['upi_id']); ?></small>
                                    <?php endif; ?>
                                </button>
                            </div>
                            <?php endwhile; mysqli_data_seek($methods, 0); ?>
                        </div>

                        <!-- UPI QR Modal -->
                        <div class="modal fade" id="upiModal" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Pay via UPI</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-center p-5">
                                        <div class="mb-4">
                                            <img src="../assets/images/upi-qr.jpg" alt="UPI QR" class="img-fluid rounded shadow" style="max-width: 250px;">
                                        </div>
                                        <div class="mb-4">
                                            <h6 class="fw-bold">Scan & Pay ₹<?php echo number_format($order['total_amount'], 2); ?></h6>
                                            <p class="text-muted mb-0">ID: <strong>poscafe@paytm</strong></p>
                                        </div>
                                        <button type="button" class="btn btn-success px-5 py-2" onclick="confirmPayment(0)">
                                            <i class="bi bi-check-lg"></i> I Have Paid
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-success btn-lg py-3" id="payBtn" onclick="processPayment()">
                                <i class="bi bi-lock-fill"></i> Complete Payment
                            </button>
                            <a href="menu.php" class="btn btn-outline-secondary">← Back to Menu</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedMethod = null;
        
        document.querySelectorAll('.payment-method').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.payment-method').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                selectedMethod = this.dataset.method;
                
                if (this.dataset.upi) {
                    new bootstrap.Modal(document.getElementById('upiModal')).show();
                }
            });
        });
        
        function processPayment() {
            if (!selectedMethod) {
                alert('Please select a payment method');
                return;
            }
            
            // AJAX payment processing
            fetch('process_payment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `order_id=<?php echo $order_id; ?>&method_id=${selectedMethod}`
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    window.location.href = 'order_success.php?order_id=<?php echo $order_id; ?>';
                } else {
                    alert('Payment failed: ' + data.message);
                }
            });
        }
        
        function confirmPayment(methodId) {
            selectedMethod = methodId;
            bootstrap.Modal.getInstance(document.getElementById('upiModal')).hide();
            processPayment();
        }
    </script>
</body>
</html>

<?php mysqli_stmt_close($stmt); ?>

