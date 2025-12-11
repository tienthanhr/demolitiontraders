<?php
// site-admin dispatcher: map /site-admin/... friendly path to real admin files
// Example: /site-admin/index.php -> includes admin/index.php
// Example: /site-admin/products.php -> includes admin/products.php

// Sanitize requested path, default to index.php
$requested = $_GET['path'] ?? 'index.php';
// If the request starts with "admin/", remove the leading admin/ so we map to files inside the admin folder properly
if (preg_match('#^admin/(.+)$#', $requested, $m)) {
	$requested = $m[1];
}
// Remove query arguments if any present
$requested = preg_replace('#[^a-zA-Z0-9_\-\/\.]+#', '', $requested);
// Prevent directory traversal
if (strpos($requested, '..') !== false) {
	http_response_code(400);
	echo "Invalid request";
	exit;
}

$adminFile = __DIR__ . '/admin/' . ltrim($requested, '/');
if (!file_exists($adminFile)) {
	// fallback to index.php
	$adminFile = __DIR__ . '/admin/index.php';
}

chdir(__DIR__ . '/admin');
require_once $adminFile;
