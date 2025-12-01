<?php
require 'backend/config/database.php';

$db = Database::getInstance();

// Get latest customer
$user = $db->fetchOne(
    "SELECT id, email, password, created_at FROM users WHERE role = 'customer' ORDER BY created_at DESC LIMIT 1"
);

if ($user) {
    echo "Latest customer:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Created: " . $user['created_at'] . "\n";
    echo "Password hash (first 40 chars): " . substr($user['password'], 0, 40) . "...\n\n";
    
    // Test password verification
    $testPasswords = ['Welcome123!', 'welcome123!', 'Welcome123', 'password'];
    
    foreach ($testPasswords as $pwd) {
        $match = password_verify($pwd, $user['password']);
        echo "Test '$pwd': " . ($match ? '✓ MATCH' : '✗ NO MATCH') . "\n";
    }
} else {
    echo "No customer found\n";
}
