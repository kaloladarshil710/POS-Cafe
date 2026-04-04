<?php
include("../config/db.php");
include("layout/header.php");

$message = $error = "";
const PER_PAGE = 10;

// ── CSRF token ────────────────────────────────────────────
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];

// ── ADD PRODUCT ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) die('CSRF check failed');

    $name        = trim($_POST['name'] ?? '');
    $category_id = safe_int($_POST['category_id'] ?? 0);
    $price       = floatval($_POST['price'] ?? 0);
    $unit        = trim($_POST['unit'] ?? 'Plate');
    $tax         = floatval($_POST['tax'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || $category_id <= 0 || $price <= 0) {
        $error = "Name, Category and Price are required!";
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO products (name, category_id, price, unit, tax, description) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "sidsds", $name, $category_id, $price, $unit, $tax, $description);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: products.php?msg=added"); exit();
        } else {
            $error = "Failed to add product.";
        }
        mysqli_stmt_close($stmt);
    }
}
if (isset($_GET['msg']) && $_GET['msg'] === 'added') $message = "✅ Product added successfully!";

// ── SORT ──────────────────────────────────────────────────
$allowed_sort = ['p.name', 'c.category_name', 'p.price', 'p.tax', 'p.created_at'];
$sort = safe_sort_col($_GET['sort'] ?? 'p.name', $allowed_sort, 'p.name');
$dir  = safe_dir($_GET['dir'] ?? 'ASC');

// ── SEARCH ────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');

// ── COUNT ─────────────────────────────────────────────────
$search_bind = '';
if ($search !== '') {
    $like = '%' . $search . '%';
    $count_stmt = mysqli_prepare($conn,
        "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id=c.id
         WHERE p.name LIKE ? OR c.category_name LIKE ?");
    mysqli_stmt_bind_param($count_stmt, "ss", $like, $like);
    mysqli_stmt_execute($count_stmt);
    $total = (int) mysqli_fetch_row(mysqli_stmt_get_result($count_stmt))[0];
    mysqli_stmt_close($count_stmt);
} else {
    $total = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM products"))[0];
}

$page   = max(1, safe_int($_GET['page'] ?? 1));
$offset = ($page - 1) * PER_PAGE;

// ── FETCH ─────────────────────────────────────────────────
$limit = PER_PAGE; // Convert constant to variable for bind_param
if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = mysqli_prepare($conn,
        "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id
         WHERE p.name LIKE ? OR c.category_name LIKE ?
         ORDER BY $sort $dir LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, "ssii", $like, $like, $limit, $offset);
} else {
    $stmt = mysqli_prepare($conn,
        "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id
         ORDER BY $sort $dir LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
}
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Categories for dropdown
$cats = mysqli_query($conn, "SELECT * FROM categories WHERE status='active' ORDER BY category_name ASC");
?>

<!-- Add Product Panel -->
<div class="panel">
    <h3>Add New Product</h3>
    <?php if($message): ?><div class="msg-success"><?php echo h($message); ?></div><?php endif; ?>
    <?php if($error):   ?><div class="msg-error"><?php echo h($error); ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
        <div class="form-grid">
            <input type="text" name="name" placeholder="Product Name" required maxlength="120">
            <select name="category_id" required>
                <option value="">— Select Category —</option>
                <?php while ($cat = mysqli_fetch_assoc($cats)): ?>
                <option value="<?php echo $cat['id']; ?>"><?php echo h($cat['category_name']); ?></option>
                <?php endwhile; ?>
            </select>
            <input type="number" step="0.01" min="0.01" name="price" placeholder="Price (₹)" required>
            <select name="unit">
                <option value="Plate">Plate</option><option value="Cup">Cup</option>
                <option value="Glass">Glass</option><option value="Piece">Piece</option>
                <option value="Bowl">Bowl</option><option value="Bottle">Bottle</option>
            </select>
            <input type="number" step="0.01" min="0" max="100" name="tax" placeholder="Tax %" value="0">
        </div>
        <div style="margin-top:12px;">
            <textarea name="description" placeholder="Description (optional)" style="min-height:70px;"></textarea>
        </div>
        <div style="margin-top:14px;display:flex;gap:10px;align-items:center;">
            <button type="submit" name="add_product" class="btn-primary">+ Add Product</button>
            <a href="categories.php" style="font-size:13px;color:var(--primary);text-decoration:none;font-weight:600;">➕ Manage Categories</a>
        </div>
    </form>
</div>

<!-- Product List Panel -->
<div class="panel">
    <div class="table-header">
        <h3>Product List</h3>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <span class="table-count"><?php echo $total; ?> Products</span>
            <form method="GET" style="display:flex;gap:6px;">
                <input type="text" name="q" value="<?php echo h($search); ?>" placeholder="Search…" style="width:180px;padding:7px 12px;font-size:13px;">
                <input type="hidden" name="sort" value="<?php echo h($sort); ?>">
                <input type="hidden" name="dir"  value="<?php echo h($dir); ?>">
                <button class="btn-primary" style="padding:7px 14px;font-size:13px;">Search</button>
                <?php if($search): ?><a href="products.php" class="btn-primary" style="background:#94a3b8;padding:7px 14px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;">✕</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <?php echo sort_th('p.name',          'Name',     $sort, $dir); ?>
                    <?php echo sort_th('c.category_name', 'Category', $sort, $dir); ?>
                    <?php echo sort_th('p.price',         'Price',    $sort, $dir); ?>
                    <?php echo sort_th('p.tax',           'Tax',      $sort, $dir); ?>
                    <th>Unit</th>
                    <?php echo sort_th('p.created_at',    'Added',    $sort, $dir); ?>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($products) === 0): ?>
            <tr><td colspan="8" class="empty-table">No products found.</td></tr>
            <?php else: while ($row = mysqli_fetch_assoc($products)): ?>
            <tr>
                <td style="color:var(--text3);"><?php echo $row['id']; ?></td>
                <td><strong><?php echo h($row['name']); ?></strong></td>
                <td><?php echo h($row['category_name'] ?? '—'); ?></td>
                <td><strong>₹<?php echo number_format($row['price'], 2); ?></strong></td>
                <td><?php echo number_format($row['tax'], 2); ?>%</td>
                <td><?php echo h($row['unit']); ?></td>
                <td style="font-size:12px;color:var(--text3);"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                <td>
                    <a class="action-btn delete-btn"
                       href="delete_product.php?id=<?php echo $row['id']; ?>&csrf=<?php echo $csrf; ?>"
                       onclick="return confirm('Delete <?php echo addslashes(h($row['name'])); ?>?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
    <?php echo pagination_html($total, PER_PAGE, $page); ?>
</div>

<?php include("layout/footer.php"); ?>