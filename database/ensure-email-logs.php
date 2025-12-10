<?php
/**
 * Ensure email_logs table exists by running the SQL in database/add-email-logs.sql
 * Run: php database/ensure-email-logs.php
 */
if (php_sapi_name() !== 'cli') {
    die("This script must be run from CLI\n");
}
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/run_sql.php';

$sqlFile = __DIR__ . '/add-email-logs.sql';
echo "Ensuring email_logs table exists using: $sqlFile\n";
// Call run_sql script programmatically
exec("php " . escapeshellarg(__DIR__ . '/run_sql.php') . " " . escapeshellarg($sqlFile), $output, $ret);
foreach ($output as $line) echo $line . PHP_EOL;
exit($ret);
