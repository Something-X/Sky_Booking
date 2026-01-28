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
    // Query detail tiket yang dipilih
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
        // Get responses untuk tiket yang dipilih
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

        // Tandai semua respon sebagai terbaca saat tiket dibuka
        $sql_mark = "UPDATE support_responses SET read_by_user = 1 WHERE ticket_id = ?";
        $stmt = $conn->prepare($sql_mark);
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Get all user tickets untuk sidebar
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

                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-4 bg-gradient-to-r from-blue-600 to-blue-700">
                            <h2 class="text-white font-bold">All Tickets</h2>
                        </div>

                        <div class="divide-y divide-gray-100">
                            <?php while ($t = $all_tickets->fetch_assoc()): ?>
                                <a href="?ticket=<?= $t['id'] ?>"
                                    class="block p-4 hover:bg-gray-50 transition-colors <?= $t['id'] == $ticket_id ? 'bg-blue-50 border-r-4 border-blue-600' : '' ?>">
                                    
                                    <div class="flex items-start justify-between mb-2">
                                        <span class="text-sm font-semibold text-gray-800">#<?= $t['id'] ?> - <?= htmlspecialchars($t['subjek']) ?></span>
                                        <?php if ($t['unread_count'] > 0): ?>
                                            <span class="bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                                                <?= $t['unread_count'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 uppercase tracking-tighter font-medium">
                                            <?= htmlspecialchars($t['kategori']) ?>
                                        </span>
                                        <span class="status-<?= $t['status'] ?> inline-flex px-2 py-0.5 rounded-full text-xs font-bold uppercase">
                                            <?= str_replace('_', ' ', $t['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <p class="text-xs text-gray-400 mt-2 italic">
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

                <div class="lg:col-span-2">
                    <?php if ($ticket): ?>
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="p-6 border-b border-gray-200">
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <h2 class="text-2xl font-bold text-gray-800 mb-2">
                                            <?= htmlspecialchars($ticket['subjek']) ?>
                                        </h2>
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-bold uppercase tracking-wide">
                                                <?= htmlspecialchars($ticket['kategori']) ?>
                                            </span>
                                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold uppercase
                                                         <?php
                                                         echo $ticket['status'] === 'open' ? 'bg-yellow-100 text-yellow-800' : 
                                                              ($ticket['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800' : 
                                                              ($ticket['status'] === 'resolved' ? 'bg-green-100 text-green-800' :
                                                              'bg-gray-100 text-gray-800'));
                                                         ?>">
                                                <?= str_replace('_', ' ', $ticket['status']) ?>
                                            </span>
                                            <span class="text-sm text-gray-400">
                                                <?= date('d M Y, H:i', strtotime($ticket['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 rounded-lg p-5 border border-gray-100 mb-4">
                                    <p class="text-gray-700 whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($ticket['pesan']) ?></p>
                                </div>

                                <?php if (!empty($ticket['admin_notes'])): ?>
                                    <div class="bg-amber-50 border-l-4 border-amber-500 rounded-lg p-4 mb-2 shadow-sm">
                                        <div class="flex items-center gap-2 mb-1">
                                            <i class="fas fa-sticky-note text-amber-600"></i>
                                            <span class="font-bold text-amber-800 text-xs uppercase tracking-wider">Catatan Penting dari Admin</span>
                                        </div>
                                        <p class="text-amber-900 text-sm italic"><?= htmlspecialchars($ticket['admin_notes']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="p-6 bg-gray-50/50">
                                <h3 class="font-bold text-lg text-gray-800 mb-6 flex items-center gap-2">
                                    <i class="fas fa-reply-all text-blue-600"></i>
                                    Responses (<?= count($responses) ?>)
                                </h3>

                                <?php if (count($responses) > 0): ?>
                                    <div class="space-y-6">
                                        <?php foreach ($responses as $response): ?>
                                            <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-5 transition-hover hover:shadow-md">
                                                <div class="flex items-start gap-4">
                                                    <div class="flex-shrink-0">
                                                        <div class="w-11 h-11 rounded-full bg-blue-600 flex items-center justify-center text-white shadow-inner">
                                                            <i class="fas fa-user-shield text-lg"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center justify-between mb-3">
                                                            <span class="font-bold text-gray-900">
                                                                <?= htmlspecialchars($response['admin_name'] ?? 'Support Team') ?>
                                                            </span>
                                                            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-md">
                                                                <?= date('d M Y, H:i', strtotime($response['created_at'])) ?>
                                                            </span>
                                                        </div>
                                                        <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-wrap">
                                                            <?= htmlspecialchars($response['pesan']) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-12 text-gray-400 bg-white rounded-xl border border-dashed border-gray-200">
                                        <i class="fas fa-comments text-5xl mb-3 opacity-20"></i>
                                        <p class="font-medium">No responses yet. Our team will respond soon!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-xl shadow-md p-20 text-center flex flex-col items-center justify-center border-2 border-dashed border-gray-100">
                            <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                                <i class="fas fa-ticket-alt text-5xl text-gray-200"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-2 tracking-tight">Select a Ticket</h2>
                            <p class="text-gray-500 max-w-sm">Pilih tiket bantuan dari daftar di sebelah kiri untuk melihat detail masalah dan respon dari tim kami.</p>
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
        .status-closed { background: #f3f4f6; color: #374151; }
    </style>
</body>

</html>