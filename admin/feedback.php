<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$message = $error = "";
$order_id = safe_int($_GET['order_id'] ?? 0);

// Get order details
if ($order_id > 0) {
    $order_q = mysqli_prepare($conn, "SELECT * FROM orders WHERE id=? AND status='paid' LIMIT 1");
    mysqli_stmt_bind_param($order_q, "i", $order_id);
    mysqli_stmt_execute($order_q);
    $order = mysqli_fetch_assoc(mysqli_stmt_get_result($order_q));
    mysqli_stmt_close($order_q);
} else {
    $order = null;
}

// Check if feedback already exists
if ($order) {
    $check_q = mysqli_prepare($conn, "SELECT id FROM feedback WHERE order_id=?");
    mysqli_stmt_bind_param($check_q, "i", $order_id);
    mysqli_stmt_execute($check_q);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($check_q));
    mysqli_stmt_close($check_q);
    
    if ($existing) {
        $message = "✅ Thank you! You've already provided feedback for this order.";
    }
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        die('CSRF check failed');
    }

    $order_id = safe_int($_POST['order_id']);
    $rating = safe_int($_POST['rating']);
    $service_rating = safe_int($_POST['service_rating']);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5 || $service_rating < 1 || $service_rating > 5) {
        $error = "Please provide valid ratings.";
    } elseif (strlen($comment) > 500) {
        $error = "Comment must be less than 500 characters.";
    } else {
        // Check order exists and belongs to user
        $check = mysqli_prepare($conn, "SELECT id FROM orders WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($check, "ii", $order_id, $_SESSION['user_id']);
        mysqli_stmt_execute($check);
        if (mysqli_stmt_get_result($check)->num_rows > 0) {
            $ins = mysqli_prepare($conn, 
                "INSERT INTO feedback (order_id, food_rating, service_rating, comment, created_at) 
                 VALUES (?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($ins, "iis", $order_id, $rating, $service_rating, $comment);
            
            if (mysqli_stmt_execute($ins)) {
                $message = "✅ Thank you for your feedback!";
                header("Refresh: 2; url=index.php");
            } else {
                $error = "Failed to save feedback.";
            }
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($check);
    }
}

// Ensure CSRF token exists
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Share Feedback — POS Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--primary:#F97316;--bg:#0A0A0F;--surface:#12121A;--border:rgba(255,255,255,0.08);--text:#F1F1F5;--text2:#9999B3;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);min-height:100vh;padding:20px;color:var(--text);}
.container{max-width:600px;margin:0 auto;background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:40px;}
.header{text-align:center;margin-bottom:30px;}
.header h1{font-size:28px;margin-bottom:8px;}
.header p{color:var(--text2);font-size:14px;}
.success{background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:#4ADE80;padding:14px;border-radius:10px;margin-bottom:20px;font-size:14px;}
.error{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#F87171;padding:14px;border-radius:10px;margin-bottom:20px;font-size:14px;}
.form-group{margin-bottom:24px;}
.form-group label{display:block;font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;}
.rating-group{display:flex;gap:10px;align-items:center;}
.star-btn{width:50px;height:50px;border:2px solid var(--border);background:transparent;border-radius:10px;font-size:20px;cursor:pointer;transition:all 0.2s;}
.star-btn:hover{border-color:var(--primary);background:rgba(249,115,22,0.1);}
.star-btn.active{background:var(--primary);border-color:var(--primary);color:#FFF;}
textarea{width:100%;padding:14px;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:10px;color:var(--text);font-family:inherit;font-size:14px;resize:vertical;min-height:100px;}
textarea:focus{outline:none;border-color:var(--primary);}
.char-count{font-size:12px;color:var(--text2);margin-top:6px;text-align:right;}
.btn{width:100%;padding:14px;background:var(--primary);color:white;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:15px;transition:0.15s;margin-top:10px;}
.btn:hover{background:#EA6C0A;}
.btn:disabled{background:#64748b;cursor:not-allowed;}
.order-summary{background:rgba(249,115,22,0.05);border:1px solid var(--border);padding:16px;border-radius:10px;margin-bottom:20px;}
.order-summary strong{color:var(--primary);}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📝 Share Your Feedback</h1>
        <p>Help us improve your dining experience</p>
    </div>

    <?php if ($message): ?><div class="success"><?php echo h($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?php echo h($error); ?></div><?php endif; ?>

    <?php if ($order && !$existing): ?>
    <div class="order-summary">
        <strong>Order #<?php echo h($order['order_number']); ?></strong> — ₹<?php echo number_format($order['total_amount'], 2); ?>
    </div>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
        <input type="hidden" name="submit_feedback" value="1">

        <div class="form-group">
            <label>⭐ How would you rate the food quality?</label>
            <div class="rating-group">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" class="star-btn" data-rating="food" data-value="<?php echo $i; ?>" onclick="setRating('food', <?php echo $i; ?>)">
                    <?php echo $i; ?>
                </button>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="food_rating" value="0">
        </div>

        <div class="form-group">
            <label>⭐ How would you rate the service?</label>
            <div class="rating-group">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" class="star-btn" data-rating="service" data-value="<?php echo $i; ?>" onclick="setRating('service', <?php echo $i; ?>)">
                    <?php echo $i; ?>
                </button>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="service_rating" id="service_rating" value="0">
        </div>

        <div class="form-group">
            <label>💬 Additional comments (optional)</label>
            <textarea name="comment" placeholder="Tell us what you think..." maxlength="500"></textarea>
            <div class="char-count"><span id="char-count">0</span>/500</div>
        </div>

        <button type="submit" class="btn" id="submit-btn" disabled>Submit Feedback</button>
    </form>
    <?php endif; ?>

    <script>
    function setRating(type, value) {
        document.getElementById(type === 'food' ? 'food_rating' : 'service_rating').value = value;
        document.querySelectorAll(`.star-btn[data-rating="${type}"]`).forEach(btn => {
            btn.classList.remove('active');
            if (parseInt(btn.dataset.value) <= value) {
                btn.classList.add('active');
            }
        });
        checkFormValid();
    }

    function checkFormValid() {
        const food = parseInt(document.getElementById('food_rating').value);
        const service = parseInt(document.getElementById('service_rating').value);
        document.getElementById('submit-btn').disabled = food === 0 || service === 0;
    }

    document.getElementById('comment').addEventListener('input', function() {
        document.getElementById('char-count').textContent = this.value.length;
    });

    checkFormValid();
    </script>
</div>
</body>
</html>
