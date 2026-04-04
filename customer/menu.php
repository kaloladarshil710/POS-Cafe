<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['customer_session_id'])) {
    header('Location: scan.php');
    exit;
}

$session_id = $_SESSION['customer_session_id'];
$table_id = $_SESSION['table_id'];

// Fetch active products
$stmt = mysqli_prepare($conn, '
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.status = "active" AND c.status = "active"
    ORDER BY c.name, p.name
');
mysqli_stmt_execute($stmt);
$products_result = mysqli_stmt_get_result($stmt);

// Fetch categories for filter
$cat_stmt = mysqli_query($conn, 'SELECT DISTINCT c.name FROM categories c JOIN products p ON p.category_id = c.id WHERE c.status = "active" ORDER BY c.name');
$categories = [];
while ($cat = mysqli_fetch_assoc($cat_stmt)) {
    $categories[] = $cat['name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - POS Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .product-card img { height: 200px; object-fit: cover; }
        .floating-cart {
            position: fixed;
            bottom: 20px; right: 20px;
            width: 380px; max-width: 90vw;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            z-index: 1000;
            transform: translateY(100%); transition: all 0.3s;
        }
        .floating-cart.show { transform: translateY(0); }
        .cart-item { border-bottom: 1px solid #eee; padding: 12px 0; }
        @media (max-width: 768px) { .floating-cart { width: 95vw; right: 2.5vw; } }
    </style>
</head>
<body class="bg-light">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="#">
                <i class="bi bi-cup-hot text-primary"></i> POS Cafe
            </a>
            <div class="d-flex align-items-center">
                <span class="me-3">
                    <strong><?php echo htmlspecialchars($_SESSION['customer_name']); ?></strong><br>
                    <small class="text-muted"><?php echo htmlspecialchars($_SESSION['mobile']); ?></small>
                </span>
                <button class="btn btn-outline-primary" onclick="toggleCart()">
                    <i class="bi bi-cart"></i> Cart (<span id="cart-count">0</span>)
                </button>
                <a href="logout.php" class="btn btn-link ms-2">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Search & Filter -->
    <div class="container my-4">
        <div class="row g-3 mb-4">
            <div class="col-md-5">
                <input type="text" class="form-control form-control-lg" id="search" placeholder="Search dishes...">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-lg" id="category-filter">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="range" class="form-range" id="price-filter" min="0" max="1000" value="1000">
                    <span class="input-group-text" id="price-value">₹0 - ₹1000</span>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="row g-4" id="products-grid">
            <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 product-item" data-category="<?php echo htmlspecialchars($product['category_name']); ?>" data-price="<?php echo $product['price']; ?>">
                <div class="card h-100 shadow-sm hover-shadow">
                    <img src="../assets/images/<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title fw-bold"><?php echo htmlspecialchars($product['name']); ?></h6>
                        <p class="card-text flex-grow-1 text-muted small"><?php echo htmlspecialchars($product['description'] ?: 'Delicious ' . $product['unit']); ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <span class="h5 text-primary fw-bold">₹<?php echo number_format($product['price'], 2); ?></span>
                            <button class="btn btn-primary btn-lg px-4" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>, '<?php echo htmlspecialchars($product['image']); ?>')">
                                <i class="bi bi-plus-lg"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <?php if (mysqli_num_rows($products_result) == 0): ?>
        <div class="text-center py-5">
            <i class="bi bi-emoji-frown display-1 text-muted mb-3"></i>
            <h4 class="text-muted">No products available</h4>
        </div>
        <?php endif; ?>
    </div>

    <!-- Floating Cart -->
    <div class="floating-cart" id="floating-cart">
        <div class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Your Cart (<span id="cart-total-items">0</span>)</h5>
                <button class="btn-close" onclick="toggleCart()"></button>
            </div>
            <div id="cart-items-list"></div>
            <hr>
            <div class="d-flex justify-content-between fs-5 fw-bold">
                <span>Total:</span>
                <span id="cart-total">₹0.00</span>
            </div>
            <button class="btn btn-success w-100 mt-3 py-3 fs-6 fw-bold" id="checkout-btn" onclick="checkout()">
                <i class="bi bi-credit-card"></i> Proceed to Checkout
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = JSON.parse(localStorage.getItem('customer_cart') || '[]');
        
        function addToCart(id, name, price, image) {
            const existing = cart.find(item => item.id === id);
            if (existing) {
                existing.quantity += 1;
            } else {
                cart.push({id, name, price, image, quantity: 1});
            }
            localStorage.setItem('customer_cart', JSON.stringify(cart));
            updateCartUI();
            showToast('Added to cart!', 'success');
        }
        
        function updateCartUI() {
            let totalItems = 0, totalAmount = 0;
            cart.forEach(item => {
                totalItems += item.quantity;
                totalAmount += item.price * item.quantity;
            });
            
            document.getElementById('cart-count').textContent = totalItems;
            document.getElementById('cart-total-items').textContent = totalItems;
            document.getElementById('cart-total').textContent = '₹' + totalAmount.toFixed(2);
            document.getElementById('checkout-btn').style.display = totalItems > 0 ? 'block' : 'none';
            
            // Update cart items list
            const list = document.getElementById('cart-items-list');
            if (cart.length === 0) {
                list.innerHTML = '<div class="text-center py-4 text-muted"><i class="bi bi-cart-x fs-1 mb-3"></i><p>Your cart is empty</p></div>';
            } else {
                list.innerHTML = cart.map(item => `
                    <div class="cart-item d-flex">
                        <img src="../assets/images/${item.image}" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-bold">${item.name}</div>
                            <small class="text-muted">₹${item.price}/ea</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm btn-outline-secondary" onclick="changeQty(${item.id}, -1)">−</button>
                            <span class="px-3 fw-bold">${item.quantity}</span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="changeQty(${item.id}, 1)">+</button>
                        </div>
                    </div>
                `).join('');
            }
        }
        
        function changeQty(id, delta) {
            const item = cart.find(item => item.id === id);
            if (item) {
                item.quantity += delta;
                if (item.quantity <= 0) {
                    cart = cart.filter(i => i.id !== id);
                }
                localStorage.setItem('customer_cart', JSON.stringify(cart));
                updateCartUI();
            }
        }
        
        function toggleCart() {
            const cart = document.getElementById('floating-cart');
            cart.classList.toggle('show');
        }
        
        function checkout() {
            if (cart.length === 0) return;
            window.location.href = 'place_order.php';
        }
        
        function showToast(message, type = 'info') {
            // Simple toast notification
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'info'} position-fixed`;
            toast.style.cssText = 'top:20px;right:20px;z-index:9999;max-width:300px;';
            toast.innerHTML = `<i class="bi bi-check-circle"></i> ${message}`;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 3000);
        }
        
        // Filters
        document.getElementById('search').addEventListener('input', filterProducts);
        document.getElementById('category-filter').addEventListener('change', filterProducts);
        document.getElementById('price-filter').addEventListener('input', function() {
            document.getElementById('price-value').textContent = '₹0 - ₹' + this.value;
            filterProducts();
        });
        
        function filterProducts() {
            const search = document.getElementById('search').value.toLowerCase();
            const category = document.getElementById('category-filter').value;
            const maxPrice = parseInt(document.getElementById('price-filter').value);
            
            document.querySelectorAll('.product-item').forEach(item => {
                const name = item.querySelector('.card-title').textContent.toLowerCase();
                const cat = item.dataset.category.toLowerCase();
                const price = parseFloat(item.dataset.price);
                
                let show = true;
                if (search && !name.includes(search)) show = false;
                if (category && cat !== category.toLowerCase()) show = false;
                if (price > maxPrice) show = false;
                
                item.style.display = show ? '' : 'none';
            });
        }
        
        // Initialize
        updateCartUI();
    </script>
</body>
</html>

<?php mysqli_stmt_close($stmt); ?>

