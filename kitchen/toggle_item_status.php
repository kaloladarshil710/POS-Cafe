<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['item_id'])) {
    header("Location: kitchen.php");
    exit();
}

$item_id = (int) $_GET['item_id'];

$query = mysqli_query($conn, "SELECT item_status FROM order_items WHERE id = $item_id");
$item = mysqli_fetch_assoc($query);

if ($item) {
    $new_status = ($item['item_status'] == 'pending') ? 'prepared' : 'pending';
    mysqli_query($conn, "UPDATE order_items SET item_status = '$new_status' WHERE id = $item_id");
}

header("Location: kitchen.php");
exit();
?>