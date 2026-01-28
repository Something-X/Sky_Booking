<?php
/**
 * FlightScheduler Class
 * Handle semua logic auto-schedule dengan clean code
 * 
 * Usage:
 * $scheduler = new FlightScheduler($conn);
 * $result = $scheduler->generateReturnFlights();
 */

class FlightScheduler {
    private $conn;
    private $groundTimeMin = 120; // 2 jam dalam menit
    private $groundTimeMax = 240; // 4 jam dalam menit
    private $operationalStart = 7; // 07:00
    private $operationalEnd = 24; // 24:00
    private $logs = [];
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Generate return flights untuk semua flight yang sudah arrived
     * 
     * @return array ['success' => bool, 'generated' => int, 'skipped' => int, 'errors' => array]
     */
    public function generateReturnFlights() {
        $generated = 0;
        $skipped = 0;
        $errors = [];
        
        try {
            // Get flights yang perlu di-generate return flight
            $flights = $this->getArrivedFlights();
            
            foreach ($flights as $flight) {
                // Cek duplikasi
                if ($this->hasReturnFlight($flight['kode_penerbangan'])) {
                    $this->addLog('info', "Flight {$flight['kode_penerbangan']} sudah ada return flight");
                    continue;
                }
                
                // Generate return flight data
                $returnFlightData = $this->calculateReturnFlight($flight);
                
                if ($returnFlightData === false) {
                    $skipped++;
                    $this->addLog('warning', "Skipped {$flight['kode_penerbangan']} - exceeds operational hours");
                    continue;
                }
                
                // Validasi konflik jadwal
                if ($this->hasScheduleConflict($returnFlightData)) {
                    $skipped++;
                    $this->addLog('warning', "Skipped {$flight['kode_penerbangan']} - schedule conflict");
                    continue;
                }
                
                // Insert ke database
                if ($this->insertReturnFlight($returnFlightData)) {
                    $generated++;
                    $this->addLog('success', 
                        "Generated: {$returnFlightData['kode_penerbangan']} - " .
                        "{$returnFlightData['asal']} → {$returnFlightData['tujuan']} " .
                        "on {$returnFlightData['tanggal']} at {$returnFlightData['jam_berangkat']}"
                    );
                } else {
                    $errors[] = "Failed to insert {$returnFlightData['kode_penerbangan']}";
                    $this->addLog('error', "Failed to insert {$returnFlightData['kode_penerbangan']}");
                }
            }
            
            // Save logs to file
            $this->saveLogsToFile();
            
            return [
                'success' => true,
                'generated' => $generated,
                'skipped' => $skipped,
                'errors' => $errors,
                'logs' => $this->logs
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'generated' => 0,
                'skipped' => 0,
                'errors' => [$e->getMessage()],
                'logs' => $this->logs
            ];
        }
    }
    
    /**
     * Get semua flight yang sudah arrived dan belum ada return flight
     */
    private function getArrivedFlights() {
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
                ORDER BY p.tanggal ASC, p.jam_tiba ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $flights = [];
        while ($row = $result->fetch_assoc()) {
            $flights[] = $row;
        }
        
        return $flights;
    }
    
    /**
     * Cek apakah flight sudah punya return flight
     */
    private function hasReturnFlight($flightCode) {
        $sql = "SELECT COUNT(*) as count 
                FROM penerbangan 
                WHERE kode_penerbangan = ?";
        
        $stmt = $this->conn->prepare($sql);
        $returnCode = $flightCode . '-R';
        $stmt->bind_param('s', $returnCode);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }
    
    /**
     * Calculate return flight data
     * 
     * @param array $flight Original flight data
     * @return array|false Return flight data atau false jika tidak valid
     */
    private function calculateReturnFlight($flight) {
        // Random ground time
        $groundTimeMinutes = rand($this->groundTimeMin, $this->groundTimeMax);
        
        // Parse datetime
        $arrivalDateTime = new DateTime($flight['tanggal'] . ' ' . $flight['jam_tiba']);
        $departureDateTime = clone $arrivalDateTime;
        $departureDateTime->add(new DateInterval('PT' . $groundTimeMinutes . 'M'));
        
        // Adjust untuk jam operasional
        $departureDateTime = $this->adjustOperationalHours($departureDateTime);
        
        if ($departureDateTime === false) {
            return false;
        }
        
        // Calculate flight duration
        $originalDeparture = new DateTime($flight['tanggal'] . ' ' . $flight['jam_berangkat']);
        $originalArrival = new DateTime($flight['tanggal'] . ' ' . $flight['jam_tiba']);
        $flightDuration = ($originalArrival->getTimestamp() - $originalDeparture->getTimestamp()) / 60;
        
        // Calculate arrival time
        $arrivalReturnDateTime = clone $departureDateTime;
        $arrivalReturnDateTime->add(new DateInterval('PT' . (int)$flightDuration . 'M'));
        
        // Validate arrival time
        $arrivalHour = (int)$arrivalReturnDateTime->format('H');
        if ($arrivalHour >= $this->operationalEnd || $arrivalHour < $this->operationalStart) {
            return false;
        }
        
        // Prepare return flight data
        return [
            'maskapai' => $flight['maskapai'],
            'kode_penerbangan' => $flight['kode_penerbangan'] . '-R',
            'asal' => $flight['tujuan'], // Balik
            'tujuan' => $flight['asal'], // Balik
            'tanggal' => $departureDateTime->format('Y-m-d'),
            'jam_berangkat' => $departureDateTime->format('H:i:s'),
            'jam_tiba' => $arrivalReturnDateTime->format('H:i:s'),
            'harga' => $flight['harga'],
            'kapasitas' => $flight['kapasitas'],
            'tersedia' => $flight['kapasitas'],
            'origin_airport_id' => $flight['destination_airport_id'], // Balik
            'destination_airport_id' => $flight['origin_airport_id'], // Balik
            'aircraft_type' => $flight['aircraft_type']
        ];
    }
    
    /**
     * Adjust waktu agar sesuai jam operasional
     */
    private function adjustOperationalHours(DateTime $datetime) {
        $hour = (int)$datetime->format('H');
        
        // Jika kurang dari jam operasional
        if ($hour < $this->operationalStart) {
            $datetime->setTime($this->operationalStart, 0, 0);
            return $datetime;
        }
        
        // Jika melewati jam operasional
        if ($hour >= $this->operationalEnd) {
            // Set ke hari berikutnya jam 07:00
            $datetime->add(new DateInterval('P1D'));
            $datetime->setTime($this->operationalStart, 0, 0);
            return $datetime;
        }
        
        return $datetime;
    }
    
    /**
     * Cek konflik jadwal (apakah pesawat sudah ada jadwal lain di waktu yang sama)
     */
    private function hasScheduleConflict($flightData) {
        $sql = "SELECT COUNT(*) as count 
                FROM penerbangan 
                WHERE aircraft_type = ? 
                AND tanggal = ? 
                AND (
                    (jam_berangkat <= ? AND jam_tiba >= ?) OR
                    (jam_berangkat <= ? AND jam_tiba >= ?) OR
                    (jam_berangkat >= ? AND jam_tiba <= ?)
                )
                AND status = 'aktif'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssssssss',
            $flightData['aircraft_type'],
            $flightData['tanggal'],
            $flightData['jam_berangkat'],
            $flightData['jam_berangkat'],
            $flightData['jam_tiba'],
            $flightData['jam_tiba'],
            $flightData['jam_berangkat'],
            $flightData['jam_tiba']
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }
    
    /**
     * Insert return flight ke database dengan prepared statement
     */
    private function insertReturnFlight($flightData) {
        $sql = "INSERT INTO penerbangan 
                (maskapai, kode_penerbangan, asal, tujuan, tanggal, 
                 jam_berangkat, jam_tiba, harga, kapasitas, tersedia, 
                 status, status_tracking, origin_airport_id, 
                 destination_airport_id, aircraft_type, current_lat, current_lon, progress)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif', 'Scheduled', ?, ?, ?, 
                        (SELECT lat FROM airports WHERE id = ?),
                        (SELECT lon FROM airports WHERE id = ?),
                        0.00)";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->addLog('error', "Prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('sssssssdiiissii',
            $flightData['maskapai'],
            $flightData['kode_penerbangan'],
            $flightData['asal'],
            $flightData['tujuan'],
            $flightData['tanggal'],
            $flightData['jam_berangkat'],
            $flightData['jam_tiba'],
            $flightData['harga'],
            $flightData['kapasitas'],
            $flightData['tersedia'],
            $flightData['origin_airport_id'],
            $flightData['destination_airport_id'],
            $flightData['aircraft_type'],
            $flightData['origin_airport_id'],
            $flightData['origin_airport_id']
        );
        
        $success = $stmt->execute();
        
        if (!$success) {
            $this->addLog('error', "Execute failed: " . $stmt->error);
        }
        
        return $success;
    }
    
    /**
     * Add log entry
     */
    private function addLog($type, $message) {
        $this->logs[] = [
            'type' => $type,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Save logs to file
     */
    private function saveLogsToFile() {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/auto_schedule_' . date('Y-m-d') . '.log';
        $logContent = "\n=== Auto Schedule Run: " . date('Y-m-d H:i:s') . " ===\n";
        
        foreach ($this->logs as $log) {
            $logContent .= "[{$log['timestamp']}] [{$log['type']}] {$log['message']}\n";
        }
        
        file_put_contents($logFile, $logContent, FILE_APPEND);
    }
    
    /**
     * Get recent return flights
     */
    public function getRecentReturnFlights($limit = 10) {
        $sql = "SELECT * FROM penerbangan 
                WHERE kode_penerbangan LIKE '%-R' 
                ORDER BY id DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $flights = [];
        while ($row = $result->fetch_assoc()) {
            $flights[] = $row;
        }
        
        return $flights;
    }
    
    /**
     * Get statistics
     */
    public function getStatistics() {
        // Total arrived flights tanpa return
        $sql1 = "SELECT COUNT(*) as count FROM penerbangan 
                 WHERE status_tracking = 'Arrived' 
                 AND status = 'aktif'
                 AND kode_penerbangan NOT LIKE '%-R'
                 AND NOT EXISTS (
                     SELECT 1 FROM penerbangan p2 
                     WHERE p2.kode_penerbangan = CONCAT(penerbangan.kode_penerbangan, '-R')
                 )";
        
        $result1 = $this->conn->query($sql1);
        $needingReturn = $result1->fetch_assoc()['count'];
        
        // Total return flights hari ini
        $sql2 = "SELECT COUNT(*) as count FROM penerbangan 
                 WHERE kode_penerbangan LIKE '%-R' 
                 AND DATE(created_at) = CURDATE()";
        
        $result2 = $this->conn->query($sql2);
        $generatedToday = $result2->fetch_assoc()['count'];
        
        // Total return flights
        $sql3 = "SELECT COUNT(*) as count FROM penerbangan 
                 WHERE kode_penerbangan LIKE '%-R'";
        
        $result3 = $this->conn->query($sql3);
        $totalReturn = $result3->fetch_assoc()['count'];
        
        return [
            'needing_return' => $needingReturn,
            'generated_today' => $generatedToday,
            'total_return' => $totalReturn
        ];
    }
}
