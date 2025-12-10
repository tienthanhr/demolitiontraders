<?php
/**
 * Simple SQL runner - executes statements from a given SQL file.
 * Usage: php database/run_sql.php path/to/file.sql
 */
if (php_sapi_name() !== 'cli') {
    die("This script must be run from CLI\n");
}

require_once __DIR__ . '/../backend/config/database.php';

$sqlFile = $argv[1] ?? null;
if (!$sqlFile || !file_exists($sqlFile)) {
    echo "Usage: php database/run_sql.php path/to/sqlfile.sql\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
if (!$sql) {
    echo "SQL file is empty or could not be read: $sqlFile\n";
    exit(1);
}

$db = Database::getInstance();

// Split SQL statements on semicolons but keep multi-line. This is a simple split that should
// work for our small migration files. For more complex files, consider using a SQL parser.
$statements = preg_split('/;\s*\n/', $sql);
$statements = array_filter(array_map('trim', $statements));

echo "Running SQL statements from: $sqlFile\n";
$executed = 0;
$errors = [];
foreach ($statements as $stmt) {
    if (!$stmt) continue;
    try {
        $db->query($stmt);
        $executed++;
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        // Continue on errors so one failing statement doesn't stop other migrations
        echo "Error executing statement: " . substr($e->getMessage(), 0, 200) . "\n";
    }
}

echo "Done. Executed: $executed statements. Errors: " . count($errors) . "\n";
if ($errors) {
    echo "First error: " . $errors[0] . "\n";
}
exit(0);
