<?php
require_once '../frontend/config.php';

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is admin
$isAdmin = ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true;

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    header('Location: ' . $protocol . $host . BASE_PATH . 'admin-login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="admin/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/api-helper.js"></script>
    <script>
        // Immediately check session on admin pages — triggers client redirect on 401 via api-helper
        (async function() {
            try {
                await apiGet('/api/index.php?request=session');
            } catch (e) {
                console.warn('Session check failed:', e);
            }
        })();
    </script>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <?php include 'topbar.php'; ?>

            <!-- Dashboard Stats -->
            <div id="dashboard-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value" id="total-products">-</div>
                                <div class="stat-label">Total Products</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value" id="total-orders">-</div>
                                <div class="stat-label">Total Orders</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value" id="total-customers">-</div>
                                <div class="stat-label">Customers</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value" id="low-stock">-</div>
                                <div class="stat-label">Low Stock</div>
                            </div>
                            <div class="stat-icon red">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-section" id="recent-activity-section">
                    <div class="section-header">
                        <h2 class="section-title">Recent Activity</h2>
                    </div>
                    <div class="loading" id="recent-activity-loading">
                        <div class="spinner"></div>
                        <p>Loading recent activity...</p>
                    </div>
                    <div id="recent-activity-content" style="display:none;"></div>
                </div>
            </div>

            <!-- Dynamic Content Area -->
            <div id="dynamic-content"></div>
        </main>
    </div>

    <script>
        // Load statistics
        async function loadStats() {
            try {
                // Products count
                const productsRes = await fetch(getApiUrl('/api/index.php?request=products&per_page=1'));
                const productsData = await productsRes.json();
                document.getElementById('total-products').textContent = productsData.pagination?.total || (Array.isArray(productsData.data) ? productsData.data.length : 0);

                // Orders count
                let ordersCount = 0;
                try {
                    const ordersRes = await fetch(getApiUrl('/api/index.php?request=orders&per_page=1'));
                    const ordersData = await ordersRes.json();
                    if (ordersData.pagination && typeof ordersData.pagination.total === 'number') {
                        ordersCount = ordersData.pagination.total;
                    } else if (Array.isArray(ordersData.data)) {
                        ordersCount = ordersData.data.length;
                    } else if (Array.isArray(ordersData)) {
                        ordersCount = ordersData.length;
                    }
                } catch (e) {
                    console.error('Error loading orders:', e);
                }
                document.getElementById('total-orders').textContent = ordersCount;

                // Customers count
                let customersCount = 0;
                try {
                    const customersRes = await fetch(getApiUrl('/api/index.php?request=customers&per_page=1'));
                    const customersData = await customersRes.json();
                    if (customersData.pagination && typeof customersData.pagination.total === 'number') {
                        customersCount = customersData.pagination.total;
                    } else if (Array.isArray(customersData.data)) {
                        customersCount = customersData.data.length;
                    } else if (Array.isArray(customersData)) {
                        customersCount = customersData.length;
                    }
                } catch (e) {
                    console.error('Error loading customers:', e);
                }
                document.getElementById('total-customers').textContent = customersCount;

                // Low stock (optional, fallback 0)
                let lowStock = 0;
                try {
                    const lowStockRes = await fetch(getApiUrl('/api/index.php?request=products&low_stock=1'));
                    const lowStockData = await lowStockRes.json();
                    if (Array.isArray(lowStockData.data)) {
                        lowStock = lowStockData.data.length;
                    } else if (Array.isArray(lowStockData)) {
                        lowStock = lowStockData.length;
                    }
                } catch (e) {
                    console.error('Error loading low stock:', e);
                }
                document.getElementById('low-stock').textContent = lowStock;

                // Load recent activity (today's orders)
                await loadRecentActivity();
            } catch (error) {
                console.error('Error loading stats:', error);
                // Hide spinner if error
                const loadingDiv = document.getElementById('recent-activity-loading');
                if (loadingDiv) loadingDiv.style.display = 'none';
            }
        }

        // Load recent activity: today's orders
        async function loadRecentActivity() {
            const loadingDiv = document.getElementById('recent-activity-loading');
            const contentDiv = document.getElementById('recent-activity-content');
            try {
                // Lấy ngày hôm nay dạng YYYY-MM-DD
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                const todayStr = `${yyyy}-${mm}-${dd}`;
                
                // Gọi API lấy orders
                const res = await fetch(getApiUrl('/api/index.php?request=orders'));
                const data = await res.json();
                let orders = Array.isArray(data) ? data : (data.data || []);
                
                // Lọc đơn hàng có ngày tạo là hôm nay
                orders = orders.filter(order => {
                    if (!order.created_at) return false;
                    // Convert server time to local date string for comparison if needed, 
                    // but simple string match is often enough if server time matches local
                    return order.created_at.startsWith(todayStr);
                });
                
                // Sắp xếp mới nhất trước
                orders.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                
                // Lấy 10 đơn gần nhất
                const topOrders = orders.slice(0, 10);
                
                if (topOrders.length === 0) {
                    contentDiv.innerHTML = `<div class="empty-state"><i class="fas fa-clipboard-list"></i><p>No orders received today.</p></div>`;
                } else {
                    contentDiv.innerHTML = `
                        <div class="table-responsive">
                            <table class="activity-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${topOrders.map(order => {
                                        // Handle billing address (can be object or string)
                                        let billing = order.billing_address;
                                        if (typeof billing === 'string') {
                                            try { billing = JSON.parse(billing); } catch(e) { billing = {}; }
                                        }
                                        billing = billing || {};
                                        
                                        // Get customer name
                                        let customerName = `${billing.first_name || ''} ${billing.last_name || ''}`.trim();
                                        
                                        // Fallback to shipping if billing name empty
                                        if (!customerName) {
                                            let shipping = order.shipping_address;
                                            if (typeof shipping === 'string') {
                                                try { shipping = JSON.parse(shipping); } catch(e) { shipping = {}; }
                                            }
                                            shipping = shipping || {};
                                            customerName = `${shipping.first_name || ''} ${shipping.last_name || ''}`.trim();
                                        }
                                        
                                        // Fallback to email
                                        if (!customerName) {
                                            customerName = order.guest_email || billing.email || 'Guest';
                                        }
                                        
                                        const total = order.total_amount ? `$${parseFloat(order.total_amount).toFixed(2)}` : '-';
                                        const status = order.status ? order.status.toUpperCase() : 'PENDING';
                                        const statusClass = getStatusClass(status);
                                        const time = order.created_at ? new Date(order.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';
                                        const itemCount = order.items ? order.items.length : 0;
                                        
                                        return `<tr>
                                            <td><span class="order-id">#${order.order_number || order.id}</span></td>
                                            <td>
                                                <div class="customer-name">${customerName}</div>
                                                <div class="customer-email">${order.guest_email || billing.email || ''}</div>
                                            </td>
                                            <td>${itemCount} item${itemCount !== 1 ? 's' : ''}</td>
                                            <td class="amount">${total}</td>
                                            <td><span class="status-badge ${statusClass}">${status}</span></td>
                                            <td class="time">${time}</td>
                                        </tr>`;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                }
                loadingDiv.style.display = 'none';
                contentDiv.style.display = 'block';
            } catch (error) {
                loadingDiv.style.display = 'none';
                contentDiv.style.display = 'block';
                contentDiv.innerHTML = `<div class="error-state">Error loading recent activity.</div>`;
                console.error('Error loading recent activity:', error);
            }
        }
        
        function getStatusClass(status) {
            status = status.toLowerCase();
            if (status === 'completed' || status === 'shipped' || status === 'paid') return 'status-success';
            if (status === 'pending' || status === 'processing') return 'status-warning';
            if (status === 'cancelled' || status === 'refunded') return 'status-danger';
            return 'status-secondary';
        }

        // Initialize
        loadStats();
    </script>
</body>
</html>
