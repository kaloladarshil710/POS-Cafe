<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Get customer info
$cust = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM customers WHERE id=$customer_id"));

// Get customer's orders (if they have email matching users table)
$orders = mysqli_query($conn, "
    SELECT o.*, rt.table_number,
           COUNT(oi.id) as item_count,
           SUM(oi.quantity) as total_items
    FROM orders o
    JOIN restaurant_tables rt ON o.table_id = rt.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = (SELECT id FROM users WHERE email=? LIMIT 1)
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 20
", [$_SESSION['customer_email']]);

// Handle NULL result if no user found
if (!$orders) {
    $orders = mysqli_query($conn, 
        "SELECT NULL as id LIMIT 0"); // Empty result set
}

// Get payment stats
$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        COUNT(DISTINCT CASE WHEN o.status='paid' THEN o.id END) as paid_orders,
        COALESCE(SUM(CASE WHEN o.status='paid' THEN o.total_amount END), 0) as total_spent,
        COUNT(DISTINCT CASE WHEN o.status!='paid' THEN o.id END) as pending_orders
    FROM orders o
    WHERE o.user_id = (SELECT id FROM users WHERE email=? LIMIT 1)
", [$_SESSION['customer_email']])) ?? 
    ['total_orders' => 0, 'paid_orders' => 0, 'total_spent' => 0, 'pending_orders' => 0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard — POS Cafe Customer</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--primary:#F97316;--bg:#0A0A0F;--surface:#12121A;--surface2:#1A1A26;--border:rgba(255,255,255,0.07);--text:#F1F1F5;--text2:#9999B3;--text3:#555570;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}

.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.logo{font-size:18px;font-weight:800;}.logo span{color:var(--primary);}
.user-chip{display:flex;align-items:center;gap:8px;padding:6px 12px;background:var(--surface2);border:1px solid var(--border);border-radius:999px;font-size:13px;font-weight:600;}
.avatar{width:26px;height:26px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:white;}
.logout-btn{color:var(--text2);text-decoration:none;font-size:13px;padding:8px 14px;background:var(--surface2);border:1px solid var(--border);border-radius:8px;cursor:pointer;transition:0.15s;}
.logout-btn:hover{background:var(--border);}

.main{padding:28px;}
.header{margin-bottom:28px;}
h1{font-size:28px;font-weight:800;margin-bottom:8px;}
p.sub{color:var(--text2);font-size:14px;}

.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:28px;}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px;}
.stat-card h3{font-size:12px;color:var(--text3);text-transform:uppercase;margin-bottom:8px;font-weight:700;letter-spacing:0.5px;}
.stat-card .value{font-size:28px;font-weight:800;color:var(--primary);margin-bottom:4px;}
.stat-card .detail{font-size:12px;color:var(--text2);}

.panel{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px;margin-bottom:24px;}
.panel h2{font-size:18px;font-weight:800;margin-bottom:16px;}

.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
table thead{background:var(--surface2);}
table th{padding:12px;text-align:left;font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid var(--border);}
table td{padding:14px 12px;border-bottom:1px solid var(--border);font-size:13px;}
table tbody tr:hover{background:rgba(249,115,22,0.03);}

.status{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;display:inline-block;}
.status.pending{background:#F59E0B;color:white;}
.status.paid{background:#10B981;color:white;}
.status.to_cook{background:#3B82F6;color:white;}
.status.preparing{background:#F97316;color:white;}

.order-link{color:var(--primary);text-decoration:none;font-weight:700;cursor:pointer;}
.order-link:hover{text-decoration:underline;}

.empty{text-align:center;color:var(--text2);padding:40px 20px;}
</style>
</head>
<body>

<div class="topbar">
    <div class="logo">POS <span>Cafe</span></div>
    <div style="display:flex;align-items:center;gap:16px;">
        <div class="user-chip">
            <div class="avatar"><?php echo strtoupper(substr($cust['name'], 0, 1)); ?></div>
            <div>
                <div><?php echo h($cust['name']); ?></div>
                <div style="font-size:11px;color:var(--text3);"><?php echo h($cust['email']); ?></div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1>Welcome back, <?php echo h(explode(' ', $cust['name'])[0]); ?>! 👋</h1>
        <p class="sub">Manage your orders and account</p>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Orders</h3>
            <div class="value"><?php echo $stats['total_orders']; ?></div>
            <div class="detail"><?php echo $stats['paid_orders']; ?> completed</div>
        </div>
        <div class="stat-card">
            <h3>Total Spent</h3>
            <div class="value" style="color:#10B981;">₹<?php echo number_format($stats['total_spent'], 0); ?></div>
            <div class="detail">across all orders</div>
        </div>
        <div class="stat-card">
            <h3>Pending Orders</h3>
            <div class="value" style="color:#F59E0B;"><?php echo $stats['pending_orders']; ?></div>
            <div class="detail">awaiting payment</div>
        </div>
        <div class="stat-card">
            <h3>Member Since</h3>
            <div class="value" style="font-size:16px;"><?php echo date('d M Y', strtotime($cust['created_at'])); ?></div>
            <div class="detail">registered on this date</div>
        </div>
    </div>

    <!-- ORDERS -->
    <div class="panel">
        <h2>📋 Your Orders</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Table</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($orders) === 0): ?>
                    <tr><td colspan="7" class="empty">No orders yet. <a href="../pos/index.php" style="color:var(--primary);">Place an order</a></td></tr>
                    <?php else: while($order = mysqli_fetch_assoc($orders)): ?>
                    <tr>
                        <td><strong><?php echo h($order['order_number']); ?></strong></td>
                        <td>Table <?php echo h($order['table_number']); ?></td>
                        <td><?php echo $order['total_items']??0; ?> items</td>
                        <td><strong style="color:var(--primary);">₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                        <td>
                            <span class="status <?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td style="font-size:12px;color:var(--text3);"><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                        <td>
                            <a href="order_details.php?order_id=<?php echo $order['id']; ?>" class="order-link">View →</a>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ACCOUNT INFO -->
    <div class="panel">
        <h2>👤 Account Information</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <div>
                <h3 style="font-size:12px;color:var(--text2);text-transform:uppercase;margin-bottom:8px;font-weight:700;">Name</h3>
                <p style="font-size:15px;"><?php echo h($cust['name']); ?></p>
            </div>
            <div>
                <h3 style="font-size:12px;color:var(--text2);text-transform:uppercase;margin-bottom:8px;font-weight:700;">Phone</h3>
                <p style="font-size:15px;"><?php echo h($cust['phone']); ?></p>
            </div>
            <div>
                <h3 style="font-size:12px;color:var(--text2);text-transform:uppercase;margin-bottom:8px;font-weight:700;">Email</h3>
                <p style="font-size:15px;"><?php echo h($cust['email']); ?></p>
            </div>
            <div>
                <h3 style="font-size:12px;color:var(--text2);text-transform:uppercase;margin-bottom:8px;font-weight:700;">Member Since</h3>
                <p style="font-size:15px;"><?php echo date('d M Y', strtotime($cust['created_at'])); ?></p>
            </div>
        </div>
        <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border);">
            <a href="edit_profile.php" style="color:var(--primary);text-decoration:none;font-weight:700;font-size:13px;">Edit Profile →</a>
        </div>
    </div>
</div>

</body>
</html>