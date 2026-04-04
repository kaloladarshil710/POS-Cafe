<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if (!isset($_GET['table_id'])) { header("Location: index.php"); exit(); }

$table_id = intval($_GET['table_id']);
$table_q  = mysqli_query($conn, "SELECT * FROM restaurant_tables WHERE id=$table_id AND active='yes'");
$table    = mysqli_fetch_assoc($table_q);
if (!$table) { header("Location: index.php"); exit(); }

$products = mysqli_query($conn, "SELECT * FROM products ORDER BY category ASC, name ASC");

// Get categories for filter tabs
$cats_q = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category ASC");
$categories = [];
while ($c = mysqli_fetch_assoc($cats_q)) $categories[] = $c['category'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order — <?php echo htmlspecialchars($table['table_number']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
        :root{--primary:#FF6B35;--primary-dark:#E85520;--bg:#F4F5F7;--text:#0F172A;--muted:#64748B;--border:#E2E8F0;--card:#FFF;--sidebar:#0C0C0C;--sidebar-text:#F5F5F5;}
        body{font-family:'Sora',sans-serif;background:var(--bg);color:var(--text);height:100vh;overflow:hidden;}

        .layout{display:grid;grid-template-columns:1fr 360px;height:100vh;}

        /* ── Left (products) ── */
        .left{display:flex;flex-direction:column;overflow:hidden;}
        .topbar{background:white;border-bottom:1px solid var(--border);padding:14px 24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;flex-shrink:0;}
        .topbar h1{font-size:20px;font-weight:800;}
        .topbar p{font-size:13px;color:var(--muted);}
        .btn-back{text-decoration:none;background:#F1F5F9;color:var(--text);border:1px solid var(--border);padding:9px 16px;border-radius:10px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;transition:0.2s;}
        .btn-back:hover{background:var(--border);}

        .cat-tabs{display:flex;gap:8px;padding:14px 24px;border-bottom:1px solid var(--border);background:white;overflow-x:auto;flex-shrink:0;scrollbar-width:none;}
        .cat-tabs::-webkit-scrollbar{display:none;}
        .cat-tab{padding:8px 18px;border-radius:999px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:white;color:var(--muted);transition:0.2s;white-space:nowrap;}
        .cat-tab.active,.cat-tab:hover{background:var(--primary);color:white;border-color:var(--primary);}

        .products-area{flex:1;overflow-y:auto;padding:20px 24px;}
        .product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:14px;}
        .product-card{background:white;border:1px solid var(--border);border-radius:18px;padding:18px;transition:all 0.2s;cursor:pointer;}
        .product-card:hover{transform:translateY(-4px);box-shadow:0 12px 28px rgba(0,0,0,0.08);border-color:var(--primary);}
        .p-cat{display:inline-block;background:#DBEAFE;color:#1D4ED8;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;margin-bottom:8px;}
        .p-name{font-size:15px;font-weight:700;margin-bottom:4px;}
        .p-desc{font-size:12px;color:var(--muted);margin-bottom:12px;line-height:1.5;min-height:30px;}
        .p-price{font-size:20px;font-weight:800;color:var(--primary);margin-bottom:12px;}
        .add-btn{width:100%;padding:10px;background:var(--primary);color:white;border:none;border-radius:12px;font-family:'Sora',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:0.2s;}
        .add-btn:hover{background:var(--primary-dark);}

        /* ── Right (cart) ── */
        .right{background:var(--sidebar);color:var(--sidebar-text);display:flex;flex-direction:column;border-left:1px solid rgba(255,255,255,0.06);}
        .cart-header{padding:20px 20px 16px;border-bottom:1px solid rgba(255,255,255,0.07);}
        .cart-header h2{font-size:18px;font-weight:800;}
        .cart-header p{font-size:13px;color:#888;margin-top:3px;}
        .cart-items{flex:1;overflow-y:auto;padding:14px 16px;display:flex;flex-direction:column;gap:10px;}
        .empty-cart{text-align:center;padding:32px 16px;color:#555;}
        .empty-cart-icon{font-size:40px;margin-bottom:10px;}

        .cart-item{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.07);border-radius:16px;padding:14px;}
        .ci-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;}
        .ci-name{font-size:14px;font-weight:700;}
        .ci-price{font-size:13px;color:#aaa;}
        .ci-total{font-size:15px;font-weight:800;color:var(--primary);}
        .qty-row{display:flex;align-items:center;gap:10px;}
        .qty-btn{width:32px;height:32px;border:none;border-radius:9px;font-size:16px;font-weight:700;cursor:pointer;transition:0.15s;}
        .qty-minus{background:rgba(239,68,68,0.15);color:#f87171;}
        .qty-minus:hover{background:#EF4444;color:white;}
        .qty-plus{background:rgba(34,197,94,0.15);color:#4ade80;}
        .qty-plus:hover{background:#10B981;color:white;}
        .qty-val{font-size:15px;font-weight:700;min-width:24px;text-align:center;}

        .cart-footer{padding:16px;border-top:1px solid rgba(255,255,255,0.07);}
        .summary-row{display:flex;justify-content:space-between;font-size:14px;color:#aaa;margin-bottom:8px;}
        .grand-row{display:flex;justify-content:space-between;font-size:20px;font-weight:800;margin-top:10px;}
        .divider{border:none;border-top:1px solid rgba(255,255,255,0.08);margin:12px 0;}
        .place-btn{width:100%;margin-top:14px;padding:15px;background:var(--primary);color:white;border:none;border-radius:14px;font-family:'Sora',sans-serif;font-size:15px;font-weight:800;cursor:pointer;transition:0.2s;}
        .place-btn:hover{background:var(--primary-dark);transform:translateY(-2px);}
        .place-btn:disabled{background:#333;color:#666;cursor:not-allowed;transform:none;}

        @media(max-width:900px){.layout{grid-template-columns:1fr;height:auto;overflow:auto;}.right{height:auto;}.left{height:auto;overflow:visible;}.products-area{overflow:visible;}}
    </style>
</head>
<body>

<div class="layout">
    <!-- LEFT: Products -->
    <div class="left">
        <div class="topbar">
            <div>
                <h1>🍴 <?php echo htmlspecialchars($table['table_number']); ?></h1>
                <p>👥 <?php echo $table['seats']; ?> seats — Add items to the order</p>
            </div>
            <a class="btn-back" href="index.php">← Tables</a>
        </div>

        <!-- Category Tabs -->
        <div class="cat-tabs">
            <button class="cat-tab active" onclick="filterCat('all', this)">🍽️ All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="cat-tab" onclick="filterCat('<?php echo htmlspecialchars(addslashes($cat)); ?>', this)">
                    <?php echo htmlspecialchars($cat); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="products-area">
            <div class="product-grid" id="productGrid">
                <?php while ($row = mysqli_fetch_assoc($products)): ?>
                <div class="product-card" data-cat="<?php echo htmlspecialchars($row['category']); ?>">
                    <div class="p-cat"><?php echo htmlspecialchars($row['category']); ?></div>
                    <div class="p-name"><?php echo htmlspecialchars($row['name']); ?></div>
                    <div class="p-desc"><?php echo htmlspecialchars($row['description'] ?: 'Fresh and delicious'); ?></div>
                    <div class="p-price">₹<?php echo number_format($row['price'], 2); ?></div>
                    <button class="add-btn" onclick="addToCart(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>', <?php echo floatval($row['price']); ?>)">
                        + Add to Order
                    </button>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT: Cart -->
    <div class="right">
        <div class="cart-header">
            <h2>🛒 Current Order</h2>
            <p><?php echo htmlspecialchars($table['table_number']); ?></p>
        </div>

        <form action="save_order.php" method="POST" onsubmit="return prepareOrder()" id="orderForm">
            <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
            <input type="hidden" name="cart_data" id="cart_data">

            <div class="cart-items" id="cartItems">
                <div class="empty-cart">
                    <div class="empty-cart-icon">🛒</div>
                    <p style="font-size:14px;">No items yet.<br>Tap a product to add.</p>
                </div>
            </div>

            <div class="cart-footer">
                <div class="summary-row"><span>Items</span><span id="totalItems">0</span></div>
                <div class="summary-row"><span>Subtotal</span><span>₹<span id="subtotal">0.00</span></span></div>
                <hr class="divider">
                <div class="grand-row"><span>Total</span><span>₹<span id="grandTotal">0.00</span></span></div>
                <button type="submit" class="place-btn" id="placeBtn" disabled>Place Order →</button>
            </div>
        </form>
    </div>
</div>

<script>
let cart = [];

function addToCart(id, name, price) {
    const existing = cart.find(i => i.id === id);
    if (existing) { existing.qty++; } 
    else { cart.push({ id, name, price: parseFloat(price), qty: 1 }); }
    renderCart();
}

function changeQty(id, delta) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const placeBtn  = document.getElementById('placeBtn');
    let subtotal = 0, totalItems = 0;

    if (cart.length === 0) {
        container.innerHTML = '<div class="empty-cart"><div class="empty-cart-icon">🛒</div><p style="font-size:14px;">No items yet.<br>Tap a product to add.</p></div>';
        placeBtn.disabled = true;
    } else {
        container.innerHTML = '';
        cart.forEach(item => {
            const itemTotal = item.price * item.qty;
            subtotal += itemTotal;
            totalItems += item.qty;
            container.innerHTML += `
                <div class="cart-item">
                    <div class="ci-top">
                        <div><div class="ci-name">${item.name}</div><div class="ci-price">₹${item.price.toFixed(2)} × ${item.qty}</div></div>
                        <div class="ci-total">₹${itemTotal.toFixed(2)}</div>
                    </div>
                    <div class="qty-row">
                        <button type="button" class="qty-btn qty-minus" onclick="changeQty(${item.id},-1)">−</button>
                        <span class="qty-val">${item.qty}</span>
                        <button type="button" class="qty-btn qty-plus"  onclick="changeQty(${item.id},1)">+</button>
                    </div>
                </div>`;
        });
        placeBtn.disabled = false;
    }

    document.getElementById('subtotal').textContent   = subtotal.toFixed(2);
    document.getElementById('grandTotal').textContent = subtotal.toFixed(2);
    document.getElementById('totalItems').textContent = totalItems;
}

function prepareOrder() {
    if (cart.length === 0) { alert("Add at least one item!"); return false; }
    document.getElementById('cart_data').value = JSON.stringify(cart);
    return true;
}

function filterCat(cat, btn) {
    document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.product-card').forEach(card => {
        card.style.display = (cat === 'all' || card.dataset.cat === cat) ? 'block' : 'none';
    });
}
</script>

</body>
</html>