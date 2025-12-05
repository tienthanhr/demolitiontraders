<?php
/**
 * Database Schema Import Endpoint
 * Imports the PostgreSQL schema to fly.io database
 */

header('Content-Type: application/json');

try {
    // Get database connection
    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Use the clean PostgreSQL schema file
    $sqlFile = __DIR__ . '/../../schema-for-migration.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Schema file not found: $sqlFile");
    }
    
    echo "Reading PostgreSQL schema file...\n";
    $sqlContent = file_get_contents($sqlFile);
    
    if (!$sqlContent) {
        throw new Exception("Schema file is empty");
    }
    
    echo "Executing schema creation...\n";
    
    // Execute the entire script
    // PostgreSQL allows executing multiple statements with PDO if we use exec or split properly
    $statements = array_filter(
        array_map('trim', preg_split('/;\s*\n/', $sqlContent)),
        function($stmt) {
            return strlen(trim($stmt)) > 0;
        }
    );
    
    echo "Found " . count($statements) . " statements\n";
    
    $count = 0;
    $errors = [];
    
    foreach ($statements as $idx => $stmt) {
        $stmt = trim($stmt);
        if (!$stmt) continue;
        
        // Add semicolon if missing
        if (!str_ends_with($stmt, ';')) {
            $stmt .= ';';
        }
        
        try {
            $db->query($stmt);
            $count++;
            
            if ($count % 5 === 0) {
                echo "✓ Executed $count statements...\n";
            }
        } catch (PDOException $e) {
            $error_msg = $e->getMessage();
            
            // Skip "already exists" errors (idempotent)
            if (stripos($error_msg, 'already exists') !== false ||
                stripos($error_msg, 'duplicate key') !== false ||
                stripos($error_msg, 'duplicate value') !== false) {
                $count++;
                continue;
            }
            
            $errors[] = [
                'line' => $idx + 1,
                'statement' => substr($stmt, 0, 100),
                'error' => $error_msg
            ];
        }
    }
    
    echo "\n✓ Schema import completed!\n";
    echo "Successfully executed: $count / " . count($statements) . " statements\n";
    
    if (!empty($errors)) {
        echo "\nEncountered " . count($errors) . " errors:\n";
        foreach (array_slice($errors, 0, 3) as $error) {
            echo "  Line " . $error['line'] . ": " . $error['statement'] . "\n";
            echo "    → " . substr($error['error'], 0, 100) . "\n";
        }
    }
    
    // Verify tables were created
    try {
        $result = $db->query("SELECT COUNT(*) as table_count FROM information_schema.tables 
                            WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $table_count = $row['table_count'] ?? 0;
        
        echo "\n📊 Tables created: $table_count\n";
    } catch (Exception $e) {
        echo "\nCould not verify tables: " . $e->getMessage() . "\n";
    }
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'statements_executed' => $count,
        'errors' => count($errors),
        'message' => 'Schema imported successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}

