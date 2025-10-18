<?php
require_once 'config.php';

// Require login
requireLogin();

if (!isset($_GET['id']) || !isset($_GET['jumlah'])) {
    redirect('index.php');
}

$id_penerbangan = (int)$_GET['id'];
$jumlah_penumpang = (int)$_GET['jumlah'];

$sql = "SELECT * FROM penerbangan WHERE id = $id_penerbangan AND status = 'aktif'";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    redirect('index.php');
}

$flight = $result->fetch_assoc();
$total_harga = $flight['harga'] * $jumlah_penumpang;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Tiket - SkyBooking</title>
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
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-gradient-to-r from-primary to-secondary shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="index.php" class="text-white text-2xl font-bold flex items-center">
                    <i class="fas fa-plane-departure mr-2"></i>SkyBooking
                </a>
                <div class="flex space-x-6">
                    <a href="index.php" class="text-white hover:text-gray-200 transition">Kembali</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-12">
        <div class="max-w-6xl mx-auto">
            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-center">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-bold">1</div>
                        <span class="ml-2 font-semibold text-primary">Detail Pemesanan</span>
                    </div>
                    <div class="w-24 h-1 bg-gray-300 mx-4"></div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 font-bold">2</div>
                        <span class="ml-2 text-gray-500">Konfirmasi</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form Pemesanan -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-lg p-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-user-edit mr-3 text-primary"></i>
                            Informasi Pemesan
                        </h2>
                        
                        <form id="bookingForm">
                            <input type="hidden" name="id_penerbangan" value="<?= $id_penerbangan ?>">
                            <input type="hidden" name="jumlah_penumpang" value="<?= $jumlah_penumpang ?>">
                            
                            <div class="space-y-4 mb-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap Pemesan *</label>
                                    <input type="text" name="nama_pemesan" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                           placeholder="Nama sesuai KTP">
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                                        <input type="email" name="email" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                               placeholder="email@example.com">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nomor HP *</label>
                                        <input type="tel" name="no_hp" required pattern="[0-9]{10,13}"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                               placeholder="08xxxxxxxxxx">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-6">

                            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-users mr-3 text-primary"></i>
                                Data Penumpang (<?= $jumlah_penumpang ?> Orang)
                            </h3>

                            <div id="penumpangContainer" class="space-y-6">
                                <?php for ($i = 1; $i <= $jumlah_penumpang; $i++): ?>
                                <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                                    <h4 class="font-bold text-gray-700 mb-4">Penumpang <?= $i ?></h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap *</label>
                                            <input type="text" name="penumpang_nama[]" required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                                   placeholder="Nama sesuai KTP/Paspor">
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Jenis Kelamin *</label>
                                                <select name="penumpang_gender[]" required
                                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                                    <option value="">Pilih</option>
                                                    <option value="Laki-laki">Laki-laki</option>
                                                    <option value="Perempuan">Perempuan</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Lahir *</label>
                                                <input type="date" name="penumpang_tgl_lahir[]" required
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">NIK/No. Paspor</label>
                                            <input type="text" name="penumpang_nik[]"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                                   placeholder="Opsional">
                                        </div>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>

                            <div class="mt-8 flex items-start">
                                <input type="checkbox" id="agree" required class="mt-1 mr-3 w-5 h-5 text-primary">
                                <label for="agree" class="text-sm text-gray-600">
                                    Saya menyetujui <a href="#" class="text-primary hover:underline">syarat dan ketentuan</a> yang berlaku
                                </label>
                            </div>

                            <div class="mt-8">
                                <button type="submit" class="w-full bg-primary hover:bg-secondary text-white font-bold py-4 rounded-lg transition duration-300 flex items-center justify-center">
                                    <i class="fas fa-arrow-right mr-2"></i>
                                    Lanjutkan ke Pembayaran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Summary Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-lg p-6 sticky top-4">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Ringkasan Pemesanan</h3>
                        
                        <div class="bg-gradient-to-r from-primary to-secondary text-white rounded-lg p-4 mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm opacity-90">Maskapai</span>
                                <span class="font-bold"><?= $flight['maskapai'] ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm opacity-90">Kode</span>
                                <span class="font-bold"><?= $flight['kode_penerbangan'] ?></span>
                            </div>
                        </div>

                        <div class="space-y-3 mb-6">
                            <div class="flex items-start">
                                <i class="fas fa-calendar text-primary mt-1 mr-3"></i>
                                <div class="flex-1">
                                    <div class="text-sm text-gray-500">Tanggal</div>
                                    <div class="font-semibold"><?= formatTanggal($flight['tanggal']) ?></div>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <i class="fas fa-map-marker-alt text-primary mt-1 mr-3"></i>
                                <div class="flex-1">
                                    <div class="text-sm text-gray-500">Rute</div>
                                    <div class="font-semibold"><?= $flight['asal'] ?></div>
                                    <i class="fas fa-arrow-down text-gray-400 my-1"></i>
                                    <div class="font-semibold"><?= $flight['tujuan'] ?></div>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <i class="fas fa-clock text-primary mt-1 mr-3"></i>
                                <div class="flex-1">
                                    <div class="text-sm text-gray-500">Waktu</div>
                                    <div class="font-semibold">
                                        <?= date('H:i', strtotime($flight['jam_berangkat'])) ?> - 
                                        <?= date('H:i', strtotime($flight['jam_tiba'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <div class="space-y-3">
                            <div class="flex justify-between text-gray-600">
                                <span>Harga per orang</span>
                                <span><?= formatRupiah($flight['harga']) ?></span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Jumlah penumpang</span>
                                <span>× <?= $jumlah_penumpang ?></span>
                            </div>
                            <hr>
                            <div class="flex justify-between items-center text-xl font-bold text-gray-800">
                                <span>Total</span>
                                <span class="text-accent"><?= formatRupiah($total_harga) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi -->
    <div id="confirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-8 transform transition-all">
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-3xl text-green-500"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Pemesanan Berhasil!</h3>
                <p class="text-gray-600 mb-2">Kode Booking Anda:</p>
                <p id="kodeBooking" class="text-3xl font-bold text-primary mb-6"></p>
                <p class="text-sm text-gray-500 mb-6">E-ticket telah dikirim ke email Anda</p>
                <div class="space-y-3">
                    <a href="riwayat.php" class="block w-full bg-primary hover:bg-secondary text-white font-bold py-3 rounded-lg transition duration-300">
                        Lihat Riwayat Pemesanan
                    </a>
                    <a href="index.php" class="block w-full border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-3 rounded-lg transition duration-300">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('bookingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
            
            try {
                const response = await fetch('api/process_booking.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Redirect ke halaman pembayaran
                    window.location.href = 'payment.php?booking=' + data.kode_booking;
                } else {
                    alert(data.message || 'Terjadi kesalahan saat memproses pemesanan');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-arrow-right mr-2"></i>Lanjutkan ke Pembayaran';
                }
            } catch (error) {
                alert('Terjadi kesalahan. Silakan coba lagi.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-arrow-right mr-2"></i>Lanjutkan ke Pembayaran';
            }
        });
    </script>
</body>
</html>