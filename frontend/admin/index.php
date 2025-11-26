<?php
session_start();

// Check if user is admin
$isAdmin = ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true;

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    header('Location: ../admin-login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Demolition Traders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #2f3192 0%, #1a1d5c 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 13px;
            opacity: 0.7;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            display: block;
            padding: 15px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .menu-item:hover,
        .menu-item.active {
            background: rgba(255,255,255,0.1);
            color: #ffca0d;
            border-left-color: #ffca0d;
        }

        .menu-item i {
            width: 25px;
            margin-right: 10px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
        }

        /* Top Bar */
        .topbar {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h1 {
            font-size: 28px;
            color: #2f3192;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2f3192, #ffca0d);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .logout-btn {
            padding: 8px 20px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.red { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2f3192;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Content Section */
        .content-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            font-size: 22px;
            color: #2f3192;
            font-weight: 600;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2f3192, #1a1d5c);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(47, 49, 146, 0.3);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-sm {
            padding: 5px 12px;
            font-size: 12px;
        }

        /* Loading Spinner */
        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #2f3192;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -260px;
                transition: margin-left 0.3s;
            }

            .sidebar.active {
                margin-left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Demolition Traders</h2>
                <p>Admin Panel</p>
            </div>
            <nav class="sidebar-menu">
                <a href="index.php" class="menu-item active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="products.php" class="menu-item">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="categories.php" class="menu-item">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="orders.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="customers.php" class="menu-item">
                    <i class="fas fa-users"></i> Customers
                </a>
                <a href="low-stock.php" class="menu-item">
                    <i class="fas fa-exclamation-triangle"></i> Low Stock
                </a>
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </nav>
        </aside>

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
                const productsRes = await fetch('/demolitiontraders/backend/api/index.php?request=products&per_page=1');
                const productsData = await productsRes.json();
                document.getElementById('total-products').textContent = productsData.pagination?.total || (Array.isArray(productsData.data) ? productsData.data.length : 0);

                // Orders count
                let ordersCount = 0;
                try {
                    const ordersRes = await fetch('/demolitiontraders/backend/api/index.php?request=orders&per_page=1');
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
                    const customersRes = await fetch('/demolitiontraders/backend/api/index.php?request=customers&per_page=1');
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
                    const lowStockRes = await fetch('/demolitiontraders/backend/api/index.php?request=products&low_stock=1');
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
                const res = await fetch('/demolitiontraders/backend/api/index.php?request=orders');
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
