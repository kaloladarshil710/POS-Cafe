<?php
session_start();
include("../config/db.php");

// Both admin and staff can free a table
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['table_id']) || !is_numeric($_GET['table_id'])) {
    header("Location: index.php");
    exit();
}

$table_id = intval($_GET['table_id']);

// Free the table and reset the occupancy timer
$stmt = mysqli_prepare($conn, "UPDATE restaurant_tables SET status='free', occupied_since=NULL WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $table_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Log: we do NOT auto-mark pending orders as paid here.
// This is just a force-free (e.g. customer left without extra items, payment done separately).

header("Location: index.php?freed=1");
exit();
?>
