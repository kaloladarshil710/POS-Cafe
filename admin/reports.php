<?php
include("../config/db.php");
include("layout/header.php");

const PER_PAGE_R = 15;

// ── Sanitise filter inputs ─────────────────────────────────
$period   = in_array($_GET['period'] ?? '', ['today','week','month','custom']) ? $_GET['period'] : 'today';
$resp_id  = safe_int($_GET['resp_id']  ?? 0);
$prod_id  = safe_int($_GET['prod_id']  ?? 0);
$meth_f   = trim($_GET['meth_f'] ?? '');

$today = date('Y-m-d');
switch ($period) {
    case 'week':   $from = date('Y-m-d', strtotime('monday this week')); $to = $today; break;
    case 'month':  $from = date('Y-m-01'); $to = $today; break;
    case 'custom':
        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_from'] ?? '') ? $_GET['date_from'] : $today;
        $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_to']   ?? '') ? $_GET['date_to']   : $today;
        if ($to < $from) $to = $from;
        break;
    default: $from = $today; $to = $today;
}

// ── Sort for orders table ──────────────────────────────────
$allowed_r = ['o.id','o.order_number','rt.table_number','u.name','o.total_amount','o.created_at'];
$sort = safe_sort_col($_GET['sort'] ?? 'o.created_at', $allowed_r, 'o.created_at');
$dir  = safe_dir($_GET['dir'] ?? 'DESC');

// ── Build WHERE (no user input interpolated unsafely) ──────
// Use date range as validated strings (regex above), IDs as int
$date_where = "DATE(o.created_at) BETWEEN '$from' AND '$to'";
$base_where = "o.status='paid' AND $date_where";
if ($resp_id > 0) $base_where .= " AND o.user_id=$resp_id";

// For product filter we join order_items
$prod_join  = '';
$prod_where = '';
if ($prod_id > 0) {
    $prod_join  = "JOIN order_items oi ON oi.order_id=o.id";
    $prod_where = " AND oi.product_id=$prod_id";
}

// Payment method filter on the payments table
$meth_allowed = [];
$meth_res = mysqli_query($conn, "SELECT DISTINCT method_name FROM payment_methods");
while ($mr = mysqli_fetch_assoc($meth_res)) $meth_allowed[] = $mr['method_name'];
if (!in_array($meth_f, $meth_allowed)) $meth_f = '';
$meth_join  = '';
$meth_where = '';
if ($meth_f !== '') {
    $meth_escaped = mysqli_real_escape_string($conn, $meth_f);
    $meth_join  = "JOIN payments pmt ON pmt.order_id=o.id";
    $meth_where = " AND pmt.payment_method='$meth_escaped'";
}

$full_where = "$base_where $prod_where $meth_where";

// ── Summary stats ─────────────────────────────────────────
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT o.id) as cnt, COALESCE(SUM(DISTINCT o.total_amount),0) as total
     FROM orders o $prod_join $meth_join WHERE $full_where"));

// ── Total for pagination ───────────────────────────────────
$total_orders = (int)mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(DISTINCT o.id) FROM orders o $prod_join $meth_join
     LEFT JOIN users u ON o.user_id=u.id
     LEFT JOIN restaurant_tables rt ON o.table_id=rt.id
     WHERE $full_where"))[0];

$page   = max(1, safe_int($_GET['page'] ?? 1));
$offset = ($page - 1) * PER_PAGE_R;

// ── Orders with sort + pagination ─────────────────────────
$orders_q = mysqli_query($conn,
    "SELECT DISTINCT o.*, u.name as staff_name, rt.table_number,
            (SELECT payment_method FROM payments WHERE order_id=o.id LIMIT 1) as pay_method
     FROM orders o
     LEFT JOIN users u ON o.user_id=u.id
     LEFT JOIN restaurant_tables rt ON o.table_id=rt.id
     $prod_join $meth_join
     WHERE $full_where
     ORDER BY $sort $dir
     LIMIT " . PER_PAGE_R . " OFFSET $offset");

// ── Payment method breakdown ──────────────────────────────
$pay_breakdown = mysqli_query($conn,
    "SELECT p.payment_method, COUNT(*) as cnt, SUM(p.amount) as total
     FROM payments p JOIN orders o ON p.order_id=o.id WHERE $base_where
     GROUP BY p.payment_method ORDER BY total DESC");

// ── Top products ──────────────────────────────────────────
$top_products = mysqli_query($conn,
    "SELECT oi.product_name, SUM(oi.quantity) as qty, SUM(oi.subtotal) as revenue
     FROM order_items oi JOIN orders o ON oi.order_id=o.id WHERE $base_where
     GROUP BY oi.product_name ORDER BY revenue DESC LIMIT 8");

// ── Filter dropdowns ──────────────────────────────────────
$all_staff    = mysqli_query($conn, "SELECT id,name FROM users WHERE status='active' ORDER BY name");
$all_products = mysqli_query($conn, "SELECT id,name FROM products ORDER BY name");
$all_methods  = mysqli_query($conn, "SELECT DISTINCT method_name FROM payment_methods WHERE is_enabled='yes' ORDER BY method_name");
?>

<!-- Filters -->
<div class="panel">
    <h3>📊 Sales Reports</h3>
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label style="font-size:11px;font-weight:700;color:var(--text3);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px;">Period</label>
            <select name="period" onchange="this.form.submit()" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:13px;">
                <option value="today"  <?php if($period==='today')  echo 'selected'; ?>>Today</option>
                <option value="week"   <?php if($period==='week')   echo 'selected'; ?>>This Week</option>
                <option value="month"  <?php if($period==='month')  echo 'selected'; ?>>This Month</option>
                <option value="custom" <?php if($period==='custom') echo 'selected'; ?>>Custom</option>
            </select>
        </div>
        <?php if ($period === 'custom'): ?>
        <div>
            <label style="font-size:11px;font-weight:700;color:var(--text3);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px;">From</label>
            <input type="date" name="date_from" value="<?php echo $from; ?>" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:13px;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:var(--text3);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px;">To</label>
            <input type="date" name="date_to" value="<?php echo $to; ?>" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:13px;">
        </div>
        <?php endif; ?>
        <div>
            <label style="font-size:11px;font-weight:700;color:var(--text3);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px;">Staff</label>
            <select name="resp_id" onchange="this.form.submit()" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:13px;">
                <option value="0">All Staff</option>
                <?php while ($s = mysqli_fetch_assoc($all_staff)): ?>
                <option value="<?php echo $s['id']; ?>" <?php if($resp_id==$s['id']) echo 'selected'; ?>><?php echo h($s['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:var(--text3);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px;">Product</label>
            <select name="prod_id" onchange="this.form.submit()" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:13px;">
                <option value="0">All Products</option>
                <?php while ($p = mysqli_fetch_assoc($all_products)): ?>
                <option value="<?php echo $p['id']; ?>" <?php if($prod_id==$p['id']) echo 'selected'; ?>><?php echo h($p['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:var(--text3);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px;">Payment</label>
            <select name="meth_f" onchange="this.form.submit()" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:13px;">
                <option value="">All Methods</option>
                <?php while ($pm = mysqli_fetch_assoc($all_methods)): ?>
                <option value="<?php echo h($pm['method_name']); ?>" <?php if($meth_f===$pm['method_name']) echo 'selected'; ?>><?php echo h($pm['method_name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div style="display:flex;gap:8px;margin-top:20px;">
            <button type="submit" class="btn-primary" style="padding:9px 16px;font-size:13px;">🔍 Apply</button>
            <a href="reports.php" class="btn-primary" style="background:#94a3b8;padding:9px 16px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;">✕ Reset</a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<?php $avg = $stats['cnt'] > 0 ? $stats['total'] / $stats['cnt'] : 0; ?>
<div class="card-grid" style="margin-bottom:20px;">
    <div class="card"><h3>Total Orders</h3><p><?php echo $stats['cnt']; ?></p></div>
    <div class="card"><h3>Total Revenue</h3><p>₹<?php echo number_format($stats['total'],2); ?></p></div>
    <div class="card"><h3>Avg Order</h3><p>₹<?php echo number_format($avg,2); ?></p></div>
    <div class="card"><h3>Period</h3><p style="font-size:16px;"><?php echo date('d M',strtotime($from)); ?> – <?php echo date('d M',strtotime($to)); ?></p></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <div class="panel" style="margin:0;">
        <h3>💳 Payment Breakdown</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Method</th><th>Orders</th><th>Revenue</th></tr></thead>
                <tbody>
                <?php $has=false; while($pr=mysqli_fetch_assoc($pay_breakdown)): $has=true; ?>
                <tr>
                    <td><?php echo h($pr['payment_method']); ?></td>
                    <td><?php echo $pr['cnt']; ?></td>
                    <td><strong>₹<?php echo number_format($pr['total'],2); ?></strong></td>
                </tr>
                <?php endwhile; if(!$has): ?>
                <tr><td colspan="3" class="empty-table">No data.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel" style="margin:0;">
        <h3>🍔 Top Products</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Product</th><th>Qty</th><th>Revenue</th></tr></thead>
                <tbody>
                <?php $has=false; while($tp=mysqli_fetch_assoc($top_products)): $has=true; ?>
                <tr>
                    <td><?php echo h($tp['product_name']); ?></td>
                    <td><?php echo $tp['qty']; ?></td>
                    <td><strong>₹<?php echo number_format($tp['revenue'],2); ?></strong></td>
                </tr>
                <?php endwhile; if(!$has): ?>
                <tr><td colspan="3" class="empty-table">No data.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Order Details (sortable + paginated) -->
<div class="panel">
    <div class="table-header">
        <h3>📋 Order Details</h3>
        <div style="display:flex;gap:10px;align-items:center;">
            <span class="table-count"><?php echo $total_orders; ?> orders</span>
            <span style="font-size:12px;color:var(--text3);"><?php echo $from; ?> → <?php echo $to; ?></span>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php echo sort_th('o.id',           '#',       $sort, $dir); ?>
                    <?php echo sort_th('o.order_number', 'Order',   $sort, $dir); ?>
                    <?php echo sort_th('rt.table_number','Table',   $sort, $dir); ?>
                    <?php echo sort_th('u.name',         'Staff',   $sort, $dir); ?>
                    <th>Pay Method</th>
                    <?php echo sort_th('o.total_amount', 'Amount',  $sort, $dir); ?>
                    <?php echo sort_th('o.created_at',   'Time',    $sort, $dir); ?>
                </tr>
            </thead>
            <tbody>
            <?php $has=false; while($ord=mysqli_fetch_assoc($orders_q)): $has=true;
                $pay_icons = ['Cash'=>'💵','Digital'=>'💳','UPI'=>'📱'];
                $pm = $ord['pay_method'] ?? '—';
            ?>
            <tr>
                <td style="color:var(--text3);"><?php echo $ord['id']; ?></td>
                <td><strong><?php echo h($ord['order_number']); ?></strong></td>
                <td><?php echo h($ord['table_number'] ?? '—'); ?></td>
                <td><?php echo h($ord['staff_name'] ?? '—'); ?></td>
                <td style="font-size:13px;"><?php echo ($pay_icons[$pm] ?? '').' '.h($pm); ?></td>
                <td><strong style="color:var(--primary);">₹<?php echo number_format($ord['total_amount'],2); ?></strong></td>
                <td style="font-size:12px;color:var(--text3);"><?php echo date('d M, h:i A',strtotime($ord['created_at'])); ?></td>
            </tr>
            <?php endwhile; if(!$has): ?>
            <tr><td colspan="7" class="empty-table">No paid orders found for this period.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php echo pagination_html($total_orders, PER_PAGE_R, $page); ?>
</div>

<?php include("layout/footer.php"); ?>
