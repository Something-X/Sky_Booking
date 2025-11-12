<?php
/**
 * Auto Schedule Flights - Generate Return Routes
 * File: auto_schedule_flights.php
 * 
 * Script ini akan:
 * 1. Cek penerbangan yang sudah arrived (status_tracking = 'Arrived')
 * 2. Generate jadwal balik otomatis (rute terbalik)
 * 3. Waktu keberangkatan: waktu tiba + 2-4 jam (random)
 * 4. Hanya jadwal antara jam 07:00 - 24:00
 * 5. Jika lewat 24:00, dijadwalkan ke 07:00 hari berikutnya
 */

require_once __DIR__ . '/config.php';

// Fungsi untuk generate jadwal balik
function generateReturnFlight($flight) {
    global $conn;
    
    // Random delay 2-4 jam (dalam menit: 120-240)
    $delayMinutes = rand(120, 240);
    
    // Hitung waktu keberangkatan baru
    $arrivalDateTime = new DateTime($flight['tanggal'] . ' ' . $flight['jam_tiba']);
    $departureDateTime = clone $arrivalDateTime;
    $departureDateTime->add(new DateInterval('PT' . $delayMinutes . 'M'));
    
    // Cek jika waktu keberangkatan melewati 24:00
    $departureHour = (int)$departureDateTime->format('H');
    if ($departureHour < 7) {
        // Set ke jam 07:00 di hari yang sama
        $departureDateTime->setTime(7, 0, 0);
    } elseif ($departureHour >= 24) {
        // Set ke jam 07:00 hari berikutnya
        $departureDateTime->add(new DateInterval('P1D'));
        $departureDateTime->setTime(7, 0, 0);
    }
    
    // Hitung durasi penerbangan dari flight asli
    $originalDuration = (strtotime($flight['tanggal'] . ' ' . $flight['jam_tiba']) - 
                         strtotime($flight['tanggal'] . ' ' . $flight['jam_berangkat'])) / 60;
    
    // Hitung waktu tiba
    $arrivalReturnDateTime = clone $departureDateTime;
    $arrivalReturnDateTime->add(new DateInterval('PT' . (int)$originalDuration . 'M'));
    
    // Cek jika waktu tiba melewati 24:00
    $arrivalHour = (int)$arrivalReturnDateTime->format('H');
    if ($arrivalHour >= 24 || $arrivalHour < 7) {
        // Skip flight ini karena akan melewati jam operasional
        return false;
    }
    
    // Format data untuk insert
    $newFlightData = [
        'maskapai' => $conn->real_escape_string($flight['maskapai']),
        'kode_penerbangan' => $conn->real_escape_string($flight['kode_penerbangan'] . '-R'), // Tambah suffix -R untuk return
        'asal' => $conn->real_escape_string($flight['tujuan']), // Balik
        'tujuan' => $conn->real_escape_string($flight['asal']), // Balik
        'tanggal' => $departureDateTime->format('Y-m-d'),
        'jam_berangkat' => $departureDateTime->format('H:i:s'),
        'jam_tiba' => $arrivalReturnDateTime->format('H:i:s'),
        'harga' => $flight['harga'],
        'kapasitas' => $flight['kapasitas'],
        'tersedia' => $flight['kapasitas'],
        'origin_airport_id' => $flight['destination_airport_id'], // Balik
        'destination_airport_id' => $flight['origin_airport_id'], // Balik
        'aircraft_type' => $conn->real_escape_string($flight['aircraft_type'])
    ];
    
    return $newFlightData;
}

// Main execution
try {
    // 1. Cari penerbangan yang sudah arrived dan belum di-generate return flight
    $sql = "SELECT p.* 
            FROM penerbangan p
            WHERE p.status_tracking = 'Arrived'
            AND p.status = 'aktif'
            AND NOT EXISTS (
                SELECT 1 FROM penerbangan p2 
                WHERE p2.kode_penerbangan = CONCAT(p.kode_penerbangan, '-R')
                AND p2.tanggal >= p.tanggal
            )
            ORDER BY p.tanggal ASC, p.jam_tiba ASC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Query error: " . $conn->error);
    }
    
    $generated = 0;
    $skipped = 0;
    $errors = [];
    
    while ($flight = $result->fetch_assoc()) {
        $newFlight = generateReturnFlight($flight);
        
        if ($newFlight === false) {
            $skipped++;
            $errors[] = "Skipped flight {$flight['kode_penerbangan']} - exceeds operational hours";
            continue;
        }
        
        // Insert jadwal baru
        $insertSql = "INSERT INTO penerbangan 
                      (maskapai, kode_penerbangan, asal, tujuan, tanggal, 
                       jam_berangkat, jam_tiba, harga, kapasitas, tersedia, 
                       status, status_tracking, origin_airport_id, 
                       destination_airport_id, aircraft_type, current_lat, current_lon)
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
                       (SELECT lon FROM airports WHERE id = {$newFlight['origin_airport_id']}))";
        
        if ($conn->query($insertSql)) {
            $generated++;
            echo "✓ Generated return flight: {$newFlight['kode_penerbangan']} - {$newFlight['asal']} to {$newFlight['tujuan']} on {$newFlight['tanggal']} at {$newFlight['jam_berangkat']}\n";
        } else {
            $errors[] = "Failed to insert {$newFlight['kode_penerbangan']}: " . $conn->error;
        }
    }
    
    // Summary
    echo "\n=== AUTO SCHEDULE SUMMARY ===\n";
    echo "Generated: $generated flights\n";
    echo "Skipped: $skipped flights\n";
    
    if (!empty($errors)) {
        echo "\nErrors:\n";
        foreach ($errors as $error) {
            echo "- $error\n";
        }
    }
    
    // Log to file
    $logFile = __DIR__ . '/logs/auto_schedule.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logMessage = sprintf(
        "[%s] Generated: %d, Skipped: %d, Errors: %d\n",
        date('Y-m-d H:i:s'),
        $generated,
        $skipped,
        count($errors)
    );
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("Auto Schedule Error: " . $e->getMessage());
}

$conn->close();
?>