<?php
include("../config/db.php");

$message = "";
$error = "";

/* -----------------------------
   DELETE PAYMENT METHOD
------------------------------*/
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM payment_methods WHERE id=$id");
    header("Location: payments.php?deleted=1");
    exit();
}

/* -----------------------------
   TOGGLE ENABLE / DISABLE
------------------------------*/
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_enabled FROM payment_methods WHERE id=$id"));
    if ($row) {
        $new_status = ($row['is_enabled'] === 'yes') ? 'no' : 'yes';
        mysqli_query($conn, "UPDATE payment_methods SET is_enabled='$new_status' WHERE id=$id");
    }

    header("Location: payments.php?toggled=1");
    exit();
}

/* -----------------------------
   ADD PAYMENT METHOD
------------------------------*/
if (isset($_POST['add_method'])) {
    $method_name = trim($_POST['method_name']);
    $upi_id = trim($_POST['upi_id']);
    $is_enabled = $_POST['is_enabled'];

    if (!empty($method_name)) {
        $check = mysqli_query($conn, "SELECT * FROM payment_methods WHERE method_name='$method_name'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Payment method already exists!";
        } else {
            mysqli_query($conn, "INSERT INTO payment_methods(method_name, is_enabled, upi_id)
                                 VALUES('$method_name', '$is_enabled', '$upi_id')");
            $message = "Payment method added successfully!";
        }
    } else {
        $error = "Method name is required!";
    }
}

/* -----------------------------
   UPDATE PAYMENT METHOD
------------------------------*/
if (isset($_POST['update_method'])) {
    $id = (int)$_POST['id'];
    $method_name = trim($_POST['method_name']);
    $upi_id = trim($_POST['upi_id']);
    $is_enabled = $_POST['is_enabled'];

    $update = mysqli_query($conn, "UPDATE payment_methods 
                                   SET method_name='$method_name', 
                                       upi_id='$upi_id', 
                                       is_enabled='$is_enabled'
                                   WHERE id=$id");

    if ($update) {
        $message = "Payment method updated successfully!";
    } else {
        $error = "Failed to update payment method!";
    }
}

/* -----------------------------
   EDIT FETCH
------------------------------*/
$editData = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $editQ = mysqli_query($conn, "SELECT * FROM payment_methods WHERE id=$edit_id");
    if (mysqli_num_rows($editQ) > 0) {
        $editData = mysqli_fetch_assoc($editQ);
    }
}

/* -----------------------------
   FETCH ALL METHODS
------------------------------*/
$methods = mysqli_query($conn, "SELECT * FROM payment_methods ORDER BY id DESC");

include("layout/header.php");
?>

<div class="panel">
    <h3><?php echo $editData ? "Edit Payment Method" : "Add Payment Method"; ?></h3>
    <p>Manage all available payment methods for POS billing.</p>

    <?php if($message) echo "<div class='msg-success'>$message</div>"; ?>
    <?php if($error) echo "<div class='msg-error'>$error</div>"; ?>
    <?php if(isset($_GET['deleted'])) echo "<div class='msg-success'>Payment method deleted successfully!</div>"; ?>
    <?php if(isset($_GET['toggled'])) echo "<div class='msg-success'>Payment method status updated!</div>"; ?>

    <form method="POST">
        <?php if($editData): ?>
            <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
        <?php endif; ?>

        <div class="form-grid">
            <input type="text" name="method_name" placeholder="Method Name (Cash / UPI / Card)"
                   value="<?php echo $editData ? htmlspecialchars($editData['method_name']) : ''; ?>" required>

            <input type="text" name="upi_id" placeholder="UPI ID (optional)"
                   value="<?php echo $editData ? htmlspecialchars($editData['upi_id']) : ''; ?>">

            <select name="is_enabled" required>
                <option value="yes" <?php if($editData && $editData['is_enabled']=='yes') echo 'selected'; ?>>Enabled</option>
                <option value="no" <?php if($editData && $editData['is_enabled']=='no') echo 'selected'; ?>>Disabled</option>
            </select>
        </div>

        <div style="margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;">
            <?php if($editData): ?>
                <button class="btn-primary" name="update_method">Update Method</button>
                <a href="payments.php" class="action-btn edit-btn">Cancel</a>
            <?php else: ?>
                <button class="btn-primary" name="add_method">+ Add Method</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="panel">
    <div class="table-header">
        <h3>All Payment Methods</h3>
        <div class="table-count"><?php echo mysqli_num_rows($methods); ?> Methods</div>
    </div>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Method Name</th>
                    <th>UPI ID</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th style="min-width:220px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($methods) > 0): ?>
                    <?php while($m = mysqli_fetch_assoc($methods)): ?>
                    <tr>
                        <td>#<?php echo $m['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($m['method_name']); ?></strong></td>
                        <td>
                            <?php echo !empty($m['upi_id']) ? htmlspecialchars($m['upi_id']) : "<span style='color:#98A2B3;'>—</span>"; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $m['is_enabled'] == 'yes' ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $m['is_enabled'] == 'yes' ? 'Enabled' : 'Disabled'; ?>
                            </span>
                        </td>
                        <td><?php echo date("d M Y", strtotime($m['created_at'])); ?></td>
                        <td>
                            <div class="action-group">
                                <a href="payments.php?edit=<?php echo $m['id']; ?>" class="action-btn edit-btn">Edit</a>

                                <a href="payments.php?toggle=<?php echo $m['id']; ?>" class="action-btn edit-btn">
                                    <?php echo $m['is_enabled'] == 'yes' ? 'Disable' : 'Enable'; ?>
                                </a>

                                <a href="payments.php?delete=<?php echo $m['id']; ?>"
                                   class="action-btn delete-btn"
                                   onclick="return confirm('Delete this payment method?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-table">No payment methods found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include("layout/footer.php"); ?>