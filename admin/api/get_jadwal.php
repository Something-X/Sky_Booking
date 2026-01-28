<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$search = $_GET['search'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';
$status = $_GET['status'] ?? '';

// Query langsung ambil dari kolom asal dan tujuan (sudah berisi "Jakarta (CGK)")
$sql = "SELECT 
    p.id,
    p.kode_penerbangan,
    p.maskapai,
    p.asal,
    p.tujuan,
    p.tanggal,
    p.jam_berangkat,
    p.jam_tiba,
    p.harga,
    p.kapasitas,
    p.tersedia,
    p.status_tracking,
    p.pesawat_id,
    p.origin_airport_id,
    p.destination_airport_id,
    ps.nomor_registrasi as pesawat_nomor,
    ps.model as pesawat_model,
    ps.kelas_layanan
FROM penerbangan p
LEFT JOIN pesawat ps ON p.pesawat_id = ps.id
WHERE p.status = 'aktif'";

if ($search) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (p.kode_penerbangan LIKE '%$search%' OR p.maskapai LIKE '%$search%')";
}

if ($tanggal) {
    $tanggal = $conn->real_escape_string($tanggal);
    $sql .= " AND p.tanggal = '$tanggal'";
}

if ($status) {
    $status = $conn->real_escape_string($status);
    $sql .= " AND p.status_tracking = '$status'";
}

$sql .= " ORDER BY p.tanggal DESC, p.jam_berangkat DESC";

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