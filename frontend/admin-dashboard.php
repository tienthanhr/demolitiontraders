<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Demolition Traders</title>
    <base href="/demolitiontraders/frontend/">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f5f5; }
        .admin-header {
            background: #212121;
            color: white;
            padding: 15px 0;
            margin-bottom: 30px;
        }
        .admin-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .admin-nav {
            display: flex;
            gap: 20px;
        }
        .admin-nav a {
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .admin-nav a:hover {
            background: rgba(255,255,255,0.1);
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .stat-card h3 {
            font-size: 36px;
            margin: 10px 0;
            color: #d32f2f;
        }
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
        .section-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .section-card h2 {
            margin-top: 0;
            color: #212121;
            border-bottom: 2px solid #d32f2f;
            padding-bottom: 10px;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .action-btn {
            flex: 1;
            min-width: 200px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.3s;
        }
        .action-btn:hover {
            transform: translateY(-3px);
        }
        .action-btn i {
            display: block;
            font-size: 32px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #4caf50; color: white; }
        .badge-warning { background: #ff9800; color: white; }
        .badge-danger { background: #f44336; color: white; }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
            <div class="admin-nav">
                <span id="admin-name">Welcome, Admin</span>
                <a href="index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a>
                <a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Statistics -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <i class="fas fa-box" style="color: #2196f3;"></i>
                <h3 id="total-products">0</h3>
                <p>Total Products</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-shopping-cart" style="color: #4caf50;"></i>
                <h3 id="total-orders">0</h3>
                <p>Total Orders</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-users" style="color: #ff9800;"></i>
                <h3 id="total-users">0</h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-sync" style="color: #9c27b0;"></i>
                <h3 id="last-sync">Never</h3>
                <p>Last POS Sync</p>
            </div>
        </div>
        
        <!-- IdealPOS Integration -->
        <div class="section-card">
            <h2><i class="fas fa-plug"></i> IdealPOS Integration</h2>
            <p>Sync your products, inventory, and orders with IdealPOS system.</p>
            <div class="action-buttons">
                <button class="action-btn" onclick="syncProducts()" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-box"></i>
                    Sync Products
                </button>
                <button class="action-btn" onclick="syncInventory()" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-warehouse"></i>
                    Sync Inventory
                </button>
                <button class="action-btn" onclick="viewSyncLogs()" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-list"></i>
                    View Sync Logs
                </button>
            </div>
            <div id="sync-result" style="margin-top: 20px;"></div>
        </div>
        
        <!-- Recent Orders -->
        <div class="section-card">
            <h2><i class="fas fa-shopping-bag"></i> Recent Orders</h2>
            <table id="orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>POS Sync</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="orders-body">
                    <tr><td colspan="6" style="text-align: center; padding: 30px;">Loading orders...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Products Management -->
        <div class="section-card">
            <h2><i class="fas fa-cubes"></i> Recent Products</h2>
            <table id="products-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Condition</th>
                        <th>POS ID</th>
                    </tr>
                </thead>
                <tbody id="products-body">
                    <tr><td colspan="6" style="text-align: center; padding: 30px;">Loading products...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Check authentication
        async function checkAuth() {
            try {
                const response = await fetch('/demolitiontraders/api/auth/me');
                if (!response.ok) {
                    window.location.href = 'admin-login.php';
                    return;
                }
                const user = await response.json();
                document.getElementById('admin-name').textContent = `Welcome, ${user.first_name}`;
                
                if (user.role !== 'admin') {
                    alert('Access denied! Admin privileges required.');
                    window.location.href = 'index.php';
                }
            } catch (error) {
                window.location.href = 'admin-login.php';
            }
        }
        
        // Load statistics
        async function loadStats() {
            try {
                // Products count
                const productsResp = await fetch('/demolitiontraders/api/products?per_page=1');
                const productsData = await productsResp.json();
                document.getElementById('total-products').textContent = productsData.pagination?.total || 0;
                
                // For orders and users, we'll show placeholder for now
                document.getElementById('total-orders').textContent = '0';
                document.getElementById('total-users').textContent = '1';
                
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }
        
        // Load recent products
        async function loadProducts() {
            try {
                const response = await fetch('/demolitiontraders/api/products?per_page=5');
                const data = await response.json();
                
                const tbody = document.getElementById('products-body');
                if (data.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 30px;">No products found</td></tr>';
                    return;
                }
                
                tbody.innerHTML = data.data.map(p => `
                    <tr>
                        <td><strong>${p.sku}</strong></td>
                        <td>${p.name}</td>
                        <td>$${parseFloat(p.price).toFixed(2)}</td>
                        <td>${p.stock_quantity}</td>
                        <td><span class="badge badge-${p.condition_type === 'new' ? 'success' : 'warning'}">${p.condition_type}</span></td>
                        <td>${p.idealpos_product_id || '-'}</td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }
        
        // Load recent orders
        async function loadOrders() {
            try {
                const response = await fetch('/demolitiontraders/api/orders');
                const data = await response.json();
                
                console.log('Orders data:', data);
                
                const tbody = document.getElementById('orders-body');
                
                // Handle array response
                const orders = Array.isArray(data) ? data : (data.data || []);
                
                if (orders.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 30px;">No orders found</td></tr>';
                    return;
                }
                
                // Update total orders stat
                document.getElementById('total-orders').textContent = orders.length;
                
                tbody.innerHTML = orders.slice(0, 10).map(o => {
                    const statusColors = {
                        'pending': 'warning',
                        'processing': 'info',
                        'completed': 'success',
                        'cancelled': 'danger'
                    };
                    
                    return `
                        <tr>
                            <td><strong>${o.order_number}</strong></td>
                            <td>${o.guest_email || 'Guest'}</td>
                            <td>$${parseFloat(o.total_amount).toFixed(2)}</td>
                            <td><span class="badge badge-${statusColors[o.status] || 'secondary'}">${o.status}</span></td>
                            <td><span class="badge badge-${o.payment_status === 'paid' ? 'success' : 'warning'}">${o.payment_status}</span></td>
                            <td>${formatDate(o.created_at)}</td>
                        </tr>
                    `;
                }).join('');
            } catch (error) {
                console.error('Error loading orders:', error);
                document.getElementById('orders-body').innerHTML = 
                    '<tr><td colspan="6" style="text-align: center; padding: 30px; color: red;">Error loading orders. Check console.</td></tr>';
            }
        }
        
        // Sync products from IdealPOS
        async function syncProducts() {
            const result = document.getElementById('sync-result');
            result.innerHTML = '<p style="color: #2196f3;">⏳ Syncing products from IdealPOS...</p>';
            
            try {
                const response = await fetch('/demolitiontraders/api/idealpos/sync-products');
                const data = await response.json();
                
                if (response.ok) {
                    result.innerHTML = `<p style="color: #4caf50;">✓ Sync successful! ${data.synced} products synced, ${data.failed} failed.</p>`;
                    loadProducts();
                    loadStats();
                } else {
                    result.innerHTML = `<p style="color: #f44336;">✗ Sync failed: ${data.error}</p>`;
                }
            } catch (error) {
                result.innerHTML = '<p style="color: #f44336;">✗ Error: IdealPOS integration not configured. Please add API credentials to .env file.</p>';
            }
        }
        
        // Sync inventory
        async function syncInventory() {
            const result = document.getElementById('sync-result');
            result.innerHTML = '<p style="color: #2196f3;">⏳ Syncing inventory from IdealPOS...</p>';
            
            try {
                const response = await fetch('/demolitiontraders/api/idealpos/sync-inventory');
                const data = await response.json();
                
                if (response.ok) {
                    result.innerHTML = `<p style="color: #4caf50;">✓ Inventory sync successful! ${data.synced} items updated.</p>`;
                    loadProducts();
                } else {
                    result.innerHTML = `<p style="color: #f44336;">✗ Sync failed: ${data.error}</p>`;
                }
            } catch (error) {
                result.innerHTML = '<p style="color: #f44336;">✗ Error: IdealPOS integration not configured.</p>';
            }
        }
        
        // View sync logs
        function viewSyncLogs() {
            alert('Sync logs feature - Check logs/cron-sync.log file for detailed sync history');
        }
        
        // Logout
        async function logout() {
            try {
                await fetch('/demolitiontraders/api/auth/logout', { method: 'POST' });
                window.location.href = 'admin-login.php';
            } catch (error) {
                window.location.href = 'admin-login.php';
            }
        }
        
        // Initialize
        checkAuth();
        loadStats();
        loadOrders();
        loadProducts();
    </script>
    <?php include 'components/toast-notification.php'; ?>
</body>
</html>
