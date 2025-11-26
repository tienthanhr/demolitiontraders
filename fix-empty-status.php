<?php
/**
 * Fix empty order status in database
 */

require_once __DIR__ . '/backend/config/database.php';

$db = Database::getInstance();

try {
    // Update all orders with empty status to 'pending'
    $result = $db->query(
        "UPDATE orders SET status = 'pending' WHERE status = '' OR status IS NULL"
    );
    
    // Get count of updated orders
    $count = $db->query("SELECT ROW_COUNT() as count")->fetch();
    
    echo "<h2>Order Status Fix</h2>";
    echo "<p>✅ Updated {$count['count']} orders from empty status to 'pending'</p>";
    
    // Show all orders
    $orders = $db->fetchAll("SELECT id, order_number, status FROM orders ORDER BY id DESC");
    
    echo "<h3>All Orders:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Order Number</th><th>Status</th></tr>";
    
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>#{$order['id']}</td>";
        echo "<td>{$order['order_number']}</td>";
        echo "<td><strong>{$order['status']}</strong></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<br><br>";
    echo "<a href='/demolitiontraders/frontend/admin/orders.php'>← Back to Admin Orders</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
