<?php
include("../config/db.php");
include("layout/header.php");

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

// Get date filters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of month
$date_to   = $_GET['date_to'] ?? date('Y-m-d');   // Today
$period    = $_GET['period'] ?? 'monthly';          // daily, weekly, monthly

// Validate dates
$date_from = date('Y-m-d', strtotime($date_from));
$date_to   = date('Y-m-d', strtotime($date_to));

if ($date_from > $date_to) {
    $temp = $date_from;
    $date_from = $date_to;
    $date_to = $temp;
}

// ── REVENUE SUMMARY ────────────────────────────────
$revenue_q = mysqli_prepare($conn, 
    "SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as revenue,
        AVG(total_amount) as avg_order,
        MIN(total_amount) as min_order,
        MAX(total_amount) as max_order
    FROM orders 
    WHERE status='paid' AND DATE(created_at) BETWEEN ? AND ?");
mysqli_stmt_bind_param($revenue_q, "ss", $date_from, $date_to);
mysqli_stmt_execute($revenue_q);
$revenue = mysqli_fetch_assoc(mysqli_stmt_get_result($revenue_q));
mysqli_stmt_close($revenue_q);

// ── DAILY BREAKDOWN ────────────────────────────────
$daily_q = mysqli_prepare($conn,
    "SELECT 
        DATE(created_at) as order_date,
        COUNT(*) as orders,
        SUM(total_amount) as daily_revenue,
        AVG(total_amount) as avg_order
    FROM orders 
    WHERE status='paid' AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY order_date DESC");
mysqli_stmt_bind_param($daily_q, "ss", $date_from, $date_to);
mysqli_stmt_execute($daily_q);
$daily_data = mysqli_stmt_get_result($daily_q);
mysqli_stmt_close($daily_q);

// ── HOURLY DISTRIBUTION ────────────────────────────
$hourly_q = mysqli_prepare($conn,
    "SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as orders,
        SUM(total_amount) as revenue
    FROM orders 
    WHERE status='paid' AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY HOUR(created_at)
    ORDER BY hour");
mysqli_stmt_bind_param($hourly_q, "ss", $date_from, $date_to);
mysqli_stmt_execute($hourly_q);
$hourly_data = mysqli_stmt_get_result($hourly_q);
mysqli_stmt_close($hourly_q);

// ── TOP PRODUCTS ────────────────────────────────────
$products_q = mysqli_prepare($conn,
    "SELECT 
        p.name,
        c.category_name,
        COUNT(oi.id) as qty_sold,
        SUM(oi.quantity * oi.price) as revenue,
        AVG(oi.price) as avg_price
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status='paid' AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY oi.product_id
    ORDER BY revenue DESC
    LIMIT 15");
mysqli_stmt_bind_param($products_q, "ss", $date_from, $date_to);
mysqli_stmt_execute($products_q);
$top_products = mysqli_stmt_get_result($products_q);
mysqli_stmt_close($products_q);

// ── CATEGORY BREAKDOWN ────────────────────────────
$category_q = mysqli_prepare($conn,
    "SELECT 
        c.category_name,
        COUNT(oi.id) as items_sold,
        SUM(oi.quantity * oi.price) as category_revenue,
        ROUND(SUM(oi.quantity * oi.price) / (SELECT SUM(total_amount) FROM orders WHERE status='paid' AND DATE(created_at) BETWEEN ? AND ?) * 100, 2) as percentage
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status='paid' AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY category_revenue DESC");
mysqli_stmt_bind_param($category_q, "ssss", $date_from, $date_to, $date_from, $date_to);
mysqli_stmt_execute($category_q);
$categories_data = mysqli_stmt_get_result($category_q);
mysqli_stmt_close($category_q);

// ── PAYMENT METHOD BREAKDOWN ────────────────────────
$payment_q = mysqli_prepare($conn,
    "SELECT 
        payment_method,
        COUNT(*) as transactions,
        SUM(amount) as total
    FROM payments
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY payment_method
    ORDER BY total DESC");
mysqli_stmt_bind_param($payment_q, "ss", $date_from, $date_to);
mysqli_stmt_execute($payment_q);
$payment_data = mysqli_stmt_get_result($payment_q);
mysqli_stmt_close($payment_q);

// ── STAFF PERFORMANCE ────────────────────────────
$staff_q = mysqli_prepare($conn,
    "SELECT 
        u.name,
        COUNT(o.id) as orders_handled,
        SUM(o.total_amount) as total_sales,
        AVG(o.total_amount) as avg_order_value
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.status='paid' AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY total_sales DESC");
mysqli_stmt_bind_param($staff_q, "ss", $date_from, $date_to);
mysqli_stmt_execute($staff_q);
$staff_data = mysqli_stmt_get_result($staff_q);
mysqli_stmt_close($staff_q);

// ── TABLE UTILIZATION ────────────────────────────
$table_q = mysqli_prepare($conn,
    "SELECT 
        rt.table_number,
        COUNT(o.id) as times_used,
        SUM(o.total_amount) as revenue,
        AVG(TIMESTAMPDIFF(MINUTE, o.created_at, 
            (SELECT MAX(created_at) FROM orders WHERE table_id=rt.id))) as avg_duration
    FROM restaurant_tables rt
    LEFT JOIN orders o ON rt.id = o.table_id AND o.status='paid' AND DATE(o.created_at) BETWEEN ? AND ?
    WHERE rt.active = 'yes'
    GROUP BY rt.id
    ORDER BY revenue DESC");
mysqli_stmt_bind_param($table_q, "ss", $date_from, $date_to);
mysqli_stmt_execute($table_q);
$table_data = mysqli_stmt_get_result($table_q);
mysqli_stmt_close($table_q);
?>

<div class="panel">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
        <h2>📊 Advanced Reports</h2>
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <input type="date" name="date_from" value="<?php echo h($date_from); ?>" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;">
            <input type="date" name="date_to" value="<?php echo h($date_to); ?>" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;">
            <select name="period" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;">
                <option value="daily" <?php echo $period==='daily'?'selected':''; ?>>Daily</option>
                <option value="weekly" <?php echo $period==='weekly'?'selected':''; ?>>Weekly</option>
                <option value="monthly" <?php echo $period==='monthly'?'selected':''; ?>>Monthly</option>
            </select>
            <button class="btn-primary" style="padding:8px 14px;">Apply</button>
        </form>
    </div>
</div>

<!-- SUMMARY CARDS -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;margin-bottom:24px;">
    <div class="card" style="border-left:4px solid #10B981;">
        <h3 style="font-size:11px;color:#64748b;text-transform:uppercase;margin-bottom:8px;">💰 Total Revenue</h3>
        <p style="font-size:28px;font-weight:800;color:#10B981;">₹<?php echo number_format($revenue['revenue']??0, 2); ?></p>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">From <?php echo date('d M', strtotime($date_from)); ?> to <?php echo date('d M', strtotime($date_to)); ?></div>
    </div>
    
    <div class="card" style="border-left:4px solid #F97316;">
        <h3 style="font-size:11px;color:#64748b;text-transform:uppercase;margin-bottom:8px;">📦 Total Orders</h3>
        <p style="font-size:28px;font-weight:800;color:#F97316;"><?php echo $revenue['total_orders']??0; ?></p>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">Average: ₹<?php echo number_format($revenue['avg_order']??0, 2); ?></div>
    </div>
    
    <div class="card" style="border-left:4px solid #3B82F6;">
        <h3 style="font-size:11px;color:#64748b;text-transform:uppercase;margin-bottom:8px;">📈 Max Order</h3>
        <p style="font-size:28px;font-weight:800;color:#3B82F6;">₹<?php echo number_format($revenue['max_order']??0, 2); ?></p>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">Min: ₹<?php echo number_format($revenue['min_order']??0, 2); ?></div>
    </div>
</div>

<!-- TOP PRODUCTS -->
<div class="panel" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">🏆 Top 15 Products</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Qty Sold</th>
                    <th>Revenue</th>
                    <th>Avg Price</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($top_products) === 0): ?>
                <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px;">No products sold in this period.</td></tr>
                <?php else: while ($row = mysqli_fetch_assoc($top_products)): ?>
                <tr>
                    <td><strong><?php echo h($row['name']); ?></strong></td>
                    <td><?php echo h($row['category_name']); ?></td>
                    <td style="text-align:right;"><?php echo $row['qty_sold']; ?></td>
                    <td style="text-align:right;color:#10B981;font-weight:700;">₹<?php echo number_format($row['revenue'], 2); ?></td>
                    <td style="text-align:right;">₹<?php echo number_format($row['avg_price'], 2); ?></td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- DAILY BREAKDOWN -->
<div class="panel" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">📅 Daily Breakdown</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Date</th><th>Orders</th><th>Revenue</th><th>Avg Order</th></tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($daily_data) === 0): ?>
                <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px;">No data available.</td></tr>
                <?php else: while ($row = mysqli_fetch_assoc($daily_data)): ?>
                <tr>
                    <td><strong><?php echo date('d M Y', strtotime($row['order_date'])); ?></strong></td>
                    <td style="text-align:right;"><?php echo $row['orders']; ?></td>
                    <td style="text-align:right;font-weight:700;">₹<?php echo number_format($row['daily_revenue'], 2); ?></td>
                    <td style="text-align:right;">₹<?php echo number_format($row['avg_order'], 2); ?></td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- CATEGORY BREAKDOWN -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
    <div class="panel">
        <h3 style="margin-bottom:16px;">📂 Category Breakdown</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Category</th><th>Items</th><th>Revenue</th><th>%</th></tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($categories_data) === 0): ?>
                    <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px;">No data.</td></tr>
                    <?php else: while ($row = mysqli_fetch_assoc($categories_data)): ?>
                    <tr>
                        <td><strong><?php echo h($row['category_name']); ?></strong></td>
                        <td style="text-align:right;"><?php echo $row['items_sold']; ?></td>
                        <td style="text-align:right;font-weight:700;">₹<?php echo number_format($row['category_revenue'], 2); ?></td>
                        <td style="text-align:right;color:#3B82F6;"><?php echo $row['percentage']; ?>%</td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h3 style="margin-bottom:16px;">💳 Payment Methods</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Method</th><th>Transactions</th><th>Total</th></tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($payment_data) === 0): ?>
                    <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:20px;">No data.</td></tr>
                    <?php else: while ($row = mysqli_fetch_assoc($payment_data)): ?>
                    <tr>
                        <td><strong><?php echo h($row['payment_method']); ?></strong></td>
                        <td style="text-align:right;"><?php echo $row['transactions']; ?></td>
                        <td style="text-align:right;font-weight:700;">₹<?php echo number_format($row['total'], 2); ?></td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- STAFF PERFORMANCE -->
<div class="panel" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">👥 Staff Performance</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Staff Name</th><th>Orders</th><th>Total Sales</th><th>Avg Order Value</th></tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($staff_data) === 0): ?>
                <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px;">No data.</td></tr>
                <?php else: while ($row = mysqli_fetch_assoc($staff_data)): ?>
                <tr>
                    <td><strong><?php echo h($row['name']); ?></strong></td>
                    <td style="text-align:right;"><?php echo $row['orders_handled']; ?></td>
                    <td style="text-align:right;font-weight:700;">₹<?php echo number_format($row['total_sales'], 2); ?></td>
                    <td style="text-align:right;">₹<?php echo number_format($row['avg_order_value'], 2); ?></td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- TABLE UTILIZATION -->
<div class="panel">
    <h3 style="margin-bottom:16px;">🪑 Table Utilization</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Table</th><th>Times Used</th><th>Revenue</th><th>Avg Duration (min)</th></tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($table_data) === 0): ?>
                <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px;">No data.</td></tr>
                <?php else: while ($row = mysqli_fetch_assoc($table_data)): ?>
                <tr>
                    <td><strong>Table <?php echo $row['table_number']; ?></strong></td>
                    <td style="text-align:right;"><?php echo $row['times_used']??0; ?></td>
                    <td style="text-align:right;font-weight:700;">₹<?php echo number_format($row['revenue']??0, 2); ?></td>
                    <td style="text-align:right;"><?php echo $row['avg_duration']??0; ?></td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include("layout/footer.php"); ?>