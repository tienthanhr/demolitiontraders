<?php
// backend/api/test_insert.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

echo "Testing Database Insert...\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Connected to DB.\n";
    
    // Create a temporary table for testing
    $db->query("CREATE TABLE IF NOT EXISTS test_insert (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "Table test_insert ensured.\n";
    
    // Test insert
    $data = ['name' => 'Test Item ' . time()];
    echo "Inserting data: " . json_encode($data) . "\n";
    
    $id = $db->insert('test_insert', $data);
    
    echo "Insert result ID: " . var_export($id, true) . "\n";
    
    if ($id) {
        echo "SUCCESS: Inserted with ID $id\n";
    } else {
        echo "FAILURE: Insert returned false/null\n";
    }
    
    // Clean up
    // $db->query("DROP TABLE test_insert");
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
