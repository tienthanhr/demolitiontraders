<?php
/**
 * Migrate Condition to Pick Up Date
 * Changes item_condition column to pickup_date (DATE type)
 */

require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Migrate Condition to Pick Up Date</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-left: 4px solid #0c5460; margin: 10px 0; }
        h1 { color: #2f3192; }
    </style>
</head>
<body>
    <h1>Migrate Condition to Pick Up Date</h1>";
    
    echo "<div class='info'><strong>Starting migration...</strong></div>";
    
    // Check if item_condition column exists
    $checkQuery = "SHOW COLUMNS FROM sell_to_us_submissions LIKE 'item_condition'";
    $result = $db->query($checkQuery);
    
    if ($result->rowCount() > 0) {
        // Change item_condition to pickup_date
        $db->exec("ALTER TABLE sell_to_us_submissions CHANGE COLUMN item_condition pickup_date DATE NULL");
        echo "<div class='success'>✓ Changed column: item_condition → pickup_date (DATE)</div>";
    } else {
        // Check if pickup_date already exists
        $checkQuery = "SHOW COLUMNS FROM sell_to_us_submissions LIKE 'pickup_date'";
        $result = $db->query($checkQuery);
        
        if ($result->rowCount() > 0) {
            echo "<div class='info'>ℹ Column 'pickup_date' already exists (migration already completed)</div>";
        } else {
            echo "<div class='error'>✗ Neither item_condition nor pickup_date column found</div>";
        }
    }
    
    echo "<div style='margin-top: 30px; padding: 20px; background: #d4edda; border-left: 4px solid #28a745; border-radius: 8px;'>";
    echo "<h2>✅ Migration Completed!</h2>";
    echo "<p>The sell_to_us_submissions table has been updated:</p>";
    echo "<ul>";
    echo "<li><strong>item_condition</strong> (dropdown) → <strong>pickup_date</strong> (DATE field)</li>";
    echo "<li>Users can now specify when they want items picked up</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='margin-top: 30px; padding: 20px; background: #d1ecf1; border-left: 4px solid #0c5460;'>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Test the form at <a href='/demolitiontraders/frontend/sell-to-us.php'>/frontend/sell-to-us.php</a></li>";
    echo "<li>Verify the date picker works correctly</li>";
    echo "<li>Submit a test form</li>";
    echo "<li>Check email shows the pick up date</li>";
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
        $highlight = ($col['Field'] === 'pickup_date') ? "background: #d4edda;" : "";
        echo "<tr style='border-bottom: 1px solid #ddd; $highlight'>";
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
