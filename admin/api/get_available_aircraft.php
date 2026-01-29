<?php
// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error di output
ini_set('log_errors', 1); // Log error ke file

require_once '../../config.php';

header('Content-Type: application/json');

try {
    // Cek koneksi database
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }

    // Query untuk menampilkan pesawat operasional yang tidak memiliki jadwal aktif
    // Pesawat dianggap "tersedia" jika tidak ada jadwal dengan status Scheduled atau Departed
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
        WHERE LOWER(p.status_pesawat) = 'operasional'
        AND p.id NOT IN (
            SELECT DISTINCT pesawat_id 
            FROM penerbangan 
            WHERE pesawat_id IS NOT NULL
            AND status_tracking IN ('Scheduled', 'Departed')
            AND tanggal >= CURDATE()
        )
        ORDER BY p.created_at DESC, p.maskapai, p.nomor_registrasi
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
    // Return JSON error instead of HTML
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>