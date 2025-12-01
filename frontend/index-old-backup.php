<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demolition Traders Hamilton - NZ's Largest Demolition Yard</title>
    <meta name="description" content="Browse thousands of new & recycled renovation materials. New Zealand's largest supplier of demolition materials.">
    <base href="/demolitiontraders/frontend/">
    <link rel="stylesheet" href="assets/css/new-style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <!-- Hero Banner -->
    <section class="hero-banner">
        <img src="assets/images/hero-banner.jpg" alt="Demolition Traders Hamilton">
        <div class="hero-content">
            <h1>NZ'S LARGEST DEMOLITION YARD</h1>
            <h2>BROWSE THOUSANDS OF NEW & RECYCLED RENOVATION MATERIALS</h2>
            <p>New Zealand's largest supplier of new and recycled renovation materials.</p>
            <div class="hero-buttons">
                <a href="shop.php">Shop Now</a>
                <a href="contact.php" class="alt">Visit Our Yard</a>
            </div>
        </div>
    </section>
    
    <!-- Featured Products -->
    <section class="featured-products">
        <div class="container">
            <h3 class="center"><span>Featured Products</span></h3>
            <div class="products-grid" id="featured-products">
                <!-- Products loaded via JavaScript -->
            </div>
        </div>
    </section>
    
    <!-- Info Boxes -->
    <section class="info-section">
        <div class="container">
            <div class="info-boxes">
                <div class="info-box-large">
                    <h2>Genuinely helpful team</h2>
                    <p>Our knowledgeable team will help you make the right decision when selecting your building renovation materials</p>
                </div>
                <div class="info-box-large">
                    <h2>Easy delivery options</h2>
                    <p>Speak with one of our team to discuss delivery options or to borrow one of our courtesy trailers</p>
                </div>
                <div class="info-box-large">
                    <h2>Fair price policy</h2>
                    <p>We aim to price our stock as reasonably and fair as possible, if you can find a better price at another store let us know and we may be able to beat it</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-grid">
                <div class="cta-box">
                    <h3 class="blue">Struggling to find something?</h3>
                    <h2>List your wanted item</h2>
                    <p>Let us know what items you can't find on our website and one of our friendly staff will have a look on our yard for you.</p>
                    <p class="center"><a href="wanted-listing.php" class="btn btn-primary">List your item</a></p>
                </div>
                <div class="cta-box">
                    <h3 class="blue">Unused items on your hands?</h3>
                    <h2>Sell your unwanted items</h2>
                    <p>Let us know if you have items you are looking to sell. We are interested in good quality aluminium and wooden joinery, kitchens, roofing iron, T&G flooring and much more.</p>
                    <p class="center"><a href="sell-to-us.php" class="btn btn-primary">Sell us your items</a></p>
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
                <a href="shop.php?category=doors" class="range-card">
                    <div class="range-image" style="background-image: url('assets/images/categories/doors.jpg')"></div>
                    <h2>Doors</h2>
                </a>
                <a href="shop.php?category=native-timber" class="range-card">
                    <div class="range-image" style="background-image: url('assets/images/categories/timber.jpg')"></div>
                    <h2>Native Timber</h2>
                </a>
                <a href="shop.php?category=windows" class="range-card">
                    <div class="range-image" style="background-image: url('assets/images/categories/windows.jpg')"></div>
                    <h2>Windows</h2>
                </a>
                <a href="shop.php?category=plywood" class="range-card">
                    <div class="range-image" style="background-image: url('assets/images/categories/plywood.jpg')"></div>
                    <h2>Plywood</h2>
                </a>
                <a href="shop.php?category=pavers" class="range-card">
                    <div class="range-image" style="background-image: url('assets/images/categories/pavers.jpg')"></div>
                    <h2>Pavers</h2>
                </a>
                <a href="shop.php?category=kitchens" class="range-card">
                    <div class="range-image" style="background-image: url('assets/images/categories/kitchens.jpg')"></div>
                    <h2>Kitchens</h2>
                </a>
            </div>
        </div>
    </section>

    <?php include 'components/footer.php'; ?>>
                <a href="shop.php?category=doors" class="range-card">
                    <i class="fas fa-door-open"></i>
                    <h3>DOORS</h3>
                </a>
                <a href="shop.php?category=timber-landscaping" class="range-card">
                    <i class="fas fa-tree"></i>
                    <h3>NATIVE TIMBER</h3>
                </a>
                <a href="shop.php?category=windows" class="range-card">
                    <i class="fas fa-window-restore"></i>
                    <h3>WINDOWS</h3>
                </a>
                <a href="shop.php?category=plywood" class="range-card">
                    <i class="fas fa-layer-group"></i>
                    <h3>PLYWOOD</h3>
                </a>
                <a href="shop.php?category=pavers" class="range-card">
                    <i class="fas fa-th"></i>
                    <h3>PAVERS</h3>
                </a>
                <a href="shop.php?category=kitchens" class="range-card">
                    <i class="fas fa-blender"></i>
                    <h3>KITCHENS</h3>
                </a>
            </div>
        </div>
    </section>
    
    <!-- Info Panels -->
    <section class="info-panels">
        <div class="container">
            <div class="panels-grid">
                <div class="info-panel">
                    <div class="panel-image">
                        <img src="assets/images/wanted-panel.jpg" alt="List Your Wanted Item">
                    </div>
                    <div class="panel-content">
                        <h3>STRUGGLING TO FIND SOMETHING?</h3>
                        <h2>LIST YOUR WANTED ITEM</h2>
                        <p>Let us know what items you can't find on our website and one of our friendly staff will have a look on our yard for you.</p>
                        <a href="wanted-listing.php" class="btn btn-primary">LIST YOUR ITEM</a>
                    </div>
                </div>
                
                <div class="info-panel">
                    <div class="panel-image">
                        <img src="assets/images/sell-panel.jpg" alt="Sell Your Items">
                    </div>
                    <div class="panel-content">
                        <h3>UNUSED ITEMS ON YOUR HANDS?</h3>
                        <h2>SELL YOUR UNWANTED ITEMS</h2>
                        <p>Let us know if you have items you are looking to sell. We are interested in good quality aluminium and wooden joinery, kitchens, roofing iron, T&G flooring and much more.</p>
                        <a href="sell-to-us.php" class="btn btn-primary">SELL US YOUR ITEMS</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Services -->
    <section class="services">
        <div class="container">
            <div class="services-grid">
                <div class="service-card">
                    <i class="fas fa-users"></i>
                    <h3>KNOWLEDGEABLE STAFF</h3>
                    <p>Our friendly staff are here to help guide you in making the right decision when selecting your building renovation materials</p>
                </div>
                <div class="service-card">
                    <i class="fas fa-truck"></i>
                    <h3>EASY DELIVERY OPTIONS</h3>
                    <p>Speak with one of our team to discuss delivery options or to borrow one of our courtesy trailers</p>
                </div>
                <div class="service-card">
                    <i class="fas fa-tags"></i>
                    <h3>FAIR PRICE POLICY</h3>
                    <p>We aim to price our stock as reasonably and fair as possible, if you can find a better price at another store let us know and we may be able to beat it</p>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'components/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script>
        // Load featured products
        async function loadFeaturedProducts() {
            try {
                const response = await fetch('/demolitiontraders/api/products?featured=1&per_page=4');
                const data = await response.json();
                
                const container = document.getElementById('featured-products');
                container.innerHTML = data.data.map(product => `
                    <div class="product-card">
                        <a href="product.php?slug=${product.slug}">
                            <div class="product-image">
                                <img src="${product.image || 'assets/images/logo.png'}" alt="${product.name}" onerror="this.src='assets/images/logo.png'">
                                ${product.condition_type === 'new' ? '<span class="badge badge-new">NEW</span>' : ''}
                            </div>
                            <div class="product-info">
                                <h3 class="product-name">${product.name}</h3>
                                <p class="product-price">$${parseFloat(product.price).toFixed(2)}</p>
                            </div>
                        </a>
                        <button class="btn btn-cart" onclick="addToCart(${product.id})">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </div>
                `).join('');
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }
        
        loadFeaturedProducts();
    </script>
</body>
</html>
