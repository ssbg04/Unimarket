</main>

<footer class="bg-brand-background border-t border-brand-muted/20 py-20 px-6 transition-colors duration-300">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
            <!-- Brand -->
            <div class="col-span-1 md:col-span-1 space-y-6">
                 <a href="/index.php" class="flex items-center gap-3 group">
                     <img src="/assets/images/logo/logo.svg" alt="UniMarket Logo" class="h-7 w-auto transition-all dark:invert">
                     <span class="serif text-xl font-semibold tracking-tight text-brand-text">UniMarket</span>
                 </a>
                <p class="text-brand-muted font-light text-sm leading-relaxed max-w-xs">
                    The university's premier exchange for students. Curated, secure, and community-driven.
                </p>
                <div class="flex gap-4 text-brand-muted">
                    <a href="#" class="hover:text-brand-text transition-colors"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="hover:text-brand-text transition-colors"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="hover:text-brand-text transition-colors"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            
            <!-- Links -->
            <div class="space-y-6">
                <h4 class="serif text-lg text-brand-text">Navigation</h4>
                <ul class="space-y-3 text-sm font-medium text-brand-muted">
                    <li><a href="/index.php" class="hover:text-brand-text transition-colors">Home</a></li>
                    <?php if (!isLoggedIn()): ?>
                        <li><a href="/auth/login.php" class="hover:text-brand-text transition-colors">Login</a></li>
                        <li><a href="/auth/register.php" class="hover:text-brand-text transition-colors">Register</a></li>
                    <?php else: ?>
                        <?php if (isCustomer()): ?>
                            <li><a href="/customer/products/browse.php" class="hover:text-brand-text transition-colors">Browse Products</a></li>
                            <li><a href="/customer/profile.php" class="hover:text-brand-text transition-colors">My Profile</a></li>
                        <?php else: ?>
                            <li><a href="/owner/dashboard.php" class="hover:text-brand-text transition-colors">Dashboard</a></li>
                            <li><a href="/owner/products/add.php" class="hover:text-brand-text transition-colors">Add Product</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Support -->
            <div class="space-y-6">
                <h4 class="serif text-lg text-brand-text">Support</h4>
                <ul class="space-y-3 text-sm font-medium text-brand-muted">
                    <li class="flex items-center gap-3 hover:text-brand-text transition-colors cursor-pointer">
                        <i class="fas fa-envelope w-4"></i> contact@unimarket.edu
                    </li>
                    <li class="flex items-center gap-3 hover:text-brand-text transition-colors cursor-pointer">
                        <i class="fas fa-phone w-4"></i> (123) 456-7890
                    </li>
                    <li class="flex items-center gap-3 hover:text-brand-text transition-colors cursor-pointer">
                        <i class="fas fa-map-marker-alt w-4"></i> University Campus
                    </li>
                </ul>
            </div>
            
            <!-- Newsletter -->
            <div class="space-y-6">
                <h4 class="serif text-lg text-brand-text">Stay Updated</h4>
                <p class="text-brand-muted text-sm font-light leading-relaxed">Get notified about the latest campus arrivals.</p>
                <form class="flex gap-2">
                    <input type="email" placeholder="Email address" class="bg-brand-surface border border-brand-muted/20 rounded-xl px-4 py-2 text-sm w-full focus:outline-none focus:ring-2 focus:ring-brand-primary transition-all text-brand-text">
                    <button class="bg-brand-text text-brand-background px-4 py-2 rounded-xl text-sm font-medium hover:bg-brand-primary transition-all">Join</button>
                </form>
            </div>
        </div>
        
        <div class="pt-8 border-t border-brand-muted/20 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-brand-muted text-xs font-light">&copy; <?php echo date('Y'); ?> UniMarket. All rights reserved.</p>
            <div class="flex gap-6 text-xs font-medium text-brand-muted">
                <a href="#" class="hover:text-brand-text transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-brand-text transition-colors">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<script src="/assets/js/script.js"></script>
</body>
</html>
