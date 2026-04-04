<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    header("Location: kitchen.php");
    exit();
}

$order_id = (int) $_GET['order_id'];

$query = mysqli_query($conn, "SELECT status FROM orders WHERE id = $order_id");
$order = mysqli_fetch_assoc($query);

if ($order) {
    $new_status = $order['status'];

    if ($order['status'] == 'to_cook') {
        $new_status = 'preparing';
    } elseif ($order['status'] == 'preparing') {
        $new_status = 'completed';
    }

    mysqli_query($conn, "UPDATE orders SET status = '$new_status' WHERE id = $order_id");
}

header("Location: kitchen.php");
exit();
?>