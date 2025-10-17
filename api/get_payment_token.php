<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$id_pemesanan = (int)$_POST['id_pemesanan'];
$kode_booking = $_POST['kode_booking'];
$nama = $_POST['nama'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$amount = (int)$_POST['amount'];

// Validasi
if (empty($id_pemesanan) || empty($kode_booking) || empty($amount)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

// Cek apakah Midtrans keys sudah diset
if (!defined('MIDTRANS_SERVER_KEY') || empty(MIDTRANS_SERVER_KEY)) {
    echo json_encode(['success' => false, 'message' => 'Midtrans Server Key belum dikonfigurasi. Silakan update config.php']);
    exit;
}

// Setup Midtrans Request
$params = [
    'transaction_details' => [
        'order_id' => $kode_booking,
        'gross_amount' => $amount,
    ],
    'customer_details' => [
        'first_name' => $nama,
        'email' => $email,
        'phone' => $phone,
    ],
    'item_details' => [
        [
            'id' => 'TICKET_' . $id_pemesanan,
            'price' => $amount,
            'quantity' => 1,
            'name' => 'Tiket Pesawat - ' . $kode_booking
        ]
    ],
    'enabled_payments' => [
        'credit_card', 'bca_va', 'bni_va', 'bri_va', 
        'permata_va', 'other_va', 'gopay', 'shopeepay', 'qris'
    ],
    'credit_card' => [
        'secure' => true
    ]
];

// Request ke Midtrans
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($params),
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
    ],
    CURLOPT_SSL_VERIFYPEER => false, // Untuk development
    CURLOPT_SSL_VERIFYHOST => false, // Untuk development
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

// Log untuk debugging
error_log("Midtrans Response: " . $response);
error_log("HTTP Code: " . $http_code);

if ($error) {
    echo json_encode([
        'success' => false, 
        'message' => 'Connection error: ' . $error
    ]);
    exit;
}

$result = json_decode($response, true);

if ($http_code == 201 && isset($result['token'])) {
    echo json_encode([
        'success' => true,
        'token' => $result['token'],
        'redirect_url' => $result['redirect_url'] ?? ''
    ]);
} else {
    // Error dari Midtrans
    $error_message = 'Gagal generate token';
    
    if (isset($result['error_messages'])) {
        $error_message = is_array($result['error_messages']) 
            ? implode(', ', $result['error_messages']) 
            : $result['error_messages'];
    } elseif (isset($result['status_message'])) {
        $error_message = $result['status_message'];
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $error_message,
        'details' => $result,
        'http_code' => $http_code
    ]);
}
?>