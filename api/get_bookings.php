<?php
require_once '../config.php';

header('Content-Type: application/json');

// ✅ 1. CEK APAKAH USER SUDAH LOGIN
if (!isLoggedIn() && !isAdmin()) {
    echo json_encode([
        'success' => false, 
        'message' => 'Silakan login terlebih dahulu'
    ]);
    exit;
}

// ✅ 2. TENTUKAN APAKAH INI ADMIN ATAU USER BIASA
$is_admin = isAdmin();
$user_id = null;

if (!$is_admin) {
    // Jika user biasa, ambil user_id dari session
    $user_id = (int)$_SESSION['user_id'];
}

$search = $conn->real_escape_string($_GET['search'] ?? '');

// ✅ 3. QUERY DENGAN FILTER USER_ID
$sql = "SELECT p.*, 
        pe.maskapai, pe.kode_penerbangan, pe.asal, pe.tujuan, 
        DATE_FORMAT(pe.tanggal, '%d %M %Y') as tanggal,
        DATE_FORMAT(pe.jam_berangkat, '%H:%i') as jam_berangkat,
        DATE_FORMAT(pe.jam_tiba, '%H:%i') as jam_tiba,
        u.nama_lengkap as user_name,
        u.email as user_email
        FROM pemesanan p
        JOIN penerbangan pe ON p.id_penerbangan = pe.id
        LEFT JOIN users u ON p.user_id = u.id
        WHERE 1=1";

// ✅ 4. FILTER BERDASARKAN USER (JIKA BUKAN ADMIN)
if (!$is_admin && $user_id) {
    $sql .= " AND p.user_id = $user_id";
}

// ✅ 5. SEARCH FILTER
if (!empty($search)) {
    $sql .= " AND (p.kode_booking LIKE '%$search%' 
              OR p.nama_pemesan LIKE '%$search%' 
              OR p.email LIKE '%$search%'
              OR p.no_hp LIKE '%$search%')";
}

$sql .= " ORDER BY p.created_at DESC LIMIT 50";

// ✅ 6. EXECUTE QUERY
$result = $conn->query($sql);

$bookings = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// ✅ 7. RETURN JSON
echo json_encode([
    'success' => true,
    'data' => $bookings,
    'total' => count($bookings),
    'is_admin' => $is_admin,
    'user_id' => $user_id // untuk debugging
]);
?>