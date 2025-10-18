<?php
$user = getUserData();
?>
<nav class="bg-gradient-to-r from-primary to-secondary shadow-lg">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <a href="index.php" class="text-white text-2xl font-bold flex items-center">
                <i class="fas fa-plane-departure mr-2"></i>
                SkyBooking
            </a>
            
            <!-- Mobile menu button -->
            <button id="mobileMenuBtn" class="md:hidden text-white">
                <i class="fas fa-bars text-xl"></i>
            </button>
            
            <!-- Desktop menu -->
            <div class="hidden md:flex items-center space-x-6">
                <a href="index.php" class="text-white hover:text-gray-200 transition">Home</a>
                
                <?php if (isLoggedIn()): ?>
                    <a href="riwayat.php" class="text-white hover:text-gray-200 transition">Riwayat</a>
                    
                    <!-- User Dropdown -->
                    <div class="relative group">
                        <button class="text-white hover:text-gray-200 transition flex items-center gap-2 py-2">
                            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-primary font-bold">
                                <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                            </div>
                            <span><?= $user['nama_lengkap'] ?></span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <div class="py-2">
                                <div class="px-4 py-3 border-b border-gray-200">
                                    <p class="text-sm font-semibold text-gray-800"><?= $user['nama_lengkap'] ?></p>
                                    <p class="text-xs text-gray-500"><?= $user['email'] ?></p>
                                </div>
                                <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50 transition">
                                    <i class="fas fa-user mr-3 text-primary"></i>Profil Saya
                                </a>
                                <a href="riwayat.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50 transition">
                                    <i class="fas fa-history mr-3 text-primary"></i>Riwayat Pemesanan
                                </a>
                                <hr class="my-2">
                                <a href="logout_user.php" class="block px-4 py-2 text-red-600 hover:bg-red-50 transition">
                                    <i class="fas fa-sign-out-alt mr-3"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="text-white hover:text-gray-200 transition">Login</a>
                    <a href="register.php" class="bg-accent hover:bg-orange-600 text-white px-5 py-2 rounded-lg transition font-semibold">
                        Daftar
                    </a>
                <?php endif; ?>
                
                <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-cog mr-1"></i>Admin
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobileMenu" class="hidden md:hidden pb-4">
            <a href="index.php" class="block text-white py-2 hover:text-gray-200">Home</a>
            <?php if (isLoggedIn()): ?>
                <div class="border-t border-blue-400 my-2 pt-2">
                    <p class="text-white text-sm mb-2"><?= $user['nama_lengkap'] ?></p>
                </div>
                <a href="riwayat.php" class="block text-white py-2 hover:text-gray-200">Riwayat</a>
                <a href="profile.php" class="block text-white py-2 hover:text-gray-200">Profil</a>
                <a href="logout_user.php" class="block text-white py-2 hover:text-gray-200">Logout</a>
            <?php else: ?>
                <a href="login.php" class="block text-white py-2 hover:text-gray-200">Login</a>
                <a href="register.php" class="block text-white py-2 hover:text-gray-200">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    // Mobile menu toggle
    document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
        document.getElementById('mobileMenu').classList.toggle('hidden');
    });
</script>