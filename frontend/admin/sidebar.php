<?php
// Admin URLs now use clean paths with base tag
?>
<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle" id="mobileMenuToggle">
    <i class="fas fa-bars"></i>
</button>

<aside class="sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <h2>Demolition Traders</h2>
        <p>Admin Panel</p>
        <button class="sidebar-close" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <nav class="sidebar-menu">
        <a href="<?php echo ADMIN_URL; ?>/index.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="<?php echo ADMIN_URL; ?>/products.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> Products
        </a>
        <a href="<?php echo ADMIN_URL; ?>/categories.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i> Categories
        </a>
        <a href="<?php echo ADMIN_URL; ?>/orders.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i> Orders
        </a>
        <a href="<?php echo ADMIN_URL; ?>/customers.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Customers
        </a>
        
        <div class="menu-divider"></div>
        <div class="menu-label">Customer Inquiries</div>
        
        <a href="<?php echo ADMIN_URL; ?>/sell-to-us.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'sell-to-us.php' ? 'active' : ''; ?>">
            <i class="fas fa-handshake"></i> Sell to Us
        </a>
        <a href="<?php echo ADMIN_URL; ?>/wanted-listings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'wanted-listings.php' ? 'active' : ''; ?>">
            <i class="fas fa-star"></i> Wanted Listings
        </a>
        <a href="<?php echo ADMIN_URL; ?>/contact-messages.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'contact-messages.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i> Contact Messages
        </a>
        
        <div class="menu-divider"></div>
        
        <a href="<?php echo ADMIN_URL; ?>/admins.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i> Admins
        </a>
        <a href="<?php echo ADMIN_URL; ?>/low-stock.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'low-stock.php' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i> Low Stock
        </a>
        <a href="<?php echo ADMIN_URL; ?>/settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
        
        <div class="menu-divider"></div>
        
        <a href="#" onclick="adminLogout(event)" class="menu-item">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>

<script>
function adminLogout(event) {
    event.preventDefault();
    if (confirm('Are you sure you want to logout?')) {
        // Clear session via logout.php then redirect to admin-login
        fetch('<?php echo BASE_PATH; ?>logout.php?admin=1')
            .then(() => {
                window.location.href = '<?php echo BASE_PATH; ?>admin-login';
            });
    }
}

// Mobile menu toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('adminSidebar');
    const closeBtn = document.getElementById('sidebarClose');
    
    if (menuToggle && sidebar) {
        // Toggle sidebar on button click
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        });
        
        // Close sidebar when clicking close button
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            });
        }
        
        // Close sidebar when clicking overlay (body::before)
        document.body.addEventListener('click', function(e) {
            if (document.body.classList.contains('sidebar-open') && 
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
        });
        
        // Close sidebar when clicking a menu item on mobile
        const menuItems = sidebar.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // Only close on mobile (when toggle button is visible)
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
        });
    }
});
</script>
