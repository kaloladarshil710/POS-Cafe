<?php
include("../config/db.php");
include("layout/header.php");

$message = "";
$error = "";

// FETCH ACTIVE CATEGORIES
$category_result = mysqli_query($conn, "SELECT * FROM categories WHERE status='active' ORDER BY category_name ASC");

// ADD PRODUCT
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $category_id = (int) $_POST['category_id'];
    $price = trim($_POST['price']);
    $unit = trim($_POST['unit']);
    $tax = trim($_POST['tax']);
    $description = trim($_POST['description']);

    if (empty($name) || empty($category_id) || empty($price)) {
        $error = "Please fill all required fields!";
    } else {
        // Get category name for compatibility with old column
        $cat_query = mysqli_query($conn, "SELECT category_name FROM categories WHERE id = $category_id");
        $cat_data = mysqli_fetch_assoc($cat_query);
        $category_name = $cat_data['category_name'];

        $sql = "INSERT INTO products (name, category_id, category, price, unit, tax, description)
                VALUES ('$name', '$category_id', '$category_name', '$price', '$unit', '$tax', '$description')";

        if (mysqli_query($conn, $sql)) {
            $message = "Product added successfully!";
        } else {
            $error = "Failed to add product!";
        }
    }
}

// FETCH PRODUCTS WITH CATEGORY NAME
$products = mysqli_query($conn, "
    SELECT p.*, c.category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
");
?>

<div class="panel">
    <h3>Add New Product</h3>

    <?php if($message): ?>
        <div class="msg-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="msg-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-grid">
            <input type="text" name="name" placeholder="Product Name (Pizza, Coffee)" required>

            <select name="category_id" required>
    <option value="">Select Category</option>

    <?php
    $cats = mysqli_query($conn, "SELECT * FROM categories WHERE status='active' ORDER BY category_name ASC");
    while($cat = mysqli_fetch_assoc($cats)) {
    ?>
        <option value="<?php echo $cat['id']; ?>">
            <?php echo htmlspecialchars($cat['category_name']); ?>
        </option>
    <?php } ?>
</select>

<!-- ADD CATEGORY BUTTON -->
<div style="margin-top:8px;">
    <a href="categories.php?redirect=products" style="
        display:inline-block;
        text-decoration:none;
        background:#10b981;
        color:white;
        padding:8px 12px;
        border-radius:8px;
        font-size:13px;
        font-weight:600;
    ">
        ➕ Add New Category
    </a>
</div>

            <input type="number" step="0.01" name="price" placeholder="Price" required>
            <input type="text" name="unit" placeholder="Unit (Plate / Cup / Glass)">
            <input type="number" step="0.01" name="tax" placeholder="Tax %" value="">
        </div>

        <div style="margin-top:16px;">
            <textarea name="description" placeholder="Product Description"></textarea>
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
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Unit</th>
                <th>Tax</th>
                <th>Action</th>
            </tr>

            <?php while($row = mysqli_fetch_assoc($products)) { ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                <td>₹<?php echo number_format($row['price'], 2); ?></td>
                <td><?php echo htmlspecialchars($row['unit']); ?></td>
                <td><?php echo $row['tax']; ?>%</td>
                <td>
                    <a class="action-btn delete-btn" href="delete_product.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this product?')">Delete</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>  
</div>

<?php include("layout/footer.php"); ?>