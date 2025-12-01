<?php
require_once 'components/date-helper.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get order ID from URL
$order_id = $_GET['id'] ?? null;
if (!$order_id || !is_numeric($order_id)) {
    header('Location: profile.php');
    exit;
}

require_once '../backend/config/database.php';
$db = Database::getInstance();

// Get order details - ensure it belongs to current user
$order = $db->fetchOne(
    "SELECT o.*, 
            u.email as user_email, u.first_name as user_first_name, u.last_name as user_last_name
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.id
     WHERE o.id = ? AND o.user_id = ?",
    [$order_id, $_SESSION['user_id']]
);

if (!$order) {
    header('Location: profile.php');
    exit;
}

// Get order items
$items = $db->fetchAll(
    "SELECT oi.*
     FROM order_items oi
     WHERE oi.order_id = ?
     ORDER BY oi.id ASC",
    [$order_id]
);

// Decode addresses
$billing_address = json_decode($order['billing_address'], true);
$shipping_address = json_decode($order['shipping_address'], true);
?>
<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo htmlspecialchars($order['order_number']); ?> - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="assets/css/new-style.css?v=6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .order-detail-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #2f3192;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .order-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .order-header h1 {
            color: #2f3192;
            margin: 0 0 20px 0;
            font-size: 28px;
        }
        
        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .order-meta-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .order-meta-label {
            color: #666;
            font-size: 14px;
            font-weight: 600;
        }
        
        .order-meta-value {
            color: #333;
            font-size: 16px;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 15px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .status-pending { 
            background: linear-gradient(135deg, #ffd54f, #ffb300); 
            color: #7f4700; 
        }
        .status-processing { 
            background: linear-gradient(135deg, #4fc3f7, #0288d1); 
            color: #01579b; 
        }
        .status-completed { 
            background: linear-gradient(135deg, #81c784, #43a047); 
            color: #1b5e20; 
        }
        .status-cancelled { 
            background: linear-gradient(135deg, #e57373, #d32f2f); 
            color: #b71c1c; 
        }
        .status-paid { 
            background: linear-gradient(135deg, #81c784, #43a047); 
            color: #1b5e20; 
        }
        
        .order-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 968px) {
            .order-content {
                grid-template-columns: 1fr;
            }
        }
        
        .order-items {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .order-items h2 {
            color: #2f3192;
            margin: 0 0 20px 0;
            font-size: 22px;
        }
        
        .order-item {
            display: flex;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
            align-items: flex-start;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            background: #f5f5f5;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            color: #2f3192;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .item-sku {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .item-pricing {
            display: flex;
            gap: 20px;
            color: #333;
            font-size: 14px;
        }
        
        .item-total {
            text-align: right;
            font-weight: 600;
            color: #2f3192;
            font-size: 18px;
        }
        
        .order-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .sidebar-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .sidebar-section h3 {
            color: #2f3192;
            margin: 0 0 15px 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .address-info {
            color: #333;
            line-height: 1.6;
        }
        
        .address-info p {
            margin: 5px 0;
        }
        
        .order-summary {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        
        .summary-row.total {
            border-top: 2px solid #2f3192;
            padding-top: 15px;
            margin-top: 10px;
            font-size: 20px;
            font-weight: 700;
            color: #2f3192;
        }
        
        .summary-label {
            color: #666;
        }
        
        .summary-value {
            color: #333;
            font-weight: 600;
        }
        
        .payment-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .payment-icon {
            font-size: 24px;
            color: #2f3192;
        }
        
        .customer-notes {
            background: #fff8e1;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin-top: 15px;
        }
        
        .customer-notes strong {
            color: #f57c00;
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="order-detail-container">
        <a href="profile.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to My Account
        </a>
        
        <div class="order-header">
            <h1><i class="fas fa-receipt"></i> Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
            
            <div class="order-meta">
                <div class="order-meta-item">
                    <span class="order-meta-label">Order Date</span>
                    <span class="order-meta-value"><?php echo formatDate($order['created_at']); ?></span>
                </div>
                
                <div class="order-meta-item">
                    <span class="order-meta-label">Status</span>
                    <span class="order-meta-value">
                        <?php 
                        $statusIcons = [
                            'pending' => 'fa-clock',
                            'processing' => 'fa-spinner',
                            'completed' => 'fa-check-circle',
                            'cancelled' => 'fa-times-circle',
                            'paid' => 'fa-check-circle'
                        ];
                        $statusClass = strtolower($order['status']);
                        $icon = $statusIcons[$statusClass] ?? 'fa-info-circle';
                        ?>
                        <span class="status-badge status-<?php echo $statusClass; ?>">
                            <i class="fas <?php echo $icon; ?>"></i>
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </span>
                </div>
                
                <div class="order-meta-item">
                    <span class="order-meta-label">Total Amount</span>
                    <span class="order-meta-value" style="font-size: 20px; font-weight: 700; color: #2f3192;">
                        $<?php echo number_format($order['total_amount'], 2); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="order-content">
            <div class="order-items">
                <h2><i class="fas fa-box"></i> Order Items</h2>
                
                <?php foreach ($items as $item): ?>
                    <div class="order-item">
                        <div class="item-details" style="flex: 1;">
                            <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div class="item-sku">SKU: <?php echo htmlspecialchars($item['sku']); ?></div>
                            <div class="item-pricing">
                                <span>Quantity: <?php echo $item['quantity']; ?></span>
                                <span>Unit Price: $<?php echo number_format($item['unit_price'], 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="item-total">
                            $<?php echo number_format($item['total'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-sidebar">
                <div class="sidebar-section">
                    <h3><i class="fas fa-calculator"></i> Order Summary</h3>
                    <div class="order-summary">
                        <div class="summary-row">
                            <span class="summary-label">Subtotal:</span>
                            <span class="summary-value">$<?php echo number_format($order['subtotal'], 2); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Tax (GST):</span>
                            <span class="summary-value">$<?php echo number_format($order['tax_amount'], 2); ?></span>
                        </div>
                        
                        <?php if ($order['shipping_amount'] > 0): ?>
                        <div class="summary-row">
                            <span class="summary-label">Shipping:</span>
                            <span class="summary-value">$<?php echo number_format($order['shipping_amount'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($order['discount_amount'] > 0): ?>
                        <div class="summary-row">
                            <span class="summary-label">Discount:</span>
                            <span class="summary-value">-$<?php echo number_format($order['discount_amount'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="payment-info">
                        <i class="fas fa-credit-card payment-icon"></i>
                        <div>
                            <div style="font-weight: 600; color: #333;">Payment Method</div>
                            <div style="color: #666; font-size: 14px;"><?php echo ucfirst($order['payment_method']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="sidebar-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Billing Address</h3>
                    <div class="address-info">
                        <p><strong><?php echo htmlspecialchars($billing_address['first_name'] . ' ' . $billing_address['last_name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($billing_address['address']); ?></p>
                        <p><?php echo htmlspecialchars($billing_address['city']); ?> <?php echo htmlspecialchars($billing_address['postcode']); ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($billing_address['phone']); ?></p>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($billing_address['email']); ?></p>
                    </div>
                </div>
                
                <div class="sidebar-section">
                    <h3><i class="fas fa-truck"></i> Shipping Address</h3>
                    <div class="address-info">
                        <p><strong><?php echo htmlspecialchars($shipping_address['first_name'] . ' ' . $shipping_address['last_name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($shipping_address['address']); ?></p>
                        <p><?php echo htmlspecialchars($shipping_address['city']); ?> <?php echo htmlspecialchars($shipping_address['postcode']); ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($shipping_address['phone']); ?></p>
                    </div>
                </div>
                
                <?php if (!empty($order['customer_notes'])): ?>
                <div class="sidebar-section">
                    <h3><i class="fas fa-comment"></i> Order Notes</h3>
                    <div class="customer-notes">
                        <strong>Customer Notes:</strong>
                        <?php echo nl2br(htmlspecialchars($order['customer_notes'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'components/footer.php'; ?>
</body>
</html>
