<?php
session_start();
include('../config/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Dashboard stats
$stats = [];
$stats['total_tables'] = mysqli_fetch_row(mysqli_query($conn, 'SELECT COUNT(*) FROM restaurant_tables WHERE active="yes"'))[0];
$stats['occupied_tables'] = mysqli_fetch_row(mysqli_query($conn, 'SELECT COUNT(*) FROM restaurant_tables WHERE status="occupied"'))[0];
$stats['today_sales'] = mysqli_fetch_row(mysqli_query($conn, 'SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status="paid" AND DATE(created_at)=CURDATE()'))[0];
$stats['total_orders'] = mysqli_fetch_row(mysqli_query($conn, 'SELECT COUNT(*) FROM orders'))[0];
$stats['active_sessions'] = mysqli_fetch_row(mysqli_query($conn, 'SELECT COUNT(*) FROM customer_sessions WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)'))[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - POS Cafe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    
    <div class="container-fluid px-4 py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-5 fw-bold mb-1">Dashboard</h1>
                <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-table fs-4 text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Tables</h6>
                                <h3 class="mb-0 fw-bold"><?php echo $stats['total_tables']; ?></h3>
                                <small class="text-success">
                                    <i class="bi bi-arrow-up"></i> <?php echo $stats['occupied_tables']; ?> occupied
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-success bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-currency-rupee fs-4 text-success"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Today's Sales</h6>
                                <h3 class="mb-0 fw-bold">₹<?php echo number_format($stats['today_sales'], 2); ?></h3>
                                <small class="text-success"><i class="bi bi-trending-up"></i> Live</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-receipt fs-4 text-warning"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Orders</h6>
                                <h3 class="mb-0 fw-bold"><?php echo number_format($stats['total_orders']); ?></h3>
                                <small class="text-muted"><?php echo $stats['active_sessions']; ?> active customers</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-info bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-people fs-4 text-info"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Quick Actions</h6>
                                <div class="btn-group-vertical w-100" role="group">
                                    <a href="tables.php" class="btn btn-outline-primary btn-sm">Manage Tables</a>
                                    <a href="products.php" class="btn btn-outline-primary btn-sm">Products</a>
                                    <a href="orders.php" class="btn btn-outline-primary btn-sm">View Orders</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white border-0 pb-0">
                        <h5 class="card-title mb-0">Recent Orders</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Table</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent = mysqli_query($conn, '
                                    SELECT o.*, rt.table_number, cs.customer_name 
                                    FROM orders o 
                                    JOIN restaurant_tables rt ON o.table_id = rt.id 
                                    LEFT JOIN customer_sessions cs ON o.session_id = cs.id 
                                    ORDER BY o.created_at DESC LIMIT 10
                                ');
                                while ($order = mysqli_fetch_assoc($recent)):
                                ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['table_number']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Staff'); ?></td>
                                    <td><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['status'] == 'paid' ? 'success' : 
                                            ($order['status'] == 'pending' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('H:i', strtotime($order['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white border-0 pt-0">
                        <a href="orders.php" class="btn btn-primary">View All Orders <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'layout/footer.php'; ?>
</body>
</html>
