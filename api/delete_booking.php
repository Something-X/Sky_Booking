<?php
require_once '../config.php';

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

// Cek apakah booking sudah expired atau batal
$sql = "SELECT p.*, pe.tanggal 
        FROM pemesanan p
        JOIN penerbangan pe ON p.id_penerbangan = pe.id
        WHERE p.id = $id";

$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Pemesanan tidak ditemukan']);
    exit;
}

$booking = $result->fetch_assoc();
$flightDate = strtotime($booking['tanggal']);
$today = strtotime(date('Y-m-d'));

// Hanya bisa hapus jika expired atau batal
if ($flightDate >= $today && $booking['status'] !== 'batal') {
    echo json_encode(['success' => false, 'message' => 'Hanya pemesanan yang sudah expired atau batal yang bisa dihapus']);
    exit;
}

// Log sebelum delete
$conn->query("INSERT INTO booking_logs (booking_id, action, reason) 
              VALUES ($id, 'deleted', 'Deleted by user - expired or cancelled')");

// Delete penumpang terlebih dahulu (foreign key)
$conn->query("DELETE FROM penumpang WHERE id_pemesanan = $id");

// Delete pemesanan
$sql = "DELETE FROM pemesanan WHERE id = $id";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Pemesanan berhasil dihapus']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus pemesanan']);
}
?>