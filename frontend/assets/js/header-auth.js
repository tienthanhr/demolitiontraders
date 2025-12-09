// Header Auth & Counts Handler
// Handles authentication, logout, cart/wishlist counts

async function updateWishlistCount() {
    try {
        const data = await apiFetch(getApiUrl('/api/wishlist/get.php'));
        console.log('Wishlist API response:', data);
        // API returns wishlist_count directly
        const count = data.wishlist_count !== undefined ? data.wishlist_count : (data.wishlist ? data.wishlist.length : 0);
        const el = document.getElementById('wishlist-count');
        if (el) {
            el.textContent = count;
            console.log('Wishlist count updated to:', count);
        }
    } catch (error) {
        console.error('Error updating wishlist count:', error);
    }
}

// Update cart count
async function updateCartCount() {
    try {
        const data = await apiFetch(getApiUrl('/api/cart/get.php'), {
            credentials: 'include'
        });
        console.log('Cart API response:', data);
        const el = document.getElementById('cart-count');
        // API returns summary.item_count
        const count = data.summary ? data.summary.item_count : (data.item_count || 0);
        if (el) {
            el.textContent = count;
            console.log('Cart count updated to:', count);
        }
    } catch (error) {
        console.error('Error updating cart count:', error);
    }
}

// Check authentication
async function checkAuth() {
    try {
        const data = await apiFetch(getApiUrl('/api/user/me.php'));
        if (data.success && data.user) {
            // Update desktop header
            const headerRight = document.getElementById('header-top-right');
            if (headerRight) {
                headerRight.innerHTML = `
                    <a href="${USER_BASE}profile.php"><i class="fa-solid fa-user"></i> ${data.user.first_name}</a>
                    <a href="#" data-action="logout"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
                    <a href="${USER_BASE}wishlist.php" class="wishlist-link"><i class="fa-regular fa-heart"></i> <span id="wishlist-count">0</span></a>
                    <a href="${USER_BASE}cart.php" class="cart-link"><i class="fa-solid fa-cart-shopping"></i> <span id="cart-count">0</span></a>
                `;
            }

            // Update mobile login button
            const mobileLoginBtn = document.getElementById('mobile-login-btn');
            if (mobileLoginBtn) {
                mobileLoginBtn.href = USER_BASE + 'profile.php';
                mobileLoginBtn.innerHTML = `<i class="fa-solid fa-user"></i> <span>${data.user.first_name}</span>`;
            }

            // Update counts after DOM update
            updateWishlistCount();
            updateCartCount();
        } else {
            // Not authenticated - this is expected for guest users
            console.log('User not authenticated (guest mode)');
        }
    } catch (error) {
        // Network or other errors - not a problem for guest users
        console.log('Auth check skipped:', error.message);
    }
}

// Logout function
async function logout() {
    try {
        await apiFetch(getApiUrl('/api/user/logout.php'), { method: 'POST' });
        window.location.href = 'index.php';
    } catch (error) {
        console.error('Logout error:', error);
        window.location.href = 'index.php';
    }
}

// Update cart and wishlist every 5 seconds (real-time updates)
setInterval(() => {
    updateCartCount();
    updateWishlistCount();
}, 5000);

// Listen for cart/wishlist updates from other pages (localStorage for cross-tab)
window.addEventListener('storage', function(e) {
    if (e.key === 'cartUpdated') {
        updateCartCount();
    }
    if (e.key === 'wishlistUpdated') {
        updateWishlistCount();
    }
});

// Listen for cart/wishlist updates in same tab (custom events)
document.addEventListener('cartUpdated', function() {
    updateCartCount();
});

document.addEventListener('wishlistUpdated', function() {
    updateWishlistCount();
});

// Update when window gets focus (after navigating back)
window.addEventListener('focus', function() {
    updateCartCount();
    updateWishlistCount();
});

// Initialize
updateCartCount();
updateWishlistCount();
checkAuth();