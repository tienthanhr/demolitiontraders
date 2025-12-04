/**
 * Product Card Events Handler
 * CSP Compliant - Removes inline onclick handlers
 */

document.addEventListener('DOMContentLoaded', function() {
    // Product card image error handlers
    setupProductImageErrorHandlers();
    
    // Wishlist button handlers
    setupWishlistButtonHandlers();
    
    // Product card click handlers
    setupProductCardClickHandlers();
});

/**
 * Setup product image error handlers
 */
function setupProductImageErrorHandlers() {
    const images = document.querySelectorAll('[data-product-image]');
    images.forEach(img => {
        img.addEventListener('error', function() {
            this.src = 'assets/images/logo.png';
            this.alt = 'Product image not available';
        });
    });
}

/**
 * Setup wishlist button handlers
 */
function setupWishlistButtonHandlers() {
    const wishlistButtons = document.querySelectorAll('[data-action="add-to-wishlist"]');
    wishlistButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = btn.getAttribute('data-product-id');
            if (productId) {
                addToWishlist(productId);
            }
        });
    });
}

/**
 * Setup product card click handlers
 */
function setupProductCardClickHandlers() {
    const productCards = document.querySelectorAll('[data-product-card]');
    productCards.forEach(card => {
        const productId = card.getAttribute('data-product-id');
        if (productId) {
            card.style.cursor = 'pointer';
            card.addEventListener('click', function(e) {
                // Don't navigate if wishlist button is clicked
                if (e.target.closest('[data-action="add-to-wishlist"]')) {
                    return;
                }
                window.location.href = BASE_PATH + 'product-detail.php?id=' + productId;
            });
        }
    });
}

/**
 * Add product to wishlist
 */
function addToWishlist(productId) {
    fetch(getApiUrl('/api/wishlist/add'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showNotification('Added to wishlist!', 'success');
            const btn = document.querySelector(`[data-action="add-to-wishlist"][data-product-id="${productId}"]`);
            if (btn) {
                btn.classList.add('in-wishlist');
                btn.innerHTML = '<i class="fa-solid fa-heart"></i>';
            }
        } else {
            showNotification(data.message || 'Failed to add to wishlist', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showNotification('Error adding to wishlist', 'error');
    });
}

/**
 * Show notification
 */
function showNotification(message, type) {
    // Use your toast notification system
    if (window.showToast) {
        window.showToast(message, type);
    } else {
        console.log(`[${type}] ${message}`);
    }
}
