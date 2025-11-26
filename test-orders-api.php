<?php
session_start();

// Set admin session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['user_role'] = 'admin';
$_SESSION['is_admin'] = true;
$_SESSION['user_email'] = 'admin@demolitiontraders.co.nz';
$_SESSION['first_name'] = 'Admin';
$_SESSION['last_name'] = 'User';

echo "<h1>Testing Orders API</h1>";
echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Fetching Orders...</h2>";

// Make API call
$url = 'http://localhost/demolitiontraders/backend/api/index.php?request=orders';
$context = stream_context_create([
    'http' => [
        'header' => 'Cookie: ' . session_name() . '=' . session_id()
    ]
]);

$response = file_get_contents($url, false, $context);
echo "<h3>API Response:</h3>";
echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>";

$data = json_decode($response, true);
echo "<h3>Decoded Data:</h3>";
echo "<pre>";
print_r($data);
echo "</pre>";
