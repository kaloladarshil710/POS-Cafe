<?php
include("../config/db.php");
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Optional protection: don't delete if products use this category
    $check = mysqli_query($conn, "SELECT * FROM products WHERE category_id = $id");
    if (mysqli_num_rows($check) > 0) {
        header("Location: categories.php");
        exit();
    }

    mysqli_query($conn, "DELETE FROM categories WHERE id = $id");
}

header("Location: categories.php");
exit();
?>