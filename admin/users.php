<?php
include("../config/db.php");
include("layout/header.php");

$message = "";

// ADD USER
if (isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    if ($name && $email && $password) {
        mysqli_query($conn, "INSERT INTO users(name,email,password,role)
        VALUES('$name','$email','$password','$role')");
        $message = "User added successfully!";
    }
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
?>

<div class="panel">
    <h3>Add Staff / User</h3>

    <?php if($message) echo "<div class='msg-success'>$message</div>"; ?>

    <form method="POST">
        <div class="form-grid">
            <input type="text" name="name" placeholder="Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>

            <select name="role">
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <div style="margin-top:15px;">
            <button class="btn-primary" name="add_user">+ Add User</button>
        </div>
    </form>
</div>

<div class="panel">
    <h3>All Users</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php while($u = mysqli_fetch_assoc($users)) { ?>
        <tr>
            <td><?php echo $u['id']; ?></td>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo ucfirst($u['role']); ?></td>
            <td><?php echo ucfirst($u['status']); ?></td>
            <td>
                <a href="delete_user.php?id=<?php echo $u['id']; ?>" 
                   class="action-btn delete-btn"
                   onclick="return confirm('Delete user?')">Delete</a>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>

<?php include("layout/footer.php"); ?>