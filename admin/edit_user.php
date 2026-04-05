<?php
include("../config/db.php");
include("layout/header.php");

if (!isset($_GET['id'])) {
 header("Location: users.php");
 exit();
}

$id = (int) $_GET['id'];
$message = "";
$error = "";

$user = mysqli_query($conn, "SELECT * FROM users WHERE id=$id");
if (mysqli_num_rows($user) == 0) {
 header("Location: users.php");
 exit();
}

$userData = mysqli_fetch_assoc($user);

if (isset($_POST['update_user'])) {
 $name = trim($_POST['name']);
 $email = trim($_POST['email']);
 $role = $_POST['role'];
 $status = $_POST['status'];
 $password = trim($_POST['password']);

 if (!empty($password)) {
 $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
 $update = mysqli_query($conn, "UPDATE users 
 SET name='$name', email='$email', role='$role', status='$status', password='$hashedPassword'
 WHERE id=$id");
 } else {
 $update = mysqli_query($conn, "UPDATE users 
 SET name='$name', email='$email', role='$role', status='$status'
 WHERE id=$id");
 }

 if ($update) {
 $message = "User updated successfully!";
 $user = mysqli_query($conn, "SELECT * FROM users WHERE id=$id");
 $userData = mysqli_fetch_assoc($user);
 } else {
 $error = "Failed to update user.";
 }
}
?>

<div class="panel">
 <h3>Edit User</h3>
 <p>Update user details below.</p>

 <?php if($message) echo "<div class='msg-success'>$message</div>"; ?>
 <?php if($error) echo "<div class='msg-error'>$error</div>"; ?>

 <form method="POST">
 <div class="form-grid">
 <input type="text" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
 <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>

 <select name="role" required>
 <option value="staff" <?php if($userData['role']=='staff') echo 'selected'; ?>>Staff</option>
 <option value="admin" <?php if($userData['role']=='admin') echo 'selected'; ?>>Admin</option>
 </select>

 <select name="status" required>
 <option value="active" <?php if($userData['status']=='active') echo 'selected'; ?>>Active</option>
 <option value="inactive" <?php if($userData['status']=='inactive') echo 'selected'; ?>>Inactive</option>
 </select>

 <input type="password" name="password" placeholder="New Password (optional)">
 </div>

 <div style="margin-top:18px; display:flex; gap:10px;">
 <button type="submit" name="update_user" class="btn-primary">Update User</button>
 <a href="users.php" class="action-btn edit-btn">Back</a>
 </div>
 </form>
</div>

<?php include("layout/footer.php"); ?>