<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) { 
    header("Location: ../auth/login.php"); 
    exit(); 
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    header("Location: index.php"); 
    exit(); 
}

$order_ids = $_POST['order_ids'] ?? [];
$table_id  = intval($_POST['table_id'] ?? 0);
$total     = floatval($_POST['total'] ?? 0);
$method    = trim($_POST['method'] ?? '');

// Validate
$allowed = ['Cash','Digital','UPI'];
if (empty($order_ids) || !in_array($method, $allowed) || $table_id < 1) {
    die("Invalid request.");
}

// Prepare statements
$pay_stmt = mysqli_prepare($conn, "INSERT INTO payments (order_id, payment_method, amount, status) VALUES (?,?,?,'paid')");
// $upd_stmt = mysqli_prepare($conn, "UPDATE orders SET payment_status='paid' WHERE id=?");
$upd_stmt = mysqli_prepare($conn, "UPDATE orders SET status='paid' WHERE id=?");

foreach ($order_ids as $raw_oid) {
    $oid = intval($raw_oid);
    if ($oid < 1) continue;

    // Get order amount
    $ord_q = mysqli_query($conn, "SELECT total_amount FROM orders WHERE id=$oid");
    $ord = mysqli_fetch_assoc($ord_q);

    if (!$ord) continue;
    $amt = floatval($ord['total_amount']);

    // Insert payment record
    mysqli_stmt_bind_param($pay_stmt, "isd", $oid, $method, $amt);
    mysqli_stmt_execute($pay_stmt);

    // Mark order paid
    mysqli_stmt_bind_param($upd_stmt, "i", $oid);
    mysqli_stmt_execute($upd_stmt);
}

mysqli_stmt_close($pay_stmt);
mysqli_stmt_close($upd_stmt);

// Free the table + reset occupied time
$free = mysqli_prepare($conn, "UPDATE restaurant_tables SET status='free', occupied_at=NULL WHERE id=?");
mysqli_stmt_bind_param($free, "i", $table_id);
mysqli_stmt_execute($free);
mysqli_stmt_close($free);

// Redirect to success page
header("Location: table_bill_success.php?table_id=$table_id&total=" . number_format($total,2,'.','') . "&method=" . urlencode($method) . "&orders=" . count($order_ids));
exit();
?>