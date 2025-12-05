<?php
/**
 * Quick database schema import CLI script
 * Run: php database/import-schema-cli.php
 */

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from CLI\n");
}

echo "Demolition Traders - PostgreSQL Schema Importer\n";
echo "==============================================\n\n";

try {
    // Load database config
    require_once __DIR__ . '/../backend/config/database.php';
    
    // Get database instance
    $db = Database::getInstance();
    
    // Path to schema file
    $schemaFile = __DIR__ . '/schema-postgresql.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    echo "📂 Reading schema from: $schemaFile\n";
    $sqlContent = file_get_contents($schemaFile);
    
    if (!$sqlContent) {
        throw new Exception("Schema file is empty");
    }
    
    // Split statements properly
    $statements = preg_split('/;\s*\n/', $sqlContent);
    $statements = array_filter(array_map('trim', $statements));
    
    echo "📊 Found " . count($statements) . " SQL statements\n\n";
    
    $executed = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($statements as $idx => $stmt) {
        $stmt = trim($stmt);
        if (!$stmt) continue;
        
        // Add semicolon if missing
        if (!str_ends_with($stmt, ';')) {
            $stmt .= ';';
        }
        
        try {
            // Extract statement type and name for logging
            $stmtType = 'UNKNOWN';
            if (preg_match('/^(CREATE|INSERT|UPDATE|DELETE|DROP|ALTER)\s+(\w+)/i', $stmt, $matches)) {
                $stmtType = $matches[1];
            }
            
            $db->query($stmt);
            $executed++;
            
            if ($executed % 10 === 0 || $stmtType === 'CREATE') {
                echo "✓ [$executed] $stmtType statement executed\n";
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // Skip "already exists" and duplicate key errors (idempotent)
            if (stripos($errorMsg, 'already exists') !== false ||
                stripos($errorMsg, 'duplicate key') !== false ||
                stripos($errorMsg, 'duplicate value') !== false) {
                $skipped++;
                continue;
            }
            
            $errors[] = [
                'line' => $idx + 1,
                'statement' => substr($stmt, 0, 80),
                'error' => $errorMsg
            ];
            
            echo "⚠  Error in statement " . ($idx + 1) . ": " . substr($errorMsg, 0, 100) . "...\n";
        }
    }
    
    echo "\n" . str_repeat("=", 45) . "\n";
    echo "✓ IMPORT COMPLETED!\n";
    echo str_repeat("=", 45) . "\n\n";
    
    echo "📈 Results:\n";
    echo "  ✓ Executed: $executed statements\n";
    echo "  ⊘ Skipped: $skipped statements (already exist)\n";
    
    if (!empty($errors)) {
        echo "  ✗ Errors: " . count($errors) . " statements\n\n";
        echo "First 3 errors:\n";
        foreach (array_slice($errors, 0, 3) as $error) {
            echo "  • Line {$error['line']}: {$error['statement']}...\n";
            echo "    → {$error['error']}\n";
        }
    } else {
        echo "  ✗ Errors: 0\n";
    }
    
    // Verify tables
    echo "\n📊 Verifying tables...\n";
    try {
        $result = $db->query("SELECT COUNT(*) as cnt FROM information_schema.tables 
                            WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $tableCount = $row['cnt'] ?? 0;
        
        echo "  ✓ Tables in database: $tableCount\n";
        
        if ($tableCount > 0) {
            $result = $db->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
            $tables = $result->fetchAll(PDO::FETCH_COLUMN);
            
            echo "\n  Tables created:\n";
            foreach ($tables as $table) {
                echo "    • $table\n";
            }
        }
    } catch (Exception $e) {
        echo "  ⚠  Could not verify tables: " . $e->getMessage() . "\n";
    }
    
    echo "\n✓ All done!\n";
    exit(0);
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line " . $e->getLine() . ")\n";
    exit(1);
}
