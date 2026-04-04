<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$order_id = (int) $_POST['order_id'];
$method = $_POST['method'];

// Get order
$order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id=$order_id"));

if (!$order) {
    die("Invalid order");
}

// Save payment
mysqli_query($conn, "
    INSERT INTO payments (order_id, payment_method, amount)
    VALUES ('$order_id', '$method', '{$order['total_amount']}')
");

// Update order status
mysqli_query($conn, "UPDATE orders SET status='paid' WHERE id='$order_id'");

// Free table
mysqli_query($conn, "UPDATE restaurant_tables SET status='free' WHERE id='{$order['table_id']}'");

header("Location: payment_success.php?order_id=$order_id");
exit();
?>