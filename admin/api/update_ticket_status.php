<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $ticket_id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
    $status = trim($_POST['status'] ?? '');
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    // Validasi
    if ($ticket_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID ticket tidak valid']);
        exit;
    }
    
    $valid_statuses = ['open', 'in_progress', 'resolved', 'closed'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
        exit;
    }
    
    // Check if ticket exists
    $check_stmt = $conn->prepare("SELECT id FROM support_tickets WHERE id = ?");
    $check_stmt->bind_param("i", $ticket_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Ticket tidak ditemukan']);
        exit;
    }
    $check_stmt->close();
    
    // Update ticket
    $resolved_at = ($status === 'resolved' || $status === 'closed') ? date('Y-m-d H:i:s') : null;
    
    $stmt = $conn->prepare("UPDATE support_tickets SET status = ?, admin_notes = ?, resolved_at = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sssi", $status, $admin_notes, $resolved_at, $ticket_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Status ticket berhasil diupdate'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal update status: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error in update_ticket_status.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>