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
    <title>Kelola Penerbangan - SkyBooking</title>
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
                    <a href="penerbangan.php" class="text-white hover:text-gray-200 transition border-b-2 border-white">Penerbangan</a>
                    <a href="pemesanan.php" class="text-white hover:text-gray-200 transition">Pemesanan</a>
                    <a href="logout.php" class="text-white hover:text-gray-200 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold text-gray-800">Kelola Penerbangan</h1>
                <p class="text-gray-600 mt-2">Tambah, edit, atau hapus jadwal penerbangan</p>
            </div>
            <button onclick="showAddModal()" class="bg-primary hover:bg-secondary text-white font-bold px-6 py-3 rounded-lg transition duration-300 flex items-center">
                <i class="fas fa-plus mr-2"></i>Tambah Penerbangan
            </button>
        </div>

        <!-- Filter & Search -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="text" id="searchInput" placeholder="Cari maskapai, kode, rute..."
                       class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <select id="filterStatus" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="batal">Batal</option>
                </select>
                <button onclick="loadFlights()" class="bg-primary hover:bg-secondary text-white font-bold px-6 py-3 rounded-lg transition duration-300">
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
                            <th class="px-6 py-4 text-left">Maskapai</th>
                            <th class="px-6 py-4 text-left">Kode</th>
                            <th class="px-6 py-4 text-left">Rute</th>
                            <th class="px-6 py-4 text-left">Tanggal</th>
                            <th class="px-6 py-4 text-left">Waktu</th>
                            <th class="px-6 py-4 text-right">Harga</th>
                            <th class="px-6 py-4 text-center">Kursi</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="flightTableBody">
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                Loading...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="flightModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-8 py-6 flex justify-between items-center">
                <h3 id="modalTitle" class="text-2xl font-bold text-gray-800">Tambah Penerbangan</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-8">
                <form id="flightForm">
                    <input type="hidden" id="flightId" name="id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Maskapai *</label>
                            <input type="text" name="maskapai" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kode Penerbangan *</label>
                            <input type="text" name="kode_penerbangan" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Asal *</label>
                            <input type="text" name="asal" required placeholder="Jakarta (CGK)"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tujuan *</label>
                            <input type="text" name="tujuan" required placeholder="Surabaya (SUB)"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal *</label>
                            <input type="date" name="tanggal" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Berangkat *</label>
                            <input type="time" name="jam_berangkat" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Tiba *</label>
                            <input type="time" name="jam_tiba" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Harga *</label>
                            <input type="number" name="harga" required min="0" step="1000"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kapasitas *</label>
                            <input type="number" name="kapasitas" required min="1" value="100"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status *</label>
                            <select name="status" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="aktif">Aktif</option>
                                <option value="batal">Batal</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-4 mt-8">
                        <button type="submit" class="flex-1 bg-primary hover:bg-secondary text-white font-bold py-4 rounded-lg transition duration-300">
                            <i class="fas fa-save mr-2"></i>Simpan
                        </button>
                        <button type="button" onclick="closeModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-4 rounded-lg transition duration-300">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', function() {
            loadFlights();
        });

        async function loadFlights() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('filterStatus').value;
            
            document.getElementById('loadingSpinner').classList.remove('hidden');
            
            try {
                const response = await fetch(`api/get_flights.php?search=${encodeURIComponent(search)}&status=${status}`);
                const data = await response.json();
                
                document.getElementById('loadingSpinner').classList.add('hidden');
                
                if (data.success) {
                    displayFlights(data.data);
                }
            } catch (error) {
                document.getElementById('loadingSpinner').classList.add('hidden');
                alert('Terjadi kesalahan saat memuat data');
            }
        }

        function displayFlights(flights) {
            const tbody = document.getElementById('flightTableBody');
            
            if (flights.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                            Tidak ada data penerbangan
                        </td>
                    </tr>
                `;
                return;
            }
            
            let html = '';
            flights.forEach(flight => {
                const statusClass = flight.status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                html += `
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-6 py-4 font-semibold">${flight.maskapai}</td>
                        <td class="px-6 py-4">${flight.kode_penerbangan}</td>
                        <td class="px-6 py-4">
                            <div class="text-sm">${flight.asal}</div>
                            <div class="text-sm text-gray-500">→ ${flight.tujuan}</div>
                        </td>
                        <td class="px-6 py-4">${flight.tanggal}</td>
                        <td class="px-6 py-4">
                            <div class="text-sm">${flight.jam_berangkat}</div>
                            <div class="text-sm text-gray-500">→ ${flight.jam_tiba}</div>
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-primary">${formatRupiah(flight.harga)}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm">${flight.tersedia}/${flight.kapasitas}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="${statusClass} px-3 py-1 rounded-full text-xs font-semibold">
                                ${flight.status.toUpperCase()}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button onclick="editFlight(${flight.id})" class="text-blue-600 hover:text-blue-800 mr-3" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteFlight(${flight.id})" class="text-red-600 hover:text-red-800" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }

        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Penerbangan';
            document.getElementById('flightForm').reset();
            document.getElementById('flightId').value = '';
            document.getElementById('flightModal').classList.remove('hidden');
        }

        async function editFlight(id) {
            try {
                const response = await fetch(`api/get_flight.php?id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('modalTitle').textContent = 'Edit Penerbangan';
                    document.getElementById('flightId').value = data.data.id;
                    
                    const form = document.getElementById('flightForm');
                    form.maskapai.value = data.data.maskapai;
                    form.kode_penerbangan.value = data.data.kode_penerbangan;
                    form.asal.value = data.data.asal;
                    form.tujuan.value = data.data.tujuan;
                    form.tanggal.value = data.data.tanggal;
                    form.jam_berangkat.value = data.data.jam_berangkat;
                    form.jam_tiba.value = data.data.jam_tiba;
                    form.harga.value = data.data.harga;
                    form.kapasitas.value = data.data.kapasitas;
                    form.status.value = data.data.status;
                    
                    document.getElementById('flightModal').classList.remove('hidden');
                }
            } catch (error) {
                alert('Gagal memuat data penerbangan');
            }
        }

        async function deleteFlight(id) {
            if (!confirm('Yakin ingin menghapus penerbangan ini?')) return;
            
            try {
                const response = await fetch('api/delete_flight.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'id=' + id
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Penerbangan berhasil dihapus');
                    loadFlights();
                } else {
                    alert(data.message || 'Gagal menghapus penerbangan');
                }
            } catch (error) {
                alert('Terjadi kesalahan');
            }
        }

        document.getElementById('flightForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
            
            try {
                const response = await fetch('api/save_flight.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Data berhasil disimpan');
                    closeModal();
                    loadFlights();
                } else {
                    alert(data.message || 'Gagal menyimpan data');
                }
            } catch (error) {
                alert('Terjadi kesalahan');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
            }
        });

        function closeModal() {
            document.getElementById('flightModal').classList.add('hidden');
        }

        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>
</body>
</html>