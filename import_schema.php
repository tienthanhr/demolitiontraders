<?php
/**
 * Database Import Script
 * This script imports the PostgreSQL dump into the fly.io database
 */

// Read the converted SQL dump file
$sqlFile = __DIR__ . '/demolitiontraders_pg.sql';

if (!file_exists($sqlFile)) {
    die("Error: SQL dump file not found at $sqlFile\n");
}

echo "Reading SQL dump file...\n";
$sqlContent = file_get_contents($sqlFile);

// Get database connection from environment
$dbUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

if (!$dbUrl) {
    echo "Warning: DATABASE_URL not set in environment. Using local MySQL.\n";
    // Try local connection
    try {
        require_once __DIR__ . '/backend/config/database.php';
    } catch (Exception $e) {
        die("Error: Could not connect to database: " . $e->getMessage() . "\n");
    }
}

echo "Attempting to import SQL schema...\n";
echo "File size: " . formatBytes(strlen($sqlContent)) . "\n";

// Split by semicolon and execute statements
$statements = array_filter(
    array_map('trim', explode(';', $sqlContent)),
    function($stmt) {
        return strlen($stmt) > 0 && !str_starts_with($stmt, '--');
    }
);

echo "Found " . count($statements) . " SQL statements to execute.\n\n";

$count = 0;
$errors = [];

foreach ($statements as $i => $statement) {
    try {
        if (function_exists('PDO')) {
            // Try using PDO if available
            $stmt = $db->prepare($statement);
            $stmt->execute();
            $count++;
            
            if ($count % 50 === 0) {
                echo "✓ Executed $count statements...\n";
            }
        } else {
            echo "Warning: PDO not available\n";
            break;
        }
    } catch (Exception $e) {
        $errors[] = [
            'statement' => substr($statement, 0, 100),
            'error' => $e->getMessage(),
            'line' => $i + 1
        ];
        
        // Log but continue for non-critical errors
        if (strpos($e->getMessage(), 'already exists') === false) {
            echo "⚠ Error in statement " . ($i + 1) . ": " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✓ Import completed!\n";
echo "Successfully executed: $count statements\n";

if (!empty($errors)) {
    echo "Total errors: " . count($errors) . "\n";
    echo "\nFirst 5 errors:\n";
    foreach (array_slice($errors, 0, 5) as $error) {
        echo "- " . $error['statement'] . "...\n  Error: " . $error['error'] . "\n";
    }
}

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}
