<?php
session_start();
include('../config/db.php');
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

include 'layout/header.php';

// Pagination & Search
$per_page = safe_int($_GET['per_page'] ?? 10, 10);
$page = safe_int($_GET['page'] ?? 1, 1);
$offset = ($page - 1) * $per_page;
$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

$where = '1=1';
$params = [];
$types = '';

if ($search) {
    $where .= ' AND (o.order_number LIKE ? OR rt.table_number LIKE ? OR cs.customer_name LIKE ?)';
    $params = array_fill(0, 3, "%$search%");
    $types .= 'sss';
}
if ($status && $status !== 'all') {
    $where .= ' AND o.status = ?';
    $params[] = $status;
    $types .= 's';
}

// Count total
$count_sql = "SELECT COUNT(*) FROM orders o 
              LEFT JOIN restaurant_tables rt ON o.table_id = rt.id 
              LEFT JOIN customer_sessions cs ON o.session_id = cs.id 
              WHERE $where";
$stmt = mysqli_prepare($conn, $count_sql);
if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$total = (int) mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];

// Fetch orders
$sql = "SELECT o.*, rt.table_number, cs.customer_name, u.name as staff_name 
        FROM orders o 
        LEFT JOIN restaurant_tables rt ON o.table_id = rt.id 
        LEFT JOIN customer_sessions cs ON o.session_id = cs.id
        LEFT JOIN users u ON o.staff_id = u.id
        WHERE $where 
        ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2>Order History <small class="text-muted">(<?php echo $total; ?> total)</small></h2>
    <div class="d-flex gap-2 flex-wrap">
        <select class="form-select form-select-sm" onchange="filterStatus(this.value)">
            <option value="all">All Status</option>
            <option value="pending" <?php echo $status=='pending'?'selected':'';?>>Pending</option>
            <option value="preparing" <?php echo $status=='preparing'?'selected':'';?>>Preparing</option>
            <option value="ready" <?php echo $status=='ready'?'selected':'';?>>Ready</option>
            <option value="paid" <?php echo $status=='paid'?'selected':'';?>>Paid</option>
        </select>
        <input type="text" class="form-control form-control-sm" style="width:200px" placeholder="Search..." value="<?php echo h($search); ?>" onchange="searchOrders()">
        <?php echo per_page_selector($per_page); ?>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover modern-orders-table">
        <thead>
            <tr>
                <th><i class="bi bi-hash"></i> Order #</th>
                <th><i class="bi bi-clock"></i> Date/Time</th>
                <th><i class="bi bi-table"></i> Table</th>
                <th><i class="bi bi-person"></i> Customer</th>
                <th><i class="bi bi-cart"></i> Items</th>
                <th><i class="bi bi-currency-rupee"></i> Amount</th>
                <th><i class="bi bi-circle-fill"></i> Status</th>
                <th><i class="bi bi-person-check"></i> Staff</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($orders) == 0): ?>
            <tr>
                <td colspan="9" class="text-center py-5 text-muted empty-table-state">
                    <i class="bi bi-receipt fs-1 d-block mb-3 opacity-50"></i>
                    <h5 class="mb-2">No orders match your filters</h5>
                    <p class="mb-0">Try clearing search or status filters</p>
                </td>
            </tr>
            <?php else: while ($order = mysqli_fetch_assoc($orders)): 
                $status_display = ucfirst($order['status'] ?? 'pending');
                $status_emoji = ['pending'=>'⏳','preparing'=>'🔥','ready'=>'✅','paid'=>'💰','cancelled'=>'❌'][$order['status'] ?? 'pending'] ?? '⏳';
                $status_class = [
                    'pending'=>'bg-warning text-dark',
                    'preparing'=>'bg-info', 
                    'ready'=>'bg-success', 
                    'paid'=>'bg-success text-white fw-bold',
                    'cancelled'=>'bg-secondary text-white'
                ][$order['status'] ?? 'pending'] ?? 'bg-secondary';
                $item_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM order_items WHERE order_id = {$order['id']}"))[0];
            ?>
            <tr class="order-row-hover" data-order-id="<?php echo $order['id']; ?>">
                <td class="fw-bold text-primary fs-6">
                    #<?php echo h($order['order_number']); ?>
                </td>
                <td class="text-muted small">
                    <div><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                    <div class="fw-semibold"><?php echo date('g:i A', strtotime($order['created_at'])); ?></div>
                </td>
                <td>
                    <span class="badge bg-gradient-primary px-3 py-2 fw-semibold">
                        🪑 <?php echo h($order['table_number']); ?>
                    </span>
                </td>
                <td class="fw-medium">
                    <?php echo h($order['customer_name'] ?? 'Walk-in Customer'); ?>
                </td>
                <td>
                    <span class="badge bg-light text-dark px-3">
                        <?php echo $item_count; ?> item<?php echo $item_count != 1 ? 's' : ''; ?>
                    </span>
                </td>
                <td class="fw-bold fs-5 text-success">
                    ₹<?php echo number_format($order['total_amount'], 2); ?>
                </td>
                <td>
                    <span class="badge <?php echo $status_class; ?> px-3 py-2 fs-6 fw-semibold d-flex align-items-center gap-1">
                        <?php echo $status_emoji; ?> <?php echo $status_display; ?>
                    </span>
                </td>
                <td class="text-muted">
                    <?php echo h($order['staff_name'] ?? 'Self'); ?>
                </td>
                <td class="text-nowrap">
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" 
                           class="btn btn-outline-primary" title="View Details">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if ($order['status'] != 'paid'): ?>
                        <a href="update_status.php?id=<?php echo $order['id']; ?>" 
                           class="btn btn-outline-success" title="Update Status">
                            <i class="bi bi-arrow-repeat"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($order['table_number'] && $order['status'] != 'paid'): ?>
                        <a href="../pos/table_bill.php?table_id=<?php echo array_search($order['table_number'], array_column($tables ?? [], 'table_number')); ?>" 
                           class="btn btn-outline-warning" title="Table Bill">
                            <i class="bi bi-receipt"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<style>
.modern-orders-table thead th {position:sticky;top:0;background:white;z-index:10;border-bottom:2px solid var(--border);}
.modern-orders-table tbody tr {transition: all 0.25s cubic-bezier(0.4,0,0.2,1);}
.modern-orders-table tbody tr:hover { 
  background: linear-gradient(90deg, rgba(249,115,22,0.06) 0%, rgba(249,115,22,0.02) 100%);
  transform: translateX(4px) scale(1.005);
  box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}
.order-row-hover {cursor:pointer;}
.order-row-hover:hover td {border-color:transparent !important;}
.bg-gradient-primary {background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;}
.empty-table-state h5 {color:var(--text);}
@media (max-width:992px) {
  .modern-orders-table {font-size:0.9rem;}
  .btn-group-sm .btn {padding:4px 8px; font-size:0.8rem;}
}
</style>

<?php 
$total_pages = ceil($total / $per_page);
if ($total_pages > 1): 
?>
<nav>
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>&per_page=<?= $per_page ?>&q=<?= urlencode($search) ?>&status=<?= $status ?>">Previous</a></li>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
        <li class="page-item <?= $i==$page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&per_page=<?= $per_page ?>&q=<?= urlencode($search) ?>&status=<?= $status ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
        <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>&per_page=<?= $per_page ?>&q=<?= urlencode($search) ?>&status=<?= $status ?>">Next</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<script>
function filterStatus(status) {
    window.location = '?status=' + status + '&per_page=<?= $per_page ?>&q=<?= urlencode($search) ?>';
}
function searchOrders() {
    const q = document.querySelector('input[placeholder="Search..."]').value;
    window.location = '?q=' + encodeURIComponent(q) + '&status=<?= $status ?>&per_page=<?= $per_page ?>';
}
</script>

<?php include 'layout/footer.php'; ?>

