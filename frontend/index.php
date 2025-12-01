<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demolition Traders Hamilton - NZ's Largest Demolition Yard</title>
    <meta name="description" content="Browse thousands of new & recycled renovation materials. New Zealand's largest supplier of demolition materials.">
    <base href="<?php echo FRONTEND_PATH; ?>">
    
    <!-- Load API Helper -->
    <script src="<?php echo asset('assets/js/api-helper.js?v=1'); ?>"></script>
    
    <link rel="stylesheet" href="<?php echo asset('assets/css/new-style.css?v=6'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.4/tiny-slider.css">
    <style>
    /* Featured Products Section */
    .featured-products {
        padding: 60px 0;
        background: #f8f9fa;
    }

    .featured-products .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .featured-products h3 {
        text-align: center;
        font-size: 32px;
        margin-bottom: 40px;
        color: #2f3192;
    }

    /* Products Carousel - CRITICAL FIX */
    #featured-products {
        position: relative;
        min-height: 500px;
    }

    /* Tiny Slider Critical Fixes */
    .tns-outer {
        position: relative !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .tns-inner {
        overflow: hidden !important;
    }

    .tns-ovh {
        overflow: hidden !important;
    }

    .tns-slider {
        display: flex !important;
        align-items: stretch !important;
    }

    .tns-item {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
        height: auto !important;
    }

    .tns-visually-hidden {
        position: absolute;
        left: -10000em;
    }

    /* Product Card */
    #featured-products .product-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        margin: 0 10px;
        display: flex !important;
        flex-direction: column;
        height: 480px;
    }

    #featured-products .product-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }

    /* Product Image */
    #featured-products .product-image {
        position: relative;
        width: 100%;
        height: 300px;
        overflow: hidden;
        background: #f5f5f5;
        flex-shrink: 0;
    }

    #featured-products .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
        display: block;
    }

    #featured-products .product-card:hover .product-image img {
        transform: scale(1.1);
    }

    /* Product Actions */
    #featured-products .product-actions {
        position: absolute;
        top: 10px;
        right: 10px;
        display: flex;
        gap: 8px;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 2;
    }

    #featured-products .product-card:hover .product-actions {
        opacity: 1;
    }

    #featured-products .wishlist-btn,
    #featured-products .view-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.95);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #2f3192;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    #featured-products .wishlist-btn:hover,
    #featured-products .view-btn:hover {
        background: #2f3192;
        color: white;
        transform: scale(1.1);
    }

    /* Badges */
    #featured-products .badge {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        z-index: 1;
    }

    #featured-products .badge-new {
        background: #28a745;
        color: white;
    }

    #featured-products .badge-recycled {
        background: #17a2b8;
        color: white;
    }

    /* Product Info */
    #featured-products .product-info {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    #featured-products .product-info a {
        text-decoration: none;
        color: inherit;
    }

    #featured-products .product-info h4 {
        font-size: 16px;
        margin: 0 0 12px 0;
        color: #333;
        min-height: 48px;
        line-height: 1.5;
        transition: color 0.3s ease;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    #featured-products .product-info a:hover h4 {
        color: #2f3192;
    }

    #featured-products .product-price {
        font-size: 24px;
        font-weight: 700;
        color: #2f3192;
    }

    #featured-products .product-price small {
        font-size: 14px;
        font-weight: 400;
        color: #666;
        margin-left: 4px;
    }

    /* Tiny Slider Controls */
    .tns-controls {
        position: absolute;
        top: 40%;
        left: 0;
        right: 0;
        transform: translateY(-50%);
        pointer-events: none;
        z-index: 10;
    }

    .tns-controls button {
        position: absolute;
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.95);
        border: none;
        border-radius: 50%;
        font-size: 24px;
        color: #2f3192;
        cursor: pointer;
        pointer-events: auto;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .tns-controls button:hover {
        background: #2f3192;
        color: white;
        transform: scale(1.1);
    }

    .tns-controls button[data-controls="prev"] {
        left: -25px;
    }

    .tns-controls button[data-controls="next"] {
        right: -25px;
    }

    /* Tiny Slider Nav */
    .tns-nav {
        text-align: center;
        margin-top: 30px;
        padding-bottom: 10px;
    }

    .tns-nav button {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #ddd;
        border: none;
        margin: 0 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 0;
    }

    .tns-nav button.tns-nav-active {
        background: #2f3192;
        width: 30px;
        border-radius: 6px;
    }

    /* Scroll Animations */
    .fade-in-on-scroll {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.8s ease-out;
    }

    .fade-in-on-scroll.visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .tns-controls button[data-controls="prev"] {
            left: 10px;
        }
        .tns-controls button[data-controls="next"] {
            right: 10px;
        }
    }

    @media (max-width: 768px) {
        #featured-products .product-card {
            height: 420px;
        }
        #featured-products .product-image {
            height: 250px;
        }
        #featured-products .product-info h4 {
            font-size: 14px;
            min-height: 40px;
        }
        #featured-products .product-price {
            font-size: 20px;
        }
    }
</style>
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <?php
    function is_mobile_device() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $mobile_agents = ['Android', 'iPhone', 'iPad', 'iPod', 'Opera Mini', 'IEMobile', 'Mobile'];
        foreach ($mobile_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                return true;
            }
        }
        return false;
    }
    ?>
    
    <?php if (!is_mobile_device()): ?>
    <!-- Hero Banner -->
    <section class="hero-banner">
        <video id="hero-video" autoplay muted loop playsinline>
            <source src="assets/images/homepage-intro@1920.mp4" type="video/mp4">
        </video>
        <audio id="hero-audio" loop>
            <source src="assets/images/jingle.mp3" type="audio/mpeg">
        </audio>
        <button class="sound-toggle" id="soundToggle">
            <i class="fas fa-volume-mute"></i>
        </button>
        <button class="minimize-toggle" id="minimizeToggle">
            <i class="fas fa-minus"></i>
        </button>
        <div class="hero-content" id="heroContent">
            <h1>NZ'S LARGEST DEMOLITION YARD</h1>
            <h2>BROWSE THOUSANDS OF NEW & RECYCLED RENOVATION MATERIALS</h2>
            <p>New Zealand's largest supplier of new and recycled renovation materials.</p>
            <div class="hero-buttons">
                <a href="shop.php">SHOP NOW</a>
                <a href="contact.php" class="alt">VISIT OUR YARD</a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Featured Products -->
    <section class="featured-products fade-in-on-scroll">
        <div class="container">
            <h3><span>Featured Products</span></h3>
            <div id="featured-products">
                <p style="text-align:center;padding:40px;">Loading products...</p>
            </div>
        </div>
    </section>
    
    <!-- Info Boxes -->
    <section class="info-boxes-section fade-in-on-scroll">
        <div class="container">
            <div class="info-boxes-grid">
                <div class="info-box-item">
                    <h2>Genuinely helpful team</h2>
                    <p>Our knowledgeable team will help you make the right decision when selecting your building renovation materials</p>
                </div>
                <div class="info-box-item">
                    <h2>Easy delivery options</h2>
                    <p>Speak with one of our team to discuss delivery options or to borrow one of our courtesy trailers</p>
                </div>
                <div class="info-box-item">
                    <h2>Fair price policy</h2>
                    <p>We aim to price our stock as reasonably and fair as possible</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Panels -->
    <section class="cta-panels fade-in-on-scroll">
        <div class="container">
            <div class="cta-panels-grid">
                <div class="cta-panel">
                    <h3 class="blue">Struggling to find something?</h3>
                    <h2>List your wanted item</h2>
                    <p>Let us know what items you can't find on our website.</p>
                    <p class="button-wrap"><a href="wanted-listing.php" class="btn">List your item</a></p>
                    <img src="assets/images/home_panel1.jpg" alt="">
                </div>
                <div class="cta-panel">
                    <h3 class="blue">Unused items on your hands?</h3>
                    <h2>Sell your unwanted items</h2>
                    <p>We are interested in good quality aluminium and wooden joinery, kitchens, and more.</p>
                    <p class="button-wrap"><a href="sell-to-us.php" class="btn">Sell us your items</a></p>
                    <img src="assets/images/home_panel2.jpg" alt="">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Popular Ranges -->
<section class="popular-ranges fade-in-on-scroll">
    <div class="container">
        <h3 class="center"><span>Popular Ranges</span></h3>
        <div class="ranges-grid">
            <a href="shop.php?category=doors" class="range-card" style="background-image: url('assets/images/ranges/doors.jpg')">
                <span class="range-title">Doors</span>
            </a>
            <a href="shop.php?category=native-timber" class="range-card" style="background-image: url('assets/images/ranges/timber.jpg')">
                <span class="range-title">Native Timber</span>
            </a>
            <a href="shop.php?category=windows" class="range-card" style="background-image: url('assets/images/ranges/windows.jpg')">
                <span class="range-title">Windows</span>
            </a>
            <a href="shop.php?category=plywood" class="range-card" style="background-image: url('assets/images/ranges/plywood.jpg')">
                <span class="range-title">Plywood</span>
            </a>
            <a href="shop.php?category=pavers" class="range-card" style="background-image: url('assets/images/ranges/pavers.jpg')">
                <span class="range-title">Pavers</span>
            </a>
            <a href="shop.php?category=kitchens" class="range-card" style="background-image: url('assets/images/ranges/kitchens.jpg')">
                <span class="range-title">Kitchens</span>
            </a>
        </div>
    </div>
</section>

    <?php include 'components/footer.php'; ?>
<?php include 'components/toast-notification.php'; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.4/min/tiny-slider.js"></script>
    <script>
        // Scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.fade-in-on-scroll').forEach(el => {
            observer.observe(el);
        });

        // Placeholder image
        const DEFAULT_IMAGE = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="300" height="300"%3E%3Crect fill="%23f0f0f0" width="300" height="300"/%3E%3Ctext fill="%23666" font-family="Arial" font-size="18" x="50%25" y="50%25" text-anchor="middle" dy=".3em"%3ENo Image%3C/text%3E%3C/svg%3E';

        // Load featured products
        async function loadFeaturedProducts() {
            try {
                const data = await apiFetch(getApiUrl('/api/index.php?request=products&is_featured=1&per_page=12'));
                
                const products = data.data || [];
                const container = document.getElementById('featured-products');
                
                if (!products.length) {
                    container.innerHTML = '<p style="text-align:center;padding:40px;">No featured products available.</p>';
                    return;
                }
                
                container.innerHTML = products.map(product => `
                    <div class="product-card">
                        <div class="product-image">
                            <a href="product-detail.php?id=${product.id}">
                                <img src="${product.image || DEFAULT_IMAGE}" 
                                     alt="${product.name}"
                                     onerror="this.onerror=null;this.src='assets/images/logo.png'">
                            </a>
                            <div class="product-actions">
                                <button class="wishlist-btn" onclick="addToWishlist(${product.id})">
                                    <i class="far fa-heart"></i>
                                </button>
                                <a href="product-detail.php?id=${product.id}" class="view-btn">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                            ${product.condition_type === 'new' ? '<span class="badge badge-new">New</span>' : ''}
                            ${product.condition_type === 'recycled' ? '<span class="badge badge-recycled">Recycled</span>' : ''}
                        </div>
                        <div class="product-info">
                            <a href="product-detail.php?id=${product.id}">
                                <h4>${product.name.length > 60 ? product.name.substring(0, 60) + '...' : product.name}</h4>
                            </a>
                            <div class="product-price">
                                $${parseFloat(product.price).toFixed(2)}
                                <small>EA</small>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                // Initialize Tiny Slider
                setTimeout(() => {
                    if (typeof tns !== 'undefined') {
                        tns({
                            container: '#featured-products',
                            items: 1,
                            slideBy: 1,
                            autoplay: true,
                            autoplayTimeout: 5000,
                            autoplayButtonOutput: false,
                            controls: true,
                            controlsText: ['‹', '›'],
                            nav: true,
                            mouseDrag: true,
                            gutter: 20,
                            loop: true,
                            responsive: {
                                0: { items: 1 },
                                600: { items: 2 },
                                900: { items: 3 },
                                1200: { items: 4 }
                            }
                        });
                    }
                }, 100);
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('featured-products').innerHTML = '<p style="text-align:center;padding:40px;color:#dc3545;">Error loading products.</p>';
            }
        }

        // Add to wishlist
        async function addToWishlist(productId) {
            try {
                const data = await apiFetch(getApiUrl('/api/wishlist/add.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                });
                alert(data.success ? 'Added to wishlist!' : 'Failed to add to wishlist');
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // Update cart count
        async function updateCartCount() {
            try {
                const data = await apiFetch(getApiUrl('/api/index.php?request=cart/get'));
                const cartCount = document.getElementById('cart-count');
                if (cartCount && data.items) {
                    cartCount.textContent = data.items.reduce((sum, item) => sum + parseInt(item.quantity || 0), 0);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadFeaturedProducts();
            updateCartCount();
            
            // Sound toggle
            const soundToggle = document.getElementById('soundToggle');
            const heroAudio = document.getElementById('hero-audio');
            const heroVideo = document.getElementById('hero-video');
            
            if (soundToggle && heroAudio && heroVideo) {
                let soundEnabled = false;
                soundToggle.addEventListener('click', function() {
                    soundEnabled = !soundEnabled;
                    if (soundEnabled) {
                        heroAudio.play();
                        heroVideo.muted = false;
                        soundToggle.innerHTML = '<i class="fas fa-volume-up"></i>';
                    } else {
                        heroAudio.pause();
                        heroVideo.muted = true;
                        soundToggle.innerHTML = '<i class="fas fa-volume-mute"></i>';
                    }
                });
            }

            // Minimize toggle
            const minimizeToggle = document.getElementById('minimizeToggle');
            const heroContent = document.getElementById('heroContent');
            
            if (minimizeToggle && heroContent) {
                let isMinimized = false;
                minimizeToggle.addEventListener('click', function() {
                    isMinimized = !isMinimized;
                    heroContent.classList.toggle('minimized');
                    minimizeToggle.innerHTML = isMinimized ? '<i class="fas fa-plus"></i>' : '<i class="fas fa-minus"></i>';
                });
            }
        });
    </script>
</body>
</html>