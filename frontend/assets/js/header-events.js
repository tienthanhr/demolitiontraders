/**
 * Header Events Handler
 * CSP Compliant - Removes all inline event handlers
 */

document.addEventListener('DOMContentLoaded', function() {
    // Google Maps button handler
    const googleMapsBtn = document.querySelector('.btn-google-maps');
    if (googleMapsBtn) {
        googleMapsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.open('https://www.google.com/maps/place/Demolition+Traders/@-37.8072281,175.2449009,6771m/data=!3m1!1e3!4m6!3m5!1s0x6d6d21fa970b5073:0x229ec1a4d67e239a!8m2!3d-37.8072319!4d175.2624104!16s%2Fg%2F1hm6cqmtt?entry=ttu&g_ep=EgoyMDI1MTEyMy4xIKXMDSoASAFQAw%3D%3D', '_blank');
        });
    }

    // Logout handler
    const logoutLinks = document.querySelectorAll('a[data-action="logout"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    });

    // Mobile menu toggle handler
    const mobileMenuBtn = document.querySelector('[data-action="toggle-mobile-menu"]');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            toggleMobileMenu();
        });
    }

    // Mobile menu close handler
    const closeMenuBtn = document.querySelector('[data-action="close-mobile-menu"]');
    if (closeMenuBtn) {
        closeMenuBtn.addEventListener('click', function() {
            closeMobileMenu();
        });
    }

    // Dropdown menu handler - Click to navigate, show dropdown on hover
    const dropdownTogles = document.querySelectorAll('.has-dropdown > a');
    dropdownTogles.forEach(link => {
        link.addEventListener('click', function(e) {
            const parent = this.closest('.has-dropdown');
            const dropdown = parent?.querySelector('.dropdown');
            
            // If no dropdown, just navigate normally
            if (!dropdown) {
                return;
            }
            
            // Special keys: Ctrl/Cmd/Shift = open in new tab (allow default)
            if (e.ctrlKey || e.metaKey || e.shiftKey) {
                return;
            }
            
            // Mobile: just navigate (allow default)
            const isMobile = window.innerWidth <= 768;
            if (isMobile) {
                return;
            }
            
            // Desktop: prevent default and close any open dropdowns
            // This allows CSS hover to handle dropdown display
            e.preventDefault();
            
            // Close all open dropdowns
            document.querySelectorAll('.has-dropdown.open').forEach(item => {
                item.classList.remove('open');
            });
            
            // Navigate after closing dropdown
            window.location.href = this.href;
        });
    });

    // Close dropdown when clicking elsewhere
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-menu')) {
            document.querySelectorAll('.has-dropdown.open').forEach(item => {
                item.classList.remove('open');
            });
        }
    });

    // Remove focus outline on links after click (better UX, keep for keyboard)
    const allLinks = document.querySelectorAll('a');
    allLinks.forEach(link => {
        link.addEventListener('mousedown', function() {
            this.style.outline = 'none';
        });
        link.addEventListener('blur', function() {
            this.style.outline = '';
        });
    });
});

/**
 * Logout function
 */
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = BASE_PATH + 'logout.php';
    }
}

/**
 * Toggle mobile menu
 */
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu-wrapper');
    if (mobileMenu) {
        mobileMenu.style.display = mobileMenu.style.display === 'none' ? 'block' : 'none';
    }
}

/**
 * Close mobile menu
 */
function closeMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu-wrapper');
    if (mobileMenu) {
        mobileMenu.style.display = 'none';
    }
}
