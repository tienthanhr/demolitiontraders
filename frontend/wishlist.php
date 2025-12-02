<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>My Wishlist</h1>
            <nav class="breadcrumb">
                <a href="index.php">Home</a> / <span>Wishlist</span>
            </nav>
        </div>
    </div>
    
    <section class="content-section">
        <div class="container">
            <div class="wishlist-container">
                <div class="wishlist-header">
                    <h2>Saved Items (<span id="wishlist-total">0</span>)</h2>
                    <button class="btn btn-secondary" onclick="clearWishlist()">Clear All</button>
                </div>
                
                <div class="wishlist-grid" id="wishlist-grid">
                    <p class="loading-text">Loading your wishlist...</p>
                </div>
                
                <div class="empty-wishlist" id="empty-wishlist" style="display: none;">
                    <i class="fa-regular fa-heart"></i>
                    <h3>Your wishlist is empty</h3>
                    <p>Start adding items you love to keep track of them</p>
                    <a href="shop.php" class="btn btn-primary">Browse Products</a>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'components/footer.php'; ?>
    <?php include 'components/toast-notification.php'; ?>
    
    <script>
        // Load wishlist items
        async function loadWishlist() {
            try {
                const [wishlistRes, cartRes] = await Promise.all([
                    fetch(getApiUrl('/api/wishlist/list.php')),
                    fetch(getApiUrl('/api/cart/get.php'))
                ]);
                
                if (!wishlistRes.ok || !cartRes.ok) {
                    throw new Error('Failed to load data');
                }
                
                const wishlistText = await wishlistRes.text();
                const cartText = await cartRes.text();
                
                let data, cartData;
                try {
                    data = JSON.parse(wishlistText);
                    cartData = JSON.parse(cartText);
                } catch (e) {
                    console.error('Invalid JSON response');
                    console.error('Wishlist response:', wishlistText);
                    console.error('Cart response:', cartText);
                    throw new Error('Invalid response from server');
                }
                
                const count = data.wishlist ? data.wishlist.length : 0;
                document.getElementById('wishlist-total').textContent = count;
                const el = document.getElementById('wishlist-count');
                if (el) el.textContent = count;
                
                if (count > 0) {
                    displayWishlist(data.wishlist, cartData);
                } else {
                    showEmptyWishlist();
                }
            } catch (error) {
                console.error('Error loading wishlist:', error);
                showEmptyWishlist();
            }
        }
        
        // Display wishlist items
        function displayWishlist(items, cartData) {
            // Get list of product IDs in cart
            const cartProductIds = new Set();
            if (cartData && cartData.items && Array.isArray(cartData.items)) {
                cartData.items.forEach(item => cartProductIds.add(item.product_id));
            }
            
            const grid = document.getElementById('wishlist-grid');
            grid.innerHTML = items.map(item => {
                // Fix image path
                let imagePath = item.image || 'assets/images/placeholder.jpg';
                if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/demolitiontraders/')) {
                    imagePath = '/demolitiontraders/' + imagePath.replace(/^\/+/, '');
                }
                
                const inCart = cartProductIds.has(item.product_id);
                
                return `
                <div class="product-card" onclick="window.location.href='product-detail.php?id=${item.product_id}'" style="cursor:pointer">
                    <div class="product-image">
                        <img src="${imagePath}" alt="${item.name}" onerror="this.src='assets/images/logo.png'">
                        <button class="btn-remove-wishlist" onclick="event.stopPropagation(); removeFromWishlist(${item.product_id})">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                    <div class="product-info">
                        <h3>${item.name}</h3>
                        <p class="product-category">${item.category || ''}</p>
                        <p class="product-price">$${parseFloat(item.price).toFixed(2)}</p>
                        <div class="product-actions">
                            ${inCart 
                                ? `<button class="btn-add-cart" onclick="event.stopPropagation(); window.location.href='cart.php'" style="background:#28a745;">
                                    <i class="fa-solid fa-cart-shopping"></i> Go to Cart
                                </button>`
                                : `<button class="btn-add-cart" onclick="event.stopPropagation(); addToCart(${item.product_id})">
                                    <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                                </button>`
                            }
                        </div>
                    </div>
                </div>
                `;
            }).join('');
            
            document.getElementById('wishlist-grid').style.display = 'grid';
            document.getElementById('empty-wishlist').style.display = 'none';
        }
        
        // Show empty wishlist message
        function showEmptyWishlist() {
            document.getElementById('wishlist-grid').style.display = 'none';
            document.getElementById('empty-wishlist').style.display = 'flex';
            document.getElementById('wishlist-total').textContent = '0';
        }
        
        // Remove from wishlist
        async function removeFromWishlist(productId) {
            if (!confirm('Remove this item from your wishlist?')) return;
            try {
                const response = await fetch(getApiUrl('/api/wishlist/remove.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                });
                const data = await response.json();
                if (data.success) {
                    loadWishlist();
                    updateWishlistCount();
                } else {
                    alert('Failed to remove item');
                }
            } catch (error) {
                console.error('Error removing from wishlist:', error);
            }
        }

        // Update wishlist count on header
        async function updateWishlistCount() {
            try {
                const response = await fetch(getApiUrl('/api/wishlist/list.php'));
                const data = await response.json();
                const count = data.wishlist ? data.wishlist.length : 0;
                const el = document.getElementById('wishlist-count');
                if (el) el.textContent = count;
            } catch (error) {
                // ignore
            }
        }
        
        // Clear wishlist
        async function clearWishlist() {
            if (!confirm('Are you sure you want to clear your entire wishlist?')) return;
            try {
                const response = await fetch(getApiUrl('/api/wishlist/empty.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    showEmptyWishlist();
                    updateWishlistCount();
                    alert('Wishlist cleared successfully');
                } else {
                    alert('Failed to clear wishlist');
                }
            } catch (error) {
                console.error('Error clearing wishlist:', error);
                alert('Error clearing wishlist');
            }
        }
        
        // Add to cart
        async function addToCart(productId) {
            try {
                const response = await fetch(getApiUrl('/api/cart/add.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId, quantity: 1 })
                });
                const data = await response.json();
                if (data.success) {
                    alert('Product added to cart!');
                    // Trigger cart update event for header and other pages
                    localStorage.setItem('cartUpdated', Date.now());
                    // Manually trigger storage event for same page (storage doesn't fire on same page)
                    window.dispatchEvent(new StorageEvent('storage', {
                        key: 'cartUpdated',
                        newValue: Date.now().toString()
                    }));
                    // Reload wishlist to update buttons
                    loadWishlist();
                } else {
                    alert('Failed to add product to cart');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
            }
        }
        
        // Initialize
        loadWishlist();
        updateWishlistCount();
    </script>
</body>
</html>
