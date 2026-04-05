<?php
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$table_id = intval($_POST['table_id'] ?? 0);
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? '');

if ($table_id <= 0 || empty($payment_method)) {
    die("Invalid payment request.");
}

// Mark all completed orders of this table as paid
mysqli_query($conn, "
    UPDATE orders 
    SET status='paid'
    WHERE table_id=$table_id AND status='completed'
");

// Free table after payment
mysqli_query($conn, "
    UPDATE restaurant_tables
    SET status='free'
    WHERE id=$table_id
");

// Calculate total if not already done (assuming sum of order amounts)
$result = mysqli_query($conn, "SELECT SUM(total_amount) AS total FROM orders WHERE table_id=$table_id AND status='paid'");
$row = mysqli_fetch_assoc($result);
$total = $row['total'] ?? 0;

echo "
<script>
alert('Payment successful!');
window.location.href = 'payment_success.php?table_id=$table_id&total=$total&method=$payment_method';
</script>
";
?>