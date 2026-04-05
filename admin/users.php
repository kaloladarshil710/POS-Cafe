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
 <div class="table-header">
 <h3>All Users</h3>
 <div class="table-count"><?php echo mysqli_num_rows($users); ?> Users</div>
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
 <?php if(mysqli_num_rows($users) > 0) { ?>
 <?php while($u = mysqli_fetch_assoc($users)) { ?>
 <tr>
 <td>#<?php echo $u['id']; ?></td>
 <td><?php echo htmlspecialchars($u['name']); ?></td>
 <td><?php echo htmlspecialchars($u['email']); ?></td>
 <td>
 <span class="badge <?php echo $u['role'] == 'admin' ? 'badge-role-admin' : 'badge-role-staff'; ?>">
 <?php echo ucfirst($u['role']); ?>
 </span>
 </td>
 <td>
 <span class="badge <?php echo $u['status'] == 'active' ? 'badge-active' : 'badge-inactive'; ?>">
 <?php echo ucfirst($u['status']); ?>
 </span>
 </td>
 <td>
 <div class="action-group">
 <a href="edit_user.php?id=<?php echo $u['id']; ?>" class="action-btn edit-btn">Edit</a>

 <?php if($u['id'] != $_SESSION['user_id']) { ?>
 <a href="delete_user.php?id=<?php echo $u['id']; ?>" 
 class="action-btn delete-btn"
 onclick="return confirm('Delete user?')">Delete</a>
 <?php } else { ?>
 <span class="self-label">Current User</span>
 <?php } ?>
 </div>
 </td>
 </tr>
 <?php } ?>
 <?php } else { ?>
 <tr>
 <td colspan="6" class="empty-table">No users found.</td>
 </tr>
 <?php } ?>
 </tbody>
 </table>
 </div>
</div>

<?php include("layout/footer.php"); ?>