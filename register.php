<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $conn->real_escape_string($_POST['nama']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $no_hp = $conn->real_escape_string($_POST['no_hp']);
    
    // Validasi
    if (empty($nama) || empty($email) || empty($password) || empty($no_hp)) {
        $error = 'Semua field wajib diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } elseif ($password !== $password_confirm) {
        $error = 'Password tidak cocok';
    } else {
        // Cek email sudah terdaftar
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check && $check->num_rows > 0) {
            $error = 'Email sudah terdaftar';
        } else {
            // Insert user baru
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (nama_lengkap, email, password, no_hp) 
                    VALUES ('$nama', '$email', '$password_hash', '$no_hp')";
            
            if ($conn->query($sql)) {
                $success = 'Registrasi berhasil! Silakan login.';
            } else {
                $error = 'Terjadi kesalahan. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - SkyBooking</title>
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
                <p class="text-gray-600 mt-2">Daftar akun baru</p>
            </div>

            <!-- Register Card -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Buat Akun</h2>

                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
                            <p class="text-red-700"><?= $error ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            <p class="text-green-700"><?= $success ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user mr-2 text-primary"></i>Nama Lengkap *
                            </label>
                            <input type="text" name="nama" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="Masukkan nama lengkap">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-2 text-primary"></i>Email *
                            </label>
                            <input type="email" name="email" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="email@example.com">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-phone mr-2 text-primary"></i>Nomor HP *
                            </label>
                            <input type="tel" name="no_hp" required pattern="[0-9]{10,13}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="08xxxxxxxxxx">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-lock mr-2 text-primary"></i>Password *
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
                        <i class="fas fa-user-plus mr-2"></i>Daftar Sekarang
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Sudah punya akun? 
                        <a href="login.php" class="text-primary hover:text-secondary font-semibold">Login di sini</a>
                    </p>
                </div>
            </div>

            <div class="mt-6 text-center">
                <a href="index.php" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</body>
</html>