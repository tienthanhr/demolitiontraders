<?php
// backend/api/test_brevo_api.php

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/html');

echo "<h1>Brevo API Test</h1>";

$apiKey = $_GET['key'] ?? getenv('BREVO_API_KEY');
$toEmail = $_GET['to'] ?? 'nguyenthanh123426@gmail.com'; // Default to user's email

if (!$apiKey) {
    die("<p style='color:red'>Error: Missing API Key. Please provide ?key=xkeysib-...</p>");
}

// Mask key for display
$maskedKey = substr($apiKey, 0, 10) . '...' . substr($apiKey, -5);
echo "<p>Testing with API Key: <strong>$maskedKey</strong></p>";
echo "<p>Sending to: <strong>$toEmail</strong></p>";

$url = 'https://api.brevo.com/v3/smtp/email';
$data = [
    'sender' => ['name' => 'Demolition Traders Test', 'email' => 'no-reply@demolitiontraders.co.nz'],
    'to' => [['email' => $toEmail, 'name' => 'Test User']],
    'subject' => 'Test Email via Brevo API (HTTP)',
    'htmlContent' => '<html><body><h1>It Works!</h1><p>This email was sent via Brevo API over HTTP, bypassing SMTP port blocks.</p></body></html>'
];

echo "<h3>Sending Request...</h3>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json',
    'api-key: ' . $apiKey,
    'content-type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<div style='background:#f0f0f0; padding:10px; border:1px solid #ccc'>";
echo "<strong>HTTP Status:</strong> $httpCode<br>";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "<h2 style='color:green'>SUCCESS! Email Sent.</h2>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "<h2 style='color:red'>FAILED</h2>";
    echo "<strong>Curl Error:</strong> $curlError<br>";
    echo "<strong>Response:</strong> <pre>" . htmlspecialchars($response) . "</pre>";
}
echo "</div>";
