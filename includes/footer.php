</main>
        
        <footer style="background-color: var(--primary-dark); color: var(--light-text); padding: 40px 0; margin-top: 40px;">
            <div class="container" style="background: transparent; box-shadow: none; backdrop-filter: none; -webkit-backdrop-filter: none; border: none; margin: 0 auto; padding: 0 30px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px;">
                    <div>
                        <h3 style="margin-bottom: 15px; color: var(--light-text);">UniMarket</h3>
                        <p>Your university's premier marketplace for buying and selling products within the campus community.</p>
                    </div>
                    
                    <div>
                        <h3 style="margin-bottom: 15px; color: var(--light-text);">Quick Links</h3>
                        <ul style="list-style: none;">
                            <li style="margin-bottom: 8px;"><a href="/index.php" style="color: var(--light-text); text-decoration: none;">Home</a></li>
                            <?php if (!isLoggedIn()): ?>
                                <li style="margin-bottom: 8px;"><a href="/auth/login.php" style="color: var(--light-text); text-decoration: none;">Login</a></li>
                                <li style="margin-bottom: 8px;"><a href="/auth/register.php" style="color: var(--light-text); text-decoration: none;">Register</a></li>
                            <?php else: ?>
                                <?php if (isCustomer()): ?>
                                    <li style="margin-bottom: 8px;"><a href="/customer/products/browse.php" style="color: var(--light-text); text-decoration: none;">Browse Products</a></li>
                                    <li style="margin-bottom: 8px;"><a href="/customer/profile.php" style="color: var(--light-text); text-decoration: none;">My Profile</a></li>
                                <?php else: ?>
                                    <li style="margin-bottom: 8px;"><a href="/owner/dashboard.php" style="color: var(--light-text); text-decoration: none;">Dashboard</a></li>
                                    <li style="margin-bottom: 8px;"><a href="/owner/products/add.php" style="color: var(--light-text); text-decoration: none;">Add Product</a></li>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 style="margin-bottom: 15px; color: var(--light-text);">Contact</h3>
                        <ul style="list-style: none;">
                            <li style="margin-bottom: 8px;"><i class="fas fa-envelope" style="margin-right: 8px;"></i> contact@unimarket.edu</li>
                            <li style="margin-bottom: 8px;"><i class="fas fa-phone" style="margin-right: 8px;"></i> (123) 456-7890</li>
                            <li style="margin-bottom: 8px;"><i class="fas fa-map-marker-alt" style="margin-right: 8px;"></i> University Campus</li>
                        </ul>
                    </div>
                </div>
                
                <div style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 30px; padding-top: 20px; text-align: center;">
                    <p>&copy; <?php echo date('Y'); ?> UniMarket. All rights reserved.</p>
                    <div style="margin-top: 15px;">
                        <a href="#" style="color: var(--light-text); margin: 0 10px; font-size: 1.2rem;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: var(--light-text); margin: 0 10px; font-size: 1.2rem;"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="color: var(--light-text); margin: 0 10px; font-size: 1.2rem;"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </footer>
        
        <script src="/assets/js/script.js"></script>
    </body>
</html>
