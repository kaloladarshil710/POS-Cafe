<?php
session_start();
include('../config/db.php');

// Clear any existing session
session_unset();
session_destroy();
session_start();

if (isset($_SESSION['customer_session_id'])) {
    header('Location: menu.php');
    exit;
}

$table_id = safe_int($_GET['table'] ?? 0);
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['customer_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    
    if (empty($name)) {
        $error = 'Customer name is required';
    } elseif (!preg_match('/^\d{10}$/', $mobile)) {
        $error = 'Mobile must be exactly 10 digits';
    } else {
        // Check table exists
        $stmt = mysqli_prepare($conn, 'SELECT table_number FROM restaurant_tables WHERE id = ? AND active = "yes"');
        mysqli_stmt_bind_param($stmt, 'i', $table_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 1) {
            $table = mysqli_fetch_assoc($result);
            
            // Create session
            $session_id = bin2hex(random_bytes(32));
            $_SESSION['customer_session_id'] = $session_id;
            $_SESSION['table_id'] = $table_id;
            $_SESSION['customer_name'] = $name;
            $_SESSION['mobile'] = $mobile;
            
            // Save to DB
            $stmt2 = mysqli_prepare($conn, 'INSERT INTO customer_sessions (id, table_id, customer_name, mobile) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt2, 'siss', $session_id, $table_id, $name, $mobile);
            mysqli_stmt_execute($stmt2);
            
            // Mark table occupied
            $stmt3 = mysqli_prepare($conn, 'UPDATE restaurant_tables SET status = "occupied", occupied_since = NOW() WHERE id = ?');
            mysqli_stmt_bind_param($stmt3, 'i', $table_id);
            mysqli_stmt_execute($stmt3);
            
            mysqli_stmt_close($stmt3);
            mysqli_stmt_close($stmt2);
            mysqli_stmt_close($stmt);
            
            header('Location: menu.php');
            exit;
        } else {
            $error = 'Invalid table number';
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch table info for display
$table_info = null;
if ($table_id) {
    $stmt = mysqli_prepare($conn, 'SELECT table_number, seats FROM restaurant_tables WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $table_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $table_info = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Cafe - Table Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h1 class="display-5 fw-bold text-primary mb-2"><i class="bi bi-qr-code-scan"></i> Quick Order</h1>
                            <p class="lead text-muted">Scan successful!</p>
                            <?php if ($table_info): ?>
                                <div class="alert alert-info">
                                    <h6><i class="bi bi-table"></i> <?php echo htmlspecialchars($table_info['table_number']); ?> 
                                    - <?php echo $table_info['seats']; ?> seats</h6>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Your Name</label>
                                <input type="text" class="form-control form-control-lg" name="customer_name" required 
                                       placeholder="John Doe" maxlength="100">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Mobile Number</label>
                                <input type="tel" class="form-control form-control-lg" name="mobile" required 
                                       pattern="\d{10}" maxlength="10" placeholder="9876543210">
                                <div class="form-text">For order updates (10 digits only)</div>
                            </div>
                            <input type="hidden" name="table" value="<?php echo $table_id; ?>">
                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold">
                                <i class="bi bi-cart-plus"></i> Start Ordering
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="text-muted small">
                                <i class="bi bi-shield-check"></i> 
                                Your details are secure and private
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
