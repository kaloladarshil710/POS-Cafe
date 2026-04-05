<?php
session_start();
include("../config/db.php");

if ($_SESSION['user_role'] != 'admin') {
    header("Location: ../pos/index.php");
    exit();
}

$id = (int) $_GET['id'];

// prevent deleting self
if ($id == $_SESSION['user_id']) {
    header("Location: users.php");
    exit();
}

mysqli_query($conn, "DELETE FROM users WHERE id=$id");

header("Location: users.php");
exit();
?>