<?php
require_once 'config.php';

$order_id = $_GET['order_id'] ?? '';

// Update status pemesanan menjadi lunas
if (!empty($order_id)) {
    $order_id = $conn->real_escape_string($order_id);
    $conn->query("UPDATE pemesanan SET status = 'lunas' WHERE kode_booking = '$order_id'");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - SkyBooking</title>
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
    <div class="bg-white rounded-2xl shadow-2xl p-12 max-w-2xl w-full text-center">
        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 animate-bounce">
            <i class="fas fa-check-circle text-6xl text-green-500"></i>
        </div>
        
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Pembayaran Berhasil!</h1>
        <p class="text-xl text-gray-600 mb-8">Terima kasih telah melakukan pembayaran</p>
        
        <div class="bg-green-50 rounded-xl p-6 mb-8">
            <p class="text-gray-700 mb-2">Kode Booking Anda:</p>
            <p class="text-3xl font-bold text-primary"><?= htmlspecialchars($order_id) ?></p>
        </div>
        
        <div class="space-y-3">
            <p class="text-gray-600">
                <i class="fas fa-envelope text-primary mr-2"></i>
                E-ticket telah dikirim ke email Anda
            </p>
            <p class="text-gray-600">
                <i class="fas fa-mobile-alt text-primary mr-2"></i>
                Simpan kode booking untuk check-in
            </p>
        </div>
        
        <div class="mt-8 space-y-3">
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
</body>
</html>