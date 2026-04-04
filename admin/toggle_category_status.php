<?php
session_start();
include("../config/db.php");

header('Content-Type: application/json');

// Auth
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit();
}

// CSRF
$csrf = $_POST['csrf'] ?? '';
if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
    echo json_encode(['success'=>false,'error'=>'Invalid CSRF']); exit();
}

$id     = safe_int($_POST['id'] ?? 0);
$status = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : null;

if ($id <= 0 || $status === null) {
    echo json_encode(['success'=>false,'error'=>'Bad input']); exit();
}

$stmt = mysqli_prepare($conn, "UPDATE categories SET status=? WHERE id=?");
mysqli_stmt_bind_param($stmt, "si", $status, $id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['success' => $ok]);
