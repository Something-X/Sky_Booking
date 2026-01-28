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
                        primary: '#3b82f6',
                        secondary: '#1e40af',
                        accent: '#f59e0b',
                        sidebar: '#1e293b'
                    }
                }
            }
        }
    </script>
    <style>
        /* Tambahkan CSS ini untuk memastikan kolom tabel konsisten */
        #jadwalTableBody tr td:nth-child(1) {
            width: 7rem;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        #jadwalTableBody tr td:nth-child(2) {
            width: 10rem;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        #jadwalTableBody tr td:nth-child(3) {
            width: 8rem;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        #jadwalTableBody tr td:nth-child(4) {
            width: 8rem;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        #jadwalTableBody tr td:nth-child(5) {
            width: 9rem;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        #jadwalTableBody tr td:nth-child(6) {
            width: 8rem;
            padding-left: 1rem;
            padding-right: 1rem;
            text-align: right;
        }

        #jadwalTableBody tr td:nth-child(7) {
            width: 6rem;
            padding-left: 1rem;
            padding-right: 1rem;
            text-align: center;
        }

        #jadwalTableBody tr td:nth-child(8) {
            width: 7rem;
            padding-left: 1rem;
            padding-right: 1rem;
            text-align: center;
        }

        #jadwalTableBody tr td:nth-child(9) {
            width: 6rem;
            padding-left: 1rem;
            padding-right: 1rem;
            text-align: center;
        }

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

        .tab-button {
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        }

        .badge-arrived {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }
    </style>
</head>

<body class="bg-gray-50 flex">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 overflow-x-hidden">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm sticky top-0 z-10">
            <div class="px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Kelola Penerbangan</h1>
                    <p class="text-gray-500 text-sm mt-1">Kelola pesawat dan jadwal penerbangan</p>
                </div>
            </div>
        </header>

        <div class="p-8">
            <!-- Tabs -->
            <div class="bg-white rounded-xl shadow-md mb-6 p-2 flex gap-2">
                <button onclick="switchTab('pesawat')" id="tab-pesawat" class="tab-button active flex-1 px-6 py-3 rounded-lg font-semibold transition-all">
                    <i class="fas fa-plane mr-2"></i>Master Pesawat
                </button>
                <button onclick="switchTab('jadwal')" id="tab-jadwal" class="tab-button flex-1 px-6 py-3 rounded-lg font-semibold transition-all">
                    <i class="fas fa-calendar-alt mr-2"></i>Jadwal Penerbangan
                </button>
                <button onclick="switchTab('arrived')" id="tab-arrived" class="tab-button flex-1 px-6 py-3 rounded-lg font-semibold transition-all relative">
                    <i class="fas fa-plane mr-2"></i>Pesawat Tersedia
                    <span id="arrivedBadge" class="badge-arrived absolute -top-1 -right-1 bg-green-500 text-white text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center hidden">0</span>
                </button>
            </div>

            <!-- Tab Content: Master Pesawat -->
            <div id="content-pesawat" class="tab-content">
                <div class="bg-white rounded-xl shadow-md p-6 mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Data Master Pesawat</h2>
                        <p class="text-gray-500 text-sm">Kelola armada pesawat Anda</p>
                    </div>
                    <button onclick="showAddPesawatModal()" class="bg-primary hover:bg-secondary text-white font-bold px-6 py-3 rounded-lg transition duration-300 flex items-center shadow-lg hover:shadow-xl">
                        <i class="fas fa-plus mr-2"></i>Tambah Pesawat
                    </button>
                </div>

                <!-- Search Pesawat -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="text" id="searchPesawat" placeholder="Cari nomor registrasi, maskapai..."
                            class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <select id="filterStatusPesawat" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Semua Status</option>
                            <option value="operasional">Operasional</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="non-aktif">Non-Aktif</option>
                        </select>
                        <button onclick="loadPesawat()" class="bg-primary hover:bg-secondary text-white font-bold px-6 py-3 rounded-lg transition duration-300">
                            <i class="fas fa-search mr-2"></i>Cari
                        </button>
                    </div>
                </div>

                <!-- Table Pesawat -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-primary to-secondary text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left">Maskapai</th>
                                    <th class="px-6 py-4 text-left">No. Registrasi</th>
                                    <th class="px-6 py-4 text-left">Model</th>
                                    <th class="px-6 py-4 text-center">Kapasitas</th>
                                    <th class="px-6 py-4 text-center">Kelas Layanan</th>
                                    <th class="px-6 py-4 text-left">Lokasi Terakhir</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="pesawatTableBody">
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-spinner fa-spin text-2xl"></i>
                                        <p class="mt-2">Memuat data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Jadwal Penerbangan -->
            <div id="content-jadwal" class="tab-content hidden">
                <div class="bg-white rounded-xl shadow-md p-6 mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Jadwal Penerbangan</h2>
                        <p class="text-gray-500 text-sm">Kelola jadwal operasional pesawat</p>
                    </div>
                    <button onclick="showAddJadwalModal()" class="bg-primary hover:bg-secondary text-white font-bold px-6 py-3 rounded-lg transition duration-300 flex items-center shadow-lg hover:shadow-xl">
                        <i class="fas fa-plus mr-2"></i>Buat Jadwal
                    </button>
                </div>

                <!-- Filter Jadwal -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <input type="text" id="searchJadwal" placeholder="Cari kode penerbangan..."
                            class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <input type="date" id="filterTanggal" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <select id="filterStatusJadwal" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Semua Status</option>
                            <option value="Scheduled">Terjadwal</option>
                            <option value="Departed">Berangkat</option>
                            <option value="Arrived">Tiba</option>
                            <option value="Cancelled">Batal</option>
                        </select>
                        <button onclick="loadJadwal()" class="bg-primary hover:bg-secondary text-white font-bold px-6 py-3 rounded-lg transition duration-300">
                            <i class="fas fa-search mr-2"></i>Cari
                        </button>
                    </div>
                </div>

                <!-- Ganti bagian Table Jadwal dengan kode ini -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed">
                            <thead class="bg-gradient-to-r from-primary to-secondary text-white">
                                <tr>
                                    <th class="px-4 py-4 text-left w-28">Kode</th>
                                    <th class="px-4 py-4 text-left w-40">Pesawat</th>
                                    <th class="px-4 py-4 text-left w-32">Rute</th>
                                    <th class="px-4 py-4 text-left w-32">Tanggal</th>
                                    <th class="px-4 py-4 text-left w-36">Waktu</th>
                                    <th class="px-4 py-4 text-right w-32">Harga</th>
                                    <th class="px-4 py-4 text-center w-24">Kursi</th>
                                    <th class="px-4 py-4 text-center w-32">Kelas</th>
                                    <th class="px-4 py-4 text-center w-28">Status</th>
                                    <th class="px-4 py-4 text-center w-24">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="jadwalTableBody">
                                <tr>
                                    <td colspan="10" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-spinner fa-spin text-2xl"></i>
                                        <p class="mt-2">Memuat data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Pesawat Tersedia -->
            <div id="content-arrived" class="tab-content hidden">
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Pesawat Tersedia</h2>
                    <p class="text-gray-500 text-sm">Pesawat operasional yang belum memiliki jadwal penerbangan aktif</p>
                </div>

                <!-- Table Pesawat Tersedia -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-green-500 to-green-600 text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left">No. Registrasi</th>
                                    <th class="px-6 py-4 text-left">Pesawat</th>
                                    <th class="px-6 py-4 text-left">Lokasi Saat Ini</th>
                                    <th class="px-6 py-4 text-center">Spesifikasi</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="arrivedTableBody">
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-spinner fa-spin text-2xl"></i>
                                        <p class="mt-2">Memuat data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Add/Edit Pesawat -->
    <div id="pesawatModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-8 py-6 flex justify-between items-center rounded-t-2xl">
                <h3 id="pesawatModalTitle" class="text-2xl font-bold text-gray-800">Tambah Pesawat</h3>
                <button onclick="closeModal('pesawatModal')" class="text-gray-400 hover:text-gray-600 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-8">
                <form id="pesawatForm">
                    <input type="hidden" id="pesawatId" name="id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Maskapai *</label>
                            <input type="text" name="maskapai" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">No. Registrasi *</label>
                            <input type="text" name="nomor_registrasi" required placeholder="PK-XXX"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Model Pesawat *</label>
                            <input type="text" name="model" required placeholder="Boeing 737-800"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kapasitas *</label>
                            <input type="number" name="kapasitas" required min="1" value="180"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kelas Layanan *</label>
                            <select name="kelas_layanan" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="Economy Class">Economy Class</option>
                                <option value="Business Class">Business Class</option>
                                <option value="First Class">First Class</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Lokasi Terakhir</label>
                            <select name="airport_id" id="airportSelect"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">Pilih Bandara</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status *</label>
                            <select name="status_pesawat" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="operasional">Operasional</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="non-aktif">Non-Aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-4 mt-8">
                        <button type="submit" class="flex-1 bg-primary hover:bg-secondary text-white font-bold py-4 rounded-lg transition duration-300 shadow-lg hover:shadow-xl">
                            <i class="fas fa-save mr-2"></i>Simpan
                        </button>
                        <button type="button" onclick="closeModal('pesawatModal')" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-4 rounded-lg transition duration-300">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Add/Edit Jadwal -->
    <div id="jadwalModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-8 py-6 flex justify-between items-center rounded-t-2xl">
                <h3 id="jadwalModalTitle" class="text-2xl font-bold text-gray-800">Buat Jadwal Penerbangan</h3>
                <button onclick="closeModal('jadwalModal')" class="text-gray-400 hover:text-gray-600 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-8">
                <form id="jadwalForm">
                    <input type="hidden" id="jadwalId" name="id">
                    <input type="hidden" id="jadwalFromArrived" name="from_arrived" value="0">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pesawat *</label>
                            <select name="pesawat_id" id="pesawatSelectJadwal" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">Pilih Pesawat</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Hanya pesawat operasional yang ditampilkan</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kode Penerbangan *</label>
                            <input type="text" name="kode_penerbangan" required placeholder="GA-123"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Harga *</label>
                            <input type="number" name="harga" required min="0" step="1000"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Asal *</label>
                            <select name="origin_airport_id" id="originAirportSelect" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">Pilih Bandara Asal</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tujuan *</label>
                            <select name="destination_airport_id" id="destAirportSelect" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">Pilih Bandara Tujuan</option>
                            </select>
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
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status *</label>
                            <select name="status_tracking" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="Scheduled">Terjadwal</option>
                                <option value="Departed">Berangkat</option>
                                <option value="Arrived">Tiba</option>
                                <option value="Cancelled">Batal</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-4 mt-8">
                        <button type="submit" class="flex-1 bg-primary hover:bg-secondary text-white font-bold py-4 rounded-lg transition duration-300 shadow-lg hover:shadow-xl">
                            <i class="fas fa-save mr-2"></i>Simpan
                        </button>
                        <button type="button" onclick="closeModal('jadwalModal')" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-4 rounded-lg transition duration-300">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/kelola_penerbangan.js"></script>
</body>

</html>