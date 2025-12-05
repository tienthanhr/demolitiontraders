<?php
/**
 * API Rate Limiting Middleware
 *
 * This script limits the number of API requests per IP address to prevent abuse.
 * It uses a simple file-based storage system.
 */

// --- Configuration ---
const RATE_LIMIT_PERIOD = 60; // Time window in seconds (1 minute)
const RATE_LIMIT_MAX_REQUESTS = 60; // Max requests per period
const RATE_LIMIT_LOG_DIR = __DIR__ . '/../logs/rate_limit/';

// --- Rate Limiting Logic ---

/**
 * Checks if the current request is allowed based on the client's IP address.
 * If the limit is exceeded, it sends a 429 response and exits.
 */
function apply_rate_limit() {
    // Do not rate limit if running from CLI (e.g., for scripts)
    if (php_sapi_name() === 'cli') {
        return;
    }

    // Bypass for logged-in admins
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        return;
    }

    // Ensure the log directory exists and is writable
    if (!is_dir(RATE_LIMIT_LOG_DIR)) {
        // Attempt to create it. Suppress errors if it fails.
        @mkdir(RATE_LIMIT_LOG_DIR, 0755, true);
    }

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown_ip';
    // Sanitize IP to create a safe filename
    $ipFile = RATE_LIMIT_LOG_DIR . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $ipAddress) . '.json';

    $currentTime = time();
    $requests = [];

    // Read the request log for this IP
    if (file_exists($ipFile)) {
        $data = json_decode(file_get_contents($ipFile), true);
        if (is_array($data)) {
            $requests = $data;
        }
    }

    // Filter out requests that are older than the time window
    $requests = array_filter($requests, function($timestamp) use ($currentTime) {
        return ($currentTime - $timestamp) < RATE_LIMIT_PERIOD;
    });

    // Check if the number of recent requests exceeds the limit
    if (count($requests) >= RATE_LIMIT_MAX_REQUESTS) {
        // Limit exceeded, send 429 response
        http_response_code(429);
        header('Content-Type: application/json');
        header('Retry-After: ' . RATE_LIMIT_PERIOD); // Inform client when they can retry
        echo json_encode([
            'error' => 'Too Many Requests',
            'message' => 'You have exceeded the API request limit. Please try again later.'
        ]);
        exit;
    }

    // The request is allowed. Log the current request timestamp.
    $requests[] = $currentTime;
    file_put_contents($ipFile, json_encode($requests));

    // Cleanup old log files occasionally to prevent the directory from filling up
    // This runs with a 1% probability on any given request
    if (rand(1, 100) === 1) {
        cleanup_old_logs();
    }
}

/**
 * Deletes log files that haven't been modified in a while.
 */
function cleanup_old_logs() {
    $files = glob(RATE_LIMIT_LOG_DIR . '*.json');
    if (!$files) return;

    $currentTime = time();
    // Delete files that are older than 5 times the rate limit period (e.g., 5 minutes)
    $maxAge = RATE_LIMIT_PERIOD * 5;

    foreach ($files as $file) {
        if (is_file($file) && ($currentTime - filemtime($file)) > $maxAge) {
            @unlink($file);
        }
    }
}

// Automatically apply the rate limit when this file is included.
apply_rate_limit();
