<?php
require_once '../config.php';

header('Content-Type: application/json');

// Get notification from Midtrans
$json = file_get_contents('php://input');
$notification = json_decode($json);

// Log untuk debugging
error_log("Midtrans Notification Received: " . $json);

if (!$notification) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

try {
    // Ambil data dari notifikasi
    $order_id = $notification->order_id;
    $transaction_status = $notification->transaction_status;
    $fraud_status = isset($notification->fraud_status) ? $notification->fraud_status : null;
    $payment_type = $notification->payment_type;
    $transaction_time = $notification->transaction_time;
    $gross_amount = $notification->gross_amount;
    
    // ========== Verify Signature ==========
    $signature_key = $notification->signature_key;
    $hashed = hash('sha512', $order_id . $notification->status_code . $gross_amount . MIDTRANS_SERVER_KEY);
    
    if ($signature_key !== $hashed) {
        error_log("⚠️ Invalid signature! Order: " . $order_id);
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit;
    }
    
    // ========== Tentukan Status Pembayaran ==========
    $status_pembayaran = 'pending';
    
    if ($transaction_status == 'capture') {
        if ($fraud_status == 'accept') {
            $status_pembayaran = 'lunas';
        }
    } else if ($transaction_status == 'settlement') {
        $status_pembayaran = 'lunas';
    } else if ($transaction_status == 'pending') {
        $status_pembayaran = 'pending';
    } else if ($transaction_status == 'deny' || $transaction_status == 'expire' || $transaction_status == 'cancel') {
        $status_pembayaran = 'batal';
    }
    
    // ========== Update Database ==========
    $conn->begin_transaction();
    
    try {
        // Update pemesanan berdasarkan order_id
        $sql = "UPDATE pemesanan 
                SET status = ?,
                    payment_type = ?,
                    transaction_time = ?
                WHERE order_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $status_pembayaran, $payment_type, $transaction_time, $order_id);
        $stmt->execute();
        
        $affected_rows = $stmt->affected_rows;
        
        if ($affected_rows === 0) {
            throw new Exception("Order not found: " . $order_id);
        }
        
        // Log transaksi
        $sql_log = "INSERT INTO transaction_logs (order_id, transaction_status, payment_type, gross_amount, raw_notification, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->bind_param("sssds", $order_id, $transaction_status, $payment_type, $gross_amount, $json);
        $stmt_log->execute();
        
        $conn->commit();
        
        error_log("✅ Payment updated! Order: $order_id → Status: $status_pembayaran");
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Notification processed',
            'order_id' => $order_id,
            'payment_status' => $status_pembayaran
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("❌ Webhook Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>