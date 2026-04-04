<?php
session_start();
include("../config/db.php");

$order_id = (int) $_GET['order_id'];

$order = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT o.*, rt.table_number 
    FROM orders o
    JOIN restaurant_tables rt ON o.table_id = rt.id
    WHERE o.id=$order_id
"));
?>

<!DOCTYPE html>
<html>
<head>
<title>Payment Success</title>
<style>
body{font-family:Poppins;background:#ecfeff;display:flex;justify-content:center;align-items:center;height:100vh;}
.box{background:white;padding:40px;border-radius:20px;text-align:center;}
h1{color:green;}
.btn{background:#2563eb;color:white;padding:12px 20px;text-decoration:none;border-radius:10px;}
</style>
</head>

<body>

<div class="box">
<h1>✅ Payment Successful</h1>

<p>Order: <?php echo $order['order_number']; ?></p>
<p>Table: <?php echo $order['table_number']; ?></p>
<p>Amount Paid: ₹<?php echo number_format($order['total_amount'],2); ?></p>

<br>
<a href="index.php" class="btn">Back to POS</a>
</div>

</body>
</html>