/**
 * Cart Events Handler
 * CSP Compliant - Removes all inline onclick handlers
 */

document.addEventListener('DOMContentLoaded', function() {
    // Empty cart button
    const emptyCartBtn = document.getElementById('empty-cart-btn');
    if (emptyCartBtn) {
        emptyCartBtn.addEventListener('click', function(e) {
            e.preventDefault();
            emptyCart();
        });
    }

    // Carousel navigation buttons
    const prevBtn = document.getElementById('carousel-prev');
    const nextBtn = document.getElementById('carousel-next');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', function(e) {
            e.preventDefault();
            scrollCarousel(-1);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
            e.preventDefault();
            scrollCarousel(1);
        });
    }

    // Cart item click handlers (delegation)
    const cartContainer = document.getElementById('cart-items');
    if (cartContainer) {
        cartContainer.addEventListener('click', function(e) {
            // Image click - navigate to product detail
            if (e.target.classList.contains('cart-item-image')) {
                const productId = e.target.getAttribute('data-product-id');
                if (productId) {
                    window.location.href = BASE_PATH + 'product-detail.php?id=' + productId;
                }
            }

            // Item details click - navigate to product detail
            if (e.target.closest('.item-details')) {
                const productId = e.target.closest('.item-details').getAttribute('data-product-id');
                if (productId) {
                    window.location.href = BASE_PATH + 'product-detail.php?id=' + productId;
                }
            }
        });
    }

    // Image error handlers (set default image)
    const cartImages = document.querySelectorAll('.cart-item-image');
    cartImages.forEach(img => {
        img.addEventListener('error', function() {
            this.src = 'assets/images/logo.png';
        });
    });
});

/**
 * Empty cart function
 */
function emptyCart() {
    if (confirm('Are you sure you want to empty your cart?')) {
        // Implementation - send request to backend
        fetch(getApiUrl('/api/cart/empty'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to empty cart');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error emptying cart');
        });
    }
}

/**
 * Scroll carousel
 */
function scrollCarousel(direction) {
    const carousel = document.querySelector('.recommendations-carousel');
    if (carousel) {
        const scrollAmount = 300;
        carousel.scrollBy({
            left: direction * scrollAmount,
            behavior: 'smooth'
        });
    }
}
