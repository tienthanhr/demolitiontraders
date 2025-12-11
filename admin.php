<?php
// Temporary entry to bypass Apache direct folder restriction
// Change working directory to ensure includes resolve as expected
chdir(__DIR__ . '/admin');
require_once __DIR__ . '/admin/index.php';
