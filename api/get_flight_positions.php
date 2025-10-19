<?php
require_once '../config.php';

header('Content-Type: application/json');

// Ambil data dari view
$sql = "SELECT * FROM flight_tracking_view 
        WHERE departure_datetime >= NOW() - INTERVAL 12 HOUR
        ORDER BY departure_datetime ASC";

$result = $conn->query($sql);

$flights = [];
$airports = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Calculate real-time progress
        $departure_time = strtotime($row['departure_datetime']);
        $arrival_time = strtotime($row['arrival_datetime']);
        $current_time = time();
        
        $progress = 0;
        $current_lat = $row['origin_lat'];
        $current_lon = $row['origin_lon'];
        $status = $row['status_tracking'];
        
        if ($current_time >= $departure_time && $current_time < $arrival_time) {
            $total_duration = $arrival_time - $departure_time;
            $elapsed_time = $current_time - $departure_time;
            $progress = ($elapsed_time / $total_duration) * 100;
            $progress = min(100, max(0, $progress));
            
            $ratio = $progress / 100;
            $current_lat = $row['origin_lat'] + (($row['dest_lat'] - $row['origin_lat']) * $ratio);
            $current_lon = $row['origin_lon'] + (($row['dest_lon'] - $row['origin_lon']) * $ratio);
            $status = 'Departed';
            
        } elseif ($current_time >= $arrival_time) {
            $progress = 100;
            $current_lat = $row['dest_lat'];
            $current_lon = $row['dest_lon'];
            $status = 'Arrived';
        }
        
        $flights[] = [
            'id' => $row['id'],
            'flight_code' => $row['kode_penerbangan'],
            'airline' => $row['maskapai'],
            'aircraft_type' => $row['aircraft_type'],
            'status' => $status,
            'progress' => round($progress, 2),
            'current_lat' => $current_lat,
            'current_lon' => $current_lon,
            'origin' => [
                'code' => $row['origin_code'],
                'name' => $row['origin_name'],
                'city' => $row['origin_city'],
                'lat' => (float)$row['origin_lat'],
                'lon' => (float)$row['origin_lon']
            ],
            'destination' => [
                'code' => $row['dest_code'],
                'name' => $row['dest_name'],
                'city' => $row['dest_city'],
                'lat' => (float)$row['dest_lat'],
                'lon' => (float)$row['dest_lon']
            ],
            'departure_time' => $row['departure_datetime'],
            'arrival_time' => $row['arrival_datetime'],
            'price' => (float)$row['harga'],
            'available_seats' => (int)$row['tersedia']
        ];
        
        // Collect unique airports
        if (!isset($airports[$row['origin_code']])) {
            $airports[$row['origin_code']] = [
                'code' => $row['origin_code'],
                'name' => $row['origin_name'],
                'city' => $row['origin_city'],
                'lat' => (float)$row['origin_lat'],
                'lon' => (float)$row['origin_lon']
            ];
        }
        
        if (!isset($airports[$row['dest_code']])) {
            $airports[$row['dest_code']] = [
                'code' => $row['dest_code'],
                'name' => $row['dest_name'],
                'city' => $row['dest_city'],
                'lat' => (float)$row['dest_lat'],
                'lon' => (float)$row['dest_lon']
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'flights' => $flights,
    'airports' => array_values($airports),
    'total_flights' => count($flights)
]);
?>