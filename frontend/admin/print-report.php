<?php
require_once '../config.php';

// If a user requests the non-canonical frontend admin path directly, redirect to the canonical root admin path
$reqUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($reqUri, '/frontend/admin') === 0) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $newUri = preg_replace('#^/frontend/admin#', rtrim(BASE_PATH, '/') . '/admin', $reqUri);
    header('Location: ' . $protocol . $host . $newUri);
    exit;
}

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

// No direct DB access in frontend - report data will be provided by backend APIs and loaded via client-side JS
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Report - Demolition Traders</title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }
            body {
                font-size: 11pt;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-after: always;
            }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: white;
            padding: 20px;
        }
        
        .report-header {
            text-align: center;
            border-bottom: 3px solid #2f3192;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .report-header h1 {
            color: #2f3192;
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .report-header .subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .report-header .date {
            color: #999;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-box .value {
            font-size: 28px;
            font-weight: bold;
            color: #2f3192;
            margin-bottom: 5px;
        }
        
        .stat-box .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #2f3192;
            font-size: 20px;
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table th {
            background: #2f3192;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
        }
        
        table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        
        table tr:hover {
            background: #f9f9f9;
        }
        
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 0 5px;
        }
        
        .btn-primary {
            background: #2f3192;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            color: #999;
            font-size: 11px;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        
        @media print {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Print Report
        </button>
        <a href="settings.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Settings
        </a>
    </div>

    <div class="report-header">
        <h1>📊 Admin Report</h1>
        <div class="subtitle">Demolition Traders - Business Overview</div>
        <div class="date">Generated: <?php echo date('l, F j, Y \a\t g:i A'); ?></div>
    </div>

    <!-- Statistics Overview -->
    <div class="section">
        <h2>📈 Key Statistics</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="value" id="stat-total-products">-</div>
                <div class="label">Total Products</div>
            </div>
            <div class="stat-box">
                <div class="value" id="stat-total-categories">-</div>
                <div class="label">Categories</div>
            </div>
            <div class="stat-box">
                <div class="value" id="stat-total-orders">-</div>
                <div class="label">Total Orders</div>
            </div>
            <div class="stat-box">
                <div class="value" id="stat-total-revenue">$-</div>
                <div class="label">Total Revenue</div>
            </div>
            <div class="stat-box">
                <div class="value" id="stat-total-customers">-</div>
                <div class="label">Customers</div>
            </div>
            <div class="stat-box">
                <div class="value" id="stat-active-products">-</div>
                <div class="label">Active Products</div>
            </div>
            <div class="stat-box">
                <div class="value" id="stat-low-stock-products">-</div>
                <div class="label">Low Stock</div>
            </div>
            <div class="stat-box">
                <div class="value" id="stat-out-of-stock-products">-</div>
                <div class="label">Out of Stock</div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="section">
        <h2>🛒 Recent Orders (Last 10)</h2>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="recent-orders-tbody">
                <!-- Recent orders will be loaded via API -->
            </tbody>
        </table>
    </div>

    <!-- Top Products -->
    <div class="section page-break">
        <h2>🏆 Top Selling Products</h2>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Orders</th>
                    <th>Total Sold</th>
                </tr>
            </thead>
            <tbody id="top-products-tbody">
                <!-- Top products will be calculated and populated via API -->
            </tbody>
        </table>
    </div>

    <!-- Low Stock Alert -->
    <div class="section">
        <h2>⚠️ Low Stock Alert</h2>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>SKU</th>
                    <th>Stock Qty</th>
                    <th>Price</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="low-stock-tbody">
                <!-- Low stock products will be loaded via API -->
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p><strong>Demolition Traders</strong></p>
        <p>249 Kahikatea Drive, Greenlea Lane, Frankton, Hamilton</p>
        <p>Phone: 07 847 4989 | Email: info@demolitiontraders.co.nz</p>
        <p style="margin-top: 10px;">Report generated by <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>
    </div>
</body>
<script>
async function loadReportData() {
    try {
        // Fetch data from APIs
        const [productsRes, categoriesRes, ordersRes, customersRes] = await Promise.all([
            apiGet('/api/index.php?request=products&per_page=1000'),
            apiGet('/api/index.php?request=categories&per_page=1000'),
            apiGet('/api/index.php?request=orders&per_page=1000'),
            apiGet('/api/index.php?request=customers&per_page=1000')
        ]);

        const products = Array.isArray(productsRes) ? productsRes : (productsRes.data || []);
        const categories = Array.isArray(categoriesRes) ? categoriesRes : (categoriesRes.data || []);
        const orders = Array.isArray(ordersRes) ? ordersRes : (ordersRes.data || []);
        const customers = Array.isArray(customersRes) ? customersRes : (customersRes.data || []);

        // Compute stats
        document.getElementById('stat-total-products').textContent = (productsRes.pagination?.total ?? products.length) || 0;
        document.getElementById('stat-total-categories').textContent = (categoriesRes.pagination?.total ?? categories.length) || 0;
        document.getElementById('stat-total-orders').textContent = (ordersRes.pagination?.total ?? orders.length) || 0;
        const totalRevenue = orders.reduce((acc, o) => acc + (parseFloat(o.total_amount) || 0), 0);
        document.getElementById('stat-total-revenue').textContent = '$' + totalRevenue.toFixed(2);
        document.getElementById('stat-total-customers').textContent = (customersRes.pagination?.total ?? customers.length) || 0;
        document.getElementById('stat-active-products').textContent = (products.filter(p => p.is_active == 1).length) || 0;
        document.getElementById('stat-low-stock-products').textContent = (products.filter(p => p.stock_quantity > 0 && p.stock_quantity <= 10).length) || 0;
        document.getElementById('stat-out-of-stock-products').textContent = (products.filter(p => p.stock_quantity == 0).length) || 0;

        // Recent Orders (last 10)
        const recentOrders = orders.sort((a,b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 10);
        const recentTbody = document.getElementById('recent-orders-tbody');
        recentTbody.innerHTML = recentOrders.map(order => `
            <tr>
                <td>#${order.id}</td>
                <td>${(order.first_name || '') + ' ' + (order.last_name || '')}</td>
                <td>${order.email || ''}</td>
                <td>$${(parseFloat(order.total_amount) || 0).toFixed(2)}</td>
                <td><span class="badge ${order.status === 'delivered' ? 'badge-success' : (order.status === 'pending' ? 'badge-warning' : 'badge-secondary')}" style="padding:6px;">${(order.status || '').charAt(0).toUpperCase() + (order.status || '').slice(1)}</span></td>
                <td>${new Date(order.created_at).toLocaleDateString()}</td>
            </tr>`).join('');

        // Top products aggregate
        const productMap = {};
        orders.forEach(order => {
            if (Array.isArray(order.items)) {
                order.items.forEach(item => {
                    const id = item.product_id || item.id;
                    if (!id) return;
                    if (!productMap[id]) {
                        productMap[id] = { id, name: item.name || item.product_name || item.title || 'Unknown', total_sold: 0, order_count: 0, price: item.price || item.unit_price || 0, stock_quantity: item.stock_quantity || 0 };
                    }
                    productMap[id].total_sold += parseInt(item.quantity || 0);
                    productMap[id].order_count += 1;
                });
            }
        });
        const topProducts = Object.values(productMap).sort((a,b) => b.order_count - a.order_count).slice(0, 10);
        document.getElementById('top-products-tbody').innerHTML = topProducts.map(p => `
            <tr>
                <td>${p.name}</td>
                <td>$${(parseFloat(p.price) || 0).toFixed(2)}</td>
                <td>${p.stock_quantity || '-'}</td>
                <td>${p.order_count}</td>
                <td>${p.total_sold}</td>
            </tr>
        `).join('');

        // Low stock table
        const lowStockProducts = products.filter(p => p.stock_quantity > 0 && p.stock_quantity <= 10).slice(0, 10);
        document.getElementById('low-stock-tbody').innerHTML = lowStockProducts.map(p => `
            <tr>
                <td>${p.name}</td>
                <td>${p.sku || '-'}</td>
                <td><span class="badge ${p.stock_quantity <= 5 ? 'badge-danger' : 'badge-warning'}">${p.stock_quantity}</span></td>
                <td>$${(parseFloat(p.price) || 0).toFixed(2)}</td>
                <td>${p.stock_quantity <= 5 ? 'Critical' : 'Low'}</td>
            </tr>
        `).join('');

    } catch (err) {
        console.error('Error loading report data:', err);
    }
}

loadReportData();
</script>
</html>
