<?php
include("../config/db.php");
include("layout/header.php");

// ── Filters ──────────────────────────────────────────────
$period = $_GET['period'] ?? 'today';
$session_uid = intval($_GET['session_uid'] ?? 0);
$resp_id = intval($_GET['resp_id'] ?? 0);
$prod_id = intval($_GET['prod_id'] ?? 0);
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Pagination
$allowed_limits = [10, 20, 50, 80, 100];
$per_page = intval($_GET['per_page'] ?? 5);
if (!in_array($per_page, $allowed_limits)) {
    $per_page = 5;
}
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Sorting
$allowed_sort_columns = [
    'id' => 'o.id',
    'order_number' => 'o.order_number',
    'table_number' => 'rt.table_number',
    'staff_name' => 'u.name',
    'total_amount' => 'o.total_amount',
    'created_at' => 'o.created_at'
];

$sort_by = $_GET['sort_by'] ?? 'id';
$sort_order = strtoupper($_GET['sort_order'] ?? 'ASC');

if (!array_key_exists($sort_by, $allowed_sort_columns)) {
    $sort_by = 'id';
}
if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'ASC';
}

$order_by_sql = $allowed_sort_columns[$sort_by] . " " . $sort_order;

// Build date range
$today = date('Y-m-d');
switch ($period) {
    case 'today':
        $from = $today;
        $to = $today;
        break;
    case 'week':
        $from = date('Y-m-d', strtotime('monday this week'));
        $to = $today;
        break;
    case 'month':
        $from = date('Y-m-01');
        $to = $today;
        break;
    case 'custom':
        $from = $date_from ?: $today;
        $to = $date_to ?: $today;
        break;
    default:
        $from = $today;
        $to = $today;
}

// ── Summary stats ─────────────────────────────────────────
$where_parts = ["o.status = 'paid'", "DATE(o.created_at) BETWEEN '$from' AND '$to'"];
if ($resp_id > 0) $where_parts[] = "o.user_id = $resp_id";
$where = implode(' AND ', $where_parts);

$total_sales = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as cnt, COALESCE(SUM(o.total_amount),0) as total
    FROM orders o
    WHERE $where
"));

// Total order count for pagination
$total_orders_result = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total_rows
    FROM orders o
    WHERE $where
"));
$total_rows = intval($total_orders_result['total_rows'] ?? 0);
$total_pages = max(1, ceil($total_rows / $per_page));

// Sales by product filter
$item_where = $where;
if ($prod_id > 0) $item_where .= " AND oi.product_id = $prod_id";

// Order list with pagination + sorting
$orders_q = mysqli_query($conn, "
    SELECT o.*, u.name as staff_name, rt.table_number
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN restaurant_tables rt ON o.table_id = rt.id
    WHERE $where
    ORDER BY $order_by_sql
    LIMIT $per_page OFFSET $offset
");

// Payment method breakdown
$pay_breakdown = mysqli_query($conn, "
    SELECT p.payment_method, COUNT(*) as cnt, SUM(p.amount) as total
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE $where
    GROUP BY p.payment_method
");

// Top products
$top_products = mysqli_query($conn, "
    SELECT oi.product_name, SUM(oi.quantity) as qty, SUM(oi.subtotal) as revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE $where
    GROUP BY oi.product_name
    ORDER BY revenue DESC
    LIMIT 8
");

// For filter dropdowns
$all_staff = mysqli_query($conn, "SELECT id, name FROM users WHERE status='active' ORDER BY name ASC");
$all_products = mysqli_query($conn, "SELECT id, name FROM products ORDER BY name ASC");

// Helper for pagination links
function buildPageUrl($pageNum) {
    $params = $_GET;
    $params['page'] = $pageNum;
    return 'reports.php?' . http_build_query($params);
}

// Helper for sortable headers
function sortLink($column) {
    $params = $_GET;
    $current_sort_by = $_GET['sort_by'] ?? 'id';
    $current_sort_order = strtoupper($_GET['sort_order'] ?? 'ASC');

    if ($current_sort_by === $column) {
        $params['sort_order'] = ($current_sort_order === 'ASC') ? 'DESC' : 'ASC';
    } else {
        $params['sort_order'] = 'ASC';
    }

    $params['sort_by'] = $column;
    $params['page'] = 1;

    return 'reports.php?' . http_build_query($params);
}

function sortIcon($column) {
    $current_sort_by = $_GET['sort_by'] ?? 'id';
    $current_sort_order = strtoupper($_GET['sort_order'] ?? 'ASC');

    if ($current_sort_by !== $column) return '⇅';
    return $current_sort_order === 'ASC' ? '↑' : '↓';
}
?>

<style>
.pagination-wrap{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-top:18px;
}
.pagination-left{
    font-size:13px;
    color:#64748b;
    font-weight:600;
}
.pagination-right{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
}
.page-btn{
    min-width:40px;
    height:40px;
    border:none;
    border-radius:10px;
    background:#f1f5f9;
    color:#0f172a;
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    transition:0.2s ease;
    padding:0 14px;
}
.page-btn:hover{
    background:#e2e8f0;
}
.page-btn.active{
    background:#FF6B35;
    color:white;
}
.page-btn.disabled{
    opacity:0.4;
    pointer-events:none;
}
.rows-select{
    padding:9px 12px;
    border:1px solid #E2E8F0;
    border-radius:10px;
    font-family:'Sora',sans-serif;
    font-size:14px;
    background:white;
}
.sort-link{
    color:inherit;
    text-decoration:none;
    font-weight:700;
    display:inline-flex;
    align-items:center;
    gap:6px;
}
.sort-link:hover{
    color:#FF6B35;
}
.table-wrap table th{
    white-space:nowrap;
}
</style>

<!-- Filters Panel -->
<div class="panel">
    <h3>📊 Sales Reports</h3>

    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">PERIOD</label>
            <select name="period" onchange="this.form.submit()" style="padding:9px 12px;border:1px solid #E2E8F0;border-radius:10px;font-family:'Sora',sans-serif;font-size:14px;">
                <option value="today" <?php if($period==='today') echo 'selected'; ?>>Today</option>
                <option value="week" <?php if($period==='week') echo 'selected'; ?>>This Week</option>
                <option value="month" <?php if($period==='month') echo 'selected'; ?>>This Month</option>
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

        <div>
            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">ROWS</label>
            <select name="per_page" onchange="this.form.submit()" class="rows-select">
                <?php foreach ($allowed_limits as $limit): ?>
                    <option value="<?php echo $limit; ?>" <?php if($per_page == $limit) echo 'selected'; ?>>
                        <?php echo $limit; ?> rows
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn-primary" style="height:40px;">🔍 Filter</button>
        <a href="reports.php" class="btn-primary" style="background:#94a3b8;height:40px;display:inline-flex;align-items:center;text-decoration:none;padding:0 16px;border-radius:10px;font-size:14px;font-weight:700;color:white;">Reset</a>
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

    <div class="panel" style="margin:0;">
        <h3>🔥 Top Products</h3>
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
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <h3>📋 Order Details</h3>
        <span style="font-size:13px;color:#64748b;">
            <?php echo $from; ?> to <?php echo $to; ?>
        </span>
    </div>

    <div class="table-wrap">
        <table>
            <tr>
                <th><a class="sort-link" href="<?php echo sortLink('id'); ?>"># <?php echo sortIcon('id'); ?></a></th>
                <th><a class="sort-link" href="<?php echo sortLink('order_number'); ?>">Order No. <?php echo sortIcon('order_number'); ?></a></th>
                <th><a class="sort-link" href="<?php echo sortLink('table_number'); ?>">Table <?php echo sortIcon('table_number'); ?></a></th>
                <th><a class="sort-link" href="<?php echo sortLink('staff_name'); ?>">Staff <?php echo sortIcon('staff_name'); ?></a></th>
                <th><a class="sort-link" href="<?php echo sortLink('total_amount'); ?>">Amount <?php echo sortIcon('total_amount'); ?></a></th>
                <th><a class="sort-link" href="<?php echo sortLink('created_at'); ?>">Time <?php echo sortIcon('created_at'); ?></a></th>
            </tr>
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
                <tr>
                    <td colspan="6" style="text-align:center;color:#94a3b8;padding:32px;">
                        No paid orders found for this period. Complete some orders to see data here.
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="pagination-wrap">
        <div class="pagination-left">
            <?php
            $start_row = $total_rows > 0 ? $offset + 1 : 0;
            $end_row = min($offset + $per_page, $total_rows);
            ?>
            Showing <strong><?php echo $start_row; ?></strong> to <strong><?php echo $end_row; ?></strong> of <strong><?php echo $total_rows; ?></strong> orders
        </div>

        <div class="pagination-right">
            <a class="page-btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>" href="<?php echo buildPageUrl(max(1, $page - 1)); ?>">← Prev</a>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            if ($start_page > 1) {
                echo '<a class="page-btn" href="'.buildPageUrl(1).'">1</a>';
                if ($start_page > 2) echo '<span style="padding:0 6px;color:#64748b;">...</span>';
            }

            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <a class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>" href="<?php echo buildPageUrl($i); ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor;

            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) echo '<span style="padding:0 6px;color:#64748b;">...</span>';
                echo '<a class="page-btn" href="'.buildPageUrl($total_pages).'">'.$total_pages.'</a>';
            }
            ?>

            <a class="page-btn <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>" href="<?php echo buildPageUrl(min($total_pages, $page + 1)); ?>">Next →</a>
        </div>
    </div>
</div>

<?php include("layout/footer.php"); ?>