<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Demolition Traders Hamilton</title>
    <base href="/demolitiontraders/frontend/">
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
    
    <script>
        // Load wishlist items
        async function loadWishlist() {
            try {
                const response = await fetch('/demolitiontraders/backend/api/wishlist/get.php');
                const data = await response.json();
                const count = data.wishlist ? data.wishlist.length : 0;
                document.getElementById('wishlist-total').textContent = count;
                const el = document.getElementById('wishlist-count');
                if (el) el.textContent = count;
                if (count > 0) {
                    displayWishlist(data.wishlist);
                } else {
                    showEmptyWishlist();
                }
            } catch (error) {
                console.error('Error loading wishlist:', error);
                showEmptyWishlist();
            }
        }
        
        // Display wishlist items
        function displayWishlist(items) {
            const grid = document.getElementById('wishlist-grid');
            grid.innerHTML = items.map(item => `
                <div class="product-card">
                    <div class="product-image">
                        <img src="${item.image || 'assets/images/placeholder.jpg'}" alt="${item.name}">
                        <button class="btn-remove-wishlist" onclick="removeFromWishlist(${item.product_id})">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                    <div class="product-info">
                        <h3>${item.name}</h3>
                        <p class="product-category">${item.category}</p>
                        <p class="product-price">$${parseFloat(item.price).toFixed(2)}</p>
                        <div class="product-actions">
                            <button class="btn-add-cart" onclick="addToCart(${item.product_id})">
                                <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
            
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
                const response = await fetch('/demolitiontraders/backend/api/wishlist/remove.php', {
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
                const response = await fetch('/demolitiontraders/backend/api/wishlist/get.php');
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
            if (!confirm('Remove all items from your wishlist?')) return;
            
            try {
                const response = await fetch('/demolitiontraders/backend/api/wishlist/clear', {
                    method: 'DELETE'
                });
                
                if (response.ok) {
                    showEmptyWishlist();
                } else {
                    alert('Failed to clear wishlist');
                }
            } catch (error) {
                console.error('Error clearing wishlist:', error);
            }
        }
        
        // Add to cart
        async function addToCart(productId) {
            try {
                const response = await fetch('/demolitiontraders/backend/api/cart/add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId, quantity: 1 })
                });
                
                if (response.ok) {
                    alert('Product added to cart!');
                    updateCartCount();
                } else {
                    alert('Failed to add product to cart');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
            }
        }
        
        // Update cart count
        async function updateCartCount() {
            try {
                const response = await fetch('/demolitiontraders/backend/api/cart');
                const data = await response.json();
                const count = data.items ? data.items.length : 0;
                document.getElementById('cart-count').textContent = count;
            } catch (error) {
                console.error('Error updating cart count:', error);
            }
        }
        
        // Initialize
        loadWishlist();
        updateWishlistCount();
    </script>
</body>
</html>
