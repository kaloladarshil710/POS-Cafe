<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: index.php"); exit(); }

$table_id  = intval($_POST['table_id'] ?? 0);
$user_id   = intval($_SESSION['user_id']);
$cart_data = $_POST['cart_data'] ?? '';

if (empty($cart_data)) { die("Cart is empty."); }

$cart = json_decode($cart_data, true);
if (!$cart || count($cart) === 0) { die("Invalid cart data."); }

// Validate table exists
$table_q = mysqli_query($conn, "SELECT id FROM restaurant_tables WHERE id=$table_id AND active='yes'");
if (mysqli_num_rows($table_q) === 0) { die("Invalid table."); }

// Calculate total
$total_amount = 0;
foreach ($cart as $item) {
    $total_amount += floatval($item['price']) * intval($item['qty']);
}

// Generate unique order number
$order_number = "ORD" . strtoupper(substr(uniqid(), -6)) . date("His");

// Insert order — FIXED: prepared statement
$stmt = mysqli_prepare($conn, "INSERT INTO orders (order_number, table_id, user_id, total_amount, status) VALUES (?, ?, ?, ?, 'pending')");
mysqli_stmt_bind_param($stmt, "siid", $order_number, $table_id, $user_id, $total_amount);

if (mysqli_stmt_execute($stmt)) {
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Insert items — FIXED: prepared statement
    $item_stmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($cart as $item) {
        $product_id   = intval($item['id']);
        $product_name = substr(trim($item['name']), 0, 120);
        $quantity     = intval($item['qty']);
        $price        = floatval($item['price']);
        $subtotal     = $price * $quantity;

        mysqli_stmt_bind_param($item_stmt, "iisidd", $order_id, $product_id, $product_name, $quantity, $price, $subtotal);
        mysqli_stmt_execute($item_stmt);
    }
    mysqli_stmt_close($item_stmt);

    // Mark table as occupied
    $upd = mysqli_prepare($conn, "UPDATE restaurant_tables SET status='occupied' WHERE id=?");
    mysqli_stmt_bind_param($upd, "i", $table_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    header("Location: order_success.php?order_id=$order_id");
    exit();
} else {
    die("Failed to save order: " . mysqli_error($conn));
}
?>