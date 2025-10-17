<?php
require_once '../config.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

// Ambil data pemesanan
$sql = "SELECT p.*, 
        pe.maskapai, pe.kode_penerbangan, pe.asal, pe.tujuan,
        DATE_FORMAT(pe.tanggal, '%d %M %Y') as tanggal,
        DATE_FORMAT(pe.jam_berangkat, '%H:%i') as jam_berangkat,
        DATE_FORMAT(pe.jam_tiba, '%H:%i') as jam_tiba,
        DATE_FORMAT(p.created_at, '%d %M %Y %H:%i') as created_at
        FROM pemesanan p
        JOIN penerbangan pe ON p.id_penerbangan = pe.id
        WHERE p.id = $id";

$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
    exit;
}

$booking = $result->fetch_assoc();

// Ambil data penumpang
$sql = "SELECT * FROM penumpang WHERE id_pemesanan = $id";
$result = $conn->query($sql);

$penumpang = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $penumpang[] = $row;
    }
}

$booking['penumpang'] = $penumpang;

echo json_encode([
    'success' => true,
    'data' => $booking
]);
?>