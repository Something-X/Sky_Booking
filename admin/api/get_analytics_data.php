<?php
// admin/api/get_analytics_data.php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get total revenue
$revenue = 0;
$result = $conn->query("SELECT SUM(total_harga) as total FROM pemesanan WHERE status = 'lunas'");
if ($result) {
    $row = $result->fetch_assoc();
    $revenue = $row['total'] ?? 0;
}

// Get total bookings
$bookings = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM pemesanan");
if ($result) {
    $row = $result->fetch_assoc();
    $bookings = $row['total'] ?? 0;
}

// Calculate average booking value
$avgBooking = $bookings > 0 ? ($revenue / $bookings) : 0;

// Calculate conversion rate (lunas / total)
$lunas = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE status = 'lunas'");
if ($result) {
    $row = $result->fetch_assoc();
    $lunas = $row['total'] ?? 0;
}
$conversionRate = $bookings > 0 ? round(($lunas / $bookings) * 100, 1) : 0;

// Get top routes
$topRoutes = [];
$query = "SELECT 
          CONCAT(pen.asal, ' → ', pen.tujuan) as route,
          COUNT(p.id) as bookings,
          SUM(p.total_harga) as revenue
          FROM pemesanan p
          INNER JOIN penerbangan pen ON p.id_penerbangan = pen.id
          WHERE p.status = 'lunas'
          GROUP BY pen.asal, pen.tujuan
          ORDER BY bookings DESC
          LIMIT 5";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $topRoutes[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'totalRevenue' => $revenue,
    'totalBookings' => $bookings,
    'avgBooking' => round($avgBooking),
    'conversionRate' => $conversionRate,
    'topRoutes' => $topRoutes
]);
?>