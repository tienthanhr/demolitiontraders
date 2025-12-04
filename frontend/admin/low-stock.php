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
    <title>Low Stock - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="admin/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/api-helper.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <?php include 'topbar.php'; ?>
            <div class="content-section">
                <h1>Low Stock Products</h1>
                <div id="low-stock-table">
                    <div style="text-align:center;padding:40px;color:#888;">Loading low stock products...</div>
                </div>
            </div>
        </main>
    </div>
    <script>
    async function loadLowStock() {
        const tableDiv = document.getElementById('low-stock-table');
        try {
            const res = await fetch(getApiUrl('/api/index.php?request=products&low_stock=1'));
            const data = await res.json();
            let products = Array.isArray(data) ? data : (data.data || []);
            if (!products.length) {
                tableDiv.innerHTML = '<div style="text-align:center;color:#888;padding:40px;">No low stock products found.</div>';
                return;
            }
            tableDiv.innerHTML = `<div class="table-container"><table><thead><tr><th>Product</th><th>SKU</th><th>Stock</th><th>Action</th></tr></thead><tbody>${products.map(p => `
                <tr>
                    <td data-label="Product">${p.product_name || p.name || 'N/A'}</td>
                    <td data-label="SKU">${p.sku || ''}</td>
                    <td data-label="Stock" class="stock-warning">${p.stock ?? '-'}</td>
                    <td data-label="Action"><a href="admin/products.php?sku=${encodeURIComponent(p.sku)}" style="color:#2f3192;text-decoration:underline;">View</a></td>
                </tr>
            `).join('')}</tbody></table></div>`;
        } catch (e) {
            tableDiv.innerHTML = '<div style="text-align:center;color:#c00;padding:40px;">Error loading low stock products.</div>';
        }
    }
        loadLowStock();
    </script>
    <script src="../assets/js/api-helper.js"></script>
</body>
</html>