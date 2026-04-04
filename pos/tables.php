<style>.btn-pay{
  margin-top:8px;
  display:block;
  padding:10px;
  text-align:center;
  background:#22c55e;
  border-radius:10px;
  color:white;
  text-decoration:none;
  font-size:14px;
}</style>
<a href="table_payment.php?table_id=<?php echo $table['id']; ?>" class="btn-pay">
  💳 Pay Table
</a>
<?php
// check if table has unpaid orders
$tid = $table['id'];
$check = mysqli_query($conn, "
  SELECT id FROM orders 
  WHERE table_id=$tid 
  AND (payment_status IS NULL OR payment_status='unpaid')
  LIMIT 1
");
if(mysqli_num_rows($check) > 0):
?>
  <a href="table_payment.php?table_id=<?php echo $table['id']; ?>" class="btn-pay">
    💳 Pay
  </a>
<?php endif; ?>