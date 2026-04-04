<?php
include("../config/db.php");
include("layout/header.php");

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];

const PER_PAGE_C = 10;
$message = $error = "";

// ── ADD CATEGORY ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) die('CSRF check failed');
    $cat_name = trim($_POST['category_name'] ?? '');
    $status   = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active';

    if (empty($cat_name)) {
        $error = "Category name is required!";
    } else {
        $chk = mysqli_prepare($conn, "SELECT id FROM categories WHERE category_name=?");
        mysqli_stmt_bind_param($chk, "s", $cat_name);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = "Category already exists!";
        } else {
            $ins = mysqli_prepare($conn, "INSERT INTO categories (category_name, status) VALUES (?,?)");
            mysqli_stmt_bind_param($ins, "ss", $cat_name, $status);
            if (mysqli_stmt_execute($ins)) {
                $message = "Category added!";
            } else {
                $error = "Failed to add category.";
            }
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($chk);
    }
}

// ── SORT ──────────────────────────────────────────────────
$allowed = ['category_name','status','created_at','id'];
$sort = safe_sort_col($_GET['sort'] ?? 'id', $allowed, 'id');
$dir  = safe_dir($_GET['dir'] ?? 'DESC');

// ── SEARCH ────────────────────────────────────────────────
$search  = trim($_GET['q'] ?? '');
$stat_f  = in_array($_GET['stat_f'] ?? '', ['active','inactive','']) ? ($_GET['stat_f'] ?? '') : '';

// Build WHERE
$where_parts = ["1=1"];
if ($search !== '') $where_parts[] = "category_name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'";
if ($stat_f  !== '') $where_parts[] = "status='" . mysqli_real_escape_string($conn, $stat_f) . "'";
$where = implode(" AND ", $where_parts);

$total = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM categories WHERE $where"))[0];
$page   = max(1, safe_int($_GET['page'] ?? 1));
$offset = ($page - 1) * PER_PAGE_C;

$categories = mysqli_query($conn,
    "SELECT *, (SELECT COUNT(*) FROM products WHERE category_id=categories.id) AS product_count
     FROM categories WHERE $where ORDER BY $sort $dir LIMIT " . PER_PAGE_C . " OFFSET $offset");
?>

<style>
.toggle-wrap{display:inline-flex;align-items:center;gap:8px;cursor:pointer;user-select:none;}
.t-track{width:42px;height:23px;border-radius:99px;position:relative;transition:background 0.25s;flex-shrink:0;}
.t-track::after{content:'';position:absolute;top:3px;left:3px;width:17px;height:17px;background:#fff;border-radius:50%;transition:transform 0.25s;box-shadow:0 1px 3px rgba(0,0,0,0.2);}
.t-label{font-size:13px;font-weight:500;min-width:52px;transition:color 0.25s;}
.tog-on .t-track{background:var(--primary);} .tog-on .t-track::after{transform:translateX(19px);} .tog-on .t-label{color:var(--primary);}
.tog-off .t-track{background:#ccc;} .tog-off .t-label{color:#888;}
.tog-disabled{opacity:0.5;pointer-events:none;}
</style>

<div class="panel">
    <h3>Add New Category</h3>
    <?php if($message): ?><div class="msg-success"><?php echo h($message); ?></div><?php endif; ?>
    <?php if($error):   ?><div class="msg-error"><?php echo h($error); ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
        <div class="form-grid">
            <input type="text" name="category_name" placeholder="Category Name" required maxlength="100">
            <select name="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <div style="margin-top:14px;">
            <button type="submit" name="add_category" class="btn-primary">+ Add Category</button>
        </div>
    </form>
</div>

<div class="panel">
    <div class="table-header">
        <h3>Category List</h3>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <span class="table-count"><?php echo $total; ?> Categories</span>
            <form method="GET" style="display:flex;gap:6px;flex-wrap:wrap;">
                <input type="text" name="q" value="<?php echo h($search); ?>" placeholder="Search…" style="width:150px;padding:7px 12px;font-size:13px;">
                <select name="stat_f" onchange="this.form.submit()" style="padding:7px 12px;font-size:13px;">
                    <option value="">All Status</option>
                    <option value="active"   <?php echo $stat_f==='active'  ?'selected':''; ?>>Active</option>
                    <option value="inactive" <?php echo $stat_f==='inactive'?'selected':''; ?>>Inactive</option>
                </select>
                <input type="hidden" name="sort" value="<?php echo h($sort); ?>">
                <input type="hidden" name="dir"  value="<?php echo h($dir); ?>">
                <button class="btn-primary" style="padding:7px 14px;font-size:13px;">Search</button>
                <?php if($search||$stat_f): ?><a href="categories.php" class="btn-primary" style="background:#94a3b8;padding:7px 14px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;">✕</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php echo sort_th('id',            '#',        $sort, $dir); ?>
                    <?php echo sort_th('category_name', 'Name',     $sort, $dir); ?>
                    <th>Products</th>
                    <?php echo sort_th('status',        'Status',   $sort, $dir); ?>
                    <?php echo sort_th('created_at',    'Created',  $sort, $dir); ?>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($categories)): ?>
            <tr id="row-<?php echo $row['id']; ?>">
                <td style="color:var(--text3);"><?php echo $row['id']; ?></td>
                <td><strong><?php echo h($row['category_name']); ?></strong></td>
                <td><span class="badge badge-role-staff"><?php echo $row['product_count']; ?> items</span></td>
                <td>
                    <div class="toggle-wrap <?php echo $row['status']==='active'?'tog-on':'tog-off'; ?>"
                         onclick="toggleStatus(<?php echo $row['id']; ?>, this)">
                        <div class="t-track"></div>
                        <span class="t-label"><?php echo $row['status']==='active'?'Active':'Inactive'; ?></span>
                    </div>
                </td>
                <td style="font-size:12px;color:var(--text3);"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                <td>
                    <a class="action-btn delete-btn"
                       href="delete_category.php?id=<?php echo $row['id']; ?>&csrf=<?php echo $csrf; ?>"
                       onclick="return confirm('Delete category? Products using it will lose category.')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php echo pagination_html($total, PER_PAGE_C, $page); ?>
</div>

<script>
function toggleStatus(id, el) {
    const isOn = el.classList.contains('tog-on');
    el.classList.add('tog-disabled');
    fetch('toggle_category_status.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'id='+id+'&status='+(isOn?'inactive':'active')+'&csrf=<?php echo $csrf; ?>'
    }).then(r=>r.json()).then(d=>{
        if(d.success){
            el.classList.toggle('tog-on');el.classList.toggle('tog-off');
            el.querySelector('.t-label').textContent=isOn?'Inactive':'Active';
        } else alert('Update failed.');
    }).catch(()=>alert('Network error.')).finally(()=>el.classList.remove('tog-disabled'));
}
</script>

<?php include("layout/footer.php"); ?>
