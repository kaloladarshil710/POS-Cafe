<?php
// ============================================================
// POS Cafe - Database Configuration
// ============================================================

$host = "localhost";
$user = "root";
$pass = "";
$db   = "pos_cafe";

// Establish connection
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8 for proper encoding
mysqli_set_charset($conn, "utf8mb4");

// Helper: Sanitize input (prevents XSS + SQL injection in non-prepared contexts)
function sanitize($conn, $value) {
    return mysqli_real_escape_string($conn, trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')));
}
?>