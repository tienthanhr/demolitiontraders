<?php
// Simple migration runner for add-email-timestamps.sql
// Usage: php -f backend/scripts/run_add_email_timestamps.php

require_once __DIR__ . '/../config/database.php';
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    echo "Failed to connect to DB: " . $e->getMessage() . "\n";
    exit(1);
}

$sqlFile = __DIR__ . '/../../database/add-email-timestamps.sql';
if (!file_exists($sqlFile)) {
    echo "Migration file not found: $sqlFile\n";
    exit(1);
}

// We'll apply columns individually and avoid duplicate errors by first checking if they already exist
$columnsToAdd = [
    'tax_invoice_sent_at' => "ALTER TABLE orders ADD COLUMN tax_invoice_sent_at DATETIME NULL DEFAULT NULL",
    'receipt_sent_at' => "ALTER TABLE orders ADD COLUMN receipt_sent_at DATETIME NULL DEFAULT NULL",
];
$errors = 0;
foreach ($columnsToAdd as $col => $alterSql) {
    try {
        // Use a raw string query; PDO wrapper may not support placeholders in SHOW statements in all environments
        $colEscaped = addslashes($col);
        $row = $db->fetchOne("SHOW COLUMNS FROM orders LIKE '$colEscaped'");
        if (!empty($row)) {
            echo "SKIP: Column $col already exists\n";
            continue;
        }
    } catch (Exception $ex) {
        // Could not query; proceed to try adding, wrapping in try/catch
        echo "WARN: Could not check column $col (will try to add): " . $ex->getMessage() . "\n";
    }
    try {
        $db->query($alterSql);
        echo "OK: Added column $col\n";
    } catch (Exception $ex) {
        echo "ERROR: Failed to add $col: " . $ex->getMessage() . "\n";
        $errors++;
    }
}

if ($errors === 0) {
    echo "Migration complete: Successfully applied all statements.\n";
    exit(0);
} else {
    echo "Migration complete: $errors errors encountered.\n";
    exit(2);
}
