<?php
require_once '../config.php';

if (!isAdmin()) {
    redirect('login.php');
}

// Ambil statistik
$stats = [
    'total_penerbangan' => 0,
    'total_pemesanan' => 0,
    'total_pending' => 0,
    'total_lunas' => 0,
    'total_pendapatan' => 0
];

$result = $conn->query("SELECT COUNT(*) as total FROM penerbangan WHERE status = 'aktif'");
if ($result) $stats['total_penerbangan'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM pemesanan");
if ($result) $stats['total_pemesanan'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE status = 'pending'");
if ($result) $stats['total_pending'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE status = 'lunas'");
if ($result) $stats['total_lunas'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT SUM(total_harga) as total FROM pemesanan WHERE status = 'lunas'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_pendapatan'] = $row['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SkyBooking</title>
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
                    <span class="text-white">
                        <i class="fas fa-user-circle mr-2"></i>
                        <?= $_SESSION['admin_name'] ?? 'Admin' ?>
                    </span>
                    <a href="../index.php" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-home mr-2"></i>Home
                    </a>
                    <a href="logout.php" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Dashboard Admin</h1>
            <p class="text-gray-600">Kelola sistem pemesanan tiket pesawat</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total Penerbangan</p>
                        <h3 class="text-3xl font-bold text-gray-800"><?= $stats['total_penerbangan'] ?></h3>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-plane text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total Pemesanan</p>
                        <h3 class="text-3xl font-bold text-gray-800"><?= $stats['total_pemesanan'] ?></h3>
                    </div>
                    <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-ticket-alt text-2xl text-purple-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Pending</p>
                        <h3 class="text-3xl font-bold text-yellow-600"><?= $stats['total_pending'] ?></h3>
                    </div>
                    <div class="w-14 h-14 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-2xl text-yellow-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Lunas</p>
                        <h3 class="text-3xl font-bold text-green-600"><?= $stats['total_lunas'] ?></h3>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total Pendapatan</p>
                        <h3 class="text-lg font-bold text-accent"><?= formatRupiah($stats['total_pendapatan']) ?></h3>
                    </div>
                    <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-2xl text-orange-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Kelola Penerbangan -->
            <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition duration-300 overflow-hidden transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-2xl font-bold text-white">Kelola Penerbangan</h3>
                        <i class="fas fa-plane-departure text-4xl text-white opacity-50"></i>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-6">Tambah, edit, atau hapus jadwal penerbangan</p>
                    <a href="penerbangan.php" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-lg transition duration-300">
                        <i class="fas fa-arrow-right mr-2"></i>Kelola Penerbangan
                    </a>
                </div>
            </div>

            <!-- Kelola Pemesanan -->
            <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition duration-300 overflow-hidden transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-2xl font-bold text-white">Kelola Pemesanan</h3>
                        <i class="fas fa-list-alt text-4xl text-white opacity-50"></i>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-6">Lihat dan update status pemesanan tiket</p>
                    <a href="pemesanan.php" class="inline-flex items-center bg-purple-600 hover:bg-purple-700 text-white font-bold px-6 py-3 rounded-lg transition duration-300">
                        <i class="fas fa-arrow-right mr-2"></i>Kelola Pemesanan
                    </a>
                </div>
            </div>

            <!-- Ke Website User -->
            <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition duration-300 overflow-hidden transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-green-500 to-green-600 p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-2xl font-bold text-white">Website User</h3>
                        <i class="fas fa-globe text-4xl text-white opacity-50"></i>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-6">Lihat tampilan website untuk user</p>
                    <a href="../index.php" target="_blank" class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-3 rounded-lg transition duration-300">
                        <i class="fas fa-external-link-alt mr-2"></i>Buka Website
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Stats Table -->
        <div class="mt-8 bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-chart-bar mr-3"></i>
                    Ringkasan Cepat
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-bold text-gray-800 mb-3">Status Pemesanan</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-gray-600">Pending</span>
                                <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full font-bold"><?= $stats['total_pending'] ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-gray-600">Lunas</span>
                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full font-bold"><?= $stats['total_lunas'] ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-600">Total</span>
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-bold"><?= $stats['total_pemesanan'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 mb-3">Informasi Sistem</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-gray-600">Total Penerbangan Aktif</span>
                                <span class="font-bold text-gray-800"><?= $stats['total_penerbangan'] ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-gray-600">Pendapatan Lunas</span>
                                <span class="font-bold text-accent"><?= formatRupiah($stats['total_pendapatan']) ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-600">Status Server</span>
                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full font-bold">
                                    <i class="fas fa-check-circle mr-1"></i>Online
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12 py-6">
        <div class="container mx-auto px-4 text-center text-gray-600">
            <p>&copy; 2025 SkyBooking Admin Panel. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>