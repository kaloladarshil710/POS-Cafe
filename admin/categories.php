<?php
include("../config/db.php");
include("layout/header.php");
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

$message = "";
$error = "";

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
            $sql = "INSERT INTO categories (category_name, status) VALUES ('$category_name', '$status')";
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

<style>
.toggle-wrap {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}
.t-track {
    width: 42px;
    height: 23px;
    border-radius: 99px;
    position: relative;
    transition: background 0.25s;
    flex-shrink: 0;
}
.t-track::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 17px;
    height: 17px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.25s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.t-label {
    font-size: 13px;
    font-weight: 500;
    min-width: 52px;
    transition: color 0.25s;
}
.tog-on  .t-track              { background: #ff6b35; }
.tog-on  .t-track::after       { transform: translateX(19px); }
.tog-on  .t-label              { color: #ff6b35; }
.tog-off .t-track              { background: #ccc; }
.tog-off .t-label              { color: #888; }
.tog-disabled                  { opacity: 0.5; pointer-events: none; }
</style>

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
            <tr id="row-<?php echo $row['id']; ?>">
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                <td>
                    <div class="toggle-wrap <?php echo ($row['status'] == 'active') ? 'tog-on' : 'tog-off'; ?>"
                         onclick="toggleStatus(<?php echo $row['id']; ?>, this)">
                        <div class="t-track"></div>
                        <span class="t-label"><?php echo ($row['status'] == 'active') ? 'Active' : 'Inactive'; ?></span>
                    </div>
                </td>
                <td>
                    <a class="action-btn delete-btn"
                       href="delete_category.php?id=<?php echo $row['id']; ?>"
                       onclick="return confirm('Delete this category?')">Delete</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>

<script>
function toggleStatus(id, el) {
    const isOn = el.classList.contains('tog-on');
    const newStatus = isOn ? 'inactive' : 'active';

    el.classList.add('tog-disabled');

    fetch('toggle_category_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&status=' + newStatus
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            el.classList.toggle('tog-on');
            el.classList.toggle('tog-off');
            el.querySelector('.t-label').textContent = newStatus === 'active' ? 'Active' : 'Inactive';
        } else {
            alert('Failed to update status. Please try again.');
        }
    })
    .catch(() => {
        alert('Network error. Please try again.');
    })
    .finally(() => {
        el.classList.remove('tog-disabled');
    });
}
</script>

<?php include("layout/footer.php"); ?>