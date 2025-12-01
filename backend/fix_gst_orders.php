<?php
/**
 * Fix GST Calculation for Existing Orders
 * 
 * This script recalculates GST for all existing orders where prices already include GST.
 * 
 * OLD (WRONG) Calculation:
 * - Subtotal = price * qty (treating as excl GST)
 * - Tax = Subtotal * 0.15
 * - Total = Subtotal + Tax (double counting GST!)
 * 
 * NEW (CORRECT) Calculation:
 * - Total Incl GST = price * qty (prices already include GST)
 * - Subtotal Excl GST = Total Incl GST / 1.15
 * - Tax = Subtotal Excl GST * 0.15
 * - Total = Total Incl GST
 */

require_once __DIR__ . '/config/database.php';

// Get database connection
$db = Database::getInstance()->getConnection();

echo "========================================\n";
echo "FIX GST CALCULATION FOR EXISTING ORDERS\n";
echo "========================================\n\n";

// Get all orders
$stmt = $db->query("SELECT * FROM orders ORDER BY id");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($orders) . " orders to check\n\n";

$fixedCount = 0;
$skippedCount = 0;

foreach ($orders as $order) {
    echo "Order #{$order['id']} ({$order['order_number']})\n";
    echo "  Current values:\n";
    echo "    Subtotal: $" . number_format($order['subtotal'], 2) . "\n";
    echo "    Tax: $" . number_format($order['tax_amount'], 2) . "\n";
    echo "    Total: $" . number_format($order['total_amount'], 2) . "\n";
    
    // Get order items to recalculate
    $itemsStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemsStmt->execute([$order['id']]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate correct values
    // Sum of all items (prices already include GST)
    $totalInclGST = 0;
    foreach ($items as $item) {
        $totalInclGST += $item['unit_price'] * $item['quantity'];
    }
    
    // Add shipping and subtract discount
    $totalInclGST += floatval($order['shipping_amount']);
    $totalInclGST -= floatval($order['discount_amount']);
    
    // Calculate GST component
    $newSubtotal = $totalInclGST / 1.15; // Excl GST
    $newTax = $newSubtotal * 0.15; // GST amount
    $newTotal = $totalInclGST; // Total incl GST
    
    echo "  Recalculated values:\n";
    echo "    Subtotal (excl GST): $" . number_format($newSubtotal, 2) . "\n";
    echo "    Tax (15%): $" . number_format($newTax, 2) . "\n";
    echo "    Total (incl GST): $" . number_format($newTotal, 2) . "\n";
    
    // Check if update is needed (allow small floating point differences)
    $needsUpdate = (
        abs($order['subtotal'] - $newSubtotal) > 0.01 ||
        abs($order['tax_amount'] - $newTax) > 0.01 ||
        abs($order['total_amount'] - $newTotal) > 0.01
    );
    
    if ($needsUpdate) {
        echo "  ⚠️  VALUES DIFFER - Updating...\n";
        
        // Update order
        $updateStmt = $db->prepare("
            UPDATE orders 
            SET subtotal = ?, 
                tax_amount = ?, 
                total_amount = ?
            WHERE id = ?
        ");
        
        $updateStmt->execute([
            $newSubtotal,
            $newTax,
            $newTotal,
            $order['id']
        ]);
        
        // Update order items
        foreach ($items as $item) {
            $itemTotalInclGST = $item['unit_price'] * $item['quantity'];
            $itemSubtotal = $itemTotalInclGST / 1.15;
            $itemTax = $itemSubtotal * 0.15;
            
            $updateItemStmt = $db->prepare("
                UPDATE order_items 
                SET subtotal = ?, 
                    tax_amount = ?, 
                    total = ?
                WHERE id = ?
            ");
            
            $updateItemStmt->execute([
                $itemSubtotal,
                $itemTax,
                $itemTotalInclGST,
                $item['id']
            ]);
        }
        
        echo "  ✅ FIXED!\n";
        $fixedCount++;
    } else {
        echo "  ✓ Already correct\n";
        $skippedCount++;
    }
    
    echo "\n";
}

echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Total orders checked: " . count($orders) . "\n";
echo "Orders fixed: $fixedCount\n";
echo "Orders skipped (already correct): $skippedCount\n";
echo "\n";
echo "✅ GST fix completed!\n";
echo "\n";
echo "NOTE: This script fixed the GST calculation for existing orders.\n";
echo "All new orders will use the corrected calculation automatically.\n";
