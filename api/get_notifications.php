<?php
/**
 * Get User Notifications/Messages
 * Path: /api/get_notifications.php
 */

require_once '../config.php';

header('Content-Type: application/json');

// Check login
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

try {
    if ($action === 'count') {
        // ========== GET UNREAD COUNT ==========
        $sql = "SELECT COUNT(DISTINCT sr.id) as unread_count
                FROM support_responses sr
                JOIN support_tickets st ON sr.ticket_id = st.id
                WHERE st.user_id = ? 
                AND sr.read_by_user = 0";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'unread_count' => (int)$row['unread_count']
        ]);
        
    } elseif ($action === 'list') {
        // ========== GET MESSAGE LIST ==========
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        $sql = "SELECT 
                    sr.id as response_id,
                    sr.ticket_id,
                    sr.pesan,
                    sr.read_by_user,
                    sr.created_at,
                    st.subjek,
                    st.kategori,
                    st.status,
                    a.nama_lengkap as admin_name
                FROM support_responses sr
                JOIN support_tickets st ON sr.ticket_id = st.id
                LEFT JOIN admin a ON sr.admin_id = a.id
                WHERE st.user_id = ?
                ORDER BY sr.created_at DESC
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'total' => count($messages)
        ]);
        
    } elseif ($action === 'mark_read') {
        // ========== MARK AS READ ==========
        $response_id = isset($_POST['response_id']) ? (int)$_POST['response_id'] : 0;
        
        if ($response_id > 0) {
            // Verify ownership
            $sql_verify = "SELECT sr.id 
                          FROM support_responses sr
                          JOIN support_tickets st ON sr.ticket_id = st.id
                          WHERE sr.id = ? AND st.user_id = ?";
            
            $stmt = $conn->prepare($sql_verify);
            $stmt->bind_param("ii", $response_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Mark as read
                $sql_update = "UPDATE support_responses SET read_by_user = 1 WHERE id = ?";
                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param("i", $response_id);
                $stmt->execute();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Marked as read'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Response not found'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid response ID'
            ]);
        }
        
    } elseif ($action === 'mark_all_read') {
        // ========== MARK ALL AS READ ==========
        $sql = "UPDATE support_responses sr
                JOIN support_tickets st ON sr.ticket_id = st.id
                SET sr.read_by_user = 1
                WHERE st.user_id = ? AND sr.read_by_user = 0";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'All messages marked as read',
            'affected_rows' => $stmt->affected_rows
        ]);
    }
    
} catch (Exception $e) {
    error_log("Get Notifications Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>