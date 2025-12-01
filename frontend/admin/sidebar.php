<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Demolition Traders</h2>
        <p>Admin Panel</p>
    </div>
    <nav class="sidebar-menu">
        <a href="index.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="products.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> Products
        </a>
        <a href="categories.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i> Categories
        </a>
        <a href="orders.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i> Orders
        </a>
        <a href="customers.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Customers
        </a>
        <a href="admins.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i> Admins
        </a>
        <a href="low-stock.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'low-stock.php' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i> Low Stock
        </a>
        <a href="settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
    </nav>
</aside>
