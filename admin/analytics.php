<?php
// admin/analytics.php
require_once '../config.php';

if (!isAdmin()) {
    redirect('login.php');
}

// Get date range (default: last 30 days)
$startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - SkyBooking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chart.js/4.4.0/chart.umd.min.js"></script>
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
        .chart-container {
            position: relative;
            height: 350px;
        }
    </style>
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
                    <a href="analytics.php" class="text-white hover:text-gray-200 transition border-b-2 border-white">Analytics</a>
                    <a href="penerbangan.php" class="text-white hover:text-gray-200 transition">Penerbangan</a>
                    <a href="pemesanan.php" class="text-white hover:text-gray-200 transition">Pemesanan</a>
                    <a href="logout.php" class="text-white hover:text-gray-200 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Header with Date Filter -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-chart-bar text-primary"></i>
                    Analytics & Reports
                </h1>
                <p class="text-gray-600 mt-2">Comprehensive business insights and performance metrics</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="bg-white rounded-lg shadow-md p-4 flex items-center gap-3">
                    <i class="fas fa-calendar text-primary"></i>
                    <div>
                        <label class="text-xs text-gray-500 block">Date Range</label>
                        <div class="flex gap-2 mt-1">
                            <input type="date" id="startDate" value="<?= $startDate ?>" 
                                   class="text-sm border-gray-300 rounded">
                            <span class="text-gray-500">to</span>
                            <input type="date" id="endDate" value="<?= $endDate ?>" 
                                   class="text-sm border-gray-300 rounded">
                        </div>
                    </div>
                    <button onclick="applyDateFilter()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition">
                        Apply
                    </button>
                </div>
                <button onclick="exportReport()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg flex items-center gap-2">
                    <i class="fas fa-file-excel"></i>
                    Export Report
                </button>
            </div>
        </div>

        <!-- Key Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-chart-line text-3xl opacity-80"></i>
                    <span class="text-sm bg-white/20 px-3 py-1 rounded-full">+12.5%</span>
                </div>
                <p class="text-blue-100 text-sm mb-1">Total Revenue</p>
                <h3 class="text-3xl font-bold" id="totalRevenue">Rp 0</h3>
                <p class="text-blue-100 text-xs mt-2">vs previous period</p>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-ticket-alt text-3xl opacity-80"></i>
                    <span class="text-sm bg-white/20 px-3 py-1 rounded-full">+8.3%</span>
                </div>
                <p class="text-green-100 text-sm mb-1">Total Bookings</p>
                <h3 class="text-3xl font-bold" id="totalBookings">0</h3>
                <p class="text-green-100 text-xs mt-2">vs previous period</p>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-chart-pie text-3xl opacity-80"></i>
                    <span class="text-sm bg-white/20 px-3 py-1 rounded-full">+15.7%</span>
                </div>
                <p class="text-purple-100 text-sm mb-1">Avg. Booking Value</p>
                <h3 class="text-3xl font-bold" id="avgBooking">Rp 0</h3>
                <p class="text-purple-100 text-xs mt-2">per transaction</p>
            </div>

            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-percentage text-3xl opacity-80"></i>
                    <span class="text-sm bg-white/20 px-3 py-1 rounded-full">+5.2%</span>
                </div>
                <p class="text-orange-100 text-sm mb-1">Conversion Rate</p>
                <h3 class="text-3xl font-bold" id="conversionRate">0%</h3>
                <p class="text-orange-100 text-xs mt-2">booking success rate</p>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Revenue Trend -->
            <div class="bg-white rounded-2xl p-6 shadow-md">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Revenue Trend</h3>
                    <select class="px-3 py-2 border rounded-lg text-sm" onchange="updateRevenueTrend(this.value)">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly" selected>Monthly</option>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>

            <!-- Booking Distribution -->
            <div class="bg-white rounded-2xl p-6 shadow-md">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Booking Distribution</h3>
                    <button class="text-primary hover:underline text-sm">View Details</button>
                </div>
                <div class="chart-container">
                    <canvas id="bookingDistChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Top Airlines -->
            <div class="bg-white rounded-2xl p-6 shadow-md">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Top Airlines</h3>
                <div class="chart-container">
                    <canvas id="airlinesChart"></canvas>
                </div>
            </div>

            <!-- Peak Hours -->
            <div class="bg-white rounded-2xl p-6 shadow-md">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Peak Booking Hours</h3>
                <div class="chart-container">
                    <canvas id="peakHoursChart"></canvas>
                </div>
            </div>

            <!-- Customer Demographics -->
            <div class="bg-white rounded-2xl p-6 shadow-md">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Booking Status</h3>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detailed Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Routes Performance -->
            <div class="bg-white rounded-2xl p-6 shadow-md">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Top Routes Performance</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Route</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600">Bookings</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">Revenue</th>
                            </tr>
                        </thead>
                        <tbody id="topRoutesTable">
                            <!-- Will be populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Revenue by Payment Method -->
            <div class="bg-white rounded-2xl p-6 shadow-md">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Performance Metrics</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-600">Occupancy Rate</p>
                            <p class="text-2xl font-bold text-gray-800">78.5%</p>
                        </div>
                        <div class="w-20 h-20">
                            <canvas id="occupancyMini"></canvas>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-600">On-Time Performance</p>
                            <p class="text-2xl font-bold text-gray-800">92.3%</p>
                        </div>
                        <div class="w-20 h-20">
                            <canvas id="ontimeMini"></canvas>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-600">Customer Satisfaction</p>
                            <p class="text-2xl font-bold text-gray-800">4.7/5.0</p>
                        </div>
                        <div class="flex gap-1">
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star-half-alt text-yellow-400"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Revenue Trend Chart
        const revenueTrendCtx = document.getElementById('revenueTrendChart');
        const revenueTrendChart = new Chart(revenueTrendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Revenue',
                    data: [1200000, 1900000, 1500000, 2200000, 2800000, 2400000, 3100000, 2900000, 3400000, 3800000, 3600000, 4200000],
                    borderColor: '#0066cc',
                    backgroundColor: 'rgba(0, 102, 204, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Booking Distribution Chart
        new Chart(document.getElementById('bookingDistChart'), {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Bookings',
                    data: [45, 52, 38, 65, 72, 85, 68],
                    backgroundColor: 'rgba(0, 102, 204, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Top Airlines Chart
        new Chart(document.getElementById('airlinesChart'), {
            type: 'doughnut',
            data: {
                labels: ['Garuda', 'Lion Air', 'Citilink', 'AirAsia', 'Others'],
                datasets: [{
                    data: [35, 28, 18, 12, 7],
                    backgroundColor: ['#0066cc', '#f59e0b', '#10b981', '#8b5cf6', '#6b7280']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Peak Hours Chart
        new Chart(document.getElementById('peakHoursChart'), {
            type: 'line',
            data: {
                labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
                datasets: [{
                    label: 'Bookings',
                    data: [5, 8, 45, 38, 52, 28],
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Status Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Lunas', 'Pending', 'Batal'],
                datasets: [{
                    data: [70, 25, 5],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%'
            }
        });

        // Mini charts
        new Chart(document.getElementById('occupancyMini'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [78.5, 21.5],
                    backgroundColor: ['#0066cc', '#e5e7eb'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                cutout: '75%'
            }
        });

        new Chart(document.getElementById('ontimeMini'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [92.3, 7.7],
                    backgroundColor: ['#10b981', '#e5e7eb'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                cutout: '75%'
            }
        });

        // Load analytics data
        async function loadAnalytics() {
            try {
                const response = await fetch('api/get_analytics_data.php');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('totalRevenue').textContent = formatRupiah(data.totalRevenue);
                    document.getElementById('totalBookings').textContent = data.totalBookings;
                    document.getElementById('avgBooking').textContent = formatRupiah(data.avgBooking);
                    document.getElementById('conversionRate').textContent = data.conversionRate + '%';
                    
                    // Update top routes table
                    const tbody = document.getElementById('topRoutesTable');
                    tbody.innerHTML = data.topRoutes.map(route => `
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium">${route.route}</td>
                            <td class="px-4 py-3 text-sm text-center">${route.bookings}</td>
                            <td class="px-4 py-3 text-sm text-right font-semibold">${formatRupiah(route.revenue)}</td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading analytics:', error);
            }
        }

        function formatRupiah(angka) {
            return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
        }

        function applyDateFilter() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            window.location.href = `analytics.php?start=${start}&end=${end}`;
        }

        function exportReport() {
            alert('Export functionality will download Excel report');
            // Implement actual export logic
        }

        function updateRevenueTrend(period) {
            // Update chart based on period
            console.log('Updating to:', period);
        }

        window.addEventListener('DOMContentLoaded', loadAnalytics);
    </script>
</body>
</html>