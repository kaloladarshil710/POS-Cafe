<?php
session_start();
include("../config/db.php");
if (isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }

// Regenerate CSRF on page load
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) die('CSRF check failed');

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email=? AND status='active' LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID on login (session fixation prevention)
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['csrf']      = bin2hex(random_bytes(32));
            header("Location: ../index.php"); exit();
        } else {
            // Generic message to prevent user enumeration
            $message = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — POS Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--primary:#F97316;--bg:#0A0A0F;--surface:#12121A;--border:rgba(255,255,255,0.08);--text:#F1F1F5;--text2:#9999B3;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.box{background:var(--surface);border:1px solid var(--border);border-radius:24px;padding:40px 36px;width:100%;max-width:420px;box-shadow:0 40px 80px rgba(0,0,0,0.5);}
.logo{font-size:22px;font-weight:800;color:var(--text);margin-bottom:28px;text-align:center;} .logo span{color:var(--primary);}
h2{font-size:22px;font-weight:800;color:var(--text);margin-bottom:6px;}
p.sub{font-size:14px;color:var(--text2);margin-bottom:28px;}
label{font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;}
input{width:100%;padding:12px 16px;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:12px;color:var(--text);font-family:inherit;font-size:14px;transition:.15s;}
input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(249,115,22,0.15);}
.field{margin-bottom:16px;}
.btn{width:100%;padding:14px;background:var(--primary);color:white;border:none;border-radius:12px;font-family:inherit;font-size:15px;font-weight:800;cursor:pointer;transition:.15s;margin-top:8px;}
.btn:hover{background:#EA6C0A;}
.msg{padding:12px 14px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#F87171;border-radius:10px;font-size:14px;margin-bottom:18px;}
.link{text-align:center;margin-top:20px;font-size:13px;color:var(--text2);}
.link a{color:var(--primary);text-decoration:none;font-weight:700;}
</style>
</head>
<body>
<div class="box">
    <div class="logo">POS <span>Cafe</span></div>
    <h2>Welcome back 👋</h2>
    <p class="sub">Sign in to your account</p>
    <?php if($message): ?><div class="msg"><?php echo h($message); ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
        <div class="field"><label>Email</label><input type="email" name="email" required autofocus placeholder="you@example.com"></div>
        <div class="field"><label>Password</label><input type="password" name="password" required placeholder="••••••••"></div>
        <button type="submit" name="login" class="btn">Sign In →</button>
    </form>
    <p class="link">No account? <a href="signup.php">Sign up</a></p>
</div>
</body>
</html>
