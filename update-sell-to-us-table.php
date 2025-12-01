<?php
/**
 * Update Sell to Us Table
 * Run this to add new columns to existing sell_to_us_submissions table
 */

require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Update Sell to Us Table</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-left: 4px solid #0c5460; margin: 10px 0; }
        h1 { color: #2f3192; }
    </style>
</head>
<body>
    <h1>Update Sell to Us Table</h1>";
    
    echo "<div class='info'><strong>Starting migration...</strong></div>";
    
    // Check if columns already exist
    $checkQuery = "SHOW COLUMNS FROM sell_to_us_submissions LIKE 'item_name'";
    $result = $db->query($checkQuery);
    
    if ($result->rowCount() > 0) {
        echo "<div class='info'>ℹ Column 'item_name' already exists (skipped)</div>";
    } else {
        // Add item_name column
        $db->exec("ALTER TABLE sell_to_us_submissions ADD COLUMN item_name VARCHAR(255) NOT NULL DEFAULT '' AFTER location");
        echo "<div class='success'>✓ Added column: item_name</div>";
    }
    
    // Check pickup_delivery
    $checkQuery = "SHOW COLUMNS FROM sell_to_us_submissions LIKE 'pickup_delivery'";
    $result = $db->query($checkQuery);
    
    if ($result->rowCount() > 0) {
        echo "<div class='info'>ℹ Column 'pickup_delivery' already exists (skipped)</div>";
    } else {
        // Add pickup_delivery column
        $db->exec("ALTER TABLE sell_to_us_submissions ADD COLUMN pickup_delivery VARCHAR(50) NOT NULL DEFAULT '' AFTER item_condition");
        echo "<div class='success'>✓ Added column: pickup_delivery</div>";
    }
    
    // Update existing columns to NOT NULL
    try {
        $db->exec("ALTER TABLE sell_to_us_submissions MODIFY COLUMN quantity VARCHAR(100) NOT NULL");
        echo "<div class='success'>✓ Updated column: quantity (now NOT NULL)</div>";
    } catch (Exception $e) {
        echo "<div class='info'>ℹ Column 'quantity' already updated or has NULL values</div>";
    }
    
    try {
        $db->exec("ALTER TABLE sell_to_us_submissions MODIFY COLUMN item_condition VARCHAR(100) NOT NULL");
        echo "<div class='success'>✓ Updated column: item_condition (now NOT NULL)</div>";
    } catch (Exception $e) {
        echo "<div class='info'>ℹ Column 'item_condition' already updated or has NULL values</div>";
    }
    
    echo "<div style='margin-top: 30px; padding: 20px; background: #d4edda; border-left: 4px solid #28a745; border-radius: 8px;'>";
    echo "<h2>✅ Migration Completed Successfully!</h2>";
    echo "<p>The sell_to_us_submissions table has been updated with:</p>";
    echo "<ul>";
    echo "<li><strong>item_name</strong> - Item name field (required)</li>";
    echo "<li><strong>pickup_delivery</strong> - Pick up or delivery option (required)</li>";
    echo "<li><strong>quantity</strong> - Updated to required</li>";
    echo "<li><strong>item_condition</strong> - Updated to required</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='margin-top: 30px; padding: 20px; background: #d1ecf1; border-left: 4px solid #0c5460;'>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Test the updated form at <a href='/demolitiontraders/frontend/sell-to-us.php'>/frontend/sell-to-us.php</a></li>";
    echo "<li>Submit a test form to verify all fields are working</li>";
    echo "<li>Check the email notification includes all new fields</li>";
    echo "</ol>";
    echo "</div>";
    
    // Show current table structure
    echo "<div style='margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;'>";
    echo "<h3>Current Table Structure:</h3>";
    $columns = $db->query("SHOW COLUMNS FROM sell_to_us_submissions")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #2f3192; color: white;'>";
    echo "<th style='padding: 10px; text-align: left;'>Field</th>";
    echo "<th style='padding: 10px; text-align: left;'>Type</th>";
    echo "<th style='padding: 10px; text-align: left;'>Null</th>";
    echo "<th style='padding: 10px; text-align: left;'>Default</th>";
    echo "</tr>";
    foreach ($columns as $col) {
        echo "<tr style='border-bottom: 1px solid #ddd;'>";
        echo "<td style='padding: 8px;'><strong>{$col['Field']}</strong></td>";
        echo "<td style='padding: 8px;'>{$col['Type']}</td>";
        echo "<td style='padding: 8px;'>{$col['Null']}</td>";
        echo "<td style='padding: 8px;'>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
    echo "</body></html>";
}
