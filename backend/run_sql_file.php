<?php
require_once __DIR__ . '/config/database.php';
$file = $argv[1] ?? null;
if (!$file || !file_exists($file)) {
    echo "Usage: php run_sql_file.php path/to/file.sql\n";
    exit(1);
}
$content = file_get_contents($file);
$db = Database::getInstance();
$connection = $db->getConnection();
try {
    // Split by semicolons - naive but OK for our simple schema files
    $queries = array_filter(array_map('trim', explode(';', $content)));
    foreach ($queries as $query) {
        if (strlen($query) > 0) {
            $db->query($query);
            echo "Executed query: " . substr($query, 0, 80) . "...\n";
        }
    }
    echo "Migration applied: $file\n";
} catch (Exception $e) {
    echo "Error applying migration: " . $e->getMessage() . "\n";
}
