<?php
/**
 * API Test Page
 * Check if API endpoints are working
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>API Endpoint Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .test { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .success { border-left: 4px solid #4caf50; }
    .error { border-left: 4px solid #f44336; }
    pre { background: #f5f5f5; padding: 10px; overflow: auto; }
</style>";

// Test endpoints
$tests = [
    'Products List' => '/demolitiontraders/api/products',
    'Categories List' => '/demolitiontraders/api/categories',
    'Cart Get' => '/demolitiontraders/api/cart/get',
];

foreach ($tests as $name => $endpoint) {
    $url = 'http://localhost' . $endpoint;
    
    echo "<div class='test'>";
    echo "<h3>{$name}</h3>";
    echo "<p><strong>URL:</strong> <code>{$url}</code></p>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<div class='error'>";
        echo "<p style='color: red;'>✗ cURL Error: {$error}</p>";
        echo "</div>";
    } else if ($httpCode === 200) {
        echo "<div class='success'>";
        echo "<p style='color: green;'>✓ HTTP {$httpCode} - OK</p>";
        $json = json_decode($response, true);
        echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<p style='color: red;'>✗ HTTP {$httpCode}</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        echo "</div>";
    }
    
    echo "</div>";
}

// Test auth endpoint with POST
echo "<div class='test'>";
echo "<h3>Auth Login (POST)</h3>";
echo "<p><strong>URL:</strong> <code>http://localhost/demolitiontraders/api/auth/login</code></p>";

$loginData = [
    'email' => 'admin@demolitiontraders.co.nz',
    'password' => 'admin123'
];

$ch = curl_init('http://localhost/demolitiontraders/api/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>✗ cURL Error: {$error}</p>";
} else if ($httpCode === 200) {
    echo "<p style='color: green;'>✓ HTTP {$httpCode} - Login OK</p>";
    $json = json_decode($response, true);
    echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<p style='color: red;'>✗ HTTP {$httpCode}</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo "</div>";

// Check .htaccess
echo "<div class='test'>";
echo "<h3>.htaccess Configuration</h3>";

$htaccessFile = __DIR__ . '/.htaccess';
if (file_exists($htaccessFile)) {
    echo "<p style='color: green;'>✓ .htaccess exists</p>";
    echo "<pre>" . htmlspecialchars(file_get_contents($htaccessFile)) . "</pre>";
} else {
    echo "<p style='color: red;'>✗ .htaccess NOT found</p>";
}

echo "</div>";

// Check mod_rewrite
echo "<div class='test'>";
echo "<h3>Apache mod_rewrite</h3>";

if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo "<p style='color: green;'>✓ mod_rewrite is enabled</p>";
    } else {
        echo "<p style='color: red;'>✗ mod_rewrite is NOT enabled</p>";
        echo "<p>Enable it in httpd.conf: LoadModule rewrite_module modules/mod_rewrite.so</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ Cannot check (apache_get_modules not available)</p>";
}

echo "</div>";
?>
