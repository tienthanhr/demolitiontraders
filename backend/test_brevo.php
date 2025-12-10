<?php
// Load .env and test Brevo API key
require_once __DIR__ . '/config/email.php';

$key = $_ENV['BREVO_API_KEY'] ?? null;
error_log('[DemolitionTraders] BREVO_API_KEY after config load: ' . ($key ?: 'NULL'));

if ($key) {
    // Test Brevo API endpoint
    $ch = curl_init('https://api.brevo.com/v3/account');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $key,
        'accept: application/json',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    error_log('[DemolitionTraders] Brevo API response code: ' . $httpCode);
    error_log('[DemolitionTraders] Brevo API response: ' . $response);
    echo 'Brevo API response code: ' . $httpCode . '<br>Response: ' . htmlspecialchars($response);
} else {
    echo 'BREVO_API_KEY not loaded.';
}
