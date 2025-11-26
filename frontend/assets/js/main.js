/**
 * Main JavaScript
 * Demolition Traders E-commerce Platform
 */

// API Base URL
const API_URL = '/demolitiontraders/api';

// Add to cart function
async function addToCart(productId, quantity = 1) {
    try {
        const response = await fetch(`${API_URL}/cart/add`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity })
        });
        
        if (response.ok) {
            const data = await response.json();
            updateCartCount();
            showNotification('Product added to cart!', 'success');
        } else {
            const error = await response.json();
            showNotification(error.error || 'Failed to add to cart', 'error');
        }
    } catch (error) {
        console.error('Add to cart error:', error);
        showNotification('An error occurred', 'error');
    }
}

// Add to wishlist
async function addToWishlist(productId) {
    try {
        const response = await fetch(`${API_URL}/wishlist/add`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        });
        
        if (response.ok) {
            showNotification('Added to wishlist!', 'success');
        } else {
            const error = await response.json();
            showNotification(error.error || 'Failed to add to wishlist', 'error');
        }
    } catch (error) {
        console.error('Wishlist error:', error);
        showNotification('An error occurred', 'error');
    }
}

// Update cart count
async function updateCartCount() {
    try {
        const response = await fetch(`${API_URL}/cart/get`);
        const data = await response.json();
        
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            cartCount.textContent = data.summary.item_count;
        }
    } catch (error) {
        console.error('Error updating cart count:', error);
    }
}

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
        color: white;
        border-radius: 5px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Format currency
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Load more products (infinite scroll)
let isLoading = false;

function setupInfiniteScroll() {
    window.addEventListener('scroll', debounce(() => {
        if (isLoading) return;
        
        const scrollPosition = window.innerHeight + window.scrollY;
        const threshold = document.documentElement.scrollHeight - 500;
        
        if (scrollPosition >= threshold) {
            // Load more products
            if (typeof loadMoreProducts === 'function') {
                loadMoreProducts();
            }
        }
    }, 200));
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    
    // Setup search autocomplete if search input exists
    const searchInput = document.querySelector('.header-search input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(async function(e) {
            const query = e.target.value;
            if (query.length < 3) return;
            
            try {
                const response = await fetch(`${API_URL}/search?q=${encodeURIComponent(query)}&limit=5`);
                const results = await response.json();
                
                // Display autocomplete results
                // (Implementation depends on your UI requirements)
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 300));
    }
});

// Export functions for use in other scripts
window.demolitionTraders = {
    addToCart,
    addToWishlist,
    updateCartCount,
    showNotification,
    formatCurrency
};
