<?php
session_start();
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$table_id  = intval($_POST['table_id'] ?? 0);
$cart_data = $_POST['cart_data'] ?? '';

if (empty($cart_data)) {
    die("Cart is empty.");
}

$cart = json_decode($cart_data, true);
if (!$cart || count($cart) === 0) {
    die("Invalid cart data.");
}

// Check valid table
$table_q = mysqli_query($conn, "SELECT * FROM restaurant_tables WHERE id=$table_id AND active='yes'");
$table = mysqli_fetch_assoc($table_q);
if (!$table) {
    die("Invalid table.");
}

// Calculate total
$total_amount = 0;
foreach ($cart as $item) {
    $total_amount += floatval($item['price']) * intval($item['qty']);
}

$order_number = "QR" . strtoupper(substr(uniqid(), -6)) . date("His");

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Insert into orders
    $stmt = mysqli_prepare($conn, "
        INSERT INTO orders (order_number, table_id, user_id, total_amount, status, order_source)
        VALUES (?, ?, NULL, ?, 'to_cook', 'customer')
    ");

    if (!$stmt) {
        throw new Exception("Order prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "sid", $order_number, $table_id, $total_amount);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Order insert failed: " . mysqli_stmt_error($stmt));
    }

    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Insert order items
    $item_stmt = mysqli_prepare($conn, "
        INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$item_stmt) {
        throw new Exception("Order items prepare failed: " . mysqli_error($conn));
    }

    foreach ($cart as $item) {
        $product_id   = intval($item['id']);
        $product_name = substr(trim($item['name']), 0, 120);
        $quantity     = intval($item['qty']);
        $price        = floatval($item['price']);
        $subtotal     = $price * $quantity;

        mysqli_stmt_bind_param($item_stmt, "iisidd", $order_id, $product_id, $product_name, $quantity, $price, $subtotal);

        if (!mysqli_stmt_execute($item_stmt)) {
            throw new Exception("Order item insert failed: " . mysqli_stmt_error($item_stmt));
        }
    }

    mysqli_stmt_close($item_stmt);

    // Update table status
    mysqli_query($conn, "UPDATE restaurant_tables SET status='occupied' WHERE id=$table_id");

    // Commit transaction
    mysqli_commit($conn);

    header("Location: order_success.php?order_id=$order_id");
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    die("Failed to place order: " . $e->getMessage());
}
?>