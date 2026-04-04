<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    header("Location: ../pos/index.php");
    exit();
}

$order_id = intval($_GET['order_id']);

// FIXED: prepared statement (was using raw $order_id)
$stmt = mysqli_prepare($conn, "UPDATE orders SET status='to_cook' WHERE id=? AND status='pending'");
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: kitchen.php");
exit();
?>
