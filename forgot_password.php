<?php
require_once 'config.php';
require_once 'vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    
    // Cek apakah email terdaftar
    $sql = "SELECT id, nama_lengkap FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Simpan token ke database
        $sql = "INSERT INTO password_resets (email, token, expires_at) 
                VALUES ('$email', '$token', '$expires_at')";
        
        if ($conn->query($sql)) {
            // Kirim email
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
            
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;
                
                // Recipients
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($email, $user['nama_lengkap']);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Reset Password - SkyBooking';
                $mail->Body = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: linear-gradient(135deg, #0066cc, #004999); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                            .button { display: inline-block; background: #ff6b35; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>🔒 Reset Password</h1>
                            </div>
                            <div class='content'>
                                <p>Halo <strong>{$user['nama_lengkap']}</strong>,</p>
                                <p>Kami menerima permintaan untuk reset password akun SkyBooking Anda.</p>
                                <p>Klik tombol di bawah ini untuk reset password Anda:</p>
                                <div style='text-align: center;'>
                                    <a href='$reset_link' class='button'>Reset Password</a>
                                </div>
                                <p><small>Atau copy link ini ke browser Anda:</small></p>
                                <p style='background: #fff; padding: 10px; border: 1px solid #ddd; word-break: break-all;'><small>$reset_link</small></p>
                                <p><strong>Link ini akan expired dalam 1 jam.</strong></p>
                                <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
                            </div>
                            <div class='footer'>
                                <p>&copy; 2025 SkyBooking. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";
                
                $mail->send();
                $message = 'Link reset password telah dikirim ke email Anda. Silakan cek inbox atau folder spam.';
            } catch (Exception $e) {
                $error = "Email gagal dikirim. Error: {$mail->ErrorInfo}";
            }
        } else {
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    } else {
        // Untuk keamanan, tetap tampilkan pesan sukses meskipun email tidak terdaftar
        $message = 'Jika email terdaftar, link reset password akan dikirim ke email Anda.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - SkyBooking</title>
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
                <p class="text-gray-600 mt-2">Reset Password Anda</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-key text-accent text-3xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800">Lupa Password?</h2>
                    <p class="text-gray-600 text-sm mt-2">Masukkan email Anda untuk menerima link reset password</p>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
                            <p class="text-red-700"><?= $error ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            <p class="text-green-700"><?= $message ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-primary"></i>Email
                        </label>
                        <input type="email" name="email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="email@example.com">
                    </div>

                    <button type="submit" class="w-full bg-accent hover:bg-orange-600 text-white font-bold py-4 rounded-lg transition duration-300">
                        <i class="fas fa-paper-plane mr-2"></i>Kirim Link Reset Password
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Sudah ingat password? 
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