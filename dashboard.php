<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - POS Cafe</title>
    <style>
        body {
            font-family: Arial;
            background: #eef2f3;
            text-align: center;
            padding-top: 100px;
        }
        .box {
            background: white;
            width: 500px;
            margin: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px #ccc;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            background: red;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="box">
    <h1>Welcome, <?php echo $_SESSION['user_name']; ?> 👋</h1>
    <h3>Role: <?php echo $_SESSION['user_role']; ?></h3>
    <p>You are successfully logged into POS Cafe System.</p>

    <a href="auth/logout.php">Logout</a>
</div>

</body>
</html>