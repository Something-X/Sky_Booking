<?php
require_once '../config.php';

/**
 * 🔍 MANUAL PAYMENT CHECKER
 * Script untuk cek status pembayaran secara manual dari Midtrans
 * 
 * Cara Pakai:
 * Browser: http://localhost/coba87/api/check_payment.php?order_id=ORDER-XXX
 * Terminal: php check_payment.php ORDER-XXX
 */

header('Content-Type: application/json');

// Get order_id from GET atau command line
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : (isset($argv[1]) ? $argv[1] : null);

if (empty($order_id)) {
    echo json_encode([
        'success' => false,
        'message' => '❌ Order ID required!',
        'usage' => 'check_payment.php?order_id=ORDER-XXX'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    echo json_encode(['status' => 'processing', 'message' => "🔍 Checking payment for: $order_id"], JSON_PRETTY_PRINT);
    error_log("🔍 Checking payment status for: $order_id");
    
    // ========== Request Status ke Midtrans ==========
    $curl = curl_init();
    
    $midtrans_url = MIDTRANS_IS_PRODUCTION 
        ? "https://api.midtrans.com/v2/$order_id/status"
        : "https://api.sandbox.midtrans.com/v2/$order_id/status";
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $midtrans_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($http_code !== 200) {
        throw new Exception("Failed to get transaction status (HTTP $http_code)");
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        throw new Exception("Invalid response from Midtrans");
    }
    
    // ========== Tentukan Status ==========
    $transaction_status = $result['transaction_status'];
    $fraud_status = isset($result['fraud_status']) ? $result['fraud_status'] : null;
    $payment_type = $result['payment_type'];
    $transaction_time = $result['transaction_time'];
    
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
    $sql = "UPDATE pemesanan 
            SET status = ?,
                payment_type = ?,
                transaction_time = ?
            WHERE order_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $status_pembayaran, $payment_type, $transaction_time, $order_id);
    $stmt->execute();
    
    $affected = $stmt->affected_rows;
    
    // ========== Log ke transaction_logs ==========
    if ($affected > 0) {
        $sql_log = "INSERT INTO transaction_logs (order_id, transaction_status, payment_type, gross_amount, raw_notification, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    transaction_status = VALUES(transaction_status),
                    payment_type = VALUES(payment_type)";
        
        $stmt_log = $conn->prepare($sql_log);
        $gross_amount = $result['gross_amount'];
        $raw_json = json_encode($result);
        $stmt_log->bind_param("sssds", $order_id, $transaction_status, $payment_type, $gross_amount, $raw_json);
        $stmt_log->execute();
    }
    
    error_log("✅ Status updated: $order_id → $status_pembayaran");
    
    // ========== Response ==========
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'midtrans_status' => $transaction_status,
        'payment_status' => $status_pembayaran,
        'payment_type' => $payment_type,
        'transaction_time' => $transaction_time,
        'database_updated' => $affected > 0,
        'affected_rows' => $affected,
        'message' => $affected > 0 
            ? "✅ Status berhasil diupdate menjadi: $status_pembayaran" 
            : "⚠️ Order ID tidak ditemukan di database",
        'full_response' => $result
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("❌ Check Payment Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'order_id' => $order_id
    ], JSON_PRETTY_PRINT);
}
?>  