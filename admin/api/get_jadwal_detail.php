<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

$id = $conn->real_escape_string($id);

$sql = "SELECT 
    p.*,
    ps.nomor_registrasi,
    ps.model,
    ps.maskapai as maskapai_pesawat
FROM penerbangan p
LEFT JOIN pesawat ps ON p.pesawat_id = ps.id
WHERE p.id = '$id'";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
}
?>