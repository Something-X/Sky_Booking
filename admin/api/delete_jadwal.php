<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = $_POST['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

$id = $conn->real_escape_string($id);

// Check if there are bookings
$check = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE id_penerbangan = '$id' AND status != 'batal'");
if ($check) {
    $row = $check->fetch_assoc();
    if ($row['total'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus jadwal yang sudah memiliki pemesanan aktif']);
        exit;
    }
}

// Soft delete - update status instead of deleting
$sql = "UPDATE penerbangan SET status = 'batal' WHERE id = '$id'";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Jadwal berhasil dihapus']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}
?>