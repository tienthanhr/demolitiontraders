<?php
/**
 * URL Helper Functions
 * Automatically handles HTTP/HTTPS protocol
 */

// Get current protocol
function get_protocol() {
    return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
}

// Get base URL with protocol
function get_base_url() {
    $protocol = get_protocol();
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/demolitiontraders';
}

// Get API URL
function get_api_url($endpoint = '') {
    return get_base_url() . '/backend/api/' . ltrim($endpoint, '/');
}

// Make URL protocol-aware
function make_url_secure($url) {
    // If URL already has protocol
    if (preg_match('/^https?:\/\//', $url)) {
        // Force HTTPS if current page is HTTPS
        if (get_protocol() === 'https') {
            return preg_replace('/^http:/', 'https:', $url);
        }
        return $url;
    }
    
    // If URL is protocol-relative (//)
    if (strpos($url, '//') === 0) {
        return $url;
    }
    
    // If URL is relative
    if (strpos($url, '/') === 0) {
        return $url;
    }
    
    return $url;
}

// Output base URL for JavaScript
function output_base_url_js() {
    echo '<script>window.BASE_URL = "' . get_base_url() . '";</script>';
}
