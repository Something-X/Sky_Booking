<?php
/**
 * Trigger Auto Schedule Flights
 * File: admin/trigger_auto_schedule.php
 */

require_once '../config.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$result = null;
$logs = [];

// Jika button di-klik
if (isset($_POST['trigger_schedule'])) {
    
    // Function untuk generate return flight
    function generateReturnFlight($flight) {
        global $conn;
        
        // Random delay 2-4 jam (120-240 menit)
        $delayMinutes = rand(120, 240);
        
        // Hitung waktu keberangkatan baru
        $arrivalDateTime = new DateTime($flight['tanggal'] . ' ' . $flight['jam_tiba']);
        $departureDateTime = clone $arrivalDateTime;
        $departureDateTime->add(new DateInterval('PT' . $delayMinutes . 'M'));
        
        // Cek jika waktu keberangkatan < 07:00
        $departureHour = (int)$departureDateTime->format('H');
        if ($departureHour < 7) {
            $departureDateTime->setTime(7, 0, 0);
        }
        
        // Hitung durasi penerbangan
        $originalDuration = (strtotime($flight['tanggal'] . ' ' . $flight['jam_tiba']) - 
                             strtotime($flight['tanggal'] . ' ' . $flight['jam_berangkat'])) / 60;
        
        // Hitung waktu tiba
        $arrivalReturnDateTime = clone $departureDateTime;
        $arrivalReturnDateTime->add(new DateInterval('PT' . (int)$originalDuration . 'M'));
        
        // Cek jika waktu tiba melewati 24:00
        $arrivalHour = (int)$arrivalReturnDateTime->format('H');
        if ($arrivalHour >= 24 || ($arrivalHour < 7 && $arrivalDateTime->format('Y-m-d') == $arrivalReturnDateTime->format('Y-m-d'))) {
            return false;
        }
        
        // Balik origin dan destination
        $newFlightData = [
            'maskapai' => $conn->real_escape_string($flight['maskapai']),
            'kode_penerbangan' => $conn->real_escape_string($flight['kode_penerbangan'] . '-R'),
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
    
    // Proses auto schedule
    try {
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
        
        $queryResult = $conn->query($sql);
        
        if (!$queryResult) {
            throw new Exception("Query error: " . $conn->error);
        }
        
        $generated = 0;
        $skipped = 0;
        
        while ($flight = $queryResult->fetch_assoc()) {
            $newFlight = generateReturnFlight($flight);
            
            if ($newFlight === false) {
                $skipped++;
                $logs[] = [
                    'type' => 'warning',
                    'message' => "Skipped {$flight['kode_penerbangan']} - exceeds operational hours"
                ];
                continue;
            }
            
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
                $logs[] = [
                    'type' => 'success',
                    'message' => "Generated: {$newFlight['kode_penerbangan']} - {$newFlight['asal']} → {$newFlight['tujuan']} on {$newFlight['tanggal']} at {$newFlight['jam_berangkat']}"
                ];
            } else {
                $logs[] = [
                    'type' => 'error',
                    'message' => "Failed: {$newFlight['kode_penerbangan']} - " . $conn->error
                ];
            }
        }
        
        $result = [
            'success' => true,
            'generated' => $generated,
            'skipped' => $skipped
        ];
        
    } catch (Exception $e) {
        $result = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Schedule Flights</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .log-success { color: #198754; }
        .log-warning { color: #ffc107; }
        .log-error { color: #dc3545; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-arrow-repeat"></i> Auto Schedule Flights</h1>
                </div>

                <!-- Info Card -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <i class="bi bi-info-circle"></i> Cara Kerja Sistem
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Sistem akan mencari penerbangan dengan status <strong>"Arrived"</strong></li>
                                    <li>Generate jadwal balik otomatis (rute terbalik: tujuan → asal)</li>
                                    <li>Waktu keberangkatan: waktu tiba + <strong>2-4 jam</strong> (random)</li>
                                    <li>Hanya membuat jadwal antara jam <strong>07:00 - 24:00</strong></li>
                                    <li>Kode penerbangan return: ditambah suffix <strong>-R</strong> (contoh: GA-101-R)</li>
                                    <li>Tidak akan membuat duplikat jadwal yang sudah ada</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trigger Button -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <form method="POST">
                                    <button type="submit" name="trigger_schedule" class="btn btn-primary btn-lg px-5">
                                        <i class="bi bi-play-circle"></i> Jalankan Auto Schedule
                                    </button>
                                </form>
                                <small class="text-muted d-block mt-3">
                                    Klik tombol di atas untuk generate jadwal penerbangan balik secara otomatis
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Result -->
                <?php if ($result !== null): ?>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <?php if ($result['success']): ?>
                            <div class="alert alert-success">
                                <h5><i class="bi bi-check-circle"></i> Process Completed!</h5>
                                <hr>
                                <p class="mb-0">
                                    <strong>Generated:</strong> <?= $result['generated'] ?> flights<br>
                                    <strong>Skipped:</strong> <?= $result['skipped'] ?> flights
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h5><i class="bi bi-exclamation-triangle"></i> Error!</h5>
                                <hr>
                                <p class="mb-0"><?= htmlspecialchars($result['error']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Logs -->
                <?php if (!empty($logs)): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-list-ul"></i> Process Logs
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th width="50">#</th>
                                                <th>Message</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($logs as $index => $log): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td class="log-<?= $log['type'] ?>">
                                                    <?php if ($log['type'] == 'success'): ?>
                                                        <i class="bi bi-check-circle"></i>
                                                    <?php elseif ($log['type'] == 'warning'): ?>
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-x-circle"></i>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($log['message']) ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Return Flights -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-clock-history"></i> Recent Return Flights (Last 10)
                            </div>
                            <div class="card-body">
                                <?php
                                $recentSql = "SELECT * FROM penerbangan 
                                              WHERE kode_penerbangan LIKE '%-R' 
                                              ORDER BY id DESC LIMIT 10";
                                $recentResult = $conn->query($recentSql);
                                ?>
                                
                                <?php if ($recentResult && $recentResult->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Kode</th>
                                                <th>Maskapai</th>
                                                <th>Rute</th>
                                                <th>Tanggal</th>
                                                <th>Waktu</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $recentResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($row['kode_penerbangan']) ?></strong></td>
                                                <td><?= htmlspecialchars($row['maskapai']) ?></td>
                                                <td><?= htmlspecialchars($row['asal']) ?> → <?= htmlspecialchars($row['tujuan']) ?></td>
                                                <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                                <td><?= date('H:i', strtotime($row['jam_berangkat'])) ?> - <?= date('H:i', strtotime($row['jam_tiba'])) ?></td>
                                                <td>
                                                    <?php if ($row['status_tracking'] == 'Scheduled'): ?>
                                                        <span class="badge bg-primary">Scheduled</span>
                                                    <?php elseif ($row['status_tracking'] == 'Departed'): ?>
                                                        <span class="badge bg-warning">Departed</span>
                                                    <?php elseif ($row['status_tracking'] == 'Arrived'): ?>
                                                        <span class="badge bg-success">Arrived</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= $row['status_tracking'] ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info mb-0">
                                    <i class="bi bi-info-circle"></i> Belum ada return flights yang di-generate.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>