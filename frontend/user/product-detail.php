<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="assets/css/new-style.css?v=10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
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
        // Base path for URLs
        const BASE_PATH = '<?php echo BASE_PATH; ?>';
        
        // Get product ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const productId = urlParams.get('id');

        if (!productId) {
            document.getElementById('product-detail').innerHTML = '<div class="error">Product ID is missing from URL</div>';
        } else {
            loadProductWithCart(productId);
        }

        // Reload product when page gets focus (after navigating back)
        window.addEventListener('focus', function() {
            if (productId) {
                loadProductWithCart(productId);
            }
        });
        
        // Also listen for storage changes (cart updates from other tabs/pages)
        window.addEventListener('storage', function(e) {
            if (e.key === 'cartUpdated' && productId) {
                loadProductWithCart(productId);
            }
        });
        
        // Listen for direct dispatch events from same window
        document.addEventListener('cartUpdated', function() {
            if (productId) {
                loadProductWithCart(productId);
            }
        });

   async function loadProductWithCart(id) {
    try {
        const [productRes, cartRes] = await Promise.all([
            fetch(getApiUrl('/api/index.php?request=products/') + id),
            fetch(getApiUrl('/api/index.php?request=cart/get'))
        ]);
        
        const productData = await productRes.json();
        const cart = await cartRes.json();
        
        console.log('Product response:', productData);
        
        // Backend API trả về {success: true, data: {...}}
        const product = productData.data || productData;
        
        if (product.error || !product.id) {
            throw new Error(product.error || 'Product not found');
        }
        
        // ✅ FIX: Convert images array to single image field
        if (product.images && product.images.length > 0) {
            product.image = product.images[0].url;
        }
        
        let cartQty = 0;
        if (cart.items && Array.isArray(cart.items)) {
            const cartItem = cart.items.find(i => i.product_id == id || i.product_id == product.id);
            if (cartItem) cartQty = parseInt(cartItem.quantity) || 0;
        }
        
        displayProduct(product, cartQty);
    } catch (error) {
        console.error('Error loading product:', error);
        document.getElementById('product-detail').innerHTML = '<div class="error">Failed to load product details: ' + error.message + '</div>';
    }
}
        
       // Display product details
function displayProduct(product, cartQty = 0) {
    document.getElementById('product-breadcrumb').textContent = product.name;
    document.title = product.name + ' - Demolition Traders';

    const imageUrl = product.image ? product.image : 'assets/images/logo.png';
    let availableStock = Math.max(0, (parseInt(product.stock_quantity) || 0) - (parseInt(cartQty) || 0));
    const totalStock = parseInt(product.stock_quantity) || 0;
    const inCart = parseInt(cartQty) || 0;
    
    let stockStatus = '';
    if (availableStock > 0) {
        stockStatus = `<span class="in-stock"><i class="fas fa-check-circle"></i> In Stock (${availableStock} available)</span>`;
    } else if (totalStock > 0 && inCart > 0) {
        // All stock is in cart
        stockStatus = '<span class="in-cart"><i class="fas fa-shopping-cart"></i> All available stock in cart</span>';
    } else {
        stockStatus = '<span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>';
    }

    // ✅ Check if collection options should be shown
    const showCollectionOptions = product.show_collection_options == 1 || product.show_collection_options === true;

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
                
                ${availableStock > 0 ? `
                <div class="product-actions">
                    <div class="quantity-control">
                        <label>Quantity:</label>
                        <div class="quantity-input">
                            <button type="button" class="qty-btn" onclick="decreaseQuantity()">−</button>
                            <input type="number" id="quantity" value="1" min="1" max="${availableStock}">
                            <button type="button" class="qty-btn" onclick="increaseQuantity()">+</button>
                        </div>
                    </div>
                    <button class="btn btn-buy-now" id="buy-now-btn" onclick="buyNow(${product.id})">
                        <i class="fas fa-bolt"></i> Buy Now
                    </button>
                    <button class="btn btn-add-cart" onclick="addToCart(${product.id})">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <button class="btn btn-wishlist" onclick="addToWishlist(${product.id})">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                ` : (totalStock > 0 && inCart > 0) ? `
                <div class="product-actions">
                    <button class="btn btn-buy-now" style="width:100%;max-width:400px;" onclick="buyNow(${product.id}, true)">
                        <i class="fas fa-shopping-cart"></i> Go to Cart
                    </button>
                    <button class="btn btn-wishlist" onclick="addToWishlist(${product.id})">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                ` : '<p class="out-of-stock-message" style="background:#fff3cd;border:2px solid #ffc107;border-radius:8px;padding:20px;text-align:center;color:#856404;"><i class="fas fa-exclamation-circle"></i> This product is currently out of stock.</p>'}
                
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
                
                ${showCollectionOptions ? `
                <div class="product-collection-options" style="margin-top:32px; border-top:1px solid #eee; padding-top:24px;">
                    <h3><i class="fas fa-box"></i> Collection & Delivery Options</h3>
                    <div style="font-size:15px; color:#333; line-height:1.7;">
                        <p><strong>OPTION A: FREE PICKUP</strong></p>
                        <ul style="margin-left:20px; margin-bottom:15px;">
                            <li>Pick up FREE from our Hamilton store.</li>
                            <li>There is no minimum sheet quantity required for pickup orders.</li>
                        </ul>
                        
                        <p><strong>OPTION B: DELIVERY - QUOTE REQUIRED</strong></p>
                        <ol style="margin-left:20px; margin-bottom:15px;">
                            <li><strong>Minimum Order:</strong> Strict 10-sheet minimum for delivery.</li>
                            <li><strong>Freight Quote:</strong> Due to size/weight variability, freight is NOT included.</li>
                        </ol>
                        
                        <p><strong>TO GET A QUOTE:</strong> Click 'Enquire' and provide your Quantity (min. 10) and Delivery Suburb & Postcode. We will reply with the freight cost to add to your order.</p>
                    </div>
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
    
    // Check if product is in wishlist
    checkWishlistStatus(product.id);
}
        
        // Check wishlist status
        function checkWishlistStatus(productId) {
            fetch(getApiUrl('/api/wishlist/get.php'))
                .then(response => response.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success && data.wishlist.includes(productId.toString())) {
                            const button = document.querySelector('.btn-wishlist');
                            const icon = button.querySelector('i');
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            button.classList.add('active');
                            isInWishlist = true;
                    if (data.success && data.wishlist.includes(productId.toString())) {
                        const button = document.querySelector('.btn-wishlist');
                        const icon = button.querySelector('i');
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        button.classList.add('active');
                        isInWishlist = true;
                    }
                })
                .catch(error => console.error('Error checking wishlist:', error));
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
        function buyNow(productId, goDirectlyToCart = false) {
            // If already at max stock, just go to cart
            if (goDirectlyToCart) {
                window.location.href = BASE_PATH + 'cart.php';
                return;
            }
            
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            
            // Add loading state
            const buyButton = document.querySelector('.btn-buy-now');
            buyButton.style.pointerEvents = 'none';
            buyButton.style.opacity = '0.6';
            
            fetch(getApiUrl('/api/cart/add.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: quantity }),
                credentials: 'include'
            })
            .then(response => response.text())
            .then(text => {
                const data = JSON.parse(text);
                if (data.success) {
                    // Trigger cart update event
                    localStorage.setItem('cartUpdated', Date.now());
                    document.dispatchEvent(new Event('cartUpdated'));
                    // Redirect to cart page
                    window.location.href = BASE_PATH + 'cart.php';
                } else {
                    showNotification(data.message || 'Failed to add product to cart', true);
                    buyButton.style.pointerEvents = '';
                    buyButton.style.opacity = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error adding product to cart', true);
                buyButton.style.pointerEvents = '';
                buyButton.style.opacity = '';
            });
        }
        
        // Listen for cart updates from other pages
        window.addEventListener('storage', function(e) {
            if (e.key === 'cartUpdated') {
                // Re-fetch product to update button state
                const productId = new URLSearchParams(window.location.search).get('id');
                if (productId) {
                    loadProduct(productId);
                }
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
                const productId = new URLSearchParams(window.location.search).get('id');
                if (productId) {
                    loadProduct(productId);
                }
            }
        });
        
        // Add to cart function
        function addToCart(productId) {
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            // Check if input quantity exceeds max
            const input = document.getElementById('quantity');
            const max = parseInt(input.max);
            if (quantity > max) {
                showNotification(`You have already added the maximum available stock (${max}) to your cart.`, true);
                return;
            }
            
            console.log('Adding to cart:', { productId, quantity });
            
            // Add loading state
            const addButton = document.querySelector('.btn-add-cart');
            addButton.style.pointerEvents = 'none';
            addButton.style.opacity = '0.6';
            
            // Use correct API endpoint through index.php
            const url = getApiUrl('/api/index.php?request=cart/add');
            const payload = { product_id: productId, quantity: quantity };
            
            console.log('Request URL:', url);
            console.log('Request payload:', payload);
            
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                credentials: 'include'
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed response:', data);
                    
                    // Check if we hit stock limit
                    if (data.success === false && data.message && data.message.includes('stock limit reached')) {
                        // Show "Already in Cart" modal as overlay
                        const modalHTML = `
                            <div id="already-in-cart-modal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:99999;">
                                <div style="background:white;border-radius:12px;padding:40px;max-width:500px;width:90%;box-shadow:0 10px 40px rgba(0,0,0,0.3);text-align:center;">
                                    <div style="width:80px;height:80px;background:#2f3192;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                                        <i class="fas fa-check" style="color:white;font-size:40px;"></i>
                                    </div>
                                    <h3 style="color:#2f3192;font-size:28px;margin:0 0 15px 0;font-weight:600;">Already in Your Cart!</h3>
                                    <p style="color:#666;font-size:16px;margin:0 0 30px 0;line-height:1.5;">You have this product in your cart. Would you like to checkout or continue shopping?</p>
                                    <div style="display:flex;gap:15px;justify-content:center;">
                                        <a href="<?php echo userUrl('cart.php'); ?>" style="flex:1;padding:14px 24px;background:#28a745;color:white;border:none;border-radius:8px;text-decoration:none;font-size:16px;font-weight:500;display:flex;align-items:center;justify-content:center;gap:8px;">
                                            <i class="fas fa-shopping-cart"></i> PROCEED TO CHECKOUT
                                        </a>
                                        <a href="<?php echo userUrl('shop.php'); ?>" style="flex:1;padding:14px 24px;background:#6c757d;color:white;border:none;border-radius:8px;text-decoration:none;font-size:16px;font-weight:500;display:flex;align-items:center;justify-content:center;gap:8px;">
                                            <i class="fas fa-store"></i> CONTINUE SHOPPING
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                        document.body.insertAdjacentHTML('beforeend', modalHTML);
                        
                        // Trigger cart update event
                        localStorage.setItem('cartUpdated', Date.now());
                        document.dispatchEvent(new Event('cartUpdated'));
                        
                        // Reload product UI to update button states and show correct message
                        setTimeout(() => {
                            const productId = new URLSearchParams(window.location.search).get('id');
                            if (productId) {
                                loadProductWithCart(productId);
                            }
                        }, 1500);
                        
                        return; // Exit early, don't show success notification
                    }
                    
                    if (data.success !== false && (data.items || data.summary || data.cart_count !== undefined)) {
                        // Product was successfully added
                        showNotification(`${quantity} item(s) added to cart!`);
                        
                        // Trigger cart update event for header
                        localStorage.setItem('cartUpdated', Date.now());
                        document.dispatchEvent(new Event('cartUpdated'));
                        
                        // Always reload product to get updated stock and display correct button
                        setTimeout(() => {
                            const pId = new URLSearchParams(window.location.search).get('id');
                            if (pId) loadProductWithCart(pId);
                        }, 500);
                    } else {
                        // Check for out of stock or already in cart error
                        const msg = (data.message || data.error || '').toLowerCase();
                        if (msg.includes('insufficient stock') || msg.includes('out of stock') || msg.includes('stock limit reached')) {
                            showNotification('You already have all available stock in your cart. Ready to checkout?', true);
                        } else if (msg.includes('maximum available') || msg.includes('already in cart')) {
                            showNotification('You already have this product in your cart. Ready to checkout?', true);
                        } else {
                            showNotification(data.message || data.error || 'Failed to add product to cart', true);
                        }
                    }
                } catch (e) {
                    console.error('JSON Parse error:', e);
                    console.error('Response text:', text);
                    showNotification('Server returned invalid response', true);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showNotification('Error adding product to cart: ' + error.message, true);
            })
            .finally(() => {
                addButton.style.pointerEvents = '';
                addButton.style.opacity = '';
            });
        }
        
        // Wishlist toggle function
        let isInWishlist = false;
        
        function addToWishlist(productId) {
            const button = event.target.closest('.btn-wishlist');
            const icon = button.querySelector('i');
            
            // Add loading state
            button.style.pointerEvents = 'none';
            button.style.opacity = '0.6';
            
            if (isInWishlist) {
                // Remove from wishlist
                fetch(getApiUrl('/api/wishlist/remove.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId }),
                    credentials: 'include'
                })
                .then(response => response.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                    if (data.success) {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        button.classList.remove('active');
                        isInWishlist = false;
                        showNotification('Removed from wishlist');
                        localStorage.setItem('wishlistUpdated', Date.now());
                        document.dispatchEvent(new Event('wishlistUpdated'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error removing from wishlist', true);
                })
                .finally(() => {
                    button.style.pointerEvents = '';
                    button.style.opacity = '';
                });
            } else {
                // Add to wishlist
                fetch(getApiUrl('/api/wishlist/add.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId }),
                    credentials: 'include'
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('Response text:', text);
                    const data = JSON.parse(text);
                    if (data.success) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        button.classList.add('active');
                        isInWishlist = true;
                        // Add heart animation
                        button.classList.add('heart-beat');
                        setTimeout(() => button.classList.remove('heart-beat'), 600);
                        showNotification('Added to wishlist');
                        localStorage.setItem('wishlistUpdated', Date.now());
                        document.dispatchEvent(new Event('wishlistUpdated'));
                            // Update wishlist count on header
                            async function updateWishlistCount() {
                                try {
                                    const response = await fetch(getApiUrl('/api/wishlist/get.php'));
                                    const responseText = await response.text();
                                    const data = JSON.parse(responseText);
                                    const count = data.wishlist ? data.wishlist.length : 0;
                                    const el = document.getElementById('wishlist-count');
                                    if (el) el.textContent = count;
                                } catch (error) {
                                    // ignore
                                }
                            }
                    } else {
                        showNotification(data.message || 'Error adding to wishlist', true);
                    }
                })
                .catch(error => {
                    console.error('Error details:', error);
                    showNotification('Error adding to wishlist', true);
                })
                .finally(() => {
                    button.style.pointerEvents = '';
                    button.style.opacity = '';
                });
            }
        }
        
        // Show notification (green for success, red for error) - v2
        function showNotification(message, isError = false) {
            console.log('showNotification called:', message, 'isError:', isError);
            const notification = document.createElement('div');
            const bgColor = isError ? '#dc3545' : '#4CAF50';
            console.log('Background color:', bgColor);
            notification.className = 'success-notification-v2';
            notification.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                background-color: ${bgColor} !important;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 10001;
                font-size: 14px;
                font-weight: 500;
                animation: slideInRight 0.3s ease;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 2500);
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
    </script>
</body>
</html>
