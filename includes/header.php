<?php
require_once __DIR__ . '/auth_functions.php';
?>
<!-- The head section is handled by index.php for the landing page, but we maintain the logic here for internal pages -->
<header class="fixed top-0 left-0 right-0 z-[1000] transition-all duration-300 glass border-b border-brand-muted/20">
    <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
        <!-- Logo -->
        <a href="/index.php" class="flex items-center gap-3 group transition-transform duration-300 active:scale-95">
            <img src="/assets/images/logo/logo.svg" alt="UniMarket Logo" class="h-8 w-auto transition-all dark:invert">
            <span class="serif text-xl font-semibold tracking-tight text-brand-text">UniMarket</span>
        </a>
        
        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center gap-4">
            <!-- Theme Toggle -->
            <button onclick="toggleTheme()" class="p-2 rounded-full hover:bg-brand-surface transition-all text-brand-text" title="Toggle Theme">
                <i class="fas fa-moon dark:hidden"></i>
                <i class="fas fa-sun hidden dark:block"></i>
            </button>

            <ul class="flex items-center gap-1 text-sm font-medium text-brand-muted">
                <?php if (isLoggedIn()): ?>
                    <?php if (isCustomer()): ?>
                        <li><a href="/customer/products/browse.php" class="px-4 py-2 rounded-full hover:bg-brand-surface hover:text-brand-text transition-all">Browse</a></li>
                        <li><a href="/customer/cart.php" class="px-4 py-2 rounded-full hover:bg-brand-surface hover:text-brand-text transition-all">Cart</a></li>
                        <li class="relative group">
                            <a href="#" onclick="openNotificationModal(); return false;" class="px-4 py-2 rounded-full hover:bg-brand-surface hover:text-brand-text transition-all flex items-center gap-2">
                                Notifications
                                <span class="notification-badge" id="notificationBadge"></span>
                            </a>
                            <!-- Notification Modal -->
                            <div class="absolute top-full right-0 mt-2 w-80 bg-brand-background rounded-3xl shadow-2xl border border-brand-muted/20 opacity-0 pointer-events-none translate-y-2 transition-all duration-300 z-[2000]" id="notificationModal">
                                <div class="p-4 border-b border-brand-muted/10 flex justify-between items-center bg-brand-surface/50 rounded-t-3xl">
                                    <h2 class="font-semibold text-sm flex items-center gap-2 text-brand-text"><i class="fas fa-bell text-brand-primary"></i> Notifications</h2>
                                    <button onclick="closeNotificationModal()" class="text-brand-muted hover:text-brand-text">&times;</button>
                                </div>
                                <div class="max-h-[400px] overflow-y-auto p-2" id="notificationList">
                                    <div class="py-10 text-center text-brand-muted text-xs italic">No notifications yet</div>
                                </div>
                            </div>
                        </li>
                        <li><a href="/customer/profile.php" class="px-4 py-2 rounded-full hover:bg-brand-surface hover:text-brand-text transition-all">Profile</a></li>
                    <?php elseif (isAdmin()): ?>
                        <li><a href="/admin/dashboard.php" class="px-4 py-2 rounded-full hover:bg-brand-surface hover:text-brand-text transition-all">Admin</a></li>
                        <li><a href="/admin/users/manage.php" class="px-4 py-2 rounded-full hover:bg-brand-surface hover:text-brand-text transition-all">Users</a></li>
                        <li><a href="/admin/products/index.php" class="px-4 py-2 rounded-full hover:bg-brand-surface hover:text-brand-text transition-all">Products</a></li>
                        <li><a href="/admin/orders/manage.php" class="px-4 py-2 rounded-full hover:bg-brand-surface hover:text-brand-text transition-all">Orders</a></li>
                    <?php else: ?>
                        <li><a href="/owner/dashboard.php" class="px-4 py-2 rounded-full hover:bg-brand-surface hover:text-brand-text transition-all">Dashboard</a></li>
                        <li><a href="/owner/products/list.php" class="px-4 py-2 rounded-full hover:bg-brand-surface hover:text-brand-text transition-all">Products</a></li>
                    <?php endif; ?>
                    <li>
                        <a href="#" onclick="openLogoutModal(); return false;" class="px-4 py-2 rounded-full bg-brand-text text-brand-background hover:bg-brand-primary transition-all ml-2">Logout</a>
                    </li>
                <?php else: ?>
                    <li><a href="/index.php" class="px-4 py-2 rounded-full hover:bg-brand-surface hover:text-brand-text transition-all">Home</a></li>
                    <li><a href="/auth/login.php" class="px-4 py-2 rounded-full hover:bg-brand-surface hover:text-brand-text transition-all">Login</a></li>
                    <li><a href="/auth/register.php" class="px-4 py-2 rounded-full bg-brand-text text-brand-background hover:bg-brand-primary transition-all ml-2">Join Now</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Mobile Toggle -->
        <button class="md:hidden p-2 text-slate-600 hover:text-black transition-colors" id="mobileMenuToggle">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div class="hidden md:hidden absolute top-20 left-0 right-0 bg-white border-b border-slate-100 z-[999] animate-in slide-in-from-top duration-300" id="mobileMenu">
        <ul class="flex flex-col p-6 gap-4 text-sm font-medium text-slate-600">
            <?php if (isLoggedIn()): ?>
                <?php if (isCustomer()): ?>
                    <li><a href="/customer/products/browse.php" class="block py-2">Browse</a></li>
                    <li><a href="/customer/cart.php" class="block py-2">Cart</a></li>
                    <li><a href="/customer/profile.php" class="block py-2">Profile</a></li>
                <?php elseif (isAdmin()): ?>
                    <li><a href="/admin/dashboard.php" class="block py-2">Admin Dashboard</a></li>
                    <li><a href="/admin/users/manage.php" class="block py-2">Users</a></li>
                    <li><a href="/admin/products/index.php" class="block py-2">Products</a></li>
                    <li><a href="/admin/orders/manage.php" class="block py-2">Orders</a></li>
                <?php else: ?>
                    <li><a href="/owner/dashboard.php" class="block py-2">Dashboard</a></li>
                    <li><a href="/owner/products/list.php" class="block py-2">Products</a></li>
                <?php endif; ?>
                <li><a href="#" onclick="openLogoutModal(); return false;" class="block py-2 text-red-500 font-semibold">Logout</a></li>
            <?php else: ?>
                <li><a href="/index.php" class="block py-2">Home</a></li>
                <li><a href="/auth/login.php" class="block py-2">Login</a></li>
                <li><a href="/auth/register.php" class="block py-2 font-semibold text-black">Join Now</a></li>
            <?php endif; ?>
        </ul>
    </div>
</header>

<!-- Logout Modal -->
<div class="fixed inset-0 z-[2000] hidden items-center justify-center bg-black/40 backdrop-blur-sm p-6" id="logoutModal">
    <div class="bg-white w-full max-w-sm rounded-3xl p-8 text-center shadow-2xl animate-in zoom-in-95 duration-200">
        <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6 text-2xl">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <h2 class="serif text-2xl mb-2">Confirm Logout</h2>
        <p class="text-muted text-sm mb-8">Are you sure you want to leave your session?</p>
        <div class="flex gap-3">
            <button onclick="closeLogoutModal()" class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-medium hover:bg-slate-200 transition-all">Cancel</button>
            <button onclick="confirmLogout()" class="flex-1 py-3 rounded-xl bg-black text-white font-medium hover:bg-red-600 transition-all">Logout</button>
        </div>
    </div>
</div>

<script>
    document.getElementById('mobileMenuToggle')?.addEventListener('click', () => {
        const menu = document.getElementById('mobileMenu');
        menu.classList.toggle('hidden');
    });

    function openLogoutModal() { document.getElementById('logoutModal').style.display = 'flex'; }
    function closeLogoutModal() { document.getElementById('logoutModal').style.display = 'none'; }
    function confirmLogout() { window.location.href = '/auth/logout.php'; }
    
    document.getElementById('logoutModal')?.addEventListener('click', (e) => {
        if (e.target === document.getElementById('logoutModal')) closeLogoutModal();
    });

    function openNotificationModal() {
        const modal = document.getElementById('notificationModal');
        modal.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-2');
        modal.classList.add('opacity-100', 'pointer-events-auto', 'translate-y-0');
        loadNotifications();
        
        const close = (e) => {
            if (!modal.contains(e.target) && !e.target.closest('.notification-btn')) {
                closeNotificationModal();
                document.removeEventListener('click', close);
            }
        };
        setTimeout(() => document.addEventListener('click', close), 10);
    }

    function closeNotificationModal() {
        const modal = document.getElementById('notificationModal');
        modal.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
        modal.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
    }

    function loadNotifications() {
        const list = document.getElementById('notificationList');
        const badge = document.getElementById('notificationBadge');
        if(!list) return;

        fetch('/customer/notifications.php')
            .then(res => res.json())
            .then(data => {
                if (data.error) throw new Error(data.error);
                if (data.notifications.length === 0) {
                    list.innerHTML = '<div class="py-10 text-center text-slate-400 text-xs italic">No notifications yet</div>';
                } else {
                    list.innerHTML = data.notifications.map(n => `
                        <div class="p-3 border-b border-slate-50 last:border-0 hover:bg-slate-50 transition-colors cursor-pointer ${n.unread ? 'bg-blue-50/30' : ''}">
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs ${n.status === 'ready' ? 'bg-emerald-100 text-emerald-700' : n.status === 'completed' ? 'bg-slate-200 text-slate-700' : 'bg-red-100 text-red-700'}">
                                    <i class="fas ${n.status === 'ready' ? 'fa-check-circle' : n.status === 'completed' ? 'fa-check-double' : 'fa-times-circle'}"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-slate-900 truncate">${n.title}</p>
                                    <div class="flex justify-between items-center mt-1">
                                        <span class="text-[10px] text-slate-400">${n.time}</span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-md font-semibold uppercase tracking-wider ${n.status === 'ready' ? 'bg-emerald-50 text-emerald-600' : n.status === 'completed' ? 'bg-slate-100 text-slate-600' : 'bg-red-50 text-red-600'}">${n.status}</span>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                }
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count;
                    badge.className = 'absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[10px] flex items-center justify-center rounded-full';
                } else {
                    badge.className = 'hidden';
                }
            })
            .catch(err => {
                list.innerHTML = `<div class="p-6 text-center text-xs text-red-400">Error: ${err.message}</div>`;
            });
    }

    <?php if (isLoggedIn()): ?>
    setInterval(loadNotifications, 30000);
    <?php endif; ?>
</script>

<main class="container mx-auto px-6 pt-24">
