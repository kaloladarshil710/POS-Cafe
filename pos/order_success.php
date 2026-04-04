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

$order_items = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = $order_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Poppins', sans-serif;
        }

        body{
            background: linear-gradient(135deg, #ecfeff, #eff6ff);
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            padding:25px;
        }

        .box{
            width:100%;
            max-width:700px;
            background:white;
            border-radius:28px;
            padding:32px;
            box-shadow: 0 25px 55px rgba(15, 23, 42, 0.08);
        }

        .success{
            text-align:center;
            margin-bottom:28px;
        }

        .success h1{
            font-size:34px;
            color:#059669;
            margin-bottom:10px;
        }

        .success p{
            color:#64748b;
        }

        .info{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:16px;
            margin-bottom:28px;
        }

        .info-card{
            background:#f8fafc;
            border-radius:18px;
            padding:18px;
        }

        .info-card h4{
            color:#64748b;
            font-size:14px;
            margin-bottom:6px;
        }

        .info-card p{
            font-size:20px;
            font-weight:700;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-bottom:28px;
        }

        table th{
            background:#eff6ff;
            color:#1d4ed8;
            padding:14px;
            text-align:left;
        }

        table td{
            padding:14px;
            border-bottom:1px solid #e2e8f0;
        }

        .btns{
            display:flex;
            gap:14px;
            flex-wrap:wrap;
        }

        .btn{
            text-decoration:none;
            padding:14px 18px;
            border-radius:14px;
            font-weight:700;
            display:inline-block;
        }

        .primary{
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            color:white;
        }

        .secondary{
            background: #0f172a;
            color:white;
        }
    </style>
</head>
<body>

<div class="box">
    <div class="success">
        <h1>✅ Order Placed Successfully</h1>
        <p>The order has been saved in the system</p>
    </div>

    <div class="info">
        <div class="info-card">
            <h4>Order Number</h4>
            <p><?php echo htmlspecialchars($order['order_number']); ?></p>
        </div>

        <div class="info-card">
            <h4>Table</h4>
            <p><?php echo htmlspecialchars($order['table_number']); ?></p>
        </div>

        <div class="info-card">
            <h4>Status</h4>
            <p><?php echo ucfirst($order['status']); ?></p>
        </div>

        <div class="info-card">
            <h4>Total Amount</h4>
            <p>₹<?php echo number_format($order['total_amount'], 2); ?></p>
        </div>
    </div>

    <table>
        <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Subtotal</th>
        </tr>

        <?php while($item = mysqli_fetch_assoc($order_items)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
            <td><?php echo $item['quantity']; ?></td>
            <td>₹<?php echo number_format($item['price'], 2); ?></td>
            <td>₹<?php echo number_format($item['subtotal'], 2); ?></td>
        </tr>
        <?php } ?>
    </table>

 <div class="btns">
    <a href="index.php" class="btn primary">← Back to Tables</a>
    <a href="order.php?table_id=<?php echo $order['table_id']; ?>" class="btn secondary">Add More Items</a>
</div>
</div>  

</body>
</html>