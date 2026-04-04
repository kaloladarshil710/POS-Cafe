<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    header("Location: index.php");
    exit();
}

$order_id = (int) $_GET['order_id'];

$order_query = mysqli_query($conn, "
    SELECT o.*, rt.table_number
    FROM orders o
    JOIN restaurant_tables rt ON o.table_id = rt.id
    WHERE o.id = $order_id
");

$order = mysqli_fetch_assoc($order_query);

if (!$order) {
    header("Location: index.php");
    exit();
}

$payments = mysqli_query($conn, "SELECT * FROM payment_methods WHERE is_enabled='yes'");
?>

<!DOCTYPE html>
<html>
<head>
<title>Payment</title>
<style>
body{font-family:Poppins;background:#f1f5f9;padding:30px;}
.box{max-width:800px;margin:auto;background:white;padding:30px;border-radius:20px;}
.method{padding:15px;border:1px solid #ddd;margin-bottom:10px;border-radius:12px;cursor:pointer;}
.method:hover{background:#f8fafc;}
.total{font-size:28px;font-weight:bold;margin:20px 0;}
.btn{background:#2563eb;color:white;padding:12px 20px;border:none;border-radius:10px;cursor:pointer;}
.qr{margin-top:20px;text-align:center;}
</style>
</head>

<body>

<div class="box">
<h2>💳 Payment</h2>

<p><strong>Order:</strong> <?php echo $order['order_number']; ?></p>
<p><strong>Table:</strong> <?php echo $order['table_number']; ?></p>

<div class="total">₹<?php echo number_format($order['total_amount'],2); ?></div>

<form action="process_payment.php" method="POST">
<input type="hidden" name="order_id" value="<?php echo $order_id; ?>">

<?php while($row = mysqli_fetch_assoc($payments)) { ?>
    <div class="method">
        <input type="radio" name="method" value="<?php echo $row['method_name']; ?>" required>
        <?php echo $row['method_name']; ?>
    </div>

    <?php if($row['method_name'] == 'UPI') { ?>
        <div class="qr" id="qrBox" style="display:none;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=upi://pay?pa=<?php echo $row['upi_id']; ?>&am=<?php echo $order['total_amount']; ?>">
            <p>Scan & Pay via UPI</p>
        </div>
    <?php } ?>
<?php } ?>

<br>
<button class="btn">Confirm Payment</button>
</form>

</div>

<script>
document.querySelectorAll('input[name="method"]').forEach(el=>{
    el.addEventListener('change', function(){
        if(this.value === "UPI"){
            document.getElementById('qrBox').style.display = "block";
        } else {
            document.getElementById('qrBox').style.display = "none";
        }
    });
});
</script>

</body>
</html>
