<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$sql = "SELECT 
    p.id,
    p.kode_penerbangan,
    p.maskapai,
    p.asal,
    p.tujuan,
    p.tanggal,
    p.jam_tiba,
    p.pesawat_id,
    p.aircraft_type,
    ps.nomor_registrasi as pesawat_nomor,
    ps.model as pesawat_model
FROM penerbangan p
LEFT JOIN pesawat ps ON p.pesawat_id = ps.id
WHERE p.status_tracking = 'Arrived'
AND p.status = 'aktif'
ORDER BY p.tanggal DESC, p.jam_tiba DESC";

$result = $conn->query($sql);

if ($result) {
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}
?>