<?php
/**
 * Fix Admin Password
 * Updates admin password to "admin123"
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/backend/config/database.php';

echo "<h1>Fix Admin Password</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .result { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .success { border-left: 4px solid #4caf50; }
    pre { background: #f5f5f5; padding: 10px; overflow: auto; }
</style>";

try {
    $db = Database::getInstance();
    
    // Generate new password hash
    $newPassword = 'admin123';
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    echo "<div class='result'>";
    echo "<h3>Generating New Password Hash</h3>";
    echo "<p><strong>Plain Password:</strong> {$newPassword}</p>";
    echo "<p><strong>New Hash:</strong></p>";
    echo "<pre>{$hashedPassword}</pre>";
    
    // Update admin password
    $result = $db->query(
        "UPDATE users SET password = :password WHERE email = :email",
        [
            'password' => $hashedPassword,
            'email' => 'admin@demolitiontraders.co.nz'
        ]
    );
    
    echo "<p style='color: green; font-size: 18px;'>✓ Password updated successfully!</p>";
    echo "</div>";
    
    // Verify the update
    echo "<div class='result success'>";
    echo "<h3>Verification Test</h3>";
    
    $user = $db->fetchOne(
        "SELECT password FROM users WHERE email = :email",
        ['email' => 'admin@demolitiontraders.co.nz']
    );
    
    $isValid = password_verify('admin123', $user['password']);
    
    if ($isValid) {
        echo "<p style='color: green; font-size: 18px;'><strong>✓ Password verification SUCCESS!</strong></p>";
        echo "<p>You can now login with:</p>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> admin@demolitiontraders.co.nz</li>";
        echo "<li><strong>Password:</strong> admin123</li>";
        echo "</ul>";
        echo "<p><a href='/demolitiontraders/frontend/admin-login.php' style='display: inline-block; background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Go to Admin Login</a></p>";
    } else {
        echo "<p style='color: red;'>✗ Verification failed</p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='result' style='border-left-color: #f44336;'>";
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
