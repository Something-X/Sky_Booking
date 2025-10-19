<?php
require_once 'config.php';

/**
 * Auto Update Flight Status & Position
 * Dijalankan setiap 1 menit via cron job atau AJAX polling
 */

$now = date('Y-m-d H:i:s');

// Ambil semua penerbangan yang aktif (tanggal hari ini atau masa depan)
$sql = "SELECT 
    p.*,
    origin.lat as origin_lat, origin.lon as origin_lon,
    dest.lat as dest_lat, dest.lon as dest_lon,
    CONCAT(p.tanggal, ' ', p.jam_berangkat) as departure_datetime,
    CONCAT(p.tanggal, ' ', p.jam_tiba) as arrival_datetime
FROM penerbangan p
LEFT JOIN airports origin ON p.origin_airport_id = origin.id
LEFT JOIN airports dest ON p.destination_airport_id = dest.id
WHERE p.tanggal >= CURDATE() - INTERVAL 1 DAY
AND p.status = 'aktif'";

$result = $conn->query($sql);
$updated = 0;

if ($result && $result->num_rows > 0) {
    while ($flight = $result->fetch_assoc()) {
        $departure_time = strtotime($flight['departure_datetime']);
        $arrival_time = strtotime($flight['arrival_datetime']);
        $current_time = time();
        
        $new_status = $flight['status_tracking'];
        $progress = 0;
        $current_lat = $flight['origin_lat'];
        $current_lon = $flight['origin_lon'];
        
        // Logika update status
        if ($current_time < $departure_time) {
            // Belum berangkat
            $new_status = 'Scheduled';
            $progress = 0;
            $current_lat = $flight['origin_lat'];
            $current_lon = $flight['origin_lon'];
            
        } elseif ($current_time >= $departure_time && $current_time < $arrival_time) {
            // Sedang terbang
            $new_status = 'Departed';
            
            // Calculate progress (0-100%)
            $total_duration = $arrival_time - $departure_time;
            $elapsed_time = $current_time - $departure_time;
            $progress = ($elapsed_time / $total_duration) * 100;
            $progress = min(100, max(0, $progress)); // Clamp between 0-100
            
            // Calculate current position (linear interpolation)
            $ratio = $progress / 100;
            $current_lat = $flight['origin_lat'] + (($flight['dest_lat'] - $flight['origin_lat']) * $ratio);
            $current_lon = $flight['origin_lon'] + (($flight['dest_lon'] - $flight['origin_lon']) * $ratio);
            
        } else {
            // Sudah tiba
            $new_status = 'Arrived';
            $progress = 100;
            $current_lat = $flight['dest_lat'];
            $current_lon = $flight['dest_lon'];
        }
        
        // Update database
        $update_sql = "UPDATE penerbangan SET 
            status_tracking = '" . $conn->real_escape_string($new_status) . "',
            progress = " . round($progress, 2) . ",
            current_lat = " . $current_lat . ",
            current_lon = " . $current_lon . "
            WHERE id = " . $flight['id'];
        
        if ($conn->query($update_sql)) {
            $updated++;
        }
    }
}

// Check expired bookings
$conn->query("CALL check_expired_bookings()");

// Return response jika dipanggil via AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    echo "Updated $updated flights at " . date('Y-m-d H:i:s') . "\n";
}
?>