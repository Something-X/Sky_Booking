<?php
require_once 'config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$valid_token = false;

// Validasi token
if (!empty($token)) {
    $token = $conn->real_escape_string($token);
    $sql = "SELECT * FROM password_resets 
            WHERE token = '$token' 
            AND used = 0 
            AND expires_at > NOW()";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $valid_token = true;
        $reset_data = $result->fetch_assoc();
    } else {
        $error = 'Link reset password tidak valid atau sudah expired.';
    }
}

// Process reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    if (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } elseif ($password !== $password_confirm) {
        $error = 'Password tidak cocok';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $email = $reset_data['email'];
        
        // Update password
        $sql = "UPDATE users SET password = '$password_hash' WHERE email = '$email'";
        if ($conn->query($sql)) {
            // Tandai token sebagai sudah digunakan
            $conn->query("UPDATE password_resets SET used = 1 WHERE token = '$token'");
            $success = 'Password berhasil diubah! Silakan login dengan password baru Anda.';
            $valid_token = false;
        } else {
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SkyBooking</title>
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
<body class="bg-gradient-to-br from-blue-50 to-purple-50 min-h-screen">
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-md mx-auto">
            <!-- Logo -->
            <div class="text-center mb-8">
                <a href="index.php" class="inline-flex items-center text-primary text-3xl font-bold">
                    <i class="fas fa-plane-departure mr-3"></i>
                    SkyBooking
                </a>
                <p class="text-gray-600 mt-2">Buat Password Baru</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-lock text-white text-3xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800">Reset Password</h2>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
                            <p class="text-red-700"><?= $error ?></p>
                        </div>
                        <?php if (!$valid_token): ?>
                            <div class="mt-4">
                                <a href="forgot_password.php" class="text-primary hover:underline font-semibold">
                                    <i class="fas fa-redo mr-2"></i>Request Link Baru
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-check-circle text-green-400 mr-3 text-2xl"></i>
                            <p class="text-green-700 font-semibold"><?= $success ?></p>
                        </div>
                        <a href="login.php" class="block w-full bg-primary hover:bg-secondary text-white font-bold py-3 rounded-lg transition duration-300 text-center">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login Sekarang
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($valid_token && !$success): ?>
                    <form method="POST" action="">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-2 text-primary"></i>Password Baru *
                                </label>
                                <input type="password" name="password" required minlength="6"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                       placeholder="Minimal 6 karakter">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-2 text-primary"></i>Konfirmasi Password *
                                </label>
                                <input type="password" name="password_confirm" required minlength="6"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                       placeholder="Ulangi password">
                            </div>
                        </div>

                        <button type="submit" class="w-full mt-6 bg-primary hover:bg-secondary text-white font-bold py-4 rounded-lg transition duration-300">
                            <i class="fas fa-check mr-2"></i>Reset Password
                        </button>
                    </form>
                <?php endif; ?>

                <?php if (!$valid_token && !$success): ?>
                    <div class="text-center">
                        <a href="login.php" class="text-primary hover:text-secondary font-semibold">
                            <i class="fas fa-arrow-left mr-2"></i>Kembali ke Login
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-6 text-center">
                <a href="index.php" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-home mr-2"></i>Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</body>
</html>