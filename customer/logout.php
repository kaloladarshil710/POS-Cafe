<?php
session_start();

// Destroy customer session
if (isset($_SESSION['customer_id'])) {
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_name']);
    unset($_SESSION['customer_email']);
    unset($_SESSION['customer_csrf']);
}

session_destroy();

header("Location: login.php");
exit();
?>