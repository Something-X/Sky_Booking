<?php
session_start();
require_once 'config.php';
$user = getUserData();

// Cek apakah user login
if (!$user) {
    $showLoginModal = true;
} else {
    $showLoginModal = false;
}
// ...existing code...

// Get search params
$asal = $_GET['asal'] ?? '';
$tujuan = $_GET['tujuan'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';
$jumlah = (int)($_GET['jumlah'] ?? 1);
$kelas = $_GET['kelas'] ?? 'Economy';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian - SkyBooking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/search_results.css">
</head>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background: #f8fafc;
    }

    .flight-detail {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }

    .flight-detail.active {
        max-height: 600px;
    }

    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 9999;
        animation: fadeIn 0.3s ease-out;
    }

    .modal-overlay.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        border-radius: 20px;
        padding: 0;
        max-width: 450px;
        width: 90%;
        box-shadow: 0 20px 60px rgba(30, 58, 138, 0.3);
        animation: slideUp 0.3s ease-out;
        overflow: hidden;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .search-summary {
        background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(30, 58, 138, 0.2);
    }

    .filter-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s;
    }

    .flight-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s;
        border: 2px solid transparent;
    }

    .flight-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(30, 58, 138, 0.15);
        border-color: #3b82f6;
    }

    .airline-logo {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .book-button {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 12px 28px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .book-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    }

    .filter-checkbox:checked {
        background: #3b82f6;
        border-color: #3b82f6;
    }

    .loading-spinner {
        border: 4px solid #dbeafe;
        border-top: 4px solid #3b82f6;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .price-badge {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .detail-toggle {
        color: #3b82f6;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .detail-toggle:hover {
        color: #2563eb;
    }

    .result-header {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    select {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 16px;
        font-size: 14px;
        transition: all 0.3s;
    }

    select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .info-badge {
        background: #eff6ff;
        color: #1e3a8a;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
    }
</style>

<body data-logged-in="<?= $user ? 'true' : 'false' ?>">
    <?php include 'includes/navbar.php'; ?>

    <!-- Modern Login Required Modal -->
    <div id="loginModal" class="modal-overlay<?= $showLoginModal ? ' active' : '' ?>">
        <div class="modal-content">
            <!-- Header with Gradient -->
            <div class="p-8 text-center" style="background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);">
                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-lock text-4xl text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-white">Login Required</h3>
                <p class="text-blue-100 mt-2">Please login to continue booking</p>
            </div>

            <!-- Body -->
            <div class="p-8 text-center">
                <p class="text-gray-600 text-lg mb-2">You need to login first</p>
                <p class="text-gray-500 text-sm">to book flight tickets</p>
            </div>

            <!-- Buttons -->
            <div class="px-8 pb-8 flex gap-4">
                <button onclick="closeLoginModal()"
                    class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition duration-300">
                    Cancel
                </button>
                <button onclick="redirectToLogin()"
                    class="flex-1 px-6 py-3 text-white font-bold rounded-lg hover:shadow-lg transform hover:scale-105 transition duration-300"
                    style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login Now
                </button>
            </div>
        </div>
    </div>

    <!-- Search Summary -->
    <div class="search-summary py-6 mt-[40px]">
        <div class="container mx-auto px-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-6 text-sm">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-plane-departure text-blue-200"></i>
                        <span class="text-blue-100">From:</span>
                        <span class="font-bold"><?= htmlspecialchars($asal) ?></span>
                    </div>
                    <i class="fas fa-arrow-right text-blue-200"></i>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-plane-arrival text-blue-200"></i>
                        <span class="text-blue-100">To:</span>
                        <span class="font-bold"><?= htmlspecialchars($tujuan) ?></span>
                    </div>
                    <div class="border-l border-blue-400 pl-6 flex items-center gap-2">
                        <i class="fas fa-calendar text-blue-200"></i>
                        <span class="text-blue-100">Date:</span>
                        <span class="font-bold"><?= date('d M Y', strtotime($tanggal)) ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-users text-blue-200"></i>
                        <span class="text-blue-100">Passengers:</span>
                        <span class="font-bold"><?= $jumlah ?></span>
                    </div>
                    <div class="border-l border-blue-400 pl-6 flex items-center gap-2">
                        <i class="fas fa-couch text-blue-200"></i>
                        <span class="text-blue-100">Class:</span>
                        <span class="font-bold"><?= htmlspecialchars($kelas) ?></span>
                    </div>
                </div>
                <button onclick="window.location.href='index.php'"
                    class="bg-white text-blue-900 px-5 py-2.5 rounded-lg font-semibold hover:bg-blue-50 transition flex items-center gap-2">
                    <i class="fas fa-edit"></i>
                    Modify Search
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar Filter -->
            <div class="lg:col-span-1">
                <div class="filter-card p-6 sticky top-4">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold" style="color: #1e3a8a;">
                            <i class="fas fa-filter mr-2" style="color: #3b82f6;"></i>Filters
                        </h3>
                        <button id="resetFilter" class="text-sm font-semibold hover:underline" style="color: #3b82f6;">Reset All</button>
                    </div>

                    <!-- Airline Filter -->
                    <div class="mb-6">
                        <h4 class="font-bold mb-3 text-sm" style="color: #1e3a8a;">Airlines</h4>
                        <div id="airlineFilters" class="space-y-2.5"></div>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-6">
                        <h4 class="font-bold mb-3 text-sm" style="color: #1e3a8a;">Price Range</h4>
                        <div class="space-y-2.5">
                            <label class="flex items-center cursor-pointer text-sm group">
                                <input type="radio" name="priceRange" class="filter-price filter-checkbox mr-3 w-4 h-4" value="0-1000000" checked>
                                <span class="text-gray-700 group-hover:text-blue-600 transition">
                                    < Rp 1 Million</span>
                            </label>
                            <label class="flex items-center cursor-pointer text-sm group">
                                <input type="radio" name="priceRange" class="filter-price filter-checkbox mr-3 w-4 h-4" value="1000000-2000000">
                                <span class="text-gray-700 group-hover:text-blue-600 transition">Rp 1-2 Million</span>
                            </label>
                            <label class="flex items-center cursor-pointer text-sm group">
                                <input type="radio" name="priceRange" class="filter-price filter-checkbox mr-3 w-4 h-4" value="2000000-99999999">
                                <span class="text-gray-700 group-hover:text-blue-600 transition">> Rp 2 Million</span>
                            </label>
                        </div>
                    </div>

                    <!-- Time Filter -->
                    <div class="mb-6">
                        <h4 class="font-bold mb-3 text-sm" style="color: #1e3a8a;">Departure Time</h4>
                        <div class="space-y-2.5">
                            <label class="flex items-center cursor-pointer text-sm group">
                                <input type="checkbox" class="filter-time filter-checkbox mr-3 w-4 h-4" value="00:00-06:00">
                                <span class="text-gray-700 group-hover:text-blue-600 transition">🌙 Night 00:00-06:00</span>
                            </label>
                            <label class="flex items-center cursor-pointer text-sm group">
                                <input type="checkbox" class="filter-time filter-checkbox mr-3 w-4 h-4" value="06:00-12:00">
                                <span class="text-gray-700 group-hover:text-blue-600 transition">🌅 Morning 06:00-12:00</span>
                            </label>
                            <label class="flex items-center cursor-pointer text-sm group">
                                <input type="checkbox" class="filter-time filter-checkbox mr-3 w-4 h-4" value="12:00-18:00">
                                <span class="text-gray-700 group-hover:text-blue-600 transition">☀️ Afternoon 12:00-18:00</span>
                            </label>
                            <label class="flex items-center cursor-pointer text-sm group">
                                <input type="checkbox" class="filter-time filter-checkbox mr-3 w-4 h-4" value="18:00-24:00">
                                <span class="text-gray-700 group-hover:text-blue-600 transition">🌃 Evening 18:00-24:00</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flight Results -->
            <div class="lg:col-span-3">
                <!-- Header -->
                <div class="result-header p-5 mb-6">
                    <div class="flex flex-wrap justify-between items-center gap-4">
                        <div>
                            <h2 class="text-2xl font-bold" style="color: #1e3a8a;">
                                <i class="fas fa-plane mr-2" style="color: #3b82f6;"></i>
                                <span id="resultCount">0</span> Flights Available
                            </h2>
                            <p class="text-gray-500 text-sm mt-1">Best prices for your journey</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-2">Sort by:</label>
                            <select id="sortSelect" class="font-medium" style="color: #1e3a8a;">
                                <option value="price-asc">Lowest Price</option>
                                <option value="price-desc">Highest Price</option>
                                <option value="time-asc">Earliest Departure</option>
                                <option value="time-desc">Latest Departure</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div id="loadingSpinner" class="text-center py-16">
                    <div class="loading-spinner mx-auto mb-4"></div>
                    <p class="text-gray-600 font-semibold">Finding best flights for you...</p>
                    <p class="text-gray-500 text-sm mt-2">Please wait a moment</p>
                </div>

                <!-- Results Container -->
                <div id="resultsContainer"></div>
            </div>
        </div>
    </div>

    <script>
        const jumlah = <?= $jumlah ?>;
    </script>
    <script src="assets/js/search_results.js"></script>
</body>

</html>