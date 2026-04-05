<?php
// CHANGE 3 of 5: Replace your entire pos/order.php with this file
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if (!isset($_GET['table_id']))    { header("Location: index.php"); exit(); }

$table_id = intval($_GET['table_id']);
$table_q  = mysqli_query($conn, "SELECT * FROM restaurant_tables WHERE id=$table_id AND active='yes'");
$table    = mysqli_fetch_assoc($table_q);
if (!$table) { header("Location: index.php"); exit(); }

// Load categories
$cats_q     = mysqli_query($conn, "SELECT DISTINCT c.category_name FROM categories c INNER JOIN products p ON p.category_id=c.id WHERE c.status='active' ORDER BY c.category_name ASC");
$categories = [];
while ($c = mysqli_fetch_assoc($cats_q)) $categories[] = $c['category_name'];

// Load products grouped by category (includes 'image' column)
$by_cat = [];
$pq     = mysqli_query($conn, "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY c.category_name ASC, p.name ASC");
while ($row = mysqli_fetch_assoc($pq)) {
    $cn = $row['category_name'] ?? 'Other';
    $by_cat[$cn][] = $row;
}

$emojis = ['Fast Food'=>'🍔','Beverages'=>'🥤','Desserts'=>'🍰','Snacks'=>'🍿'];

// Base path where product images are stored (same folder as this file)
// pos/product_images/  →  __DIR__ . '/product_images/'
$img_base_fs  = __DIR__ . '/product_images/';   // filesystem path for file_exists()
$img_base_url = 'product_images/';              // URL path relative to order.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order — <?php echo htmlspecialchars($table['table_number']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#F0F2F5;--surface:#FFF;--surface2:#F8FAFC;
  --border:#E4E7EC;
  --primary:#C8602A;--primary-dark:#A84E20;
  --sidebar:#0D0D14;--sidebar-border:rgba(255,255,255,0.08);
  --text:#101828;--text2:#667085;--text3:#98A2B3;
  --green:#12B76A;--red:#F04438;
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);height:100vh;overflow:hidden;display:flex;flex-direction:column;}

/* TOP BAR */
.topbar{background:var(--surface);border-bottom:1px solid var(--border);height:56px;padding:0 20px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,0.06);}
.topbar-left{display:flex;align-items:center;gap:12px;}
.back-btn{display:flex;align-items:center;gap:6px;text-decoration:none;background:var(--surface2);border:1px solid var(--border);padding:7px 14px;border-radius:10px;font-size:13px;font-weight:700;color:var(--text);transition:0.15s;}
.back-btn:hover{background:var(--border);}
.table-tag{background:var(--primary);color:white;padding:4px 12px;border-radius:8px;font-size:13px;font-weight:800;}
.table-sub{font-size:13px;color:var(--text2);}

/* LAYOUT */
.layout{flex:1;display:grid;grid-template-columns:1fr 360px;overflow:hidden;}

/* LEFT */
.left{display:flex;flex-direction:column;overflow:hidden;background:var(--bg);}

.cat-tabs{background:var(--surface);border-bottom:1px solid var(--border);padding:0 20px;display:flex;gap:6px;overflow-x:auto;flex-shrink:0;scrollbar-width:none;height:52px;align-items:center;}
.cat-tabs::-webkit-scrollbar{display:none;}
.cat-tab{padding:6px 16px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;border:1px solid var(--border);background:transparent;color:var(--text2);transition:0.15s;white-space:nowrap;flex-shrink:0;}
.cat-tab.active{background:var(--primary);color:white;border-color:var(--primary);}
.cat-tab:hover:not(.active){background:var(--surface2);color:var(--text);}

.products-wrap{flex:1;overflow-y:auto;padding:18px 20px;}
.cat-section{margin-bottom:28px;}
.cat-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text3);margin-bottom:12px;padding-left:2px;}
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:14px;}

/* PRODUCT CARD */
.product-card{
  background:var(--surface);
  border:1.5px solid var(--border);
  border-radius:16px;
  cursor:pointer;
  transition:all 0.2s;
  overflow:hidden;
  display:flex;
  flex-direction:column;
}
.product-card:hover{border-color:var(--primary);box-shadow:0 8px 24px rgba(200,96,42,0.14);transform:translateY(-3px);}
.product-card:active{transform:scale(0.97);}

/* IMAGE AREA */
.p-img-wrap{
  width:100%;height:115px;
  overflow:hidden;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,#f5f5f0,#ece8e2);
}
/* per-category tint for emoji fallback */
.p-img-wrap[data-cat="Beverages"]{background:linear-gradient(135deg,#e8f4fd,#c8e6f8);}
.p-img-wrap[data-cat="Desserts"] {background:linear-gradient(135deg,#fdf0f8,#f5d5ed);}
.p-img-wrap[data-cat="Fast Food"]{background:linear-gradient(135deg,#fdf5e8,#f5e0b5);}
.p-img-wrap[data-cat="Snacks"]   {background:linear-gradient(135deg,#f0fdf4,#c8f0d8);}

.p-img{
  width:100%;height:100%;
  object-fit:cover;display:block;
  transition:transform 0.35s ease;
}
.product-card:hover .p-img{transform:scale(1.08);}
.p-emoji-fallback{font-size:40px;line-height:1;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.1));}

/* CARD BODY */
.p-body{padding:12px 14px 14px;display:flex;flex-direction:column;flex:1;}
.p-name{font-size:14px;font-weight:700;margin-bottom:3px;line-height:1.3;}
.p-desc{font-size:11px;color:var(--text3);margin-bottom:10px;line-height:1.4;flex:1;}
.p-footer{display:flex;align-items:center;justify-content:space-between;margin-top:auto;}
.p-price{font-size:17px;font-weight:800;color:var(--primary);}
.p-add{
  width:32px;height:32px;
  background:var(--primary);color:white;
  border:none;border-radius:9px;
  font-size:20px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:0.15s;flex-shrink:0;
  box-shadow:0 2px 8px rgba(200,96,42,0.3);
}
.p-add:hover{background:var(--primary-dark);transform:scale(1.1);}

/* RIGHT PANEL / CART */
.right{background:var(--sidebar);color:#F1F1F5;display:flex;flex-direction:column;border-left:1px solid var(--sidebar-border);}
.cart-header{padding:18px 18px 14px;border-bottom:1px solid var(--sidebar-border);}
.cart-title{font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;}
.cart-subtitle{font-size:12px;color:#666;margin-top:3px;}
.cart-count{background:var(--primary);color:white;padding:1px 8px;border-radius:999px;font-size:11px;font-weight:800;}

.cart-items{flex:1;overflow-y:auto;padding:12px 14px;display:flex;flex-direction:column;gap:8px;}
.empty-cart{text-align:center;padding:48px 16px;}
.empty-cart-icon{font-size:48px;margin-bottom:12px;opacity:0.35;}
.empty-cart p{font-size:13px;color:#555;line-height:1.6;}

.cart-item{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:13px;}
.ci-row1{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:9px;}
.ci-name{font-size:13px;font-weight:700;flex:1;padding-right:8px;line-height:1.3;}
.ci-sub{font-size:11px;color:#777;margin-top:2px;}
.ci-total{font-size:14px;font-weight:800;color:var(--primary);white-space:nowrap;}
.ci-row2{display:flex;align-items:center;}
.qty-ctrl{display:flex;align-items:center;gap:8px;}
.qty-btn{width:28px;height:28px;border:none;border-radius:7px;font-size:15px;font-weight:700;cursor:pointer;transition:0.12s;display:flex;align-items:center;justify-content:center;}
.qb-minus{background:rgba(239,68,68,0.2);color:#F87171;}
.qb-minus:hover{background:#EF4444;color:white;}
.qb-plus{background:rgba(34,197,94,0.2);color:#4ADE80;}
.qb-plus:hover{background:#22C55E;color:white;}
.qty-num{font-size:14px;font-weight:800;min-width:22px;text-align:center;}

.cart-footer{padding:14px;border-top:1px solid var(--sidebar-border);}
.sum-line{display:flex;justify-content:space-between;font-size:13px;color:#888;margin-bottom:6px;}
.sum-divider{border:none;border-top:1px solid rgba(255,255,255,0.07);margin:10px 0;}
.total-line{display:flex;justify-content:space-between;font-size:20px;font-weight:800;}
.total-line span:last-child{color:var(--primary);}
.place-btn{width:100%;margin-top:14px;background:var(--primary);border:none;color:white;border-radius:12px;padding:14px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:800;cursor:pointer;transition:0.15s;}
.place-btn:hover:not(:disabled){background:var(--primary-dark);}
.place-btn:disabled{opacity:0.3;cursor:not-allowed;}
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-left">
    <a class="back-btn" href="index.php">← Tables</a>
    <div style="display:flex;align-items:center;gap:10px;">
      <div class="table-tag"><?php echo htmlspecialchars($table['table_number']); ?></div>
      <div class="table-sub">
        <?php echo $table['seats']; ?> seats &nbsp;·&nbsp;
        <?php echo $table['status']==='occupied'
          ? '<span style="color:#F04438;">● Occupied</span>'
          : '<span style="color:#12B76A;">● Free</span>'; ?>
      </div>
    </div>
  </div>
  <div style="font-size:13px;color:#98A2B3;"><?php echo date('D, d M Y · h:i A'); ?></div>
</div>

<div class="layout">

  <!-- LEFT: Products -->
  <div class="left">
    <div class="cat-tabs">
      <button class="cat-tab active" onclick="filterCat('all',this)">🍽️ All</button>
      <?php foreach ($categories as $cat): ?>
        <button class="cat-tab" onclick="filterCat('<?php echo htmlspecialchars(addslashes($cat)); ?>',this)">
          <?php echo ($emojis[$cat] ?? '🍽️') . ' ' . htmlspecialchars($cat); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <div class="products-wrap" id="productsWrap">
      <?php foreach ($by_cat as $catname => $prods):
        $icon = $emojis[$catname] ?? '🍽️';
      ?>
      <div class="cat-section" data-section="<?php echo htmlspecialchars($catname); ?>">
        <div class="cat-label"><?php echo $icon . ' ' . htmlspecialchars($catname); ?></div>
        <div class="product-grid">

          <?php foreach ($prods as $row):
            // ── Image resolution ────────────────────────────────────────────
            // 'image' column stores just the filename, e.g. "burger.jpg"
            $img_file = !empty($row['image']) ? trim($row['image']) : '';
            $has_img  = $img_file !== '' && file_exists($img_base_fs . $img_file);
            $img_url  = $img_base_url . $img_file;   // relative URL for <img src>
            $fallback = $emojis[$row['category_name']] ?? '🍽️';
          ?>
          <div class="product-card"
               data-cat="<?php echo htmlspecialchars($row['category_name']); ?>"
               onclick="addToCart(
                 <?php echo $row['id']; ?>,
                 '<?php echo addslashes(htmlspecialchars($row['name'])); ?>',
                 <?php echo floatval($row['price']); ?>
               )">

            <!-- IMAGE or EMOJI FALLBACK -->
            <div class="p-img-wrap" data-cat="<?php echo htmlspecialchars($row['category_name']); ?>">
              <?php if ($has_img): ?>
                <img
                  class="p-img"
                  src="<?php echo htmlspecialchars($img_url); ?>"
                  alt="<?php echo htmlspecialchars($row['name']); ?>"
                  loading="lazy"
                  onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                >
                <!-- Hidden fallback shown only if image fails to load -->
                <span class="p-emoji-fallback" style="display:none;"><?php echo $fallback; ?></span>
              <?php else: ?>
                <span class="p-emoji-fallback"><?php echo $fallback; ?></span>
              <?php endif; ?>
            </div>

            <!-- CARD BODY -->
            <div class="p-body">
              <div class="p-name"><?php echo htmlspecialchars($row['name']); ?></div>
              <div class="p-desc"><?php echo htmlspecialchars($row['description'] ?: 'Freshly prepared'); ?></div>
              <div class="p-footer">
                <div class="p-price">₹<?php echo number_format($row['price'], 2); ?></div>
                <button class="p-add"
                  onclick="event.stopPropagation();addToCart(
                    <?php echo $row['id']; ?>,
                    '<?php echo addslashes(htmlspecialchars($row['name'])); ?>',
                    <?php echo floatval($row['price']); ?>
                  )">+</button>
              </div>
            </div>

          </div>
          <?php endforeach; ?>

        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- RIGHT: Cart -->
  <div class="right">
    <div class="cart-header">
      <div class="cart-title">🧾 Current Order <span class="cart-count" id="cartCount">0</span></div>
      <div class="cart-subtitle"><?php echo htmlspecialchars($table['table_number']); ?></div>
    </div>

    <form action="save_order.php" method="POST" onsubmit="return prepareOrder()" id="orderForm">
      <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
      <input type="hidden" name="cart_data" id="cart_data">

      <div class="cart-items" id="cartItems">
        <div class="empty-cart">
          <div class="empty-cart-icon">🛒</div>
          <p>No items yet.<br>Tap any product to add it.</p>
        </div>
      </div>

      <div class="cart-footer">
        <div class="sum-line"><span>Subtotal</span><span>₹<span id="subtotal">0.00</span></span></div>
        <div class="sum-line"><span>Items</span><span id="totalItems">0</span></div>
        <hr class="sum-divider">
        <div class="total-line"><span>Total</span><span>₹<span id="grandTotal">0.00</span></span></div>
        <button type="submit" class="place-btn" id="placeBtn" disabled>Place Order & Proceed →</button>
      </div>
    </form>
  </div>

</div>

<script>
let cart = [];

function addToCart(id, name, price) {
  const ex = cart.find(i => i.id === id);
  if (ex) { ex.qty++; } else { cart.push({id, name, price: parseFloat(price), qty: 1}); }
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
  const el  = document.getElementById('cartItems');
  const btn = document.getElementById('placeBtn');
  let sub = 0, total = 0;

  if (cart.length === 0) {
    el.innerHTML = `<div class="empty-cart">
      <div class="empty-cart-icon">🛒</div>
      <p>No items yet.<br>Tap any product to add it.</p>
    </div>`;
    btn.disabled = true;
  } else {
    el.innerHTML = '';
    cart.forEach(item => {
      const it = item.price * item.qty;
      sub += it; total += item.qty;
      el.innerHTML += `<div class="cart-item">
        <div class="ci-row1">
          <div>
            <div class="ci-name">${item.name}</div>
            <div class="ci-sub">₹${item.price.toFixed(2)} each</div>
          </div>
          <div class="ci-total">₹${it.toFixed(2)}</div>
        </div>
        <div class="ci-row2">
          <div class="qty-ctrl">
            <button type="button" class="qty-btn qb-minus" onclick="changeQty(${item.id},-1)">−</button>
            <span class="qty-num">${item.qty}</span>
            <button type="button" class="qty-btn qb-plus" onclick="changeQty(${item.id},1)">+</button>
          </div>
        </div>
      </div>`;
    });
    btn.disabled = false;
  }

  document.getElementById('subtotal').textContent   = sub.toFixed(2);
  document.getElementById('grandTotal').textContent = sub.toFixed(2);
  document.getElementById('totalItems').textContent = total;
  document.getElementById('cartCount').textContent  = total;
}

function prepareOrder() {
  if (cart.length === 0) { alert('Add at least one item!'); return false; }
  document.getElementById('cart_data').value = JSON.stringify(cart);
  return true;
}

function filterCat(cat, btn) {
  document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.cat-section').forEach(sec => {
    sec.style.display = (cat === 'all' || sec.dataset.section === cat) ? '' : 'none';
  });
}
</script>
</body>
</html>