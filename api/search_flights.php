<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$asal = $conn->real_escape_string($_POST['asal'] ?? '');
$tujuan = $conn->real_escape_string($_POST['tujuan'] ?? '');
$tanggal = $conn->real_escape_string($_POST['tanggal'] ?? '');

// Validasi input
if (empty($asal) || empty($tujuan) || empty($tanggal)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

// Query pencarian dengan LIKE untuk fleksibilitas 
$sql = "SELECT * FROM penerbangan 
        WHERE (asal LIKE '%$asal%')
        AND (tujuan LIKE '%$tujuan%')
        AND tanggal = '$tanggal'
        AND status = 'aktif'
        AND tersedia > 0
        ORDER BY jam_berangkat ASC";

$result = $conn->query($sql);

$flights = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $flights[] = [
            'id' => $row['id'],
            'maskapai' => $row['maskapai'],
            'kode_penerbangan' => $row['kode_penerbangan'],
            'asal' => $row['asal'],
            'tujuan' => $row['tujuan'],
            'tanggal' => $row['tanggal'],
            'jam_berangkat' => date('H:i', strtotime($row['jam_berangkat'])),
            'jam_tiba' => date('H:i', strtotime($row['jam_tiba'])),
            'harga' => (float)$row['harga'],
            'tersedia' => (int)$row['tersedia']
        ];
    }
}

echo json_encode([
    'success' => true,
    'data' => $flights,
    'total' => count($flights)
]);
?>