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
