<?php
/**
 * ✅ FIXED: Submit Support Ticket
 * Path: /api/submit_support.php
 */

// ⚠️ PENTING: Jangan ada output sebelum header()
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error ke client
ini_set('log_errors', 1); // Log error ke file

require_once '../config.php';

// ✅ Set header JSON PERTAMA KALI
header('Content-Type: application/json; charset=utf-8');

// ✅ Function untuk log error
function logError($message) {
    error_log("[SUPPORT_TICKET] " . $message);
}

// ✅ Function untuk response JSON
function jsonResponse($success, $message, $data = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

// ========== VALIDATION ==========

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logError("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    jsonResponse(false, 'Invalid request method');
}

try {
    logError("=== NEW SUPPORT TICKET REQUEST ===");
    logError("POST data: " . json_encode($_POST));
    
    // ========== GET DATA ==========
    $kategori = trim($_POST['kategori'] ?? '');
    $subjek = trim($_POST['subjek'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');
    
    logError("Kategori: $kategori");
    logError("Subjek: $subjek");
    logError("Pesan length: " . strlen($pesan));
    
    // ========== VALIDASI INPUT ==========
    if (empty($kategori)) {
        logError("Validation failed: kategori empty");
        jsonResponse(false, 'Kategori harus dipilih');
    }
    
    if (empty($subjek)) {
        logError("Validation failed: subjek empty");
        jsonResponse(false, 'Subjek harus diisi');
    }
    
    if (empty($pesan)) {
        logError("Validation failed: pesan empty");
        jsonResponse(false, 'Pesan harus diisi');
    }
    
    // ========== GET USER DATA ==========
    $user_id = null;
    $nama_user = 'Guest';
    $email = '';
    
    if (isLoggedIn()) {
        logError("User is logged in");
        $user_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("SELECT nama_lengkap, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $nama_user = $user['nama_lengkap'];
            $email = $user['email'];
            logError("Logged in user: $nama_user ($email)");
        } else {
            logError("User not found in database: $user_id");
            jsonResponse(false, 'User tidak ditemukan');
        }
        $stmt->close();
        
    } else {
        logError("User is guest");
        
        // Jika tidak login, ambil dari form
        $nama_user = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($nama_user)) {
            logError("Validation failed: nama empty for guest");
            jsonResponse(false, 'Nama harus diisi');
        }
        
        if (empty($email)) {
            logError("Validation failed: email empty for guest");
            jsonResponse(false, 'Email harus diisi');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            logError("Validation failed: invalid email format: $email");
            jsonResponse(false, 'Format email tidak valid');
        }
        
        logError("Guest user: $nama_user ($email)");
    }
    
    // ========== TENTUKAN PRIORITAS ==========
    $prioritas = 'medium';
    if (in_array($kategori, ['Pembayaran', 'Pemesanan'])) {
        $prioritas = 'high';
    } elseif ($kategori === 'Lainnya') {
        $prioritas = 'low';
    }
    
    logError("Prioritas: $prioritas");
    
    // ========== INSERT KE DATABASE ==========
    $sql = "INSERT INTO support_tickets (user_id, nama_user, email, kategori, subjek, pesan, prioritas, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open', NOW())";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        logError("Prepare failed: " . $conn->error);
        jsonResponse(false, 'Database error: gagal prepare statement');
    }
    
    $stmt->bind_param("issssss", $user_id, $nama_user, $email, $kategori, $subjek, $pesan, $prioritas);
    
    if ($stmt->execute()) {
        $ticket_id = $conn->insert_id;
        logError("✅ Ticket created successfully! ID: $ticket_id");
        
        $stmt->close();
        
        jsonResponse(true, 'Keluhan Anda berhasil dikirim. Tim kami akan segera menghubungi Anda via email.', [
            'ticket_id' => $ticket_id
        ]);
        
    } else {
        logError("Execute failed: " . $stmt->error);
        $stmt->close();
        jsonResponse(false, 'Gagal menyimpan keluhan ke database');
    }
    
} catch (Exception $e) {
    logError("Exception caught: " . $e->getMessage());
    logError("Stack trace: " . $e->getTraceAsString());
    jsonResponse(false, 'Terjadi kesalahan sistem: ' . $e->getMessage());
}
?>