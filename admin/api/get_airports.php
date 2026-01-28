<?php
require_once '../../config.php';

header('Content-Type: application/json');

// Allow access for admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $sql = "SELECT id, code, name, city FROM airports ORDER BY city ASC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id' => $row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'city' => $row['city']
            ];
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
}
?>