<?php
include("../config/db.php");
include("layout/header.php");

$product_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"))['total'];
$table_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM restaurant_tables"))['total'];
$payment_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM payment_methods WHERE is_enabled='yes'"))['total'];
$user_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];
?>

<div class="card-grid">
    <div class="card">
        <h3>Total Products</h3>
        <p><?php echo $product_count; ?></p>
    </div>
    <div class="card">
        <h3>Total Tables</h3>
        <p><?php echo $table_count; ?></p>
    </div>
    <div class="card">
        <h3>Active Payments</h3>
        <p><?php echo $payment_count; ?></p>
    </div>
    <div class="card">
        <h3>Total Users</h3>
        <p><?php echo $user_count; ?></p>
    </div>
</div>

<div class="panel">
    <h3>Welcome to POS Cafe Admin</h3>
    <p style="line-height:1.9; color:#475569; font-size:15px;">
        This backend panel is where you manage your restaurant POS system.
        From here you can add products, manage restaurant tables, configure payment methods,
        and prepare your POS system for the frontend ordering screen.
    </p>
    <div style="margin-top:20px;">
    <a href="../pos/index.php" style="
        display:inline-block;
        text-decoration:none;
        background: linear-gradient(90deg, #2563eb, #06b6d4);
        color:white;
        padding:14px 20px;
        border-radius:14px;
        font-weight:700;
    ">
        🚀 Open POS Terminal
    </a>
</div>
<div style="margin-top:15px;">
    <a href="../kitchen/kitchen.php" style="
        display:inline-block;
        text-decoration:none;
        background: linear-gradient(90deg, #f59e0b, #ef4444);
        color:white;
        padding:14px 20px;
        border-radius:14px;
        font-weight:700;
    ">
        👨‍🍳 Open Kitchen Display
    </a>
</div>
</div>

<div class="panel">
    <h3>Quick Setup Checklist</h3>
    <div style="display:grid; gap:14px;">
        <div style="padding:14px; background:#f8fafc; border-radius:14px;">✅ Add menu products</div>
        <div style="padding:14px; background:#f8fafc; border-radius:14px;">✅ Create restaurant tables</div>
        <div style="padding:14px; background:#f8fafc; border-radius:14px;">✅ Configure payment methods</div>
        <div style="padding:14px; background:#f8fafc; border-radius:14px;">🔜 Next: Build POS ordering screen</div>
    </div>
</div>

<?php include("layout/footer.php"); ?>