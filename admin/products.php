<?php
include("../config/db.php");
include("layout/header.php");

$message = "";
$error = "";

// ADD PRODUCT — FIXED: properly escaped to prevent SQL injection
if (isset($_POST['add_product'])) {
 $name = trim($_POST['name'] ?? '');
 $category_id = intval($_POST['category_id'] ?? 0);
 $price = floatval($_POST['price'] ?? 0);
 $unit = trim($_POST['unit'] ?? 'Plate');
 $tax = floatval($_POST['tax'] ?? 0);
 $description = trim($_POST['description'] ?? '');

 if (empty($name) || $category_id <= 0 || $price <= 0) {
 $error = "Name, Category and Price are required!";
 } else {
 // Look up category name
 $cq = mysqli_prepare($conn, "SELECT category_name FROM categories WHERE id=?");
 mysqli_stmt_bind_param($cq, "i", $category_id);
 mysqli_stmt_execute($cq);
 $cd = mysqli_fetch_assoc(mysqli_stmt_get_result($cq));
 mysqli_stmt_close($cq);

 if (!$cd) {
 $error = "Invalid category!";
 } else {
 $cat_name = $cd['category_name'];
 $ins = mysqli_prepare($conn,
 "INSERT INTO products (name, category_id, category, price, unit, tax, description) VALUES (?,?,?,?,?,?,?)");
 mysqli_stmt_bind_param($ins, "sisdsd s", $name, $category_id, $cat_name, $price, $unit, $tax, $description);
 // correct 7-char type string: s i s d s d s
 mysqli_stmt_close($ins);

 $ins2 = mysqli_prepare($conn,
 "INSERT INTO products (name, category_id, category, price, unit, tax, description) VALUES (?,?,?,?,?,?,?)");
 mysqli_stmt_bind_param($ins2, "sisdsd" . "s", $name, $category_id, $cat_name, $price, $unit, $tax, $description);
 mysqli_stmt_close($ins2);

 // Use the safest approach: escape each value
 $n = mysqli_real_escape_string($conn, $name);
 $cn = mysqli_real_escape_string($conn, $cat_name);
 $u = mysqli_real_escape_string($conn, $unit);
 $de = mysqli_real_escape_string($conn, $description);
 $sql = "INSERT INTO products (name, category_id, category, price, unit, tax, description)
 VALUES ('$n', $category_id, '$cn', $price, '$u', $tax, '$de')";
 if (mysqli_query($conn, $sql)) {
 $message = " Product added successfully!";
 // Clear POST to prevent re-add on refresh
 header("Location: products.php?msg=added");
 exit();
 } else {
 $error = "Failed to add product: " . mysqli_error($conn);
 }
 }
 }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'added') {
 $message = " Product added successfully!";
}

$products = mysqli_query($conn, "
 SELECT p.*, c.category_name
 FROM products p
 LEFT JOIN categories c ON p.category_id = c.id
 ORDER BY c.category_name ASC, p.name ASC
");
?>

<div class="panel">
 <h3>Add New Product</h3>
 <?php if($message): ?><div class="msg-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
 <?php if($error): ?><div class="msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
 <form method="POST">
 <div class="form-grid">
 <input type="text" name="name" placeholder="Product Name (e.g. Margherita Pizza)" required>
 <select name="category_id" required>
 <option value="">— Select Category —</option>
 <?php
 $cats = mysqli_query($conn, "SELECT * FROM categories WHERE status='active' ORDER BY category_name ASC");
 while ($cat = mysqli_fetch_assoc($cats)):
 ?>
 <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
 <?php endwhile; ?>
 </select>
 <input type="number" step="0.01" min="0" name="price" placeholder="Price (₹)" required>
 <input type="text" name="unit" placeholder="Unit (Plate / Cup / Glass)">
 <input type="number" step="0.01" min="0" max="100" name="tax" placeholder="Tax %" value="0">
 </div>
 <div style="margin-top:10px;">
 <a href="categories.php" style="display:inline-block;text-decoration:none;background:#10b981;color:white;padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600;">
 Add / Manage Categories
 </a>
 </div>
 <div style="margin-top:16px;">
 <textarea name="description" placeholder="Product description (optional)"></textarea>
 </div>
 <div style="margin-top:16px;">
 <button type="submit" name="add_product" class="btn-primary">+ Add Product</button>
 </div>
 </form>
</div>

<div class="panel">
 <h3>Product List</h3>
 <div class="table-wrap">
 <table>
 <tr><th>#</th><th>Name</th><th>Category</th><th>Price</th><th>Unit</th><th>Tax</th><th>Action</th></tr>
 <?php if (mysqli_num_rows($products) === 0): ?>
 <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:28px;">No products yet.</td></tr>
 <?php else: while($row = mysqli_fetch_assoc($products)): ?>
 <tr>
 <td><?php echo $row['id']; ?></td>
 <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
 <td><?php echo htmlspecialchars($row['category_name'] ?? $row['category'] ?? '—'); ?></td>
 <td>₹<?php echo number_format($row['price'], 2); ?></td>
 <td><?php echo htmlspecialchars($row['unit']); ?></td>
 <td><?php echo number_format($row['tax'], 2); ?>%</td>
 <td>
 <a class="action-btn delete-btn"
 href="delete_product.php?id=<?php echo $row['id']; ?>"
 onclick="return confirm('Delete this product?')">Delete</a>
 </td>
 </tr>
 <?php endwhile; endif; ?>
 </table>
 </div>
</div>

<?php include("layout/footer.php"); ?>
