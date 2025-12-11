<?php
require_once __DIR__ . '/../backend/config/database.php';

$password = 'ChangeMe!123';
$hash = password_hash($password, PASSWORD_DEFAULT);
$db = Database::getInstance();
$db->update('users', ['password' => $hash], 'id = :id', ['id' => 1]);

echo "Password for admin@demolitiontraders.co.nz updated to: $password\n";
