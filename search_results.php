<?php 
require_once 'config.php';
$user = getUserData();

// Get search params
$asal = $_GET['asal'] ?? '';
$tujuan = $_GET['tujuan'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';
$jumlah = (int)($_GET['jumlah'] ?? 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian - SkyBooking</title>
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
    <style>
        .flight-detail {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .flight-detail.active {
            max-height: 500px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/navbar.php'; ?>

    <!-- Search Summary -->
    <div class="bg-white shadow-md py-4">
        <div class="container mx-auto px-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-6 text-sm">
                    <div>
                        <span class="text-gray-500">Dari:</span>
                        <span class="font-bold text-gray-800 ml-2"><?= htmlspecialchars($asal) ?></span>
                    </div>
                    <i class="fas fa-arrow-right text-primary"></i>
                    <div>
                        <span class="text-gray-500">Ke:</span>
                        <span class="font-bold text-gray-800 ml-2"><?= htmlspecialchars($tujuan) ?></span>
                    </div>
                    <div class="border-l pl-6">
                        <span class="text-gray-500">Tanggal:</span>
                        <span class="font-bold text-gray-800 ml-2"><?= date('d M Y', strtotime($tanggal)) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500">Penumpang:</span>
                        <span class="font-bold text-gray-800 ml-2"><?= $jumlah ?> orang</span>
                    </div>
                </div>
                <button onclick="window.location.href='index.php'" class="text-primary hover:text-secondary font-semibold">
                    <i class="fas fa-edit mr-2"></i>Ubah Pencarian
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar Filter -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6 sticky top-4">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-800">
                            <i class="fas fa-filter mr-2 text-primary"></i>Filter
                        </h3>
                        <button id="resetFilter" class="text-sm text-primary hover:underline">Reset</button>
                    </div>

                    <!-- Airline Filter -->
                    <div class="mb-6">
                        <h4 class="font-bold text-gray-800 mb-3 text-sm">Maskapai</h4>
                        <div id="airlineFilters" class="space-y-2"></div>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-6">
                        <h4 class="font-bold text-gray-800 mb-3 text-sm">Kisaran Harga</h4>
                        <div class="space-y-2">
                            <label class="flex items-center cursor-pointer text-sm">
                                <input type="radio" name="priceRange" class="filter-price mr-2" value="0-1000000" checked>
                                <span class="text-gray-700">< Rp 1 Juta</span>
                            </label>
                            <label class="flex items-center cursor-pointer text-sm">
                                <input type="radio" name="priceRange" class="filter-price mr-2" value="1000000-2000000">
                                <span class="text-gray-700">Rp 1-2 Juta</span>
                            </label>
                            <label class="flex items-center cursor-pointer text-sm">
                                <input type="radio" name="priceRange" class="filter-price mr-2" value="2000000-99999999">
                                <span class="text-gray-700">> Rp 2 Juta</span>
                            </label>
                        </div>
                    </div>

                    <!-- Time Filter -->
                    <div class="mb-6">
                        <h4 class="font-bold text-gray-800 mb-3 text-sm">Waktu Keberangkatan</h4>
                        <div class="space-y-2">
                            <label class="flex items-center cursor-pointer text-sm">
                                <input type="checkbox" class="filter-time mr-2" value="00:00-06:00">
                                <span class="text-gray-700">🌙 Dini Hari 00:00-06:00</span>
                            </label>
                            <label class="flex items-center cursor-pointer text-sm">
                                <input type="checkbox" class="filter-time mr-2" value="06:00-12:00">
                                <span class="text-gray-700">🌅 Pagi 06:00-12:00</span>
                            </label>
                            <label class="flex items-center cursor-pointer text-sm">
                                <input type="checkbox" class="filter-time mr-2" value="12:00-18:00">
                                <span class="text-gray-700">☀️ Siang 12:00-18:00</span>
                            </label>
                            <label class="flex items-center cursor-pointer text-sm">
                                <input type="checkbox" class="filter-time mr-2" value="18:00-24:00">
                                <span class="text-gray-700">🌃 Malam 18:00-24:00</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flight Results -->
            <div class="lg:col-span-3">
                <!-- Header -->
                <div class="bg-white rounded-xl shadow-md p-4 mb-4">
                    <div class="flex flex-wrap justify-between items-center gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-plane text-primary mr-2"></i>
                                <span id="resultCount">0</span> Penerbangan Tersedia
                            </h2>
                        </div>
                        <select id="sortSelect" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary text-sm">
                            <option value="price-asc">Harga Terendah</option>
                            <option value="price-desc">Harga Tertinggi</option>
                            <option value="time-asc">Keberangkatan Paling Awal</option>
                            <option value="time-desc">Keberangkatan Paling Akhir</option>
                        </select>
                    </div>
                </div>

                <!-- Loading -->
                <div id="loadingSpinner" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-4 border-primary"></div>
                    <p class="mt-4 text-gray-600 font-semibold">Mencari penerbangan terbaik...</p>
                </div>

                <!-- Results Container -->
                <div id="resultsContainer"></div>
            </div>
        </div>
    </div>

    <script>
        let allFlights = [];
        const jumlah = <?= $jumlah ?>;

        // Load flights on page load
        window.addEventListener('DOMContentLoaded', function() {
            searchFlights();
        });

        async function searchFlights() {
            const formData = new FormData();
            formData.append('asal', '<?= $asal ?>');
            formData.append('tujuan', '<?= $tujuan ?>');
            formData.append('tanggal', '<?= $tanggal ?>');
            
            try {
                const response = await fetch('api/search_flights.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                document.getElementById('loadingSpinner').classList.add('hidden');
                
                if (data.success && data.data.length > 0) {
                    allFlights = data.data;
                    populateAirlineFilters(allFlights);
                    applyFilters();
                } else {
                    showNoResults();
                }
            } catch (error) {
                document.getElementById('loadingSpinner').classList.add('hidden');
                showError();
            }
        }

        function populateAirlineFilters(flights) {
            const airlines = [...new Set(flights.map(f => f.maskapai))];
            const container = document.getElementById('airlineFilters');
            container.innerHTML = airlines.map(airline => `
                <label class="flex items-center cursor-pointer text-sm">
                    <input type="checkbox" class="filter-airline mr-2" value="${airline}" checked>
                    <span class="text-gray-700">${airline}</span>
                </label>
            `).join('');
            
            document.querySelectorAll('.filter-airline').forEach(el => {
                el.addEventListener('change', applyFilters);
            });
        }

        function applyFilters() {
            const selectedAirlines = Array.from(document.querySelectorAll('.filter-airline:checked')).map(el => el.value);
            const priceRange = document.querySelector('.filter-price:checked')?.value;
            let minPrice = 0, maxPrice = 99999999;
            if (priceRange) {
                [minPrice, maxPrice] = priceRange.split('-').map(Number);
            }
            
            const selectedTimes = Array.from(document.querySelectorAll('.filter-time:checked')).map(el => el.value);
            
            let filtered = allFlights.filter(flight => {
                if (!selectedAirlines.includes(flight.maskapai)) return false;
                
                const price = flight.harga * jumlah;
                if (price < minPrice || price > maxPrice) return false;
                
                if (selectedTimes.length > 0) {
                    const time = flight.jam_berangkat;
                    const [hours] = time.split(':').map(Number);
                    const timeInMinutes = hours * 60;
                    
                    const matchesTime = selectedTimes.some(range => {
                        const [start, end] = range.split('-').map(t => {
                            const [h, m] = t.split(':').map(Number);
                            return h * 60 + (m || 0);
                        });
                        return timeInMinutes >= start && timeInMinutes < end;
                    });
                    
                    if (!matchesTime) return false;
                }
                
                return true;
            });
            
            displayResults(filtered);
        }

        function displayResults(flights) {
            const container = document.getElementById('resultsContainer');
            document.getElementById('resultCount').textContent = flights.length;
            
            if (flights.length === 0) {
                container.innerHTML = `
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-filter text-yellow-400 text-3xl mr-4"></i>
                            <div>
                                <p class="font-bold text-yellow-700 text-lg">Tidak ada hasil</p>
                                <p class="text-yellow-600 text-sm mt-1">Coba ubah filter atau kriteria pencarian Anda</p>
                            </div>
                        </div>
                    </div>
                `;
                return;
            }
            
            let html = '';
            flights.forEach((flight, index) => {
                const totalHarga = flight.harga * jumlah;
                html += createFlightCard(flight, totalHarga, index);
            });
            
            container.innerHTML = html;
            
            // Add toggle listeners
            document.querySelectorAll('.toggle-detail').forEach(btn => {
                btn.addEventListener('click', function() {
                    const detailId = this.dataset.detail;
                    const detail = document.getElementById(detailId);
                    const icon = this.querySelector('.toggle-icon');
                    
                    detail.classList.toggle('active');
                    icon.classList.toggle('fa-chevron-down');
                    icon.classList.toggle('fa-chevron-up');
                });
            });
        }

        function createFlightCard(flight, totalHarga, index) {
            return `
                <div class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 mb-4 overflow-hidden border border-gray-200">
                    <!-- Main Content -->
                    <div class="p-6">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <!-- Airline Info -->
                            <div class="flex-shrink-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-plane text-primary text-xl"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-800">${flight.maskapai}</div>
                                        <div class="text-xs text-gray-500">${flight.kode_penerbangan}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Flight Time -->
                            <div class="flex items-center gap-6 flex-1">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-800">${flight.jam_berangkat}</div>
                                    <div class="text-sm text-gray-600">${flight.asal.split('(')[1]?.replace(')', '') || flight.asal}</div>
                                </div>
                                
                                <div class="flex-1 relative">
                                    <div class="border-t-2 border-gray-300"></div>
                                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white px-2">
                                        <i class="fas fa-plane text-primary"></i>
                                    </div>
                                    <div class="text-center text-xs text-gray-500 mt-10">Langsung</div>
                                </div>
                                
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-800">${flight.jam_tiba}</div>
                                    <div class="text-sm text-gray-600">${flight.tujuan.split('(')[1]?.replace(')', '') || flight.tujuan}</div>
                                </div>
                            </div>

                            <!-- Price & Book -->
                            <div class="text-right flex-shrink-0">
                                <div class="mb-1">
                                    <span class="text-xs text-gray-500">Mulai dari</span>
                                </div>
                                <div class="text-2xl font-bold text-accent mb-1">${formatRupiah(totalHarga)}</div>
                                <div class="text-xs text-gray-500 mb-3">/per ${jumlah} orang</div>
                                <button onclick="bookFlight(${flight.id}, ${jumlah})" 
                                        class="w-full bg-primary hover:bg-secondary text-white font-bold py-2 px-6 rounded-lg transition duration-300">
                                    Pesan
                                </button>
                            </div>
                        </div>

                        <!-- Detail Toggle Button -->
                        <button class="toggle-detail mt-4 w-full text-primary hover:text-secondary font-semibold text-sm flex items-center justify-center gap-2" data-detail="detail-${index}">
                            <span>Flight Details</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </button>
                    </div>

                    <!-- Flight Details (Collapsible) -->
                    <div id="detail-${index}" class="flight-detail bg-gray-50 border-t border-gray-200">
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h5 class="font-bold text-gray-800 mb-3 flex items-center">
                                        <i class="fas fa-plane-departure text-primary mr-2"></i>
                                        Keberangkatan
                                    </h5>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Bandara:</span>
                                            <span class="font-semibold">${flight.asal}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Waktu:</span>
                                            <span class="font-semibold">${flight.jam_berangkat}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Tanggal:</span>
                                            <span class="font-semibold"><?= date('d M Y', strtotime($tanggal)) ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <h5 class="font-bold text-gray-800 mb-3 flex items-center">
                                        <i class="fas fa-plane-arrival text-primary mr-2"></i>
                                        Kedatangan
                                    </h5>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Bandara:</span>
                                            <span class="font-semibold">${flight.tujuan}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Waktu:</span>
                                            <span class="font-semibold">${flight.jam_tiba}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Kursi Tersedia:</span>
                                            <span class="font-semibold text-green-600">${flight.tersedia} kursi</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                                <div class="flex items-start gap-2 text-sm text-gray-700">
                                    <i class="fas fa-info-circle text-primary mt-0.5"></i>
                                    <div>
                                        <p class="font-semibold mb-1">Informasi Tambahan:</p>
                                        <ul class="list-disc list-inside space-y-1 text-xs">
                                            <li>Bagasi kabin 7kg</li>
                                            <li>Bagasi check-in 20kg</li>
                                            <li>Refundable sesuai ketentuan maskapai</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function showNoResults() {
            document.getElementById('resultsContainer').innerHTML = `
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-8 rounded-lg text-center">
                    <i class="fas fa-plane-slash text-yellow-400 text-6xl mb-4"></i>
                    <h3 class="text-xl font-bold text-yellow-700 mb-2">Tidak Ada Penerbangan</h3>
                    <p class="text-yellow-600 mb-4">Maaf, tidak ada penerbangan yang sesuai dengan pencarian Anda</p>
                    <button onclick="window.location.href='index.php'" class="bg-primary hover:bg-secondary text-white font-bold py-2 px-6 rounded-lg">
                        Ubah Pencarian
                    </button>
                </div>
            `;
        }

        function showError() {
            document.getElementById('resultsContainer').innerHTML = `
                <div class="bg-red-50 border-l-4 border-red-400 p-8 rounded-lg text-center">
                    <i class="fas fa-exclamation-triangle text-red-400 text-6xl mb-4"></i>
                    <h3 class="text-xl font-bold text-red-700 mb-2">Terjadi Kesalahan</h3>
                    <p class="text-red-600">Silakan coba lagi atau hubungi customer service</p>
                </div>
            `;
        }

        // Filter event listeners
        document.querySelectorAll('.filter-price, .filter-time').forEach(el => {
            el.addEventListener('change', applyFilters);
        });

        // Reset filter
        document.getElementById('resetFilter').addEventListener('click', function() {
            document.querySelectorAll('.filter-airline').forEach(el => el.checked = true);
            document.querySelectorAll('.filter-price')[0].checked = true;
            document.querySelectorAll('.filter-time').forEach(el => el.checked = false);
            applyFilters();
        });

        // Sort handler
        document.getElementById('sortSelect').addEventListener('change', function() {
            const value = this.value;
            const sorted = [...allFlights];
            
            switch(value) {
                case 'price-asc':
                    sorted.sort((a, b) => a.harga - b.harga);
                    break;
                case 'price-desc':
                    sorted.sort((a, b) => b.harga - a.harga);
                    break;
                case 'time-asc':
                    sorted.sort((a, b) => a.jam_berangkat.localeCompare(b.jam_berangkat));
                    break;
                case 'time-desc':
                    sorted.sort((a, b) => b.jam_berangkat.localeCompare(a.jam_berangkat));
                    break;
            }
            
            allFlights = sorted;
            applyFilters();
        });

        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function bookFlight(flightId, jumlah) {
            <?php if (isLoggedIn()): ?>
                window.location.href = `pesan.php?id=${flightId}&jumlah=${jumlah}`;
            <?php else: ?>
                if (confirm('Anda harus login terlebih dahulu untuk memesan tiket. Login sekarang?')) {
                    window.location.href = `login.php?redirect=${encodeURIComponent('pesan.php?id=' + flightId + '&jumlah=' + jumlah)}`;
                }
            <?php endif; ?>
        }
    </script>
</body>
</html>