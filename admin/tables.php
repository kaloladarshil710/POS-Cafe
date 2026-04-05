<?php
include("../config/db.php");
include("layout/header.php");

$message = "";
$error = "";

// ADD TABLE
if (isset($_POST['add_table'])) {
 $table_number = trim($_POST['table_number']);
 $seats = trim($_POST['seats']);
 $status = $_POST['status'];
 $active = $_POST['active'];

 if (empty($table_number) || empty($seats)) {
 $error = "Please fill all required fields!";
 } else {
 $sql = "INSERT INTO restaurant_tables (table_number, seats, status, active)
 VALUES ('$table_number', '$seats', '$status', '$active')";

 if (mysqli_query($conn, $sql)) {
 $message = "Table added successfully!";
 } else {
 $error = "Failed to add table!";
 }
 }
}

$tables = mysqli_query($conn, "SELECT * FROM restaurant_tables ORDER BY id DESC");
?>

<div class="panel">
 <h3>Add Restaurant Table</h3>

 <?php if($message): ?>
 <div class="msg-success"><?php echo $message; ?></div>
 <?php endif; ?>

 <?php if($error): ?>
 <div class="msg-error"><?php echo $error; ?></div>
 <?php endif; ?>

 <form method="POST">
 <div class="form-grid">
 <input type="text" name="table_number" placeholder="Table Number (T1, T2, Table 3)" required>
 <input type="number" name="seats" placeholder="Seats" required>
 <select name="status">
 <option value="free">Free</option>
 <option value="occupied">Occupied</option>
 </select>
 <select name="active">
 <option value="yes">Active</option>
 <option value="no">Inactive</option>
 </select>
 </div>

 <div style="margin-top:16px;">
 <button type="submit" name="add_table" class="btn-primary">+ Add Table</button>
 </div>
 </form>
</div>

<div class="panel">
 <h3>Restaurant Tables</h3>

 <div class="table-wrap">
 <table>
 <tr>
 <th>ID</th>
 <th>Table No.</th>
 <th>Seats</th>
 <th>Status</th>
 <th>Active</th>
 <th>Action</th>
 </tr>

 <?php while($row = mysqli_fetch_assoc($tables)) { ?>
 <tr>
 <td><?php echo $row['id']; ?></td>
 <td><?php echo htmlspecialchars($row['table_number']); ?></td>
 <td><?php echo $row['seats']; ?></td>
 <td>
 <span class="<?php echo ($row['status'] == 'free') ? 'status-free' : 'status-occupied'; ?>">
 <?php echo ucfirst($row['status']); ?>
 </span>
 </td>
 <td>
 <span class="<?php echo ($row['active'] == 'yes') ? 'status-yes' : 'status-no'; ?>">
 <?php echo ucfirst($row['active']); ?>
 </span>
 </td>
 <td>
 <a class="action-btn delete-btn" href="delete_table.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this table?')">Delete</a>
 </td>
 </tr>
 <?php } ?>
 </table>
 </div>
</div>

<?php include("layout/footer.php"); ?>