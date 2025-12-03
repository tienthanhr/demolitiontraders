<?php
// Admin URLs now use clean paths with base tag
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Demolition Traders</h2>
        <p>Admin Panel</p>
    </div>
    <nav class="sidebar-menu">
        <a href="admin/index.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="admin/products.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> Products
        </a>
        <a href="admin/categories.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i> Categories
        </a>
        <a href="admin/orders.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i> Orders
        </a>
        <a href="admin/customers.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Customers
        </a>
        
        <div class="menu-divider"></div>
        <div class="menu-label">Customer Inquiries</div>
        
        <a href="admin/sell-to-us.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'sell-to-us.php' ? 'active' : ''; ?>">
            <i class="fas fa-handshake"></i> Sell to Us
        </a>
        <a href="admin/wanted-listings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'wanted-listings.php' ? 'active' : ''; ?>">
            <i class="fas fa-star"></i> Wanted Listings
        </a>
        <a href="admin/contact-messages.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'contact-messages.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i> Contact Messages
        </a>
        
        <div class="menu-divider"></div>
        
        <a href="admin/admins.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i> Admins
        </a>
        <a href="admin/low-stock.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'low-stock.php' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i> Low Stock
        </a>
        <a href="admin/settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
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
</script>
