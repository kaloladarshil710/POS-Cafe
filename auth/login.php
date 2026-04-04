<?php
session_start();
include("../config/db.php");
if (isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }

$message = "";
if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (empty($email)||empty($password)) { $message="All fields are required!"; }
    elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) { $message="Invalid email format!"; }
    else {
        $stmt=mysqli_prepare($conn,"SELECT * FROM users WHERE email=?");
        mysqli_stmt_bind_param($stmt,"s",$email);
        mysqli_stmt_execute($stmt);
        $result=mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result)==1) {
            $user=mysqli_fetch_assoc($result);
            if ($user['status']!=='active') { $message="Account inactive. Contact admin."; }
            elseif (password_verify($password,$user['password'])) {
                $_SESSION['user_id']=$user['id'];
                $_SESSION['user_name']=$user['name'];
                $_SESSION['user_role']=$user['role'];
                header("Location: ".($user['role']==='admin'?'../admin/dashboard.php':'../pos/index.php'));
                exit();
            } else { $message="Incorrect password."; }
        } else { $message="No account found with that email."; }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — POS Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{--bg:#0A0A0F;--surface:#12121A;--border:rgba(255,255,255,0.08);--primary:#F97316;--primary-dark:#EA6C0A;--text:#F1F1F5;--muted:#9999B3;--dim:#555570;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;overflow:hidden;}
body::before{content:'';position:fixed;top:-30%;left:-20%;width:60%;height:60%;background:radial-gradient(ellipse,rgba(249,115,22,0.1) 0%,transparent 65%);pointer-events:none;}
body::after{content:'';position:fixed;bottom:-20%;right:-10%;width:50%;height:50%;background:radial-gradient(ellipse,rgba(249,115,22,0.04) 0%,transparent 70%);pointer-events:none;}

.wrap{display:grid;grid-template-columns:1fr 1fr;max-width:920px;width:100%;background:var(--surface);border:1px solid var(--border);border-radius:28px;overflow:hidden;box-shadow:0 40px 100px rgba(0,0,0,0.7);position:relative;z-index:1;}

/* left brand */
.brand{padding:52px 44px;display:flex;flex-direction:column;justify-content:space-between;border-right:1px solid var(--border);background:linear-gradient(145deg,#0F0F18,#0A0A0F);position:relative;overflow:hidden;}
.brand::before{content:'';position:absolute;top:-60px;right:-60px;width:260px;height:260px;background:radial-gradient(circle,rgba(249,115,22,0.12) 0%,transparent 70%);border-radius:50%;}
.brand-logo{font-size:26px;font-weight:800;position:relative;z-index:1;}
.brand-logo span{color:var(--primary);}
.brand-body{position:relative;z-index:1;}
.brand-body h2{font-size:30px;font-weight:800;line-height:1.2;margin-bottom:14px;letter-spacing:-0.5px;}
.brand-body p{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:28px;}
.features{display:flex;flex-direction:column;gap:12px;}
.feat{display:flex;align-items:center;gap:12px;font-size:14px;color:#888;}
.feat-icon{width:38px;height:38px;background:rgba(249,115,22,0.1);border:1px solid rgba(249,115,22,0.15);border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;}
.brand-foot{font-size:12px;color:var(--dim);position:relative;z-index:1;}

/* right form */
.form-side{padding:52px 44px;display:flex;flex-direction:column;justify-content:center;}
.form-side h3{font-size:24px;font-weight:800;margin-bottom:6px;letter-spacing:-0.3px;}
.form-side .sub{font-size:14px;color:var(--muted);margin-bottom:32px;}
.field{margin-bottom:18px;}
.field label{display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#777;margin-bottom:7px;}
.field input{width:100%;padding:13px 16px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:12px;color:var(--text);font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;transition:0.2s;}
.field input:focus{outline:none;border-color:var(--primary);background:rgba(249,115,22,0.05);box-shadow:0 0 0 4px rgba(249,115,22,0.1);}
.field input::placeholder{color:#444;}
.login-btn{width:100%;padding:15px;background:var(--primary);color:white;border:none;border-radius:12px;font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:700;cursor:pointer;transition:0.18s;margin-top:6px;letter-spacing:0.2px;}
.login-btn:hover{background:var(--primary-dark);transform:translateY(-2px);box-shadow:0 12px 30px rgba(249,115,22,0.35);}
.msg-error{background:rgba(239,68,68,0.1);color:#FCA5A5;border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:12px 15px;font-size:13px;font-weight:600;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
.divider{display:flex;align-items:center;gap:12px;margin:22px 0;color:#333;font-size:12px;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border);}
.demo-box{background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:11px;padding:13px 15px;font-size:13px;color:#555;line-height:1.8;}
.demo-box strong{color:#888;}
.signup-link{text-align:center;margin-top:22px;font-size:13px;color:var(--muted);}
.signup-link a{color:var(--primary);text-decoration:none;font-weight:700;}

@media(max-width:700px){.wrap{grid-template-columns:1fr;}.brand{display:none;}.form-side{padding:36px 24px;}}
</style>
</head>
<body>
<div class="wrap">
  <div class="brand">
    <div class="brand-logo">POS <span>Cafe</span></div>
    <div class="brand-body">
      <h2>Restaurant POS that just works.</h2>
      <p>Table ordering, kitchen display, multi-payment — all in one clean system.</p>
      <div class="features">
        <div class="feat"><div class="feat-icon">🍽️</div><span>Table-based floor ordering</span></div>
        <div class="feat"><div class="feat-icon">👨‍🍳</div><span>Real-time kitchen display</span></div>
        <div class="feat"><div class="feat-icon">💳</div><span>Cash, Card & UPI QR payments</span></div>
        <div class="feat"><div class="feat-icon">📊</div><span>Sales reports & analytics</span></div>
      </div>
    </div>
    <div class="brand-foot">© 2025 POS Cafe System</div>
  </div>

  <div class="form-side">
    <h3>Welcome back 👋</h3>
    <p class="sub">Sign in to your POS Cafe account</p>

    <?php if ($message): ?>
    <div class="msg-error">⚠️ <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="field">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="admin@poscafe.com"
               value="<?php echo htmlspecialchars($_POST['email']??''); ?>" required autofocus>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" name="login" class="login-btn">Sign In →</button>
    </form>

    <div class="divider">or use demo</div>
    <div class="demo-box">
      <strong>Demo Account</strong><br>
      Email: admin@poscafe.com<br>
      Password: admin123
    </div>

    <p class="signup-link">No account? <a href="signup.php">Create one →</a></p>
  </div>
</div>
</body>
</html>
