<?php
require_once '../../config.php';

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$status = $conn->real_escape_string($_POST['status'] ?? '');

if ($id === 0 || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

$sql = "UPDATE pemesanan SET status = '$status' WHERE id = $id";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal update status']);
}
?>