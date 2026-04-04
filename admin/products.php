<?php
include("../config/db.php");
include("layout/header.php");

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];
$message = $error = "";

// ── ADD PRODUCT ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) die('CSRF check failed');
    $name        = trim($_POST['name'] ?? '');
    $category_id = safe_int($_POST['category_id'] ?? 0);
    $price       = floatval($_POST['price'] ?? 0);
    $unit        = trim($_POST['unit'] ?? 'Plate');
    $tax         = floatval($_POST['tax'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $is_available= in_array($_POST['is_available']??'',['yes','no'])?$_POST['is_available']:'yes';

    if (empty($name) || $category_id <= 0 || $price <= 0) {
        $error = "Name, Category and Price are required!";
    } else {
        // Handle image upload
        $image_name = null;
        if (!empty($_FILES['image']['name'])) {
            $allowed_types = ['image/jpeg','image/png','image/gif','image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            if (in_array($mime, $allowed_types) && $_FILES['image']['size'] < 2*1024*1024) {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_name = uniqid('prod_') . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/products/$image_name");
            } else {
                $error = "Invalid image file. Max 2MB, JPG/PNG/GIF/WebP only.";
            }
        }
        if (!$error) {
            $stmt = mysqli_prepare($conn, "INSERT INTO products (name,category_id,price,unit,tax,description,image,is_available) VALUES (?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, "sidsdsss", $name, $category_id, $price, $unit, $tax, $description, $image_name, $is_available);
            if (mysqli_stmt_execute($stmt)) { header("Location: products.php?msg=added"); exit(); }
            else { $error = "Failed to add product."; }
            mysqli_stmt_close($stmt);
        }
    }
}
if (isset($_GET['msg']) && $_GET['msg'] === 'added') $message = "✅ Product added successfully!";

// ── DELETE ───────────────────────────────────────────────
if (isset($_GET['delete'], $_GET['csrf']) && hash_equals($csrf, $_GET['csrf'])) {
    $did = safe_int($_GET['delete']);
    // Get image to delete
    $img_r = mysqli_fetch_assoc(mysqli_query($conn,"SELECT image FROM products WHERE id=$did"));
    if ($img_r && $img_r['image']) @unlink("../uploads/products/".$img_r['image']);
    $s = mysqli_prepare($conn,"DELETE FROM products WHERE id=?");
    mysqli_stmt_bind_param($s,"i",$did); mysqli_stmt_execute($s); mysqli_stmt_close($s);
    header("Location: products.php?msg=deleted"); exit();
}
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') $message = "✅ Product deleted.";

// ── TOGGLE AVAILABILITY ──────────────────────────────────
if (isset($_GET['toggle'], $_GET['csrf']) && hash_equals($csrf, $_GET['csrf'])) {
    $tid = safe_int($_GET['toggle']);
    $r = mysqli_fetch_assoc(mysqli_query($conn,"SELECT is_available FROM products WHERE id=$tid"));
    if ($r) {
        $ns = $r['is_available']==='yes'?'no':'yes';
        $s = mysqli_prepare($conn,"UPDATE products SET is_available=? WHERE id=?");
        mysqli_stmt_bind_param($s,"si",$ns,$tid); mysqli_stmt_execute($s);
    }
    header("Location: products.php"); exit();
}

// ── SORT, SEARCH, PAGINATE ───────────────────────────────
$per_page = in_array(safe_int($_GET['per_page']??10),[10,25,50,100])?safe_int($_GET['per_page']??10):10;
$allowed_sort = ['p.name','c.category_name','p.price','p.tax','p.created_at'];
$sort = safe_sort_col($_GET['sort']??'p.name',$allowed_sort,'p.name');
$dir  = safe_dir($_GET['dir']??'ASC');
$search = trim($_GET['q']??'');

if ($search !== '') {
    $like = '%'.$search.'%';
    $cs = mysqli_prepare($conn,"SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.name LIKE ? OR c.category_name LIKE ?");
    mysqli_stmt_bind_param($cs,"ss",$like,$like); mysqli_stmt_execute($cs);
    $total = (int)mysqli_fetch_row(mysqli_stmt_get_result($cs))[0];
} else {
    $total = (int)mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM products"))[0];
}
$page   = max(1, safe_int($_GET['page']??1));
$offset = ($page-1)*$per_page;

if ($search !== '') {
    $like = '%'.$search.'%';
    $stmt = mysqli_prepare($conn,"SELECT p.*,c.category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.name LIKE ? OR c.category_name LIKE ? ORDER BY $sort $dir LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt,"ssii",$like,$like,$per_page,$offset);
} else {
    $stmt = mysqli_prepare($conn,"SELECT p.*,c.category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY $sort $dir LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt,"ii",$per_page,$offset);
}
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

$cats = mysqli_query($conn,"SELECT * FROM categories WHERE status='active' ORDER BY category_name ASC");
?>

<!-- Add Product -->
<div class="panel">
  <h6 class="fw-bold mb-3">Add New Product</h6>
  <?php if($message): ?><div class="msg-success"><?php echo h($message); ?></div><?php endif; ?>
  <?php if($error):   ?><div class="msg-error"><?php echo h($error); ?></div><?php endif; ?>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label fw-semibold">Product Name *</label><input type="text" name="name" class="form-control" required maxlength="120" placeholder="e.g. Margherita Pizza"></div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Category *</label>
        <select name="category_id" class="form-select" required>
          <option value="">— Select Category —</option>
          <?php while ($cat=mysqli_fetch_assoc($cats)): ?><option value="<?php echo $cat['id']; ?>"><?php echo h($cat['category_name']); ?></option><?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label fw-semibold">Price (₹) *</label><input type="number" step="0.01" min="0.01" name="price" class="form-control" placeholder="0.00" required></div>
      <div class="col-md-2"><label class="form-label fw-semibold">Tax %</label><input type="number" step="0.01" min="0" max="100" name="tax" class="form-control" value="0"></div>
      <div class="col-md-2">
        <label class="form-label fw-semibold">Unit</label>
        <select name="unit" class="form-select"><option>Plate</option><option>Cup</option><option>Glass</option><option>Piece</option><option>Bowl</option><option>Bottle</option></select>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold">Available</label>
        <select name="is_available" class="form-select"><option value="yes">Yes</option><option value="no">No</option></select>
      </div>
      <div class="col-md-4"><label class="form-label fw-semibold">Product Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Description</label><input type="text" name="description" class="form-control" placeholder="Short description"></div>
    </div>
    <div class="mt-3">
      <button type="submit" name="add_product" class="btn text-white fw-bold px-4" style="background:#F97316;border-radius:10px;">+ Add Product</button>
      <a href="categories.php" class="btn btn-link" style="color:#F97316;font-weight:600;">➕ Manage Categories</a>
    </div>
  </form>
</div>

<!-- Product List -->
<div class="panel">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
      <h6 class="fw-bold mb-0">Products</h6>
      <span class="badge" style="background:rgba(249,115,22,0.1);color:#F97316;"><?php echo $total; ?></span>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <?php echo per_page_selector($per_page); ?>
      <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" value="<?php echo h($search); ?>" class="form-control form-control-sm" style="width:180px;" placeholder="🔍 Search products…">
        <input type="hidden" name="sort" value="<?php echo h($sort); ?>"><input type="hidden" name="dir" value="<?php echo h($dir); ?>">
        <input type="hidden" name="per_page" value="<?php echo $per_page; ?>">
        <button class="btn btn-sm text-white" style="background:#F97316;border-radius:8px;">Search</button>
        <?php if($search): ?><a href="products.php" class="btn btn-sm btn-secondary">✕</a><?php endif; ?>
      </form>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr>
        <th>#</th><th>Image</th>
        <?php echo sort_th('p.name','Name',$sort,$dir); ?>
        <?php echo sort_th('c.category_name','Category',$sort,$dir); ?>
        <?php echo sort_th('p.price','Price',$sort,$dir); ?>
        <?php echo sort_th('p.tax','Tax',$sort,$dir); ?>
        <th>Unit</th><th>Status</th>
        <?php echo sort_th('p.created_at','Added',$sort,$dir); ?>
        <th>Action</th>
      </tr></thead>
      <tbody>
      <?php if(mysqli_num_rows($products)===0): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">No products found.</td></tr>
      <?php else: while($row=mysqli_fetch_assoc($products)): ?>
        <tr>
          <td class="text-muted"><?php echo $row['id']; ?></td>
          <td><?php echo product_img($row['image'],$row['name'],'40px'); ?></td>
          <td><strong><?php echo h($row['name']); ?></strong><?php if($row['description']): ?><br><small class="text-muted"><?php echo h(substr($row['description'],0,40)); ?></small><?php endif; ?></td>
          <td><span class="badge bg-light text-dark border"><?php echo h($row['category_name']??'—'); ?></span></td>
          <td><strong style="color:#F97316;"><?php echo fmt_money($row['price']); ?></strong></td>
          <td><?php echo number_format($row['tax'],1); ?>%</td>
          <td><?php echo h($row['unit']); ?></td>
          <td>
            <span class="badge <?php echo $row['is_available']==='yes'?'badge-active':'badge-inactive'; ?>">
              <?php echo $row['is_available']==='yes'?'Available':'Unavailable'; ?>
            </span>
          </td>
          <td style="font-size:12px;color:#98A2B3;"><?php echo date('d M Y',strtotime($row['created_at'])); ?></td>
          <td>
            <div class="d-flex gap-1 flex-wrap">
              <a href="products.php?toggle=<?php echo $row['id']; ?>&csrf=<?php echo $csrf; ?>" class="btn btn-sm btn-outline-warning"><?php echo $row['is_available']==='yes'?'Disable':'Enable'; ?></a>
              <a href="products.php?delete=<?php echo $row['id']; ?>&csrf=<?php echo $csrf; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete <?php echo addslashes(h($row['name'])); ?>?')">Delete</a>
            </div>
          </td>
        </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
  <?php echo pagination_html($total, $per_page, $page); ?>
</div>

<?php include("layout/footer.php"); ?>