<?php
session_start();
include("../config/db.php");
if (isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }

$message = "";
if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (empty($email)||empty($password)) { $message="All fields are required."; }
    elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) { $message="Invalid email format."; }
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
<title>Sign In — POS Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#F7F4EF;
  --surface:#FFFFFF;
  --border:#E8E2D9;
  --primary:#C8602A;
  --primary-dark:#A84E20;
  --primary-light:#F5E9E2;
  --text:#1A1410;
  --text2:#6B5E52;
  --text3:#9C8E84;
  --sidebar:#1C1410;
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}

.wrap{display:grid;grid-template-columns:420px 1fr;max-width:960px;width:100%;background:var(--surface);border:1px solid var(--border);border-radius:24px;overflow:hidden;box-shadow:0 8px 48px rgba(28,20,16,0.12);}

/* LEFT PANEL */
.brand{background:var(--sidebar);padding:52px 44px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden;}
.brand::before{content:'';position:absolute;top:0;right:0;width:200px;height:200px;background:radial-gradient(circle at top right,rgba(200,96,42,0.15),transparent 70%);pointer-events:none;}
.brand::after{content:'';position:absolute;bottom:0;left:0;width:160px;height:160px;background:radial-gradient(circle at bottom left,rgba(200,96,42,0.08),transparent 70%);pointer-events:none;}

.brand-logo{display:flex;align-items:center;gap:10px;position:relative;z-index:1;}
.brand-logo-icon{width:38px;height:38px;background:var(--primary);border-radius:10px;display:flex;align-items:center;justify-content:center;}
.brand-logo-icon svg{width:20px;height:20px;color:white;}
.brand-logo-text{font-family:'DM Serif Display',serif;font-size:22px;color:white;letter-spacing:0.3px;}

.brand-body{position:relative;z-index:1;}
.brand-headline{font-family:'DM Serif Display',serif;font-size:32px;line-height:1.2;color:white;margin-bottom:14px;letter-spacing:-0.5px;}
.brand-headline em{color:var(--primary);font-style:normal;}
.brand-desc{font-size:14px;color:rgba(255,255,255,0.45);line-height:1.7;margin-bottom:36px;}

.features{display:flex;flex-direction:column;gap:14px;}
.feat{display:flex;align-items:center;gap:14px;}
.feat-dot{width:6px;height:6px;background:var(--primary);border-radius:50%;flex-shrink:0;}
.feat-text{font-size:13px;color:rgba(255,255,255,0.55);line-height:1.4;}

.brand-foot{font-size:12px;color:rgba(255,255,255,0.2);position:relative;z-index:1;}

/* RIGHT FORM */
.form-side{padding:52px 44px;display:flex;flex-direction:column;justify-content:center;}
.form-heading{font-family:'DM Serif Display',serif;font-size:30px;color:var(--text);margin-bottom:6px;letter-spacing:-0.3px;}
.form-sub{font-size:14px;color:var(--text3);margin-bottom:36px;line-height:1.5;}

.field{margin-bottom:20px;}
.field label{display:block;font-size:12px;font-weight:600;letter-spacing:0.4px;color:var(--text2);margin-bottom:8px;text-transform:uppercase;}
.field input{width:100%;padding:12px 16px;background:var(--bg);border:1.5px solid var(--border);border-radius:10px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:15px;transition:border-color 0.2s,background 0.2s;}
.field input:focus{outline:none;border-color:var(--primary);background:#fff;box-shadow:0 0 0 4px rgba(200,96,42,0.08);}
.field input::placeholder{color:var(--text3);}

.alert{background:var(--primary-light);color:var(--primary-dark);border:1px solid rgba(200,96,42,0.25);border-radius:10px;padding:12px 16px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.alert svg{width:16px;height:16px;flex-shrink:0;}

.login-btn{width:100%;padding:14px;background:var(--primary);color:white;border:none;border-radius:11px;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:600;cursor:pointer;transition:background 0.18s,transform 0.15s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px;}
.login-btn:hover{background:var(--primary-dark);transform:translateY(-1px);}
.login-btn svg{width:16px;height:16px;}

.divider{display:flex;align-items:center;gap:12px;margin:24px 0;color:var(--text3);font-size:12px;font-weight:500;letter-spacing:0.3px;text-transform:uppercase;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border);}

.demo-box{background:var(--bg);border:1px solid var(--border);border-radius:11px;padding:14px 16px;font-size:13px;color:var(--text3);line-height:1.9;}
.demo-box span{color:var(--text2);font-weight:500;}

.signup-link{text-align:center;margin-top:24px;font-size:13px;color:var(--text3);}
.signup-link a{color:var(--primary);text-decoration:none;font-weight:600;}
.signup-link a:hover{text-decoration:underline;}

@media(max-width:720px){.wrap{grid-template-columns:1fr;}.brand{display:none;}.form-side{padding:36px 24px;}}
</style>
</head>
<body>
<div class="wrap">
  <div class="brand">
    <div class="brand-logo">
      <div class="brand-logo-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 0 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V8z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>
      </div>
      <span class="brand-logo-text">POS Cafe</span>
    </div>
    <div class="brand-body">
      <h2 class="brand-headline">Restaurant ops,<br><em>simplified.</em></h2>
      <p class="brand-desc">Table ordering, kitchen display, and multi-payment — everything your team needs in one place.</p>
      <div class="features">
        <div class="feat"><div class="feat-dot"></div><span class="feat-text">Table-based floor ordering</span></div>
        <div class="feat"><div class="feat-dot"></div><span class="feat-text">Real-time kitchen display system</span></div>
        <div class="feat"><div class="feat-dot"></div><span class="feat-text">Cash, card &amp; UPI QR payments</span></div>
        <div class="feat"><div class="feat-dot"></div><span class="feat-text">Sales reports &amp; analytics</span></div>
      </div>
    </div>
    <div class="brand-foot">&copy; 2025 POS Cafe System</div>
  </div>

  <div class="form-side">
    <h1 class="form-heading">Welcome back</h1>
    <p class="form-sub">Sign in to your POS Cafe account to continue.</p>

    <?php if ($message): ?>
    <div class="alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="field">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com"
               value="<?php echo htmlspecialchars($_POST['email']??''); ?>" required autofocus>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password" required>
      </div>
      <button type="submit" name="login" class="login-btn">
        Sign In
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
    </form>

    <div class="divider">demo credentials</div>
    <div class="demo-box">
      <span>Email:</span> admin@poscafe.com<br>
      <span>Password:</span> admin123
    </div>

    <p class="signup-link">No account? <a href="signup.php">Create one</a></p>
  </div>
</div>
</body>
</html>
