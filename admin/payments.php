<?php
include("../config/db.php");
include("layout/header.php");

$message = "";
$error   = "";

// UPDATE PAYMENT METHOD — FIXED: prepared statement (was vulnerable to SQL injection)
if (isset($_POST['update_payment'])) {
    $id         = intval($_POST['id']);
    $is_enabled = in_array($_POST['is_enabled'], ['yes', 'no']) ? $_POST['is_enabled'] : 'yes';
    $upi_id     = trim($_POST['upi_id'] ?? '');

    $stmt = mysqli_prepare($conn, "UPDATE payment_methods SET is_enabled=?, upi_id=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssi", $is_enabled, $upi_id, $id);
    if (mysqli_stmt_execute($stmt)) {
        $message = "✅ Payment method updated successfully!";
    } else {
        $error = "Failed to update: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

$payments = mysqli_query($conn, "SELECT * FROM payment_methods ORDER BY id ASC");
?>

<div class="panel">
    <h3>Payment Method Configuration</h3>
    <p style="color:#64748b;font-size:14px;margin-bottom:16px;">
        Enable or disable payment methods. UPI requires a valid UPI ID to show the QR code at checkout.
    </p>

    <?php if($message): ?><div class="msg-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if($error): ?><div class="msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="table-wrap">
        <table>
            <tr><th>Method</th><th>Status</th><th>UPI ID</th><th>Action</th></tr>
            <?php while($row = mysqli_fetch_assoc($payments)): ?>
            <tr>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <td>
                        <?php
                        $icons = ['Cash'=>'💵','Digital'=>'💳','UPI'=>'📱'];
                        echo ($icons[$row['method_name']] ?? '💰') . ' ';
                        echo htmlspecialchars($row['method_name']);
                        ?>
                    </td>
                    <td>
                        <select name="is_enabled" style="padding:6px 10px;border-radius:8px;border:1px solid #E2E8F0;">
                            <option value="yes" <?php if($row['is_enabled']==='yes') echo 'selected'; ?>>✅ Enabled</option>
                            <option value="no"  <?php if($row['is_enabled']==='no')  echo 'selected'; ?>>❌ Disabled</option>
                        </select>
                    </td>
                    <td>
                        <?php if ($row['method_name'] === 'UPI'): ?>
                        <input type="text" name="upi_id"
                               value="<?php echo htmlspecialchars($row['upi_id'] ?? ''); ?>"
                               placeholder="e.g. yourname@ybl"
                               style="padding:7px 10px;border:1px solid #E2E8F0;border-radius:8px;font-family:'Sora',sans-serif;font-size:13px;width:180px;">
                        <?php else: ?>
                        <span style="color:#94a3b8;font-size:13px;">N/A</span>
                        <input type="hidden" name="upi_id" value="">
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="submit" name="update_payment" class="btn-primary" style="padding:8px 14px;">Save</button>
                    </td>
                </form>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<?php include("layout/footer.php"); ?>
