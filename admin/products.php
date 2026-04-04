<?php
include("../config/db.php");
include("layout/header.php");

$message = "";
$error = "";

// ADD PRODUCT
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);
    $unit = trim($_POST['unit']);
    $tax = trim($_POST['tax']);
    $description = trim($_POST['description']);

    if (empty($name) || empty($category) || empty($price)) {
        $error = "Please fill all required fields!";
    } else {
        $sql = "INSERT INTO products (name, category, price, unit, tax, description)
                VALUES ('$name', '$category', '$price', '$unit', '$tax', '$description')";

        if (mysqli_query($conn, $sql)) {
            $message = "Product added successfully!";
        } else {
            $error = "Failed to add product!";
        }
    }
}

$products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
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
            <input type="text" name="category" placeholder="Category (Fast Food, Beverage)" required>
            <input type="number" step="0.01" name="price" placeholder="Price" required>
            <input type="text" name="unit" placeholder="Unit (Plate / Cup / Glass)">
            <input type="number" step="0.01" name="tax" placeholder="Tax %" value="0">
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
                <td><?php echo htmlspecialchars($row['category']); ?></td>
                <td>₹<?php echo $row['price']; ?></td>
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