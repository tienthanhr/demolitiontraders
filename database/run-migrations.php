<?php
/**
 * Run all sql migration files found in the database folder.
 * Usage: php database/run-migrations.php
 */
if (php_sapi_name() !== 'cli') {
    die("This script must be run from CLI\n");
}
require_once __DIR__ . '/../backend/config/database.php';

$files = glob(__DIR__ . '/*.sql');
sort($files);
if (!$files) {
    echo "No SQL files found in database/\n";
    exit(0);
}

foreach ($files as $f) {
    // Skip the large schema-postgresql which is for Postgres import
    if (strpos($f, 'schema-postgresql.sql') !== false) continue;
    echo "Running migration: " . basename($f) . "\n";
    $r = null;
    $cmd = "php " . escapeshellarg(__DIR__ . '/run_sql.php') . " " . escapeshellarg($f);
    passthru($cmd, $r);
    if ($r !== 0) {
        echo "Migration failed for file: $f (exit code $r)\n";
    }
}
echo "All migrations complete.\n";
exit(0);
