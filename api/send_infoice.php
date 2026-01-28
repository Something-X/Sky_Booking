<?php
require_once __DIR__ . '/../config.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Cek apakah PHPMailer tersedia
$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'PHPMailer tidak terinstall. Jalankan: composer require phpmailer/phpmailer',
        'debug' => 'Vendor path: ' . $vendorPath
    ]);
    exit;
}

// Load PHPMailer
require_once $vendorPath;

// Load EmailSender class
$emailSenderPath = __DIR__ . '/../classes/EmailSender.php';
if (!file_exists($emailSenderPath)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'File EmailSender.php tidak ditemukan di: ' . $emailSenderPath
    ]);
    exit;
}
require_once $emailSenderPath;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$booking_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($booking_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID pemesanan tidak valid']);
    exit;
}

try {
    // Ambil data pemesanan dengan informasi penerbangan
    $query = "
        SELECT 
            p.*, 
            pe.maskapai, 
            pe.kode_penerbangan, 
            pe.asal, 
            pe.tujuan, 
            pe.tanggal, 
            pe.jam_berangkat, 
            pe.jam_tiba
        FROM pemesanan p
        JOIN penerbangan pe ON p.id_penerbangan = pe.id
        WHERE p.id = ? AND p.user_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Query error: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Pemesanan tidak ditemukan atau bukan milik Anda']);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    // Ambil data penumpang
    $query_penumpang = "SELECT * FROM penumpang WHERE id_pemesanan = ?";
    $stmt_penumpang = $conn->prepare($query_penumpang);
    if (!$stmt_penumpang) {
        throw new Exception("Query error: " . $conn->error);
    }
    
    $stmt_penumpang->bind_param("i", $booking_id);
    $stmt_penumpang->execute();
    $result_penumpang = $stmt_penumpang->get_result();
    
    $penumpang = [];
    while ($row = $result_penumpang->fetch_assoc()) {
        $penumpang[] = $row;
    }
    $stmt_penumpang->close();
    
    if (empty($penumpang)) {
        echo json_encode(['success' => false, 'message' => 'Data penumpang tidak ditemukan']);
        exit;
    }
    
    // Debug: Log SMTP configuration
    error_log("SMTP Config - Host: " . (defined('SMTP_HOST') ? SMTP_HOST : 'NOT SET'));
    error_log("SMTP Config - Port: " . (defined('SMTP_PORT') ? SMTP_PORT : 'NOT SET'));
    error_log("SMTP Config - Username: " . (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'NOT SET'));
    error_log("SMTP Config - Password: " . (defined('SMTP_PASSWORD') && !empty(SMTP_PASSWORD) ? '***SET***' : 'NOT SET'));
    
    // Initialize EmailSender
    try {
        $emailSender = new EmailSender();
        
        // Check if configured
        if (!$emailSender->isConfigured()) {
            echo json_encode([
                'success' => false,
                'message' => 'Email tidak terkonfigurasi dengan benar. Periksa file .env',
                'debug' => $emailSender->getLastError()
            ]);
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error inisialisasi EmailSender: ' . $e->getMessage()
        ]);
        exit;
    }
    
    // Kirim email
    $sent = $emailSender->sendInvoice($booking, $penumpang);
    
    if ($sent) {
        // Log aktivitas (optional - jika tabel email_logs sudah ada)
        $checkTable = $conn->query("SHOW TABLES LIKE 'email_logs'");
        if ($checkTable && $checkTable->num_rows > 0) {
            $log_query = "INSERT INTO email_logs (booking_id, email_to, email_type, status, sent_at) 
                          VALUES (?, ?, 'invoice', 'sent', NOW())";
            $log_stmt = $conn->prepare($log_query);
            if ($log_stmt) {
                $log_stmt->bind_param("is", $booking_id, $booking['email']);
                $log_stmt->execute();
                $log_stmt->close();
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Invoice berhasil dikirim ke ' . htmlspecialchars($booking['email'])
        ]);
    } else {
        $errorMsg = $emailSender->getLastError();
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal mengirim email: ' . $errorMsg,
            'debug' => [
                'to_email' => $booking['email'],
                'error' => $errorMsg
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error sending invoice: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>