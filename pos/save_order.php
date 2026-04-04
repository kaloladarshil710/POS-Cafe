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

$table_id = (int) $_POST['table_id'];
$user_id = $_SESSION['user_id'];
$cart_data = $_POST['cart_data'];

if (empty($cart_data)) {
    die("Cart is empty.");
}

$cart = json_decode($cart_data, true);

if (!$cart || count($cart) == 0) {
    die("Invalid cart data.");
}

// Generate order number
$order_number = "ORD" . date("YmdHis");

// Calculate total
$total_amount = 0;
foreach ($cart as $item) {
    $subtotal = $item['price'] * $item['qty'];
    $total_amount += $subtotal;
}

// Insert into orders
$order_sql = "INSERT INTO orders (order_number, table_id, user_id, total_amount, status)
              VALUES ('$order_number', '$table_id', '$user_id', '$total_amount', 'pending')";

if (mysqli_query($conn, $order_sql)) {
    $order_id = mysqli_insert_id($conn);

    // Insert order items
    foreach ($cart as $item) {
        $product_id = (int) $item['id'];
        $product_name = mysqli_real_escape_string($conn, $item['name']);
        $quantity = (int) $item['qty'];
        $price = (float) $item['price'];
        $subtotal = $price * $quantity;

        $item_sql = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal)
                     VALUES ('$order_id', '$product_id', '$product_name', '$quantity', '$price', '$subtotal')";
        mysqli_query($conn, $item_sql);
    }

    // Update table status
    mysqli_query($conn, "UPDATE restaurant_tables SET status='occupied' WHERE id='$table_id'");

    header("Location: order_success.php?order_id=$order_id");
    exit();
} else {
    die("Failed to save order.");
}
?>