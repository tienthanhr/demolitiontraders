<?php
/**
 * Create or Reset Admin User
 */

require_once __DIR__ . '/backend/config/database.php';

$db = Database::getInstance();

$email = 'admin@demolitiontraders.co.nz';
$password = 'admin123'; // Change this to your desired password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$existing = $db->fetchOne(
    "SELECT id FROM users WHERE email = :email",
    ['email' => $email]
);

if ($existing) {
    // Update existing admin
    $db->update(
        'users',
        [
            'password' => $hashedPassword,
            'role' => 'admin',
            'status' => 'active'
        ],
        'email = :email',
        ['email' => $email]
    );
    echo "✓ Admin password updated successfully!\n";
} else {
    // Create new admin
    $db->insert('users', [
        'email' => $email,
        'password' => $hashedPassword,
        'first_name' => 'Admin',
        'last_name' => 'User',
        'role' => 'admin',
        'status' => 'active'
    ]);
    echo "✓ Admin user created successfully!\n";
}

echo "\nLogin credentials:\n";
echo "Email: $email\n";
echo "Password: $password\n";
echo "\nAccess admin panel at: http://localhost/demolitiontraders/frontend/admin-login.php\n";
?>
