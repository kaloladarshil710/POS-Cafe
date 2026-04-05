<?php
include("../config/db.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    mysqli_query($conn, "DELETE FROM products WHERE id = $id");
}

header("Location: products.php");
exit();
?>