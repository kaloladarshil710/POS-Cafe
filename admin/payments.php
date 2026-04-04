<?php
include("../config/db.php");
include("layout/header.php");

$message = "";
$error = "";

// UPDATE PAYMENT METHOD
if (isset($_POST['update_payment'])) {
    $id = $_POST['id'];
    $is_enabled = $_POST['is_enabled'];
    $upi_id = trim($_POST['upi_id']);

    $sql = "UPDATE payment_methods 
            SET is_enabled='$is_enabled', upi_id='$upi_id'
            WHERE id='$id'";

    if (mysqli_query($conn, $sql)) {
        $message = "Payment method updated successfully!";
    } else {
        $error = "Failed to update payment method!";
    }
}

$payments = mysqli_query($conn, "SELECT * FROM payment_methods ORDER BY id ASC");
?>

<div class="panel">
    <h3>Payment Method Configuration</h3>

    <?php if($message): ?>
        <div class="msg-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="msg-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <tr>
                <th>ID</th>
                <th>Method</th>
                <th>Enabled</th>
                <th>UPI ID</th>
                <th>Action</th>
            </tr>

            <?php while($row = mysqli_fetch_assoc($payments)) { ?>
            <tr>
                <form method="POST">
                    <td>
                        <?php echo $row['id']; ?>
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    </td>
                    <td><?php echo htmlspecialchars($row['method_name']); ?></td>
                    <td>
                        <select name="is_enabled">
                            <option value="yes" <?php if($row['is_enabled']=='yes') echo 'selected'; ?>>Yes</option>
                            <option value="no" <?php if($row['is_enabled']=='no') echo 'selected'; ?>>No</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="upi_id" value="<?php echo htmlspecialchars($row['upi_id']); ?>" placeholder="Only for UPI">
                    </td>
                    <td>
                        <button type="submit" name="update_payment" class="btn-primary">Update</button>
                    </td>
                </form>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>

<?php include("layout/footer.php"); ?>