<?php
/**
 * Lightweight wrapper for Railway 'run' commands.
 * Usage:
 *   php run_railway.php ensure-email-logs
 *   php run_railway.php run-smtp-debug you@example.com
 */
$root = __DIR__;
$cmd = $argv[1] ?? null;
if (!$cmd) {
    echo "Usage: php run_railway.php <command> [args]\n";
    echo "Commands:\n";
    echo "  ensure-email-logs      - Run database/add-email-logs.sql\n";
    echo "  migrate-all            - Run database/run-migrations.php\n";
    echo "  smtp-debug <email>     - Run backend/test_smtp_debug.php <email>\n";
    exit(0);
}

switch ($cmd) {
    case 'ensure-email-logs':
        $file = $root . '/database/run_sql.php';
        $sql = $root . '/database/add-email-logs.sql';
        passthru("php -f " . escapeshellarg($file) . " " . escapeshellarg($sql), $r);
        exit($r);
    case 'migrate-all':
        $file = $root . '/database/run-migrations.php';
        passthru("php -f " . escapeshellarg($file), $r);
        exit($r);
    case 'smtp-debug':
        $email = $argv[2] ?? null;
        if (!$email) {
            echo "smtp-debug requires an email address\n";
            exit(1);
        }
        $file = $root . '/backend/test_smtp_debug.php';
        passthru("php -f " . escapeshellarg($file) . " " . escapeshellarg($email), $r);
        exit($r);
    default:
        echo "Unknown command: $cmd\n";
        exit(1);
}
