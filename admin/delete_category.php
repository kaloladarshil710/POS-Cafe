<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php"); exit();
}
// CSRF check
if (!isset($_GET['csrf']) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_GET['csrf'])) {
    die('Invalid CSRF token.');
}
$id = safe_int($_GET['id'] ?? 0);
if ($id > 0) {
    $s = mysqli_prepare($conn,"DELETE FROM categories WHERE id=?");
    mysqli_stmt_bind_param($s,"i",$id); mysqli_stmt_execute($s); mysqli_stmt_close($s);
}
header("Location: categories.php"); exit();
