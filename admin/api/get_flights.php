<?php
require_once '../../config.php';

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$search = $conn->real_escape_string($_GET['search'] ?? '');
$status = $conn->real_escape_string($_GET['status'] ?? '');

$sql = "SELECT *, 
        DATE_FORMAT(tanggal, '%d %M %Y') as tanggal,
        DATE_FORMAT(jam_berangkat, '%H:%i') as jam_berangkat,
        DATE_FORMAT(jam_tiba, '%H:%i') as jam_tiba
        FROM penerbangan WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (maskapai LIKE '%$search%' 
              OR kode_penerbangan LIKE '%$search%'
              OR asal LIKE '%$search%'
              OR tujuan LIKE '%$search%')";
}

if (!empty($status)) {
    $sql .= " AND status = '$status'";
}

$sql .= " ORDER BY tanggal DESC, jam_berangkat ASC";

$result = $conn->query($sql);

$flights = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $flights[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'data' => $flights
]);
?>