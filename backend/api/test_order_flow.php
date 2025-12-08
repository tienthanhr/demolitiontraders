<?php
// backend/api/test_order_flow.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EmailService.php';

echo "Testing Order Flow Simulation...\n";
echo "--------------------------------\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "1. DB Connected.\n";
    
    // 2. Start Transaction
    $db->beginTransaction();
    echo "2. Transaction Started.\n";
    
    // 3. Insert Order
    $orderNumber = 'TEST-' . time();
    $orderData = [
        'order_number' => $orderNumber,
        'user_id' => null,
        'guest_email' => 'test@example.com',
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_method' => 'card',
        'subtotal' => 100,
        'tax_amount' => 15,
        'shipping_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 115,
        'billing_address' => json_encode(['first_name' => 'Test', 'last_name' => 'User', 'email' => 'test@example.com']),
        'shipping_address' => json_encode(['first_name' => 'Test', 'last_name' => 'User', 'address' => '123 Test St']),
        'customer_notes' => 'Test Order'
    ];
    
    $orderId = $db->insert('orders', $orderData);
    echo "3. Order Inserted. ID: " . var_export($orderId, true) . "\n";
    
    if (!$orderId) {
        throw new Exception("Failed to insert order");
    }
    
    // 4. Insert Order Item (Dummy)
    // Assuming product_id 1 exists, if not we might fail foreign key constraint.
    // Let's check if we have any product first.
    $product = $db->fetchOne("SELECT id, name, sku, price FROM products LIMIT 1");
    if ($product) {
        $itemData = [
            'order_id' => $orderId,
            'product_id' => $product['id'],
            'sku' => $product['sku'],
            'product_name' => $product['name'],
            'quantity' => 1,
            'unit_price' => $product['price'],
            'subtotal' => $product['price'] / 1.15,
            'tax_amount' => ($product['price'] / 1.15) * 0.15,
            'total' => $product['price']
        ];
        $db->insert('order_items', $itemData);
        echo "4. Order Item Inserted for Product ID {$product['id']}.\n";
    } else {
        echo "4. SKIPPED Order Item (No products found).\n";
    }
    
    // 5. Commit
    $db->commit();
    echo "5. Transaction Committed.\n";
    
    // 6. Test Email Service Instantiation
    echo "6. Testing EmailService...\n";
    $emailService = new EmailService();
    echo "   EmailService Instantiated.\n";
    
    // 7. Test PDF Generation (This is likely the crash point)
    echo "7. Testing PDF Generation...\n";
    
    // Mock order data for PDF
    $mockOrder = array_merge($orderData, ['id' => $orderId, 'created_at' => date('Y-m-d H:i:s')]);
    $mockOrder['items'] = $product ? [$itemData] : [];
    
    // We need to access the private/protected method or just call sendTaxInvoice which calls it.
    // But sendTaxInvoice sends real email. Let's try to just check if mPDF class loads.
    
    if (class_exists('\Mpdf\Mpdf')) {
        echo "   Class \Mpdf\Mpdf exists.\n";
        try {
            $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir()]);
            echo "   \Mpdf\Mpdf instantiated successfully.\n";
        } catch (Throwable $e) {
            echo "   FAILED to instantiate \Mpdf\Mpdf: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   Class \Mpdf\Mpdf NOT FOUND.\n";
    }

    echo "SUCCESS: Test Flow Completed.\n";

} catch (Throwable $e) {
    if (isset($db) && $db->getConnection()->inTransaction()) {
        $db->rollback();
        echo "Transaction Rolled Back.\n";
    }
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
