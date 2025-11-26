<?php
/**
 * Test Login API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Login API Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .test { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .success { border-left: 4px solid #4caf50; }
    .error { border-left: 4px solid #f44336; }
    pre { background: #f5f5f5; padding: 10px; overflow: auto; }
</style>";

// Test 1: Check if user exists in database
echo "<div class='test'>";
echo "<h3>Step 1: Check Database</h3>";

require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance();
    $user = $db->fetchOne(
        "SELECT id, email, role, status, created_at FROM users WHERE email = :email",
        ['email' => 'admin@demolitiontraders.co.nz']
    );
    
    if ($user) {
        echo "<p style='color: green;'>✓ User found in database</p>";
        echo "<pre>" . json_encode($user, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p style='color: red;'>✗ User NOT found</p>";
        echo "<p><strong>Creating admin user now...</strong></p>";
        
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $db->query(
            "INSERT INTO users (email, password, first_name, last_name, role, status) 
             VALUES (:email, :password, :first_name, :last_name, :role, :status)",
            [
                'email' => 'admin@demolitiontraders.co.nz',
                'password' => $hashedPassword,
                'first_name' => 'Admin',
                'last_name' => 'User',
                'role' => 'admin',
                'status' => 'active'
            ]
        );
        
        echo "<p style='color: green;'>✓ Admin user created!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 2: Test password verification
echo "<div class='test'>";
echo "<h3>Step 2: Test Password Hash</h3>";

try {
    $user = $db->fetchOne(
        "SELECT password FROM users WHERE email = :email",
        ['email' => 'admin@demolitiontraders.co.nz']
    );
    
    if ($user) {
        $testPassword = 'admin123';
        $isValid = password_verify($testPassword, $user['password']);
        
        if ($isValid) {
            echo "<p style='color: green;'>✓ Password verification SUCCESS</p>";
        } else {
            echo "<p style='color: red;'>✗ Password verification FAILED</p>";
            echo "<p>Hash in DB: " . substr($user['password'], 0, 50) . "...</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 3: Test API endpoint
echo "<div class='test'>";
echo "<h3>Step 3: Test API Endpoint</h3>";

$loginData = json_encode([
    'email' => 'admin@demolitiontraders.co.nz',
    'password' => 'admin123'
]);

$ch = curl_init('http://localhost/demolitiontraders/api/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($loginData)
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>✗ cURL Error: {$error}</p>";
} else {
    echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $json = json_decode($response, true);
    if ($json) {
        echo "<p><strong>Parsed JSON:</strong></p>";
        echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT) . "</pre>";
    }
    
    if ($httpCode == 200) {
        echo "<p style='color: green;'>✓ Login API works!</p>";
    } else {
        echo "<p style='color: red;'>✗ Login API returned error</p>";
    }
}

echo "</div>";

// Test 4: Check API routing
echo "<div class='test'>";
echo "<h3>Step 4: Check API Routing</h3>";

$apiIndexFile = __DIR__ . '/backend/api/index.php';
if (file_exists($apiIndexFile)) {
    echo "<p style='color: green;'>✓ API index.php exists</p>";
} else {
    echo "<p style='color: red;'>✗ API index.php NOT found</p>";
}

$authControllerFile = __DIR__ . '/backend/controllers/AuthController.php';
if (file_exists($authControllerFile)) {
    echo "<p style='color: green;'>✓ AuthController.php exists</p>";
} else {
    echo "<p style='color: red;'>✗ AuthController.php NOT found</p>";
}

echo "</div>";
?>
