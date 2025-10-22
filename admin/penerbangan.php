<?php
require_once '../config.php';

if (!isAdmin()) {
    redirect('login.php');
}

// Get pending count for badge
$pending_result = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE status = 'pending'");
$pending_count = $pending_result ? $pending_result->fetch_assoc()['total'] : 0;
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
                        accent: '#ff6b35',
                        sidebar: '#1a1d2e'
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar {
            width: 260px;
            transition: all 0.3s ease;
        }
        .sidebar.collapsed {
            width: 80px;
        }
        .sidebar-item {
            transition: all 0.2s ease;
        }
        .sidebar-item:hover {
            background: rgba(59, 130, 246, 0.1);
            transform: translateX(4px);
        }
        .sidebar-item.active {
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
            color: white !important;
        }
    </style>
</head>
<body class="bg-gray-50 flex">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar bg-sidebar h-screen sticky top-0 flex flex-col shadow-2xl">
        <div class="p-6 flex items-center justify-between border-b border-gray-700">
            <a href="dashboard.php" class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-plane text-white text-lg"></i>
                </div>
                <span id="logo-text" class="text-white text-xl font-bold">SkyBooking</span>
            </a>
            <button onclick="toggleSidebar()" class="text-gray-400 hover:text-white">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <nav class="flex-1 p-4 overflow-y-auto">
            <div class="mb-6">
                <p class="text-gray-500 text-xs uppercase mb-3 px-4" id="general-label">General</p>
                <a href="dashboard.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2">
                    <i class="fas fa-home w-5"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
                <a href="penerbangan.php" class="sidebar-item active flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2">
                    <i class="fas fa-plane-departure w-5"></i>
                    <span class="sidebar-text">Penerbangan</span>
                </a>
                <a href="pemesanan.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2">
                    <i class="fas fa-ticket-alt w-5"></i>
                    <span class="sidebar-text">Pemesanan</span>
                    <?php if ($pending_count > 0): ?>
                    <span class="sidebar-text ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $pending_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="flight_tracking.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2">
                    <i class="fas fa-map-marked-alt w-5"></i>
                    <span class="sidebar-text">Live Tracking</span>
                </a>
            </div>

            <div class="mb-6">
                <p class="text-gray-500 text-xs uppercase mb-3 px-4" id="analytics-label">Analytics</p>
                <a href="analytics.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2">
                    <i class="fas fa-chart-line w-5"></i>
                    <span class="sidebar-text">Analytics</span>
                </a>
                <a href="#reports" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2">
                    <i class="fas fa-file-alt w-5"></i>
                    <span class="sidebar-text">Reports</span>
                </a>
            </div>

            <div>
                <p class="text-gray-500 text-xs uppercase mb-3 px-4" id="support-label">Support</p>
                <a href="#settings" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2">
                    <i class="fas fa-cog w-5"></i>
                    <span class="sidebar-text">Settings</span>
                </a>
                <a href="logout.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span class="sidebar-text">Logout</span>
                </a>
            </div>
        </nav>

        <div class="p-4 border-t border-gray-700">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-4" id="upgrade-card">
                <i class="fas fa-crown text-yellow-300 text-2xl mb-2"></i>
                <p class="text-white font-semibold mb-1 sidebar-text">Upgrade Plan</p>
                <p class="text-blue-100 text-xs mb-3 sidebar-text">Get premium features</p>
                <button class="sidebar-text w-full bg-white text-blue-600 font-semibold py-2 rounded-lg text-sm hover:bg-blue-50 transition">
                    Upgrade Now
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-x-hidden">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm sticky top-0 z-10">
            <div class="px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Kelola Penerbangan</h1>
                    <p class="text-gray-500 text-sm mt-1">Tambah, edit, atau hapus jadwal penerbangan</p>
                </div>
                <button onclick="showAddModal()" class="bg-primary hover:bg-secondary text-white font-bold px-6 py-3 rounded-lg transition duration-300 flex items-center">
                    <i class="fas fa-plus mr-2"></i>Tambah Penerbangan
                </button>
            </div>
        </header>

        <div class="p-8">
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
    </main>

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
        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            
            const texts = document.querySelectorAll('.sidebar-text');
            const labels = document.querySelectorAll('[id$="-label"]');
            const upgradeCard = document.getElementById('upgrade-card');
            
            texts.forEach(el => el.classList.toggle('hidden'));
            labels.forEach(el => el.classList.toggle('hidden'));
            if (upgradeCard) upgradeCard.classList.toggle('hidden');
        }

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