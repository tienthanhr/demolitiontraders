<?php
require 'backend/config/database.php';

$email = 'nguyenthanh123426@gmail.com'; // Email from error

$db = Database::getInstance();

$user = $db->fetchOne(
    "SELECT id, email, first_name, last_name, role, status, created_at FROM users WHERE email = :email",
    ['email' => $email]
);

if ($user) {
    echo "User found:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Name: " . $user['first_name'] . " " . $user['last_name'] . "\n";
    echo "Role: " . $user['role'] . "\n";
    echo "Status: " . $user['status'] . "\n";
    echo "Created: " . $user['created_at'] . "\n";
    
    if ($user['status'] !== 'active') {
        echo "\n⚠️ WARNING: User status is '" . $user['status'] . "' - must be 'active' to login!\n";
    }
} else {
    echo "User not found with email: $email\n";
}
