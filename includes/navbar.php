<?php 
require_once 'config.php'; 

// Cek apakah di halaman yang membutuhkan navbar putih
$currentPage = basename($_SERVER['PHP_SELF']);
$whiteNavbarPages = ['search_results.php', 'profile.php', 'riwayat.php'];
$isWhiteNavbar = in_array($currentPage, $whiteNavbarPages);

// Get user data if logged in
$userData = null;
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, nama_lengkap, email, foto_profil FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
}
?>

<nav id="navbar" 
     class="fixed top-0 left-0 right-0 z-[1000] transition-all duration-300 py-2 group
            <?php echo $isWhiteNavbar ? 'bg-white shadow-md' : 'bg-transparent'; ?>"
     <?php echo $isWhiteNavbar ? 'data-white-navbar="true"' : ''; ?>>

    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between">
            
            <a href="index.php" class="flex items-center -space-x-[15px] group/logo text-decoration-none">
                <img src="uploads/logo/logos.png" alt="Logo" class="w-[63px] pb-[12px]">
                <p class="text-2xl font-bold transition-colors duration-300
                          <?php echo $isWhiteNavbar ? 'text-[#1e3a8a]' : 'text-white'; ?>
                          <?php echo !$isWhiteNavbar ? 'group-[.scrolled]:text-[#1e3a8a]' : ''; ?>">
                    viato
                </p>
            </a>

            <div class="flex items-center space-x-3">
                
                <div class="hidden md:flex items-center space-x-6 mr-4">
                    <?php 
                    $navItems = ['Home' => 'index.php', 'Service' => '#', 'History' => 'riwayat.php', 'About' => '#'];
                    foreach($navItems as $name => $link): 
                    ?>
                        <a href="<?php echo $link; ?>" 
                           <?php if($name === 'Service') echo 'onclick="triggerService(event)"'; ?>
                           class="font-medium text-sm transition-colors duration-300 hover:text-blue-500
                                  <?php echo $isWhiteNavbar ? 'text-[#1e3a8a]' : 'text-white'; ?>
                                  <?php echo !$isWhiteNavbar ? 'group-[.scrolled]:text-[#1e3a8a]' : ''; ?>">
                            <?php echo $name; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if (isLoggedIn() && $userData): ?>
                    
                    <!-- ========== NOTIFICATION BELL (NEW) ========== -->
                    <div class="relative notification-dropdown">
                        <button id="notificationButton" type="button" class="relative p-2 rounded-full transition-all hover:bg-white/10 focus:outline-none">
                            <!-- Bell Icon -->
                            <svg class="w-6 h-6 transition-colors duration-300
                                        <?php echo $isWhiteNavbar ? 'text-[#1e3a8a]' : 'text-white'; ?>
                                        <?php echo !$isWhiteNavbar ? 'group-[.scrolled]:text-[#1e3a8a]' : ''; ?>" 
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            
                            <!-- Badge Count -->
                            <span id="notificationBadge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center animate-pulse">
                                0
                            </span>
                        </button>
                        
                        <!-- Notification Dropdown -->
                        <div id="notificationMenu" class="hidden absolute top-full right-0 mt-3 w-96 bg-white rounded-xl shadow-2xl overflow-hidden z-[1001] origin-top-right">
                            <!-- Header -->
                            <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-4 flex items-center justify-between">
                                <h3 class="text-white font-bold text-lg">Notifications</h3>
                                <button onclick="markAllAsRead()" class="text-white/80 hover:text-white text-sm font-medium transition-colors">
                                    Mark all as read
                                </button>
                            </div>
                            
                            <!-- Messages List -->
                            <div id="notificationList" class="max-h-[400px] overflow-y-auto">
                                <!-- Loading -->
                                <div id="notificationLoading" class="p-8 text-center">
                                    <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="text-gray-500 mt-2 text-sm">Loading notifications...</p>
                                </div>
                                
                                <!-- Empty State -->
                                <div id="notificationEmpty" class="hidden p-8 text-center">
                                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <p class="text-gray-500 font-medium">No notifications</p>
                                    <p class="text-gray-400 text-sm mt-1">You're all caught up!</p>
                                </div>
                                
                                <!-- Messages akan dimuat di sini via JavaScript -->
                            </div>
                            
                            <!-- Footer -->
                            <div class="border-t border-gray-100 p-3 text-center">
                                <a href="support_tickets.php" class="text-blue-600 hover:text-blue-700 font-medium text-sm transition-colors">
                                    View all tickets →
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Dropdown (existing) -->
                    <div class="relative profile-dropdown">
                        <button id="profileButton" type="button" class="flex items-center space-x-2 focus:outline-none transition-opacity hover:opacity-80">
                            <div class="w-10 h-10 rounded-full bg-[#0A63C4] flex items-center justify-center text-white font-semibold overflow-hidden border-2 border-transparent">
                                <?php if (!empty($userData['foto_profil']) && file_exists($userData['foto_profil'])): ?>
                                    <img src="<?php echo htmlspecialchars($userData['foto_profil']); ?>" alt="Profile" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span><?php echo strtoupper(substr($userData['nama_lengkap'] ?? 'U', 0, 1)); ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="hidden sm:inline font-semibold text-sm transition-colors duration-300
                                         <?php echo $isWhiteNavbar ? 'text-[#1e3a8a]' : 'text-white'; ?>
                                         <?php echo !$isWhiteNavbar ? 'group-[.scrolled]:text-[#1e3a8a]' : ''; ?>">
                                <?php echo htmlspecialchars($userData['nama_lengkap'] ?? 'User'); ?>
                            </span>
                            <svg class="w-4 h-4 transition-transform duration-300 profile-arrow
                                        <?php echo $isWhiteNavbar ? 'text-[#1e3a8a]' : 'text-white'; ?>
                                        <?php echo !$isWhiteNavbar ? 'group-[.scrolled]:text-[#1e3a8a]' : ''; ?>" 
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div id="profileMenu" class="hidden absolute top-full right-0 mt-3 w-72 bg-white rounded-xl shadow-xl overflow-hidden z-[1001] origin-top-right transition-all">
                            <div class="bg-[#0A63C4] p-5 flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-[#0A63C4] font-bold text-xl overflow-hidden">
                                    <?php if (!empty($userData['foto_profil']) && file_exists($userData['foto_profil'])): ?>
                                        <img src="<?php echo htmlspecialchars($userData['foto_profil']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span><?php echo strtoupper(substr($userData['nama_lengkap'] ?? 'U', 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-white truncate"><?php echo htmlspecialchars($userData['nama_lengkap'] ?? 'User'); ?></p>
                                    <p class="text-xs text-white/90 truncate"><?php echo htmlspecialchars($userData['email'] ?? ''); ?></p>
                                </div>
                            </div>
                            <div class="py-2">
                                <a href="profile.php" class="flex items-center gap-3 px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-[#1e3a8a] transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    Profil Saya
                                </a>
                                <div class="h-px bg-gray-100 my-1"></div>
                                <a href="logout_user.php" class="flex items-center gap-3 px-5 py-3 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                    Logout
                                </a>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <a href="register.php" 
                       class="px-6 py-2 rounded-lg font-semibold text-sm border-2 transition-all duration-300
                              <?php if ($isWhiteNavbar): ?>
                                  border-[#1e3a8a] text-[#1e3a8a] hover:bg-[#1e3a8a] hover:text-white
                              <?php else: ?>
                                  border-white text-white hover:bg-white hover:text-[#1e3a8a]
                                  group-[.scrolled]:border-[#1e3a8a] 
                                  group-[.scrolled]:text-[#1e3a8a] 
                                  group-[.scrolled]:hover:bg-[#1e3a8a] 
                                  group-[.scrolled]:hover:text-white
                              <?php endif; ?>">
                        Register
                    </a>

                    <a href="login.php" 
                       class="px-6 py-2.5 rounded-lg font-semibold text-sm text-white transition-all duration-300 border-2 border-[#1e3a8a] bg-[#1e3a8a] hover:bg-[#1e40af] hover:border-[#1e40af]">
                        Sign In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<style>
    nav.scrolled {
        background-color: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    #profileMenu:not(.hidden), #notificationMenu:not(.hidden) {
        animation: slideDown 0.3s ease-out forwards;
    }
    
    .profile-dropdown.active .profile-arrow {
        transform: rotate(180deg);
    }
    
    /* Custom scrollbar untuk notification list */
    #notificationList::-webkit-scrollbar {
        width: 6px;
    }
    #notificationList::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    #notificationList::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }
    #notificationList::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

<script>
    // ========== NOTIFICATION FUNCTIONS ==========
    
    let notificationRefreshInterval;
    
    // Load notifications
    async function loadNotifications() {
        try {
            const response = await fetch('api/get_notifications.php?action=list&limit=10');
            const data = await response.json();
            
            const listDiv = document.getElementById('notificationList');
            const loadingDiv = document.getElementById('notificationLoading');
            const emptyDiv = document.getElementById('notificationEmpty');
            
            // Hide loading
            loadingDiv.classList.add('hidden');
            
            if (data.success && data.messages && data.messages.length > 0) {
                emptyDiv.classList.add('hidden');
                
                let html = '';
                data.messages.forEach(msg => {
                    const isUnread = msg.read_by_user == 0;
                    const timeAgo = formatTimeAgo(msg.created_at);
                    
                    html += `
                        <div class="border-b border-gray-100 p-4 hover:bg-gray-50 transition-colors cursor-pointer ${isUnread ? 'bg-blue-50' : ''}"
                             onclick="viewNotification(${msg.response_id}, ${msg.ticket_id})">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="font-semibold text-sm text-gray-900">${msg.admin_name || 'Admin'}</p>
                                        ${isUnread ? '<span class="w-2 h-2 bg-blue-600 rounded-full"></span>' : ''}
                                    </div>
                                    <p class="text-xs text-gray-500 mb-1">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                            ${msg.kategori}
                                        </span>
                                        · ${msg.subjek}
                                    </p>
                                    <p class="text-sm text-gray-700 line-clamp-2">${msg.pesan}</p>
                                    <p class="text-xs text-gray-400 mt-2">${timeAgo}</p>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                listDiv.innerHTML = html;
            } else {
                emptyDiv.classList.remove('hidden');
                listDiv.innerHTML = '';
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            document.getElementById('notificationLoading').innerHTML = `
                <p class="text-red-500 text-sm">Failed to load notifications</p>
            `;
        }
    }
    
    // Update notification count
    async function updateNotificationCount() {
        try {
            const response = await fetch('api/get_notifications.php?action=count');
            const data = await response.json();
            
            if (data.success) {
                const badge = document.getElementById('notificationBadge');
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        } catch (error) {
            console.error('Error updating count:', error);
        }
    }
    
    // View notification (mark as read)
    async function viewNotification(responseId, ticketId) {
        try {
            // Mark as read
            const formData = new FormData();
            formData.append('response_id', responseId);
            
            await fetch('api/get_notifications.php?action=mark_read', {
                method: 'POST',
                body: formData
            });
            
            // Redirect to ticket detail or support page
            window.location.href = 'support_tickets.php?ticket=' + ticketId;
        } catch (error) {
            console.error('Error:', error);
        }
    }
    
    // Mark all as read
    async function markAllAsRead() {
        try {
            const response = await fetch('api/get_notifications.php?action=mark_all_read', {
                method: 'POST'
            });
            const data = await response.json();
            
            if (data.success) {
                loadNotifications();
                updateNotificationCount();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    
    // Format time ago
    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
        if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
        
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    }
    
    // ========== EVENT LISTENERS ==========
    
    document.addEventListener('DOMContentLoaded', function() {
        const notificationButton = document.getElementById('notificationButton');
        const notificationMenu = document.getElementById('notificationMenu');
        const notificationDropdown = document.querySelector('.notification-dropdown');
        
        const profileButton = document.getElementById('profileButton');
        const profileMenu = document.getElementById('profileMenu');
        const profileDropdown = document.querySelector('.profile-dropdown');
        
        // Update notification count on load
        <?php if (isLoggedIn()): ?>
        updateNotificationCount();
        
        // Refresh every 30 seconds
        notificationRefreshInterval = setInterval(updateNotificationCount, 30000);
        <?php endif; ?>
        
        // Notification dropdown toggle
        if (notificationButton && notificationMenu) {
            notificationButton.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Close profile menu if open
                if (profileMenu) profileMenu.classList.add('hidden');
                if (profileDropdown) profileDropdown.classList.remove('active');
                
                // Toggle notification menu
                const isHidden = notificationMenu.classList.toggle('hidden');
                
                // Load notifications when opened
                if (!isHidden) {
                    loadNotifications();
                }
            });
        }
        
        // Profile dropdown toggle
        if (profileButton && profileMenu) {
            profileButton.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Close notification menu if open
                if (notificationMenu) notificationMenu.classList.add('hidden');
                
                // Toggle profile menu
                profileMenu.classList.toggle('hidden');
                profileDropdown.classList.toggle('active');
            });
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (notificationDropdown && !notificationDropdown.contains(e.target)) {
                notificationMenu.classList.add('hidden');
            }
            if (profileDropdown && !profileDropdown.contains(e.target)) {
                profileMenu.classList.add('hidden');
                profileDropdown.classList.remove('active');
            }
        });
    });
    
    // ========== EXISTING FUNCTIONS ==========
    
    function triggerService(e) {
        e.preventDefault();
        if (typeof openChatSupport === 'function') {
            openChatSupport();
        } else {
            window.location.href = "index.php?action=openchat";
        }
    }
    
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        const isWhiteNavbar = navbar.getAttribute('data-white-navbar') === 'true';
        
        if (isWhiteNavbar) return;

        if (window.scrollY > 50) {
            navbar.classList.remove('transparent', 'bg-transparent');
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
            navbar.classList.add('transparent', 'bg-transparent');
        }
    });
</script>