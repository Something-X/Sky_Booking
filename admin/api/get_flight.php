<?php
require_once '../../config.php';

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

$sql = "SELECT * FROM penerbangan WHERE id = $id";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo json_encode([
        'success' => true,
        'data' => $result->fetch_assoc()
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
}
?>