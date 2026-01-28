    <?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = $_POST['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

$id = (int)$id;

// Check if aircraft is being used in any active flights
$check = $conn->query("SELECT COUNT(*) as total FROM penerbangan WHERE pesawat_id = $id AND status = 'aktif'");
if ($check) {
    $row = $check->fetch_assoc();
    if ($row['total'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Pesawat tidak dapat dihapus karena masih digunakan dalam jadwal penerbangan aktif']);
        exit;
    }
}

$sql = "DELETE FROM pesawat WHERE id = $id";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Pesawat berhasil dihapus']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}
?>