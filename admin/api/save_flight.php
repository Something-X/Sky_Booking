<?php
require_once '../../config.php';

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$maskapai = $conn->real_escape_string($_POST['maskapai']);
$kode = $conn->real_escape_string($_POST['kode_penerbangan']);
$asal = $conn->real_escape_string($_POST['asal']);
$tujuan = $conn->real_escape_string($_POST['tujuan']);
$tanggal = $conn->real_escape_string($_POST['tanggal']);
$jam_berangkat = $conn->real_escape_string($_POST['jam_berangkat']);
$jam_tiba = $conn->real_escape_string($_POST['jam_tiba']);
$harga = (float)$_POST['harga'];
$kapasitas = (int)$_POST['kapasitas'];
$status = $conn->real_escape_string($_POST['status']);

if ($id > 0) {
    // Update
    $sql = "UPDATE penerbangan SET 
            maskapai = '$maskapai',
            kode_penerbangan = '$kode',
            asal = '$asal',
            tujuan = '$tujuan',
            tanggal = '$tanggal',
            jam_berangkat = '$jam_berangkat',
            jam_tiba = '$jam_tiba',
            harga = $harga,
            kapasitas = $kapasitas,
            status = '$status'
            WHERE id = $id";
} else {
    // Insert
    $sql = "INSERT INTO penerbangan 
            (maskapai, kode_penerbangan, asal, tujuan, tanggal, jam_berangkat, jam_tiba, harga, kapasitas, tersedia, status)
            VALUES ('$maskapai', '$kode', '$asal', '$tujuan', '$tanggal', '$jam_berangkat', '$jam_tiba', $harga, $kapasitas, $kapasitas, '$status')";
}

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Data berhasil disimpan']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data: ' . $conn->error]);
}
?>