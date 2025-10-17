<?php
require_once 'config.php';

if (!isset($_GET['booking'])) {
    redirect('index.php');
}

$kode_booking = $conn->real_escape_string($_GET['booking']);

// Ambil data pemesanan
$sql = "SELECT p.*, pe.maskapai, pe.kode_penerbangan, pe.asal, pe.tujuan,
        DATE_FORMAT(pe.tanggal, '%d %M %Y') as tanggal
        FROM pemesanan p
        JOIN penerbangan pe ON p.id_penerbangan = pe.id
        WHERE p.kode_booking = '$kode_booking' AND p.status = 'pending'";

$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    redirect('index.php');
}

$booking = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - SkyBooking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Midtrans Snap JS -->
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?= MIDTRANS_CLIENT_KEY ?>"></script>
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
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-gradient-to-r from-primary to-secondary shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="index.php" class="text-white text-2xl font-bold flex items-center">
                    <i class="fas fa-plane-departure mr-2"></i>SkyBooking
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-12">
        <div class="max-w-4xl mx-auto">
            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-center">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="ml-2 font-semibold text-green-600">Detail Pemesanan</span>
                    </div>
                    <div class="w-24 h-1 bg-primary mx-4"></div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-bold">2</div>
                        <span class="ml-2 font-semibold text-primary">Pembayaran</span>
                    </div>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-4xl text-green-500"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">Pemesanan Berhasil!</h2>
                    <p class="text-gray-600">Kode Booking: <span class="font-bold text-primary text-2xl"><?= $booking['kode_booking'] ?></span></p>
                </div>

                <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg p-6 mb-6">
                    <h3 class="font-bold text-gray-800 mb-4 text-xl">Detail Penerbangan</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Maskapai</p>
                            <p class="font-bold"><?= $booking['maskapai'] ?> (<?= $booking['kode_penerbangan'] ?>)</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Tanggal Keberangkatan</p>
                            <p class="font-bold"><?= $booking['tanggal'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Rute</p>
                            <p class="font-bold"><?= $booking['asal'] ?> → <?= $booking['tujuan'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Jumlah Penumpang</p>
                            <p class="font-bold"><?= $booking['jumlah_penumpang'] ?> Orang</p>
                        </div>
                    </div>
                </div>

                <div class="bg-accent bg-opacity-10 rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-600 mb-1">Total yang harus dibayar:</p>
                            <p class="text-4xl font-bold text-accent"><?= formatRupiah($booking['total_harga']) ?></p>
                        </div>
                        <div class="text-right">
                            <i class="fas fa-credit-card text-6xl text-accent opacity-20"></i>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <button id="payButton" class="w-full bg-accent hover:bg-orange-600 text-white font-bold py-4 rounded-lg transition duration-300 flex items-center justify-center text-lg">
                        <i class="fas fa-credit-card mr-3"></i>
                        Bayar Sekarang
                    </button>
                    
                    <button onclick="window.location.href='riwayat.php'" class="w-full border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-4 rounded-lg transition duration-300">
                        <i class="fas fa-history mr-2"></i>
                        Bayar Nanti (Lihat Riwayat)
                    </button>
                </div>

                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm text-gray-600 flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mr-2 mt-1"></i>
                        <span>Anda dapat membayar menggunakan: <strong>Credit Card, Virtual Account (BCA, BNI, BRI, Permata), GoPay, QRIS, ShopeePay</strong></span>
                    </p>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h3 class="font-bold text-gray-800 mb-4 text-xl">Metode Pembayaran yang Tersedia</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="border rounded-lg p-4 text-center hover:border-primary transition">
                        <i class="fas fa-credit-card text-3xl text-primary mb-2"></i>
                        <p class="text-sm font-semibold">Credit Card</p>
                    </div>
                    <div class="border rounded-lg p-4 text-center hover:border-primary transition">
                        <i class="fas fa-university text-3xl text-blue-600 mb-2"></i>
                        <p class="text-sm font-semibold">Virtual Account</p>
                    </div>
                    <div class="border rounded-lg p-4 text-center hover:border-primary transition">
                        <i class="fas fa-wallet text-3xl text-green-600 mb-2"></i>
                        <p class="text-sm font-semibold">E-Wallet</p>
                    </div>
                    <div class="border rounded-lg p-4 text-center hover:border-primary transition">
                        <i class="fas fa-qrcode text-3xl text-purple-600 mb-2"></i>
                        <p class="text-sm font-semibold">QRIS</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const payButton = document.getElementById('payButton');
        
        payButton.addEventListener('click', async function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>Memproses...';
            
            try {
                const formData = new FormData();
                formData.append('id_pemesanan', '<?= $booking['id'] ?>');
                formData.append('kode_booking', '<?= $booking['kode_booking'] ?>');
                formData.append('nama', '<?= $booking['nama_pemesan'] ?>');
                formData.append('email', '<?= $booking['email'] ?>');
                formData.append('phone', '<?= $booking['no_hp'] ?>');
                formData.append('amount', '<?= $booking['total_harga'] ?>');
                
                const response = await fetch('api/get_payment_token.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.token) {
                    // Panggil Midtrans Snap
                    snap.pay(data.token, {
                        onSuccess: function(result) {
                            alert('Pembayaran berhasil!');
                            window.location.href = 'payment_success.php?order_id=' + result.order_id;
                        },
                        onPending: function(result) {
                            alert('Menunggu pembayaran...');
                            window.location.href = 'riwayat.php';
                        },
                        onError: function(result) {
                            alert('Pembayaran gagal!');
                            payButton.disabled = false;
                            payButton.innerHTML = '<i class="fas fa-credit-card mr-3"></i>Bayar Sekarang';
                        },
                        onClose: function() {
                            payButton.disabled = false;
                            payButton.innerHTML = '<i class="fas fa-credit-card mr-3"></i>Bayar Sekarang';
                        }
                    });
                } else {
                    alert(data.message || 'Gagal memproses pembayaran');
                    payButton.disabled = false;
                    payButton.innerHTML = '<i class="fas fa-credit-card mr-3"></i>Bayar Sekarang';
                }
            } catch (error) {
                alert('Terjadi kesalahan. Silakan coba lagi.');
                payButton.disabled = false;
                payButton.innerHTML = '<i class="fas fa-credit-card mr-3"></i>Bayar Sekarang';
            }
        });
    </script>
</body>
</html>