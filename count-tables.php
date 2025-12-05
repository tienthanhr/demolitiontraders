<?php
require 'backend/config/database.php';

$db = Database::getInstance();
$result = $db->query('SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = \'public\'');
$row = $result->fetch(PDO::FETCH_ASSOC);
echo 'Total tables: ' . $row['cnt'] . "\n";
