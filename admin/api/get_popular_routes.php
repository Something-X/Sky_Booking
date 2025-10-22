<?php
// admin/api/get_popular_routes.php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = "SELECT 
          pen.asal, 
          pen.tujuan,
          COUNT(p.id) as total_bookings,
          SUM(p.total_harga) as total_revenue
          FROM pemesanan p
          INNER JOIN penerbangan pen ON p.id_penerbangan = pen.id
          WHERE p.status = 'lunas'
          GROUP BY pen.asal, pen.tujuan
          ORDER BY total_bookings DESC
          LIMIT 5";

$result = $conn->query($query);

$routes = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $routes[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'data' => $routes
]);
?>