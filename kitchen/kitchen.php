<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$to_cook_orders = mysqli_query($conn, "
    SELECT o.*, rt.table_number
    FROM orders o
    JOIN restaurant_tables rt ON o.table_id = rt.id
    WHERE o.status = 'to_cook'
    ORDER BY o.created_at DESC
");

$preparing_orders = mysqli_query($conn, "
    SELECT o.*, rt.table_number
    FROM orders o
    JOIN restaurant_tables rt ON o.table_id = rt.id
    WHERE o.status = 'preparing'
    ORDER BY o.created_at DESC
");

$completed_orders = mysqli_query($conn, "
    SELECT o.*, rt.table_number
    FROM orders o
    JOIN restaurant_tables rt ON o.table_id = rt.id
    WHERE o.status = 'completed'
    ORDER BY o.created_at DESC
");

function renderOrderCard($conn, $order) {
    $order_id = $order['id'];
    $items = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = $order_id");
?>
    <div class="ticket">
        <div class="ticket-top">
            <div>
                <h3>#<?php echo htmlspecialchars($order['order_number']); ?></h3>
                <p>Table: <?php echo htmlspecialchars($order['table_number']); ?></p>
            </div>
            <div class="ticket-time">
                <?php echo date("h:i A", strtotime($order['created_at'])); ?>
            </div>
        </div>

        <div class="items">
            <?php while($item = mysqli_fetch_assoc($items)) { ?>
                <a class="item <?php echo ($item['item_status'] == 'prepared') ? 'prepared' : ''; ?>"
                   href="toggle_item_status.php?item_id=<?php echo $item['id']; ?>">
                    <span><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['product_name']); ?></span>
                    <span><?php echo ($item['item_status'] == 'prepared') ? '✔' : '•'; ?></span>
                </a>
            <?php } ?>
        </div>

        <div class="ticket-footer">
            <div><strong>Total:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></div>

            <?php if($order['status'] != 'completed') { ?>
                <a class="next-btn" href="update_order_status.php?order_id=<?php echo $order['id']; ?>">
                    Move Next →
                </a>
            <?php } else { ?>
                <span class="done-badge">Ready</span>
            <?php } ?>
        </div>
    </div>
<?php
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Poppins', sans-serif;
        }

        body{
            background: linear-gradient(135deg, #020617, #0f172a);
            color:white;
            min-height:100vh;
            padding:28px;
        }

        .topbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:28px;
            flex-wrap:wrap;
            gap:15px;
        }

        .topbar h1{
            font-size:32px;
            font-weight:700;
        }

        .topbar p{
            color:#94a3b8;
            margin-top:6px;
        }

        .btn{
            text-decoration:none;
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            color:white;
            padding:12px 18px;
            border-radius:14px;
            font-weight:600;
        }

        .board{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:22px;
        }

        .column{
            background: rgba(255,255,255,0.05);
            border:1px solid rgba(255,255,255,0.08);
            border-radius:28px;
            padding:22px;
            min-height:75vh;
            backdrop-filter: blur(12px);
        }

        .column h2{
            font-size:24px;
            margin-bottom:18px;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }

        .count{
            background: rgba(255,255,255,0.12);
            padding:8px 14px;
            border-radius:999px;
            font-size:13px;
        }

        .ticket{
            background: rgba(255,255,255,0.08);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:22px;
            padding:18px;
            margin-bottom:18px;
            box-shadow: 0 12px 28px rgba(0,0,0,0.18);
        }

        .ticket-top{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            margin-bottom:14px;
            gap:12px;
        }

        .ticket-top h3{
            font-size:18px;
            margin-bottom:6px;
        }

        .ticket-top p{
            color:#cbd5e1;
            font-size:14px;
        }

        .ticket-time{
            background: rgba(255,255,255,0.08);
            padding:8px 12px;
            border-radius:12px;
            font-size:12px;
            color:#cbd5e1;
        }

        .items{
            display:flex;
            flex-direction:column;
            gap:10px;
            margin-bottom:18px;
        }

        .item{
            text-decoration:none;
            color:white;
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:12px 14px;
            border-radius:14px;
            background: rgba(255,255,255,0.06);
            transition:0.2s ease;
        }

        .item:hover{
            transform:translateX(3px);
        }

        .item.prepared{
            text-decoration: line-through;
            opacity:0.6;
            background: rgba(34,197,94,0.18);
        }

        .ticket-footer{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            flex-wrap:wrap;
        }

        .next-btn{
            text-decoration:none;
            background: linear-gradient(90deg, #f59e0b, #ef4444);
            color:white;
            padding:10px 14px;
            border-radius:12px;
            font-weight:700;
        }

        .done-badge{
            background:#22c55e;
            color:white;
            padding:10px 14px;
            border-radius:12px;
            font-weight:700;
        }

        .empty{
            color:#94a3b8;
            text-align:center;
            padding:28px 10px;
            background: rgba(255,255,255,0.04);
            border-radius:18px;
        }

        @media(max-width:1100px){
            .board{
                grid-template-columns:1fr;
            }

            .column{
                min-height:auto;
            }
        }
    </style>
</head>
<body>

    <div class="topbar">
        <div>
            <h1>👨‍🍳 Kitchen Display System</h1>
            <p>Live order workflow for restaurant kitchen</p>
        </div>

        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <a href="../pos/index.php" class="btn">← POS Terminal</a>
            <a href="../admin/dashboard.php" class="btn">Admin Panel</a>
        </div>
    </div>

    <div class="board">
        <!-- TO COOK -->
        <div class="column">
            <h2>🔥 To Cook <span class="count"><?php echo mysqli_num_rows($to_cook_orders); ?></span></h2>
            <?php
            if (mysqli_num_rows($to_cook_orders) > 0) {
                while($order = mysqli_fetch_assoc($to_cook_orders)) {
                    renderOrderCard($conn, $order);
                }
            } else {
                echo "<div class='empty'>No new orders</div>";
            }
            ?>
        </div>

        <!-- PREPARING -->
        <div class="column">
            <h2>🍳 Preparing <span class="count"><?php echo mysqli_num_rows($preparing_orders); ?></span></h2>
            <?php
            if (mysqli_num_rows($preparing_orders) > 0) {
                while($order = mysqli_fetch_assoc($preparing_orders)) {
                    renderOrderCard($conn, $order);
                }
            } else {
                echo "<div class='empty'>No orders being prepared</div>";
            }
            ?>
        </div>

        <!-- COMPLETED -->
        <div class="column">
            <h2>✅ Completed <span class="count"><?php echo mysqli_num_rows($completed_orders); ?></span></h2>
            <?php
            if (mysqli_num_rows($completed_orders) > 0) {
                while($order = mysqli_fetch_assoc($completed_orders)) {
                    renderOrderCard($conn, $order);
                }
            } else {
                echo "<div class='empty'>No completed orders</div>";
            }
            ?>
            <span class="done-badge">Ready</span>
            <a class="next-btn" href="../pos/payment.php?order_id=<?php echo $order['id']; ?>">
    💳 Take Payment
</a>
        </div>
    </div>

</body>
</html>