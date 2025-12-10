<?php
// Simple test for PHP error logging and environment variable loading
error_log('[DemolitionTraders] test.php loaded at ' . date('Y-m-d H:i:s'));
error_log('[DemolitionTraders] BREVO_API_KEY: ' . (getenv('BREVO_API_KEY') ?: 'NULL'));
echo 'Test script executed. Check your PHP error log.';
