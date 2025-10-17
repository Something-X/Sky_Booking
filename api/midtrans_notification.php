<?php
require_once '../config.php';

// Read notification from Midtrans
$json = file_get_contents('php://input');
$notification = json_decode($json);

// Verify signature key
$signatureKey = hash('sha512', $notification->order_id . $notification->status_code . $notification->gross_amount . MIDTRANS_SERVER_KEY);

if ($signatureKey != $notification->signature_key) {
    http_response_code(403);
    exit('Invalid signature');
}

$order_id = $notification->order_id;
$transaction_status = $notification->transaction_status;
$fraud_status = $notification->fraud_status ?? '';

// Update database based on transaction status
$status = 'pending';

if ($transaction_status == 'capture') {
    if ($fraud_status == 'accept') {
        $status = 'lunas';
    }
} else if ($transaction_status == 'settlement') {
    $status = 'lunas';
} else if ($transaction_status == 'cancel' || $transaction_status == 'deny' || $transaction_status == 'expire') {
    $status = 'batal';
}

// Update pemesanan status
$order_id = $conn->real_escape_string($order_id);
$status = $conn->real_escape_string($status);
$conn->query("UPDATE pemesanan SET status = '$status' WHERE kode_booking = '$order_id'");

http_response_code(200);
echo json_encode(['success' => true]);
?>