<?php
/**
 * IdealPOS Sync Cron Job
 * Run this script periodically to sync data with IdealPOS
 * 
 * Setup in Windows Task Scheduler:
 * php "C:\xampp\htdocs\demolitiontraders\backend\cron\sync-idealpos.php"
 */

// Load dependencies
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/services/IdealPOSService.php';

// Set execution time limit
set_time_limit(300); // 5 minutes

// Log start
$logFile = dirname(__DIR__, 2) . '/logs/cron-sync.log';
$logDir = dirname($logFile);

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
}

logMessage("=== IdealPOS Sync Started ===");

try {
    $idealpos = new IdealPOSService();
    
    if (!$idealpos->isEnabled()) {
        logMessage("IdealPOS sync is disabled");
        exit;
    }
    
    // Sync products
    logMessage("Starting product sync...");
    $productResult = $idealpos->syncProducts();
    logMessage("Product sync completed: {$productResult['synced']} synced, {$productResult['failed']} failed");
    
    // Sync inventory
    logMessage("Starting inventory sync...");
    $inventoryResult = $idealpos->syncInventory();
    logMessage("Inventory sync completed: {$inventoryResult['synced']} synced, {$inventoryResult['failed']} failed");
    
    // Sync pending orders to POS
    logMessage("Checking for pending orders to sync...");
    $db = Database::getInstance();
    $pendingOrders = $db->fetchAll(
        "SELECT id FROM orders WHERE synced_to_pos = 0 AND status != 'cancelled' LIMIT 10"
    );
    
    $ordersSynced = 0;
    $ordersFailed = 0;
    
    foreach ($pendingOrders as $order) {
        try {
            $idealpos->pushOrder($order['id']);
            $ordersSynced++;
            logMessage("Order {$order['id']} synced successfully");
        } catch (Exception $e) {
            $ordersFailed++;
            logMessage("Order {$order['id']} sync failed: " . $e->getMessage());
        }
    }
    
    logMessage("Orders sync completed: {$ordersSynced} synced, {$ordersFailed} failed");
    logMessage("=== IdealPOS Sync Completed Successfully ===");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    logMessage("=== IdealPOS Sync Failed ===");
}

logMessage("");
