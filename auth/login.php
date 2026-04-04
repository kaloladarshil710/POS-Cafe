<?php
session_start();
include("../config/db.php");

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$message = "";
$msg_type = "error";

if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $message = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
    } else {
        // FIXED: Use prepared statement to prevent SQL injection
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            if ($user['status'] !== 'active') {
                $message = "Your account is inactive. Please contact admin.";
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../pos/index.php");
                }
                exit();
            } else {
                $message = "Invalid password!";
            }
        } else {
            $message = "No account found with that email!";
        }
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
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #FF6B35;
            --primary-dark: #E85520;
            --secondary: #2D3142;
            --bg: #0D0D0D;
            --card: #161616;
            --border: rgba(255,255,255,0.08);
            --text: #F5F5F5;
            --muted: #888;
        }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Ambient background glow */
        body::before {
            content: '';
            position: fixed;
            top: -30%;
            left: -20%;
            width: 70%;
            height: 70%;
            background: radial-gradient(ellipse, rgba(255,107,53,0.12) 0%, transparent 65%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -20%;
            right: -10%;
            width: 50%;
            height: 50%;
            background: radial-gradient(ellipse, rgba(45,49,66,0.6) 0%, transparent 70%);
            pointer-events: none;
        }

        .auth-wrap {
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-width: 960px;
            width: 100%;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 40px 100px rgba(0,0,0,0.6);
            position: relative;
            z-index: 1;
        }

        /* Left branding panel */
        .brand-panel {
            background: linear-gradient(145deg, #1a1a1a, #111);
            padding: 56px 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 240px;
            height: 240px;
            background: radial-gradient(circle, rgba(255,107,53,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.5px;
        }

        .logo span { color: var(--primary); }

        .brand-tagline {
            margin-top: 40px;
        }

        .brand-tagline h2 {
            font-size: 34px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.25;
            margin-bottom: 18px;
        }

        .brand-tagline p {
            font-size: 15px;
            color: var(--muted);
            line-height: 1.7;
        }

        .features {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 40px;
        }

        .feat {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: #aaa;
        }

        .feat-icon {
            width: 36px;
            height: 36px;
            background: rgba(255,107,53,0.12);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        /* Right form panel */
        .form-panel {
            padding: 56px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-panel h3 {
            font-size: 26px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }

        .form-panel .sub {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 36px;
        }

        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #aaa;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .field input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 14px;
            color: var(--text);
            font-family: 'Sora', sans-serif;
            font-size: 15px;
            transition: all 0.2s ease;
        }

        .field input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255,107,53,0.06);
            box-shadow: 0 0 0 4px rgba(255,107,53,0.1);
        }

        .field input::placeholder { color: #555; }

        .login-btn {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 14px;
            font-family: 'Sora', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 8px;
            letter-spacing: 0.3px;
        }

        .login-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(255,107,53,0.35);
        }

        .login-btn:active { transform: translateY(0); }

        .msg {
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .msg.error { background: rgba(239,68,68,0.12); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }
        .msg.success { background: rgba(34,197,94,0.12); color: #86efac; border: 1px solid rgba(34,197,94,0.2); }

        .signup-link {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: var(--muted);
        }

        .signup-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 24px 0;
            color: #444;
            font-size: 13px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .demo-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 13px;
            color: #666;
            line-height: 1.7;
        }

        .demo-box strong { color: #aaa; }

        @media (max-width: 768px) {
            .auth-wrap { grid-template-columns: 1fr; }
            .brand-panel { display: none; }
            .form-panel { padding: 40px 28px; }
        }
    </style>
</head>
<body>

<div class="auth-wrap">
    <!-- Left Branding -->
    <div class="brand-panel">
        <div class="logo">POS <span>Cafe</span></div>

        <div class="brand-tagline">
            <h2>Restaurant POS that just works.</h2>
            <p>Manage tables, orders, kitchen flow, and payments — all in one modern system.</p>

            <div class="features">
                <div class="feat">
                    <div class="feat-icon">🍽️</div>
                    <span>Table-based ordering system</span>
                </div>
                <div class="feat">
                    <div class="feat-icon">👨‍🍳</div>
                    <span>Real-time kitchen display</span>
                </div>
                <div class="feat">
                    <div class="feat-icon">💳</div>
                    <span>Cash, Card & UPI QR payments</span>
                </div>
                <div class="feat">
                    <div class="feat-icon">📊</div>
                    <span>Sales reports & dashboard</span>
                </div>
            </div>
        </div>

        <div style="color:#444; font-size:13px;">© 2024 POS Cafe System</div>
    </div>

    <!-- Right Form -->
    <div class="form-panel">
        <h3>Welcome back 👋</h3>
        <p class="sub">Sign in to your POS Cafe account</p>

        <?php if ($message): ?>
            <div class="msg error">⚠️ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="admin@poscafe.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" name="login" class="login-btn">Sign In →</button>
        </form>

        <div class="divider">or</div>

        <div class="demo-box">
            <strong>Demo Credentials:</strong><br>
            Email: admin@poscafe.com<br>
            Password: admin123
        </div>

        <p class="signup-link">
            Don't have an account? <a href="signup.php">Create one</a>
        </p>
    </div>
</div>

</body>
</html>