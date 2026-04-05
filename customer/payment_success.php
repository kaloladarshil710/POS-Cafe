<?php
include("../config/db.php");

$table_id = intval($_GET['table_id'] ?? 0);
$total = htmlspecialchars($_GET['total'] ?? '0.00');
$method = htmlspecialchars($_GET['method'] ?? 'Cash');
$orders = intval($_GET['orders'] ?? 1);

$table = null;
if ($table_id) {
    $table = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM restaurant_tables WHERE id=$table_id"));
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Payment Successful</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700;800&display=swap" rel="stylesheet">
<style>
body{
    font-family:'DM Sans',sans-serif;
    background:#F5F7FA;
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:100vh;
}
.card{
    background:#fff;
    padding:40px;
    border-radius:24px;
    text-align:center;
    width:420px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
}
.icon{
    font-size:60px;
    margin-bottom:15px;
}
h1{
    font-size:26px;
    margin-bottom:8px;
}
.sub{
    color:#6b7280;
    margin-bottom:25px;
}
.box{
    background:#f9fafb;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:14px;
    margin-bottom:10px;
}
.total{
    color:#C8602A;
    font-weight:800;
    font-size:22px;
}
.btn{
    display:block;
    margin-top:15px;
    padding:14px;
    background:#C8602A;
    color:#fff;
    border-radius:12px;
    text-decoration:none;
    font-weight:700;
}
</style>
</head>
<body>

<div class="card">
    <div class="icon">✅</div>

    <h1>Payment Successful</h1>
    <p class="sub">
        <?php echo $table ? htmlspecialchars($table['table_number']) : 'Table'; ?> is now free 🎉
    </p>

    <div class="box">
        Amount Paid<br>
        <span class="total">₹<?php echo $total; ?></span>
    </div>

    <div class="box">
        Payment Method<br>
        <strong><?php echo $method; ?></strong>
    </div>

    <div class="box">
        Orders Settled<br>
        <strong><?php echo $orders; ?> order<?php echo $orders>1?'s':''; ?></strong>
    </div>

    <a href="menu.php?table=<?php echo $table_id; ?>" class="btn">
        Order Again 🍽
    </a>
</div>

<script>
// Auto redirect after 10 sec (optional)
setTimeout(()=>{
    window.location.href = "menu.php?table=<?php echo $table_id; ?>";
},10000);
</script>

</body>
</html>