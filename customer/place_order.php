<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['customer_session_id'])) {
    header('Location: scan.php');
    exit;
}

$session_id = $_SESSION['customer_session_id'];
$table_id = $_SESSION['table_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_data = json_decode($_POST['cart_data'], true);
    if (!$cart_data || empty($cart_data)) {
        $_SESSION['error'] = 'Cart is empty';
        header('Location: menu.php');
        exit;
    }
    
    $total_amount = 0;
    foreach ($cart_data as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }
    
    // Generate order number
    $order_number = 'CUST' . strtoupper(bin2hex(random_bytes(4))) . date('His');
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Create order
        $stmt = mysqli_prepare($conn, 'INSERT INTO orders (order_number, session_id, table_id, total_amount, status) VALUES (?, ?, ?, ?, "pending")');
        mysqli_stmt_bind_param($stmt, 'ssid', $order_number, $session_id, $table_id, $total_amount);
        mysqli_stmt_execute($stmt);
        $order_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        // Add order items
        foreach ($cart_data as $item) {
            $stmt2 = mysqli_prepare($conn, 'INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)');
            $subtotal = $item['price'] * $item['quantity'];
            mysqli_stmt_bind_param($stmt2, 'iiidi', $order_id, $item['id'], $item['quantity'], $item['price'], $subtotal);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
        }
        
        mysqli_commit($conn);
        
        // Clear cart
        unset($_SESSION['cart']);
        // Redirect to payment
        header("Location: payment.php?order_id=$order_id");
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = 'Order failed: ' . $e->getMessage();
        header('Location: menu.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - POS Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5 text-center">
                        <i class="bi bi-check-circle-fill display-1 text-success mb-4"></i>
                        <h2 class="fw-bold mb-3">Order Placed Successfully!</h2>
                        <p class="lead text-muted mb-4">Your order has been sent to the kitchen. We'll notify you when it's ready.</p>
                        
                        <div class="alert alert-info">
                            <h6><i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['customer_name']); ?></h6>
                            <small><?php echo htmlspecialchars($_SESSION['mobile']); ?></small>
                        </div>
                        
                        <a href="payment.php" class="btn btn-success btn-lg w-100 py-3 mb-3">
                            <i class="bi bi-credit-card"></i> Proceed to Payment
                        </a>
                        
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="menu.php" class="btn btn-outline-primary">Add More Items</a>
                            <a href="order_history.php" class="btn btn-outline-secondary">View Orders</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
