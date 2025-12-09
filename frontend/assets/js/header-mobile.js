// Header Mobile Menu Handler
// Handles mobile menu toggles and interactions

// Function to close mobile menu
function closeMobileMenu() {
    const mobileMenuWrapper = document.getElementById('mobile-menu-wrapper');
    if (mobileMenuWrapper) {
        mobileMenuWrapper.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Mobile menu toggle - Wrap in DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    // Handle new fullscreen mobile menu
    const mobileMenuBtn = document.getElementById('mobile-categories-btn');
    const mobileMenuWrapper = document.getElementById('mobile-menu-wrapper');
    const mobileNavDropdown = document.getElementById('mobile-nav-dropdown');
    const mobileNavClose = document.getElementById('mobile-nav-close');

    if (mobileMenuBtn && mobileMenuWrapper && mobileNavDropdown) {
        // Open menu
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            mobileMenuWrapper.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });

        // Close menu - close button
        if (mobileNavClose) {
            mobileNavClose.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeMobileMenu();
            });
        }

        // Close menu - ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenuWrapper.style.display === 'block') {
                closeMobileMenu();
            }
        });

        // Close menu - clicking on wrapper background
        mobileMenuWrapper.addEventListener('click', function(e) {
            if (e.target === mobileMenuWrapper) {
                closeMobileMenu();
            }
        });

        // Handle dropdown toggle clicks - toggle submenu but allow navigation
        const dropdownToggles = mobileNavDropdown.querySelectorAll('.mobile-nav-item.has-dropdown > .mobile-nav-link');
        dropdownToggles.forEach(link => {
            link.addEventListener('click', function(e) {
                // Toggle dropdown state
                const item = this.closest('.mobile-nav-item');
                item.classList.toggle('open');
                // Allow link to navigate - don't preventDefault
            });
        });

        // Close menu when clicking regular links (not dropdowns)
        const regularLinks = mobileNavDropdown.querySelectorAll('.mobile-nav-submenu a');
        regularLinks.forEach(link => {
            link.addEventListener('click', function() {
                closeMobileMenu();
            });
        });
    }

    // Old mobile toggle (keep for compatibility)
    const mobileToggle = document.getElementById('mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (mobileToggle && navMenu) {
        // Toggle menu on button click
        mobileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            navMenu.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (navMenu.classList.contains('active') &&
                !navMenu.contains(e.target) &&
                !mobileToggle.contains(e.target)) {
                navMenu.classList.remove('active');
                document.body.classList.remove('menu-open');
            }
        });

        // Close menu when clicking on a link - allow navigation for all links
        const menuLinks = navMenu.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Allow normal link navigation; close menu afterwards
                navMenu.classList.remove('active');
                document.body.classList.remove('menu-open');
            });
        });
    }

    // Mobile categories menu button
    const mobileCategoriesBtn = document.getElementById('mobile-categories-btn');
    const mobileNavDropdownOld = document.getElementById('mobile-nav-dropdown');

    if (mobileCategoriesBtn && mobileNavDropdownOld) {
        mobileCategoriesBtn.addEventListener('click', function(e) {
            e.preventDefault();
            mobileNavDropdownOld.classList.toggle('active');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileCategoriesBtn.contains(e.target) && !mobileNavDropdownOld.contains(e.target)) {
                mobileNavDropdownOld.classList.remove('active');
            }
        });
    }

    // Mobile dropdown toggles (subcategories) - only toggle, don't block link clicks
    // Note: handlers are re-attached by header-events.js after rendering; this is kept for fallback
    const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const parentItem = this.closest('.mobile-nav-item');
            const hasSubmenu = parentItem && parentItem.classList.contains('has-dropdown');

            if (hasSubmenu) {
                // Toggle dropdown but allow long-press/double-tap for navigation
                parentItem.classList.toggle('active');
                // Don't prevent default here - let the link navigate if user wants
            }
        });
    });

    // Update mobile wishlist and cart counts
    function syncMobileCounts() {
        const wishlistCount = document.getElementById('wishlist-count');
        const cartCount = document.getElementById('cart-count');
        const mobileWishlistCount = document.getElementById('mobile-wishlist-count');
        const mobileCartCount = document.getElementById('mobile-cart-count');

        if (wishlistCount && mobileWishlistCount) {
            mobileWishlistCount.textContent = wishlistCount.textContent;
        }
        if (cartCount && mobileCartCount) {
            mobileCartCount.textContent = cartCount.textContent;
        }
    }

    // Sync counts on load and periodically
    syncMobileCounts();
    setInterval(syncMobileCounts, 1000);
});