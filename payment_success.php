<?php
require_once 'config.php';

// ========== PERBAIKAN: Validasi dan Auto-check ke Midtrans ==========
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;

if (!$order_id) {
    redirect('index.php');
}

$order_id = $conn->real_escape_string($order_id);

// ========== AUTO-CHECK STATUS KE MIDTRANS ==========
// Ini penting untuk memastikan status benar-benar dari Midtrans
try {
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
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($http_code == 200) {
        $result = json_decode($response, true);
        
        $transaction_status = $result['transaction_status'];
        $fraud_status = isset($result['fraud_status']) ? $result['fraud_status'] : null;
        $payment_type = $result['payment_type'];
        $transaction_time = $result['transaction_time'];
        
        // Tentukan status pembayaran
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
        
        // ✅ PERBAIKAN: Update berdasarkan order_id (BUKAN kode_booking)
        $sql = "UPDATE pemesanan 
                SET status = ?,
                    payment_type = ?,
                    transaction_time = ?
                WHERE order_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $status_pembayaran, $payment_type, $transaction_time, $order_id);
        $stmt->execute();
        
        error_log("✅ Payment Success Page: Updated $order_id to $status_pembayaran");
        
        // Log transaction
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
    
} catch (Exception $e) {
    error_log("Payment Success Error: " . $e->getMessage());
}

// ========== GET BOOKING DETAILS ==========
$sql = "SELECT p.*, pe.maskapai, pe.kode_penerbangan, pe.asal, pe.tujuan,
        DATE_FORMAT(pe.tanggal, '%d %M %Y') as tanggal_format
        FROM pemesanan p
        JOIN penerbangan pe ON p.id_penerbangan = pe.id
        WHERE p.order_id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('index.php');
}

$booking = $result->fetch_assoc();
$status = $booking['status'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - SkyBooking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0066cc',
                        secondary: '#004999',
                        accent: '#ff6b35'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen flex items-center justify-center p-4">
    
    <?php if ($status === 'lunas'): ?>
        <!-- ✅ PEMBAYARAN BERHASIL -->
        <div class="bg-white rounded-2xl shadow-2xl p-12 max-w-2xl w-full text-center">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 animate-bounce">
                <i class="fas fa-check-circle text-6xl text-green-500"></i>
            </div>
            
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Pembayaran Berhasil!</h1>
            <p class="text-xl text-gray-600 mb-8">Terima kasih telah melakukan pembayaran</p>
            
            <div class="bg-green-50 rounded-xl p-6 mb-8">
                <p class="text-gray-700 mb-2">Kode Booking Anda:</p>
                <p class="text-3xl font-bold text-primary"><?= htmlspecialchars($booking['kode_booking']) ?></p>
                
                <div class="mt-4 pt-4 border-t border-green-200">
                    <div class="grid grid-cols-2 gap-4 text-left">
                        <div>
                            <p class="text-sm text-gray-600">Penerbangan</p>
                            <p class="font-bold text-gray-800"><?= $booking['maskapai'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Rute</p>
                            <p class="font-bold text-gray-800"><?= $booking['asal'] ?> → <?= $booking['tujuan'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Tanggal</p>
                            <p class="font-bold text-gray-800"><?= $booking['tanggal_format'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Bayar</p>
                            <p class="font-bold text-green-600"><?= formatRupiah($booking['total_harga']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="space-y-3 mb-8">
                <p class="text-gray-600">
                    <i class="fas fa-envelope text-primary mr-2"></i>
                    E-ticket telah dikirim ke email <strong><?= htmlspecialchars($booking['email']) ?></strong>
                </p>
                <p class="text-gray-600">
                    <i class="fas fa-mobile-alt text-primary mr-2"></i>
                    Simpan kode booking untuk check-in
                </p>
                <?php if (!empty($booking['payment_type'])): ?>
                <p class="text-gray-600">
                    <i class="fas fa-credit-card text-primary mr-2"></i>
                    Metode: <strong class="uppercase"><?= $booking['payment_type'] ?></strong>
                </p>
                <?php endif; ?>
            </div>
            
            <div class="space-y-3">
                <a href="riwayat.php" class="block w-full bg-primary hover:bg-secondary text-white font-bold py-4 rounded-lg transition duration-300">
                    <i class="fas fa-history mr-2"></i>
                    Lihat Riwayat Pemesanan
                </a>
                <a href="index.php" class="block w-full border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-4 rounded-lg transition duration-300">
                    <i class="fas fa-home mr-2"></i>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
        
    <?php elseif ($status === 'pending'): ?>
        <!-- ⏳ MENUNGGU PEMBAYARAN -->
        <div class="bg-white rounded-2xl shadow-2xl p-12 max-w-2xl w-full text-center">
            <div class="w-24 h-24 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-clock text-6xl text-yellow-500"></i>
            </div>
            
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Menunggu Pembayaran</h1>
            <p class="text-xl text-gray-600 mb-8">Silakan selesaikan pembayaran Anda</p>
            
            <div class="bg-yellow-50 rounded-xl p-6 mb-8">
                <p class="text-gray-700 mb-2">Kode Booking:</p>
                <p class="text-3xl font-bold text-yellow-600"><?= htmlspecialchars($booking['kode_booking']) ?></p>
                
                <div class="mt-4 pt-4 border-t border-yellow-200">
                    <p class="text-gray-600 mb-2">
                        Status: <strong class="text-yellow-600">MENUNGGU PEMBAYARAN</strong>
                    </p>
                    <?php if (!empty($booking['payment_type'])): ?>
                    <p class="text-sm text-gray-500">
                        Metode: <strong class="uppercase"><?= $booking['payment_type'] ?></strong>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="space-y-3 mb-8">
                <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Jika Anda sudah melakukan pembayaran, mohon tunggu beberapa saat atau klik tombol "Cek Status" di bawah.
                    </p>
                </div>
            </div>
            
            <div class="space-y-3">
                <button onclick="checkPaymentStatus()" class="w-full bg-accent hover:bg-orange-600 text-white font-bold py-4 rounded-lg transition duration-300">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Cek Status Pembayaran
                </button>
                
                <a href="payment.php?booking=<?= $booking['kode_booking'] ?>" class="block w-full bg-primary hover:bg-secondary text-white font-bold py-4 rounded-lg transition duration-300">
                    <i class="fas fa-credit-card mr-2"></i>
                    Lanjutkan Pembayaran
                </a>
                
                <a href="riwayat.php" class="block w-full border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-4 rounded-lg transition duration-300">
                    <i class="fas fa-history mr-2"></i>
                    Lihat Riwayat Pemesanan
                </a>
            </div>
        </div>
        
        <script>
            function checkPaymentStatus() {
                const button = event.target;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengecek...';
                
                fetch('api/check_payment.php?order_id=<?= $order_id ?>&format=json')
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.payment_status === 'lunas') {
                            alert('✅ Pembayaran berhasil!');
                            location.reload();
                        } else {
                            alert('⏳ Pembayaran masih pending. Silakan selesaikan pembayaran Anda.');
                            button.disabled = false;
                            button.innerHTML = '<i class="fas fa-sync-alt mr-2"></i>Cek Status Pembayaran';
                        }
                    })
                    .catch(err => {
                        alert('Gagal mengecek status. Silakan coba lagi.');
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-sync-alt mr-2"></i>Cek Status Pembayaran';
                    });
            }
            
            // Auto-refresh setelah 15 detik
            setTimeout(function() {
                location.reload();
            }, 15000);
        </script>
        
    <?php else: ?>
        <!-- ❌ PEMBAYARAN GAGAL/BATAL -->
        <div class="bg-white rounded-2xl shadow-2xl p-12 max-w-2xl w-full text-center">
            <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-times-circle text-6xl text-red-500"></i>
            </div>
            
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Pembayaran Gagal</h1>
            <p class="text-xl text-gray-600 mb-8">Silakan coba lagi</p>
            
            <div class="bg-red-50 rounded-xl p-6 mb-8">
                <p class="text-gray-700 mb-2">Kode Booking:</p>
                <p class="text-3xl font-bold text-red-600"><?= htmlspecialchars($booking['kode_booking']) ?></p>
                <p class="text-sm text-gray-500 mt-2">Status: <strong class="uppercase"><?= $status ?></strong></p>
            </div>
            
            <div class="space-y-3">
                <a href="payment.php?booking=<?= $booking['kode_booking'] ?>" class="block w-full bg-accent hover:bg-orange-600 text-white font-bold py-4 rounded-lg transition duration-300">
                    <i class="fas fa-redo mr-2"></i>
                    Coba Bayar Lagi
                </a>
                
                <a href="riwayat.php" class="block w-full border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-4 rounded-lg transition duration-300">
                    <i class="fas fa-history mr-2"></i>
                    Lihat Riwayat Pemesanan
                </a>
            </div>
        </div>
    <?php endif; ?>
    
</body>
</html>