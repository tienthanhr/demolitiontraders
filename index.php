<?php

// Serve static files correctly
if (php_sapi_name() === 'cli-server') {
    $path = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($path)) {
        return false;
    }
}

// Load frontend homepage
require_once __DIR__ . '/frontend/user/index.php';
