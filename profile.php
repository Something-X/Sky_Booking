<?php
require_once 'config.php';

// Cek login
if (!isLoggedIn()) {
    redirect('login.php?redirect=profile.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle Update Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $no_hp = trim($_POST['no_hp']);
        $tanggal_lahir = $_POST['tanggal_lahir'] ?: null;
        $jenis_kelamin = $_POST['jenis_kelamin'] ?: null;

        if (empty($nama_lengkap)) {
            $error = 'Nama lengkap tidak boleh kosong';
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, no_hp = ?, tanggal_lahir = ?, jenis_kelamin = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $nama_lengkap, $no_hp, $tanggal_lahir, $jenis_kelamin, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $nama_lengkap;
                $success = 'Profil berhasil diperbarui';
                // Refresh data user
                $user['nama_lengkap'] = $nama_lengkap;
                $user['no_hp'] = $no_hp;
                $user['tanggal_lahir'] = $tanggal_lahir;
                $user['jenis_kelamin'] = $jenis_kelamin;
            } else {
                $error = 'Gagal memperbarui profil';
            }
            $stmt->close();
        }
    }

    if ($action === 'update_email') {
        $new_email = trim($_POST['new_email']);
        $password = $_POST['password_confirm'];

        if (empty($new_email) || empty($password)) {
            $error = 'Email dan password harus diisi';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Password salah';
        } else {
            // Cek apakah email sudah digunakan
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->bind_param("si", $new_email, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = 'Email sudah digunakan oleh akun lain';
            } else {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->bind_param("si", $new_email, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['user_email'] = $new_email;
                    $user['email'] = $new_email;
                    $success = 'Email berhasil diperbarui';
                } else {
                    $error = 'Gagal memperbarui email';
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
    }

    if ($action === 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Semua field password harus diisi';
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = 'Password lama salah';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password baru minimal 6 karakter';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Konfirmasi password tidak cocok';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success = 'Password berhasil diperbarui';
            } else {
                $error = 'Gagal memperbarui password';
            }
            $stmt->close();
        }
    }

    if ($action === 'update_photo') {
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB

            $file_type = $_FILES['foto_profil']['type'];
            $file_size = $_FILES['foto_profil']['size'];
            $file_tmp = $_FILES['foto_profil']['tmp_name'];

            if (!in_array($file_type, $allowed_types)) {
                $error = 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF';
            } elseif ($file_size > $max_size) {
                $error = 'Ukuran file maksimal 2MB';
            } else {
                // Create uploads directory if not exists
                $upload_dir = 'uploads/profile/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Generate unique filename
                $file_ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Delete old photo if exists
                    if (!empty($user['foto_profil']) && file_exists($user['foto_profil'])) {
                        unlink($user['foto_profil']);
                    }

                    $stmt = $conn->prepare("UPDATE users SET foto_profil = ? WHERE id = ?");
                    $stmt->bind_param("si", $upload_path, $user_id);
                    
                    if ($stmt->execute()) {
                        $user['foto_profil'] = $upload_path;
                        $success = 'Foto profil berhasil diperbarui';
                    } else {
                        $error = 'Gagal menyimpan foto profil';
                    }
                    $stmt->close();
                } else {
                    $error = 'Gagal mengupload foto';
                }
            }
        } else {
            $error = 'Pilih foto terlebih dahulu';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - SkyBooking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tab-button {
            transition: all 0.3s;
        }
        .tab-button.active {
            background: #1e3a8a;
            color: white;
        }
        .profile-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-purple-50 min-h-screen">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-24">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Profil Saya</h1>
                <p class="text-gray-600">Kelola informasi profil Anda</p>
            </div>

            <!-- Alert Messages -->
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-400 mr-3"></i>
                        <p class="text-green-700"><?= htmlspecialchars($success) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
                        <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="profile-card p-6">
                        <div class="text-center mb-6">
                            <div class="w-32 h-32 mx-auto mb-4 rounded-full bg-blue-100 flex items-center justify-center overflow-hidden">
                                <?php if (!empty($user['foto_profil']) && file_exists($user['foto_profil'])): ?>
                                    <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Profile" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-4xl text-blue-600 font-bold">
                                        <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="font-bold text-lg text-gray-800"><?= htmlspecialchars($user['nama_lengkap']) ?></h3>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                        </div>
                        
                        <div class="space-y-2">
                            <button onclick="showTab('profile')" class="tab-button w-full text-left px-4 py-3 rounded-lg active">
                                <i class="fas fa-user mr-3"></i>Informasi Profil
                            </button>
                            <button onclick="showTab('email')" class="tab-button w-full text-left px-4 py-3 rounded-lg">
                                <i class="fas fa-envelope mr-3"></i>Ubah Email
                            </button>
                            <button onclick="showTab('password')" class="tab-button w-full text-left px-4 py-3 rounded-lg">
                                <i class="fas fa-lock mr-3"></i>Ubah Password
                            </button>
                            <button onclick="showTab('photo')" class="tab-button w-full text-left px-4 py-3 rounded-lg">
                                <i class="fas fa-camera mr-3"></i>Foto Profil
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <!-- Tab: Profile Info -->
                    <div id="tab-profile" class="tab-content profile-card p-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Informasi Profil</h2>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-user mr-2 text-blue-600"></i>Nama Lengkap
                                    </label>
                                    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-phone mr-2 text-blue-600"></i>Nomor HP
                                    </label>
                                    <input type="tel" name="no_hp" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-calendar mr-2 text-blue-600"></i>Tanggal Lahir
                                    </label>
                                    <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($user['tanggal_lahir'] ?? '') ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-venus-mars mr-2 text-blue-600"></i>Jenis Kelamin
                                    </label>
                                    <select name="jenis_kelamin" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="">Pilih jenis kelamin</option>
                                        <option value="Laki-laki" <?= ($user['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="Perempuan" <?= ($user['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6">
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-lg transition duration-300">
                                    <i class="fas fa-save mr-2"></i>Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab: Email -->
                    <div id="tab-email" class="tab-content profile-card p-8 hidden">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Ubah Email</h2>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_email">
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Email Saat Ini
                                    </label>
                                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-envelope mr-2 text-blue-600"></i>Email Baru
                                    </label>
                                    <input type="email" name="new_email" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="emailbaru@example.com">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-lock mr-2 text-blue-600"></i>Konfirmasi Password
                                    </label>
                                    <input type="password" name="password_confirm" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Masukkan password untuk konfirmasi">
                                    <p class="text-xs text-gray-500 mt-1">Masukkan password Anda untuk keamanan</p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-lg transition duration-300">
                                    <i class="fas fa-save mr-2"></i>Update Email
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab: Password -->
                    <div id="tab-password" class="tab-content profile-card p-8 hidden">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Ubah Password</h2>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_password">
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-lock mr-2 text-blue-600"></i>Password Lama
                                    </label>
                                    <input type="password" name="current_password" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Masukkan password lama">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-key mr-2 text-blue-600"></i>Password Baru
                                    </label>
                                    <input type="password" name="new_password" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Minimal 6 karakter">
                                    <p class="text-xs text-gray-500 mt-1">Password minimal 6 karakter</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-check-circle mr-2 text-blue-600"></i>Konfirmasi Password Baru
                                    </label>
                                    <input type="password" name="confirm_password" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Ulangi password baru">
                                </div>
                            </div>

                            <div class="mt-6">
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-lg transition duration-300">
                                    <i class="fas fa-save mr-2"></i>Update Password
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab: Photo -->
                    <div id="tab-photo" class="tab-content profile-card p-8 hidden">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Foto Profil</h2>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_photo">
                            
                            <div class="text-center mb-6">
                                <div class="w-48 h-48 mx-auto mb-4 rounded-full bg-blue-100 flex items-center justify-center overflow-hidden border-4 border-blue-200">
                                    <?php if (!empty($user['foto_profil']) && file_exists($user['foto_profil'])): ?>
                                        <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Profile" class="w-full h-full object-cover" id="preview-image">
                                    <?php else: ?>
                                        <span class="text-6xl text-blue-600 font-bold" id="preview-initial">
                                            <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-image mr-2 text-blue-600"></i>Pilih Foto Baru
                                    </label>
                                    <input type="file" name="foto_profil" accept="image/*" id="photo-input"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, GIF. Maksimal 2MB</p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-lg transition duration-300">
                                    <i class="fas fa-upload mr-2"></i>Upload Foto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            
            // Add active class to clicked button
            event.target.closest('.tab-button').classList.add('active');
        }

        // Photo preview
        document.getElementById('photo-input')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview-image');
                    const initial = document.getElementById('preview-initial');
                    
                    if (preview) {
                        preview.src = e.target.result;
                    } else if (initial) {
                        initial.parentElement.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover" id="preview-image">`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>