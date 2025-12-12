<?php
// Ensure core config is loaded even if the including page forgot to require it
if (!defined('BASE_PATH')) {
    $configPath = __DIR__ . '/../../config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }
}
// Basic fallbacks to avoid undefined constants in production
if (!defined('SITE_URL')) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('SITE_URL', $scheme . '://' . $host);
}
if (!defined('FRONTEND_URL')) {
    define('FRONTEND_URL', SITE_URL . '/frontend');
}
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/..');
}
?>
<!-- Header Component -->
<!-- Load API Helper First -->
<script src="assets/js/api-helper.js?v=1"></script>
<script>
// Set base URL for user pages
const USER_BASE = '<?php echo BASE_PATH; ?>';

// Ensure favicon is registered (works on localhost and production)
(function() {
    const href = '<?php echo rtrim(FRONTEND_URL, '/'); ?>/assets/images/favicon.png?v=1';
    let link = document.querySelector('link[rel="icon"]');
    if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        link.type = 'image/png';
        document.head.appendChild(link);
    }
    link.href = href;
})();
</script>

<!-- Top Navigation Bar -->
<div class="header-top">
    <div class="container">
        <div class="header-top-left">
            <a href="<?php echo userUrl('index.php'); ?>">Home</a>
            <a href="<?php echo userUrl('wanted-listing.php'); ?>">Wanted Listing</a>
            <a href="<?php echo userUrl('sell-to-us.php'); ?>">Sell to Us</a>
            <a href="<?php echo userUrl('cabins.php'); ?>">Cabins</a>
            <a href="<?php echo userUrl('staff.php'); ?>">Staff</a>
            <a href="<?php echo userUrl('faqs.php'); ?>">FAQs</a>
            <a href="<?php echo userUrl('about.php'); ?>">About Us</a>
            <a href="<?php echo userUrl('contact.php'); ?>">Contact Us</a>
            <a href="https://www.facebook.com/profile.php?id=100063449630280#" target="_blank"><i class="fa-brands fa-facebook-square"></i></a>
        </div>
        <div class="header-top-right" id="header-top-right">
            <a href="<?php echo userUrl('login.php'); ?>" id="login-link"><i class="fa-solid fa-user"></i> Login</a>
            <a href="<?php echo userUrl('wishlist.php'); ?>" class="wishlist-link"><i class="fa-regular fa-heart"></i> <span id="wishlist-count">0</span></a>
            <a href="<?php echo userUrl('cart.php'); ?>" class="cart-link"><i class="fa-solid fa-cart-shopping"></i> <span id="cart-count">0</span></a>
        </div>
    </div>
</div>

<!-- Main Header -->
<header class="site-header">
    <div class="container">
        <div class="header-content">
            <div class="header-top-row">
                <div class="logo">
                    <a href="<?php echo userUrl('index.php'); ?>">
                        <img src="assets/images/logo.png" alt="Demolition Traders" style="max-height: 100px;">
                    </a>
                </div>
                
                <div class="header-info">
                    <a href="tel:0800336548466" class="info-box phone-info">
                        <i class="fa-solid fa-phone"></i>
                        <div>
                            <strong>Give us a call</strong>
                            <span>0800 DEMOLITION</span>
                        </div>
                    </a>
                    <button class="info-box location-info" id="location-btn">
                        <i class="fa-solid fa-location-dot"></i>
                        <div>
                            <strong>Visit Our Yard</strong>
                            <span>249 Kahikatea Drive, Hamilton</span>
                        </div>
                    </button>
                    <div class="info-box opening-hours-box hours-info" id="opening-hours-box">
                        <i class="fa-solid fa-clock"></i>
                        <div>
                            <strong>Opening Hours</strong>
                            <span id="opening-hours-display">Mon-Fri: 8am-5pm</span>
                        </div>
                        <i class="fa-solid fa-chevron-down dropdown-icon" id="hours-dropdown-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="header-center">
                <div class="header-search">
                    <form action="<?php echo userUrl('shop.php'); ?>" method="GET">
                        <input type="text" name="search" placeholder="Search Products" class="search-input">
                        <button type="submit" class="search-button"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>
                <div class="tagline">"Take a look... you'll be surprised"</div>
            </div>

            <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                <i class="fa-solid fa-bars"></i>
                <span class="menu-text">Menu</span>
            </button>
        </div>
    </div>
</header>

<!-- Opening Hours Floating Modal -->
<div class="opening-hours-modal" id="opening-hours-modal">
    <div class="modal-backdrop" id="modal-backdrop"></div>
    <div class="opening-hours-dropdown" id="opening-hours-dropdown">
        <div class="dropdown-header">
            <h3>Weekly Opening Hours</h3>
            <button class="close-btn" id="close-hours-modal">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <div class="opening-hours-list" id="opening-hours-list">
            <div class="loading">Loading...</div>
        </div>
    </div>
</div>

<!-- Location Map Modal -->
<div class="location-modal" id="location-modal">
    <div class="modal-backdrop-location" id="modal-backdrop-location"></div>
    <div class="location-map-container" id="location-map-container">
        <div class="location-map-header">
            <h3>Demolition Traders Location</h3>
            <button class="close-btn" id="close-location-modal">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <div class="location-map-content">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3193.1234567890!2d175.2449009!3d-37.8072319!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6d6d21fa970b5073%3A0x229ec1a4d67e239a!2sDemolition%20Traders!5e0!3m2!1sen!2snz!4v1234567890" width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <div class="location-details">
                <p><strong>Demolition Traders</strong></p>
                <p>249 Kahikatea Drive, Hamilton, New Zealand</p>
                <p>Phone: <a href="tel:0800336548466">0800 DEMOLITION</a></p>
                <button class="btn-google-maps" onclick="window.open('https://www.google.com/maps/place/Demolition+Traders/@-37.8072281,175.2449009,6771m/data=!3m1!1e3!4m6!3m5!1s0x6d6d21fa970b5073:0x229ec1a4d67e239a!8m2!3d-37.8072319!4d175.2624104!16s%2Fg%2F1hm6cqmtt?entry=ttu&g_ep=EgoyMDI1MTEyMy4xIKXMDSoASAFQAw%3D%3D', '_blank')">
                    <i class="fa-brands fa-google"></i> <span>Open in Google Maps</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Menu -->
<nav class="main-nav">
    <div class="container">
        <!-- Mobile Menu Buttons (only visible on mobile) -->
        <div class="mobile-menu-bar">
            <button class="mobile-menu-btn" id="mobile-categories-btn">
                <i class="fa-solid fa-bars"></i> <span>Menu</span>
            </button>
            <a href="<?php echo userUrl('login.php'); ?>" class="mobile-menu-btn" id="mobile-login-btn">
                <i class="fa-solid fa-user"></i> <span>LOGIN</span>
            </a>
            <div class="mobile-menu-btn-group">
                <a href="<?php echo userUrl('wishlist.php'); ?>" class="mobile-menu-btn mobile-wishlist-btn">
                    <i class="fa-regular fa-heart"></i> <span id="mobile-wishlist-count">0</span>
                </a>
                <a href="<?php echo userUrl('cart.php'); ?>" class="mobile-menu-btn mobile-cart-btn">
                    <i class="fa-solid fa-cart-shopping"></i> <span id="mobile-cart-count">0</span>
                </a>
            </div>
        </div>
        
        <!-- Desktop Menu -->
        <ul class="nav-menu" id="nav-menu-dynamic">
            <li><span style="color:#6c757d;">Loading categories...</span></li>
        </ul>
        
    </div>
</nav>

<!-- Mobile Navigation Dropdown Wrapper - Escape layout constraints -->
<div id="mobile-menu-wrapper" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 9998; display: none; background: white;">
    <div class="mobile-nav-dropdown" id="mobile-nav-dropdown">
    <div class="mobile-nav-header">
        <h3>Menu</h3>
        <button class="mobile-nav-close" id="mobile-nav-close">
            <i class="fa-solid fa-times"></i>
        </button>
    </div>
    <div class="mobile-nav-content" id="mobile-nav-content">
        <div class="loading">Loading categories...</div>
    </div>
    </div>
</div>

<script>
    const CATEGORY_API = (typeof getApiUrl === 'function')
        ? getApiUrl('/api/index.php?request=categories')
        : '<?php echo rtrim(SITE_URL, '/'); ?>/backend/api/index.php?request=categories';
    const SHOP_URL = '<?php echo userUrl('shop.php'); ?>';

    function decodeHtml(str) {
        const txt = document.createElement('textarea');
        txt.innerHTML = str || '';
        return txt.value;
    }

    // Build desktop & mobile menus from categories (ordered by display_order/name from API)
    async function loadHeaderCategories() {
        try {
            const res = await fetch(CATEGORY_API, { credentials: 'include' });
            const raw = await res.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch (err) {
                console.error('Header categories JSON parse failed', err, raw);
                throw err;
            }
            const cats = data.data || data || [];
            // Keep only active + show_in_header
            const filtered = cats
                .filter(c => parseInt(c.is_active ?? 0) === 1 && parseInt(c.show_in_header ?? 1) === 1)
                .sort((a, b) => {
                    const ao = parseInt(a.display_order ?? 0);
                    const bo = parseInt(b.display_order ?? 0);
                    if (ao !== bo) return ao - bo;
                    return (a.name || '').localeCompare(b.name || '');
                });
            buildMenus(filtered);
        } catch (e) {
            console.error('Failed to load categories for header', e);
            const navMenu = document.getElementById('nav-menu-dynamic');
            if (navMenu) navMenu.innerHTML = '<li><span style="color:#c00;">Menu unavailable</span></li>';
            const mobileContent = document.getElementById('mobile-nav-content');
            if (mobileContent) mobileContent.innerHTML = '<div class="mobile-nav-item"><span style="color:#c00;">Menu unavailable</span></div>';
        }
    }

    function buildMenus(items) {
        const navMenu = document.getElementById('nav-menu-dynamic');
        const mobileContent = document.getElementById('mobile-nav-content');
        if (!navMenu || !mobileContent) return;

        const byParent = {};
        items.forEach(c => {
            const pid = c.parent_id || 0;
            if (!byParent[pid]) byParent[pid] = [];
            byParent[pid].push(c);
        });

        const top = byParent[0] || byParent[null] || [];

        const renderDesktop = (cat) => {
            const children = byParent[cat.id] || [];
            const hasChild = children.length > 0;
            const slug = cat.slug ? encodeURIComponent(cat.slug) : '';
            const link = slug ? `${SHOP_URL}?category=${slug}` : '#';
            const displayName = decodeHtml(cat.name || '');
            const childHtml = hasChild
                ? `<ul class="dropdown">${children.map(renderDesktop).join('')}</ul>`
                : '';
            return `<li class="${hasChild ? 'has-dropdown' : ''}"><a href="${link}">${displayName}</a>${childHtml}</li>`;
        };

        const renderMobile = (cat) => {
            const children = byParent[cat.id] || [];
            const hasChild = children.length > 0;
            const slug = cat.slug ? encodeURIComponent(cat.slug) : '';
            const link = slug ? `${SHOP_URL}?category=${slug}` : '#';
            const displayName = decodeHtml(cat.name || '');
            const childHtml = hasChild
                ? `<div class="mobile-nav-submenu">${children.map(c => renderMobile(c)).join('')}</div>`
                : '';
            return `<div class="mobile-nav-item ${hasChild ? 'has-dropdown' : ''}">
                        <a href="${link}" class="mobile-nav-link">
                            ${displayName}${hasChild ? ' <i class="fa-solid fa-plus toggle-icon"></i>' : ''}
                        </a>
                        ${childHtml}
                    </div>`;
        };

        navMenu.innerHTML = top.length ? top.map(renderDesktop).join('') : '<li><em>No categories</em></li>';
        mobileContent.innerHTML = top.length ? top.map(renderMobile).join('') : '<div class="mobile-nav-item"><em>No categories</em></div>';

        attachMobileDropdownHandlers();
    }

    function attachMobileDropdownHandlers() {
        const mobileNavLinks = document.querySelectorAll('#mobile-nav-content .mobile-nav-link');
        mobileNavLinks.forEach(link => {
            link.onclick = function(e) {
                const parentItem = this.closest('.mobile-nav-item');
                const hasDropdown = parentItem && parentItem.classList.contains('has-dropdown');
                if (!hasDropdown) return;
                e.preventDefault();
                const isActive = parentItem.classList.contains('active');
                document.querySelectorAll('#mobile-nav-content .mobile-nav-item').forEach(item => item.classList.remove('active'));
                if (!isActive) parentItem.classList.add('active');
            };
        });
    }

        // Update wishlist count
        async function updateWishlistCount() {
            try {
                const data = await apiFetch(getApiUrl('/api/wishlist/get.php'));
                const count = data.wishlist ? data.wishlist.length : 0;
                const el = document.getElementById('wishlist-count');
                if (el) el.textContent = count;
            } catch (error) {
                // ignore
            }
        }
    // Update cart count
    async function updateCartCount() {
        try {
            const data = await apiFetch(getApiUrl('/api/cart/get.php'), {
                credentials: 'same-origin'
            });
            const el = document.getElementById('cart-count');
            if (el && data.summary) el.textContent = data.summary.item_count;
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
                        <a href="#" onclick="logout(); return false;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
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
            
            // Handle dropdown toggle clicks
            const dropdownToggles = mobileNavDropdown.querySelectorAll('.mobile-nav-item.has-dropdown > .mobile-nav-link');
            dropdownToggles.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const item = this.closest('.mobile-nav-item');
                    item.classList.toggle('open');
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
            
            // Close menu when clicking on a link
            const menuLinks = navMenu.querySelectorAll('a');
            menuLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // If it's a parent item with dropdown, toggle dropdown instead
                    const parentLi = link.closest('li.has-dropdown');
                    if (parentLi && link.parentElement === parentLi) {
                        e.preventDefault();
                        parentLi.classList.toggle('open');
                    } else {
                        // Close menu for regular links
                        navMenu.classList.remove('active');
                        document.body.classList.remove('menu-open');
                    }
                });
            });
        }
    });
    
    // Fetch opening hours from Google Places API
    let weeklyHours = [];
    
    async function fetchOpeningHours() {
        try {
            const data = await apiFetch(getApiUrl('/api/opening-hours.php'));
            
            if (data.success && data.today_hours) {
                const displayElement = document.getElementById('opening-hours-display');
                
                // Store weekly hours for dropdown
                weeklyHours = data.weekday_text || [];
                
                // Extract just the hours part (remove day name)
                const hoursOnly = data.today_hours.replace(/^[A-Za-z]+:\s*/, '');
                
                // Add open/closed indicator
                const statusIcon = data.open_now 
                    ? '<span style="color: #4caf50; margin-right: 5px;">●</span>' 
                    : '<span style="color: #f44336; margin-right: 5px;">●</span>';
                
                const statusText = data.open_now ? 'Open Now' : 'Closed';
                
                displayElement.innerHTML = statusIcon + statusText + ' - ' + hoursOnly;
                
                // Populate dropdown list
                populateHoursList(data.weekday_text, data.open_now);
            }
        } catch (error) {
            console.error('Error fetching opening hours:', error);
            // Keep default hours on error
        }
    }
    
    function populateHoursList(weekdayText, isOpenNow) {
        const listElement = document.getElementById('opening-hours-list');
        
        if (!weekdayText || weekdayText.length === 0) {
            listElement.innerHTML = '<div class="hours-item">Hours not available</div>';
            return;
        }
        
        const today = new Date().getDay(); // 0 (Sunday) to 6 (Saturday)
        const adjustedToday = (today + 6) % 7; // Convert to Monday-first (0=Monday, 6=Sunday)
        
        listElement.innerHTML = weekdayText.map((hours, index) => {
            const isToday = index === adjustedToday;
            const dayClass = isToday ? 'hours-item today' : 'hours-item';
            const parts = hours.split(': ');
            const day = parts[0];
            const time = parts[1] || 'Closed';
            
            return '<div class="' + dayClass + '">' +
                '<span class="day-name">' + day + '</span>' +
                '<span class="day-hours">' + time + '</span>' +
                '</div>';
        }).join('');
    }
    
    // Toggle opening hours dropdown
    document.getElementById('opening-hours-box')?.addEventListener('click', function(e) {
        e.stopPropagation();
        const modal = document.getElementById('opening-hours-modal');
        const icon = document.getElementById('hours-dropdown-icon');
        
        modal.classList.add('active');
        icon.style.transform = 'rotate(180deg)';
        document.body.style.overflow = 'hidden';
    });
    
    // Location button click
    document.getElementById('location-btn')?.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const modal = document.getElementById('location-modal');
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
    
    // Close location modal
    function closeLocationModal() {
        const modal = document.getElementById('location-modal');
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    document.getElementById('close-location-modal')?.addEventListener('click', closeLocationModal);
    document.getElementById('modal-backdrop-location')?.addEventListener('click', closeLocationModal);
    
    // Close modal functions
    function closeHoursModal() {
        const modal = document.getElementById('opening-hours-modal');
        const icon = document.getElementById('hours-dropdown-icon');
        
        modal.classList.remove('active');
        icon.style.transform = 'rotate(0deg)';
        document.body.style.overflow = '';
    }
    
    // Close button
    document.getElementById('close-hours-modal')?.addEventListener('click', closeHoursModal);
    
    // Backdrop click
    document.getElementById('modal-backdrop')?.addEventListener('click', closeHoursModal);
    
    // ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeHoursModal();
            closeLocationModal();
        }
    });
    
    // Initialize
    updateCartCount();
    updateWishlistCount();
    checkAuth();
    fetchOpeningHours();
    
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
    
    // Mobile menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        loadHeaderCategories();
        // Mobile categories menu button
        const mobileCategoriesBtn = document.getElementById('mobile-categories-btn');
        const mobileNavDropdown = document.getElementById('mobile-nav-dropdown');
        
        if (mobileCategoriesBtn && mobileNavDropdown) {
            mobileCategoriesBtn.addEventListener('click', function(e) {
                e.preventDefault();
                mobileNavDropdown.classList.toggle('active');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!mobileCategoriesBtn.contains(e.target) && !mobileNavDropdown.contains(e.target)) {
                    mobileNavDropdown.classList.remove('active');
                }
            });
        }
        
        // Mobile dropdown toggles (subcategories)
        const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const parentItem = this.closest('.mobile-nav-item');
                const hasSubmenu = parentItem && parentItem.classList.contains('has-dropdown');
                
                if (hasSubmenu) {
                    e.preventDefault();
                    
                    // Close other dropdowns
                    document.querySelectorAll('.mobile-nav-item').forEach(item => {
                        if (item !== parentItem) {
                            item.classList.remove('active');
                        }
                    });
                    
                    // Toggle current dropdown
                    parentItem.classList.toggle('active');
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
</script>

<?php include 'url-cleaner.php'; ?>
