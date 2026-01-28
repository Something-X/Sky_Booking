<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$ticket_id = isset($_GET['ticket']) ? (int)$_GET['ticket'] : 0;

// Get ticket detail
$ticket = null;
$responses = [];

if ($ticket_id > 0) {
    $sql = "SELECT st.*, 
            (SELECT COUNT(*) FROM support_responses WHERE ticket_id = st.id) as response_count
            FROM support_tickets st
            WHERE st.id = ? AND st.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $ticket_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    $stmt->close();
    
    if ($ticket) {
        // Get responses
        $sql_resp = "SELECT sr.*, a.nama_lengkap as admin_name
                     FROM support_responses sr
                     LEFT JOIN admin a ON sr.admin_id = a.id
                     WHERE sr.ticket_id = ?
                     ORDER BY sr.created_at ASC";
        
        $stmt = $conn->prepare($sql_resp);
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $responses[] = $row;
        }
        $stmt->close();
        
        // Mark all responses as read
        $sql_mark = "UPDATE support_responses SET read_by_user = 1 WHERE ticket_id = ?";
        $stmt = $conn->prepare($sql_mark);
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Get all user tickets
$sql_all = "SELECT st.*, 
            (SELECT COUNT(*) FROM support_responses WHERE ticket_id = st.id AND read_by_user = 0) as unread_count
            FROM support_tickets st
            WHERE st.user_id = ?
            ORDER BY st.created_at DESC";

$stmt = $conn->prepare($sql_all);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$all_tickets = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Support Tickets - SkyBooking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-24">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">My Support Tickets</h1>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Ticket List (Sidebar) -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-4 bg-gradient-to-r from-blue-600 to-blue-700">
                            <h2 class="text-white font-bold">All Tickets</h2>
                        </div>
                        
                        <div class="divide-y divide-gray-100">
                            <?php while ($t = $all_tickets->fetch_assoc()): ?>
                                <a href="?ticket=<?= $t['id'] ?>" 
                                   class="block p-4 hover:bg-gray-50 transition-colors <?= $t['id'] == $ticket_id ? 'bg-blue-50' : '' ?>">
                                    <div class="flex items-start justify-between mb-2">
                                        <span class="text-sm font-semibold text-gray-800">#<?= $t['id'] ?> - <?= htmlspecialchars($t['subjek']) ?></span>
                                        <?php if ($t['unread_count'] > 0): ?>
                                            <span class="bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                                                <?= $t['unread_count'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
                                            <?= $t['kategori'] ?>
                                        </span>
                                        <span class="status-<?= $t['status'] ?> inline-flex px-2 py-0.5 rounded-full text-xs font-medium">
                                            <?= ucfirst($t['status']) ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-2">
                                        <?= date('d M Y, H:i', strtotime($t['created_at'])) ?>
                                    </p>
                                </a>
                            <?php endwhile; ?>
                            
                            <?php if ($all_tickets->num_rows === 0): ?>
                                <div class="p-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>No tickets yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Ticket Detail -->
                <div class="lg:col-span-2">
                    <?php if ($ticket): ?>
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <!-- Ticket Header -->
                            <div class="p-6 border-b border-gray-200">
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <h2 class="text-2xl font-bold text-gray-800 mb-2">
                                            <?= htmlspecialchars($ticket['subjek']) ?>
                                        </h2>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-sm font-medium">
                                                <?= $ticket['kategori'] ?>
                                            </span>
                                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-bold
                                                         <?php
                                                         echo $ticket['status'] === 'open' ? 'bg-yellow-100 text-yellow-800' :
                                                              ($ticket['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                                                               ($ticket['status'] === 'resolved' ? 'bg-green-100 text-green-800' :
                                                                'bg-gray-100 text-gray-800'));
                                                         ?>">
                                                <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                            </span>
                                            <span class="text-sm text-gray-500">
                                                <?= date('d M Y, H:i', strtotime($ticket['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Original Message -->
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($ticket['pesan']) ?></p>
                                </div>
                            </div>
                            
                            <!-- Responses -->
                            <div class="p-6">
                                <h3 class="font-bold text-lg text-gray-800 mb-4">
                                    Responses (<?= count($responses) ?>)
                                </h3>
                                
                                <?php if (count($responses) > 0): ?>
                                    <div class="space-y-4">
                                        <?php foreach ($responses as $response): ?>
                                            <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-4">
                                                <div class="flex items-start gap-3">
                                                    <div class="flex-shrink-0">
                                                        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">
                                                            <i class="fas fa-user-shield"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <span class="font-semibold text-gray-800">
                                                                <?= htmlspecialchars($response['admin_name'] ?? 'Admin') ?>
                                                            </span>
                                                            <span class="text-xs text-gray-500">
                                                                <?= date('d M Y, H:i', strtotime($response['created_at'])) ?>
                                                            </span>
                                                        </div>
                                                        <p class="text-gray-700 whitespace-pre-wrap">
                                                            <?= htmlspecialchars($response['pesan']) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-8 text-gray-500">
                                        <i class="fas fa-comments text-4xl mb-2"></i>
                                        <p>No responses yet. Our team will respond soon!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-xl shadow-md p-12 text-center">
                            <i class="fas fa-ticket-alt text-6xl text-gray-300 mb-4"></i>
                            <h2 class="text-2xl font-bold text-gray-800 mb-2">Select a Ticket</h2>
                            <p class="text-gray-600">Choose a ticket from the list to view details and responses</p>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
    
    <style>
        .status-open { background: #fef3c7; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-resolved { background: #d1fae5; color: #065f46; }
        .status-closed { background: #e5e7eb; color: #1f2937; }
    </style>
</body>
</html>