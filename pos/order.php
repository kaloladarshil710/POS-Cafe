<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['table_id'])) {
    header("Location: index.php");
    exit();
}

$table_id = (int) $_GET['table_id'];

$table_query = mysqli_query($conn, "SELECT * FROM restaurant_tables WHERE id = $table_id");
$table = mysqli_fetch_assoc($table_query);

if (!$table) {
    header("Location: index.php");
    exit();
}

$products = mysqli_query($conn, "SELECT * FROM products ORDER BY category ASC, name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Order Screen</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Poppins', sans-serif;
        }

        body{
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            min-height:100vh;
            color:#0f172a;
        }

        .wrapper{
            display:grid;
            grid-template-columns: 2fr 1fr;
            min-height:100vh;
        }

        .left{
            padding:28px;
        }

        .right{
            background: linear-gradient(180deg, #0f172a, #1e293b);
            color:white;
            padding:28px;
            position:sticky;
            top:0;
            height:100vh;
            overflow-y:auto;
        }

        .topbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:24px;
            flex-wrap:wrap;
            gap:15px;
        }

        .topbar h1{
            font-size:28px;
        }

        .sub{
            color:#475569;
            margin-top:6px;
        }

        .back-btn{
            text-decoration:none;
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            color:white;
            padding:12px 18px;
            border-radius:14px;
            font-weight:600;
        }

        .product-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
            gap:20px;
        }

        .product-card{
            background: rgba(255,255,255,0.85);
            border:1px solid rgba(255,255,255,0.5);
            backdrop-filter: blur(12px);
            border-radius:24px;
            padding:22px;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.06);
            transition:0.25s ease;
        }

        .product-card:hover{
            transform:translateY(-5px);
        }

        .product-icon{
            font-size:42px;
            margin-bottom:14px;
        }

        .category{
            display:inline-block;
            background:#dbeafe;
            color:#1d4ed8;
            padding:6px 12px;
            border-radius:999px;
            font-size:12px;
            font-weight:600;
            margin-bottom:12px;
        }

        .product-card h3{
            font-size:20px;
            margin-bottom:8px;
        }

        .price{
            font-size:24px;
            font-weight:700;
            margin:12px 0;
        }

        .add-btn{
            width:100%;
            border:none;
            background: linear-gradient(90deg, #10b981, #06b6d4);
            color:white;
            padding:12px;
            border-radius:14px;
            font-weight:700;
            cursor:pointer;
            transition:0.2s ease;
        }

        .add-btn:hover{
            transform:translateY(-2px);
        }

        .cart-title{
            font-size:26px;
            font-weight:700;
            margin-bottom:10px;
        }

        .cart-sub{
            color:#cbd5e1;
            margin-bottom:22px;
        }

        .cart-items{
            display:flex;
            flex-direction:column;
            gap:14px;
            margin-bottom:22px;
        }

        .cart-item{
            background: rgba(255,255,255,0.08);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:18px;
            padding:16px;
        }

        .cart-item-top{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:12px;
            gap:10px;
        }

        .cart-item h4{
            font-size:16px;
        }

        .cart-item p{
            color:#cbd5e1;
            font-size:13px;
        }

        .qty-box{
            display:flex;
            align-items:center;
            gap:10px;
            margin-top:12px;
        }

        .qty-btn{
            width:36px;
            height:36px;
            border:none;
            border-radius:12px;
            font-size:18px;
            font-weight:700;
            cursor:pointer;
        }

        .minus{ background:#fee2e2; color:#991b1b; }
        .plus{ background:#dcfce7; color:#166534; }

        .summary{
            background: rgba(255,255,255,0.08);
            border-radius:22px;
            padding:20px;
            margin-top:22px;
        }

        .summary-row{
            display:flex;
            justify-content:space-between;
            margin-bottom:14px;
            font-size:15px;
        }

        .grand-total{
            font-size:28px;
            font-weight:800;
            margin-top:10px;
        }

        .place-btn{
            width:100%;
            margin-top:22px;
            border:none;
            background: linear-gradient(90deg, #f59e0b, #ef4444);
            color:white;
            padding:16px;
            border-radius:16px;
            font-size:16px;
            font-weight:700;
            cursor:pointer;
        }

        .empty-cart{
            color:#94a3b8;
            padding:18px;
            background: rgba(255,255,255,0.06);
            border-radius:16px;
            text-align:center;
        }

        @media(max-width:1000px){
            .wrapper{
                grid-template-columns:1fr;
            }

            .right{
                position:relative;
                height:auto;
            }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- LEFT SIDE -->
    <div class="left">
        <div class="topbar">
            <div>
                <h1>🍴 Order Screen</h1>
                <div class="sub">Table: <strong><?php echo htmlspecialchars($table['table_number']); ?></strong> | Seats: <?php echo $table['seats']; ?></div>
            </div>
            <a href="index.php" class="back-btn">← Back to Tables</a>
        </div>

        <div class="product-grid">
            <?php while($row = mysqli_fetch_assoc($products)) { ?>
                <div class="product-card">
                    <div class="product-icon">🍽️</div>
                    <div class="category"><?php echo htmlspecialchars($row['category']); ?></div>
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p style="color:#64748b; font-size:14px;"><?php echo htmlspecialchars($row['description']); ?></p>
                    <div class="price">₹<?php echo number_format($row['price'], 2); ?></div>

                    <button class="add-btn"
                        onclick="addToCart(
                            <?php echo $row['id']; ?>,
                            '<?php echo addslashes($row['name']); ?>',
                            <?php echo $row['price']; ?>
                        )">
                        + Add to Cart
                    </button>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right">
        <div class="cart-title">🛒 Current Order</div>
        <div class="cart-sub">Table: <?php echo htmlspecialchars($table['table_number']); ?></div>

        <form action="save_order.php" method="POST" onsubmit="return prepareOrderData()">
            <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
            <input type="hidden" name="cart_data" id="cart_data">

            <div id="cartItems" class="cart-items">
                <div class="empty-cart">No items added yet</div>
            </div>

            <div class="summary">
                <div class="summary-row">
                    <span>Total Items</span>
                    <span id="totalItems">0</span>
                </div>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>₹<span id="subtotal">0.00</span></span>
                </div>

                <hr style="border:none; border-top:1px solid rgba(255,255,255,0.12); margin:15px 0;">

                <div class="summary-row grand-total">
                    <span>Total</span>
                    <span>₹<span id="grandTotal">0.00</span></span>
                </div>
            </div>

            <button type="submit" class="place-btn">🍽 Place Order</button>
        </form>
    </div>
</div>

<script>
    let cart = [];

    function addToCart(id, name, price) {
        let existing = cart.find(item => item.id === id);

        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({
                id: id,
                name: name,
                price: parseFloat(price),
                qty: 1
            });
        }

        renderCart();
    }

    function increaseQty(id) {
        let item = cart.find(item => item.id === id);
        if (item) {
            item.qty += 1;
            renderCart();
        }
    }

    function decreaseQty(id) {
        let item = cart.find(item => item.id === id);
        if (item) {
            item.qty -= 1;
            if (item.qty <= 0) {
                cart = cart.filter(i => i.id !== id);
            }
            renderCart();
        }
    }

    function renderCart() {
        let cartItems = document.getElementById('cartItems');
        let subtotal = 0;
        let totalItems = 0;

        if (cart.length === 0) {
            cartItems.innerHTML = `<div class="empty-cart">No items added yet</div>`;
        } else {
            cartItems.innerHTML = '';

            cart.forEach(item => {
                let itemTotal = item.price * item.qty;
                subtotal += itemTotal;
                totalItems += item.qty;

                cartItems.innerHTML += `
                    <div class="cart-item">
                        <div class="cart-item-top">
                            <div>
                                <h4>${item.name}</h4>
                                <p>₹${item.price.toFixed(2)} each</p>
                            </div>
                            <strong>₹${itemTotal.toFixed(2)}</strong>
                        </div>

                        <div class="qty-box">
                            <button type="button" class="qty-btn minus" onclick="decreaseQty(${item.id})">−</button>
                            <strong>${item.qty}</strong>
                            <button type="button" class="qty-btn plus" onclick="increaseQty(${item.id})">+</button>
                        </div>
                    </div>
                `;
            });
        }

        document.getElementById('subtotal').innerText = subtotal.toFixed(2);
        document.getElementById('grandTotal').innerText = subtotal.toFixed(2);
        document.getElementById('totalItems').innerText = totalItems;
    }

    function prepareOrderData() {
        if (cart.length === 0) {
            alert("Please add at least one item to cart!");
            return false;
        }

        document.getElementById('cart_data').value = JSON.stringify(cart);
        return true;
    }
</script>

</body>
</html>