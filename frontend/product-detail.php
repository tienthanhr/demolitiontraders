<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - Demolition Traders</title>
    <base href="/demolitiontraders/frontend/">
    <link rel="stylesheet" href="assets/css/new-style.css?v=10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1 id="product-name">Product Details</h1>
            <nav class="breadcrumb">
                <a href="index.php">Home</a> / <a href="shop.php">Shop</a> / <span id="product-breadcrumb">Product</span>
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
    
    <?php include 'components/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script>
        // Get product ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const productId = urlParams.get('id');

        if (!productId) {
            document.getElementById('product-detail').innerHTML = '<div class="error">Product ID is missing from URL</div>';
        } else {
            loadProductWithCart(productId);
        }

        // Load product details and cart quantity
        async function loadProductWithCart(id) {
            try {
                const [productRes, cartRes] = await Promise.all([
                    fetch('/demolitiontraders/api/products/' + id),
                    fetch('/demolitiontraders/backend/api/index.php?request=cart/get')
                ]);
                const product = await productRes.json();
                const cart = await cartRes.json();
                if (product.error) {
                    throw new Error(product.error);
                }
                let cartQty = 0;
                if (cart.items && Array.isArray(cart.items)) {
                    const cartItem = cart.items.find(i => i.product_id == id || i.product_id == product.id);
                    if (cartItem) cartQty = parseInt(cartItem.quantity) || 0;
                }
                displayProduct(product, cartQty);
            } catch (error) {
                console.error('Error loading product:', error);
                document.getElementById('product-detail').innerHTML = '<div class="error">Failed to load product details</div>';
            }
        }
        
        // Display product details
        function displayProduct(product, cartQty = 0) {
            document.getElementById('product-name').textContent = product.name;
            document.getElementById('product-breadcrumb').textContent = product.name;
            document.title = product.name + ' - Demolition Traders';

            const imageUrl = product.image ? product.image : 'assets/images/placeholder.jpg';
            let availableStock = Math.max(0, (parseInt(product.stock_quantity) || 0) - (parseInt(cartQty) || 0));
            let stockStatus = '';
            if (availableStock > 0) {
                stockStatus = `<span class="in-stock"><i class="fas fa-check-circle"></i> In Stock (${availableStock} available)</span>`;
            } else if ((parseInt(product.stock_quantity) || 0) > 0 && (parseInt(cartQty) || 0) > 0) {
                stockStatus = '<span class="in-cart"><i class="fas fa-shopping-cart"></i> You have already added this product to your cart. Please proceed to checkout.</span>';
            } else {
                stockStatus = '<span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>';
            }

            const html = `
                <div class="product-layout">
                    <div class="product-images">
                        <div class="main-image">
                            <img src="${imageUrl}" alt="${product.name}" id="main-product-image">
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
                                                ` : ((parseInt(product.stock_quantity) || 0) > 0 && (parseInt(cartQty) || 0) > 0
                                                        ? `<div class="in-cart-message" style="text-align:center;margin:30px 0;">
                                                                <i class="fas fa-info-circle"></i> You have already added this product to your cart.<br><br>
                                                                <a href="cart.php" class="btn btn-primary" style="margin:5px 10px;display:inline-block;">Go to Checkout</a>
                                                                <a href="shop.php" class="btn btn-secondary" style="margin:5px 10px;display:inline-block;">Browse More Products</a>
                                                            </div>`
                                                        : '<p class="out-of-stock-message"><i class="fas fa-exclamation-circle"></i> This product is currently out of stock.</p>')}
                        
                        <div class="product-meta">
                            <div class="meta-row">
                                <span class="meta-label"><i class="fas fa-tag"></i> Category:</span> 
                                <a href="shop.php?category=${product.category_id}">${product.category_name || 'Uncategorized'}</a>
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
            
            // Check if product is in wishlist
            checkWishlistStatus(product.id);
        }
        
        // Check wishlist status
        function checkWishlistStatus(productId) {
            fetch('/demolitiontraders/backend/api/wishlist/get.php')
                .then(response => response.json())
                .then(data => {
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
        function buyNow(productId) {
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            
            // Add loading state
            const buyButton = document.querySelector('.btn-buy-now');
            buyButton.style.pointerEvents = 'none';
            buyButton.style.opacity = '0.6';
            
            fetch('/demolitiontraders/backend/api/index.php?request=cart/add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: quantity })
            })
            .then(response => response.text())
            .then(text => {
                const data = JSON.parse(text);
                if (data.success !== false && (data.items || data.summary)) {
                    // Redirect to checkout
                    window.location.href = 'checkout.php';
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
            
            const url = '/demolitiontraders/backend/api/index.php?request=cart/add';
            const payload = { product_id: productId, quantity: quantity };
            
            console.log('Request URL:', url);
            console.log('Request payload:', payload);
            
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
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
                    if (data.success !== false && (data.items || data.summary)) {
                        showNotification(`${quantity} item(s) added to cart!`);
                        if (typeof updateCartCount === 'function') {
                            updateCartCount();
                        }
                        // Update stock display after add to cart
                        const stockElem = document.querySelector('.product-stock .in-stock');
                        if (stockElem) {
                            let currentStock = parseInt(stockElem.textContent.match(/\d+/));
                            if (!isNaN(currentStock)) {
                                let newStock = currentStock - quantity;
                                if (newStock <= 0) {
                                    stockElem.outerHTML = '<span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>';
                                    // Hide Buy Now button
                                    const buyNowBtn = document.getElementById('buy-now-btn');
                                    if (buyNowBtn) buyNowBtn.style.display = 'none';
                                } else {
                                    stockElem.innerHTML = `<i class=\"fas fa-check-circle\"></i> In Stock (${newStock} available)`;
                                }
                            }
                        }
                    } else {
                        // Check for out of stock or already in cart error
                        const msg = (data.message || data.error || '').toLowerCase();
                        if (msg.includes('insufficient stock') || msg.includes('out of stock')) {
                            showNotification('This product is out of stock', true);
                        } else if (msg.includes('maximum available') || msg.includes('already in cart')) {
                            showNotification('You have already added the maximum available stock to your cart.', true);
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
                fetch('/demolitiontraders/backend/api/wishlist/remove.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        button.classList.remove('active');
                        isInWishlist = false;
                        showNotification('Removed from wishlist');
                        updateWishlistCount();
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
                fetch('/demolitiontraders/backend/api/wishlist/add.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
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
                        updateWishlistCount();
                            // Update wishlist count on header
                            async function updateWishlistCount() {
                                try {
                                    const response = await fetch('/demolitiontraders/backend/api/wishlist/get.php');
                                    const data = await response.json();
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
        
        function showNotification(message, isError = false) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                background: ${isError ? '#dc3545' : '#2f3192'};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 10001;
                font-size: 14px;
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
