<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
$isAdmin = ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true;

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    header('Location: ' . BASE_PATH . 'admin-login');
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
                // Gọi API lấy orders hôm nay (giả sử backend hỗ trợ filter by date, nếu không sẽ filter ở FE)
                const res = await fetch(getApiUrl('/api/index.php?request=orders'));
                const data = await res.json();
                let orders = Array.isArray(data) ? data : (data.data || []);
                // Lọc đơn hàng có ngày tạo là hôm nay
                orders = orders.filter(order => {
                    if (!order.created_at) return false;
                    return order.created_at.startsWith(todayStr);
                });
                // Sắp xếp mới nhất trước
                orders.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                // Lấy 5 đơn gần nhất
                const topOrders = orders.slice(0, 5);
                if (topOrders.length === 0) {
                    contentDiv.innerHTML = `<div style='text-align:center;color:#888;font-size:16px;padding:30px;'>No recent orders today.</div>`;
                } else {
                    contentDiv.innerHTML = `
                        <table style='width:100%;border-collapse:collapse;'>
                            <thead style='background:#f8f9fa;'>
                                <tr>
                                    <th style='padding:8px 6px;text-align:left;'>Order #</th>
                                    <th style='padding:8px 6px;text-align:left;'>Customer</th>
                                    <th style='padding:8px 6px;text-align:right;'>Total</th>
                                    <th style='padding:8px 6px;text-align:left;'>Status</th>
                                    <th style='padding:8px 6px;text-align:left;'>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${topOrders.map(order => {
                                    let billing = {};
                                    try { billing = JSON.parse(order.billing_address || '{}'); } catch(e){}
                                    const customer = `${billing.first_name || ''} ${billing.last_name || ''}`.trim() || 'Guest';
                                    const total = order.total_amount ? `$${parseFloat(order.total_amount).toFixed(2)}` : '-';
                                    const status = order.status ? order.status.toUpperCase() : 'PENDING';
                                    const time = order.created_at ? new Date(order.created_at).toLocaleTimeString() : '';
                                    return `<tr>
                                        <td style='padding:8px 6px;'><strong>#${order.id}</strong></td>
                                        <td style='padding:8px 6px;'>${customer}</td>
                                        <td style='padding:8px 6px;text-align:right;'>${total}</td>
                                        <td style='padding:8px 6px;'>${status}</td>
                                        <td style='padding:8px 6px;'>${time}</td>
                                    </tr>`;
                                }).join('')}
                            </tbody>
                        </table>
                    `;
                }
                loadingDiv.style.display = 'none';
                contentDiv.style.display = '';
            } catch (error) {
                loadingDiv.style.display = 'none';
                contentDiv.style.display = '';
                contentDiv.innerHTML = `<div style='text-align:center;color:#c00;font-size:16px;padding:30px;'>Error loading recent activity.</div>`;
                console.error('Error loading recent activity:', error);
            }
        }

        // Initialize
        loadStats();
    </script>
</body>
</html>
