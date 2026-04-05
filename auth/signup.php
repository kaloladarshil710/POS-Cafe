<?php
session_start();
include("../config/db.php");

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$message = "";
$msg_type = "error";

if (isset($_POST['signup'])) {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = $_POST['role'] ?? 'staff';

    // Validate role to prevent privilege escalation
    if (!in_array($role, ['admin', 'staff'])) {
        $role = 'staff';
    }

    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address!";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters!";
    } else {
        // FIXED: Prepared statement to prevent SQL injection
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $message = "An account with this email already exists!";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashedPassword, $role);

            if (mysqli_stmt_execute($stmt)) {
                $msg_type = "success";
                $message = "Account created successfully!";
            } else {
                $message = "Something went wrong. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($check);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up — POS Cafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #FF6B35;
            --primary-dark: #E85520;
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
            padding: 24px;
        }

        body::before {
            content: '';
            position: fixed;
            top: -30%;
            right: -20%;
            width: 60%;
            height: 60%;
            background: radial-gradient(ellipse, rgba(255,107,53,0.1) 0%, transparent 65%);
            pointer-events: none;
        }

        .wrap {
            max-width: 500px;
            width: 100%;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 48px 44px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.5);
            position: relative;
            z-index: 1;
        }

        .logo {
            font-size: 22px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo span { color: var(--primary); }

        h3 {
            font-size: 26px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }

        .sub {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 36px;
        }

        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #aaa;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .field input, .field select {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 14px;
            color: var(--text);
            font-family: 'Sora', sans-serif;
            font-size: 15px;
            transition: all 0.2s ease;
            appearance: none;
        }

        .field input:focus, .field select:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255,107,53,0.06);
            box-shadow: 0 0 0 4px rgba(255,107,53,0.1);
        }

        .field input::placeholder { color: #555; }
        .field select option { background: #222; }

        .btn {
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
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(255,107,53,0.35);
        }

        .msg {
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .msg.error { background: rgba(239,68,68,0.12); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }
        .msg.success { background: rgba(34,197,94,0.12); color: #86efac; border: 1px solid rgba(34,197,94,0.2); }

        .login-link {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: var(--muted);
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="wrap">
    <div class="logo">POS <span>Cafe</span></div>

    <h3>Create Account</h3>
    <p class="sub">Set up your POS Cafe staff account</p>

    <?php if ($message): ?>
        <div class="msg <?php echo $msg_type; ?>">
            <?php echo ($msg_type === 'success') ? ' ' : ' '; ?>
            <?php echo htmlspecialchars($message); ?>
            <?php if ($msg_type === 'success'): ?>
                <br><a href="login.php" style="color: inherit; font-weight: 700;"> Login now</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="field">
            <label>Full Name</label>
            <input type="text" name="name" placeholder="John Smith"
                value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
        </div>
        <div class="field">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="john@poscafe.com"
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="field">
            <label>Password (min. 6 chars)</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <div class="field">
            <label>Role</label>
            <select name="role">
                <option value="staff">Staff (Cashier)</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" name="signup" class="btn">Create Account </button>
    </form>

    <p class="login-link">Already have an account? <a href="login.php">Sign in</a></p>
</div>

</body>
</html>