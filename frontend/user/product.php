<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1 id="product-name">Product Details</h1>
            <nav class="breadcrumb">
                <a href="<?php echo userUrl('index.php'); ?>">Home</a> / <a href="<?php echo userUrl('shop.php'); ?>">Shop</a> / <span id="product-breadcrumb">Product</span>
            </nav>
        </div>
    </div>
    
    <section class="content-section">
        <div class="container">
            <div class="product-detail" id="product-detail">
                <div class="loading">Loading product details...</div>
            </div>
        </div>
    </section>
    
    <?php include '../components/footer.php'; ?>
    <?php include '../components/toast-notification.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script>
    const BASE_PATH = '<?php echo BASE_PATH; ?>';
        // Get product ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const productId = urlParams.get('id');
        
        if (!productId) {
            document.getElementById('product-detail').innerHTML = '<div class="error">Product ID is missing from URL</div>';
        } else {
            loadProduct(productId);
        }
        
        // Load product details
        async function loadProduct(id) {
            try {
                const response = await fetch('/demolitiontraders/api/products/' + id);
                const responseText = await response.text();
                const product = JSON.parse(responseText);
                if (product.error) {
                    throw new Error(product.error);
                }
                
                displayProduct(product);
            } catch (error) {
                console.error('Error loading product:', error);
                document.getElementById('product-detail').innerHTML = '<div class="error">Failed to load product details</div>';
            }
        }
        
        // Display product details
        function displayProduct(product) {
            document.getElementById('product-name').textContent = product.name;
            document.getElementById('product-breadcrumb').textContent = product.name;
            document.title = product.name + ' - Demolition Traders';
            
            const imageUrl = product.image ? product.image : 'assets/images/logo.png';
            const stockStatus = product.stock_quantity > 0 ? 
                '<span class="in-stock"><i class="fas fa-check-circle"></i> In Stock (' + product.stock_quantity + ' available)</span>' : 
                '<span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>';
            
            let collectionOptionsHtml = '';
            if (product.show_collection_options == 1 || product.show_collection_options === true) {
                collectionOptionsHtml = `
                <div class="product-collection-options" style="margin-top:32px; border-top:1px solid #eee; padding-top:24px;">
                    <h3><i class="fas fa-box"></i> Collection & Delivery Options</h3>
                    <div style="font-size:15px; color:#333;">
                        <b>OPTION A: FREE PICKUP</b><br>
                        - Pick up FREE from our Hamilton store.<br>
                        (There is no minimum sheet quantity required for pickup orders.)<br><br>
                        <b>OPTION B: DELIVERY - QUOTE REQUIRED</b><br>
                        1. Minimum Order: Strict 10-sheet minimum for delivery.<br>
                        2. Freight Quote: Due to size/weight variability, freight is NOT included.<br><br>
                        <b>TO GET A QUOTE:</b> Click 'Enquire' and provide your Quantity (min. 10) and Delivery Suburb & Postcode. We will reply with the freight cost to add to your order.
                    </div>
                </div>
                `;
            }
            const html = `
                <div class="product-layout">
                    <div class="product-images">
                        <div class="main-image">
                            <img src="${imageUrl}" alt="${product.name}" id="main-product-image" onerror="this.src='assets/images/logo.png'">
                        </div>
                    </div>
                    
                    <div class="product-info">
                        <h1 class="product-title">${product.name}</h1>
                        <div class="product-sku">SKU: ${product.sku || 'N/A'}</div>
                        
                        <div class="product-price">
                            <span class="price">$${parseFloat(product.price).toFixed(2)}</span>
                            ${product.compare_at_price ? '<span class="compare-price">$' + parseFloat(product.compare_at_price).toFixed(2) + '</span>' : ''}
                        </div>
                        
                        <div class="product-stock">
                            ${stockStatus}
                        </div>
                        
                        ${product.stock_quantity > 0 ? `
                        <div class="product-actions">
                            <div class="quantity-control">
                                <label>Quantity:</label>
                                <div class="quantity-input">
                                    <button type="button" class="qty-btn" onclick="decreaseQuantity()">−</button>
                                    <input type="number" id="quantity" value="1" min="1" max="${product.stock_quantity}" readonly>
                                    <button type="button" class="qty-btn" onclick="increaseQuantity()">+</button>
                                </div>
                            </div>
                            <button class="btn btn-buy-now" onclick="buyNow(${product.id})">
                                <i class="fas fa-bolt"></i> Buy Now
                            </button>
                            <button class="btn btn-add-cart" onclick="addToCart(${product.id})">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                            <button class="btn btn-wishlist" onclick="addToWishlist(${product.id})">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        ` : '<p class="out-of-stock-message"><i class="fas fa-exclamation-circle"></i> This product is currently out of stock.</p>'}
                        
                        <div class="product-meta">
                            <div class="meta-row">
                                <span class="meta-label"><i class="fas fa-tag"></i> Category:</span> 
                                <a href="<?php echo userUrl('shop.php?category=${product.category_id}'); ?>">${product.category_name || 'Uncategorized'}</a>
                            </div>
                            <div class="meta-row">
                                <span class="meta-label"><i class="fas fa-certificate"></i> Condition:</span> 
                                <span class="meta-value">${product.condition || 'New'}</span>
                            </div>
                        </div>
                        
                        <div class="product-description">
                            <h3><i class="fas fa-info-circle"></i> Description</h3>
                            <div class="desc-content">${product.description || 'No description available.'}</div>
                        </div>
                        
                        ${product.specifications ? `
                        <div class="product-specs">
                            <h3><i class="fas fa-clipboard-list"></i> Specifications</h3>
                            <div class="specs-content">${product.specifications}</div>
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                <div class="product-tabs">
                    <div class="tabs">
                        <button class="tab-button active" onclick="showTab('shipping')">
                            <i class="fas fa-truck"></i> Shipping & Pickup
                        </button>
                        <button class="tab-button" onclick="showTab('returns')">
                            <i class="fas fa-undo"></i> Returns Policy
                        </button>
                    </div>
                    
                    <div class="tab-content active" id="shipping-tab">
                        <div class="info-box">
                            <h4><i class="fas fa-store"></i> Free Pickup Available</h4>
                            <p>Pick up FREE from our Hamilton store at <strong>249 Kahikatea Drive, Greenlea Lane, Frankton, Hamilton</strong></p>
                            <p>No minimum quantity required for pickup orders.</p>
                        </div>
                        <div class="info-box">
                            <h4><i class="fas fa-truck"></i> Delivery Available</h4>
                            <p><strong>Minimum Order:</strong> 10 sheets minimum for delivery</p>
                            <p><strong>Freight Quote:</strong> Contact us for a delivery quote to your area</p>
                            <p>Email: <a href="mailto:info@demolitiontraders.co.nz">info@demolitiontraders.co.nz</a></p>
                            <p>Phone: <a href="tel:0800336548466">0800 DEMOLITION</a></p>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="returns-tab">
                        <div class="info-box">
                            <h4><i class="fas fa-shield-alt"></i> Returns & Refunds</h4>
                            <p>Please contact us <strong>before</strong> returning any items.</p>
                            <p>Some products may not be eligible for return due to their nature.</p>
                            <p>Contact us: <strong>0800 DEMOLITION</strong> or <a href="mailto:info@demolitiontraders.co.nz">info@demolitiontraders.co.nz</a></p>
                        </div>
                        <div class="info-box">
                            <h4><i class="fas fa-question-circle"></i> Questions?</h4>
                            <p>Our friendly staff are here to help Monday to Friday 8am - 5pm, Saturday 8am - 4pm</p>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('product-detail').innerHTML = html;
                    // Append collection options to product detail if enabled
                    if (collectionOptionsHtml) {
                        document.getElementById('product-detail').innerHTML += collectionOptionsHtml;
                    }
        }
        
        // Quantity controls
        function increaseQuantity() {
            const input = document.getElementById('quantity');
            const max = parseInt(input.max);
            const current = parseInt(input.value);
            if (current < max) {
                input.value = current + 1;
            }
        }
        
        function decreaseQuantity() {
            const input = document.getElementById('quantity');
            const min = parseInt(input.min);
            const current = parseInt(input.value);
            if (current > min) {
                input.value = current - 1;
            }
        }
        
        // Buy now function
        function buyNow(productId) {
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            
            fetch('/demolitiontraders/api/cart/add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: quantity })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success || data.cart) {
                if (data.success || data.cart) {
                    // Trigger cart update event
                    localStorage.setItem('cartUpdated', Date.now());
                    // Redirect to cart page
                    window.location.href = BASE_PATH + 'cart';
                } else {
                    alert('Failed to add product to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding product to cart');
            });
        }
        
        // Add to cart function
        function addToCart(productId) {
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            
            fetch('/demolitiontraders/api/cart/add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: quantity })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success || data.cart) {
                if (data.success || data.cart) {
                    alert('Product added to cart!');
                    updateCartCount();
                } else {
                    alert('Failed to add product to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding product to cart');
            });
        }
        
        // Tab switching
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        // Listen for cart updates from other pages
        window.addEventListener('storage', function(e) {
            if (e.key === 'cartUpdated') {
                // Re-load product to update button state
                loadProduct();
            }
        });
        
        // Also check on window focus (when user comes back from cart)
        let lastCheckTime = 0;
        window.addEventListener('focus', function() {
            const now = Date.now();
            // Prevent multiple checks within 2 seconds
            if (now - lastCheckTime < 2000) return;
            lastCheckTime = now;
            
            const lastUpdate = localStorage.getItem('cartUpdated');
            if (lastUpdate && now - lastUpdate < 10000) {
                // Cart was updated in last 10 seconds, reload product
                loadProduct();
            }
        });
    </script>
</body>
</html>
