<?php
session_start();
include("../config/db.php");

if (!isset($_POST['order_ids'])) {
    die("No orders found");
}

$order_ids = $_POST['order_ids'];
$total = floatval($_POST['total']);

foreach ($order_ids as $oid) {
    $oid = intval($oid);

    // Mark order paid
    mysqli_query($conn, "
      UPDATE orders 
      SET payment_status='paid', status='completed' 
      WHERE id=$oid
    ");

    // Insert payment record
    mysqli_query($conn, "
      INSERT INTO payments (order_id, method, amount, payment_status, paid_at)
      VALUES ($oid, 'Combined', $total, 'paid', NOW())
    ");
}

header("Location: ../pos/tables.php?success=1");
exit();