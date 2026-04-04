<?php
include("../config/db.php");
include("layout/header.php");
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

$message = "";
$error = "";

// ADD CATEGORY
if (isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $status = $_POST['status'];

    if (empty($category_name)) {
        $error = "Category name is required!";
    } else {
        $check = mysqli_query($conn, "SELECT * FROM categories WHERE category_name='$category_name'");

        if (mysqli_num_rows($check) > 0) {
            $error = "Category already exists!";
        } else {
            $sql = "INSERT INTO categories (category_name, status)
                    VALUES ('$category_name', '$status')";

            if (mysqli_query($conn, $sql)) {

    if ($redirect == 'products') {
        header("Location: products.php");
        exit();
    } else {
        $message = "Category added successfully!";
    }

} else {
    $error = "Failed to add category!";
}
        }
    }
}

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");
?>

<div class="panel">
    <h3>Add New Category</h3>

    <?php if($message): ?>
        <div class="msg-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="msg-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-grid">
            <input type="text" name="category_name" placeholder="Category Name (Fast Food, Beverages)" required>

            <select name="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div style="margin-top:16px;">
            <button type="submit" name="add_category" class="btn-primary">+ Add Category</button>
        </div>
    </form>
</div>

<div class="panel">
    <h3>Category List</h3>

    <div class="table-wrap">
        <table>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php while($row = mysqli_fetch_assoc($categories)) { ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                <td>
                    <span class="<?php echo ($row['status'] == 'active') ? 'status-yes' : 'status-no'; ?>">
                        <?php echo ucfirst($row['status']); ?>
                    </span>
                </td>
                <td>
                    <a class="action-btn delete-btn" href="delete_category.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this category?')">Delete</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>

<?php include("layout/footer.php"); ?>