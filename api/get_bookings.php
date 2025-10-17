<?php
require_once '../config.php';

header('Content-Type: application/json');

$search = $conn->real_escape_string($_GET['search'] ?? '');

$sql = "SELECT p.*, 
        pe.maskapai, pe.kode_penerbangan, pe.asal, pe.tujuan, 
        DATE_FORMAT(pe.tanggal, '%d %M %Y') as tanggal,
        DATE_FORMAT(pe.jam_berangkat, '%H:%i') as jam_berangkat,
        DATE_FORMAT(pe.jam_tiba, '%H:%i') as jam_tiba
        FROM pemesanan p
        JOIN penerbangan pe ON p.id_penerbangan = pe.id";

if (!empty($search)) {
    $sql .= " WHERE p.kode_booking LIKE '%$search%' 
              OR p.nama_pemesan LIKE '%$search%' 
              OR p.email LIKE '%$search%'
              OR p.no_hp LIKE '%$search%'";
}

$sql .= " ORDER BY p.created_at DESC LIMIT 50";

$result = $conn->query($sql);

$bookings = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'data' => $bookings,
    'total' => count($bookings)
]);
?>