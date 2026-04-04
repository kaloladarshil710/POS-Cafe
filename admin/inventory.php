<?php
include("../config/db.php");
include("layout/header.php");

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$message = $error = "";

// ── ADD STOCK ENTRY ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) die('CSRF check failed');

    $product_id = safe_int($_POST['product_id']);
    $quantity   = safe_int($_POST['quantity']);
    $action     = trim($_POST['action'] ?? 'add'); // add, reduce, reset
    $notes      = trim($_POST['notes'] ?? '');

    if ($product_id <= 0 || $quantity <= 0) {
        $error = "Invalid product or quantity.";
    } else {
        // Get product name for logging
        $prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM products WHERE id=$product_id"));
        
        if (!$prod) {
            $error = "Product not found.";
        } else {
            // Update stock
            if ($action === 'add') {
                mysqli_query($conn, "UPDATE inventory SET quantity = quantity + $quantity WHERE product_id = $product_id");
            } elseif ($action === 'reduce') {
                mysqli_query($conn, "UPDATE inventory SET quantity = GREATEST(quantity - $quantity, 0) WHERE product_id = $product_id");
            } else {
                mysqli_query($conn, "UPDATE inventory SET quantity = $quantity WHERE product_id = $product_id");
            }
            
            // Log the transaction
            $ins = mysqli_prepare($conn, 
                "INSERT INTO inventory_logs (product_id, action, quantity, notes, user_id) 
                 VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($ins, "isisi", $product_id, $action, $quantity, $notes, $_SESSION['user_id']);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
            
            $message = "✅ Stock updated for {$prod['name']}!";
        }
    }
}

// ── GET CURRENT INVENTORY ──────────────────────────
$inventory = mysqli_query($conn, "
    SELECT 
        p.id, p.name, c.category_name,
        COALESCE(i.quantity, 0) as current_stock,
        COALESCE(i.reorder_level, 10) as reorder_level,
        COALESCE(i.max_stock, 100) as max_stock,
        COALESCE(i.unit, 'units') as unit,
        (COALESCE(i.quantity, 0) <= COALESCE(i.reorder_level, 10)) as is_low as is_low_stock
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN inventory i ON p.id = i.product_id
    WHERE c.status = 'active'
    ORDER BY is_low_stock DESC, p.name ASC
");

// ── GET RECENT STOCK LOGS ──────────────────────────
$logs = mysqli_query($conn, "
    SELECT 
        il.*, p.name, u.name as staff_name
    FROM inventory_logs il
    JOIN products p ON il.product_id = p.id
    JOIN users u ON il.user_id = u.id
    ORDER BY il.created_at DESC
    LIMIT 50
");

// Get products for dropdown
$products = mysqli_query($conn, "SELECT id, name FROM products WHERE status='active' ORDER BY name");
?>

<div class="panel">
    <h2>📦 Inventory Management</h2>
    <?php if($message): ?><div class="msg-success"><?php echo h($message); ?></div><?php endif; ?>
    <?php if($error):   ?><div class="msg-error"><?php echo h($error); ?></div><?php endif; ?>
</div>

<!-- ADD STOCK FORM -->
<div class="panel" style="margin-bottom:24px;">
    <h3>➕ Add Stock Entry</h3>
    <form method="POST" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;align-items:flex-end;">
        <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
        
        <div>
            <label style="font-size:12px;color:#94a3b8;display:block;margin-bottom:6px;font-weight:700;">Product</label>
            <select name="product_id" required style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--surface);">
                <option value="">Select Product...</option>
                <?php mysqli_data_seek($products, 0); while ($p = mysqli_fetch_assoc($products)): ?>
                <option value="<?php echo $p['id']; ?>"><?php echo h($p['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label style="font-size:12px;color:#94a3b8;display:block;margin-bottom:6px;font-weight:700;">Quantity</label>
            <input type="number" name="quantity" required min="1" placeholder="0" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;">
        </div>

        <div>
            <label style="font-size:12px;color:#94a3b8;display:block;margin-bottom:6px;font-weight:700;">Action</label>
            <select name="action" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--surface);">
                <option value="add">Add Stock</option>
                <option value="reduce">Reduce Stock</option>
                <option value="reset">Reset Stock</option>
            </select>
        </div>

        <div>
            <label style="font-size:12px;color:#94a3b8;display:block;margin-bottom:6px;font-weight:700;">Notes</label>
            <input type="text" name="notes" placeholder="e.g., Delivery received" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;">
        </div>

        <button type="submit" name="add_stock" class="btn-primary" style="width:100%;">Update Stock</button>
    </form>
</div>

<!-- CURRENT INVENTORY -->
<div class="panel" style="margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">📊 Current Stock Levels</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Current Stock</th>
                    <th>Reorder Level</th>
                    <th>Max Level</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($inventory)): ?>
                <tr style="<?php echo $row['is_low_stock'] ? 'background:rgba(239,68,68,0.05);' : ''; ?>">
                    <td><strong><?php echo h($row['name']); ?></strong></td>
                    <td><?php echo h($row['category_name']); ?></td>
                    <td style="text-align:right;">
                        <span style="background:<?php echo $row['is_low_stock'] ? '#EF4444' : '#22C55E'; ?>;color:white;padding:4px 8px;border-radius:6px;font-weight:700;">
                            <?php echo $row['current_stock']; ?> <?php echo h($row['unit']); ?>
                        </span>
                    </td>
                    <td style="text-align:right;"><?php echo $row['reorder_level']; ?></td>
                    <td style="text-align:right;"><?php echo $row['max_stock']; ?></td>
                    <td style="text-align:center;">
                        <?php if ($row['is_low_stock']): ?>
                            <span style="background:#EF4444;color:white;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:700;">⚠️ LOW STOCK</span>
                        <?php elseif ($row['current_stock'] >= $row['max_stock']): ?>
                            <span style="background:#3B82F6;color:white;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:700;">✓ FULL</span>
                        <?php else: ?>
                            <span style="background:#22C55E;color:white;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:700;">✓ OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- RECENT ACTIVITY LOG -->
<div class="panel">
    <h3 style="margin-bottom:16px;">📜 Recent Stock Changes</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Product</th>
                    <th>Action</th>
                    <th>Quantity</th>
                    <th>Staff</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($logs) === 0): ?>
                <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:20px;">No stock changes yet.</td></tr>
                <?php else: while ($log = mysqli_fetch_assoc($logs)): ?>
                <tr>
                    <td style="font-size:12px;color:#94a3b8;"><?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?></td>
                    <td><strong><?php echo h($log['name']); ?></strong></td>
                    <td>
                        <span style="background:<?php 
                            echo $log['action'] === 'add' ? '#22C55E' : 
                                ($log['action'] === 'reduce' ? '#F97316' : '#3B82F6'); 
                        ?>;color:white;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:700;">
                            <?php echo ucfirst(h($log['action'])); ?>
                        </span>
                    </td>
                    <td style="text-align:right;font-weight:700;"><?php echo $log['quantity']; ?></td>
                    <td><?php echo h($log['staff_name']); ?></td>
                    <td style="font-size:12px;color:#94a3b8;"><?php echo h($log['notes'] ?? '—'); ?></td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include("layout/footer.php"); ?>