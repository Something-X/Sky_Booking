<?php
/**
 * Test Database API
 * Path: /api/test_db.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? 'count';
    
    if ($action === 'count') {
        // Test: Count tickets
        $result = $conn->query("SELECT COUNT(*) as total FROM support_tickets");
        
        if ($result) {
            $row = $result->fetch_assoc();
            echo json_encode([
                'success' => true,
                'count' => $row['total'],
                'message' => 'Database connection successful!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Query failed: ' . $conn->error
            ]);
        }
        
    } elseif ($action === 'list') {
        // Test: List latest tickets
        $result = $conn->query("SELECT * FROM support_tickets ORDER BY created_at DESC LIMIT 5");
        
        if ($result) {
            $tickets = [];
            while ($row = $result->fetch_assoc()) {
                $tickets[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'tickets' => $tickets
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Query failed: ' . $conn->error
            ]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage()
    ]);
}
?>