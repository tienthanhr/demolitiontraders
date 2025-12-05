<?php
require 'backend/config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();
$driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);

echo "Database driver: $driver\n";

if ($driver === 'mysql') {
    $result = $db->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE()");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "Tables in MySQL: " . $row['cnt'] . "\n";
} else if ($driver === 'pgsql') {
    $result = $db->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = 'public'");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "Tables in PostgreSQL: " . $row['cnt'] . "\n";
}
