<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkyBooking - Pemesanan Tiket Pesawat</title>
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
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="relative bg-gradient-to-r from-primary to-secondary text-white">
        <div class="absolute inset-0 bg-black opacity-30"></div>
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1600'); mix-blend-mode: overlay;"></div>
        <div class="container mx-auto px-4 py-24 relative z-10">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold mb-4">Terbang ke Mana Pun Jadi Mudah</h1>
                <p class="text-xl md:text-2xl mb-8">Pesan tiket pesawat dengan harga terbaik dan layanan terpercaya</p>
            </div>
        </div>
    </div>

    <!-- Search Card -->
    <div class="container mx-auto px-4 -mt-20 relative z-20">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-search mr-3 text-primary"></i>
                Cari Penerbangan
            </h3>
            <form id="searchForm">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Kota Asal</label>
                        <input type="text" id="asal" name="asal" placeholder="Jakarta" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Kota Tujuan</label>
                        <input type="text" id="tujuan" name="tujuan" placeholder="Surabaya"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Berangkat</label>
                        <input type="date" id="tanggal" name="tanggal"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Jumlah Penumpang</label>
                        <input type="number" id="jumlah" name="jumlah" value="1" min="1" max="10"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               required>
                    </div>
                </div>
                <button type="submit" class="w-full bg-primary hover:bg-secondary text-white font-bold py-4 rounded-lg transition duration-300 flex items-center justify-center">
                    <i class="fas fa-search mr-2"></i>
                    Cari Penerbangan
                </button>
            </form>
        </div>
    </div>

    <!-- Features Section -->
    <div class="container mx-auto px-4 my-16">
        <h2 class="text-4xl font-bold text-center text-gray-800 mb-12">Mengapa Memilih SkyBooking?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center p-6">
                <i class="fas fa-tag text-6xl text-primary mb-4"></i>
                <h4 class="text-xl font-bold text-gray-800 mb-3">Harga Terbaik</h4>
                <p class="text-gray-600">Dapatkan harga tiket termurah dengan berbagai pilihan maskapai</p>
            </div>
            <div class="text-center p-6">
                <i class="fas fa-shield-alt text-6xl text-primary mb-4"></i>
                <h4 class="text-xl font-bold text-gray-800 mb-3">Aman & Terpercaya</h4>
                <p class="text-gray-600">Transaksi Anda dijamin aman dengan sistem keamanan terbaik</p>
            </div>
            <div class="text-center p-6">
                <i class="fas fa-headset text-6xl text-primary mb-4"></i>
                <h4 class="text-xl font-bold text-gray-800 mb-3">Layanan 24/7</h4>
                <p class="text-gray-600">Customer service siap membantu Anda kapan saja</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p class="mb-0">&copy; 2025 SkyBooking. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Set minimum date to today
        document.getElementById('tanggal').min = new Date().toISOString().split('T')[0];

        // Search Form Handler
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);
            
            // Redirect to search results page
            window.location.href = 'search_results.php?' + params.toString();
        });
    </script>
</body>
</html>