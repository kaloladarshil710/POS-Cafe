<?php
session_start();
include("../config/db.php");
if (isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];
$message = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) die('CSRF check failed');

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['admin','staff']) ? $_POST['role'] : 'staff';

    if (empty($name)||empty($email)||empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email=?");
        mysqli_stmt_bind_param($chk, "s", $email);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = "Email already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = mysqli_prepare($conn, "INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($ins, "ssss", $name, $email, $hashed, $role);
            if (mysqli_stmt_execute($ins)) {
                $message = "Account created! You can now log in.";
            } else {
                $error = "Failed to create account.";
            }
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($chk);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign Up — POS Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--primary:#F97316;--bg:#0A0A0F;--surface:#12121A;--border:rgba(255,255,255,0.08);--text:#F1F1F5;--text2:#9999B3;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.box{background:var(--surface);border:1px solid var(--border);border-radius:24px;padding:40px 36px;width:100%;max-width:420px;}
.logo{font-size:22px;font-weight:800;color:var(--text);margin-bottom:24px;text-align:center;} .logo span{color:var(--primary);}
h2{font-size:20px;font-weight:800;color:var(--text);margin-bottom:6px;}
p.sub{font-size:14px;color:var(--text2);margin-bottom:24px;}
label{font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;}
input,select{width:100%;padding:12px 16px;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:12px;color:var(--text);font-family:inherit;font-size:14px;transition:.15s;margin-bottom:14px;}
input:focus,select:focus{outline:none;border-color:var(--primary);}
select option{background:#1A1A26;}
.btn{width:100%;padding:14px;background:var(--primary);color:white;border:none;border-radius:12px;font-family:inherit;font-size:15px;font-weight:800;cursor:pointer;transition:.15s;}
.btn:hover{background:#EA6C0A;}
.msg-success{padding:12px 14px;background:rgba(18,183,106,0.1);border:1px solid rgba(18,183,106,0.25);color:#4ade80;border-radius:10px;font-size:14px;margin-bottom:16px;}
.msg-error{padding:12px 14px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#F87171;border-radius:10px;font-size:14px;margin-bottom:16px;}
.link{text-align:center;margin-top:16px;font-size:13px;color:var(--text2);}
.link a{color:var(--primary);text-decoration:none;font-weight:700;}
</style>
</head>
<body>
<div class="box">
    <div class="logo">POS <span>Cafe</span></div>
    <h2>Create Account</h2>
    <p class="sub">Set up your POS Cafe access</p>
    <?php if($message): ?><div class="msg-success"><?php echo h($message); ?> <a href="login.php" style="color:inherit;font-weight:700;">Login →</a></div><?php endif; ?>
    <?php if($error):   ?><div class="msg-error"><?php echo h($error); ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
        <label>Full Name</label>
        <input type="text" name="name" required maxlength="100" placeholder="Your Name">
        <label>Email</label>
        <input type="email" name="email" required maxlength="120" placeholder="you@example.com">
        <label>Password (min 6 chars)</label>
        <input type="password" name="password" required placeholder="••••••••">
        <label>Role</label>
        <select name="role"><option value="staff">Staff</option><option value="admin">Admin</option></select>
        <button type="submit" name="signup" class="btn">Create Account →</button>
    </form>
    <p class="link">Already have an account? <a href="login.php">Sign in</a></p>
</div>
</body>
</html>
