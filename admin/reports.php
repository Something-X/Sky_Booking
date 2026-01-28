<?php
require_once '../config.php'; // ✅ Path benar untuk file di folder admin/

if (!isAdmin()) {
    redirect('login.php');
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$kategori_filter = $_GET['kategori'] ?? 'all';
$prioritas_filter = $_GET['prioritas'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$types = '';

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($kategori_filter !== 'all') {
    $where_conditions[] = "kategori = ?";
    $params[] = $kategori_filter;
    $types .= 's';
}

if ($prioritas_filter !== 'all') {
    $where_conditions[] = "prioritas = ?";
    $params[] = $prioritas_filter;
    $types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(nama_user LIKE ? OR email LIKE ? OR subjek LIKE ? OR pesan LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= 'ssss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get tickets
$query = "SELECT * FROM support_tickets $where_clause ORDER BY 
    CASE 
        WHEN prioritas = 'high' THEN 1
        WHEN prioritas = 'medium' THEN 2
        WHEN prioritas = 'low' THEN 3
    END,
    CASE
        WHEN status = 'open' THEN 1
        WHEN status = 'in_progress' THEN 2
        WHEN status = 'resolved' THEN 3
        WHEN status = 'closed' THEN 4
    END,
    created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tickets = $stmt->get_result();
$stmt->close();

// Get statistics - SINGLE LINE VERSION (avoid line break issues)
$stats_query = "
SELECT 
    COUNT(*) AS `total`,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS `open_count`,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS `in_progress`,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS `resolved`,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) AS `closed_count`,
    SUM(CASE WHEN prioritas = 'high' THEN 1 ELSE 0 END) AS `high_priority`
FROM support_tickets
";


$stats_result = $conn->query($stats_query);

if (!$stats_result) {
    die("Query Error: " . $conn->error . "<br><br>Query: <pre>" . htmlspecialchars($stats_query) . "</pre>");
}

$stats = $stats_result->fetch_assoc();

// Set defaults if no data
if ($stats['total'] == 0) {
    $stats = [
        'total' => 0,
        'open_count' => 0,
        'in_progress' => 0,
        'resolved' => 0,
        'closed_count' => 0,
        'high_priority' => 0
    ];
}

// Rename for backward compatibility
$stats['open'] = $stats['open_count'];
$stats['closed'] = $stats['closed_count'];

// Get pending count for sidebar
$pending_result = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE status = 'pending'");
$pending_count = $pending_result->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Reports - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        }

        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .ticket-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .ticket-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-open {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }

        .status-in_progress {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }

        .status-resolved {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .status-closed {
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            color: #1f2937;
        }

        .priority-high {
            color: #dc2626;
            background: #fee2e2;
        }

        .priority-medium {
            color: #d97706;
            background: #fef3c7;
        }

        .priority-low {
            color: #059669;
            background: #d1fae5;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
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
    </style>
</head>

<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1">
            <!-- Header -->
            <header class="bg-white shadow-md sticky top-0 z-50">
                <div class="px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800">Support Reports</h1>
                            <p class="text-gray-600 mt-1">Kelola dan pantau keluhan customer</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-sm text-gray-600">Admin</p>
                                <p class="font-semibold text-gray-800"><?= htmlspecialchars($_SESSION['admin_name']) ?></p>
                            </div>
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-shield text-white text-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
                    <div class="stat-card bg-white rounded-xl p-6 shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Total Tickets</p>
                                <p class="text-3xl font-bold text-gray-800 mt-2"><?= $stats['total'] ?></p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-ticket-alt text-white text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white rounded-xl p-6 shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Open</p>
                                <p class="text-3xl font-bold text-yellow-600 mt-2"><?= $stats['open'] ?></p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-clock text-white text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white rounded-xl p-6 shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">In Progress</p>
                                <p class="text-3xl font-bold text-blue-600 mt-2"><?= $stats['in_progress'] ?></p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-spinner text-white text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white rounded-xl p-6 shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Resolved</p>
                                <p class="text-3xl font-bold text-green-600 mt-2"><?= $stats['resolved'] ?></p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-check-circle text-white text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white rounded-xl p-6 shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Closed</p>
                                <p class="text-3xl font-bold text-gray-600 mt-2"><?= $stats['closed'] ?></p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-times-circle text-white text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white rounded-xl p-6 shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">High Priority</p>
                                <p class="text-3xl font-bold text-red-600 mt-2"><?= $stats['high_priority'] ?></p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl p-6 shadow-md mb-8">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Semua Status</option>
                                <option value="open" <?= $status_filter === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                            <select name="kategori" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?= $kategori_filter === 'all' ? 'selected' : '' ?>>Semua Kategori</option>
                                <option value="Pemesanan" <?= $kategori_filter === 'Pemesanan' ? 'selected' : '' ?>>Pemesanan</option>
                                <option value="Pembayaran" <?= $kategori_filter === 'Pembayaran' ? 'selected' : '' ?>>Pembayaran</option>
                                <option value="Penerbangan" <?= $kategori_filter === 'Penerbangan' ? 'selected' : '' ?>>Penerbangan</option>
                                <option value="Akun" <?= $kategori_filter === 'Akun' ? 'selected' : '' ?>>Akun</option>
                                <option value="Lainnya" <?= $kategori_filter === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Prioritas</label>
                            <select name="prioritas" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?= $prioritas_filter === 'all' ? 'selected' : '' ?>>Semua Prioritas</option>
                                <option value="high" <?= $prioritas_filter === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="medium" <?= $prioritas_filter === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="low" <?= $prioritas_filter === 'low' ? 'selected' : '' ?>>Low</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <a href="reports.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Tickets List -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">ID</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">User</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Kategori</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Subjek</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Prioritas</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Tanggal</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($ticket = $tickets->fetch_assoc()): ?>
                                    <tr class="ticket-card hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">#<?= $ticket['id'] ?></td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($ticket['nama_user']) ?></p>
                                                <p class="text-xs text-gray-500"><?= htmlspecialchars($ticket['email']) ?></p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                <?= htmlspecialchars($ticket['kategori']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                            <?= htmlspecialchars($ticket['subjek']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full priority-<?= $ticket['prioritas'] ?>">
                                                <?= strtoupper($ticket['prioritas']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="badge-status status-<?= $ticket['status'] ?>">
                                                <i class="fas fa-circle text-xs"></i>
                                                <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <?= date('d M Y, H:i', strtotime($ticket['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <button onclick="viewTicket(<?= $ticket['id'] ?>)" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                                <i class="fas fa-eye mr-1"></i>Detail
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                                <?php if ($tickets->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                                            <p>Tidak ada ticket yang ditemukan</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Detail Ticket -->
    <div id="ticketModal" class="modal">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-800">Detail Ticket</h2>
                    <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>
            <div id="modalContent" class="p-6">
                <!-- Content will be loaded here via AJAX -->
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
                    <p class="mt-4 text-gray-600">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewTicket(id) {
            console.log('Viewing ticket:', id);
            const modal = document.getElementById('ticketModal');
            const modalContent = document.getElementById('modalContent');

            modal.classList.add('active');

            // Load ticket detail via AJAX
            fetch(`api/get_ticket_detail.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalContent.innerHTML = createTicketDetailHTML(data.ticket);
                    } else {
                        modalContent.innerHTML = `
                            <div class="text-center py-8">
                                <i class="fas fa-exclamation-circle text-4xl text-red-500"></i>
                                <p class="mt-4 text-gray-600">${data.message}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalContent.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-4xl text-yellow-500"></i>
                            <p class="mt-4 text-gray-600">Terjadi kesalahan saat memuat data</p>
                        </div>
                    `;
                });
        }

        function createTicketDetailHTML(ticket) {
            return `
                <div class="space-y-6">
                    <!-- Header Info -->
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Ticket #${ticket.id}</h3>
                            <p class="text-sm text-gray-500 mt-1">${formatDate(ticket.created_at)}</p>
                        </div>
                        <div class="flex gap-2">
                            <span class="badge-status status-${ticket.status}">
                                <i class="fas fa-circle text-xs"></i>
                                ${formatStatus(ticket.status)}
                            </span>
                            <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full priority-${ticket.prioritas}">
                                ${ticket.prioritas.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    
                    <!-- User Info -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-3">Informasi User</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Nama</p>
                                <p class="font-medium text-gray-800">${escapeHtml(ticket.nama_user)}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p class="font-medium text-gray-800">${escapeHtml(ticket.email)}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Kategori</p>
                                <p class="font-medium text-gray-800">${escapeHtml(ticket.kategori)}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">User ID</p>
                                <p class="font-medium text-gray-800">${ticket.user_id || 'Guest'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ticket Content -->
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">Subjek</h4>
                        <p class="text-gray-700">${escapeHtml(ticket.subjek)}</p>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">Pesan</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-700 whitespace-pre-wrap">${escapeHtml(ticket.pesan)}</p>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="border-t border-gray-200 pt-6">
                        <h4 class="font-semibold text-gray-800 mb-4">Update Status</h4>
                        <form onsubmit="updateTicketStatus(event, ${ticket.id})" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="open" ${ticket.status === 'open' ? 'selected' : ''}>Open</option>
                                    <option value="in_progress" ${ticket.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                                    <option value="resolved" ${ticket.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                                    <option value="closed" ${ticket.status === 'closed' ? 'selected' : ''}>Closed</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Opsional)</label>
                                <textarea name="admin_notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Tambahkan catatan...">${ticket.admin_notes || ''}</textarea>
                            </div>
                            
                            <div class="flex gap-3">
                                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                                    <i class="fas fa-save mr-2"></i>Update Status
                                </button>
                                <button type="button" onclick="closeModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                                    Tutup
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
        }

        function updateTicketStatus(event, ticketId) {
            event.preventDefault();

            const formData = new FormData(event.target);
            formData.append('ticket_id', ticketId);

            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
            submitBtn.disabled = true;

            fetch('api/update_ticket_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Status ticket berhasil diupdate!');
                        location.reload();
                    } else {
                        alert(data.message || 'Gagal update status');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat update status');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        }

        function closeModal() {
            document.getElementById('ticketModal').classList.remove('active');
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleDateString('id-ID', options);
        }

        function formatStatus(status) {
            return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal when clicking outside
        document.getElementById('ticketModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>

</html>