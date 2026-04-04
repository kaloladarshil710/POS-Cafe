<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

// Fetch orders for each stage
$to_cook_q   = mysqli_query($conn, "SELECT o.*, rt.table_number FROM orders o JOIN restaurant_tables rt ON o.table_id=rt.id WHERE o.status='to_cook' ORDER BY o.created_at ASC");
$preparing_q = mysqli_query($conn, "SELECT o.*, rt.table_number FROM orders o JOIN restaurant_tables rt ON o.table_id=rt.id WHERE o.status='preparing' ORDER BY o.created_at ASC");
$completed_q = mysqli_query($conn, "SELECT o.*, rt.table_number FROM orders o JOIN restaurant_tables rt ON o.table_id=rt.id WHERE o.status='completed' ORDER BY o.created_at DESC LIMIT 10");

$count_cook  = mysqli_num_rows($to_cook_q);
$count_prep  = mysqli_num_rows($preparing_q);
$count_done  = mysqli_num_rows($completed_q);

// Helper to render a single order ticket
function renderTicket($conn, $order) {
    $oid   = $order['id'];
    $items = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id=$oid ORDER BY id ASC");
    $status = $order['status'];
    $mins   = round((time() - strtotime($order['created_at'])) / 60);
    $is_urgent = $mins >= 10 && $status === 'to_cook';
    ?>
    <div class="ticket <?php echo $is_urgent ? 'urgent' : ''; ?>">
        <div class="ticket-top">
            <div>
                <div class="ticket-num">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                <div class="ticket-table">🪑 <?php echo htmlspecialchars($order['table_number']); ?></div>
            </div>
            <div style="text-align:right;">
                <div class="ticket-time"><?php echo date("h:i A", strtotime($order['created_at'])); ?></div>
                <div class="ticket-age <?php echo $is_urgent ? 'urgent-text' : ''; ?>"><?php echo $mins; ?>m ago</div>
            </div>
        </div>

        <div class="items-list">
            <?php while ($item = mysqli_fetch_assoc($items)): ?>
            <a class="item-row <?php echo $item['item_status'] === 'prepared' ? 'item-done' : ''; ?>"
               href="toggle_item_status.php?item_id=<?php echo $item['id']; ?>&return=<?php echo $oid; ?>">
                <span class="item-qty"><?php echo $item['quantity']; ?>×</span>
                <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                <span class="item-check"><?php echo $item['item_status'] === 'prepared' ? '✓' : '○'; ?></span>
            </a>
            <?php endwhile; ?>
        </div>

        <div class="ticket-footer">
            <div class="ticket-total">₹<?php echo number_format($order['total_amount'], 2); ?></div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php if ($status !== 'completed'): ?>
                    <a class="action-btn btn-next" href="update_order_status.php?order_id=<?php echo $oid; ?>">
                        <?php echo $status === 'to_cook' ? '🍳 Start Cooking' : '✅ Mark Ready'; ?>
                    </a>
                <?php else: ?>
                    <a class="action-btn btn-pay" href="../pos/payment.php?order_id=<?php echo $oid; ?>">
                        💳 Take Payment
                    </a>
                    <span class="badge-ready">✅ Ready</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- FIXED: auto-refresh every 10 seconds for real-time updates -->
    <meta http-equiv="refresh" content="10">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display — POS Cafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
        :root{--bg:#060A14;--col1:rgba(255,107,53,0.12);--col2:rgba(245,158,11,0.1);--col3:rgba(34,197,94,0.1);--border:rgba(255,255,255,0.07);--text:#F5F5F5;--muted:#888;}
        body{font-family:'Sora',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column;}

        .topbar{background:rgba(255,255,255,0.03);border-bottom:1px solid var(--border);padding:16px 28px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;flex-shrink:0;backdrop-filter:blur(14px);}
        .topbar-left h1{font-size:22px;font-weight:800;}
        .topbar-left p{font-size:13px;color:var(--muted);margin-top:3px;}
        .topbar-right{display:flex;gap:10px;}
        .btn{text-decoration:none;padding:10px 16px;border-radius:12px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:7px;transition:0.2s;border:1px solid var(--border);background:rgba(255,255,255,0.05);color:var(--text);}
        .btn:hover{background:rgba(255,255,255,0.1);}

        .refresh-bar{background:rgba(255,255,255,0.02);padding:8px 28px;border-bottom:1px solid var(--border);font-size:12px;color:var(--muted);display:flex;align-items:center;gap:8px;}
        .pulse{width:8px;height:8px;background:#22c55e;border-radius:50%;animation:pulse 2s infinite;}
        @keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.3;}}

        .board{display:grid;grid-template-columns:repeat(3,1fr);gap:0;flex:1;}
        .column{border-right:1px solid var(--border);padding:20px 18px;overflow-y:auto;}
        .column:last-child{border-right:none;}
        .col-cook{background:rgba(255,107,53,0.04);}
        .col-prep{background:rgba(245,158,11,0.04);}
        .col-done{background:rgba(34,197,94,0.04);}

        .col-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border);}
        .col-title{font-size:16px;font-weight:800;}
        .col-count{padding:4px 12px;border-radius:999px;font-size:13px;font-weight:700;}
        .count-cook{background:rgba(255,107,53,0.2);color:#FF6B35;}
        .count-prep{background:rgba(245,158,11,0.2);color:#F59E0B;}
        .count-done{background:rgba(34,197,94,0.2);color:#22c55e;}

        .ticket{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:18px;padding:16px;margin-bottom:14px;transition:0.2s;}
        .ticket.urgent{border-color:rgba(239,68,68,0.5);background:rgba(239,68,68,0.08);}
        .ticket-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;}
        .ticket-num{font-size:15px;font-weight:800;margin-bottom:3px;}
        .ticket-table{font-size:13px;color:var(--muted);}
        .ticket-time{font-size:13px;font-weight:700;}
        .ticket-age{font-size:12px;color:var(--muted);margin-top:3px;}
        .urgent-text{color:#f87171;font-weight:700;}

        .items-list{display:flex;flex-direction:column;gap:7px;margin-bottom:14px;}
        .item-row{text-decoration:none;color:var(--text);display:flex;align-items:center;gap:10px;padding:10px 12px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);border-radius:12px;transition:0.15s;cursor:pointer;}
        .item-row:hover{background:rgba(255,255,255,0.09);}
        .item-done{opacity:0.5;text-decoration:line-through;}
        .item-qty{font-size:13px;font-weight:800;color:#FF6B35;min-width:24px;}
        .item-name{flex:1;font-size:13px;font-weight:500;}
        .item-check{font-size:14px;font-weight:700;color:#4ade80;}

        .ticket-footer{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;}
        .ticket-total{font-size:14px;font-weight:700;}
        .action-btn{text-decoration:none;padding:8px 14px;border-radius:10px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:5px;transition:0.2s;}
        .btn-next{background:linear-gradient(135deg,#F59E0B,#D97706);color:white;}
        .btn-next:hover{transform:translateY(-1px);}
        .btn-pay{background:linear-gradient(135deg,#3B82F6,#2563EB);color:white;}
        .btn-pay:hover{transform:translateY(-1px);}
        .badge-ready{background:rgba(34,197,94,0.2);color:#4ade80;padding:8px 12px;border-radius:10px;font-size:12px;font-weight:700;}

        .empty-col{text-align:center;padding:32px 12px;color:var(--muted);}
        .empty-col-icon{font-size:36px;margin-bottom:10px;}

        @media(max-width:900px){.board{grid-template-columns:1fr;}.column{border-right:none;border-bottom:1px solid var(--border);}}
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <h1>👨‍🍳 Kitchen Display System</h1>
        <p>Live order board — auto-refreshes every 10 seconds</p>
    </div>
    <div class="topbar-right">
        <a class="btn" href="../pos/index.php">🚀 POS Terminal</a>
        <a class="btn" href="../admin/dashboard.php">⚙️ Admin</a>
    </div>
</div>

<div class="refresh-bar">
    <div class="pulse"></div>
    Live · Last updated: <?php echo date("h:i:s A"); ?>
</div>

<div class="board">
    <!-- TO COOK -->
    <div class="column col-cook">
        <div class="col-header">
            <div class="col-title">🔥 To Cook</div>
            <span class="col-count count-cook"><?php echo $count_cook; ?></span>
        </div>
        <?php if ($count_cook > 0):
            while ($o = mysqli_fetch_assoc($to_cook_q)) renderTicket($conn, $o);
        else: ?>
            <div class="empty-col"><div class="empty-col-icon">😴</div><p>No orders waiting</p></div>
        <?php endif; ?>
    </div>

    <!-- PREPARING -->
    <div class="column col-prep">
        <div class="col-header">
            <div class="col-title">🍳 Preparing</div>
            <span class="col-count count-prep"><?php echo $count_prep; ?></span>
        </div>
        <?php if ($count_prep > 0):
            while ($o = mysqli_fetch_assoc($preparing_q)) renderTicket($conn, $o);
        else: ?>
            <div class="empty-col"><div class="empty-col-icon">🍴</div><p>Nothing being prepared</p></div>
        <?php endif; ?>
    </div>

    <!-- COMPLETED -->
    <div class="column col-done">
        <div class="col-header">
            <div class="col-title">✅ Completed</div>
            <span class="col-count count-done"><?php echo $count_done; ?></span>
        </div>
        <?php if ($count_done > 0):
            while ($o = mysqli_fetch_assoc($completed_q)) renderTicket($conn, $o);
        else: ?>
            <div class="empty-col"><div class="empty-col-icon">🏁</div><p>No completed orders yet</p></div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>