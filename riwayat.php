<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pemesanan - SkyBooking</title>
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
                <div class="hidden md:flex space-x-6">
                    <a href="index.php" class="text-white hover:text-gray-200 transition">Home</a>
                    <a href="riwayat.php" class="text-white hover:text-gray-200 transition border-b-2 border-white">Riwayat Pemesanan</a>
                    <?php if (isAdmin()): ?>
                        <a href="admin/dashboard.php" class="text-white hover:text-gray-200 transition">Dashboard Admin</a>
                        <a href="admin/logout.php" class="text-white hover:text-gray-200 transition">Logout</a>
                    <?php else: ?>
                        <a href="admin/login.php" class="text-white hover:text-gray-200 transition">Login Admin</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-12">
        <div class="max-w-5xl mx-auto">
            <h1 class="text-4xl font-bold text-gray-800 mb-8 flex items-center">
                <i class="fas fa-history mr-4 text-primary"></i>
                Riwayat Pemesanan
            </h1>

            <!-- Search Box -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <div class="flex flex-col md:flex-row gap-4">
                    <input type="text" id="searchInput" placeholder="Cari berdasarkan kode booking, nama, atau email..."
                           class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <button onclick="searchBooking()" class="bg-primary hover:bg-secondary text-white font-bold px-8 py-3 rounded-lg transition duration-300">
                        <i class="fas fa-search mr-2"></i>Cari
                    </button>
                </div>
            </div>

            <!-- Loading -->
            <div id="loadingSpinner" class="text-center hidden">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
                <p class="mt-4 text-gray-600">Memuat data...</p>
            </div>

            <!-- Results -->
            <div id="resultsContainer"></div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-8 py-6 flex justify-between items-center">
                <h3 class="text-2xl font-bold text-gray-800">Detail Pemesanan</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalContent" class="p-8"></div>
        </div>
    </div>

    <script>
        // Load semua pemesanan saat halaman dimuat
        window.addEventListener('DOMContentLoaded', function() {
            loadBookings();
        });

        async function loadBookings(search = '') {
            const loadingSpinner = document.getElementById('loadingSpinner');
            const resultsContainer = document.getElementById('resultsContainer');
            
            loadingSpinner.classList.remove('hidden');
            resultsContainer.innerHTML = '';
            
            try {
                const response = await fetch('api/get_bookings.php?search=' + encodeURIComponent(search));
                const data = await response.json();
                
                loadingSpinner.classList.add('hidden');
                
                if (data.success && data.data.length > 0) {
                    displayBookings(data.data);
                } else {
                    resultsContainer.innerHTML = `
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle text-yellow-400 text-2xl mr-4"></i>
                                <div>
                                    <p class="font-semibold text-yellow-700">Tidak ada data pemesanan</p>
                                    <p class="text-yellow-600 text-sm mt-1">Belum ada riwayat pemesanan atau data tidak ditemukan</p>
                                </div>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                loadingSpinner.classList.add('hidden');
                resultsContainer.innerHTML = `
                    <div class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-400 text-2xl mr-4"></i>
                            <p class="text-red-700">Terjadi kesalahan saat memuat data</p>
                        </div>
                    </div>
                `;
            }
        }

        function searchBooking() {
            const search = document.getElementById('searchInput').value;
            loadBookings(search);
        }

        // Enter key untuk search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchBooking();
            }
        });

        function displayBookings(bookings) {
            const container = document.getElementById('resultsContainer');
            let html = '';
            
            bookings.forEach(booking => {
                const statusClass = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'lunas': 'bg-green-100 text-green-800',
                    'batal': 'bg-red-100 text-red-800'
                }[booking.status] || 'bg-gray-100 text-gray-800';
                
                const statusIcon = {
                    'pending': 'fa-clock',
                    'lunas': 'fa-check-circle',
                    'batal': 'fa-times-circle'
                }[booking.status] || 'fa-question-circle';
                
                html += `
                    <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300 p-6 mb-4">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-2xl font-bold text-primary">${booking.kode_booking}</span>
                                    <span class="${statusClass} px-3 py-1 rounded-full text-sm font-semibold">
                                        <i class="fas ${statusIcon} mr-1"></i>
                                        ${booking.status.toUpperCase()}
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <div class="flex items-center text-gray-600 mb-2">
                                            <i class="fas fa-user w-5 mr-2"></i>
                                            <span class="font-semibold">${booking.nama_pemesan}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600 mb-2">
                                            <i class="fas fa-envelope w-5 mr-2"></i>
                                            <span>${booking.email}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-phone w-5 mr-2"></i>
                                            <span>${booking.no_hp}</span>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <div class="flex items-center text-gray-600 mb-2">
                                            <i class="fas fa-plane w-5 mr-2"></i>
                                            <span class="font-semibold">${booking.maskapai}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600 mb-2">
                                            <i class="fas fa-map-marker-alt w-5 mr-2"></i>
                                            <span>${booking.asal} → ${booking.tujuan}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-calendar w-5 mr-2"></i>
                                            <span>${booking.tanggal}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col items-end gap-3">
                                <div class="text-right">
                                    <div class="text-sm text-gray-500">Total Harga</div>
                                    <div class="text-2xl font-bold text-accent">${formatRupiah(booking.total_harga)}</div>
                                    <div class="text-sm text-gray-500">${booking.jumlah_penumpang} penumpang</div>
                                </div>
                                <button onclick="showDetail(${booking.id})" 
                                        class="bg-primary hover:bg-secondary text-white font-bold px-6 py-2 rounded-lg transition duration-300">
                                    <i class="fas fa-eye mr-2"></i>Detail
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        async function showDetail(id) {
            const modalContent = document.getElementById('modalContent');
            modalContent.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-4xl text-primary"></i></div>';
            document.getElementById('detailModal').classList.remove('hidden');
            
            try {
                const response = await fetch('api/get_booking_detail.php?id=' + id);
                const data = await response.json();
                
                if (data.success) {
                    displayDetail(data.data);
                } else {
                    modalContent.innerHTML = '<p class="text-red-600 text-center">Gagal memuat detail</p>';
                }
            } catch (error) {
                modalContent.innerHTML = '<p class="text-red-600 text-center">Terjadi kesalahan</p>';
            }
        }

        function displayDetail(data) {
            const modalContent = document.getElementById('modalContent');
            
            const statusClass = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'lunas': 'bg-green-100 text-green-800',
                'batal': 'bg-red-100 text-red-800'
            }[data.status] || 'bg-gray-100 text-gray-800';
            
            let penumpangHtml = '';
            data.penumpang.forEach((p, i) => {
                penumpangHtml += `
                    <div class="bg-gray-50 rounded-lg p-4 mb-3">
                        <h5 class="font-bold text-gray-800 mb-2">Penumpang ${i + 1}</h5>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><span class="text-gray-600">Nama:</span> <span class="font-semibold">${p.nama_lengkap}</span></div>
                            <div><span class="text-gray-600">Jenis Kelamin:</span> ${p.jenis_kelamin}</div>
                            <div><span class="text-gray-600">Tanggal Lahir:</span> ${p.tanggal_lahir}</div>
                            <div><span class="text-gray-600">NIK:</span> ${p.nik || '-'}</div>
                        </div>
                    </div>
                `;
            });
            
            modalContent.innerHTML = `
                <div class="space-y-6">
                    <div class="bg-gradient-to-r from-primary to-secondary text-white rounded-xl p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h4 class="text-sm opacity-90 mb-1">Kode Booking</h4>
                                <p class="text-3xl font-bold">${data.kode_booking}</p>
                            </div>
                            <span class="${statusClass} px-4 py-2 rounded-full text-sm font-semibold">
                                ${data.status.toUpperCase()}
                            </span>
                        </div>
                        <p class="text-sm opacity-90">Tanggal Booking: ${data.created_at}</p>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-gray-800 mb-3 text-lg">Informasi Penerbangan</h4>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between"><span class="text-gray-600">Maskapai:</span> <span class="font-semibold">${data.maskapai}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Kode:</span> <span class="font-semibold">${data.kode_penerbangan}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Rute:</span> <span class="font-semibold">${data.asal} → ${data.tujuan}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Tanggal:</span> <span class="font-semibold">${data.tanggal}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Waktu:</span> <span class="font-semibold">${data.jam_berangkat} - ${data.jam_tiba}</span></div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-gray-800 mb-3 text-lg">Informasi Pemesan</h4>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between"><span class="text-gray-600">Nama:</span> <span class="font-semibold">${data.nama_pemesan}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Email:</span> <span class="font-semibold">${data.email}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">No. HP:</span> <span class="font-semibold">${data.no_hp}</span></div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-gray-800 mb-3 text-lg">Data Penumpang</h4>
                        ${penumpangHtml}
                    </div>
                    
                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center text-xl">
                            <span class="font-bold text-gray-800">Total Pembayaran</span>
                            <span class="font-bold text-accent">${formatRupiah(data.total_harga)}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        function closeModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>
</body>
</html>