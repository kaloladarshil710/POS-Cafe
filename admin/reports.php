<?php
include("../config/db.php");
include("layout/header.php");

// ── Filters ──────────────────────────────────────────────
$period      = $_GET['period']      ?? 'today';
$session_uid = intval($_GET['session_uid'] ?? 0);
$resp_id     = intval($_GET['resp_id']     ?? 0);
$prod_id     = intval($_GET['prod_id']     ?? 0);
$date_from   = $_GET['date_from']   ?? '';
$date_to     = $_GET['date_to']     ?? '';

// Build date range
$today = date('Y-m-d');
switch ($period) {
    case 'today':   $from = $today; $to = $today; break;
    case 'week':    $from = date('Y-m-d', strtotime('monday this week')); $to = $today; break;
    case 'month':   $from = date('Y-m-01'); $to = $today; break;
    case 'custom':
        $from = $date_from ?: $today;
        $to   = $date_to   ?: $today;
        break;
    default:        $from = $today; $to = $today;
}

// ── Summary stats ─────────────────────────────────────────
$where_parts = ["o.status = 'paid'", "DATE(o.created_at) BETWEEN '$from' AND '$to'"];
if ($resp_id > 0) $where_parts[] = "o.user_id = $resp_id";
$where = implode(' AND ', $where_parts);

$total_sales = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt, COALESCE(SUM(o.total_amount),0) as total
     FROM orders o WHERE $where"));;

// Sales by product filter
$item_where = $where;
if ($prod_id > 0) $item_where .= " AND oi.product_id = $prod_id";

$orders_q = mysqli_query($conn,
    "SELECT o.*, u.name as staff_name, rt.table_number
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.id
     LEFT JOIN restaurant_tables rt ON o.table_id = rt.id
     WHERE $where
     ORDER BY o.created_at DESC
     LIMIT 100");

// Payment method breakdown
$pay_breakdown = mysqli_query($conn,
    "SELECT p.payment_method, COUNT(*) as cnt, SUM(p.amount) as total
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     WHERE $where
     GROUP BY p.payment_method");

// Top products
$top_products = mysqli_query($conn,
    "SELECT oi.product_name, SUM(oi.quantity) as qty, SUM(oi.subtotal) as revenue
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     WHERE $where
     GROUP BY oi.product_name
     ORDER BY revenue DESC
     LIMIT 8");

// For filter dropdowns
$all_staff    = mysqli_query($conn, "SELECT id, name FROM users WHERE status='active' ORDER BY name ASC");
$all_products = mysqli_query($conn, "SELECT id, name FROM products ORDER BY name ASC");
?>

<!-- Filters Panel -->
<div class="panel">
    <h3>📊 Sales Reports</h3>
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">PERIOD</label>
            <select name="period" onchange="this.form.submit()" style="padding:9px 12px;border:1px solid #E2E8F0;border-radius:10px;font-family:'Sora',sans-serif;font-size:14px;">
                <option value="today"  <?php if($period==='today')  echo 'selected'; ?>>Today</option>
                <option value="week"   <?php if($period==='week')   echo 'selected'; ?>>This Week</option>
                <option value="month"  <?php if($period==='month')  echo 'selected'; ?>>This Month</option>
                <option value="custom" <?php if($period==='custom') echo 'selected'; ?>>Custom Range</option>
            </select>
        </div>
        <?php if ($period === 'custom'): ?>
        <div>
            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">FROM</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($from); ?>"
                   style="padding:9px 12px;border:1px solid #E2E8F0;border-radius:10px;font-family:'Sora',sans-serif;font-size:14px;">
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">TO</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($to); ?>"
                   style="padding:9px 12px;border:1px solid #E2E8F0;border-radius:10px;font-family:'Sora',sans-serif;font-size:14px;">
        </div>
        <?php endif; ?>
        <div>
            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">RESPONSIBLE</label>
            <select name="resp_id" onchange="this.form.submit()" style="padding:9px 12px;border:1px solid #E2E8F0;border-radius:10px;font-family:'Sora',sans-serif;font-size:14px;">
                <option value="0">All Staff</option>
                <?php while ($s = mysqli_fetch_assoc($all_staff)): ?>
                <option value="<?php echo $s['id']; ?>" <?php if($resp_id==$s['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($s['name']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">PRODUCT</label>
            <select name="prod_id" onchange="this.form.submit()" style="padding:9px 12px;border:1px solid #E2E8F0;border-radius:10px;font-family:'Sora',sans-serif;font-size:14px;">
                <option value="0">All Products</option>
                <?php while ($p = mysqli_fetch_assoc($all_products)): ?>
                <option value="<?php echo $p['id']; ?>" <?php if($prod_id==$p['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($p['name']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary" style="height:40px;">🔍 Filter</button>
        <a href="reports.php" class="btn-primary" style="background:#94a3b8;height:40px;display:inline-flex;align-items:center;text-decoration:none;padding:0 16px;border-radius:10px;font-size:14px;font-weight:700;color:white;">✕ Reset</a>
    </form>
</div>

<!-- Summary Cards -->
<?php
$total_cnt = $total_sales['cnt'];
$total_rev = floatval($total_sales['total']);
$avg_order = $total_cnt > 0 ? $total_rev / $total_cnt : 0;
?>
<div class="card-grid" style="margin-bottom:20px;">
    <div class="card">
        <h3>Total Orders</h3>
        <p><?php echo $total_cnt; ?></p>
    </div>
    <div class="card">
        <h3>Total Revenue</h3>
        <p>₹<?php echo number_format($total_rev, 2); ?></p>
    </div>
    <div class="card">
        <h3>Avg Order Value</h3>
        <p>₹<?php echo number_format($avg_order, 2); ?></p>
    </div>
    <div class="card">
        <h3>Period</h3>
        <p style="font-size:16px;"><?php echo date('d M', strtotime($from)); ?> – <?php echo date('d M', strtotime($to)); ?></p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <!-- Payment Method Breakdown -->
    <div class="panel" style="margin:0;">
        <h3>💳 Payment Methods</h3>
        <div class="table-wrap">
            <table>
                <tr><th>Method</th><th>Orders</th><th>Revenue</th></tr>
                <?php
                $has_pay = false;
                while ($pr = mysqli_fetch_assoc($pay_breakdown)):
                $has_pay = true;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($pr['payment_method']); ?></td>
                    <td><?php echo $pr['cnt']; ?></td>
                    <td><strong>₹<?php echo number_format($pr['total'], 2); ?></strong></td>
                </tr>
                <?php endwhile;
                if (!$has_pay): ?>
                <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:20px;">No paid orders in this period.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Top Products -->
    <div class="panel" style="margin:0;">
        <h3>🍔 Top Products</h3>
        <div class="table-wrap">
            <table>
                <tr><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr>
                <?php
                $has_prod = false;
                while ($tp = mysqli_fetch_assoc($top_products)):
                $has_prod = true;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($tp['product_name']); ?></td>
                    <td><?php echo $tp['qty']; ?></td>
                    <td><strong>₹<?php echo number_format($tp['revenue'], 2); ?></strong></td>
                </tr>
                <?php endwhile;
                if (!$has_prod): ?>
                <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:20px;">No data yet.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- Order Details Table -->
<div class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3>📋 Order Details</h3>
        <span style="font-size:13px;color:#64748b;"><?php echo $from; ?> to <?php echo $to; ?></span>
    </div>
    <div class="table-wrap">
        <table>
            <tr><th>#</th><th>Order No.</th><th>Table</th><th>Staff</th><th>Amount</th><th>Time</th></tr>
            <?php
            $has_orders = false;
            while ($ord = mysqli_fetch_assoc($orders_q)):
            $has_orders = true;
            ?>
            <tr>
                <td><?php echo $ord['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($ord['order_number']); ?></strong></td>
                <td><?php echo htmlspecialchars($ord['table_number']); ?></td>
                <td><?php echo htmlspecialchars($ord['staff_name'] ?? '—'); ?></td>
                <td><strong style="color:#FF6B35;">₹<?php echo number_format($ord['total_amount'], 2); ?></strong></td>
                <td><?php echo date('d M, h:i A', strtotime($ord['created_at'])); ?></td>
            </tr>
            <?php endwhile;
            if (!$has_orders): ?>
            <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:32px;">No paid orders found for this period. Complete some orders to see data here.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php include("layout/footer.php"); ?>
