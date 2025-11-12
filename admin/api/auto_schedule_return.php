<?php
/**
 * Auto Schedule Return Flights - Backend
 * File: admin/api/auto_schedule_return.php
 * 
 * API untuk generate jadwal balik otomatis
 */

require_once '../../config.php';

header('Content-Type: application/json');

// Function untuk generate return flight
function generateReturnFlight($flight, $conn) {
    // Random delay 2-4 jam (120-240 menit)
    $delayMinutes = rand(120, 240);
    
    // Hitung waktu keberangkatan baru
    $arrivalDateTime = new DateTime($flight['tanggal'] . ' ' . $flight['jam_tiba']);
    $departureDateTime = clone $arrivalDateTime;
    $departureDateTime->add(new DateInterval('PT' . $delayMinutes . 'M'));
    
    // Cek jika waktu keberangkatan < 07:00
    $departureHour = (int)$departureDateTime->format('H');
    if ($departureHour < 7) {
        // Set ke jam 07:00 hari berikutnya
        $departureDateTime->add(new DateInterval('P1D'));
        $departureDateTime->setTime(7, 0, 0);
    }
    
    // Hitung durasi penerbangan
    $originalDuration = (strtotime($flight['tanggal'] . ' ' . $flight['jam_tiba']) - 
                         strtotime($flight['tanggal'] . ' ' . $flight['jam_berangkat'])) / 60;
    
    // Hitung waktu tiba
    $arrivalReturnDateTime = clone $departureDateTime;
    $arrivalReturnDateTime->add(new DateInterval('PT' . (int)$originalDuration . 'M'));
    
    // Cek jika waktu tiba melewati 24:00 atau kurang dari 07:00
    $arrivalHour = (int)$arrivalReturnDateTime->format('H');
    $arrivalDay = $arrivalReturnDateTime->format('Y-m-d');
    $departureDay = $departureDateTime->format('Y-m-d');
    
    if ($arrivalHour >= 24 || ($arrivalHour < 7 && $arrivalDay == $departureDay)) {
        return false; // Skip flight ini
    }
    
    // Generate kode penerbangan return
    $kodeReturn = $conn->real_escape_string($flight['kode_penerbangan'] . '-R');
    
    // Balik origin dan destination
    $newFlightData = [
        'maskapai' => $conn->real_escape_string($flight['maskapai']),
        'kode_penerbangan' => $kodeReturn,
        'asal' => $conn->real_escape_string($flight['tujuan']),
        'tujuan' => $conn->real_escape_string($flight['asal']),
        'tanggal' => $departureDateTime->format('Y-m-d'),
        'jam_berangkat' => $departureDateTime->format('H:i:s'),
        'jam_tiba' => $arrivalReturnDateTime->format('H:i:s'),
        'harga' => $flight['harga'],
        'kapasitas' => $flight['kapasitas'],
        'tersedia' => $flight['kapasitas'],
        'origin_airport_id' => $flight['destination_airport_id'],
        'destination_airport_id' => $flight['origin_airport_id'],
        'aircraft_type' => $conn->real_escape_string($flight['aircraft_type'])
    ];
    
    return $newFlightData;
}

try {
    // Cari penerbangan yang sudah arrived dan belum ada return flight
    $sql = "SELECT p.* 
            FROM penerbangan p
            WHERE p.status_tracking = 'Arrived'
            AND p.status = 'aktif'
            AND p.kode_penerbangan NOT LIKE '%-R'
            AND NOT EXISTS (
                SELECT 1 FROM penerbangan p2 
                WHERE p2.kode_penerbangan = CONCAT(p.kode_penerbangan, '-R')
                AND p2.tanggal >= p.tanggal
            )
            ORDER BY p.tanggal ASC, p.jam_tiba ASC
            LIMIT 20";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Query error: " . $conn->error);
    }
    
    $generated = 0;
    $skipped = 0;
    $details = [];
    
    while ($flight = $result->fetch_assoc()) {
        $newFlight = generateReturnFlight($flight, $conn);
        
        if ($newFlight === false) {
            $skipped++;
            $details[] = [
                'status' => 'skipped',
                'flight' => $flight['kode_penerbangan'],
                'reason' => 'Exceeds operational hours'
            ];
            continue;
        }
        
        // Insert jadwal baru
        $insertSql = "INSERT INTO penerbangan 
                      (maskapai, kode_penerbangan, asal, tujuan, tanggal, 
                       jam_berangkat, jam_tiba, harga, kapasitas, tersedia, 
                       status, status_tracking, origin_airport_id, 
                       destination_airport_id, aircraft_type, current_lat, current_lon, progress)
                      VALUES 
                      ('{$newFlight['maskapai']}', 
                       '{$newFlight['kode_penerbangan']}', 
                       '{$newFlight['asal']}', 
                       '{$newFlight['tujuan']}', 
                       '{$newFlight['tanggal']}', 
                       '{$newFlight['jam_berangkat']}', 
                       '{$newFlight['jam_tiba']}', 
                       {$newFlight['harga']}, 
                       {$newFlight['kapasitas']}, 
                       {$newFlight['tersedia']}, 
                       'aktif', 
                       'Scheduled',
                       {$newFlight['origin_airport_id']},
                       {$newFlight['destination_airport_id']},
                       '{$newFlight['aircraft_type']}',
                       (SELECT lat FROM airports WHERE id = {$newFlight['origin_airport_id']}),
                       (SELECT lon FROM airports WHERE id = {$newFlight['origin_airport_id']}),
                       0.00)";
        
        if ($conn->query($insertSql)) {
            $generated++;
            $details[] = [
                'status' => 'success',
                'flight' => $newFlight['kode_penerbangan'],
                'route' => $newFlight['asal'] . ' → ' . $newFlight['tujuan'],
                'date' => $newFlight['tanggal'],
                'time' => $newFlight['jam_berangkat']
            ];
        } else {
            $details[] = [
                'status' => 'error',
                'flight' => $newFlight['kode_penerbangan'],
                'error' => $conn->error
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'generated' => $generated,
        'skipped' => $skipped,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>