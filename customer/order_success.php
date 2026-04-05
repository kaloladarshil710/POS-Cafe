<?php
include("../config/db.php");

$order_id = intval($_GET['order_id'] ?? 0);
$order_q = mysqli_query($conn, "
    SELECT o.*, rt.table_number 
    FROM orders o
    JOIN restaurant_tables rt ON o.table_id = rt.id
    WHERE o.id = $order_id
");
$order = mysqli_fetch_assoc($order_q);

if (!$order) die("Invalid order.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Success</title>
<style>
body{
    font-family:Arial,sans-serif;
    background:#f8fafc;
    display:flex;justify-content:center;align-items:center;
    min-height:100vh;margin:0;
}
.box{
    background:#fff;padding:35px;border-radius:18px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
    text-align:center;max-width:450px;width:90%;
}
h1{color:#16a34a;margin-bottom:10px;}
p{color:#4b5563;font-size:16px;line-height:1.7;}
.btn{
    display:inline-block;margin-top:20px;
    background:#C8602A;color:#fff;
    text-decoration:none;padding:12px 20px;
    border-radius:12px;font-weight:700;
}
</style>
</head>
<body>
<div class="box">
    <h1>✅ Order Sent to Kitchen</h1>
    <p><strong>Order No:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
    <p><strong>Table:</strong> <?php echo htmlspecialchars($order['table_number']); ?></p>
    <p><strong>Total:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
    <p>Your food is now being prepared.</p>

    <a class="btn" href="payment.php?table=<?php echo $order['table_id']; ?>">Proceed to Payment</a>
</div>
</body>
</html>