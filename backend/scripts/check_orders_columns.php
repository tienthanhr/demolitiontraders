<?php
// Check for existence of tax_invoice_sent_at and receipt_sent_at columns on the orders table
require_once __DIR__ . '/../config/database.php';
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    echo "Failed to connect to DB: " . $e->getMessage() . "\n";
    exit(1);
}

function colExists($db, $col) {
    try {
        $colEscaped = addslashes($col);
        $r = $db->fetchOne("SHOW COLUMNS FROM orders LIKE '$colEscaped'");
        return !empty($r);
    } catch (Exception $e) {
        echo "Query error: " . $e->getMessage() . "\n";
        return false;
    }
}

$cols = [ 'tax_invoice_sent_at', 'receipt_sent_at' ];
$ok = true;
foreach ($cols as $c) {
    echo "$c: ";
    $exists = colExists($db, $c);
    echo ($exists ? "FOUND" : "MISSING") . "\n";
    if (!$exists) $ok = false;
}

if ($ok) {
    echo "OK: All expected columns exist\n";
    exit(0);
} else {
    echo "WARN: Some columns are missing. Run: php -f backend/scripts/run_add_email_timestamps.php\n";
    exit(2);
}
