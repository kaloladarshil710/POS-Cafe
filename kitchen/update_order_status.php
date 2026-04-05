Here is the full `update_order_status.php` code:

```php
<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Support both ?order_id=1 (legacy) and ?order_ids=1,2,3 (merged tables)
if (!empty($_GET['order_ids'])) {
    $raw = $_GET['order_ids'];
} elseif (!empty($_GET['order_id'])) {
    $raw = $_GET['order_id'];
} else {
    header("Location: kitchen.php");
    exit();
}

// Sanitize: digits and commas only
$raw = preg_replace('/[^0-9,]/', '', $raw);
$ids = array_filter(array_map('intval', explode(',', $raw)));

if (empty($ids)) {
    header("Location: kitchen.php");
    exit();
}

$ids_str = implode(',', $ids);

// Get current status from first order
$query = mysqli_query($conn, "SELECT status FROM orders WHERE id IN ($ids_str) LIMIT 1");
$order = mysqli_fetch_assoc($query);

if ($order) {
    if ($order['status'] == 'to_cook') {
        $new_status = 'preparing';
    } elseif ($order['status'] == 'preparing') {
        $new_status = 'completed';
    } else {
        header("Location: kitchen.php");
        exit();
    }

    mysqli_query($conn, "UPDATE orders SET status = '$new_status' WHERE id IN ($ids_str)");
}

header("Location: kitchen.php");
exit();
?>
```