<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ticket_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID ticket tidak valid']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM support_tickets WHERE id = ?");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($ticket = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'ticket' => $ticket
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Ticket tidak ditemukan'
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error in get_ticket_detail.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>