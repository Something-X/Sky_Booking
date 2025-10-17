<?php
require_once '../config.php';

if (!isAdmin()) {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pemesanan - SkyBooking</title>
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
                <a href="dashboard.php" class="text-white text-2xl font-bold flex items-center">
                    <i class="fas fa-plane-departure mr-2"></i>SkyBooking Admin
                </a>
                <div class="flex items-center space-x-6">
                    <a href="dashboard.php" class="text-white hover:text-gray-200 transition">Dashboard</a>
                    <a href="penerbangan.php" class="text-white hover:text-gray-200 transition">Penerbangan</a>
                    <a href="pemesanan.php" class="text-white hover:text-gray-200 transition border-b-2 border-white">Pemesanan</a>
                    <a href="logout.php" class="text-white hover:text-gray-200 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800">Kelola Pemesanan</h1>
            <p class="text-gray-600 mt-2">Lihat dan update status pemesanan tiket</p>
        </div>

        <!-- Filter & Search -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="text" id="searchInput" placeholder="Cari kode booking, nama, email..."
                       class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <select id="filterStatus" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="lunas">Lunas</option>
                    <option value="batal">Batal</option>
                </select>
                <button onclick="loadBookings()" class="bg-primary hover:bg-secondary text-white font-bold px-6 py-3 rounded-lg transition duration-300">
                    <i class="fas fa-search mr-2"></i>Cari
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div id="loadingSpinner" class="text-center hidden">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-primary to-secondary text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">Kode Booking</th>
                            <th class="px-6 py-4 text-left">Pemesan</th>
                            <th class="px-6 py-4 text-left">Penerbangan</th>
                            <th class="px-6 py-4 text-left">Rute</th>
                            <th class="px-6 py-4 text-center">Penumpang</th>
                            <th class="px-6 py-4 text-right">Total</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="bookingTableBody">
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                Loading...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">Update Status Pemesanan</h3>
            <form id="statusForm">
                <input type="hidden" id="bookingId">
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Kode Booking</label>
                    <input type="text" id="bookingCode" readonly
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status *</label>
                    <select id="bookingStatus" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="pending">Pending</option>
                        <option value="lunas">Lunas</option>
                        <option value="batal">Batal</option>
                    </select>
                </div>
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-primary hover:bg-secondary text-white font-bold py-3 rounded-lg transition duration-300">
                        <i class="fas fa-save mr-2"></i>Update
                    </button>
                    <button type="button" onclick="closeStatusModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 rounded-lg transition duration-300">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', function() {
            loadBookings();
        });

        async function loadBookings() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('filterStatus').value;
            
            document.getElementById('loadingSpinner').classList.remove('hidden');
            
            try {
                const response = await fetch(`api/get_bookings_admin.php?search=${encodeURIComponent(search)}&status=${status}`);
                const data = await response.json();
                
                document.getElementById('loadingSpinner').classList.add('hidden');
                
                if (data.success) {
                    displayBookings(data.data);
                }
            } catch (error) {
                document.getElementById('loadingSpinner').classList.add('hidden');
                alert('Terjadi kesalahan saat memuat data');
            }
        }

        function displayBookings(bookings) {
            const tbody = document.getElementById('bookingTableBody');
            
            if (bookings.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            Tidak ada data pemesanan
                        </td>
                    </tr>
                `;
                return;
            }
            
            let html = '';
            bookings.forEach(booking => {
                const statusClass = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'lunas': 'bg-green-100 text-green-800',
                    'batal': 'bg-red-100 text-red-800'
                }[booking.status] || 'bg-gray-100 text-gray-800';
                
                html += `
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-6 py-4 font-bold text-primary">${booking.kode_booking}</td>
                        <td class="px-6 py-4">
                            <div class="font-semibold">${booking.nama_pemesan}</div>
                            <div class="text-sm text-gray-500">${booking.email}</div>
                            <div class="text-sm text-gray-500">${booking.no_hp}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-semibold">${booking.maskapai}</div>
                            <div class="text-sm text-gray-500">${booking.kode_penerbangan}</div>
                            <div class="text-sm text-gray-500">${booking.tanggal}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm">${booking.asal}</div>
                            <div class="text-sm text-gray-500">→ ${booking.tujuan}</div>
                        </td>
                        <td class="px-6 py-4 text-center font-semibold">${booking.jumlah_penumpang}</td>
                        <td class="px-6 py-4 text-right font-bold text-accent">${formatRupiah(booking.total_harga)}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="${statusClass} px-3 py-1 rounded-full text-xs font-semibold">
                                ${booking.status.toUpperCase()}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button onclick="updateStatus(${booking.id}, '${booking.kode_booking}', '${booking.status}')" 
                                    class="text-blue-600 hover:text-blue-800 mr-3" title="Update Status">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="viewDetail(${booking.id})" 
                                    class="text-green-600 hover:text-green-800" title="Detail">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }

        function updateStatus(id, kode, status) {
            document.getElementById('bookingId').value = id;
            document.getElementById('bookingCode').value = kode;
            document.getElementById('bookingStatus').value = status;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        document.getElementById('statusForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const id = document.getElementById('bookingId').value;
            const status = document.getElementById('bookingStatus').value;
            
            try {
                const response = await fetch('api/update_booking_status.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `id=${id}&status=${status}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Status berhasil diupdate');
                    closeStatusModal();
                    loadBookings();
                } else {
                    alert(data.message || 'Gagal update status');
                }
            } catch (error) {
                alert('Terjadi kesalahan');
            }
        });

        function viewDetail(id) {
            window.open(`../riwayat.php?view=${id}`, '_blank');
        }

        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>
</body>
</html>