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
    <!-- Navbar -->
    <nav class="bg-gradient-to-r from-primary to-secondary shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="index.php" class="text-white text-2xl font-bold flex items-center">
                    <i class="fas fa-plane-departure mr-2"></i>
                    SkyBooking
                </a>
                
                <!-- Mobile menu button -->
                <button id="mobileMenuBtn" class="md:hidden text-white">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <!-- Desktop menu -->
                <div class="hidden md:flex space-x-6">
                    <a href="index.php" class="text-white hover:text-gray-200 transition">Home</a>
                    <a href="riwayat.php" class="text-white hover:text-gray-200 transition">Riwayat Pemesanan</a>
                    <?php if (isAdmin()): ?>
                        <a href="admin/dashboard.php" class="text-white hover:text-gray-200 transition">Dashboard Admin</a>
                        <a href="admin/logout.php" class="text-white hover:text-gray-200 transition">Logout</a>
                    <?php else: ?>
                        <a href="admin/login.php" class="text-white hover:text-gray-200 transition">Login Admin</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Mobile menu -->
            <div id="mobileMenu" class="hidden md:hidden pb-4">
                <a href="index.php" class="block text-white py-2 hover:text-gray-200">Home</a>
                <a href="riwayat.php" class="block text-white py-2 hover:text-gray-200">Riwayat Pemesanan</a>
                <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php" class="block text-white py-2 hover:text-gray-200">Dashboard Admin</a>
                    <a href="admin/logout.php" class="block text-white py-2 hover:text-gray-200">Logout</a>
                <?php else: ?>
                    <a href="admin/login.php" class="block text-white py-2 hover:text-gray-200">Login Admin</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

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

    <!-- Results Section -->
    <div class="container mx-auto px-4 py-12">
        <div id="loadingSpinner" class="text-center hidden">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
            <p class="mt-4 text-gray-600">Mencari penerbangan...</p>
        </div>
        <div id="resultsContainer"></div>
    </div>

    <!-- Features Section -->
    <div class="container mx-auto px-4 py-16">
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
            <p>&copy; 2025 SkyBooking. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        // Set minimum date to today
        document.getElementById('tanggal').min = new Date().toISOString().split('T')[0];

        // Search Form Handler
        document.getElementById('searchForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const loadingSpinner = document.getElementById('loadingSpinner');
            const resultsContainer = document.getElementById('resultsContainer');
            
            loadingSpinner.classList.remove('hidden');
            resultsContainer.innerHTML = '';
            
            try {
                const response = await fetch('api/search_flights.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                loadingSpinner.classList.add('hidden');
                
                if (data.success && data.data.length > 0) {
                    displayResults(data.data, formData.get('jumlah'));
                } else {
                    resultsContainer.innerHTML = `
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-400 mr-3"></i>
                                <p class="text-yellow-700">Tidak ada penerbangan yang ditemukan. Coba ubah kriteria pencarian Anda.</p>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                loadingSpinner.classList.add('hidden');
                resultsContainer.innerHTML = `
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-times-circle text-red-400 mr-3"></i>
                            <p class="text-red-700">Terjadi kesalahan. Silakan coba lagi.</p>
                        </div>
                    </div>
                `;
            }
        });

        function displayResults(flights, jumlah) {
            const container = document.getElementById('resultsContainer');
            let html = `
                <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-plane mr-3 text-primary"></i>
                    Penerbangan Tersedia (${flights.length})
                </h3>
            `;
            
            flights.forEach(flight => {
                const totalHarga = flight.harga * jumlah;
                html += `
                    <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300 p-6 mb-4 border border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                            <div class="md:col-span-2 text-center">
                                <i class="fas fa-plane-departure text-5xl text-primary mb-2"></i>
                                <div class="font-bold text-gray-800">${flight.maskapai}</div>
                                <div class="text-sm text-gray-500">${flight.kode_penerbangan}</div>
                            </div>
                            <div class="md:col-span-3">
                                <div class="text-2xl font-bold text-gray-800">${flight.jam_berangkat}</div>
                                <div class="text-gray-600">${flight.asal}</div>
                            </div>
                            <div class="md:col-span-2 text-center">
                                <i class="fas fa-arrow-right text-primary text-xl"></i>
                                <div class="text-sm text-gray-500 mt-1">Langsung</div>
                            </div>
                            <div class="md:col-span-3">
                                <div class="text-2xl font-bold text-gray-800">${flight.jam_tiba}</div>
                                <div class="text-gray-600">${flight.tujuan}</div>
                            </div>
                            <div class="md:col-span-2 text-center">
                                <div class="text-3xl font-bold text-accent">${formatRupiah(totalHarga)}</div>
                                <div class="text-sm text-gray-500">per ${jumlah} orang</div>
                                <button onclick="bookFlight(${flight.id}, ${jumlah})" 
                                        class="w-full mt-3 bg-accent hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                    <i class="fas fa-ticket-alt mr-2"></i>Pesan
                                </button>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <span class="text-sm text-gray-600">
                                <i class="fas fa-chair mr-2"></i>
                                Tersedia: ${flight.tersedia} kursi
                            </span>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function bookFlight(flightId, jumlah) {
            window.location.href = `pesan.php?id=${flightId}&jumlah=${jumlah}`;
        }
    </script>
</body>
</html>