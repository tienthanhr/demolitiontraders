<?php
/**
 * Create Admin User Script
 *
 * This script is intended to be run from the command line (CLI).
 * Usage: php create_admin.php <email> <password> <first_name> <last_name>
 */

// This script should only be run from the command line.
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

// Manually include necessary files
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

// Check for correct number of arguments
if ($argc < 5) {
    echo "Usage: php " . basename(__FILE__) . " <email> <password> <first_name> <last_name>\n";
    exit(1);
}

// Assign arguments to variables
$email = $argv[1];
$password = $argv[2];
$firstName = $argv[3];
$lastName = $argv[4];

// --- Validation ---

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Error: Invalid email format.\n";
    exit(1);
}

// Validate password strength
if (strlen($password) < 8) {
    echo "Error: Password must be at least 8 characters long.\n";
    exit(1);
}

try {
    $db = Database::getInstance();

    // Check if user already exists
    $existing = $db->fetchOne("SELECT id FROM users WHERE email = :email", ['email' => $email]);
    if ($existing) {
        echo "Error: A user with the email '{$email}' already exists.\n";
        exit(1);
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new admin user
    $userId = $db->insert('users', [
        'email' => $email,
        'password' => $hashedPassword,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'role' => 'admin',
        'status' => 'active'
    ]);

    if ($userId) {
        echo "Successfully created admin user:\n";
        echo "  ID:         {$userId}\n";
        echo "  Email:      {$email}\n";
        echo "  Name:       {$firstName} {$lastName}\n";
        echo "  Role:       admin\n";
    } else {
        echo "Error: Failed to create admin user in the database.\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
    exit(1);
}
