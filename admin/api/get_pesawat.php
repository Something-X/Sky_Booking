<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT p.*, 
        CONCAT(a.city, ' (', a.code, ')') as lokasi 
        FROM pesawat p 
        LEFT JOIN airports a ON p.airport_id = a.id 
        WHERE 1=1";

if ($search) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (p.maskapai LIKE '%$search%' OR p.nomor_registrasi LIKE '%$search%' OR p.model LIKE '%$search%')";
}

if ($status) {
    $status = $conn->real_escape_string($status);
    $sql .= " AND p.status_pesawat = '$status'";
}

$sql .= " ORDER BY p.created_at DESC";

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