<?php
// Detect if we're in /admin/ subfolder or root frontend/
$inAdminFolder = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$prefix = $inAdminFolder ? '../' : '';
$adminPrefix = $inAdminFolder ? '' : 'admin/';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Demolition Traders</h2>
        <p>Admin Panel</p>
    </div>
    <nav class="sidebar-menu">
        <a href="<?php echo $adminPrefix; ?>index.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="<?php echo $adminPrefix; ?>products.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> Products
        </a>
        <a href="<?php echo $adminPrefix; ?>categories.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i> Categories
        </a>
        <a href="<?php echo $adminPrefix; ?>orders.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i> Orders
        </a>
        <a href="<?php echo $adminPrefix; ?>customers.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Customers
        </a>
        
        <div class="menu-divider"></div>
        <div class="menu-label">Customer Inquiries</div>
        
        <a href="<?php echo $prefix; ?>admin-sell-to-us.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin-sell-to-us.php' ? 'active' : ''; ?>">
            <i class="fas fa-handshake"></i> Sell to Us
        </a>
        <a href="<?php echo $prefix; ?>admin-wanted-listings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin-wanted-listings.php' ? 'active' : ''; ?>">
            <i class="fas fa-search"></i> Wanted Listings
        </a>
        <a href="<?php echo $prefix; ?>admin-contact.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin-contact.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i> Contact Messages
        </a>
        
        <div class="menu-divider"></div>
        
        <a href="<?php echo $adminPrefix; ?>admins.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i> Admins
        </a>
        <a href="<?php echo $adminPrefix; ?>low-stock.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'low-stock.php' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i> Low Stock
        </a>
        <a href="<?php echo $adminPrefix; ?>settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
        
        <div class="menu-divider"></div>
        
        <a href="<?php echo $prefix; ?>../logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>
