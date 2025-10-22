<?php
// admin/api/get_recent_bookings.php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = "SELECT p.*, 
          pen.maskapai, pen.asal, pen.tujuan, pen.tanggal,
          p.created_at as booking_date
          FROM pemesanan p
          LEFT JOIN penerbangan pen ON p.id_penerbangan = pen.id
          ORDER BY p.created_at DESC
          LIMIT 10";

$result = $conn->query($query);

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