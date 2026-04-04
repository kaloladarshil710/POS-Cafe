<?php
include("../config/db.php");
include("layout/header.php");

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];

const PER_PAGE_T = 10;
$message = $error = "";

// ── ADD TABLE ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_table'])) {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) die('CSRF check failed');
    $table_number = trim($_POST['table_number'] ?? '');
    $seats        = safe_int($_POST['seats'] ?? 0);
    $active       = in_array($_POST['active'] ?? '', ['yes','no']) ? $_POST['active'] : 'yes';

    if (empty($table_number) || $seats <= 0) {
        $error = "Table number and seats (min 1) are required!";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO restaurant_tables (table_number,seats,status,active) VALUES (?,'free','free',?)");
        $stmt = mysqli_prepare($conn, "INSERT INTO restaurant_tables (table_number,seats,status,active) VALUES (?,?,'free',?)");
        mysqli_stmt_bind_param($stmt, "sis", $table_number, $seats, $active);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Table added!";
        } else {
            $error = "Failed: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// ── FREE TABLE (admin override) ────────────────────────────
if (isset($_GET['free']) && isset($_GET['csrf']) && hash_equals($csrf, $_GET['csrf'])) {
    $fid = safe_int($_GET['free']);
    $s = mysqli_prepare($conn, "UPDATE restaurant_tables SET status='free', occupied_at=NULL WHERE id=?");
    mysqli_stmt_bind_param($s, "i", $fid);
    mysqli_stmt_execute($s);
    mysqli_stmt_close($s);
    header("Location: tables.php?msg=freed"); exit();
}
if (isset($_GET['msg']) && $_GET['msg'] === 'freed') $message = "Table marked as free.";

// ── SORT ──────────────────────────────────────────────────
$allowed = ['table_number','seats','status','active','created_at'];
$sort = safe_sort_col($_GET['sort'] ?? 'id', array_merge($allowed,['id']), 'id');
$dir  = safe_dir($_GET['dir'] ?? 'ASC');

// ── FILTER ────────────────────────────────────────────────
$stat_f   = in_array($_GET['stat_f']   ?? '', ['free','occupied','']) ? ($_GET['stat_f']   ?? '') : '';
$active_f = in_array($_GET['active_f'] ?? '', ['yes','no',''])        ? ($_GET['active_f'] ?? '') : '';
$search   = trim($_GET['q'] ?? '');

$where_parts = ["1=1"];
if ($stat_f   !== '') $where_parts[] = "rt.status='"   . mysqli_real_escape_string($conn, $stat_f)   . "'";
if ($active_f !== '') $where_parts[] = "rt.active='"   . mysqli_real_escape_string($conn, $active_f) . "'";
if ($search   !== '') $where_parts[] = "rt.table_number LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'";
$where = implode(" AND ", $where_parts);

$total = (int)mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM restaurant_tables rt WHERE $where"))[0];
$page   = max(1, safe_int($_GET['page'] ?? 1));
$offset = ($page - 1) * PER_PAGE_T;

$tables = mysqli_query($conn,
    "SELECT rt.*,
            COALESCE((SELECT SUM(o.total_amount) FROM orders o WHERE o.table_id=rt.id AND o.status!='paid'),0) AS running_total,
            COALESCE((SELECT COUNT(*) FROM orders o WHERE o.table_id=rt.id AND o.status!='paid'),0) AS order_count
     FROM restaurant_tables rt
     WHERE $where ORDER BY $sort $dir LIMIT " . PER_PAGE_T . " OFFSET $offset");
?>

<div class="panel">
    <h3>Add Restaurant Table</h3>
    <?php if($message): ?><div class="msg-success"><?php echo h($message); ?></div><?php endif; ?>
    <?php if($error):   ?><div class="msg-error"><?php echo h($error); ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
        <div class="form-grid">
            <input type="text"   name="table_number" placeholder="e.g. Table 1 / VIP-1" required maxlength="20">
            <input type="number" name="seats" min="1" max="50" placeholder="Seats" required>
            <select name="active">
                <option value="yes">Active</option>
                <option value="no">Inactive</option>
            </select>
        </div>
        <div style="margin-top:14px;">
            <button type="submit" name="add_table" class="btn-primary">+ Add Table</button>
        </div>
    </form>
</div>

<div class="panel">
    <div class="table-header">
        <h3>Restaurant Tables</h3>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <span class="table-count"><?php echo $total; ?> Tables</span>
            <form method="GET" style="display:flex;gap:6px;flex-wrap:wrap;">
                <input type="text" name="q" value="<?php echo h($search); ?>" placeholder="Search…" style="width:130px;padding:7px 12px;font-size:13px;">
                <select name="stat_f" onchange="this.form.submit()" style="padding:7px 12px;font-size:13px;">
                    <option value="">All Status</option>
                    <option value="free"     <?php echo $stat_f==='free'    ?'selected':''; ?>>Free</option>
                    <option value="occupied" <?php echo $stat_f==='occupied'?'selected':''; ?>>Occupied</option>
                </select>
                <select name="active_f" onchange="this.form.submit()" style="padding:7px 12px;font-size:13px;">
                    <option value="">Active/All</option>
                    <option value="yes" <?php echo $active_f==='yes'?'selected':''; ?>>Active Only</option>
                    <option value="no"  <?php echo $active_f==='no' ?'selected':''; ?>>Inactive</option>
                </select>
                <input type="hidden" name="sort" value="<?php echo h($sort); ?>">
                <input type="hidden" name="dir"  value="<?php echo h($dir); ?>">
                <button class="btn-primary" style="padding:7px 14px;font-size:13px;">Filter</button>
                <?php if($search||$stat_f||$active_f): ?><a href="tables.php" class="btn-primary" style="background:#94a3b8;padding:7px 14px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;">✕</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php echo sort_th('id',           '#',        $sort, $dir); ?>
                    <?php echo sort_th('table_number', 'Table',    $sort, $dir); ?>
                    <?php echo sort_th('seats',        'Seats',    $sort, $dir); ?>
                    <?php echo sort_th('status',       'Status',   $sort, $dir); ?>
                    <th>Pending Bill</th>
                    <th>Occ. Since</th>
                    <?php echo sort_th('active',       'Active',   $sort, $dir); ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($tables) === 0): ?>
            <tr><td colspan="8" class="empty-table">No tables found.</td></tr>
            <?php else: while ($row = mysqli_fetch_assoc($tables)):
                $occ = $row['status'] === 'occupied';
            ?>
            <tr>
                <td style="color:var(--text3);"><?php echo $row['id']; ?></td>
                <td><strong><?php echo h($row['table_number']); ?></strong></td>
                <td>👥 <?php echo $row['seats']; ?></td>
                <td>
                    <span class="badge" style="<?php echo $occ?'background:rgba(239,68,68,0.1);color:#EF4444;':'background:rgba(18,183,106,0.1);color:#12B76A;'; ?>">
                        <?php echo $occ ? '🔴 Occupied' : '🟢 Free'; ?>
                    </span>
                </td>
                <td>
                    <?php if ($row['running_total'] > 0): ?>
                    <a href="../pos/table_bill.php?table_id=<?php echo $row['id']; ?>"
                       style="color:var(--primary);font-weight:700;text-decoration:none;">
                       ₹<?php echo number_format($row['running_total'],2); ?></a>
                    <?php else: ?><span style="color:var(--text3);">—</span><?php endif; ?>
                </td>
                <td style="font-size:12px;color:var(--text3);">
                    <?php echo ($occ && !empty($row['occupied_at'])) ? date('h:i A, d M', strtotime($row['occupied_at'])) : '—'; ?>
                </td>
                <td>
                    <span class="badge <?php echo $row['active']==='yes'?'badge-active':'badge-inactive'; ?>">
                        <?php echo $row['active']==='yes'?'Active':'Inactive'; ?>
                    </span>
                </td>
                <td>
                    <div class="action-group">
                        <?php if ($occ): ?>
                        <a href="../pos/table_bill.php?table_id=<?php echo $row['id']; ?>"
                           class="action-btn edit-btn">💳 Pay</a>
                        <a href="tables.php?free=<?php echo $row['id']; ?>&csrf=<?php echo $csrf; ?>"
                           class="action-btn edit-btn"
                           onclick="return confirm('Force-free this table?')">🔓 Free</a>
                        <?php endif; ?>
                        <a class="action-btn delete-btn"
                           href="delete_table.php?id=<?php echo $row['id']; ?>&csrf=<?php echo $csrf; ?>"
                           onclick="return confirm('Delete table?')">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
    <?php echo pagination_html($total, PER_PAGE_T, $page); ?>
</div>

<?php include("layout/footer.php"); ?>
