<?php
header('Content-Type: application/json');
require_once '../config.php';

// Get POST parameters
$asal = $_POST['asal'] ?? '';
$tujuan = $_POST['tujuan'] ?? '';
$tanggal = $_POST['tanggal'] ?? '';
$kelas = $_POST['kelas'] ?? 'Economy';
$jumlah = (int)($_POST['jumlah'] ?? 1);

// Debug log
error_log("Search params: asal=$asal, tujuan=$tujuan, tanggal=$tanggal, kelas=$kelas, jumlah=$jumlah");

// Validasi input
if (empty($asal) || empty($tujuan) || empty($tanggal)) {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter tidak lengkap',
        'data' => [],
        'debug' => ['asal' => $asal, 'tujuan' => $tujuan, 'tanggal' => $tanggal]
    ]);
    exit;
}

try {
    // Query untuk mencari penerbangan sesuai kriteria
    // Sesuaikan dengan struktur database yang sebenarnya
    $query = "SELECT 
                p.id,
                p.kode_penerbangan,
                p.maskapai,
                p.asal,
                p.tujuan,
                p.jam_berangkat,
                p.jam_tiba,
                p.harga,
                p.tersedia,
                p.tanggal,
                ps.kelas_layanan
            FROM penerbangan p
            LEFT JOIN pesawat ps ON p.pesawat_id = ps.id
            WHERE 
                p.asal LIKE ?
                AND p.tujuan LIKE ?
                AND DATE(p.tanggal) = ?
                AND p.status = 'aktif'
                AND p.tersedia > 0
            ORDER BY p.jam_berangkat ASC";

    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare statement error: " . $conn->error);
    }

    // Gunakan LIKE untuk match format "Bali (DPS)" atau "Bali"
    $asal_like = '%' . $asal . '%';
    $tujuan_like = '%' . $tujuan . '%';

    $stmt->bind_param("sss", $asal_like, $tujuan_like, $tanggal);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute error: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $flights = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Filter berdasarkan kelas jika diperlukan
            $kelas_pesawat = $row['kelas_layanan'] ?? 'Economy Class';
            
            // Map kelas dari form ke database
            $kelas_map = [
                'Economy' => 'Economy Class',
                'Business' => 'Business Class',
                'First' => 'First Class'
            ];
            $kelas_db = $kelas_map[$kelas] ?? 'Economy Class';
            
            // Jika kelas berbeda, skip (optional, bisa dihapus jika ingin tampilkan semua)
            // if ($kelas_pesawat !== $kelas_db) continue;

            $flights[] = [
                'id' => (int)$row['id'],
                'kode_penerbangan' => $row['kode_penerbangan'],
                'maskapai' => $row['maskapai'],
                'asal' => $row['asal'],
                'tujuan' => $row['tujuan'],
                'jam_berangkat' => $row['jam_berangkat'],
                'jam_tiba' => $row['jam_tiba'],
                'harga' => (float)$row['harga'],
                'tersedia' => (int)$row['tersedia'],
                'tanggal_penerbangan' => $row['tanggal'],
                'kelas' => $kelas_db
            ];
        }
    }

    $stmt->close();

    // Return response
    echo json_encode([
        'success' => true,
        'message' => count($flights) > 0 ? 'Penerbangan ditemukan' : 'Tidak ada penerbangan yang sesuai',
        'data' => $flights,
        'count' => count($flights),
        'debug' => [
            'asal_like' => $asal_like,
            'tujuan_like' => $tujuan_like,
            'tanggal' => $tanggal
        ]
    ]);

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => []
    ]);
}
?>