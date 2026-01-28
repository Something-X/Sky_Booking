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
$is_roundtrip = isset($_POST['is_roundtrip']) && $_POST['is_roundtrip'] === '1';

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

try {
    // ========== Generate Unique Order ID ==========
    $order_id = 'ORDER-' . $kode_booking . '-' . time();
    
    // ========== Get Flight Details ==========
    $item_details = [];
    
    if ($is_roundtrip) {
        // Get both departure and return flight details
        $sql = "SELECT 
                    p1.jumlah_penumpang,
                    pn1.kode_penerbangan as departure_flight,
                    pn1.maskapai as departure_airline,
                    pn1.asal as departure_origin,
                    pn1.tujuan as departure_destination,
                    pn1.harga as departure_price,
                    pn2.kode_penerbangan as return_flight,
                    pn2.maskapai as return_airline,
                    pn2.asal as return_origin,
                    pn2.tujuan as return_destination,
                    pn2.harga as return_price
                FROM pemesanan p1
                LEFT JOIN penerbangan pn1 ON p1.id_penerbangan = pn1.id
                LEFT JOIN pemesanan p2 ON p1.kode_booking = p2.linked_booking_code
                LEFT JOIN penerbangan pn2 ON p2.id_penerbangan = pn2.id
                WHERE p1.id = ? AND p1.tipe_booking = 'departure'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_pemesanan);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Data booking tidak ditemukan');
        }
        
        $booking_details = $result->fetch_assoc();
        
        $departure_total = (int)($booking_details['departure_price'] * $booking_details['jumlah_penumpang']);
        $return_total = (int)($booking_details['return_price'] * $booking_details['jumlah_penumpang']);
        
        if (($departure_total + $return_total) !== $amount) {
            throw new Exception('Total amount mismatch');
        }
        
        $item_details = [
            [
                'id' => 'DEP-' . $booking_details['departure_flight'],
                'price' => $departure_total,
                'quantity' => 1,
                'name' => substr(sprintf('%s %s-%s x%d pax',
                    $booking_details['departure_airline'],
                    $booking_details['departure_origin'],
                    $booking_details['departure_destination'],
                    $booking_details['jumlah_penumpang']
                ), 0, 50)
            ],
            [
                'id' => 'RET-' . $booking_details['return_flight'],
                'price' => $return_total,
                'quantity' => 1,
                'name' => substr(sprintf('%s %s-%s x%d pax',
                    $booking_details['return_airline'],
                    $booking_details['return_origin'],
                    $booking_details['return_destination'],
                    $booking_details['jumlah_penumpang']
                ), 0, 50)
            ]
        ];
        
    } else {
        // One-way flight
        $sql = "SELECT 
                    p.jumlah_penumpang,
                    pn.kode_penerbangan, 
                    pn.maskapai,
                    pn.asal, 
                    pn.tujuan,
                    pn.harga
                FROM pemesanan p 
                JOIN penerbangan pn ON p.id_penerbangan = pn.id 
                WHERE p.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_pemesanan);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Data booking tidak ditemukan');
        }
        
        $booking_details = $result->fetch_assoc();
        
        $item_details = [
            [
                'id' => 'FLIGHT-' . $booking_details['kode_penerbangan'],
                'price' => $amount,
                'quantity' => 1,
                'name' => substr(sprintf('%s %s-%s x%d',
                    $booking_details['maskapai'],
                    $booking_details['asal'],
                    $booking_details['tujuan'],
                    $booking_details['jumlah_penumpang']
                ), 0, 50)
            ]
        ];
    }
    
    // ========== Update order_id di database ==========
    $sql = "UPDATE pemesanan SET order_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $order_id, $id_pemesanan);
    $stmt->execute();
    
    if ($is_roundtrip) {
        $sql = "UPDATE pemesanan SET order_id = ? WHERE linked_booking_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $order_id, $kode_booking);
        $stmt->execute();
    }
    
    // ========== Setup Midtrans Request (WITH NOTIFICATION URL) ==========
    $params = [
        'transaction_details' => [
            'order_id' => $order_id,
            'gross_amount' => $amount,
        ],
        'customer_details' => [
            'first_name' => $nama,
            'email' => $email,
            'phone' => $phone,
        ],
        'item_details' => $item_details,
        'enabled_payments' => [
            'credit_card', 'bca_va', 'bni_va', 'bri_va', 
            'permata_va', 'other_va', 'gopay', 'shopeepay', 'qris'
        ],
        'credit_card' => [
            'secure' => true
        ],
        'callbacks' => [
            'finish' => SITE_URL . '/payment_success.php?order_id=' . $order_id,
            // 🔥 TAMBAHAN PENTING: Notification URL
            // Jika masih localhost, gunakan ngrok atau alternatif lain (lihat solusi di bawah)
        ]
    ];
    
    // 🔥 TAMBAHAN: Notification URL (jika sudah ada domain/ngrok)
    // Uncomment baris ini jika sudah deploy atau pakai ngrok:
    // $params['callbacks']['notification'] = SITE_URL . '/api/midtrans_notification.php';
    
    // ========== Request ke Midtrans ==========
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
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    error_log("Midtrans Request: " . json_encode($params));
    error_log("Midtrans Response: " . $response);
    error_log("HTTP Code: " . $http_code);
    
    if ($error) {
        throw new Exception('Connection error: ' . $error);
    }
    
    $result = json_decode($response, true);
    
    if ($http_code == 201 && isset($result['token'])) {
        echo json_encode([
            'success' => true,
            'token' => $result['token'],
            'redirect_url' => $result['redirect_url'] ?? '',
            'order_id' => $order_id
        ]);
    } else {
        $error_message = 'Gagal generate token';
        
        if (isset($result['error_messages'])) {
            $error_message = is_array($result['error_messages']) 
                ? implode(', ', $result['error_messages']) 
                : $result['error_messages'];
        } elseif (isset($result['status_message'])) {
            $error_message = $result['status_message'];
        }
        
        throw new Exception($error_message . ' (HTTP ' . $http_code . ')');
    }
    
} catch (Exception $e) {
    error_log("Payment Token Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>