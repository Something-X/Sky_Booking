<?php
require_once 'config.php';

if (!isset($_GET['booking'])) {
    redirect('index.php');
}

$kode_booking = $conn->real_escape_string($_GET['booking']);

// ========== Check booking type ==========
$sql_check = "SELECT tipe_booking, status FROM pemesanan WHERE kode_booking = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $kode_booking);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    redirect('index.php');
}

$booking_info = $result_check->fetch_assoc();
$tipe_booking = $booking_info['tipe_booking'];
$status = $booking_info['status'];

// Redirect if already paid
if ($status === 'lunas') {
    redirect('riwayat.php');
}

$isRoundTrip = ($tipe_booking === 'departure');

if ($isRoundTrip) {
    // ========== ROUND-TRIP BOOKING ==========
    $sql = "SELECT 
                p1.id as departure_id,
                p1.kode_booking as departure_booking_code,
                p1.nama_pemesan,
                p1.email,
                p1.no_hp,
                p1.jumlah_penumpang,
                p1.total_harga,
                p1.status,
                p1.created_at,
                
                pn1.maskapai as departure_maskapai,
                pn1.kode_penerbangan as departure_flight_code,
                pn1.asal as departure_origin,
                pn1.tujuan as departure_destination,
                DATE_FORMAT(pn1.tanggal, '%d %M %Y') as departure_date,
                pn1.jam_berangkat as departure_time,
                pn1.jam_tiba as departure_arrival,
                pn1.harga as departure_price,
                
                p2.id as return_id,
                p2.kode_booking as return_booking_code,
                pn2.maskapai as return_maskapai,
                pn2.kode_penerbangan as return_flight_code,
                pn2.asal as return_origin,
                pn2.tujuan as return_destination,
                DATE_FORMAT(pn2.tanggal, '%d %M %Y') as return_date,
                pn2.jam_berangkat as return_time,
                pn2.jam_tiba as return_arrival,
                pn2.harga as return_price
                
            FROM pemesanan p1
            LEFT JOIN penerbangan pn1 ON p1.id_penerbangan = pn1.id
            LEFT JOIN pemesanan p2 ON p1.kode_booking = p2.linked_booking_code
            LEFT JOIN penerbangan pn2 ON p2.id_penerbangan = pn2.id
            WHERE p1.kode_booking = ? AND p1.tipe_booking = 'departure'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kode_booking);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        redirect('index.php');
    }
    
    $booking = $result->fetch_assoc();
    
} else {
    // ========== ONE-WAY BOOKING ==========
    $sql = "SELECT p.*, pe.maskapai, pe.kode_penerbangan, pe.asal, pe.tujuan,
            DATE_FORMAT(pe.tanggal, '%d %M %Y') as tanggal,
            pe.jam_berangkat, pe.jam_tiba
            FROM pemesanan p
            JOIN penerbangan pe ON p.id_penerbangan = pe.id
            WHERE p.kode_booking = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kode_booking);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        redirect('index.php');
    }
    
    $booking = $result->fetch_assoc();
}
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

            <!-- Round-Trip Badge -->
            <?php if ($isRoundTrip): ?>
            <div class="mb-6 text-center">
                <span class="inline-flex items-center px-6 py-3 rounded-full text-white font-bold" style="background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);">
                    <i class="fas fa-exchange-alt mr-2"></i>
                    Round-Trip Booking
                </span>
            </div>
            <?php endif; ?>

            <!-- Payment Info -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-4xl text-green-500"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">Pemesanan Berhasil!</h2>
                    <p class="text-gray-600">Kode Booking: 
                        <span class="font-bold text-primary text-2xl">
                            <?= $isRoundTrip ? $booking['departure_booking_code'] : $booking['kode_booking'] ?>
                        </span>
                    </p>
                </div>

                <?php if ($isRoundTrip): ?>
                    <!-- ========== ROUND-TRIP FLIGHT DETAILS ========== -->
                    
                    <!-- Departure Flight -->
                    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-lg p-6 mb-4 border-l-4 border-blue-500">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-plane-departure text-2xl text-blue-600 mr-3"></i>
                            <h3 class="font-bold text-gray-800 text-xl">Penerbangan Berangkat</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Maskapai</p>
                                <p class="font-bold"><?= $booking['departure_maskapai'] ?> (<?= $booking['departure_flight_code'] ?>)</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Tanggal Keberangkatan</p>
                                <p class="font-bold"><?= $booking['departure_date'] ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Rute</p>
                                <p class="font-bold"><?= $booking['departure_origin'] ?> → <?= $booking['departure_destination'] ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Waktu</p>
                                <p class="font-bold"><?= date('H:i', strtotime($booking['departure_time'])) ?> - <?= date('H:i', strtotime($booking['departure_arrival'])) ?></p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-blue-200">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Harga per orang</span>
                                <span class="font-bold text-blue-600"><?= formatRupiah($booking['departure_price']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Return Flight -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-6 mb-6 border-l-4 border-purple-500">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-plane-arrival text-2xl text-purple-600 mr-3"></i>
                            <h3 class="font-bold text-gray-800 text-xl">Penerbangan Kembali</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Maskapai</p>
                                <p class="font-bold"><?= $booking['return_maskapai'] ?> (<?= $booking['return_flight_code'] ?>)</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Tanggal Kepulangan</p>
                                <p class="font-bold"><?= $booking['return_date'] ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Rute</p>
                                <p class="font-bold"><?= $booking['return_origin'] ?> → <?= $booking['return_destination'] ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Waktu</p>
                                <p class="font-bold"><?= date('H:i', strtotime($booking['return_time'])) ?> - <?= date('H:i', strtotime($booking['return_arrival'])) ?></p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-purple-200">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Harga per orang</span>
                                <span class="font-bold text-purple-600"><?= formatRupiah($booking['return_price']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Passengers Info -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Jumlah Penumpang</span>
                            <span class="font-bold"><?= $booking['jumlah_penumpang'] ?> Orang</span>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- ========== ONE-WAY FLIGHT DETAILS ========== -->
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
                <?php endif; ?>

                <!-- Total Payment -->
                <div class="bg-accent bg-opacity-10 rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-600 mb-1">Total yang harus dibayar:</p>
                            <p class="text-4xl font-bold text-accent"><?= formatRupiah($booking['total_harga']) ?></p>
                            <?php if ($isRoundTrip): ?>
                            <p class="text-sm text-gray-500 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Termasuk kedua penerbangan (pergi & pulang)
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <i class="fas fa-credit-card text-6xl text-accent opacity-20"></i>
                        </div>
                    </div>
                </div>

                <!-- Payment Buttons -->
                <div class="space-y-4">
                    <button id="payButton" 
                            data-booking-id="<?= $isRoundTrip ? $booking['departure_id'] : $booking['id'] ?>"
                            data-booking-code="<?= $isRoundTrip ? $booking['departure_booking_code'] : $booking['kode_booking'] ?>"
                            data-is-roundtrip="<?= $isRoundTrip ? '1' : '0' ?>"
                            class="w-full bg-accent hover:bg-orange-600 text-white font-bold py-4 rounded-lg transition duration-300 flex items-center justify-center text-lg">
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
            const bookingId = this.dataset.bookingId;
            const bookingCode = this.dataset.bookingCode;
            const isRoundTrip = this.dataset.isRoundtrip === '1';
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>Memproses...';
            
            try {
                const formData = new FormData();
                formData.append('id_pemesanan', bookingId);
                formData.append('kode_booking', bookingCode);
                formData.append('nama', '<?= $booking['nama_pemesan'] ?>');
                formData.append('email', '<?= $booking['email'] ?>');
                formData.append('phone', '<?= $booking['no_hp'] ?>');
                formData.append('amount', '<?= $booking['total_harga'] ?>');
                formData.append('is_roundtrip', isRoundTrip ? '1' : '0');
                
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