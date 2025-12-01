<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, #2f3192 0%, #1a1d4d 100%);
            color: white;
            padding: 40px 0;
            text-align: center;
        }
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .cart-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .btn-empty-cart {
            background: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-empty-cart:hover {
            background: #d32f2f;
        }
        .cart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin: 40px auto;
            max-width: 1200px;
            padding: 0 20px;
        }
        .cart-items {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .cart-item {
            display: flex;
            align-items: center;
            gap: 20px;
            border-bottom: 1px solid #eee;
            padding: 20px 0;
        }
        .cart-item:last-child { border-bottom: none; }
        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }
        .item-details {
            flex: 1;
            min-width: 0;
        }
        .item-details h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 600;
        }
        .item-details .item-category {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .item-price {
            font-size: 20px;
            font-weight: 700;
            color: #2f3192;
            margin-right: 20px;
            min-width: 90px;
            text-align: right;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: 20px;
        }
        .quantity-control button {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 5px;
            font-size: 18px;
        }
        .quantity-control button:hover { background: #f5f5f5; }
        .quantity-control input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 5px;
        }
        .remove-btn {
            background: none;
            border: none;
            color: #f44336;
            cursor: pointer;
            font-size: 24px;
            padding: 5px;
        }
        .remove-btn:hover { color: #d32f2f; }
        .cart-summary {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .summary-row.total {
            font-size: 20px;
            font-weight: 700;
            color: #2f3192;
            border-top: 2px solid #2f3192;
            border-bottom: none;
            margin-top: 10px;
        }
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-cart i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        /* Recommendations Section */
        .recommendations-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 30px auto;
            max-width: 1200px;
        }
        .recommendations-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2f3192;
        }
        .recommendations-header h3 {
            margin: 0;
            color: #2f3192;
            font-size: 22px;
        }
        .recommendations-header i {
            color: #2f3192;
            font-size: 24px;
        }
        .recommendations-carousel {
            position: relative;
            padding: 0 50px;
        }
        .recommendations-grid {
            display: flex;
            overflow-x: auto;
            scroll-behavior: smooth;
            gap: 20px;
            padding: 10px 0;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
        }
        .recommendations-grid::-webkit-scrollbar {
            display: none; /* Chrome/Safari */
        }
        .recommendation-card {
            flex: 0 0 220px;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: #2f3192;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        .carousel-btn:hover {
            background: #1a1d4d;
            transform: translateY(-50%) scale(1.1);
        }
        .carousel-btn.prev {
            left: 0;
        }
        .carousel-btn.next {
            right: 0;
        }
        .recommendation-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        .recommendation-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .recommendation-card img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .recommendation-card h4 {
            font-size: 14px;
            margin: 8px 0;
            color: #333;
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .recommendation-card .price {
            color: #2f3192;
            font-weight: 700;
            font-size: 16px;
            margin: 8px 0;
        }
        .recommendation-card .btn-add {
            background: #2f3192;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            width: 100%;
            margin-top: 8px;
        }
        .recommendation-card .btn-add:hover {
            background: #1a1d4d;
        }
        
        @media (max-width: 768px) {
            .cart-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .cart-item {
                flex-wrap: wrap;
            }
            .quantity-control {
                width: 100%;
                justify-content: flex-start;
            }
            .cart-summary {
                position: static;
            }
            .recommendations-carousel {
                padding: 0 40px;
            }
            .carousel-btn {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }
            .recommendation-card {
                flex: 0 0 180px;
            }
            .recommendations-section {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>Shopping Cart</h1>
        </div>
    </div>
    
    <div class="cart-container">
        <div class="cart-items">
            <div class="cart-header">
                <h2>Your Cart (<span id="cart-item-count">0</span> items)</h2>
                <button class="btn-empty-cart" onclick="emptyCart()" id="empty-cart-btn" style="display:none;">
                    <i class="fas fa-trash"></i> Empty Cart
                </button>
            </div>
            <div id="cart-items">
                <!-- Cart items will be loaded here -->
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>Browse our products and start shopping!</p>
                </div>
            </div>
        </div>
        
        <div class="cart-summary">
            <h3>Order Summary</h3>
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="subtotal">$0.00</span>
            </div>
            <div class="summary-row" style="font-size: 12px; color: #666; border: none; padding-top: 5px;">
                <span><i class="fas fa-info-circle"></i> GST Included</span>
                <span></span>
            </div>
            <div class="summary-row total">
                <span>Total:</span>
                <span id="total">$0.00</span>
            </div>
            <a href="checkout.php" class="btn btn-primary" style="width: 100%; display: block; text-align: center; margin-top: 20px;" id="checkout-button">
                Proceed to Checkout
            </a>
            <a href="shop.php" class="btn btn-secondary" style="width: 100%; display: block; text-align: center; margin-top: 10px;">
                Continue Shopping
            </a>
        </div>
    </div>
    
    <!-- Recommendations Section -->
    <div class="recommendations-section" id="recommendations-section" style="display:none;">
        <div class="recommendations-header">
            <i class="fas fa-heart"></i>
            <h3>You May Also Like</h3>
        </div>
        <div class="recommendations-carousel">
            <button class="carousel-btn prev" id="carousel-prev" onclick="scrollCarousel(-1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="recommendations-grid" id="recommendations-grid">
                <!-- Recommendations will be loaded here -->
            </div>
            <button class="carousel-btn next" id="carousel-next" onclick="scrollCarousel(1)">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    
    <?php include 'components/footer.php'; ?>
    <?php include 'components/toast-notification.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script>
        // Prevent infinite loops - track if recommendations already loaded
        let recommendationsLoaded = false;
        let currentCartProductIds = '';
        let loadCartInProgress = false;
        
        // Load cart items
        async function loadCart(skipRecommendations = false) {
            // Prevent concurrent calls
            if (loadCartInProgress) {
                return;
            }
            
            loadCartInProgress = true;
            
            try {
                const response = await fetch('/demolitiontraders/backend/api/cart/get.php', {
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    throw new Error('Failed to load cart');
                }
                
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Invalid response from server');
                }
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load cart');
                }
                
                // Debug logging
                if (data.debug) {
                    console.log('Cart Debug Info:', data.debug);
                }
                
                const container = document.getElementById('cart-items');
                const checkoutBtn = document.getElementById('checkout-button');
                const emptyBtn = document.getElementById('empty-cart-btn');
                const itemCount = document.getElementById('cart-item-count');
                
                const count = data.items ? data.items.length : 0;
                console.log(`Cart loaded: ${count} items`);
                itemCount.textContent = count;
                
                if (count === 0) {
                    container.innerHTML = `
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <h2>Your cart is empty</h2>
                            <p>Browse our products and start shopping!</p>
                            <a href="shop.php" class="btn btn-primary" style="margin-top:20px;display:inline-block;">Browse Products</a>
                        </div>
                    `;
                    if (checkoutBtn) checkoutBtn.style.display = 'none';
                    if (emptyBtn) emptyBtn.style.display = 'none';
                    
                    // Hide recommendations when cart is empty
                    document.getElementById('recommendations-section').style.display = 'none';
                    recommendationsLoaded = false;
                } else {
                    if (checkoutBtn) checkoutBtn.style.display = 'block';
                    if (emptyBtn) emptyBtn.style.display = 'block';
                    
                    container.innerHTML = data.items.map(item => {
                        // Fix image path
                        let imagePath = item.image || 'assets/images/no-image.jpg';
                        
                        // If it's a relative path (like 'uploads/xxx.jpg'), add /demolitiontraders/
                        if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
                            imagePath = '/demolitiontraders/' + imagePath;
                        }
                        // If it starts with / but not /demolitiontraders/, add prefix
                        else if (imagePath && imagePath.startsWith('/') && !imagePath.startsWith('/demolitiontraders/')) {
                            imagePath = '/demolitiontraders' + imagePath;
                        }
                        
                        return `
                        <div class="cart-item">
                            <img src="${imagePath}" alt="${item.name}" onclick="window.location.href='product-detail.php?id=${item.product_id}'" style="cursor:pointer" onerror="this.src='assets/images/logo.png'">
                            <div class="item-details" onclick="window.location.href='product-detail.php?id=${item.product_id}'" style="cursor:pointer">
                                <h3>${item.name}</h3>
                                ${item.category_name ? `<p class="item-category">${item.category_name}</p>` : ''}
                            </div>
                            <div class="item-price">$${parseFloat(item.price).toFixed(2)}</div>
                            <div class="quantity-control">
                                <button onclick="updateQuantity(${item.product_id}, ${item.quantity - 1})">-</button>
                                <input type="number" value="${item.quantity}" min="1" max="${item.stock_quantity || 999}" 
                                    onchange="updateQuantity(${item.product_id}, this.value)">
                                <button onclick="updateQuantity(${item.product_id}, ${item.quantity + 1})">+</button>
                            </div>
                            <button class="remove-btn" onclick="removeItem(${item.product_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        `;
                    }).join('');
                    
                    // Always show and update recommendations when cart has items
                    const productIds = data.items.map(item => item.product_id);
                    const newCartIds = productIds.sort().join(',');
                    
                    console.log('Current cart IDs:', newCartIds);
                    console.log('Previous cart IDs:', currentCartProductIds);
                    console.log('IDs changed?', newCartIds !== currentCartProductIds);
                    
                    // Always show recommendations section
                    document.getElementById('recommendations-section').style.display = 'block';
                    
                    // Only reload recommendations if cart content changed
                    if (newCartIds !== currentCartProductIds) {
                        console.log('>>> Cart changed! Loading new recommendations...');
                        currentCartProductIds = newCartIds;
                        loadRecommendations(productIds);
                    } else {
                        console.log('>>> Cart IDs same, skipping recommendations reload');
                    }
                }
                
                // Update summary
                document.getElementById('subtotal').textContent = '$' + (data.summary?.subtotal ?? '0.00');
                document.getElementById('total').textContent = '$' + (data.summary?.total ?? '0.00');
                
            } catch (error) {
                console.error('Error loading cart:', error);
                if (error.name === 'AbortError') {
                    console.error('Cart request timed out');
                }
            } finally {
                loadCartInProgress = false;
            }
        }
        
        // Remove item from cart
        async function removeItem(productId) {
            const confirmed = await showConfirm('Remove this item from your cart?', 'Remove Item', true);
            if (!confirmed) return;
            
            try {
                const response = await fetch('/demolitiontraders/backend/api/cart/remove.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                });
                const data = await response.json();
                if (data.success) {
                    // Trigger cart update event for other tabs/pages
                    localStorage.setItem('cartUpdated', Date.now());
                    window.dispatchEvent(new StorageEvent('storage', {
                        key: 'cartUpdated',
                        newValue: Date.now().toString()
                    }));
                    
                    // Reload cart (will update recommendations automatically)
                    loadCart();
                } else {
                    showError('Failed to remove item');
                }
            } catch (error) {
                console.error('Error removing item:', error);
                showError('Error removing item');
            }
        }
        
        // Update quantity
        async function updateQuantity(productId, newQty) {
            newQty = parseInt(newQty);
            if (isNaN(newQty) || newQty < 0) return;
            
            if (newQty === 0) {
                removeItem(productId);
                return;
            }
            
            try {
                const response = await fetch('/demolitiontraders/backend/api/cart/update.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId, quantity: newQty })
                });
                const data = await response.json();
                if (data.success) {
                    // Trigger cart update event
                    localStorage.setItem('cartUpdated', Date.now());
                    window.dispatchEvent(new StorageEvent('storage', {
                        key: 'cartUpdated',
                        newValue: Date.now().toString()
                    }));
                    
                    // Reload cart but recommendations won't reload (cart IDs haven't changed)
                    loadCart();
                } else {
                    showError(data.message || 'Failed to update quantity');
                }
            } catch (error) {
                console.error('Error updating quantity:', error);
                showError('Error updating quantity');
            }
        }
        
        // Empty entire cart
        async function emptyCart() {
            const confirmed = await showConfirm(
                'Are you sure you want to empty your cart? This will remove all items.',
                'Empty Cart',
                true
            );
            if (!confirmed) return;
            
            try {
                const response = await fetch('/demolitiontraders/backend/api/cart/empty.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    // Trigger cart update event
                    localStorage.setItem('cartUpdated', Date.now());
                    window.dispatchEvent(new StorageEvent('storage', {
                        key: 'cartUpdated',
                        newValue: Date.now().toString()
                    }));
                    
                    // Reset cart state
                    currentCartProductIds = '';
                    
                    // Reload cart
                    loadCart();
                    
                    alert('Cart emptied successfully');
                } else {
                    alert('Failed to empty cart');
                }
            } catch (error) {
                console.error('Error emptying cart:', error);
                alert('Error emptying cart');
            }
        }
        
        // Load product recommendations
        async function loadRecommendations(productIds) {
            try {
                console.log('Loading recommendations, cart has', productIds.length, 'products');
                
                // Fetch a large number of products to always have recommendations
                const response = await fetch('/demolitiontraders/backend/api/index.php?request=products&per_page=300');
                const data = await response.json();
                
                let products = data.data || data.products || [];
                console.log('Fetched', products.length, 'total products from API');
                
                // Filter out products already in cart
                const cartProductIdSet = new Set(productIds);
                products = products.filter(p => !cartProductIdSet.has(p.id));
                console.log('After filtering cart products:', products.length, 'products remain');
                
                // Take first 12 available products
                products = products.slice(0, 12);
                console.log('Taking first 12 products:', products.length);
                
                // Always display recommendations if we have any products
                if (products.length > 0) {
                    displayRecommendations(products);
                    document.getElementById('recommendations-section').style.display = 'block';
                    console.log('Displaying', products.length, 'recommendations');
                } else {
                    // Hide section if no products available (shouldn't happen with 200 limit)
                    document.getElementById('recommendations-section').style.display = 'none';
                    console.log('No products to recommend - hiding section');
                }
            } catch (error) {
                console.error('Error loading recommendations:', error);
            }
        }
        
        // Display recommendations
        function displayRecommendations(items) {
            const section = document.getElementById('recommendations-section');
            const grid = document.getElementById('recommendations-grid');
            
            if (!section || !grid) {
                return;
            }
            
            grid.innerHTML = items.map(item => {
                // Fix image path
                let imagePath = item.image || 'assets/images/no-image.jpg';
                
                // If it's a relative path (like 'uploads/xxx.jpg'), add /demolitiontraders/
                if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
                    imagePath = '/demolitiontraders/' + imagePath;
                }
                // If it starts with / but not /demolitiontraders/, add prefix
                else if (imagePath && imagePath.startsWith('/') && !imagePath.startsWith('/demolitiontraders/')) {
                    imagePath = '/demolitiontraders' + imagePath;
                }
                
                return `
                    <div class="recommendation-card" onclick="window.location.href='product-detail.php?id=${item.id}'">
                        <img src="${imagePath}" alt="${item.name}" onerror="this.src='assets/images/logo.png'">
                        <h4>${item.name}</h4>
                        <div class="price">$${parseFloat(item.price).toFixed(2)}</div>
                        <button class="btn-add" onclick="event.stopPropagation(); quickAddToCart(${item.id})">
                            <i class="fas fa-plus"></i> Add to Cart
                        </button>
                    </div>
                `;
            }).join('');
            
            section.style.display = 'block';
        }
        
        // Quick add to cart from recommendations
        async function quickAddToCart(productId) {
            const card = event.target.closest('.recommendation-card');
            
            try {
                const response = await fetch('/demolitiontraders/backend/api/cart/add.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId, quantity: 1 })
                });
                const data = await response.json();
                
                if (data.success) {
                    // Hide the card with animation
                    if (card) {
                        card.style.transition = 'opacity 0.3s, transform 0.3s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8)';
                    }
                    
                    // Trigger cart update event
                    localStorage.setItem('cartUpdated', Date.now());
                    window.dispatchEvent(new StorageEvent('storage', {
                        key: 'cartUpdated',
                        newValue: Date.now().toString()
                    }));
                    
                    // Wait for animation, then reload everything
                    setTimeout(() => {
                        // Force full reload by clearing cache
                        currentCartProductIds = '';
                        loadCart();
                        
                        // Scroll to continue shopping button after reload
                        setTimeout(() => {
                            const continueShoppingBtn = document.getElementById('continue-shopping-btn');
                            if (continueShoppingBtn) {
                                continueShoppingBtn.scrollIntoView({ 
                                    behavior: 'smooth', 
                                    block: 'center' 
                                });
                            }
                        }, 200);
                    }, 350);
                } else {
                    showError(data.message || 'Failed to add product');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                showError('Error adding product to cart');
            }
        }
        
        // Scroll carousel
        function scrollCarousel(direction) {
            const grid = document.getElementById('recommendations-grid');
            if (!grid) return;
            
            // Scroll 3 cards at once: (220px card + 20px gap) * 3 = 720px
            const scrollAmount = 720;
            grid.scrollBy({
                left: direction * scrollAmount,
                behavior: 'smooth'
            });
        }
        
        // Load cart on page load
        document.addEventListener('DOMContentLoaded', loadCart);
        
        // Listen for popstate (back/forward navigation)
        window.addEventListener('popstate', function() {
            loadCart();
        });
    </script>
</body>
</html>
