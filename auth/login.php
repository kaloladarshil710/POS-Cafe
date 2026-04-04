<?php
session_start();
include("../config/db.php");

$message = "";

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $message = "All fields are required!";
    } else {
        $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

        if (mysqli_num_rows($query) == 1) {
            $user = mysqli_fetch_assoc($query);

            // Check if account is active
            if ($user['status'] != 'active') {
                $message = "Your account is inactive!";
            }
            // Check password
            elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                // Role-based redirect
                if ($user['role'] == 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../pos/index.php");
                }
                exit();
            } else {
                $message = "Invalid password!";
            }
        } else {
            $message = "User not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - POS Cafe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #e0f2fe, #f8fafc);
            margin: 0;
            padding: 0;
        }
        .box {
            width: 400px;
            margin: 80px auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #0f172a;
        }
        input, button {
            width: 100%;
            padding: 12px;
            margin-top: 14px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        button {
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: 0.2s ease;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .msg {
            color: red;
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }
        .link {
            text-align: center;
            margin-top: 18px;
            font-size: 14px;
        }
        .link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>🔐 POS Cafe Login</h2>

    <form method="POST">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>

    <p class="msg"><?php echo $message; ?></p>
    <div class="link">
        Don't have an account? <a href="signup.php">Signup</a>
    </div>
</div>

</body>
</html>