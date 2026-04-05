<?php
include("../config/db.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id     = intval($_POST['id']);
    $status = trim($_POST['status']);

    if ($id <= 0 || !in_array($status, ['active', 'inactive'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE categories SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'status' => $status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB error']);
    }

    $stmt->close();
    $conn->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>