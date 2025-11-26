<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demolition Traders Hamilton - NZ's Largest Demolition Yard</title>
    <meta name="description" content="Browse thousands of new & recycled renovation materials. New Zealand's largest supplier of demolition materials.">
    <base href="/demolitiontraders/frontend/">
    <link rel="stylesheet" href="assets/css/new-style.css?v=5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <button class="sound-toggle" id="soundToggle" aria-label="Toggle sound">
                <i class="fas fa-volume-mute"></i>
            </button>
            <button class="minimize-toggle" id="minimizeToggle" aria-label="Minimize content">
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
    <section class="featured-products">
        <div class="container">
            <h3 class="center"><span>Featured Products</span></h3>
            <div class="products-carousel" id="featured-products">
                <!-- Products loaded via JavaScript -->
            </div>
        </div>
    </section>
    
    <!-- Info Boxes -->
    <section class="info-boxes-section">
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
                    <p>We aim to price our stock as reasonably and fair as possible, if you can find a better price at another store let us know and we may be able to beat it</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Panels -->
    <section class="cta-panels">
        <div class="container">
            <div class="cta-panels-grid">
                <div class="cta-panel">
                    <h3 class="blue">Struggling to find something?</h3>
                    <h2>List your wanted item</h2>
                    <p>Let us know what items you can't find on our website and one of our friendly staff will have a look on our yard for you.</p>
                    <p class="button-wrap"><a href="wanted-listing.php" class="btn">List your item</a></p>
                    <img src="assets/images/home_panel1.jpg" alt="">
                </div>
                <div class="cta-panel">
                    <h3 class="blue">Unused items on your hands?</h3>
                    <h2>Sell your unwanted items</h2>
                    <p>Let us know if you have items you are looking to sell. We are interested in good quality aluminium and wooden joinery, kitchens, roofing iron, T&G flooring and much more.</p>
                    <p class="button-wrap"><a href="sell-to-us.php" class="btn">Sell us your items</a></p>
                    <img src="assets/images/home_panel2.jpg" alt="">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Popular Ranges -->
    <section class="popular-ranges">
        <div class="container">
            <h3 class="center"><span>Popular Ranges</span></h3>
            <p class="small center">A hand picked selection of our most popular ranges</p>
            <div class="ranges-grid">
                <a href="shop.php?category=doors" class="range-card" style="background-image: url('https://via.placeholder.com/400x400/2f3192/ffffff?text=Doors')">
                    <span class="range-title">Doors</span>
                </a>
                <a href="shop.php?category=native-timber" class="range-card" style="background-image: url('https://via.placeholder.com/400x400/2f3192/ffffff?text=Timber')">
                    <span class="range-title">Native Timber</span>
                </a>
                <a href="shop.php?category=windows" class="range-card" style="background-image: url('https://via.placeholder.com/400x400/2f3192/ffffff?text=Windows')">
                    <span class="range-title">Windows</span>
                </a>
                <a href="shop.php?category=plywood" class="range-card" style="background-image: url('https://via.placeholder.com/400x400/2f3192/ffffff?text=Plywood')">
                    <span class="range-title">Plywood</span>
                </a>
                <a href="shop.php?category=pavers" class="range-card" style="background-image: url('https://via.placeholder.com/400x400/2f3192/ffffff?text=Pavers')">
                    <span class="range-title">Pavers</span>
                </a>
                <a href="shop.php?category=kitchens" class="range-card" style="background-image: url('https://via.placeholder.com/400x400/2f3192/ffffff?text=Kitchens')">
                    <span class="range-title">Kitchens</span>
                </a>
            </div>
        </div>
    </section>

    <?php include 'components/footer.php'; ?>
    
    <script>
        // Load featured products
        async function loadFeaturedProducts() {
            try {
                const response = await fetch('/demolitiontraders/backend/api/products/featured.php');
                const data = await response.json();
                
                if (data.success && data.products) {
                    const container = document.getElementById('featured-products');
                    container.innerHTML = data.products.slice(0, 10).map(product => `
                        <div class="product-card">
                            <div class="product-image">
                                <a href="product-detail.php?id=${product.id}">
                                    <img src="${product.image_url}" alt="${product.name}">
                                </a>
                                <div class="product-actions">
                                    <a href="#" class="wishlist-btn"><i class="far fa-heart"></i></a>
                                    <a href="product-detail.php?id=${product.id}" class="view-btn"><i class="far fa-search"></i></a>
                                </div>
                                ${product.is_featured ? '<span class="badge badge-new">New</span>' : ''}
                                ${product.condition_type === 'recycled' ? '<span class="badge badge-recycled">Recycled</span>' : ''}
                            </div>
                            <div class="product-info">
                                <a href="product-detail.php?id=${product.id}">
                                    <h4>${product.name}</h4>
                                </a>
                                <div class="product-price">$${parseFloat(product.price).toFixed(2)} <small>EA</small></div>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading products:', error);
                document.getElementById('featured-products').innerHTML = '<p>Loading products...</p>';
            }
        }

        // Update cart count
        async function updateCartCount() {
            try {
                const response = await fetch('/demolitiontraders/backend/api/cart/get.php');
                const data = await response.json();
                if (data.success) {
                    document.getElementById('cart-count').textContent = data.summary.item_count;
                }
            } catch (error) {
                console.error('Error updating cart:', error);
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadFeaturedProducts();
            updateCartCount();
            
            // Mobile menu
            const mobileToggle = document.getElementById('mobile-menu-toggle');
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    document.querySelector('.nav-menu').classList.toggle('active');
                });
            }

            // Sound toggle
            const soundToggle = document.getElementById('soundToggle');
            const heroAudio = document.getElementById('hero-audio');
            const heroVideo = document.getElementById('hero-video');
            let soundEnabled = false;

            soundToggle.addEventListener('click', function() {
                soundEnabled = !soundEnabled;
                if (soundEnabled) {
                    heroAudio.play();
                    heroVideo.muted = false;
                    soundToggle.innerHTML = '<i class="fas fa-volume-up"></i>';
                    soundToggle.classList.add('active');
                } else {
                    heroAudio.pause();
                    heroVideo.muted = true;
                    soundToggle.innerHTML = '<i class="fas fa-volume-mute"></i>';
                    soundToggle.classList.remove('active');
                }
            });

            // Minimize/Maximize hero content
            const minimizeToggle = document.getElementById('minimizeToggle');
            const heroContent = document.getElementById('heroContent');
            let isMinimized = false;

            minimizeToggle.addEventListener('click', function() {
                isMinimized = !isMinimized;
                if (isMinimized) {
                    heroContent.classList.add('minimized');
                    minimizeToggle.innerHTML = '<i class="fas fa-plus"></i>';
                    minimizeToggle.setAttribute('aria-label', 'Maximize content');
                } else {
                    heroContent.classList.remove('minimized');
                    minimizeToggle.innerHTML = '<i class="fas fa-minus"></i>';
                    minimizeToggle.setAttribute('aria-label', 'Minimize content');
                }
            });
        });
    </script>
</body>
</html>
