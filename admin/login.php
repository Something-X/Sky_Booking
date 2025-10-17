<?php
require_once '../config.php';

// Redirect jika sudah login
if (isAdmin()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validasi input tidak kosong
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        // Query dengan prepared statement (lebih aman dari SQL Injection)
        $sql = "SELECT id, username, password, nama_lengkap FROM admin WHERE username = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                
                // Verifikasi password dengan bcrypt
                if (password_verify($password, $admin['password'])) {
                    // Login berhasil
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['nama_lengkap'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['login_time'] = time();
                    
                    redirect('dashboard.php');
                } else {
                    $error = 'Username atau password salah';
                    // Log failed attempt (optional)
                    error_log("Failed login attempt for username: $username");
                }
            } else {
                $error = 'Username atau password salah';
                error_log("Login attempt with non-existent username: $username");
            }
            $stmt->close();
        } else {
            $error = 'Terjadi kesalahan database';
            error_log("Database error: " . $conn->error);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - SkyBooking</title>
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
<body class="bg-gradient-to-br from-primary to-secondary min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="../index.php" class="inline-flex items-center text-white text-3xl font-bold">
                <i class="fas fa-plane-departure mr-3"></i>
                SkyBooking
            </a>
            <p class="text-white mt-2 opacity-90">Admin Panel</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-shield text-white text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Login Admin</h2>
                <p class="text-gray-600 mt-2">Masuk ke dashboard admin</p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded animate-pulse">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
                        <p class="text-red-700 font-medium"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-400 mr-3"></i>
                        <p class="text-green-700 font-medium"><?= htmlspecialchars($success) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-primary"></i>Username
                    </label>
                    <input type="text" 
                           name="username" 
                           required
                           autocomplete="username"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition"
                           placeholder="Masukkan username">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-primary"></i>Password
                    </label>
                    <input type="password" 
                           name="password" 
                           required
                           autocomplete="current-password"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition"
                           placeholder="Masukkan password">
                </div>

                <button type="submit" 
                        class="w-full mt-6 bg-primary hover:bg-secondary text-white font-bold py-3 rounded-lg transition duration-300 flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Login
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="../index.php" class="text-primary hover:text-secondary text-sm font-semibold transition">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Beranda
                </a>
            </div>
        </div>

        <!-- Info -->
        <div class="mt-6 text-center text-white text-sm">
            <p class="opacity-75">Test: username: <span class="font-bold">admin</span> / password: <span class="font-bold">admin123</span></p>
        </div>
    </div>
</body>
</html>