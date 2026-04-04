<?php
include("../config/db.php");
include("layout/header.php");

$message = $error = "";

if (isset($_POST['add_product'])) {
    $name        = trim($_POST['name'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $unit        = trim($_POST['unit'] ?? 'Plate');
    $tax         = floatval($_POST['tax'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $unit        = empty($unit) ? 'Plate' : $unit;

    if (empty($name) || empty($category) || $price <= 0) {
        $error = "Product name, category and a valid price are required!";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO products (name,category,price,unit,tax,description) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "ssdsds", $name, $category, $price, $unit, $tax, $description);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Product added successfully!";
        } else {
            $error = "Failed to add product.";
        }
        mysqli_stmt_close($stmt);
    }
}

$products = mysqli_query($conn, "SELECT * FROM products ORDER BY category ASC, name ASC");
$total_products = mysqli_num_rows($products);
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY category ASC, name ASC");
?>

<div class="panel">
    <div class="panel-header">
        <h3>Add New Product</h3>
        <span class="badge badge-blue"><?php echo $total_products; ?> Products</span>
    </div>

    <?php if ($message): ?><div class="alert alert-success">✅ <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Product Name *</label>
                <input type="text" name="name" placeholder="e.g. Margherita Pizza" required>
            </div>
            <div class="form-group">
                <label>Category *</label>
                <input type="text" name="category" placeholder="e.g. Pizza, Coffee, Burger" required>
            </div>
            <div class="form-group">
                <label>Price (₹) *</label>
                <input type="number" step="0.01" min="0.01" name="price" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label>Unit</label>
                <select name="unit">
                    <option value="Plate">Plate</option>
                    <option value="Cup">Cup</option>
                    <option value="Glass">Glass</option>
                    <option value="Piece">Piece</option>
                    <option value="Bowl">Bowl</option>
                    <option value="Bottle">Bottle</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tax %</label>
                <input type="number" step="0.01" min="0" max="100" name="tax" placeholder="0" value="0">
            </div>
        </div>
        <div class="form-group" style="margin-top:16px;">
            <label>Description</label>
            <textarea name="description" placeholder="Short description of the product..."></textarea>
        </div>
        <div style="margin-top:18px;">
            <button type="submit" name="add_product" class="btn-primary">➕ Add Product</button>
        </div>
    </form>
</div>

<div class="panel">
    <div class="panel-header"><h3>Product Menu</h3></div>
    <?php if (mysqli_num_rows($products) > 0): ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Name</th><th>Category</th><th>Price</th><th>Unit</th><th>Tax</th><th>Description</th><th>Action</th></tr></thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($products)): ?>
                <tr>
                    <td style="color:#94a3b8;"><?php echo $row['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                    <td><span class="badge badge-blue"><?php echo htmlspecialchars($row['category']); ?></span></td>
                    <td><strong>₹<?php echo number_format($row['price'],2); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['unit']); ?></td>
                    <td><?php echo $row['tax']; ?>%</td>
                    <td style="color:#64748b;font-size:13px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($row['description'] ?: '—'); ?></td>
                    <td><a class="btn-danger" href="delete_product.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this product?')">🗑 Delete</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div style="text-align:center;padding:40px;color:#94a3b8;"><div style="font-size:48px;margin-bottom:12px;">🍽️</div><p>No products yet. Add your first menu item above!</p></div>
    <?php endif; ?>
</div>

<?php include("layout/footer.php"); ?>