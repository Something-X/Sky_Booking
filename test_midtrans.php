<?php
require_once 'config.php';

echo "<h2>Test Midtrans Configuration</h2>";

// Cek konfigurasi
echo "<h3>1. Cek Konfigurasi:</h3>";
echo "Server Key: " . (defined('MIDTRANS_SERVER_KEY') ? '✅ Set' : '❌ Not Set') . "<br>";
echo "Client Key: " . (defined('MIDTRANS_CLIENT_KEY') ? '✅ Set' : '❌ Not Set') . "<br>";
echo "Server Key Value: " . substr(MIDTRANS_SERVER_KEY, 0, 20) . "..." . "<br>";
echo "Client Key Value: " . substr(MIDTRANS_CLIENT_KEY, 0, 20) . "..." . "<br>";

// Test koneksi ke Midtrans
echo "<h3>2. Test Koneksi ke Midtrans API:</h3>";

$params = [
    'transaction_details' => [
        'order_id' => 'TEST-' . time(),
        'gross_amount' => 100000,
    ],
    'customer_details' => [
        'first_name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '08123456789',
    ],
    'item_details' => [
        [
            'id' => 'ITEM1',
            'price' => 100000,
            'quantity' => 1,
            'name' => 'Test Item'
        ]
    ]
];

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
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

echo "HTTP Code: " . $http_code . "<br>";

if ($error) {
    echo "❌ CURL Error: " . $error . "<br>";
} else {
    echo "✅ Connection OK<br>";
}

echo "<h3>3. Response:</h3>";
echo "<pre>" . print_r(json_decode($response, true), true) . "</pre>";

if ($http_code == 201) {
    echo "<h3 style='color: green;'>✅ SUCCESS! Midtrans API Working!</h3>";
} else {
    echo "<h3 style='color: red;'>❌ FAILED! Check error above</h3>";
}
?>