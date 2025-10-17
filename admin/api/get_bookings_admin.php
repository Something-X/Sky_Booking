<?php
require_once '../../config.php';

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$search = $conn->real_escape_string($_GET['search'] ?? '');
$status = $conn->real_escape_string($_GET['status'] ?? '');

$sql = "SELECT p.*, 
        pe.maskapai, pe.kode_penerbangan, pe.asal, pe.tujuan,
        DATE_FORMAT(pe.tanggal, '%d %M %Y') as tanggal
        FROM pemesanan p
        JOIN penerbangan pe ON p.id_penerbangan = pe.id
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (p.kode_booking LIKE '%$search%' 
              OR p.nama_pemesan LIKE '%$search%'
              OR p.email LIKE '%$search%'
              OR p.no_hp LIKE '%$search%')";
}

if (!empty($status)) {
    $sql .= " AND p.status = '$status'";
}

$sql .= " ORDER BY p.created_at DESC";

$result = $conn->query($sql);

$bookings = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'data' => $bookings
]);
?>