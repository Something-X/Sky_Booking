<?php
require_once '../config.php';

header('Content-Type: application/json');

// ✅ PASTIKAN USER SUDAH LOGIN
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// ✅ AMBIL USER ID DARI SESSION
$user_id = (int)$_SESSION['user_id'];

// Validasi input
$id_penerbangan = (int)$_POST['id_penerbangan'];
$jumlah_penumpang = (int)$_POST['jumlah_penumpang'];
$nama_pemesan = $conn->real_escape_string($_POST['nama_pemesan']);
$email = $conn->real_escape_string($_POST['email']);
$no_hp = $conn->real_escape_string($_POST['no_hp']);

// Validasi data penumpang
$penumpang_nama = $_POST['penumpang_nama'] ?? [];
$penumpang_gender = $_POST['penumpang_gender'] ?? [];
$penumpang_tgl_lahir = $_POST['penumpang_tgl_lahir'] ?? [];
$penumpang_nik = $_POST['penumpang_nik'] ?? [];

if (empty($nama_pemesan) || empty($email) || empty($no_hp)) {
    echo json_encode(['success' => false, 'message' => 'Data pemesan tidak lengkap']);
    exit;
}

if (count($penumpang_nama) !== $jumlah_penumpang) {
    echo json_encode(['success' => false, 'message' => 'Data penumpang tidak lengkap']);
    exit;
}

// Cek ketersediaan kursi
$sql = "SELECT * FROM penerbangan WHERE id = $id_penerbangan AND status = 'aktif'";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Penerbangan tidak ditemukan']);
    exit;
}

$flight = $result->fetch_assoc();

if ($flight['tersedia'] < $jumlah_penumpang) {
    echo json_encode(['success' => false, 'message' => 'Kursi tidak mencukupi']);
    exit;
}

// Mulai transaksi
$conn->begin_transaction();

try {
    // Generate kode booking
    $kode_booking = generateKodeBooking();
    
    // Hitung total harga
    $total_harga = $flight['harga'] * $jumlah_penumpang;
    
    // ✅ INSERT PEMESANAN DENGAN USER_ID
    $sql = "INSERT INTO pemesanan (user_id, id_penerbangan, kode_booking, nama_pemesan, email, no_hp, jumlah_penumpang, total_harga, status) 
            VALUES ($user_id, $id_penerbangan, '$kode_booking', '$nama_pemesan', '$email', '$no_hp', $jumlah_penumpang, $total_harga, 'pending')";
    
    if (!$conn->query($sql)) {
        throw new Exception('Gagal menyimpan pemesanan: ' . $conn->error);
    }
    
    $id_pemesanan = $conn->insert_id;
    
    // Insert data penumpang
    for ($i = 0; $i < $jumlah_penumpang; $i++) {
        $nama = $conn->real_escape_string($penumpang_nama[$i]);
        $gender = $conn->real_escape_string($penumpang_gender[$i]);
        $tgl_lahir = $conn->real_escape_string($penumpang_tgl_lahir[$i]);
        $nik = $conn->real_escape_string($penumpang_nik[$i] ?? '');
        
        $sql = "INSERT INTO penumpang (id_pemesanan, nama_lengkap, jenis_kelamin, tanggal_lahir, nik) 
                VALUES ($id_pemesanan, '$nama', '$gender', '$tgl_lahir', '$nik')";
        
        if (!$conn->query($sql)) {
            throw new Exception('Gagal menyimpan data penumpang: ' . $conn->error);
        }
    }
    
    // Update kursi tersedia
    $sql = "UPDATE penerbangan SET tersedia = tersedia - $jumlah_penumpang WHERE id = $id_penerbangan";
    if (!$conn->query($sql)) {
        throw new Exception('Gagal update kursi tersedia: ' . $conn->error);
    }
    
    // Commit transaksi
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pemesanan berhasil',
        'kode_booking' => $kode_booking,
        'id_pemesanan' => $id_pemesanan,
        'redirect_payment' => true
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>