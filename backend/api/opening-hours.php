<?php
/**
 * Google Places API - Get Opening Hours
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Force NZ timezone so "today" lines up with the yard's hours even on UTC hosts (e.g., Railway)
date_default_timezone_set('Pacific/Auckland');

$placeId = "ChIJc1ALl_ohbW0RmiN-1qTBniI";
$apiKey = "AIzaSyBJPrspkS9hp6p1_iD3LIJU7V72PY32pZk";

$url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=opening_hours,business_status&key={$apiKey}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if (isset($data['result']['opening_hours'])) {
        $hours = $data['result']['opening_hours'];
        
        // Get current day status
        $isOpenNow = $hours['open_now'] ?? false;
        $weekdayText = $hours['weekday_text'] ?? [];
        
        // Get today's hours
        $today = date('w'); // 0 (Sunday) to 6 (Saturday)
        $todayHours = '';
        
        if (!empty($weekdayText)) {
            // Google returns Monday first, adjust index
            $adjustedIndex = ($today + 6) % 7;
            $todayHours = $weekdayText[$adjustedIndex] ?? '';
        }
        
        echo json_encode([
            'success' => true,
            'open_now' => $isOpenNow,
            'today_hours' => $todayHours,
            'weekday_text' => $weekdayText,
            'business_status' => $data['result']['business_status'] ?? 'UNKNOWN'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Opening hours not available'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch data from Google Places API'
    ]);
}
