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
    'total_batal' => 0,
    'total_pendapatan' => 0,
    'pemesanan_hari_ini' => 0,
    'pendapatan_hari_ini' => 0,
    'total_penumpang' => 0
];

$result = $conn->query("SELECT COUNT(*) as total FROM penerbangan WHERE status = 'aktif'");
if ($result) $stats['total_penerbangan'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM pemesanan");
if ($result) $stats['total_pemesanan'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE status = 'pending'");
if ($result) $stats['total_pending'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE status = 'lunas'");
if ($result) $stats['total_lunas'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE status = 'batal'");
if ($result) $stats['total_batal'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT SUM(total_harga) as total FROM pemesanan WHERE status = 'lunas'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_pendapatan'] = $row['total'] ?? 0;
}

$result = $conn->query("SELECT COUNT(*) as total, SUM(total_harga) as revenue FROM pemesanan WHERE DATE(created_at) = CURDATE()");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['pemesanan_hari_ini'] = $row['total'] ?? 0;
    $stats['pendapatan_hari_ini'] = $row['revenue'] ?? 0;
}

$result = $conn->query("SELECT SUM(jumlah_penumpang) as total FROM pemesanan WHERE status = 'lunas'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_penumpang'] = $row['total'] ?? 0;
}

// Hitung persentase perubahan (simulasi)
$stats['pemesanan_change'] = 15.8;
$stats['pendapatan_change'] = -8.2;
$stats['penumpang_change'] = 24.5;

// Get revenue by month for chart
$revenueData = [];
$monthlyQuery = "SELECT 
    MONTH(created_at) as bulan,
    SUM(total_harga) as total
    FROM pemesanan 
    WHERE status = 'lunas' 
    AND YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
    ORDER BY bulan";
$result = $conn->query($monthlyQuery);
$monthlyRevenue = array_fill(1, 12, 0);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $monthlyRevenue[$row['bulan']] = $row['total'];
    }
}

// Get recent bookings
$recentBookings = [];
$bookingQuery = "SELECT p.*, 
          pen.maskapai, pen.asal, pen.tujuan, pen.kode_penerbangan
          FROM pemesanan p
          LEFT JOIN penerbangan pen ON p.id_penerbangan = pen.id
          ORDER BY p.created_at DESC
          LIMIT 5";
$result = $conn->query($bookingQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentBookings[] = $row;
    }
}

// Get popular routes
$popularRoutes = [];
$routeQuery = "SELECT pen.asal, pen.tujuan, 
          COUNT(p.id) as total_bookings
          FROM pemesanan p
          INNER JOIN penerbangan pen ON p.id_penerbangan = pen.id
          WHERE p.status = 'lunas'
          GROUP BY pen.asal, pen.tujuan
          ORDER BY total_bookings DESC
          LIMIT 5";
$result = $conn->query($routeQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $popularRoutes[] = $row;
    }
}
$maxBookings = !empty($popularRoutes) ? $popularRoutes[0]['total_bookings'] : 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SkyBooking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
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
        .sidebar {
            width: 240px;
            transition: all 0.3s ease;
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .sidebar-item {
            transition: all 0.2s ease;
        }
        .sidebar-item:hover {
            background: rgba(59, 130, 246, 0.1);
            transform: translateX(4px);
        }
        .sidebar-item.active {
            background: #3b82f6;
            color: white !important;
        }
        .metric-card {
            transition: all 0.3s ease;
        }
        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }
        .chart-container {
            position: relative;
            height: 300px;
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
                    <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
                    <p class="text-gray-500 text-sm mt-1">Welcome back, <?= $_SESSION['admin_name'] ?? 'Administrator' ?>! 👋</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 bg-gray-100 rounded-lg px-4 py-2">
                        <i class="fas fa-calendar text-gray-500"></i>
                        <span class="text-sm font-medium"><?= date('d M Y') ?></span>
                    </div>
                    <button class="relative p-2 text-gray-500 hover:text-gray-700">
                        <i class="fas fa-bell text-xl"></i>
                        <?php if ($stats['total_pending'] > 0): ?>
                        <span class="absolute top-0 right-0 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"><?= $stats['total_pending'] ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="flex items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['admin_name'] ?? 'Admin') ?>&background=3b82f6&color=fff" 
                             class="w-10 h-10 rounded-full">
                        <div>
                            <p class="text-sm font-semibold"><?= $_SESSION['admin_name'] ?? 'Administrator' ?></p>
                            <p class="text-xs text-gray-500">Administrator</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-8">
            <!-- Metrics Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Bookings -->
                <div class="metric-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-ticket-alt text-blue-600 text-xl"></i>
                        </div>
                        <div class="flex items-center gap-1 text-green-500">
                            <i class="fas fa-arrow-up text-sm"></i>
                            <span class="text-sm font-semibold"><?= abs($stats['pemesanan_change']) ?>%</span>
                        </div>
                    </div>
                    <p class="text-gray-500 text-sm mb-1">Total Bookings</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?= $stats['total_pemesanan'] ?></h3>
                    <p class="text-xs text-gray-400 mt-2">+<?= $stats['pemesanan_hari_ini'] ?> today</p>
                </div>

                <!-- Total Revenue -->
                <div class="metric-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                        </div>
                        <div class="flex items-center gap-1 text-red-500">
                            <i class="fas fa-arrow-down text-sm"></i>
                            <span class="text-sm font-semibold"><?= abs($stats['pendapatan_change']) ?>%</span>
                        </div>
                    </div>
                    <p class="text-gray-500 text-sm mb-1">Total Revenue</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?= formatRupiah($stats['total_pendapatan']) ?></h3>
                    <p class="text-xs text-gray-400 mt-2"><?= formatRupiah($stats['pendapatan_hari_ini']) ?> today</p>
                </div>

                <!-- Active Flights -->
                <div class="metric-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-plane-departure text-purple-600 text-xl"></i>
                        </div>
                        <div class="flex items-center gap-1">
                            <i class="fas fa-circle text-green-500 text-xs animate-pulse"></i>
                            <span class="text-gray-500 text-xs font-medium">Live</span>
                        </div>
                    </div>
                    <p class="text-gray-500 text-sm mb-1">Active Flights</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?= $stats['total_penerbangan'] ?></h3>
                    <p class="text-xs text-gray-400 mt-2">Scheduled today</p>
                </div>

                <!-- Total Passengers -->
                <div class="metric-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-users text-orange-600 text-xl"></i>
                        </div>
                        <div class="flex items-center gap-1 text-green-500">
                            <i class="fas fa-arrow-up text-sm"></i>
                            <span class="text-sm font-semibold"><?= $stats['penumpang_change'] ?>%</span>
                        </div>
                    </div>
                    <p class="text-gray-500 text-sm mb-1">Total Passengers</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?= number_format($stats['total_penumpang']) ?></h3>
                    <p class="text-xs text-gray-400 mt-2">All time</p>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Revenue Overview -->
                <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-chart-line text-primary"></i>
                                Revenue Overview
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">Monthly revenue trend</p>
                        </div>
                        <div class="flex gap-2">
                            <button class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium">Monthly</button>
                            <button class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200">Weekly</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Booking Status -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fas fa-chart-pie text-primary"></i>
                        Booking Status
                    </h3>
                    <div class="flex justify-center mb-6" style="height: 200px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <span class="text-sm text-gray-600">Lunas</span>
                            </div>
                            <span class="text-sm font-semibold"><?= $stats['total_lunas'] ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                <span class="text-sm text-gray-600">Pending</span>
                            </div>
                            <span class="text-sm font-semibold"><?= $stats['total_pending'] ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                <span class="text-sm text-gray-600">Batal</span>
                            </div>
                            <span class="text-sm font-semibold"><?= $stats['total_batal'] ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity & Top Routes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Bookings -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-clock text-primary"></i>
                            Recent Bookings
                        </h3>
                        <a href="pemesanan.php" class="text-primary text-sm font-medium hover:underline">View All →</a>
                    </div>
                    <div class="space-y-4">
                        <?php if (!empty($recentBookings)): ?>
                            <?php foreach ($recentBookings as $booking): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-plane text-primary"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($booking['nama_pemesan']) ?></p>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($booking['asal'] ?? 'N/A') ?> → <?= htmlspecialchars($booking['tujuan'] ?? 'N/A') ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-sm text-accent"><?= formatRupiah($booking['total_harga']) ?></p>
                                        <span class="text-xs px-2 py-1 rounded-full <?= 
                                            $booking['status'] === 'lunas' ? 'bg-green-100 text-green-700' :
                                            ($booking['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')
                                        ?>"><?= ucfirst($booking['status']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-gray-500 py-8">No recent bookings</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Popular Routes -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-fire text-primary"></i>
                            Popular Routes
                        </h3>
                        <select class="px-3 py-1 border border-gray-200 rounded-lg text-sm">
                            <option>This Month</option>
                            <option>This Week</option>
                        </select>
                    </div>
                    <div class="space-y-4">
                        <?php if (!empty($popularRoutes)): ?>
                            <?php foreach ($popularRoutes as $index => $route): ?>
                                <div class="flex items-center gap-4">
                                    <div class="w-8 h-8 bg-gradient-to-br from-primary to-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($route['asal']) ?> → <?= htmlspecialchars($route['tujuan']) ?></p>
                                        <p class="text-xs text-gray-500"><?= $route['total_bookings'] ?> bookings</p>
                                    </div>
                                    <div class="w-24 bg-gray-200 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-primary to-blue-600 h-2 rounded-full" 
                                             style="width: <?= ($route['total_bookings'] / $maxBookings) * 100 ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-gray-500 py-8">No data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Debug: Check if Chart.js loaded
        console.log('Chart.js loaded:', typeof Chart !== 'undefined');
        
        // Wait for page to fully load
        window.addEventListener('DOMContentLoaded', function() {
            initCharts();
        });
        
        function initCharts() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart');
            if (!revenueCtx) {
                console.error('Revenue chart canvas not found');
                return;
            }
            
            const monthlyData = <?= json_encode(array_values($monthlyRevenue)) ?>;
            console.log('Monthly data:', monthlyData);
            
            try {
                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        datasets: [{
                            label: 'Revenue',
                            data: monthlyData,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 1000000) {
                                            return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                                        }
                                        return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
                console.log('Revenue chart created successfully');
            } catch (error) {
                console.error('Error creating revenue chart:', error);
            }

            // Status Chart
            const statusCtx = document.getElementById('statusChart');
            if (!statusCtx) {
                console.error('Status chart canvas not found');
                return;
            }
            
            const lunasCount = <?= $stats['total_lunas'] ?>;
            const pendingCount = <?= $stats['total_pending'] ?>;
            const batalCount = <?= $stats['total_batal'] ?>;
            
            console.log('Status data:', {lunas: lunasCount, pending: pendingCount, batal: batalCount});
            
            try {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Lunas', 'Pending', 'Batal'],
                        datasets: [{
                            data: [lunasCount, pendingCount, batalCount],
                            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const value = context.parsed;
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return context.label + ': ' + value + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        },
                        cutout: '70%'
                    }
                });
                console.log('Status chart created successfully');
            } catch (error) {
                console.error('Error creating status chart:', error);
            }
        }
        
        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            
            const texts = document.querySelectorAll('.sidebar-text');
            const labels = document.querySelectorAll('.sidebar-label');
            const upgradeSection = document.getElementById('upgrade-section');
            
            texts.forEach(el => el.classList.toggle('hidden'));
            labels.forEach(el => el.classList.toggle('hidden'));
            if (upgradeSection) upgradeSection.classList.toggle('hidden');
        }
    </script>
</body>
</html>