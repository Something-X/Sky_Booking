<?php
require_once '../../config.php';

header('Content-Type: application/json');

// =======================
// AUTH CHECK
// =======================
if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// =======================
// METHOD CHECK
// =======================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {

    // =======================
    // AMBIL DATA
    // =======================
    $id = $_POST['id'] ?? null;

    $maskapai = trim($_POST['maskapai'] ?? '');
    $nomor_registrasi = trim($_POST['nomor_registrasi'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $kapasitas = intval($_POST['kapasitas'] ?? 0);
    $kelas_layanan = $_POST['kelas_layanan'] ?? 'Economy Class';
    $status_pesawat = $_POST['status_pesawat'] ?? 'operasional';

    // airport boleh kosong
    $airport_id = !empty($_POST['airport_id']) ? intval($_POST['airport_id']) : 0;

    // =======================
    // VALIDASI WAJIB
    // =======================
    if ($maskapai === '' || $nomor_registrasi === '' || $model === '' || $kapasitas < 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Data tidak lengkap'
        ]);
        exit;
    }

    // =======================
    // VALIDASI ENUM
    // =======================
    $allowed_kelas = [
        'Economy Class',
        'Business Class',
        'First Class'
    ];

    if (!in_array($kelas_layanan, $allowed_kelas, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Kelas layanan tidak valid'
        ]);
        exit;
    }

    $allowed_status = [
        'operasional',
        'maintenance',
        'non-aktif'
    ];

    if (!in_array($status_pesawat, $allowed_status, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Status pesawat tidak valid'
        ]);
        exit;
    }

    // =======================
    // INSERT / UPDATE
    // =======================
    if ($id) {
        // UPDATE
        $stmt = $conn->prepare("
            UPDATE pesawat SET
                maskapai = ?,
                nomor_registrasi = ?,
                model = ?,
                kapasitas = ?,
                kelas_layanan = ?,
                airport_id = ?,
                status_pesawat = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "sssisisi",
            $maskapai,
            $nomor_registrasi,
            $model,
            $kapasitas,
            $kelas_layanan,
            $airport_id,
            $status_pesawat,
            $id
        );

    } else {
        // INSERT
        $stmt = $conn->prepare("
            INSERT INTO pesawat
                (maskapai, nomor_registrasi, model, kapasitas, kelas_layanan, airport_id, status_pesawat)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssiiss",
            $maskapai,
            $nomor_registrasi,
            $model,
            $kapasitas,
            $kelas_layanan,
            $airport_id,
            $status_pesawat
        );
    }

    // =======================
    // EKSEKUSI
    // =======================
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => $id
                ? 'Pesawat berhasil diperbarui'
                : 'Pesawat berhasil ditambahkan'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan data'
        ]);
    }

    $stmt->close();

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$conn->close();
