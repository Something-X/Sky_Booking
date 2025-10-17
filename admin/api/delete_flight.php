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

if ($id === 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

// Cek apakah ada pemesanan yang terkait
$check = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE id_penerbangan = $id");
if ($check) {
    $row = $check->fetch_assoc();
    if ($row['total'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus. Ada pemesanan yang terkait dengan penerbangan ini.']);
        exit;
    }
}

$sql = "DELETE FROM penerbangan WHERE id = $id";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus data: ' . $conn->error]);
}
?>