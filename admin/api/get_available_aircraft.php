<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    // Query untuk mendapatkan pesawat yang tidak memiliki jadwal aktif
    $query = "
        SELECT 
            p.id,
            p.maskapai,
            p.nomor_registrasi,
            p.model,
            p.kapasitas,
            p.kelas_layanan,
            p.status_pesawat,
            CONCAT(a.city, ' (', a.code, ')') as lokasi_terakhir,
            a.id as airport_id
        FROM pesawat p
        LEFT JOIN airports a ON p.airport_id = a.id
        WHERE p.status_pesawat = 'operasional'
        AND p.id NOT IN (
            SELECT DISTINCT pesawat_id 
            FROM jadwal_penerbangan 
            WHERE status_tracking IN ('Scheduled', 'Departed')
            AND pesawat_id IS NOT NULL
        )
        ORDER BY p.maskapai, p.nomor_registrasi
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query error: " . $conn->error);
    }
    
    $pesawat = [];
    while ($row = $result->fetch_assoc()) {
        $pesawat[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $pesawat,
        'count' => count($pesawat)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>