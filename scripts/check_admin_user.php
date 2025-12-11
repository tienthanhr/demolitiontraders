<?php
require_once __DIR__ . '/../backend/config/database.php';
$db = Database::getInstance();
$admin = $db->fetchOne("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
var_dump($admin);
