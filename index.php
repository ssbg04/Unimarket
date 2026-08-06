<?php
require_once 'includes/auth_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniMarket | Premium Campus Exchange</title>

    <!-- Tailwind CSS CDN (must come BEFORE any Tailwind config) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts: Playfair Display (Serif) & Inter (Sans) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome for high-quality icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind config (set BEFORE Tailwind finishes processing) -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    },
                    colors: {
                        brand: {
                            primary: 'var(--color-primary)',
                            secondary: 'var(--color-secondary)',
                            accent: 'var(--color-accent)',
                            background: 'var(--color-background)',
                            surface: 'var(--color-surface)',
                            text: 'var(--color-text)',
                            muted: 'var(--color-muted)',
                            border: 'var(--color-border)',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        :root[data-theme="light"] {
            --color-primary: #006400;
            --color-secondary: #ffffff;
            --color-accent: #064e3b;
            --color-background: #ffffff;
            --color-surface: #f8fafc;
            --color-text: #0f172a;
            --color-muted: #64748b;
            --color-border: #e2e8f0;
        }
        :root[data-theme="dark"] {
            --color-primary: #006400;
            --color-secondary: #000000;
            --color-accent: #064e3b;
            --color-background: #000000;
            --color-surface: #0f172a;
            --color-text: #f8fafc;
            --color-muted: #94a3b8;
            --color-border: #334155;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--color-background);
            color: var(--color-text);
            transition: background-color 0.3s, color 0.3s;
        }
        .serif { font-family: 'Playfair Display', serif; }

        .glass {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        [data-theme="dark"] .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .hero-overlay {
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        [data-theme="dark"] .hero-overlay {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
    </style>

    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
        }
    </script>
</head>
<body class="antialiased">
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center px-6 py-24 overflow-hidden">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 -z-10">
            <img src="/assets/images/hero/hero-bg.jpg" class="w-full h-full object-cover" alt="Campus Background">
            <div class="absolute inset-0 hero-overlay"></div>
        </div>

        <div class="max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-2 gap-12 items-center relative z-10">
            <!-- Content Column -->
            <div class="text-left space-y-8" id="hero-text">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-primary/10 text-brand-primary text-xs font-semibold uppercase tracking-wider border border-brand-primary/20">
                    <i class="fas fa-sparkles"></i> Exclusive Student Marketplace
                </div>
                <h1 class="serif text-5xl md:text-7xl lg:text-8xl tracking-tight leading-[1.1] text-brand-text">
                    Elevating the <span class="text-brand-primary italic">Campus</span><br>Exchange Experience.
                </h1>
                <p class="text-lg md:text-xl text-brand-muted max-w-xl font-light leading-relaxed">
                    A curated marketplace for student essentials. Minimalist, secure, and designed exclusively for the university community.
                </p>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 pt-4">
                    <?php if (!isLoggedIn()): ?>
                        <a href="/auth/register.php" class="px-8 py-4 bg-brand-text text-brand-background rounded-full hover:bg-brand-primary transition-all duration-300 w-full sm:w-auto font-medium shadow-lg shadow-black/20 flex items-center justify-center gap-2">
                            Join the Community <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                        <a href="/auth/login.php" class="px-8 py-4 border border-brand-muted/30 rounded-full hover:bg-brand-surface transition-all duration-300 w-full sm:w-auto font-medium text-center text-brand-text">Sign In</a>
                    <?php else: ?>
                        <?php if (isCustomer()): ?>
                            <a href="/customer/products/browse.php" class="px-8 py-4 bg-brand-text text-brand-background rounded-full hover:bg-brand-primary transition-all duration-300 w-full sm:w-auto font-medium flex items-center justify-center gap-2">
                                Browse Collection <i class="fas fa-shopping-bag text-xs"></i>
                            </a>
                        <?php else: ?>
                            <a href="/owner/dashboard.php" class="px-8 py-4 bg-brand-text text-brand-background rounded-full hover:bg-brand-primary transition-all duration-300 w-full sm:w-auto font-medium flex items-center justify-center gap-2">
                                Manage Shop <i class="fas fa-store text-xs"></i>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Visual/Stats Column -->
            <div class="hidden lg:grid grid-cols-2 gap-6" id="hero-visuals">
                <div class="p-8 bg-white/60 dark:bg-brand-surface/60 backdrop-blur-md rounded-3xl border border-white/40 dark:border-brand-border shadow-sm space-y-4">
                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 text-brand-accent dark:text-brand-primary rounded-xl flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <h3 class="serif text-xl text-brand-text">Secure Trade</h3>
                    <p class="text-brand-muted text-sm font-light">Verified university accounts ensure safety for every exchange.</p>
                </div>
                <div class="p-8 bg-white/60 dark:bg-brand-surface/60 backdrop-blur-md rounded-3xl border border-white/40 dark:border-brand-border shadow-sm space-y-4">
                    <div class="w-10 h-10 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-xl flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-location-dot"></i>
                    </div>
                    <h3 class="serif text-xl text-brand-text">Easy Pickup</h3>
                    <p class="text-brand-muted text-sm font-light">Coordinate fast, free pickups at designated campus spots.</p>
                </div>
                <div class="p-8 bg-white/60 dark:bg-brand-surface/60 backdrop-blur-md rounded-3xl border border-white/40 dark:border-brand-border shadow-sm space-y-4">
                    <div class="w-10 h-10 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-xl flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3 class="serif text-xl text-brand-text">Student Focus</h3>
                    <p class="text-brand-muted text-sm font-light">Books, supplies, and gear curated by students, for students.</p>
                </div>
                <div class="p-8 bg-white/60 dark:bg-brand-surface/60 backdrop-blur-md rounded-3xl border border-white/40 dark:border-brand-border shadow-sm space-y-4">
                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 text-brand-accent dark:text-brand-primary rounded-xl flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3 class="serif text-xl text-brand-text">Eco-Friendly</h3>
                    <p class="text-brand-muted text-sm font-light">Reduce waste by recycling campus essentials efficiently.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Value Propositions -->
    <section class="py-24 px-6 bg-brand-surface transition-colors duration-300">
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-16">
                <div class="space-y-4 group">
                    <div class="w-14 h-14 bg-brand-background rounded-2xl flex items-center justify-center shadow-sm text-xl mb-6 transition-all duration-300 group-hover:bg-brand-primary group-hover:text-brand-background group-hover:rotate-3 text-brand-primary">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3 class="serif text-2xl text-brand-text">Curated Marketplace</h3>
                    <p class="text-brand-muted font-light leading-relaxed">Exclusive access to university-verified products and student essentials.</p>
                </div>
                <div class="space-y-4 group">
                    <div class="w-14 h-14 bg-brand-background rounded-2xl flex items-center justify-center shadow-sm text-xl mb-6 transition-all duration-300 group-hover:bg-brand-primary group-hover:text-brand-background group-hover:rotate-3 text-brand-primary">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3 class="serif text-2xl text-brand-text">Intuitive Pickup</h3>
                    <p class="text-brand-muted font-light leading-relaxed">Seamless on-campus coordination designed for the student schedule.</p>
                </div>
                <div class="space-y-4 group">
                    <div class="w-14 h-14 bg-brand-background rounded-2xl flex items-center justify-center shadow-sm text-xl mb-6 transition-all duration-300 group-hover:bg-brand-primary group-hover:text-brand-background group-hover:rotate-3 text-brand-primary">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3 class="serif text-2xl text-brand-text">Trust-Based Trade</h3>
                    <p class="text-brand-muted font-light leading-relaxed">Safe, community-driven transactions within a secure university environment.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Trending Section -->
    <?php
    $trending_products = [];
    try {
        if (!isLoggedIn() || isCustomer()) {
            require_once 'config/database.php';
            if (isset($pdo)) {
                $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 6");
                $trending_products = $stmt->fetchAll();
            }
        }
    } catch (Exception $e) {
        error_log("Trending products error: " . $e->getMessage());
    }

    if (!empty($trending_products)): ?>
    <section class="py-24 px-6">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-end justify-between mb-12">
                <div class="text-left">
                    <h2 class="serif text-4xl mb-2 text-brand-text">Latest Arrivals</h2>
                    <p class="text-brand-muted font-light">Freshly listed essentials from your peers.</p>
                </div>
                <a href="/customer/products/browse.php" class="hidden md:block text-sm font-medium underline underline-offset-4 hover:text-brand-accent transition-colors">View all products &rarr;</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($trending_products as $product): ?>
                <div class="group relative bg-brand-background border border-brand-border rounded-3xl p-4 hover:shadow-xl transition-all duration-500 glass">
                    <div class="aspect-square rounded-2xl overflow-hidden bg-brand-surface mb-6">
                        <?php if (!empty($product['image_path'])): ?>
                            <img src="/uploads/products/<?= htmlspecialchars($product['image_path']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" alt="<?= htmlspecialchars($product['name']) ?>">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-brand-muted italic">No image available</div>
                        <?php endif; ?>
                    </div>
                    <div class="px-2">
                        <h3 class="text-lg font-medium mb-1 text-brand-text"><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="text-brand-accent font-semibold mb-4">₱<?= number_format($product['price'], 2) ?></p>
                        <?php if (isLoggedIn()): ?>
                            <a href="/customer/products/view.php?id=<?= $product['product_id'] ?>" class="block w-full py-3 text-center bg-brand-surface rounded-xl text-sm font-medium hover:bg-brand-text hover:text-brand-background transition-all duration-300">View Details</a>
                        <?php else: ?>
                            <a href="/auth/login.php" class="block w-full py-3 text-center bg-brand-surface rounded-xl text-sm font-medium hover:bg-brand-text hover:text-brand-background transition-all duration-300">Login to Purchase</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Final CTA -->
    <section class="py-24 px-6 bg-brand-text text-brand-background text-center transition-colors duration-300">
        <div class="max-w-3xl mx-auto">
            <h2 class="serif text-4xl md:text-5xl mb-6">Join the inner circle of campus trade.</h2>
            <p class="text-brand-muted font-light mb-10 text-lg">Simpler, cleaner, and faster. The way university shopping should be.</p>
            <?php if (!isLoggedIn()): ?>
                <a href="/auth/register.php" class="px-10 py-4 bg-brand-background text-brand-text rounded-full font-medium hover:bg-brand-primary hover:text-brand-background transition-all duration-300">Get Started</a>
            <?php else: ?>
                <?php if (isCustomer()): ?>
                    <a href="/customer/products/browse.php" class="px-10 py-4 bg-brand-background text-brand-text rounded-full font-medium hover:bg-brand-primary hover:text-brand-background transition-all duration-300">Browse Products</a>
                <?php else: ?>
                    <a href="/owner/products/add.php" class="px-10 py-4 bg-brand-background text-brand-text rounded-full font-medium hover:bg-brand-primary hover:text-brand-background transition-all duration-300">List a Product</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const heroText = document.getElementById('hero-text');
            const heroVisuals = document.getElementById('hero-visuals');

            if (heroText) {
                heroText.style.opacity = '0';
                heroText.style.transform = 'translateX(-50px)';
                heroText.style.transition = 'all 1s ease-out';
                setTimeout(() => {
                    heroText.style.opacity = '1';
                    heroText.style.transform = 'translateX(0)';
                }, 100);
            }

            if (heroVisuals) {
                heroVisuals.style.opacity = '0';
                heroVisuals.style.transform = 'translateX(50px)';
                heroVisuals.style.transition = 'all 1s ease-out 0.3s';
                setTimeout(() => {
                    heroVisuals.style.opacity = '1';
                    heroVisuals.style.transform = 'translateX(0)';
                }, 100);
            }

            const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('section').forEach(el => {
                if (el.id === 'hero-text' || el.id === 'hero-visuals') return;
                el.style.opacity = '0';
                el.style.transform = 'translateY(40px)';
                el.style.transition = 'opacity 1s ease-out, transform 1s ease-out';
                observer.observe(el);
            });
        });
    </script>
</body>
</html>
