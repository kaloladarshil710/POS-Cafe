<?php
include("../config/db.php");

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];

const PER_PAGE_P = 10;
$message = $error = "";

// ── CSRF check helper ─────────────────────────────────────
function check_csrf(string $token): void {
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        die('Invalid CSRF token.');
    }
}

// ── DELETE ────────────────────────────────────────────────
if (isset($_GET['delete'], $_GET['csrf'])) {
    check_csrf($_GET['csrf']);
    $id = safe_int($_GET['delete']);
    $s = mysqli_prepare($GLOBALS['conn'], "DELETE FROM payment_methods WHERE id=?");
    mysqli_stmt_bind_param($s, "i", $id);
    mysqli_stmt_execute($s);
    mysqli_stmt_close($s);
    header("Location: payments.php?msg=deleted"); exit();
}

// ── TOGGLE ────────────────────────────────────────────────
if (isset($_GET['toggle'], $_GET['csrf'])) {
    check_csrf($_GET['csrf']);
    $id = safe_int($_GET['toggle']);
    $r  = mysqli_fetch_assoc(mysqli_query($GLOBALS['conn'], "SELECT is_enabled FROM payment_methods WHERE id=$id"));
    if ($r) {
        $ns = $r['is_enabled'] === 'yes' ? 'no' : 'yes';
        $s  = mysqli_prepare($GLOBALS['conn'], "UPDATE payment_methods SET is_enabled=? WHERE id=?");
        mysqli_stmt_bind_param($s, "si", $ns, $id);
        mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
    }
    header("Location: payments.php?msg=toggled"); exit();
}

// ── ADD ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_method'])) {
    check_csrf($_POST['csrf'] ?? '');
    $method_name = trim($_POST['method_name'] ?? '');
    $upi_id      = trim($_POST['upi_id'] ?? '');
    $is_enabled  = in_array($_POST['is_enabled'] ?? '', ['yes','no']) ? $_POST['is_enabled'] : 'yes';

    if (empty($method_name)) {
        $error = "Method name is required!";
    } else {
        $chk = mysqli_prepare($GLOBALS['conn'], "SELECT id FROM payment_methods WHERE method_name=?");
        mysqli_stmt_bind_param($chk, "s", $method_name);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = "Method already exists!";
        } else {
            $ins = mysqli_prepare($GLOBALS['conn'], "INSERT INTO payment_methods (method_name,is_enabled,upi_id) VALUES (?,?,?)");
            $upi_val = empty($upi_id) ? null : $upi_id;
            mysqli_stmt_bind_param($ins, "sss", $method_name, $is_enabled, $upi_val);
            if (mysqli_stmt_execute($ins)) {
                $message = "Method added!";
            } else {
                $error = "Failed to add.";
            }
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($chk);
    }
}

// ── UPDATE ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_method'])) {
    check_csrf($_POST['csrf'] ?? '');
    $id          = safe_int($_POST['id'] ?? 0);
    $method_name = trim($_POST['method_name'] ?? '');
    $upi_id      = trim($_POST['upi_id'] ?? '');
    $is_enabled  = in_array($_POST['is_enabled'] ?? '', ['yes','no']) ? $_POST['is_enabled'] : 'yes';
    $upi_val     = empty($upi_id) ? null : $upi_id;

    $s = mysqli_prepare($GLOBALS['conn'], "UPDATE payment_methods SET method_name=?,upi_id=?,is_enabled=? WHERE id=?");
    mysqli_stmt_bind_param($s, "sssi", $method_name, $upi_val, $is_enabled, $id);
    if (mysqli_stmt_execute($s)) $message = "Updated!"; else $error = "Failed.";
    mysqli_stmt_close($s);
}

// ── MESSAGES ─────────────────────────────────────────────
$msg_map = ['deleted'=>'Payment method deleted.','toggled'=>'Status updated.','added'=>'Added.'];
if (isset($_GET['msg'], $msg_map[$_GET['msg']])) $message = $msg_map[$_GET['msg']];

// ── EDIT FETCH ────────────────────────────────────────────
$editData = null;
if (isset($_GET['edit'])) {
    $eid = safe_int($_GET['edit']);
    $eq  = mysqli_prepare($GLOBALS['conn'], "SELECT * FROM payment_methods WHERE id=?");
    mysqli_stmt_bind_param($eq, "i", $eid);
    mysqli_stmt_execute($eq);
    $editData = mysqli_fetch_assoc(mysqli_stmt_get_result($eq));
    mysqli_stmt_close($eq);
}

// ── SORT & PAGINATE list of payment_methods ───────────────
$allowed_pm = ['method_name','is_enabled','created_at','id'];
$sort = safe_sort_col($_GET['sort'] ?? 'id', $allowed_pm, 'id');
$dir  = safe_dir($_GET['dir'] ?? 'ASC');

$total_pm = (int)mysqli_fetch_row(mysqli_query($GLOBALS['conn'], "SELECT COUNT(*) FROM payment_methods"))[0];
$page     = max(1, safe_int($_GET['page'] ?? 1));
$offset   = ($page - 1) * PER_PAGE_P;
$methods  = mysqli_query($GLOBALS['conn'], "SELECT * FROM payment_methods ORDER BY $sort $dir LIMIT " . PER_PAGE_P . " OFFSET $offset");

include("layout/header.php");
?>

<div class="panel">
    <h3><?php echo $editData ? 'Edit Payment Method' : 'Add Payment Method'; ?></h3>
    <p>Manage payment methods available at checkout.</p>
    <?php if($message): ?><div class="msg-success"><?php echo h($message); ?></div><?php endif; ?>
    <?php if($error):   ?><div class="msg-error"><?php echo h($error); ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
        <?php if($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>
        <div class="form-grid">
            <input type="text" name="method_name" placeholder="Method Name (Cash / UPI / Card)" required maxlength="50"
                   value="<?php echo $editData ? h($editData['method_name']) : ''; ?>">
            <input type="text" name="upi_id" placeholder="UPI ID (only for UPI)" maxlength="120"
                   value="<?php echo $editData ? h($editData['upi_id'] ?? '') : ''; ?>">
            <select name="is_enabled">
                <option value="yes" <?php if($editData && $editData['is_enabled']==='yes') echo 'selected'; ?>>Enabled</option>
                <option value="no"  <?php if($editData && $editData['is_enabled']==='no')  echo 'selected'; ?>>Disabled</option>
            </select>
        </div>
        <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
            <?php if($editData): ?>
            <button class="btn-primary" name="update_method">Update</button>
            <a href="payments.php" class="action-btn edit-btn">Cancel</a>
            <?php else: ?>
            <button class="btn-primary" name="add_method">+ Add Method</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="panel">
    <div class="table-header">
        <h3>All Payment Methods</h3>
        <span class="table-count"><?php echo $total_pm; ?> Methods</span>
    </div>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <?php echo sort_th('id',          '#',       $sort, $dir); ?>
                    <?php echo sort_th('method_name', 'Method',  $sort, $dir); ?>
                    <th>UPI ID</th>
                    <?php echo sort_th('is_enabled',  'Status',  $sort, $dir); ?>
                    <?php echo sort_th('created_at',  'Created', $sort, $dir); ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($methods) === 0): ?>
            <tr><td colspan="6" class="empty-table">No methods found.</td></tr>
            <?php else: while ($m = mysqli_fetch_assoc($methods)): ?>
            <tr>
                <td style="color:var(--text3);">#<?php echo $m['id']; ?></td>
                <td><strong><?php echo h($m['method_name']); ?></strong></td>
                <td style="color:var(--text2);"><?php echo !empty($m['upi_id']) ? h($m['upi_id']) : '<span style="color:var(--text3);">—</span>'; ?></td>
                <td><span class="badge <?php echo $m['is_enabled']==='yes'?'badge-active':'badge-inactive'; ?>">
                    <?php echo $m['is_enabled']==='yes'?'Enabled':'Disabled'; ?>
                </span></td>
                <td style="font-size:12px;color:var(--text3);"><?php echo date('d M Y', strtotime($m['created_at'])); ?></td>
                <td>
                    <div class="action-group">
                        <a href="payments.php?edit=<?php echo $m['id']; ?>" class="action-btn edit-btn">Edit</a>
                        <a href="payments.php?toggle=<?php echo $m['id']; ?>&csrf=<?php echo $csrf; ?>" class="action-btn edit-btn">
                            <?php echo $m['is_enabled']==='yes'?'Disable':'Enable'; ?>
                        </a>
                        <a href="payments.php?delete=<?php echo $m['id']; ?>&csrf=<?php echo $csrf; ?>"
                           class="action-btn delete-btn"
                           onclick="return confirm('Delete this payment method?')">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
    <?php echo pagination_html($total_pm, PER_PAGE_P, $page); ?>
</div>

<?php include("layout/footer.php"); ?>
