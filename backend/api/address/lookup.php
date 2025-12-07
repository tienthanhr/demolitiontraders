<?php
// Server-side address lookup proxy to avoid CORS issues when calling Nominatim directly.
// Accepts ?q= query string and returns JSON results.

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '' || strlen($q) < 3) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Query too short']);
    exit;
}

$endpoint = 'https://nominatim.openstreetmap.org/search';
$params = http_build_query([
    'format' => 'json',
    'addressdetails' => 1,
    'limit' => 5,
    'countrycodes' => 'nz',
    'q' => $q,
]);
$url = $endpoint . '?' . $params;

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'DemolitionTraders/1.0 (contact: info@demolitiontraders.co.nz)',
    CURLOPT_TIMEOUT => 10,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Lookup request failed', 'detail' => $error]);
    exit;
}

if ($status >= 400) {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => 'Lookup service returned error', 'status' => $status]);
    exit;
}

echo json_encode([
    'success' => true,
    'results' => json_decode($response, true),
]);
