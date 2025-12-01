<?php
/**
 * Import Contact, Wanted Listing and Sell to Us Tables
 * Run this file once to create the necessary database tables
 */

require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/database/contact_wanted_selltous_tables.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Import - Contact Forms</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-left: 4px solid #0c5460; margin: 10px 0; }
        h1 { color: #2f3192; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Database Import - Contact Forms Tables</h1>";
    
    echo "<div class='info'><strong>Starting import...</strong></div>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $db->exec($statement);
            
            // Extract table name from CREATE TABLE statement
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[1];
                echo "<div class='success'>✓ Created table: <strong>$tableName</strong></div>";
                $successCount++;
            }
        } catch (PDOException $e) {
            // Check if error is because table already exists
            if (strpos($e->getMessage(), 'already exists') !== false) {
                if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    $tableName = $matches[1];
                    echo "<div class='info'>ℹ Table already exists: <strong>$tableName</strong> (skipped)</div>";
                }
            } else {
                echo "<div class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                $errorCount++;
            }
        }
    }
    
    echo "<div style='margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;'>";
    echo "<h2>Import Summary</h2>";
    echo "<p><strong>Tables processed successfully:</strong> $successCount</p>";
    if ($errorCount > 0) {
        echo "<p style='color: #dc3545;'><strong>Errors encountered:</strong> $errorCount</p>";
    } else {
        echo "<p style='color: #28a745;'><strong>No errors!</strong> All tables created successfully.</p>";
    }
    echo "</div>";
    
    echo "<div style='margin-top: 30px; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107;'>";
    echo "<h3>Tables Created:</h3>";
    echo "<ul>";
    echo "<li><strong>contact_submissions</strong> - Stores contact form submissions</li>";
    echo "<li><strong>sell_to_us_submissions</strong> - Stores sell-to-us form submissions with photo uploads</li>";
    echo "<li><strong>wanted_listings</strong> - Stores wanted item requests from customers</li>";
    echo "<li><strong>wanted_listing_matches</strong> - Tracks matches between wanted listings and products</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='margin-top: 30px; padding: 20px; background: #d1ecf1; border-left: 4px solid #0c5460;'>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Test the contact form at <a href='/demolitiontraders/frontend/contact.php'>/frontend/contact.php</a></li>";
    echo "<li>Test the sell-to-us form at <a href='/demolitiontraders/frontend/sell-to-us.php'>/frontend/sell-to-us.php</a></li>";
    echo "<li>Test the wanted listing form at <a href='/demolitiontraders/frontend/wanted-listing.php'>/frontend/wanted-listing.php</a></li>";
    echo "<li>Check your email inbox for notifications</li>";
    echo "<li>Review admin email configuration in backend/config/email.php</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>Fatal Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
    echo "</body></html>";
    exit;
}
