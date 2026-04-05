<?php
session_start();
include("../config/db.php");

$table_id = intval($_GET['table'] ?? 0);

// Validate table
$table_q = mysqli_query($conn, "SELECT * FROM restaurant_tables WHERE id=$table_id AND active='yes'");
$table = mysqli_fetch_assoc($table_q);
if (!$table) {
    die("Invalid table QR.");
}

// Fetch products with category names
$products = mysqli_query($conn, "
    SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE c.status='active'
    ORDER BY c.category_name ASC, p.name ASC
");

$categories = [];
while ($row = mysqli_fetch_assoc($products)) {
    $cat = $row['category_name'] ?: 'Other';
    if (!isset($categories[$cat])) $categories[$cat] = [];
    $categories[$cat][] = $row;
}

// Emoji fallback if image missing
$emojis = [
    'Fast Food' => '🍔',
    'Beverages' => '🥤',
    'Desserts'  => '🍰',
    'Snacks'    => '🍿',
    'Other'     => '🍽️'
];

// Image URL base (same as POS page)
$img_base_url = '../pos/product_images/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu — <?php echo htmlspecialchars($table['table_number']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
    --bg:#F0F2F5;
    --surface:#FFF;
    --surface2:#F8FAFC;
    --border:#E4E7EC;
    --primary:#C8602A;
    --primary-dark:#A84E20;
    --text:#101828;
    --text2:#667085;
    --text3:#98A2B3;
    --green:#12B76A;
    --sidebar:#050816;
    --sidebar-border:rgba(255,255,255,0.08);
}

body{
    font-family:'DM Sans',sans-serif;
    background:var(--bg);
    color:var(--text);
    height:100vh;
    overflow:hidden;
    display:flex;
    flex-direction:column;
}

/* STATUS BAR */
#orderStatusBar{
    display:none;
    padding:10px 20px;
    text-align:center;
    font-size:13px;
    font-weight:700;
    color:#fff;
}

/* TOPBAR */
.topbar{
    background:var(--surface);
    border-bottom:1px solid var(--border);
    height:58px;
    padding:0 20px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-shrink:0;
}
.topbar-left{
    display:flex;
    align-items:center;
    gap:14px;
}
.brand{
    font-size:18px;
    font-weight:800;
    color:var(--primary);
}
.table-tag{
    background:var(--primary);
    color:white;
    padding:6px 12px;
    border-radius:10px;
    font-size:13px;
    font-weight:800;
}
.topbar-right{
    font-size:13px;
    color:var(--text3);
}

/* MAIN LAYOUT */
.layout{
    flex:1;
    display:grid;
    grid-template-columns:1fr 380px;
    overflow:hidden;
}

/* LEFT PANEL */
.left{
    display:flex;
    flex-direction:column;
    overflow:hidden;
}

/* CATEGORY TABS */
.cat-tabs{
    background:var(--surface);
    border-bottom:1px solid var(--border);
    padding:0 16px;
    display:flex;
    gap:8px;
    overflow-x:auto;
    scrollbar-width:none;
    height:56px;
    align-items:center;
    flex-shrink:0;
}
.cat-tabs::-webkit-scrollbar{display:none;}
.cat-tab{
    padding:8px 16px;
    border-radius:12px;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    border:1px solid var(--border);
    background:#fff;
    color:var(--text2);
    transition:0.15s ease;
    white-space:nowrap;
    flex-shrink:0;
}
.cat-tab:hover{
    background:var(--surface2);
}
.cat-tab.active{
    background:var(--primary);
    color:#fff;
    border-color:var(--primary);
}

/* PRODUCT AREA */
.products-wrap{
    flex:1;
    overflow-y:auto;
    padding:18px 16px 90px;
}
.cat-section{
    margin-bottom:28px;
}
.cat-label{
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:1px;
    color:var(--text3);
    margin-bottom:14px;
    padding-left:2px;
}
.product-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
    gap:16px;
}

/* PRODUCT CARD */
.product-card{
    background:#fff;
    border:1px solid var(--border);
    border-radius:22px;
    overflow:hidden;
    cursor:pointer;
    transition:all 0.18s ease;
    position:relative;
}
.product-card:hover{
    transform:translateY(-3px);
    box-shadow:0 12px 28px rgba(0,0,0,0.08);
    border-color:#d8dce2;
}
.product-card.in-cart{
    border-color:var(--primary);
    box-shadow:0 12px 28px rgba(200,96,42,0.12);
}
.in-cart-badge{
    position:absolute;
    top:12px;
    right:12px;
    background:var(--primary);
    color:#fff;
    font-size:10px;
    font-weight:800;
    padding:4px 8px;
    border-radius:999px;
    z-index:2;
}

/* IMAGE */
.p-img-wrap{
    width:100%;
    height:160px;
    overflow:hidden;
    background:linear-gradient(135deg,#f5f5f0,#ece8e2);
    display:flex;
    align-items:center;
    justify-content:center;
}
.p-img-wrap[data-cat="Beverages"]{background:linear-gradient(135deg,#e8f4fd,#c8e6f8);}
.p-img-wrap[data-cat="Desserts"]{background:linear-gradient(135deg,#fdf0f8,#f5d5ed);}
.p-img-wrap[data-cat="Fast Food"]{background:linear-gradient(135deg,#fdf5e8,#f5e0b5);}
.p-img-wrap[data-cat="Snacks"]{background:linear-gradient(135deg,#f0fdf4,#c8f0d8);}
.p-img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    transition:transform 0.35s ease;
}
.product-card:hover .p-img{
    transform:scale(1.06);
}
.p-emoji-fallback{
    font-size:48px;
    line-height:1;
}

/* CARD CONTENT */
.p-body{
    padding:14px 16px 14px;
}
.p-name{
    font-size:15px;
    font-weight:800;
    line-height:1.3;
    margin-bottom:5px;
}
.p-desc{
    font-size:12px;
    color:var(--text3);
    line-height:1.5;
    min-height:38px;
    margin-bottom:14px;
}
.p-footer{
    display:flex;
    align-items:center;
    justify-content:space-between;
}
.p-price{
    font-size:18px;
    font-weight:800;
    color:var(--primary);
}
.p-add{
    width:38px;
    height:38px;
    border:none;
    border-radius:12px;
    background:var(--primary);
    color:#fff;
    font-size:22px;
    line-height:1;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    transition:0.15s ease;
}
.p-add:hover{
    background:var(--primary-dark);
    transform:scale(1.06);
}

/* RIGHT CART PANEL */
.right{
    background:var(--sidebar);
    color:#fff;
    display:flex;
    flex-direction:column;
    border-left:1px solid var(--sidebar-border);
}
.cart-header{
    padding:20px 18px 14px;
    border-bottom:1px solid var(--sidebar-border);
}
.cart-title{
    font-size:15px;
    font-weight:800;
    display:flex;
    align-items:center;
    gap:8px;
}
.cart-count{
    background:var(--primary);
    color:#fff;
    padding:2px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
}
.cart-subtitle{
    font-size:13px;
    color:#9aa3b2;
    margin-top:4px;
}
.cart-items{
    flex:1;
    overflow-y:auto;
    padding:14px;
    display:flex;
    flex-direction:column;
    gap:10px;
}
.empty-cart{
    text-align:center;
    padding:70px 18px;
    color:#8b93a5;
}
.empty-cart-icon{
    font-size:56px;
    margin-bottom:12px;
    opacity:0.5;
}
.empty-cart p{
    font-size:13px;
    line-height:1.7;
}

/* CART ITEM */
.cart-item{
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.06);
    border-radius:14px;
    padding:12px;
}
.ci-row1{
    display:flex;
    justify-content:space-between;
    gap:10px;
    margin-bottom:10px;
}
.ci-name{
    font-size:13px;
    font-weight:700;
    line-height:1.4;
}
.ci-sub{
    font-size:11px;
    color:#9aa3b2;
    margin-top:3px;
}
.ci-total{
    font-size:14px;
    font-weight:800;
    color:var(--primary);
    white-space:nowrap;
}
.ci-row2{
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.qty-ctrl{
    display:flex;
    align-items:center;
    gap:8px;
}
.qty-btn{
    width:30px;
    height:30px;
    border:none;
    border-radius:9px;
    font-size:16px;
    font-weight:800;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    transition:0.12s ease;
}
.qb-minus{
    background:rgba(239,68,68,0.18);
    color:#ff8b8b;
}
.qb-minus:hover{
    background:#EF4444;
    color:#fff;
}
.qb-plus{
    background:rgba(34,197,94,0.18);
    color:#7ee7a1;
}
.qb-plus:hover{
    background:#22C55E;
    color:#fff;
}
.qty-num{
    min-width:20px;
    text-align:center;
    font-size:14px;
    font-weight:800;
}
.ci-remove{
    background:none;
    border:none;
    color:#7c8597;
    cursor:pointer;
    font-size:15px;
}
.ci-remove:hover{
    color:#ff8b8b;
}

/* CART FOOTER */
.cart-footer{
    padding:16px;
    border-top:1px solid var(--sidebar-border);
}
.sum-line{
    display:flex;
    justify-content:space-between;
    font-size:13px;
    color:#9aa3b2;
    margin-bottom:6px;
}
.sum-divider{
    border:none;
    border-top:1px solid rgba(255,255,255,0.08);
    margin:12px 0;
}
.total-line{
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-size:20px;
    font-weight:800;
}
.total-line span:last-child{
    color:var(--primary);
}
.place-btn{
    width:100%;
    margin-top:16px;
    background:var(--primary);
    border:none;
    color:#fff;
    border-radius:14px;
    padding:15px;
    font-family:'DM Sans',sans-serif;
    font-size:15px;
    font-weight:800;
    cursor:pointer;
    transition:0.15s ease;
}
.place-btn:hover:not(:disabled){
    background:var(--primary-dark);
}
.place-btn:disabled{
    opacity:0.4;
    cursor:not-allowed;
}

/* MOBILE BAR */
.mobile-bar{
    display:none;
    position:fixed;
    bottom:0;
    left:0;
    right:0;
    background:var(--sidebar);
    color:#fff;
    padding:14px 18px;
    justify-content:space-between;
    align-items:center;
    z-index:100;
    box-shadow:0 -8px 24px rgba(0,0,0,0.2);
}
.mobile-bar button{
    background:var(--primary);
    color:#fff;
    border:none;
    padding:11px 18px;
    border-radius:12px;
    font-weight:800;
    font-family:'DM Sans',sans-serif;
    cursor:pointer;
}

/* RESPONSIVE */
@media(max-width: 900px){
    body{
        height:auto;
        overflow:auto;
    }
    .layout{
        grid-template-columns:1fr;
    }
    .right{
        display:none;
    }
    .products-wrap{
        overflow:visible;
    }
    .mobile-bar{
        display:flex;
    }
}
</style>
</head>
<body>

<!-- Live kitchen status -->
<div id="orderStatusBar"></div>

<!-- Header -->
<div class="topbar">
    <div class="topbar-left">
        <div class="brand">🍽 Cafe Menu</div>
        <div class="table-tag"><?php echo htmlspecialchars($table['table_number']); ?></div>
    </div>
    <div class="topbar-right"><?php echo date('D, d M Y'); ?></div>
</div>

<div class="layout">
    <!-- LEFT -->
    <div class="left">

        <!-- Category Tabs -->
        <div class="cat-tabs">
            <button class="cat-tab active" onclick="filterCat('all', this)">🍽 All</button>
            <?php foreach ($categories as $catName => $items): ?>
                <button class="cat-tab" onclick="filterCat('<?php echo htmlspecialchars(addslashes($catName)); ?>', this)">
                    <?php
                        $catEmoji = $emojis[$catName] ?? '🍽️';
                        echo $catEmoji . ' ' . htmlspecialchars($catName);
                    ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Products -->
        <div class="products-wrap">
            <?php foreach ($categories as $catName => $items): ?>
                <div class="cat-section" data-cat="<?php echo htmlspecialchars($catName); ?>">
                    <div class="cat-label">
                        <?php echo ($emojis[$catName] ?? '🍽️') . ' ' . htmlspecialchars($catName); ?>
                    </div>

                    <div class="product-grid">
                        <?php foreach ($items as $item): ?>
                            <?php
                                $img_file = !empty($item['image']) ? trim($item['image']) : '';
                                $img_url  = !empty($img_file) ? $img_base_url . rawurlencode($img_file) : '';
                                $fallback = $emojis[$item['category_name'] ?? 'Other'] ?? '🍽️';
                            ?>

                            <div class="product-card" id="pcard_<?php echo $item['id']; ?>" onclick='addToCart(<?php echo json_encode([
                                "id"    => (int)$item["id"],
                                "name"  => $item["name"],
                                "price" => (float)$item["price"]
                            ]); ?>)'>

                                <div class="p-img-wrap" data-cat="<?php echo htmlspecialchars($item['category_name'] ?? 'Other'); ?>">
                                    <?php if (!empty($img_file)): ?>
                                        <img
                                            class="p-img"
                                            src="<?php echo htmlspecialchars($img_url); ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>"
                                            loading="lazy"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                        >
                                        <span class="p-emoji-fallback" style="display:none;"><?php echo $fallback; ?></span>
                                    <?php else: ?>
                                        <span class="p-emoji-fallback"><?php echo $fallback; ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="p-body">
                                    <div class="p-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="p-desc"><?php echo htmlspecialchars($item['description'] ?? 'Fresh and tasty'); ?></div>

                                    <div class="p-footer">
                                        <div class="p-price">₹<?php echo number_format($item['price'], 2); ?></div>
                                        <button class="p-add" onclick="event.stopPropagation(); addToCart(<?php echo json_encode([
                                            "id"    => (int)$item["id"],
                                            "name"  => $item["name"],
                                            "price" => (float)$item["price"]
                                        ]); ?>)">+</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- RIGHT CART -->
    <div class="right">
        <div class="cart-header">
            <div class="cart-title">🛒 Your Order <span class="cart-count" id="cartBadge">0</span></div>
            <div class="cart-subtitle"><?php echo htmlspecialchars($table['table_number']); ?> · Live total</div>
        </div>

        <div class="cart-items" id="cartItems">
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <p>Your cart is empty.<br>Tap any item to add it.</p>
            </div>
        </div>

        <div class="cart-footer">
            <div class="sum-line"><span>Items</span><span id="footerCount">0</span></div>
            <hr class="sum-divider">
            <div class="total-line"><span>Total</span><span id="footerTotal">₹0.00</span></div>
            <button class="place-btn" id="placeBtn" disabled onclick="placeOrder()">🛒 Add Items to Order</button>
        </div>
    </div>
</div>

<!-- Mobile cart -->
<div class="mobile-bar">
    <div><strong id="mobileCount">0</strong> items · ₹<strong id="mobileTotal">0.00</strong></div>
    <button onclick="placeOrder()">Place Order →</button>
</div>

<!-- Hidden order form -->
<form id="orderForm" method="POST" action="place_order.php">
    <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
    <input type="hidden" name="cart_data" id="cart_data">
</form>

<script>
let cart = [];

/* Add to cart */
function addToCart(product) {
    const existing = cart.find(i => i.id == product.id);
    if (existing) {
        existing.qty += 1;
    } else {
        cart.push({...product, qty: 1});
    }
    renderCart();
}

/* Change qty */
function changeQty(id, delta) {
    const item = cart.find(i => i.id == id);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) {
        cart = cart.filter(i => i.id != id);
    }
    renderCart();
}

/* Remove item */
function removeItem(id) {
    cart = cart.filter(i => i.id != id);
    renderCart();
}

/* Escape HTML */
function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
}

/* Render cart */
function renderCart() {
    let total = 0;
    let count = 0;

    cart.forEach(i => {
        total += i.price * i.qty;
        count += i.qty;
    });

    const container = document.getElementById('cartItems');
    container.innerHTML = '';

    if (cart.length === 0) {
        container.innerHTML = `
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <p>Your cart is empty.<br>Tap any item to add it.</p>
            </div>
        `;
    } else {
        cart.forEach(item => {
            const sub = (item.price * item.qty).toFixed(2);
            const div = document.createElement('div');
            div.className = 'cart-item';
            div.innerHTML = `
                <div class="ci-row1">
                    <div>
                        <div class="ci-name">${escHtml(item.name)}</div>
                        <div class="ci-sub">₹${parseFloat(item.price).toFixed(2)} each</div>
                    </div>
                    <div class="ci-total">₹${sub}</div>
                </div>
                <div class="ci-row2">
                    <div class="qty-ctrl">
                        <button type="button" class="qty-btn qb-minus" onclick="changeQty(${item.id}, -1)">−</button>
                        <span class="qty-num">${item.qty}</span>
                        <button type="button" class="qty-btn qb-plus" onclick="changeQty(${item.id}, 1)">+</button>
                    </div>
                    <button type="button" class="ci-remove" onclick="removeItem(${item.id})">✕</button>
                </div>
            `;
            container.appendChild(div);
        });
    }

    // Highlight cards in cart
    document.querySelectorAll('.product-card').forEach(card => {
        card.classList.remove('in-cart');
        const badge = card.querySelector('.in-cart-badge');
        if (badge) badge.remove();
    });

    cart.forEach(item => {
        const card = document.getElementById('pcard_' + item.id);
        if (card) {
            card.classList.add('in-cart');
            const badge = document.createElement('div');
            badge.className = 'in-cart-badge';
            badge.textContent = 'x' + item.qty;
            card.appendChild(badge);
        }
    });

    document.getElementById('footerCount').textContent = count;
    document.getElementById('footerTotal').textContent = '₹' + total.toFixed(2);
    document.getElementById('cartBadge').textContent = count;
    document.getElementById('mobileCount').textContent = count;
    document.getElementById('mobileTotal').textContent = total.toFixed(2);

    const btn = document.getElementById('placeBtn');
    btn.disabled = cart.length === 0;
    btn.textContent = cart.length > 0 ? `Place Order · ₹${total.toFixed(2)} →` : '🛒 Add Items to Order';
}

/* Category filter */
function filterCat(cat, btn) {
    document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.cat-section').forEach(section => {
        section.style.display = (cat === 'all' || section.dataset.cat === cat) ? '' : 'none';
    });
}

/* Submit order */
function placeOrder() {
    if (cart.length === 0) {
        alert("Please add items to your cart first.");
        return;
    }

    document.getElementById('cart_data').value = JSON.stringify(cart);
    document.getElementById('orderForm').submit();
}

/* Live kitchen status */
const tableId = <?php echo $table_id; ?>;

function checkOrderStatus() {
    fetch("check_status.php?table=" + tableId)
    .then(r => r.json())
    .then(data => {
        const bar = document.getElementById("orderStatusBar");

        if (!data.status || data.status === "none") {
            bar.style.display = "none";
            return;
        }

        bar.style.display = "block";

        const map = {
            to_cook:   ["#f59e0b", "🟡 Order received. Waiting for kitchen..."],
            preparing: ["#3b82f6", "👨‍🍳 Your food is being prepared..."],
            completed: ["#16a34a", "✅ Your food is ready! Please proceed to payment."]
        };

        const [bg, text] = map[data.status] || ["#111827", "Checking your order..."];
        bar.style.background = bg;
        bar.innerHTML = text;
    })
    .catch(() => {});
}

setInterval(checkOrderStatus, 5000);
checkOrderStatus();
</script>

</body>
</html>