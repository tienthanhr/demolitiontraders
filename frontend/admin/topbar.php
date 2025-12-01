<div class="topbar">
    <h1 id="page-title">
        <?php
        $page = basename($_SERVER['PHP_SELF'], '.php');
        $titles = [
            'index' => 'Dashboard',
            'products' => 'Products Management',
            'categories' => 'Categories Management',
            'orders' => 'Orders Management',
            'customers' => 'Customers Management',
            'settings' => 'Settings',
            'low-stock' => 'Low Stock Products'
        ];
        echo $titles[$page] ?? 'Admin Panel';
        ?>
    </h1>
    <div class="user-info">
        <div class="user-avatar">
            <?php echo strtoupper(substr($_SESSION['first_name'] ?? 'A', 0, 1)); ?>
        </div>
        <span><?php echo $_SESSION['first_name'] ?? 'Admin'; ?></span>
        <a href="#" onclick="confirmLogout(event)" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<script>
function confirmLogout(event) {
    event.preventDefault();
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../../logout.php';
    }
}
</script>
