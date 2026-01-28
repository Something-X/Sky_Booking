<?php
// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Get pending count if not already set
if (!isset($pending_count)) {
    $pending_result = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE status = 'pending'");
    $pending_count = $pending_result ? $pending_result->fetch_assoc()['total'] : 0;
}

// Get open support tickets count
$support_result = $conn->query("SELECT COUNT(*) as total FROM support_tickets WHERE status = 'open'");
$support_count = $support_result ? $support_result->fetch_assoc()['total'] : 0;
?>

<style>
    /* Animasi untuk sidebar */
    .sidebar {
        transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        width: 260px;
        overflow: hidden;
    }

    .sidebar.collapsed {
        width: 95px;
    }

    /* Header logo area */
    .sidebar-header {
        transition: padding 0.4s ease;
    }

    .sidebar.collapsed .sidebar-header {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    /* Logo container */
    .logo-container {
        transition: all 0.4s ease;
        overflow: hidden;
        min-height: 2.5rem;
    }

    .sidebar.collapsed .logo-container {
        justify-content: center;
        width: 100%;
    }

    .logo-icon {
        transition: all 0.3s ease;
    }

    .sidebar.collapsed .logo-icon {
        margin: 0 auto;
    }

    /* Animasi untuk teks dan label */
    .sidebar-text,
    .sidebar-label {
        opacity: 1;
        transition: opacity 0.2s ease-in-out, width 0.3s ease;
        white-space: nowrap;
        overflow: hidden;
    }

    .sidebar.collapsed .sidebar-text,
    .sidebar.collapsed .sidebar-label {
        opacity: 0;
        width: 0;
    }

    /* Animasi untuk logo text */
    #logo-text {
        transition: opacity 0.2s ease-in-out, width 0.3s ease;
        overflow: hidden;
    }

    .sidebar.collapsed #logo-text {
        opacity: 0;
        width: 0;
    }

    /* Toggle button animation */
    .toggle-btn {
        transition: transform 0.3s ease;
    }

    .sidebar.collapsed .toggle-btn {
        transform: rotate(180deg);
    }

    /* Nav container */
    .sidebar-nav {
        transition: padding 0.4s ease;
    }

    .sidebar.collapsed .sidebar-nav {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    /* Sidebar items */
    .sidebar-item {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .sidebar.collapsed .sidebar-item {
        justify-content: center;
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .sidebar-item:hover {
        background-color: rgba(59, 130, 246, 0.1);
        color: #60a5fa;
        transform: translateX(4px);
    }

    .sidebar.collapsed .sidebar-item:hover {
        transform: translateX(0) scale(1.1);
    }

    .sidebar-item:hover i {
        color: #60a5fa;
        transform: scale(1.05);
    }

    /* Active state */
    .sidebar-item.active {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(37, 99, 235, 0.15));
        color: #60a5fa !important;
        border-left: 3px solid #3b82f6;
        font-weight: 600;
    }

    .sidebar.collapsed .sidebar-item.active {
        border-left: none;
        border-radius: 0.5rem;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.3), rgba(37, 99, 235, 0.25));
    }

    .sidebar-item.active i {
        color: #3b82f6;
    }

    /* Icon styles */
    .sidebar-item i {
        transition: all 0.3s ease;
        font-size: 1.1rem;
        min-width: 1.25rem;
        text-align: center;
    }

    .sidebar.collapsed .sidebar-item i {
        font-size: 1.25rem;
    }

    /* Badge animasi */
    .badge-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        transition: opacity 0.3s ease;
    }

    .sidebar.collapsed .badge-pulse {
        position: absolute;
        top: 8px;
        right: 8px;
        opacity: 1;
        padding: 0.125rem 0.375rem;
        font-size: 0.65rem;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: .7;
        }
    }

    /* Tooltip untuk collapsed sidebar */
    .sidebar.collapsed .sidebar-item::before {
        content: attr(data-tooltip);
        position: absolute;
        left: calc(100% + 15px);
        top: 50%;
        transform: translateY(-50%) translateX(-10px);
        padding: 8px 12px;
        background-color: #1f2937;
        color: white;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        z-index: 1000;
    }

    .sidebar.collapsed .sidebar-item::after {
        content: '';
        position: absolute;
        left: calc(100% + 8px);
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 6px 7px 6px 0;
        border-color: transparent #1f2937 transparent transparent;
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1000;
    }

    .sidebar.collapsed .sidebar-item:hover::before {
        opacity: 1;
        transform: translateY(-50%) translateX(0);
    }

    .sidebar.collapsed .sidebar-item:hover::after {
        opacity: 1;
    }

    /* Smooth transitions for all elements */
    * {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
</style>

<aside id="sidebar" class="sidebar bg-sidebar h-screen sticky top-0 flex flex-col shadow-2xl">
    <div class="sidebar-header p-6 flex items-center justify-between border-b border-gray-700">
        <a href="dashboard.php" class="logo-container flex items-center gap-3">
            <div class="logo-icon w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-plane text-white text-base"></i>
            </div>
            <span id="logo-text" class="text-white text-xl font-bold">SkyBooking</span>
        </a>
        <button onclick="toggleSidebar()" class="toggle-btn text-gray-400 hover:text-white transition-all duration-300 flex-shrink-0">
            <i class="fas fa-chevron-left text-sm"></i>
        </button>
    </div>

    <nav class="sidebar-nav flex-1 p-4 overflow-y-auto overflow-x-hidden">
        <div class="mb-6">
            <p class="text-gray-500 text-xs uppercase mb-3 px-4 sidebar-label font-semibold tracking-wider">GENERAL</p>
            <a href="dashboard.php"
                class="sidebar-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2"
                data-tooltip="Dashboard">
                <i class="fas fa-home flex-shrink-0"></i>
                <span class="sidebar-text">Dashboard</span>
            </a>
            <a href="penerbangan.php"
                class="sidebar-item <?= $current_page === 'penerbangan.php' ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2"
                data-tooltip="Penerbangan">
                <i class="fas fa-plane-departure flex-shrink-0"></i>
                <span class="sidebar-text">Penerbangan</span>
            </a>
            <a href="kelola_penerbangan.php"
                class="sidebar-item <?= $current_page === 'kelola_penerbangan.php' ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2"
                data-tooltip="Penerbangan">
                <i class="fas fa-plane-departure flex-shrink-0"></i>
                <span class="sidebar-text">Kelola Penerbangan</span>
            </a>
            <a href="pemesanan.php"
                class="sidebar-item <?= $current_page === 'pemesanan.php' ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2"
                data-tooltip="Pemesanan">
                <i class="fas fa-ticket-alt flex-shrink-0"></i>
                <span class="sidebar-text">Pemesanan</span>
                <?php if ($pending_count > 0): ?>
                    <span class="sidebar-text badge-pulse ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full flex-shrink-0">
                        <?= $pending_count ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="flight_tracking.php"
                class="sidebar-item <?= $current_page === 'flight_tracking.php' ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2"
                data-tooltip="Live Tracking">
                <i class="fas fa-map-marked-alt flex-shrink-0"></i>
                <span class="sidebar-text">Live Tracking</span>
            </a>
        </div>

        <div class="mb-6">
            <p class="text-gray-500 text-xs uppercase mb-3 px-4 sidebar-label font-semibold tracking-wider">SUPPORT</p>
            <a href="reports.php"
                class="sidebar-item <?= $current_page === 'reports.php' ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2"
                data-tooltip="Support Reports">
                <i class="fas fa-headset flex-shrink-0"></i>
                <span class="sidebar-text">Support Reports</span>
                <?php if ($support_count > 0): ?>
                    <span class="sidebar-text badge-pulse ml-auto bg-orange-500 text-white text-xs px-2 py-1 rounded-full flex-shrink-0">
                        <?= $support_count ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>

        <div class="mb-6">
            <p class="text-gray-500 text-xs uppercase mb-3 px-4 sidebar-label font-semibold tracking-wider">ANALYTICS</p>
            <a href="analytics.php"
                class="sidebar-item <?= $current_page === 'analytics.php' ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2"
                data-tooltip="Analytics">
                <i class="fas fa-chart-line flex-shrink-0"></i>
                <span class="sidebar-text">Analytics</span>
            </a>
            <a href="reports.php"
                class="sidebar-item <?= $current_page === 'reports.php' ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2"
                data-tooltip="Reports">
                <i class="fas fa-file-alt flex-shrink-0"></i>
                <span class="sidebar-text">Reports</span>
            </a>
        </div>

        <div>
            <p class="text-gray-500 text-xs uppercase mb-3 px-4 sidebar-label font-semibold tracking-wider">SETTINGS</p>
            <a href="settings.php"
                class="sidebar-item <?= $current_page === 'settings.php' ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 mb-2"
                data-tooltip="Settings">
                <i class="fas fa-cog flex-shrink-0"></i>
                <span class="sidebar-text">Settings</span>
            </a>
            <a href="logout.php"
                class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300"
                data-tooltip="Logout">
                <i class="fas fa-sign-out-alt flex-shrink-0"></i>
                <span class="sidebar-text">Logout</span>
            </a>
        </div>
    </nav>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');

        // Simpan state ke localStorage
        if (sidebar.classList.contains('collapsed')) {
            localStorage.setItem('sidebarCollapsed', 'true');
        } else {
            localStorage.setItem('sidebarCollapsed', 'false');
        }
    }

    // Load state dari localStorage saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }
    });
</script>