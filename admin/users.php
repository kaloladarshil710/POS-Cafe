<?php
include("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";
$error = "";

/* -----------------------------
   ADD USER
------------------------------*/
if (isset($_POST['add_user'])) {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role     = trim($_POST['role']);

    if (!empty($name) && !empty($email) && !empty($password) && !empty($role)) {
        $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

        if (mysqli_num_rows($check) > 0) {
            $error = "Email already exists!";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insert = mysqli_query($conn, "INSERT INTO users(name, email, password, role, status)
                                           VALUES('$name', '$email', '$hashedPassword', '$role', 'active')");

            if ($insert) {
                $message = "User added successfully!";
            } else {
                $error = "Failed to add user!";
            }
        }
    } else {
        $error = "All fields are required!";
    }
}

/* -----------------------------
   DELETE USER
------------------------------*/
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // prevent self delete
    if ($id != $_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id=$id");
        header("Location: users.php?deleted=1");
        exit();
    } else {
        $error = "You cannot delete your own account!";
    }
}

/* -----------------------------
   PAGINATION
------------------------------*/
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

/* total users */
$total_users_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$total_users_row = mysqli_fetch_assoc($total_users_q);
$total_users = (int)$total_users_row['total'];
$total_pages = ceil($total_users / $limit);

/* paginated users */
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC LIMIT $limit OFFSET $offset");

include("layout/header.php");
?>

<div class="panel">
    <h3>Add Staff / User</h3>
    <p>Create a new admin or staff account for your café system.</p>

    <?php if($message): ?>
        <div class="msg-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="msg-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if(isset($_GET['deleted'])): ?>
        <div class="msg-success">User deleted successfully!</div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-grid">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>

            <select name="role" required>
                <option value="">Select Role</option>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <div style="margin-top:15px;">
            <button type="submit" name="add_user" class="btn-primary">+ Add User</button>
        </div>
    </form>
</div>

<div class="panel">
    <div class="table-header">
        <h3>All Users</h3>
        <div class="table-count"><?php echo $total_users; ?> Users</div>
    </div>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th style="min-width:170px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($users) > 0): ?>
                    <?php while($u = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td>#<?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['name']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <span class="badge <?php echo ($u['role'] == 'admin') ? 'badge-role-admin' : 'badge-role-staff'; ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo ($u['status'] == 'active') ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo ucfirst($u['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-group">
                                <a href="edit_user.php?id=<?php echo $u['id']; ?>" class="action-btn edit-btn">Edit</a>

                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="users.php?delete=<?php echo $u['id']; ?>" 
                                       class="action-btn delete-btn"
                                       onclick="return confirm('Delete this user?')">Delete</a>
                                <?php else: ?>
                                    <span class="self-label">Current User</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-table">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if($total_users > 0): ?>
    <div class="pagination-wrap">
        <div class="pagination-info">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_users); ?> of <?php echo $total_users; ?> users
        </div>

        <div class="pagination">
            <a class="page-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>" 
               href="?page=<?php echo $page - 1; ?>">Prev</a>

            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);

            for($i = $start; $i <= $end; $i++):
            ?>
                <a class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>" 
                   href="?page=<?php echo $i; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <a class="page-link <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>" 
               href="?page=<?php echo $page + 1; ?>">Next</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include("layout/footer.php"); ?>