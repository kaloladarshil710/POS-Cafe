<?php
session_start();
include("../config/db.php");

$message = "";

if (isset($_POST['signup'])) {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'];

    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required!";
    } else {
        $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $message = "Email already exists!";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insert = mysqli_query($conn, "INSERT INTO users(name, email, password, role)
                                           VALUES('$name', '$email', '$hashedPassword', '$role')");

            if ($insert) {
                $message = "Signup successful! <a href='login.php'>Login here</a>";
            } else {
                $message = "Something went wrong!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Signup - POS Cafe</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f4f4;
        }
        .box {
            width: 400px;
            margin: 60px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin-top: 12px;
        }
        button {
            background: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        .msg {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>POS Cafe Signup</h2>
    <form method="POST">
        <input type="text" name="name" placeholder="Full Name">
        <input type="email" name="email" placeholder="Email Address">
        <input type="password" name="password" placeholder="Password">

        <select name="role">
            <option value="admin">Admin</option>
            <option value="staff">Staff</option>
        </select>

        <button type="submit" name="signup">Signup</button>
    </form>

    <p class="msg"><?php echo $message; ?></p>
    <p>Already have an account? <a href="login.php">Login</a></p>
</div>

</body>
</html>