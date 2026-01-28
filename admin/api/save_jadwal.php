<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Ambil dan validasi input
$id = $_POST['id'] ?? '';
$pesawat_id = $_POST['pesawat_id'] ?? '';
$kode_penerbangan = $_POST['kode_penerbangan'] ?? '';
$harga = $_POST['harga'] ?? 0;
$origin_airport_id = $_POST['origin_airport_id'] ?? '';
$destination_airport_id = $_POST['destination_airport_id'] ?? '';
$tanggal = $_POST['tanggal'] ?? '';
$jam_berangkat = $_POST['jam_berangkat'] ?? '';
$jam_tiba = $_POST['jam_tiba'] ?? '';
$status_tracking = $_POST['status_tracking'] ?? 'Scheduled';
$kapasitas = $_POST['kapasitas'] ?? 100;

// ====================================
// VALIDASI INPUT
// ====================================
$errors = [];

if (empty($kode_penerbangan)) $errors[] = "Kode penerbangan wajib diisi";
if (empty($tanggal)) $errors[] = "Tanggal wajib diisi";
if (empty($jam_berangkat)) $errors[] = "Jam berangkat wajib diisi";
if (empty($jam_tiba)) $errors[] = "Jam tiba wajib diisi";
if (empty($origin_airport_id)) $errors[] = "Bandara asal wajib dipilih";
if (empty($destination_airport_id)) $errors[] = "Bandara tujuan wajib dipilih";
if ($origin_airport_id === $destination_airport_id) $errors[] = "Bandara asal dan tujuan tidak boleh sama";

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// ====================================
// ESCAPE VALUES
// ====================================
$id = $id ? $conn->real_escape_string($id) : '';
$pesawat_id = $pesawat_id ? $conn->real_escape_string($pesawat_id) : '';
$kode_penerbangan = $conn->real_escape_string($kode_penerbangan);
$harga = $conn->real_escape_string($harga);
$origin_airport_id = $conn->real_escape_string($origin_airport_id);
$destination_airport_id = $conn->real_escape_string($destination_airport_id);
$tanggal = $conn->real_escape_string($tanggal);
$jam_berangkat = $conn->real_escape_string($jam_berangkat);
$jam_tiba = $conn->real_escape_string($jam_tiba);
$status_tracking = $conn->real_escape_string($status_tracking);
$kapasitas = (int)$kapasitas;

// ====================================
// GET AIRPORT NAMES
// ====================================
$origin_name = '';
$dest_name = '';

$origin_result = $conn->query("SELECT CONCAT(city, ' (', code, ')') as name FROM airports WHERE id = '$origin_airport_id'");
if ($origin_result && $origin_result->num_rows > 0) {
    $origin_name = $origin_result->fetch_assoc()['name'];
} else {
    echo json_encode(['success' => false, 'message' => 'Bandara asal tidak ditemukan']);
    exit;
}

$dest_result = $conn->query("SELECT CONCAT(city, ' (', code, ')') as name FROM airports WHERE id = '$destination_airport_id'");
if ($dest_result && $dest_result->num_rows > 0) {
    $dest_name = $dest_result->fetch_assoc()['name'];
} else {
    echo json_encode(['success' => false, 'message' => 'Bandara tujuan tidak ditemukan']);
    exit;
}

// ====================================
// GET PESAWAT INFO
// ====================================
$maskapai = 'Unknown';
$aircraft_type = 'Boeing 737';

if ($pesawat_id) {
    $pesawat_result = $conn->query("SELECT maskapai, model FROM pesawat WHERE id = '$pesawat_id' AND status_pesawat = 'operasional'");
    if ($pesawat_result && $pesawat_result->num_rows > 0) {
        $pesawat_data = $pesawat_result->fetch_assoc();
        $maskapai = $pesawat_data['maskapai'];
        $aircraft_type = $pesawat_data['model'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Pesawat tidak ditemukan atau tidak operasional']);
        exit;
    }
}

// ====================================
// VALIDASI TANGGAL & WAKTU
// ====================================
$tanggal_obj = DateTime::createFromFormat('Y-m-d', $tanggal);
$now = new DateTime();
$now->setTime(0, 0, 0); // Reset to midnight

if (!$tanggal_obj || $tanggal_obj < $now) {
    echo json_encode(['success' => false, 'message' => 'Tanggal penerbangan harus hari ini atau setelahnya']);
    exit;
}

// Validasi jam berangkat < jam tiba (untuk penerbangan di hari yang sama)
$berangkat_time = strtotime($jam_berangkat);
$tiba_time = strtotime($jam_tiba);

if ($tiba_time <= $berangkat_time) {
    // Jika jam tiba <= jam berangkat, asumsikan tiba besok (midnight flight)
    // Ini valid, tapi beri peringatan di log
    error_log("Warning: Flight {$kode_penerbangan} crosses midnight - Depart: {$jam_berangkat}, Arrive: {$jam_tiba}");
}

// ====================================
// EXECUTE QUERY
// ====================================
if ($id) {
    // UPDATE EXISTING
    $sql = "UPDATE penerbangan SET 
        pesawat_id = " . ($pesawat_id ? "'$pesawat_id'" : "NULL") . ",
        maskapai = '$maskapai',
        kode_penerbangan = '$kode_penerbangan',
        asal = '$origin_name',
        tujuan = '$dest_name',
        tanggal = '$tanggal',
        jam_berangkat = '$jam_berangkat',
        jam_tiba = '$jam_tiba',
        harga = '$harga',
        status_tracking = '$status_tracking',
        origin_airport_id = '$origin_airport_id',
        destination_airport_id = '$destination_airport_id',
        aircraft_type = '$aircraft_type'
        WHERE id = '$id'";
        
    $success_msg = 'Jadwal berhasil diupdate';
} else {
    // INSERT NEW
    $tersedia = $kapasitas;
    
    // Get origin coordinates for initial position
    $origin_lat = null;
    $origin_lon = null;
    $coord_result = $conn->query("SELECT lat, lon FROM airports WHERE id = '$origin_airport_id'");
    if ($coord_result && $coord_result->num_rows > 0) {
        $coords = $coord_result->fetch_assoc();
        $origin_lat = $coords['lat'];
        $origin_lon = $coords['lon'];
    }
    
    $sql = "INSERT INTO penerbangan 
        (pesawat_id, maskapai, kode_penerbangan, asal, tujuan, tanggal, jam_berangkat, jam_tiba, 
         harga, kapasitas, tersedia, status_tracking, origin_airport_id, destination_airport_id, 
         aircraft_type, current_lat, current_lon, status) 
        VALUES 
        (" . ($pesawat_id ? "'$pesawat_id'" : "NULL") . ", 
         '$maskapai', '$kode_penerbangan', '$origin_name', '$dest_name', 
         '$tanggal', '$jam_berangkat', '$jam_tiba', '$harga', '$kapasitas', '$tersedia', 
         '$status_tracking', 
         '$origin_airport_id', 
         '$destination_airport_id', 
         '$aircraft_type', 
         " . ($origin_lat ? "'$origin_lat'" : "NULL") . ", 
         " . ($origin_lon ? "'$origin_lon'" : "NULL") . ", 
         'aktif')";
         
    $success_msg = 'Jadwal berhasil ditambahkan';
}

// Execute with error handling
if ($conn->query($sql)) {
    // Update pesawat location if arrived
    if ($pesawat_id && $destination_airport_id && $status_tracking == 'Arrived') {
        $conn->query("UPDATE pesawat SET airport_id = '$destination_airport_id' WHERE id = '$pesawat_id'");
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $success_msg,
        'id' => $id ?: $conn->insert_id
    ]);
} else {
    // Log detailed error
    error_log("SQL Error in save_jadwal.php: " . $conn->error);
    error_log("Query: " . $sql);
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error menyimpan data: ' . $conn->error,
        'debug_query' => substr($sql, 0, 200) // First 200 chars for debugging
    ]);
}
?>