<?php
session_start();
include("../config/db.php");

if (!isset($_GET['table_id'])) {
 die("Table ID missing");
}

$table_id = intval($_GET['table_id']);

// Get table name
$tq = mysqli_query($conn, "SELECT table_number FROM tables WHERE id=$table_id LIMIT 1");
$table = mysqli_fetch_assoc($tq);

// Get ALL unpaid orders for this table
$orders = mysqli_query($conn, "
SELECT * FROM orders 
WHERE table_id = $table_id 
AND (payment_status IS NULL OR payment_status='unpaid')
ORDER BY created_at ASC
");

$total_amount = 0;
$order_ids = [];

?>
<!DOCTYPE html>
<html>
<head>
 <title>Table Payment</title>
 <style>
 body{
 background:#0B0F19;
 color:white;
 font-family:sans-serif;
 padding:30px;
 }
 .card{
 max-width:600px;
 margin:auto;
 background:#111827;
 padding:20px;
 border-radius:16px;
 }
 .row{
 display:flex;
 justify-content:space-between;
 padding:8px 0;
 border-bottom:1px solid rgba(255,255,255,0.05);
 }
 .total{
 font-size:22px;
 font-weight:bold;
 margin-top:15px;
 }
 .btn{
 margin-top:20px;
 width:100%;
 padding:14px;
 background:#22c55e;
 border:none;
 border-radius:10px;
 color:white;
 font-size:16px;
 cursor:pointer;
 }
 </style>
</head>
<body>

<div class="card">
 <h2>🪑 Table <?php echo $table['table_number']; ?></h2>

 <?php while($order = mysqli_fetch_assoc($orders)): 
 $order_ids[] = $order['id'];

 $items = mysqli_query($conn, "
 SELECT oi.*, p.name 
 FROM order_items oi 
 JOIN products p ON oi.product_id=p.id 
 WHERE oi.order_id=".$order['id']
 );

 while($item = mysqli_fetch_assoc($items)):
 $subtotal = $item['quantity'] * $item['price'];
 $total_amount += $subtotal;
 ?>

 <div class="row">
 <span><?php echo $item['name']; ?> (x<?php echo $item['quantity']; ?>)</span>
 <span>₹<?php echo number_format($subtotal,2); ?></span>
 </div>

 <?php endwhile; endwhile; ?>

 <div class="total">
 Total: ₹<?php echo number_format($total_amount,2); ?>
 </div>

 <form action="process_table_payment.php" method="POST">
 <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
 <input type="hidden" name="total" value="<?php echo $total_amount; ?>">

 <?php foreach($order_ids as $oid): ?>
 <input type="hidden" name="order_ids[]" value="<?php echo $oid; ?>">
 <?php endforeach; ?>

 <button class="btn"> Pay Total</button>
 </form>
</div>

</body>
</html>