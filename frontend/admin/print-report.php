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

require_once '../../backend/config/database.php';

// Get statistics
$db = Database::getInstance();

$totalProducts = $db->fetchOne("SELECT COUNT(*) as count FROM products")['count'];
$totalCategories = $db->fetchOne("SELECT COUNT(*) as count FROM categories")['count'];
$totalOrders = $db->fetchOne("SELECT COUNT(*) as count FROM orders")['count'];
$totalRevenue = $db->fetchOne("SELECT SUM(total_amount) as total FROM orders WHERE status IN ('paid', 'processing', 'shipped', 'delivered')")['total'] ?? 0;
$totalCustomers = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")['count'];
$activeProducts = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE is_active = 1")['count'];
$lowStockProducts = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE stock_quantity > 0 AND stock_quantity <= 10")['count'];
$outOfStockProducts = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0")['count'];

// Recent orders
$recentOrders = $db->fetchAll(
    "SELECT o.*, u.first_name, u.last_name, u.email 
     FROM orders o 
     LEFT JOIN users u ON o.user_id = u.id 
     ORDER BY o.created_at DESC 
     LIMIT 10"
);

// Top selling products (by order count)
$topProducts = $db->fetchAll(
    "SELECT p.name, p.price, p.stock_quantity, COUNT(oi.id) as order_count, SUM(oi.quantity) as total_sold
     FROM products p
     LEFT JOIN order_items oi ON p.id = oi.product_id
     GROUP BY p.id
     ORDER BY order_count DESC
     LIMIT 10"
);

// Low stock products
$lowStock = $db->fetchAll(
    "SELECT name, sku, stock_quantity, price 
     FROM products 
     WHERE stock_quantity > 0 AND stock_quantity <= 10 
     ORDER BY stock_quantity ASC 
     LIMIT 10"
);
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
                <div class="value"><?php echo number_format($totalProducts); ?></div>
                <div class="label">Total Products</div>
            </div>
            <div class="stat-box">
                <div class="value"><?php echo number_format($totalCategories); ?></div>
                <div class="label">Categories</div>
            </div>
            <div class="stat-box">
                <div class="value"><?php echo number_format($totalOrders); ?></div>
                <div class="label">Total Orders</div>
            </div>
            <div class="stat-box">
                <div class="value">$<?php echo number_format($totalRevenue, 2); ?></div>
                <div class="label">Total Revenue</div>
            </div>
            <div class="stat-box">
                <div class="value"><?php echo number_format($totalCustomers); ?></div>
                <div class="label">Customers</div>
            </div>
            <div class="stat-box">
                <div class="value"><?php echo number_format($activeProducts); ?></div>
                <div class="label">Active Products</div>
            </div>
            <div class="stat-box">
                <div class="value"><?php echo number_format($lowStockProducts); ?></div>
                <div class="label">Low Stock</div>
            </div>
            <div class="stat-box">
                <div class="value"><?php echo number_format($outOfStockProducts); ?></div>
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
            <tbody>
                <?php foreach ($recentOrders as $order): ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['email']); ?></td>
                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td><span class="badge badge-<?php echo $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'info'); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentOrders)): ?>
                <tr><td colspan="6" style="text-align: center; color: #999;">No orders found</td></tr>
                <?php endif; ?>
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
            <tbody>
                <?php foreach ($topProducts as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo $product['stock_quantity']; ?></td>
                    <td><?php echo $product['order_count'] ?? 0; ?></td>
                    <td><?php echo $product['total_sold'] ?? 0; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($topProducts)): ?>
                <tr><td colspan="5" style="text-align: center; color: #999;">No products found</td></tr>
                <?php endif; ?>
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
            <tbody>
                <?php foreach ($lowStock as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                    <td><span class="badge badge-<?php echo $product['stock_quantity'] <= 5 ? 'danger' : 'warning'; ?>"><?php echo $product['stock_quantity']; ?></span></td>
                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo $product['stock_quantity'] <= 5 ? 'Critical' : 'Low'; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($lowStock)): ?>
                <tr><td colspan="5" style="text-align: center; color: #999;">All products have sufficient stock</td></tr>
                <?php endif; ?>
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
</html>
