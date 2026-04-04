<?php
session_start();
include("../config/db.php");

// FIXED: Added missing auth check
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: index.php"); exit(); }

$order_id = intval($_POST['order_id'] ?? 0);
$method   = trim($_POST['method'] ?? '');

// Validate method
$allowed_methods = ['Cash', 'Digital', 'UPI'];
if (!in_array($method, $allowed_methods)) {
    die("Invalid payment method.");
}

// Get order — FIXED: prepared statement
$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) { die("Order not found."); }
if ($order['status'] === 'paid') { header("Location: payment_success.php?order_id=$order_id"); exit(); }

// Record payment — FIXED: prepared statement
$pay_stmt = mysqli_prepare($conn, "INSERT INTO payments (order_id, payment_method, amount, status) VALUES (?, ?, ?, 'paid')");
mysqli_stmt_bind_param($pay_stmt, "isd", $order_id, $method, $order['total_amount']);
mysqli_stmt_execute($pay_stmt);
mysqli_stmt_close($pay_stmt);

// Update order status to paid
$upd = mysqli_prepare($conn, "UPDATE orders SET status='paid' WHERE id=?");
mysqli_stmt_bind_param($upd, "i", $order_id);
mysqli_stmt_execute($upd);
mysqli_stmt_close($upd);

// Free the table
$tbl = mysqli_prepare($conn, "UPDATE restaurant_tables SET status='free' WHERE id=?");
mysqli_stmt_bind_param($tbl, "i", $order['table_id']);
mysqli_stmt_execute($tbl);
mysqli_stmt_close($tbl);

header("Location: payment_success.php?order_id=$order_id");
exit();
?>